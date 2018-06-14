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
 * 实时进度填报
 * Class actual
 * @package app\progress\controller
 */
class Actual extends Permissions
{
    public function index()
    {
        return $this->fetch();
    }


}