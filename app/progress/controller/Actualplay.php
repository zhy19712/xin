<?php
/**
 * Created by PhpStorm.
 * User: sir
 * Date: 2018/6/13
 * Time: 9:06
 */

namespace app\progress\controller;


use app\admin\controller\Permissions;
use app\contract\model\SectionModel;
use app\modelmanagement\model\ConfigureModel;
use app\modelmanagement\model\QualitymassModel;
use app\progress\model\ActualModel;
use think\Session;

/**
 * 实时进度展示
 * Class actual
 * @package app\progress\controller
 */
class Actualplay extends Permissions
{

    /**
     * 首页加载初始化
     * 选择了区间时间后
     * 共用接口
     */
    public function index()
    {
        if($this->request->isAjax()){
            // 首页加载初始 的时候, 什么都不传递 ,默认返回 1 标段和起止时间，2 显示效果配置，3 所有模型编号和显示样式，4 归属当前标段的所有模型编号和显示样式 5 归属当前标段的最近日期之前的模型编号
            // 当选择了区间时间后,前台传递,选择的标段编号 section_id 和 开始时间 date_start  结束时间 date_end
            // 会多返回最左侧的[未建，在建，完建]的数据
            $param = input('param.');
            $section_id = isset($param['section_id']) ? $param['section_id'] : 0;
            $date_start = isset($param['date_start']) ? $param['date_start'] : 0;
            $date_end = isset($param['date_end']) ? $param['date_end'] : 0;
            // 根据当前登陆人的权限获取对应的 -- 标段列表选项
            $section = new SectionModel();
            $data['section'] = $section->sectionList();
            // 起止时间
            $keys = [0];
            if(sizeof($data)){
                $keys = array_keys($data);
            }
            if($section_id && $date_start && $date_end){
                $sid = $section_id;
            }else{
                $sid = $keys[0];
            }
            $actual = new ActualModel();
            $time_data = $actual->dateScope($sid);
            $data['date_start'] = $time_data['date_start'];
            $data['date_end'] = $time_data['date_end'];
            // 显示效果配置
            $version = new ConfigureModel();
            $data['configureInfo'] = $version->getConfigure(3); // 1 全景3D模型 和 质量3D模型 2 进度模拟 和 进度对比 3 实时进度展示
            // 所有模型编号和显示样式
            $model = new QualitymassModel();
            $data['model_all'] = $model->modelData(1); // type 1 所有 2 标段所有 3 最近日期
            // 归属当前标段的所有模型编号和显示样式
            $data['section_model'] = $model->modelData(2,$sid);// type 1 所有 2 标段所有 3 最近日期
            // 归属当前标段的最近日期之前的模型编号
            $data['scope_model'] = $model->modelData(3,$sid);// type 1 所有 2 标段所有 3 最近日期
            if($section_id && $date_start && $date_end){
                // 返回最左侧的[未建，在建，完建]的数据
                $data['actual_info'] = $actual->actualInfo($section_id,$date_start,$date_end);
                return json(['code'=>1,'data'=>$data,'msg'=>'切换区间时间的对应数据']);
            }
            return json(['code'=>1,'data'=>$data,'msg'=>'初始化(切换标段)获取初始化数据']);
        }
        return $this->fetch();
    }



}