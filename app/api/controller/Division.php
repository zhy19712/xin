<?php
/**
 * Created by PhpStorm.
 * User: 98113
 * Date: 2018/5/28
 * Time: 9:43
 */
namespace app\api\controller;

use app\api\controller\Login;
use app\quality\model\DivisionModel;
use think\Db;
use think\Session;
use think\Controller;
use think\Request;

Class Division extends Login
{

    //获取section表中数据
     public function section()
     {

             $seciton=Db::name('section')
                 ->select();
             return json($seciton);
     }

     public function division()
     {
         $param=input('param.');
         $section_id=$param['section_id'];
         $division=Db::name('quality_division')
             ->where(['section_id'=>$section_id])
             ->select();

         return json($division);

     }

}