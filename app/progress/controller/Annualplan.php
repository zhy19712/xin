<?php
/**
 * Created by PhpStorm.
 * User: sir
 * Date: 2018/6/13
 * Time: 9:06
 */

namespace app\progress\controller;


use app\admin\controller\Permissions;

/**
 * 年计划管理
 * Class actual
 * @package app\progress\controller
 */
class Annualplan extends Permissions
{
    /**
     * 年计划管理
     * @return mixed
     * @author hutao
     */
    public function index()
    {
        return $this->fetch();
    }

    /**
     * 年计划列表
     * @return mixed
     * @author hutao
     */
    public function listTable()
    {
        return $this->fetch();
    }


}