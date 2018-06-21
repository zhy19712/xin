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
    public function templateForm($cpr_id)
    {
        //获取模板路径
        //获取控制点信息，组合模板路径
        $norm_template=Db::name('norm_template')->alias('t')
            ->join('norm_controlpoint c', 't.id = c.qualitytemplateid', 'left')
            ->join('quality_division_controlpoint_relation r', 'r.control_id = c.id', 'left')
            ->where(['r.id'=>$cpr_id])
            ->find();
        $template=Db::name('quality_form_info')
            ->where(['DivisionId'=>$norm_template['division_id'],'ControlPointId'=>$norm_template['control_id'],'ApproveStatus'=>2])
            ->find();
        $id= $template['id'];
        $qualitytemplateid = $norm_template['qualitytemplateid'];

        if ($qualitytemplateid == 0) {
            return  '控制点未进行模板关联!';
        }

        $template_name=Db::name('norm_template')
            ->where('id',$qualitytemplateid)
            ->value('name');

        $cp = $this->divisionControlPointService->with('controlpoint')->where('id', $cpr_id)->find();
        $formPath = ROOT_PATH . 'public' . DS . "data\\form\\qualityNew\\" . $cp['controlpoint']['code'] . $template_name . "下载.html";
        $formPath = iconv('UTF-8', 'GB2312', $formPath);
        if (!file_exists($formPath)) {
            return "模板文件不存在";
        }
        if (!is_null($id)) {
            $_formdata = $this->qualityFormInfoService->where(['id' => $id])->find()['form_data'];
            $formdata = json_encode(unserialize($_formdata));
        }
        $output = $this->getFormBaseInfo($norm_template['division_id']);
        $htmlContent = file_get_contents($formPath);

//        $host="http://".$_SERVER['HTTP_HOST'];
//        $code=$host."/quality/matchform/matchform?cpr_id=".$cpr_id;
        //当该控制点有已经填写的表单时
        if($id>0&&!is_null($id))
        {
           $dataId=$id;
           $type=1;//1表示是表单id
        }
        //当没有已填写表单时
        else
        {
            $dataId=$cpr_id;
            $type=2;//2表示是cpr_id
        }

        //app接口地址
        $host="http://".$_SERVER['HTTP_HOST'];
        $code = $host."/api/qualityform/getforminfo?dataId=".$dataId."&type=".$type;

        $htmlContent=    $this->fetch($formPath,
            [   'id'=>$id,
                'divisionId'=>$cp['division_id'],
                'templateId'=>$cp['controlpoint']['qualitytemplateid'],
                'qrcode'=>$code,
                'hideSelect'=>'0',
                'isInspect'=>$cp['type'],
                'procedureId'=>$cp['ma_division_id'],
                'formName'=>$cp['ControlPoint']['name'],
                'currentStep'=>'',
                'controlPointId'=>$cp['control_id'],
                'isView'=>'null',
                'formData'=>"",
                'JYPName'=>$output['JYPName'],
                'JYPCode'=>$output['JYPCode'],
                'JJCode'=>$output['JJCode'],
                'start_date'=>'',
                'completion_date'=>'',
                'Quantity'=>$output['Quantity'],
                'PileNo'=>$output['PileNo'],
                'Altitude'=>$output['Altitude'],
                'BuildBase'=>$output['BuildBase'],
                'DYName'=>$output['DYName'],
                'DYCode'=>$output['DYCode'],
                'Constructor'=>$output['Constructor'],
                'Supervisor'=>$output['Supervisor'],
                'SectionCode'=>$output['ContractCode'],
                'SectionName'=>$output['SectionName'],
                'ContractCode'=>$output['ContractCode'],
                'FBName'=>$output['FBName'],
                'FBCode'=>$output['FBCode'],
                'DWName'=>$output['DWName'],
                'DWCode'=>$output['DWCode']
            ]);
        //返回模板内容
        return $htmlContent;
    }
    //在线填报的表单
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
            ->where(['DivisionId'=>$norm_template['division_id'],'ControlPointId'=>$norm_template['control_id'],'ApproveStatus'=>2])
            ->find();
        $id= $template['id'];
        $qualitytemplateid = $norm_template['qualitytemplateid'];

        if ($qualitytemplateid == 0) {
            return  '控制点未进行模板关联!';
        }

        $template_name=Db::name('norm_template')
            ->where('id',$qualitytemplateid)
            ->value('name');

        $cp = $this->divisionControlPointService->with('controlpoint')->where('id', $cpr_id)->find();
        $formPath = ROOT_PATH . 'public' . DS . "data\\form\\qualityNew\\" . $cp['controlpoint']['code'] . $template_name . "下载.html";
        $formPath = iconv('UTF-8', 'GB2312', $formPath);
        if (!file_exists($formPath)) {
            return "模板文件不存在";
        }
        if (!is_null($id)) {
            $_formdata = $this->qualityFormInfoService->where(['id' => $id])->find()['form_data'];
            $formdata = json_encode(unserialize($_formdata));
        }
        $output = $this->getFormBaseInfo($norm_template['division_id']);
        $htmlContent = file_get_contents($formPath);

        if($id>0&&!is_null($id))
        {
            $dataId=$id;
            $type=1;//1表示是表单id
        }
        //当没有已填写表单时
        else
        {
            $dataId=$cpr_id;
            $type=2;//2表示是cpr_id
        }
        $host="http://".$_SERVER['HTTP_HOST'];
        $code = $host."/api/qualityform/getforminfo?dataId=".$dataId."&type=".$type;
        $htmlContent=    $this->fetch($formPath,
            [   'id'=>$id,
                'divisionId'=>$cp['division_id'],
                'templateId'=>$cp['controlpoint']['qualitytemplateid'],
                'qrcode'=>$code,
                'hideSelect'=>'0',
                'isInspect'=>$cp['type'],
                'procedureId'=>$cp['ma_division_id'],
                'formName'=>$cp['ControlPoint']['name'],
                'currentStep'=>'',
                'controlPointId'=>$cp['control_id'],
                'isView'=>'null',
                'formData'=>$formdata,
                'JYPName'=>$output['JYPName'],
                'JYPCode'=>$output['JYPCode'],
                'JJCode'=>$output['JJCode'],
                'start_date'=>'',
                'completion_date'=>'',
                'Quantity'=>$output['Quantity'],
                'PileNo'=>$output['PileNo'],
                'Altitude'=>$output['Altitude'],
                'BuildBase'=>$output['BuildBase'],
                'DYName'=>$output['DYName'],
                'DYCode'=>$output['DYCode'],
                'Constructor'=>$output['Constructor'],
                'Supervisor'=>$output['Supervisor'],
                'SectionCode'=>$output['ContractCode'],
                'SectionName'=>$output['SectionName'],
                'ContractCode'=>$output['ContractCode'],
                'FBName'=>$output['FBName'],
                'FBCode'=>$output['FBCode'],
                'DWName'=>$output['DWName'],
                'DWCode'=>$output['DWCode']
            ]);
        //返回模板内容
        return $htmlContent;
    }
    /**
     * 设置表单基本信息
     * @param $qualityUnit_id 检验批
     */
    public function getFormBaseInfo($qualityUnit_id)
    {

        $output = array();
        $mod = $this->divisionUnitService->with("Division.Section")->where(['id' => $qualityUnit_id])->find();
        $unit=Db::name('quality_unit')->where(['id'=>$qualityUnit_id])->find();
        $division=Db::name('quality_division')->where(['id'=>$unit['division_id']])->find();
        $section=Db::name('section')->where(['id'=>$division['section_id']])->find();

        //获取施工依据图纸信息
        $atlas_id=$unit['ma_bases'];
        $atlas_id=explode(',',$atlas_id);
        foreach ($atlas_id as $id)
            {
              $atlas= Db::name('archive_atlas_cate ')
                      ->where('id',$id)
                      ->find();
              $bases[]=$atlas['picture_name'].$atlas['picture_number'].$unit['su_basis'];
             }
        $output['BuildBase']=implode(',',$bases);

        $output['JYPName'] = $mod['site'];
        $output['JYPCode'] = $output['JJCode'] = $unit['serial_number'];
        $output['Quantity'] = $mod['quantities'];
        $output['PileNo'] = $mod['pile_number'];
        $output['Altitude'] = $mod['el_start'] . $mod['el_cease'];
        $output['DYName'] = $mod['Division']['d_name'];
        $output['DYCode'] = $mod['Division']['d_code'];
        //标段信息
        if ($mod['Division']['Section'] != null) {

            $_section = $mod['Division']['Section'];
            $contract=Db::name('contract')->where(['id'=>$section['contractId']])->find();
            $output['Constructor'] = $_section['constructorId'] ? AdminGroup::get($_section['constructorId'])['name'] : "";
            $output ['Supervisor'] = $_section['supervisorId'] ? AdminGroup::get($_section['supervisorId'])['name'] : "";
            $output ['SectionCode'] = $_section['code'];
            $output['SectionName'] = $contract['projectName'];
            $output['ContractCode'] =  $contract['contractCode'];
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