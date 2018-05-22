<?php
/**
 * Created by PhpStorm.
 * User: waterforest
 * Date: 2018/5/22
 * Time: 16:39
 */

namespace app\modelmanagement\controller;


use app\admin\controller\Permissions;

class Oblique extends  Permissions
{

    function index()
    {
        return $this->fetch();
    }
}