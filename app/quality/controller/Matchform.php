<?php
/**
 * Created by PhpStorm.
 * User: zhifang
 * Date: 2018/4/16
 * Time: 14:45
 */

namespace app\quality\controller;

use app\admin\model\Admin;
use app\admin\model\AdminGroup;
use app\archive\model\AtlasCateModel;
use app\contract\model\ContractModel;
use app\quality\model\DivisionControlPointModel;
use app\quality\model\DivisionModel;
use app\quality\model\DivisionUnitModel;
use app\quality\model\QualityFormInfoModel;
use app\admin\model\MessageremindingModel;//消息记录
use think\Exception;
use think\Request;
use think\Session;
use think\Db;
use think\Controller;

/**
 * 在线填报
 * Class Qualityform
 * @package app\quality\model
 */
//用于生成表单的专用控制器，不能继承permission类，否则会出现wkhtmltopdf不能访问表单html的情况
class Matchform extends Controller
{
    protected $divisionControlPointService;
    protected $atlasCateService;
    protected $divisionUnitService;
    protected $qualityFormInfoService;

    public function __construct(Request $request = null)
    {
        $this->divisionControlPointService = new DivisionControlPointModel();
        $this->atlasCateService = new AtlasCateModel();
        $this->divisionUnitService = new DivisionUnitModel();
        $this->qualityFormInfoService = new QualityFormInfoModel();
        parent::__construct($request);
    }

    /**
     * 编辑质量表单
     * @param $cpr_id 控制点
     * @param $currentStep 当前审批步骤
     * @param bool $isView 是否查看
     * @param null $id 表单id
     * @return bool|mixed|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function matchForm($cpr_id)
    {
        //获取模板路径
        //获取控制点信息，组合模板路径

        $norm_template=Db::name('norm_template')->alias('t')
            ->join('norm_controlpoint c', 't.id = c.qualitytemplateid', 'left')
            ->join('quality_division_controlpoint_relation r', 'r.control_id = c.id', 'left')
            ->where(['r.id'=>$cpr_id])
            ->find();
        $template=Db::name('quality_form_info')
            ->where(['DivisionId'=>$norm_template['division_id'],'ControlPointId'=>$norm_template['control_id']])
            ->find();
        $id= $template['id'];
        $qualitytemplateid = $norm_template['qualitytemplateid'];

        if ($qualitytemplateid == 0) {
            return  '控制点未进行模板关联!';
        }

        $cp = $this->divisionControlPointService->with('controlpoint')->where('id', $cpr_id)->find();
        $formPath = ROOT_PATH . 'public' . DS . "data\\form\\aqulityNew\\" . $cp['controlpoint']['code'] . $norm_template['name'] . "下载.html";
        $formPath = iconv('UTF-8', 'GB2312', $formPath);
        if (!file_exists($formPath)) {
            return "模板文件不存在";
        }
        if (!is_null($id)) {
            $_formdata = $this->qualityFormInfoService->where(['id' => $id])->find()['form_data'];
            $formdata = json_encode(unserialize($_formdata));
        }
        $output = $this->setFormInfo($norm_template['division_id']);
        $htmlContent = file_get_contents($formPath);

        $host="http://".$_SERVER['HTTP_HOST'];
        $code=$host."/quality/matchform/matchform?cpr_id=".$cpr_id;
        $htmlContent=    $this->fetch($formPath,
            [   'id'=>$id,
                'divisionId'=>$cp['division_id'],
                'templateId'=>$cp['controlpoint']['qualitytemplateid'],
                'qrcode'=>$code,
                'isInspect'=>$cp['type'],
                'procedureId'=>$cp['ma_division_id'],
                'formName'=>$cp['ControlPoint']['name'],
                'currentStep'=>'',
                'controlPointId'=>$cp['control_id'],
                'isView'=>'null',
                'formData'=>$formdata,
                'JYPName'=>$output['JYPName'],
                'JYPCode'=>$output['JYPCode'],
                'Quantity'=>$output['Quantity'],
                'PileNo'=>$output['PileNo'],
                'Altitude'=>$output['Altitude'],
                'BuildBase'=>$output['BuildBase'],
                'DYName'=>$output['DYName'],
                'DYCode'=>$output['DYCode'],
                'Constructor'=>$output['Constructor'],
                'Supervisor'=>$output['Supervisor'],
                'SectionCode'=>$output['SectionCode'],
                'SectionName'=>'丰宁抽水蓄能电站',
                'ContractCode'=>$output['SectionCode'],
                'FBName'=>$output['FBName'],
                'FBCode'=>$output['FBCode'],
                'DWName'=>$output['DWName'],
                'DWCode'=>$output['DWCode']
            ]);
        //输出模板内容
        //Todo 暂时使用replace替换，后期修改模板使用fetch自定义模板渲染
        $res= Db::name('quality_division_controlpoint_relation')
            ->where(['id'=>$cpr_id])
            ->find();
        $unit_id=$res['division_id'];
        //获取表单基本信息
        $formdata = "";
        if (!is_null($id)) {
            $_formdata = $this->qualityFormInfoService->where(['id' => $id])->find()['form_data'];
            $formdata = json_encode(unserialize($_formdata));
        }
        return $htmlContent;
    }
    /**
     * 设置表单基本信息
     * @param $qualityUnit_id 检验批
     */
    protected function setFormInfo($qualityUnit_id)
    {

        $mod = $this->divisionUnitService->with("Division.Section")->where(['id' => $qualityUnit_id])->find();
        $output = array();
        $output['JYPName'] = $mod['site'];
        $output['JYPCode'] = $output['JJCode'] = $mod['coding'];
        $output['Quantity'] = $mod['quantities'];
        $output['PileNo'] = $mod['pile_number'];
        $output['Altitude'] = $mod['el_start'] . $mod['el_cease'];
        $output['BuildBase'] = $mod['ma_bases'] ? "" : $this->getBuildBaseInfo($mod['ma_bases']) . $mod['su_basis'];
        $output['DYName'] = $mod['Division']['d_name'];
        $output['DYCode'] = $mod['Division']['d_code'];
        //标段信息
        if ($mod['Division']['Section'] != null) {
            $_section = $mod['Division']['Section'];
            $output['Constructor'] = $_section['constructorId'] ? AdminGroup::get($_section['constructorId'])['name'] : "";
            $output ['Supervisor'] = $_section['supervisorId'] ? AdminGroup::get($_section['supervisorId'])['name'] : "";
            $output ['SectionCode'] = $_section['code'];
            $output['SectionName'] = "丰宁抽水蓄能电站";
            $output['ContractCode'] = $_section['contractId'] ? ContractModel::get($_section['contractId'])['contractName'] : "";
        }
        $Info = $this->getDivsionInfo($mod['division_id']);
        $output['FBName'] = $Info['FB']['d_name'];
        $output['FBCode'] = $Info['FB']['d_code'];
        $output['DWName'] = $Info['DW']['d_name'];
        $output['DWCode'] = $Info['DW']['d_code'];
        return $output;
    }
    /**
     * 表单附件
     * @param $divisionId
     * @param $procedureId
     * @param $controlPointId
     * @return mixed
     */
    /**
     * 获取工程依据信息
     * @param $ids
     * @return string
     */
    function getBuildBaseInfo($ids)
    {
        $idArr = explode(',', $ids);
        $_str = "";
        foreach ($this->atlasCateService->whereIn('id', $idArr) as $item) {
            $_str .= $item['picture_name'] . "({$item['picture_number']})";
        }
        return $_str;
    }

    /**
     * 获取单元及分布信息
     * @param $id
     */
    function getDivsionInfo($id)
    {
        $__mod = DivisionModel::get($id);
        $_mod = array();

        $_mod['FB'] = DivisionModel::get($__mod['pid']);
        $_mod['DW'] = DivisionModel::get($_mod['FB']['pid']);
        return $_mod;
    }
}