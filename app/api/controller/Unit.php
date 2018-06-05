<?php
/**
 * Created by PhpStorm.
 * User: 98113
 * Date: 2018/5/28
 * Time: 10:00
 */
namespace app\api\controller;

use app\api\controller\Login;
use app\quality\model\DivisionUnitModel;
use app\admin\model\Admin;
use app\admin\model\AdminGroup;
use think\Db;
use think\Session;
use think\Controller;
use think\Request;

Class Unit extends Login
{
    //获取单元划分段号的信息
    public function getUnit()
    {
        $par=input('param.');
        $id=$par['division_id'];
        return json(DivisionUnitModel::all(['division_id' => $id]));

    }
    //获取单元段号下的工序
    public function getProcedures()
    {
        //按工序类型排序
        $par=input('param.');
        $id=$par['unit_id'];
        $res=Db::name('norm_materialtrackingdivision')
            ->where(['pid' => $id, 'type' => 3])
            ->order('sort_id')
            ->select();
        return json($res);

    }

    //获取单元划分段号下的所有控制点
    public function norm_materialtrackingdivision ()
    {
        //查询
        //条件过滤后记录数 必要
        $recordsFiltered = 0;
        $recordsFilteredResult = array();
        $param = input('param.');
        $en_type=$param['en_type'];
        $unit_id=$param['unit_id'];

        //norm_materialtrackingdivision的id数组
        $nm_arr=Db::name('norm_materialtrackingdivision')
            ->where(['pid'=>$en_type,'type'=>3,'cat'=>5])
            ->column('id');

        //如果传的有工序id
        if(isset($param['nm_id'])&&$param['nm_id']>0)
        {
            $wherestr['procedureid']=$param['nm_id'];
            $id_arr=Db::name('norm_controlpoint')
                ->where($wherestr)
                ->column('id');
        }
        else
        {
            //controlpoint里的id数组

            $id_arr=Db::name('norm_controlpoint')
                ->where('procedureid','in',$nm_arr)
                ->column('id');
        }

        $search=Db::name('quality_division_controlpoint_relation')
            ->where(['type'=>1,'division_id'=>$unit_id])
            ->select();
        //如果之前触发了insertalldata函数
        if (count($search) > 0) {
            //是否有传入工序
            if(isset($param['nm_id'])&&$param['nm_id']>0)
            {
                $wherenm['r.ma_division_id']=$param['nm_id'];
            }
            else{
                $wherenm='';
            }
            //是否有传入check判断（区分单元测评/单元管控）
                //是管控模块
                $wherech['r.checked']=0;


            //*****多表查询join改这里******
            $recordsFilteredResult = Db::name('norm_controlpoint')->alias('c')
                ->join('quality_division_controlpoint_relation r', 'r.control_id = c.id', 'left')
                ->where(['r.type'=>1,'r.division_id'=>$unit_id])
                ->where('r.control_id','in',$id_arr)//控制点必须对应在当前的工程类型下，防止切换单元类型
                ->where($wherenm)
                ->where($wherech)
                ->order('code')
                ->select();
            $recordsFiltered = sizeof($recordsFilteredResult);
        } else {
                //*****多表查询join改这里******
            $recordsFilteredResult= Db::name('norm_controlpoint')->alias('c')
                    ->join('quality_division_controlpoint_relation r', 'r.control_id = c.id', 'left')
                    ->where(['r.type'=>1,'r.division_id'=>$unit_id])
                    ->where('r.control_id','in',$id_arr)//控制点必须对应在当前的工程类型下，防止切换单元类型
                    ->order('code')
                    ->select();
                $recordsFiltered = sizeof($recordsFilteredResult);
        }
        //表的总记录数 必要
        $recordsTotal =count($recordsFiltered);
        $temp = array();
        $infos = array();

        return json([ 'recordsTotal' => intval($recordsTotal), 'recordsFiltered' => $recordsFiltered, 'data' => $recordsFilteredResult]);
    }

    //展示信息的功能结束

    //检测管控中的控件能否使用
    public function checkform()
    {
        $search_name='单元工程质量验评';
        $cp_name='单元工程质量等级评定表';
        $param = input('param.');
        $unit_id=17;
        $unit= Db::name('quality_unit')
            ->where(['id' =>$unit_id])
            ->find();
        //找工程类型，找验评工序，再找到对应控制点
        $en_type=$unit['en_type'];
        $nm=Db::name('norm_materialtrackingdivision')
            ->where(['pid' =>$en_type])
            ->where('name', 'like', '%'.$search_name)
            ->find();
        $nm_id=$nm['id'];

        $cp=Db::name('norm_controlpoint')
            ->where(['procedureid' =>$nm_id])
            ->where('name', 'like', '%'.$cp_name)
            ->find();
        $cp_id=$cp['id'];

        $res = Db::name('quality_form_info')
            ->where(['ControlPointId' =>$cp_id,'DivisionId' =>$unit_id,'ApproveStatus'=>2])
            ->where('form_name', 'like', '%' . $cp_name)
            ->find();

        $cpr=Db::name('quality_division_controlpoint_relation')
            ->where(['control_id' =>$cp_id,'division_id' =>$unit_id,'type'=>1])
            ->find();
        $cpr_id=$cpr['id'];
        //获取cpr_id
        //去附件表里找是否有扫描件上传，如果有就有权限修改
        $copy = Db::name('quality_upload')
            ->where(['contr_relation_id' => $cpr_id, 'type' => 1])
            ->find();
        if(count($copy)>0)
        {
            $flag=$this->evaluatePremission();
            if($flag==1)
            {
                return json(['msg' => 'success']);
            }
            else
            {
                return json(['msg' => 'fail','remark'=>'权限不足']);
            }
        }
        //如果没有扫描件去检查线上流程是否有验评结果
        else
        {
            if (count($res) > 0)
            {
                return json(['msg' => 'fail', 'remark' => '线上流程', 'EvaluateDate' => $unit['EvaluateDate'], 'EvaluateDate' => $unit['EvaluateDate']]);
            }
            else
            {
                return json(['msg' => 'fail', 'remark' => '尚未上传验评扫描件或在线流程未完成审批']);
            }
        }
    }
    //获取单元批的验评结果
    public function getEvaluation()
    {
        $param=input("param.");
        $unit_id=$param['unit_id'];
        $model=new DivisionUnitModel();
        $data=$model->getOne($unit_id);

        $evaluateDate=$data['EvaluateDate'];
        if(($evaluateDate!=0)||($evaluateDate!=0)!='')
        {
            $evaluateDate=date('Y-m-d',$evaluateDate);
        }
        else
        {
            $evaluateDate=0;
        }
        if(count($data)>0) {
            return json(['msg'=>'success','evaluateDate'=>$evaluateDate,'evaluateResult'=>$data['EvaluateResult']]);
        }
        else
        {
            return json(['msg'=>'fail']);
        }
    }


    //手动填写验评结果的保存
    public function Evaluate()
    {
        $mod = input('post.');
        $_mod = DivisionUnitModel::get($mod['Unit_id']);
        $_mod['EvaluateResult'] = $mod['EvaluateResult'];
        $_mod['EvaluateDate'] = strtotime($mod['EvaluateDate']);
        $res = $_mod->save();
        if ($res) {
            return json(['code' => 1]);
        } else {
            return json(['code' => -1]);
        }
    }
    //判断当前登陆人是否是监理或者超级管理员
    public function evaluatePremission()
    {

            //实例化模型类
            $admin = new Admin();
            $admincate = new AdminCate();
            //首先判断当前的登录人是否有验评权限，管理员和监理可以编辑
            $admin_id= Session::has('admin') ? Session::get('admin') : 0;
            $admin_info = $admin->getOne($admin_id);
            $admin_cate_id = $admin_info["admin_cate_id"];
            if(!empty($admin_cate_id))
            {
                $admin_cate_id_array = explode(",",$admin_cate_id);
                //查询角色角色分类表中超级管理员和监理单位中是否有当前登录的用户
                $data = $admincate->getAlladminSupervisor();
                //$flag = 1表示有权限
                $flag = 1;
                foreach ($admin_cate_id_array as $va) {
                    if (in_array($va, $data)) {
                        continue;
                    }else {
                        $flag = 0;
                        break;
                    }
                }
                return $flag;
            }
        }


}