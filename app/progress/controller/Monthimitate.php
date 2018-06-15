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
 * 月进度模拟
 * Class actual
 * @package app\progress\controller
 */
class Monthimitate extends Permissions
{
    public function index()
    {
        return $this->fetch();
    }

}