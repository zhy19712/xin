<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/4/13
 * Time: 11:26
 */
/**
 * 质量管理-进度模型关联
 * Class Branch
 * @package app\quality\controller
 */
namespace app\quality\controller;
use app\admin\controller\Permissions;

class ModelRelevance extends Permissions
{

    public function index($type=0)
    {
        if($type == 1){
            // 月进度
            return $this->fetch('modelRelevance/monthly');
        }
        return $this->fetch('modelRelevance/index');
    }

}