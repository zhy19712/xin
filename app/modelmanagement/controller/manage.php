<?php
/**
 * Created by PhpStorm.
 * User: waterforest
 * Date: 2018/5/22
 * Time: 11:08
 */

namespace app\modelmanagement\controller;


use app\admin\controller\Permissions;

class manage extends Permissions
{
    function index()
    {
        return $this->fetch();
    }
}