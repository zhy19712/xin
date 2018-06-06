<?php

namespace app\api\controller;

use think\Db;
use think\Session;
use think\Controller;
use think\Request;
use think\Cookie;


class Login extends Controller
{

    public function __construct()
    {
       $this->login();//自动调取登陆函数，存储session

    }

    //密码加密方法
    function password($password, $password_code='lshi4AsSUrUOwWV')
    {
        return md5(md5($password) . md5($password_code));
    }



        //验证
    public function login()
    {
        if(Session::has('admin') == false)
        {
                $post=input('post.');
                $name = Db::name('admin')->where('name',$post['name'])->find();
                if(empty($name)) {

                    //不存在该用户名
                    return json(['code'=>'-1','msg'=>'用户名不存在']);
                }
                else
                {
                    //验证密码
                    $temp = $post['password'];
                    $post['password'] = $this->password($post['password']);
                    if($name['password'] != $post['password'])
                     {
                        return json(['code'=>'-1','msg'=>'密码错误']);
                     }
                        Session::set("admin",$name['id']); //保存新的
                        Session::set("current_name",$name['name']); //保存新的
                        Session::set("current_id",$name['id']); //保存新的
                        Session::set("current_nickname",$name['nickname']); //保存新的
                        Session::set("admin_cate_id",$name['admin_cate_id']); //保存新的
                        //记录登录时间和ip
                        Db::name('admin')->where('id',$name['id'])->update(['login_ip' =>  $this->request->ip(),'login_time' => time()]);
                        return json(['code'=>'1','msg'=>'登陆成功']);
                  }
        }
        else{

            return json(['code'=>'1','msg'=>'您已经登陆','cookiekey'=>$_COOKIE['PHPSESSID']]);
        }

     }



    //测试登陆，加上权限验证



}