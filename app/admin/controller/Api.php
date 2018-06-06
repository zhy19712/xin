<?php
/**
 * Created by PhpStorm.
 * User: wyang
 * Date: 2018/6/6
 * Time: 11:32
 */

namespace app\admin\controller;


use think\Controller;

class api extends Controller
{
    public function index()
    {
        return $this->fetch();
    }
}