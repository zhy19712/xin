<?php
/**
 * Created by PhpStorm.
 * User: waterforest
 * Date: 2018/5/22
 * Time: 16:40
 */

namespace app\modelmanagement\controller;


use app\admin\controller\Permissions;

class Safety extends  Permissions
{
    function index()
    {
        return $this->fetch();
    }

}