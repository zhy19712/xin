<?php
/**
 * Created by PhpStorm.
 * User: sir
 * Date: 2018/5/22
 * Time: 11:57
 */

namespace app\modelmanagement\controller;


use app\admin\controller\Permissions;
use app\modelmanagement\model\ConfigureModel;

/**
 * 模型效果配置 -- model_type 1 全景3D模型 2 质量3D模型
 * Class Configure
 * @package app\modelmanagement\controller
 * @author hutao
 */
class Configure extends Permissions
{
    public function index()
    {
       if($this->request->isAjax()){
           $model_type = input('model_type');
           if(empty($model_type)){
               return json(['code'=>-1,'msg'=>'缺少类型参数']);
           }
           $configure = new ConfigureModel();
           $data = $configure->getConfigure($model_type);
           return json(['code'=>1,'data'=>$data,'msg'=>'模型效果配置']);
       }
        return $this->fetch();
    }

}