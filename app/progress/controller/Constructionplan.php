<?php
/**
 * Created by PhpStorm.
 * User: 19113
 * Date: 2018/4/19
 * Time: 11:03
 */
/**
 * 进度管理-施工总计划进度
 * Class Progressversion
 * @package app\quality\controller
 */
namespace app\progress\controller;
use app\admin\controller\Permissions;
use \think\Session;
use think\exception\PDOException;
use think\Loader;
use think\Db;

class Constructionplan extends Permissions
{
    /**
     * 施工总计划进度模板首页
     * @return mixed
     */
    public function index()
    {
        return $this->fetch();
    }
}