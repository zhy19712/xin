<?php
/**
 * Created by PhpStorm.
 * User: waterforest
 * Date: 2018/5/22
 * Time: 9:43
 */

namespace app\admin\controller;


class Dashboard extends Permissions
{
    function index()
    {
        return $this->fetch();
    }
}