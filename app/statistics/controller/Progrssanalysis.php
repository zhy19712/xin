<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/4/18
 * Time: 10:44
 */
/**
 * 质量管理-进度分析
 * Class Progrssanalysis
 * @package app\quality\controller
 */
namespace app\statistics\controller;
use app\admin\controller\Permissions;
use app\quality\model\DivisionModel;//工程划分
use \think\Session;
use think\exception\PDOException;
use think\Loader;
use think\Db;

class Progrssanalysis extends Permissions
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