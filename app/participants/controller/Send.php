<?php
/**
 * Created by PhpStorm.
 * User: sir
 * Date: 2018/5/17
 * Time: 9:44
 */

namespace app\participants\controller;

use app\admin\controller\Permissions;

/**
 * 发文
 * Class Income
 * @package app\participants\controller
 */
class Send extends Permissions
{
    public function index()
    {
        return $this->fetch();
    }

}