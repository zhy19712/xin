<?php
/**
 * Created by PhpStorm.
 * User: 98113
 * Date: 2018/5/28
 * Time: 14:17
 */
namespace app\api\controller;

use app\api\controller\Log;
use think\Db;
use think\Session;
use think\Controller;
use think\Request;

class Controlpoint extends Log
{


    //控制点下的表单信息
    public function quality_form_info()
    {
        $param = input('param.');
        //通过unit_id和控制点id获取cpr_id;
        $unit_id=$param['unit_id'];
        $control_id=$param['control_id'];
        $whereStr['DivisionId']=$unit_id;
        $whereStr['ControlPointId']=$control_id;

        //传递表单链接
        $relation=Db::name('quality_division_controlpoint_relation')
            ->where(['division_id'=>$unit_id,'control_id'=>$control_id,'type'=>1])
            ->find();
        $cpr_id=$relation['id'];
        $host="http://".$_SERVER['HTTP_HOST'];
        $html=$host."/quality/matchform/matchform?cpr_id=".$cpr_id;

        //查询
        //条件过滤后记录数 必要
        $recordsFiltered = 0;
        $recordsFilteredResult = array();
        //表的总记录数 必要
        $recordsTotal = Db::name('quality_form_info')->where($whereStr)->count();

        //*****多表查询join改这里******
        $recordsFilteredResult = Db::name('quality_form_info')->alias('a')
            ->join('admin u', 'a.user_id = u.id', 'left')
            ->join('admin c', 'a.CurrentApproverId = c.id', 'left')
            ->field('a.id,u.nickname as nickname,c.nickname as currentname,a.approvestatus,a.create_time,a.CurrentApproverId,a.CurrentStep,a.user_id')
            ->where($whereStr)
            ->order('create_time','desc')
            ->select();

        $recordsFiltered = sizeof($recordsFilteredResult);

        return json(['recordsTotal' => intval($recordsTotal), 'recordsFiltered' => $recordsFiltered, 'data' => $recordsFilteredResult,'herf'=>$html]);
    }


    public function copy()
    {
        //获取控制点下扫描件的信息
        $param = input('param.');
        //通过unit_id和控制点id获取cpr_id;
        $unit_id=$param['unit_id'];
        $control_id=$param['control_id'];
        $relation=Db::name('quality_division_controlpoint_relation')
            ->where(['division_id'=>$unit_id,'control_id'=>$control_id,'type'=>1])
           ->find();
        $cpr_id=$relation['id'];

        //查询
        //条件过滤后记录数 必要
        $recordsFiltered = 0;
        $recordsFilteredResult = array();
        //表的总记录数 必要
        $recordsTotal = Db::name('quality_upload')->where(['contr_relation_id'=>$cpr_id])->count();

        //*****多表查询join改这里******
        $recordsFilteredResult = Db::name('quality_upload')->alias('u')
            ->join('attachment m', 'u.attachment_id = m.id', 'left')
            ->where(['contr_relation_id'=>$cpr_id,'type'=>1])
            ->select();
        foreach ($recordsFilteredResult as $key =>  $result)
        {
            $recordsFilteredResult[$key]['filepath']=ROOT_PATH . 'public' .$result['filepath'];
        }
        $recordsFiltered = sizeof($recordsFilteredResult);

        return json(['recordsTotal' => intval($recordsTotal), 'recordsFiltered' => $recordsFiltered, 'data' => $recordsFilteredResult]);
    }

    public function uploads()
    {
        //获取控制点下附件资料的信息
        $param = input('param.');
        //通过unit_id和控制点id获取cpr_id;
        $unit_id=$param['unit_id'];
        $control_id=$param['control_id'];
        $relation=Db::name('quality_division_controlpoint_relation')
            ->where(['division_id'=>$unit_id,'control_id'=>$control_id,'type'=>1])
            ->find();
        $cpr_id=$relation['id'];
        //查询
        //条件过滤后记录数 必要
        $recordsFiltered = 0;
        $recordsFilteredResult = array();
        //表的总记录数 必要
        $recordsTotal = Db::name('quality_upload')->where(['contr_relation_id'=>$cpr_id])->count();
        //*****多表查询join改这里******
        $recordsFilteredResult = Db::name('quality_upload')->alias('u')
            ->join('attachment m', 'u.attachment_id = m.id', 'left')
            ->where(['contr_relation_id'=>$cpr_id,'type'=>4])
            ->select();
        foreach ($recordsFilteredResult as $key =>  $result)
        {
            $recordsFilteredResult[$key]['filepath']=ROOT_PATH . 'public' .$result['filepath'];
        }
        $recordsFiltered = sizeof($recordsFilteredResult);
        return json(['recordsTotal' => intval($recordsTotal), 'recordsFiltered' => $recordsFiltered, 'data' => $recordsFilteredResult]);
    }

}