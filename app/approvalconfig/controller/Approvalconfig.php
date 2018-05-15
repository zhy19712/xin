<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/5/15
 * Time: 11:40
 */
/**
 * 审批流程配置
 * Class Approvalconfig
 * @package app\approvalconfig\controller
 */
namespace app\approvalconfig\controller;
use app\admin\controller\Permissions;
use think\exception\PDOException;
use think\Loader;
use think\Db;
use think\Request;
use think\Session;

class Approvalconfig extends Permissions
{
    /**
     * 模板首页
     * @return mixed
     */
    public function index()
    {
        return $this->fetch();
    }
}