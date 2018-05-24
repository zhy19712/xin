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
           $configure = new ConfigureModel();
           $data = $configure->getConfigure();
           return json(['code'=>1,'data'=>$data,'msg'=>'模型效果配置']);
       }
        return $this->fetch();
    }

    /**
     * 新增 或 编辑
     * @return \think\response\Json
     * @author hutao
     */
    public function edit()
    {
        if($this->request->isAjax()){
            // 前台需要传递的参数有:
            // model_type 1 全景3D模型 2 质量3D模型

            //  pellucidity 透明度  pigment 颜色 transparent_effect 透明效果的透明度

            // choiceness_pellucidity 优良透明度 choiceness_pigment 优良颜色
            // qualified_pellucidity  合格透明度  qualified_pigment 合格颜色
            // un_evaluation_pellucidity  未验评透明度  un_evaluation_pigment 未验评颜色

            // 当编辑时,传递 主键编号 major_key


            /**
             * 全景3D模型 包含 -- 透明度 颜色 透明效果的透明度
             * 质量3D模型 包含 -- 透明度 颜色 优良/合格/未验评
             */

            $param = input('param.');
            // 验证规则
            $rule = [
                ['model_type', 'require', '缺少类型参数'],
                ['pellucidity', 'require', '请填写透明度'],
                ['pigment', 'require', '请填写颜色']
            ];
            $validate = new \think\Validate($rule);
            //验证部分数据合法性
            if (!$validate->check($param)) {
                return json(['code' => -1,'msg' => $validate->getError()]);
            }

            $send = new ConfigureModel();
            $major_key = isset($param['major_key']) ? $param['major_key'] : 0;

            if(empty($major_key)){
                $flag = $send->insertTb($param);
            }else{
                $param['id'] = $major_key;
                $flag = $send->editTb($param);
            }
            return json($flag);
        }
    }

}