<?php
/**
 * Created by PhpStorm.
 * User: waterforest
 * Date: 2018/5/17
 * Time: 10:29
 */
namespace app\modelmanagement\controller;

use app\admin\controller\Permissions;
use \think\Cache;
use \think\Controller;
use think\Loader;
use think\Db;
use \think\Cookie;
use think\Model;
use \think\Session;

class Index extends Permissions
{
    function index()
    {
        $this->assign('content','{include file="../app/public/test.html"}');
        return $this->fetch();
    }

}