<?php
/**
 * Created by PhpStorm.
 * User: sir
 * Date: 2018/5/17
 * Time: 9:44
 */

namespace app\archive\controller;

use app\admin\controller\Permissions;
use think\Session;

/**
 * 收文
 * Class Income
 * @package app\participants\controller
 */
class Income extends Permissions
{
    public function index()
    {
        $user_name = Session::has('current_nickname') ? Session::get('current_nickname') : 0;
        $this->assign('user_name',$user_name);
        return $this->fetch();
    }

}