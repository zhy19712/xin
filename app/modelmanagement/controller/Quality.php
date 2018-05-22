<?php
/**
 * Created by PhpStorm.
 * User: waterforest
 * Date: 2018/5/22
 * Time: 10:49
 */

namespace app\modelmanagement\controller;


use app\admin\controller\Permissions;

class Quality extends Permissions
{
    function index()
    {
        return $this->fetch();
    }
}