<?php

/**
 * 质量管理-进度模型关联
 * Class Branch
 * @package app\quality\controller
 */
namespace app\modelmanagement\controller;
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

    /**
     * 关联模型
     * @return mixed
     * @author hutao
     */
    public function reportModelRelation()
    {
        return $this->fetch('modelRelevance/reportModelRelation');
    }

}