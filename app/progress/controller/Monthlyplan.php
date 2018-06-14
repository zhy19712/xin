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
 * 月计划管理
 * Class actual
 * @package app\progress\controller
 */
class Monthlyplan extends Permissions
{
    /**
     * 月计划管理
     * @return mixed
     * @author hutao
     */
    public function index()
    {
        return $this->fetch();
    }

    /**
     * 月计划列表
     * @return mixed
     * @author hutao
     */
    public function listTable()
    {
        return $this->fetch();
    }


}