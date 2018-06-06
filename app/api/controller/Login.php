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
        if(Session::has('admin') == false) {
            if($this->request->isPost()) {
                //是登录操作
                $post = $this->request->post();
                //验证  唯一规则： 表名，字段名，排除主键值，主键名
                $validate = new \think\Validate([
                    ['name', 'require|alphaDash', '用户名不能为空|用户名格式只能是字母、数字、——或_'],
                    ['password', 'require', '密码不能为空'],
                ]);
//                $validate = new \think\Validate([
//                    ['name', 'require|alphaDash', '用户名不能为空|用户名格式只能是字母、数字、——或_'],
//                    ['password', 'require', '密码不能为空']
//                ]);
                //验证部分数据合法性
                if (!$validate->check($post)) {
                    $this->error('提交失败：' . $validate->getError());
                }
                $name = Db::name('admin')->where('name',$post['name'])->find();
                if(empty($name)) {
                    //不存在该用户名
                    return json(['code'=>'-1','msg'=>'用户名不存在']);
                } else {
                    //验证密码
                    $temp = $post['password'];
                    $post['password'] = password($post['password']);
                    if($name['password'] != $post['password']) {
                        return json(['code'=>'-1','msg'=>'密码错误']);
                    } else {
                        //是否记住账号
                        if(!empty($post['remember']) and $post['remember'] == 1) {
                            //检查当前有没有记住的账号
                            if(Cookie::has('usermember'))
                            {
                                Cookie::delete('usermember');
                                Cookie::delete('userpass');
                            }
                            //保存新的
                            Cookie::forever('usermember',$post['name']);
                            Cookie::forever('userpass',$temp);

                        } else {
                            //未选择记住账号，或属于取消操作
                            if(Cookie::has('usermember')) {
                                Cookie::delete('usermember');
                                Cookie::delete('userpass');
                            }
                        }
                        Session::set("admin",$name['id']); //保存新的
                        Session::set("current_name",$name['name']); //保存新的
                        Session::set("current_id",$name['id']); //保存新的
                        Session::set("current_nickname",$name['nickname']); //保存新的
                        Session::set("admin_cate_id",$name['admin_cate_id']); //保存新的
                        //记录登录时间和ip
                        Db::name('admin')->where('id',$name['id'])->update(['login_ip' =>  $this->request->ip(),'login_time' => time()]);

                    }
                }
            } else
             {
                if(Cookie::has('usermember'))
                {
                    $this->assign('usermember',Cookie::get('usermember'));
                    $this->assign('userpass',Cookie::get('userpass'));
                }

            }
        } else {
            return json(['code'=>'1','msg'=>'您已登陆']);
        }
    }


    //测试登陆，加上权限验证



}