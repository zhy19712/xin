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
            /**
             * 前台需要传递的参数有:
             * model_type 1 全景3D模型 2 质量3D模型 3 进度模拟 4 进度对比 5 实时进度展示
             */

            /**
             * 首页3D
             * 选择集:   pigment 颜色  pellucidity 透明度
             * 透明效果: transparent_effect 透明效果的透明度
             */

            /**
             * 质量3D
             * 选择集:   pigment 颜色  pellucidity 透明度
             * 验评结果: choiceness_pigment 优良颜色 qualified_pigment  合格颜色 un_evaluation_pigment  未施工颜色
             */

            /**
             * 进度模拟
             * 选择集:   pigment 颜色  pellucidity 透明度
             * 从有到无: 未建颜色 has_unbuilt_color 在建颜色 has_building_color 完建透明度 has_completion_transparency
             * 从无到有: 未建颜色 unbuilt_color 未建透明度 unbuilt_transparency 在建颜色 building_color 在建透明度 building_transparency
             */

            /**
             * 进度对比
             * 选择集:   pigment 颜色  pellucidity 透明度
             * 从有到无: 未建颜色 has_unbuilt_color  在建颜色 has_building_color (如期)完建透明度 has_completion_transparency 超期完建颜色 has_late_finish_color 超期完建透明度 late_completion_transparency
             * 从无到有: 未建颜色 unbuilt_color 未建透明度 unbuilt_transparency 在建颜色 building_color 在建透明度 building_transparency 超期完建颜色 late_finish_color
             */

            /**
             * 实时进度展示
             * 选择集:   pigment 颜色  pellucidity 透明度
             * 从有到无: 未建颜色 has_unbuilt_color 在建颜色 has_building_color 完建透明度 has_completion_transparency
             * 从无到有: 未建颜色 unbuilt_color 未建透明度 unbuilt_transparency 在建颜色 building_color 在建透明度 building_transparency
             */

            /**
             * 编辑时,传递主键编号 $major_key
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
                // 避免前台忘记传递的主键编号或者传递的不准确
                $id = $send->configureId($param['model_type']);
                if($major_key == $id){
                    $param['id'] = $major_key;
                }else{
                    $param['id'] = $id;
                }
                $flag = $send->editTb($param);
            }
            return json($flag);
        }
    }

}