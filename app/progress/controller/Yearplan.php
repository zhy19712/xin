<?php
/**
 * Created by PhpStorm.
 * User: 19113
 * Date: 2018/4/19
 * Time: 11:03
 */
/**
 * 进度管理-年度计划
 * Class Progressversion
 * @package app\quality\controller
 */
namespace app\progress\controller;
use app\admin\controller\Permissions;
use \think\Session;
use think\exception\PDOException;
use think\Loader;
use think\Db;

class Yearplan extends Permissions
{
    /**
     * 进度版本管理模板首页
     * @return mixed
     */
    public function index()
    {
        return $this->fetch();
    }
}