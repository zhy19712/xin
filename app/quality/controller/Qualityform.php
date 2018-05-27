<?php
/**
 * Created by PhpStorm.
 * User: zhifang
 * Date: 2018/4/16
 * Time: 14:45
 */

namespace app\quality\controller;

use app\admin\controller\Permissions;
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

/**
 * 在线填报
 * Class Qualityform
 * @package app\quality\model
 */
class Qualityform extends Permissions
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
    public function edit($cpr_id, $currentStep, $isView = false, $id = null)
    {
        //获取模板路径
        //获取控制点信息，组合模板路径
        $norm_template=Db::name('norm_template')->alias('t')
            ->join('norm_controlpoint c', 't.id = c.qualitytemplateid', 'left')
            ->join('quality_division_controlpoint_relation r', 'r.control_id = c.id', 'left')
            ->where(['r.id'=>$cpr_id])
            ->find();
        $qualitytemplateid = $norm_template['qualitytemplateid'];

        if ($qualitytemplateid == 0) {
            return  '控制点未进行模板关联!';
        }


        $cp = $this->divisionControlPointService->with('controlpoint')->where('id', $cpr_id)->find();
        $formPath = ROOT_PATH . 'public' . DS . "data\\form\\quality\\" . $cp['controlpoint']['code'] . $cp['controlpoint']['name'] . ".html";
        $formPath = iconv('UTF-8', 'GB2312', $formPath);
        if (!file_exists($formPath)) {
            return "模板文件不存在";
        }
        $htmlContent = file_get_contents($formPath);
        $htmlContent = str_replace("{id}", $id, $htmlContent);
        $htmlContent = str_replace("{templateId}", $cp['controlpoint']['qualitytemplateid'], $htmlContent);
        $htmlContent = str_replace('{divisionId}', $cp['division_id'], $htmlContent);
        $htmlContent = str_replace('{isInspect}', $cp['type'] ? 'true' : 'false', $htmlContent);
        $htmlContent = str_replace('{procedureId}', $cp['ma_division_id'], $htmlContent);
        $htmlContent = str_replace('{controlPointId}', $cp['control_id'], $htmlContent);
        $htmlContent = str_replace('{formName}', $cp['ControlPoint']['name'], $htmlContent);
        $htmlContent = str_replace('{currentStep}', $currentStep, $htmlContent);
        $htmlContent = str_replace('{isView}', $isView, $htmlContent);

        //输出模板内容
        //Todo 暂时使用replace替换，后期修改模板使用fetch自定义模板渲染
        $res= Db::name('quality_division_controlpoint_relation')
            ->where(['id'=>$cpr_id])
            ->find();
        $unit_id=$res['division_id'];
        $htmlContent = $this->setFormInfo($unit_id, $htmlContent);

        //获取表单基本信息
        $formdata = "";
        if (!is_null($id)) {
            $_formdata = $this->qualityFormInfoService->where(['id' => $id])->find()['form_data'];
            $formdata = json_encode(unserialize($_formdata));
        }
        $htmlContent = str_replace('{formData}', $formdata, $htmlContent);


        $htmlContent .= "<input type='hidden' id='cpr' value='{$cpr_id}'>";
        return $htmlContent;
    }

    /**
     * 设置表单基本信息
     * @param $qualityUnit_id 检验批
     */
    protected function setFormInfo($qualityUnit_id, $htmlContent)
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
        foreach ($output as $key => $value) {
            $htmlContent = str_replace("{{$key}}", $value, $htmlContent);
        }
        return $htmlContent;
    }

    /**
     * 表单附件
     * @param $divisionId
     * @param $procedureId
     * @param $controlPointId
     * @return mixed
     */
    public function Index($cpr_id)
    {
        $this->assign('cpr_id', $cpr_id);
        return $this->fetch();
    }

    /**
     * 新增或编辑表单
     * @param $dto
     * @return \think\response\Json
     */
    public function InsertOrUpdate($dto)
    {
        try {
            $mod = array();


            $mod['DivisionId'] = $dto['DivisionId'];
            $mod['ProcedureId'] = $dto['ProcedureId'];
            $mod['ControlPointId'] = $dto['ControlPointId'];
            $mod['IsInspect'] = $dto['IsInspect'];
            $mod['TemplateId'] = $dto['TemplateId'];
            $mod['form_name'] = $dto['FormName'];
            $mod['form_data'] = serialize($dto['QualityFormDatas']);
            $mod['CurrentStep']=0;//初始化状态0
            if (empty($dto['Id'])) {
                $mod['user_id'] = Session::get('current_id');
                $mod['create_time'] = time();
                $judge=Db::name('quality_form_info')
                    ->where(['ControlPointId'=>$mod['ControlPointId'],'DivisionId'=>$mod['DivisionId']])
                    ->where('ApproveStatus','>',-2)//除了作废状态其他都不让新建
                    ->find();
                if($judge){
                    return json(['result' => 'Refund']);
                }
                /*
                //判断是否有扫描件
                $cpr=Db::name('quality_division_controlpoint_relation')
                    ->where(['control_id' =>$mod['ControlPointId'],'division_id' =>$mod['DivisionId'],'type'=>1])
                    ->find();
                $cpr_id=$cpr['id'];
                $copy=Db::name('quality_upload')
                    ->where(['contr_relation_id '=>$cpr_id,'type'=>1])
                    ->find();

                if($copy){
                    return json(['result' => 'Refund','remark'=>'已经有扫描件']);
                }
                */
                else {
                    $res = $this->qualityFormInfoService->insertGetId($mod);
                    $dto['Id'] = $res;
                }
            } else {
                //判断是否有处于审批和新建的填报文件
                $judge=Db::name('quality_form_info')
                    ->where(['ControlPointId'=>$mod['ControlPointId'],'DivisionId'=>$mod['DivisionId']])
                    ->where('ApproveStatus','in',[1,0])
                    ->find();
                if(count($judge)>0)
                {
                    $mod['CurrentStep']=$judge['CurrentStep'];//步骤为当前步骤
                }

                $this->qualityFormInfoService->allowField(true)->isUpdate(true)->save($mod, ['id' => $dto['Id']]);
            }
            return json(['result' => $dto['Id']]);
        } catch (Exception $exception) {
            return json(['result' => 'Faild']);
        }
    }

    public function QalityFormAttachment($cpr_id)
    {
        $this->assign('cpr_id', $cpr_id);
        return $this->fetch();
    }

    /**
     * 删除表单信息 TODO 后续审批相关信息也要删除，目前没有明确逻辑需求
     * @param $id
     * @return \think\response\Json
     */
    public function delForm($id)
    {
        try {
            QualityFormInfoModel::destroy($id);
            //删除的同时删除消息记录表中的信息
            $model = new MessageremindingModel();
            $model->delTb($id);
            return json(['code' => 1]);
        } catch (Exception $exception) {
            return json(['code' => -1]);
        }
    }
    //表单作废
    public function cancel($id)
    {
        $olddata['ApproveStatus']=-2;
        $res=$this->qualityFormInfoService->allowField(true)->isUpdate(true)->save($olddata, ['id' => $id]);

        if($res)
        {
            //规范data

            $data_res=$this->qualityFormInfoService->where(['id' => $id])->find();

            //更新状态relation_id 状态为未执行
            Db::name('quality_division_controlpoint_relation')
                ->where(['control_id' =>$data_res['ControlPointId'],'division_id' =>$data_res['DivisionId'],'type'=>1])
                ->update(['status'=>0]);

            //将表内填的数据全部情况
            $form_data=$data_res['form_data'];
            $se_data=unserialize($form_data);
            foreach($se_data as $k => $v)
            {
             $se_data[$k]['Value']='';

            }
            $data['form_data']=$se_data;
            $data['form_name']=$data_res['form_name'];
            $data['DivisionId']=$data_res['DivisionId'];
            $data['user_id']=$data_res['user_id'];
            $data['create_time']=time();
            $data['ProcedureId']=$data_res['ProcedureId'];
            $data['ControlPointId']=$data_res['ControlPointId'];
            $data['ApproveStatus']=0;
            $data['CurrentStep']=0;
            $data['update_time']=time();


            $qfi = Db::name('quality_form_info')
                ->insert($data);
            if($qfi)
            {
                return json(['msg' => 'success']);
            }
            else
            {
                return json(['msg' => 'fail','remark'=>'生成新数据时出错']);
            }
        }
        else
        {
            return json(['msg'=>'fail']);
        }
    }
    //获取表单总步骤和当前步骤,传入form_info主键
    public function getStep($form_id)
    {
        //表单信息
        $res=Db::name('quality_form_info')
            ->where(['id'=>$form_id])
            ->find();
        $form_data=$res['form_data'];
        $form_data=unserialize($form_data);
        foreach($form_data as $v)
        {
            $step[]=$v['Step'];
        }
        $maxstep=intval(max($step));
        $user=Db::name('admin')
            ->where(['id'=>$res['user_id']])
            ->find();
        //如果当前步骤不是最后一步
        if ($res['CurrentStep']<$maxstep)
        {

            return json(['msg'=>'success','creater'=>$user['nickname']]);
        }
        else
        {
            return json(['msg'=>'fail','creater'=>$user['nickname']]);
        }
    }

    /**
     * 获取当前审批人的点子签名，如果没有则获取当前用户的
     * @param $id
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function GetCurrentUserSignature($id)
    {
        try {
            if (!empty($id)) {
                //获取当前审批人
                $currentApproverId = $this->qualityFormInfoService->where(['id' => $id])->field('CurrentApproverId')->find();
                if (!empty($currentApproverId['CurrentApproverId'])) {
                    return Admin::get($currentApproverId['CurrentApproverId'], 'SignImg')['SignImg']['filepath'];
                }
            }
            return Admin::get(Session::get('current_id'), 'SignImg')['SignImg']['filepath'];
        } catch (Exception $e) {
            return "";
        }
    }

    ##私有方法

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