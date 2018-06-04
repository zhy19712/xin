<?php
/**
 * Created by PhpStorm.
 * User: 98113
 * Date: 2018/5/28
 * Time: 14:25
 */
namespace app\api\controller;

use app\api\controller\Log;
use think\Db;
use think\Session;
use think\Controller;
use think\Request;


class Qualityform extends Log
{
    //获取某个表单的信息
    public function getFormInfo()
    {
        $param=input('param.');
        $form_id=$param['form_id'];
        $info=Db::name('quality_form_info')
            ->where(['id'=>$form_id])
            ->find();

        $form_data=unserialize($info['form_data']);
        return json(['data'=>$info,'form_data'=>$form_data]);
    }












}