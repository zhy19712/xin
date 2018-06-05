<?php

namespace app\api\controller;

use think\Db;
use think\Session;
use think\Controller;
use think\Request;

class Login extends Controller
{

    public function __construct()
    {
       $this->login();//自动调取登陆函数，存储session

    }

    public function check()
    {
        //验证
        $par=input('param.');

    }


    public function login()
    {

            Session::set('admin',1);
            Session::set('current_name','admin');
            Session::set('current_id',1);


    }
    //测试登陆，加上权限验证



}