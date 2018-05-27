<?php
/**
 * Created by PhpStorm.
 * User: waterforest
 * Date: 2018/5/22
 * Time: 11:08
 */

namespace app\modelmanagement\controller;
use app\admin\controller\Permissions;
use app\modelmanagement\model\QualitymassModel;
use think\Db;
use \think\Session;

class Manage extends Permissions
{
    function index()
    {
        return $this->fetch();
    }

    /**
     * 管理信息-验收资料-工序、控制点
     */
    public function getAcceptance()
    {
        if ($this->request->isAjax()) {
            //实例化模型类
            $model =  new QualitymassModel();

            $model_id = input('post.model_id')?input('post.model_id'):"";
//            $model_id = 5;
            /*******基本信息**********/
            $unit_info = $model->getUnitInfo($model_id);

            $unit_info = empty($unit_info)?[]:$unit_info;

            //获取xin_quality_unit表中的工程类型，en_type为xin_norm_materialtrackingdivision质量验评标准库-工序中的pid
            $en_type = $unit_info["en_type"];

            //根据xin_norm_materialtrackingdivision质量验评标准库-工序中的pid查询cat=5type=3单元工程下的工序
            $processinfo = $model->getProcessInfo($en_type);

            //需要的条件 unit_id->division_id checked type=1
            if(!empty($processinfo))
            {
                foreach ($processinfo as $key=>$val)
                {
                    //查询控制点的信息
                    $processinfo[$key]["controlpoint_info"]=
                        Db::name('norm_controlpoint')->alias('c')
                        ->where("c.procedureid",$val["id"])
                        ->field("c.id,c.procedureid,c.code,c.name,c.qualitytemplateid")
                        ->order('c.code asc')
                        ->select();

                    $processinfo[$key]["unit_id"]=$unit_info["id"];//unit表中的主键id
                }
                return json(["code"=>1,"processinfo"=>$processinfo]);

            }
        }
    }

    /**
     * 点击控制点返回相应的信息
     * @return \think\response\Json
     */
    public function getLineReport()
    {
        if ($this->request->isAjax()) {
            $ControlPointId = input("post.id");
//            $ControlPointId = 220;
            $ProcedureId = input("post.procedureid");
//            $ProcedureId = 39;
            $DivisionId = input("unit_id");
//            $DivisionId = 17;
            //当前登录用户id
            $admin_id= Session::has('admin') ? Session::get('admin') : 0;

            //查询xin_quality_form_info在线填报表的中的信息
        $form_info = Db::name("quality_form_info")->alias("q")
            ->join('admin ad', 'ad.id=q.user_id', 'left')
            ->field("ad.nickname,FROM_UNIXTIME(q.update_time,'%Y-%c-%d') as update_time,q.ApproveStatus,q.id,q.user_id,q.CurrentApproverId,q.CurrentStep")
            ->where(["DivisionId"=>$DivisionId,"ProcedureId"=>$ProcedureId,"ControlPointId"=>$ControlPointId])
            ->order("update_time desc")
            ->select();

            //查询xin_quality_division_controlpoint_relation对应关系表
        $relation_form = Db::name("quality_division_controlpoint_relation")
            ->field("id")
            ->where(["division_id"=>$DivisionId,"ma_division_id"=>$ProcedureId,"control_id"=>$ControlPointId])
            ->where(["type"=>1,"checked"=>0])
            ->find();

            //查询xin_quality_upload表中的扫描件和附件资料
        $upload_form_sao = Db::name("quality_upload")->alias("u")
            ->join('attachment a', 'a.id=u.attachment_id', 'left')
            ->join('admin ad', 'ad.id=a.user_id', 'left')
            ->field("u.data_name,ad.nickname,FROM_UNIXTIME(a.create_time,'%Y-%c-%d') as create_time,u.id,u.type")
            ->where("u.contr_relation_id",$relation_form["id"])
            ->where("type = 1")
            ->select();

        $upload_form_fu = Db::name("quality_upload")->alias("u")
                ->join('attachment a', 'a.id=u.attachment_id', 'left')
                ->join('admin ad', 'ad.id=a.user_id', 'left')
                ->field("u.data_name,ad.nickname,FROM_UNIXTIME(a.create_time,'%Y-%c-%d') as create_time,u.id,u.type")
                ->where("u.contr_relation_id",$relation_form["id"])
                ->where("type = 4")
                ->select();
            //form_info,在线填报，relation_form，relation关系表中的主键id,upload_form,$upload_form_sao扫描件,$upload_form_fu附件
            return json(["code"=>1,"admin_id"=>$admin_id,"form_info"=>$form_info,"relation_form"=>$relation_form,"upload_form_sao"=>$upload_form_sao,"upload_form_fu"=>$upload_form_fu]);
        }
    }

    /**
     * 点击模型返回管理信息中的属性信息
     * @return \think\response\Json
     */
    public function getManageInfo()
    {
        if ($this->request->isAjax()) {
            //实例化模型类
            $model =  new QualitymassModel();
            $model_id = input('post.model_id')?input('post.model_id'):"";
//            $model_id = 13;
            $attribute = $model->getUnitInfo($model_id);
            return json(["code"=>1,"attribute"=>$attribute]);
        }
    }
}