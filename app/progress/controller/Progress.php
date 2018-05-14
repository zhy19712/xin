<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/4/19
 * Time: 10:40
 */
/**
 * 进度模块-进度管理
 * Class Progress
 * @package app\quality\controller
 */
namespace app\Progress\controller;
use app\admin\controller\Permissions;
use \think\Session;
use think\exception\PDOException;
use think\Loader;
use think\Db;

class Progress extends Permissions
{
    /**
     * 模板首页
     * @return mixed
     */
    public function index()
    {
        return $this->fetch();
    }

    /**
     * 模板首页
     * @return mixed
     */
    public function getindex()
    {
        return $this->fetch();
    }
}