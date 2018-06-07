<?php
namespace app\api\controller;
use think\Db;
use think\Session;
use think\Controller;
use think\Request;
use think\Cookie;
use app\admin\model\Admin;//管理员模型

class Login extends Controller
{

    public function __construct()
    {
       $this->login();//自动调取登陆函数，存储session

    }

    //密码加密方法
    protected function password($password, $password_code='lshi4AsSUrUOwWV')
    {
        return md5(md5($password) . md5($password_code));
    }

    /**
     * 登录验证
     * @return \think\response\Json
     * @throws \think\exception\PDOException
     */
    public function login()
    {
        if(Session::has('admin') == false)
        {
                $post["name"] = input('post.name');
                $post["password"] = input('post.password');
                $name = Db::name('admin')->where('name',$post['name'])->find();
                if(empty($name)) {
                    //不存在该用户名
                    return json(['code'=>'-1','msg'=>'用户名不存在']);
                }
                else
                {
                    //验证密码
                    if($name['password'] != $this->password($post['password']))
                     {
                        return json(['code'=>'-1','msg'=>'密码错误']);
                     }
                        Session::set("admin",$name['id']); //保存新的
                        Session::set("current_name",$name['name']); //保存新的
                        Session::set("current_id",$name['id']); //保存新的
                        Session::set("current_nickname",$name['nickname']); //保存新的
                        Session::set("admin_cate_id",$name['admin_cate_id']); //保存新的
                        //记录登录时间和ip
                        Db::name('admin')->where('id',$name['id'])->update(['login_ip' =>$_SERVER["REMOTE_ADDR"],'login_time' => time()]);
                        return json(['code'=>'1','msg'=>'登陆成功']);
                  }
        }
        else{
            return json(['code'=>'1','msg'=>'您已经登陆','cookiekey'=>$_COOKIE['PHPSESSID']]);
        }
     }

    /**
     * 管理员退出，清除名字为admin的session
     * @return [type] [description]
     */
    public function logout()
    {
        Session::delete('admin');
        Session::delete('admin_cate_id');
        if(Session::has('admin') or Session::has('admin_cate_id')) {
            return json(["code"=>-1,"msg"=>"退出失败！"]);
        }else{
            return json(["code"=>-1,"msg"=>"正在退出..."]);
        }
    }

    /**
     * 个人中心
     * @return mixed|void
     * @throws \think\exception\DbException
     */
    public function personal()
    {
        //获取管理员id
        $admin_id = Session::has('current_id') ? Session::get('current_id') : 0;
        $model = new admin();
        //定义一个空的数组
        $admin_info = array();
        if($admin_id>0)
        {
            //非提交操作
            $info['admin'] = $model->getOne($admin_id);
            $admin_info["id"] = $info['admin']["id"];
            $admin_info["nickname"] = $info['admin']["nickname"];
            $thumb = Db::name('attachment')->field("filepath")->where('id',$info['admin']['thumb'])->find();
            $admin_info['thumb'] = $_SERVER['HTTP_HOST'].$thumb["filepath"];
            $admin_info["position"] = $info['admin']["position"];
            if($info["admin"]["gender"] == 1)
            {

                $admin_info["gender"] = "男";

            }else if($admin_info["gender"] == 0)
            {
                $admin_info["gender"] = "女";
            }
            $admin_info["wechat"] = $info['admin']["wechat"];
            $admin_info["mobile"] = $info['admin']["mobile"];
            $admin_info["tele"] = $info['admin']["tele"];
            $admin_info["mail"] = $info['admin']["mail"];
//                $info['signature'] = Db::name('attachment')->where('id',$info['admin']['signature'])->value('filepath');
            return json(["code"=>1,"admin_info"=>$admin_info]);
        } else {
            return json(["code"=>-1,"msg"=>"个人信息错误"]);
        }
    }
}