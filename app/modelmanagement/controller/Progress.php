<?php
/**
 * Created by PhpStorm.
 * User: waterforest
 * Date: 2018/5/22
 * Time: 16:41
 */

namespace app\modelmanagement\controller;


use app\admin\controller\Permissions;

class Progress extends Permissions
{

    function index()
    {
        return $this->fetch();
    }
}