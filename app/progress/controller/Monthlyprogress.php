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
 * 月进度填报
 * Class actual
 * @package app\progress\controller
 */
class Monthlyprogress extends Permissions
{
    /**
     * 月进度填报
     * @return mixed
     * @author hutao
     */
    public function index()
    {
        return $this->fetch();
    }

    /**
     * 提醒配置
     * @return mixed
     * @author hutao
     */
    public function config()
    {
        return $this->fetch();
    }

}