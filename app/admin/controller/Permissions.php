<?php

namespace app\admin\controller;

use \think\Cache;
use \think\Controller;
use think\Loader;
use think\Db;
use \think\Session;
use think\Cookie;
class Permissions extends Controller
{

    /**
     * 检查操作用户的ip是否在黑名单里
     * 检查用户是否被禁用
     * 检查用户是否登录
     * 检查用户访问的url在不在其角色组的权限范围内
     * @return [type] [description]
     */
    protected function _initialize()
    {
        //检查当前ip是不是在黑名单
        $black_ip = Db::name('admin_webconfig')->where('web','web')->value('black_ip');
        
        if(!empty($black_ip)) {
            //转化成数组
            $black_ip = explode(',',$black_ip);
            //获取当前访问的ip
            $ip = $this->request->ip();
            if(in_array($ip,$black_ip)) {
                //清空session
                if(Session::has('admin')) {
                    Session::delete('admin');
                }
                return $this->error('你已被封禁！','admin/common/login');
            }
        }
        //检查是否有app端传来的token值
        if(isset($_POST['appToken'])&&($_POST['appToken']!=''))
        {
            $appToken=$_POST['appToken'];
            //调取验证和赋值方法
            $this->checkToken($appToken);
        }
        
        //检查是否登录
        
        if(!Session::has('admin') && !Cookie::has("admin")) {

            $this->redirect('admin/common/login');
        }

        $user_id = Session::get('admin')?Session::get('admin'):Cookie::get("admin");

        //检查当前用户是否被禁
        $black_id = Db::name('admin')->where('id',$user_id)->value('status');

        if($black_id == 0) {
            Session::delete('admin');
            return $this->error('你已被封禁！请联系系统管理员！','admin/common/login');
        }

        $where['module'] = $this->request->module();
        $where['controller'] = $this->request->controller();
        $where['function'] = $this->request->action();
        $where['type'] = 1;
        //获取除了域名和后缀外的url，是字符串
        $parameter = $this->request->path() ? $this->request->path() : null;
        //将字符串转化为数组
        $parameter = explode('/',$parameter);
        //剔除url中的模块、控制器和方法
        foreach ($parameter as $key => $value) {
            if($value != $where['module'] and $value != $where['controller'] and $value != $where['function']) {
                $param[] = $value;
            }
        }

        if(isset($param) and !empty($param)) {
            //确定有参数
            $string = '';
            foreach ($param as $key => $value) {
                //奇数为参数的参数名，偶数为参数的值
                if($key%2 !== 0) {
                    //过滤只有一个参数和最后一个参数的情况
                    if(count($param) > 2 and $key < count($param)-1) {
                        $string.=$value.'&';
                    } else {
                        $string.=$value;
                    }
                } else {
                    $string.=$value.'=';
                }
            } 
        } else {
            $string = [];
            $param = $this->request->param();
            foreach ($param as $key => $value) {
                if(!is_array($value)) {
                    //这里过滤掉值为数组的参数
                    $string[] = $key.'='.$value;
                }
            }
            $string = implode('&',$string);
        }
        
        //得到用户的权限菜单
        $admin_cate_id = Session::get('admin_cate_id');
        if(!empty($admin_cate_id))
        {
            $admin_cate_id = explode(',',$admin_cate_id);
        }
        $menus = Db::name('admin_cate')->whereIn('id',$admin_cate_id)->value('permissions');

        //将得到的菜单id集成的字符串拆分成数组
        $menus = explode(',',$menus);

        if(!empty($string)) {
            //检查该带参数的url是否设置了权限
            $param_menu = Db::name('admin_menu')->where($where)->where('parameter',$string)->find();
            
            if(!empty($param_menu)) {
                //该url的参数设置了权限，检查用户有没有权限
                if(false == in_array($param_menu['id'],$menus)) {
                    return $this->error('缺少权限');
                }
            } else {
                //该url带参数状态未设置权限，检查该url去掉参数时，用户有无权限
                $menu = Db::name('admin_menu')->where($where)->find();
                if(!empty($menu)) {
                    if(empty($menu['parameter'])) {
                        if(!in_array($menu['id'],$menus)) {
                            return $this->error('缺少权限');
                        }
                    }
                }
            }
        } else {
            //用户访问的url里没有参数
            $menu = Db::name('admin_menu')->where($where)->find();
            
            if(!empty($menu)) {
                if(empty($menu['parameter'])) {
                    if(!in_array($menu['id'],$menus)) {
                        return $this->error('缺少权限');
                    }
                }
            }  
        }
    }
    //验证app权限
    protected function checkToken($appToken)
    {
        $info=Db::name('admin')
            ->where(['token'=>$appToken])
            ->find();
        if($appToken==$info['token'])
         {
             Session::set("admin",$info['id']); //保存新的
             Session::set("current_name",$info['name']); //保存新的
             Session::set("current_id",$info['id']); //保存新的
             Session::set("current_nickname",$info['nickname']); //保存新的
             Session::set("admin_cate_id",$info['admin_cate_id']);//保存新的

         }
    }
}
