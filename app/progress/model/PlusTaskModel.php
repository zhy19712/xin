<?php
/**
 * Created by PhpStorm.
 * User: sir
 * Date: 2018/6/14
 * Time: 15:52
 */

namespace app\progress\model;


use think\exception\PDOException;
use think\Model;

class PlusTaskModel extends Model
{
    protected $name = 'progress_plus_task';

    public function insertTb($param)
    {
        try {
            $result = $this->allowField(true)->save($param);
            if (false === $result) {
                return ['code' => -1, 'msg' => $this->getError()];
            } else {
                return ['code' => 1, 'data' => [], 'msg' => '添加成功'];
            }
        } catch (PDOException $e) {
            return ['code' => -1, 'msg' => $e->getMessage()];
        }
    }

    public function editTb($param)
    {
        try {
            $result = $this->allowField(true)->save($param, ['id' => $param['id']]);
            if (false === $result) {
                return ['code' => -1, 'msg' => $this->getError()];
            } else {
                return ['code' => 1, 'msg' => '编辑成功'];
            }
        } catch (PDOException $e) {
            return ['code' => 0, 'msg' => $e->getMessage()];
        }
    }


    // ht $plan_type 1月计划2年计划3总计划
    public function tasksData($plan_type,$uid)
    {
        $data = $this->where(['plan_type'=>$plan_type,'project_uid'=>$uid])->select();
        $new_data = [];
        foreach ($data as $k=>$v){
            // 父级任务
            if($v['parent_task_uid'] == -1){
                // 自带参数
                $new_data[$k]['UID'] = $v['id']; // 任务唯一标识符
                $new_data[$k]['WBS'] = $v['wbs']; // WBS编码
                $new_data[$k]['Name'] = $v['name']; // 任务名称
                // 自定义列
                $new_data[$k]['Unit'] = $v['unit']; // 单位
                $new_data[$k]['Quantities'] = $v['quantities']; // 工程量
                // 自带参数
                $new_data[$k]['Duration'] = $v['duration']; // 工期
                $new_data[$k]['Start'] = $v['start']; // 开始日期
                $new_data[$k]['Finish'] = $v['finish']; // 完成日期

                // 未显示参数
                $new_data[$k]['ID'] = $v['order_number']; // 序号(是一个数字,体现任务的前后顺序)
                $new_data[$k]['ParentTaskUID'] = $v['parent_task_uid']; // 父任务UID(体现树形结构)
                $new_data[$k]['Milestone'] = $v['milestone']; // 里程碑
                // [{"TaskUID": "3","LinkLag": 0,"LagFormat": 7,"Type": 1,"PredecessorUID": "35"}]
                $new_data[$k]['PredecessorLink'] = empty($v['predecessor_link']) ? [] : $v['predecessor_link']; // 前置任务（JSON字符串）。如"[{PredecessorUID: 2,Type: 1,LinkLag: 0}, ...]"
                $new_data[$k]['ProjectUID'] = $v['project_uid']; // 计划UID

                // 不存在于数据表里的参数
                $new_data[$k]['OutlineLevel'] = 1; // 行的等级 例如 父级是1 子集 是2
                $new_data[$k]['OutlineNumber'] = $v['wbs']; // 行数  例如 父级是1 子集 是 1.1 1.2 1.3 ... 父级是2 子集 是 2.1 2.2 2.3 ...

//                $new_data[$k]['ActualDuration'] = $v['actual_duration']; // 实际工期
//                $new_data[$k]['ActualFinish'] = $v['actual_finish']; // 实际完成日期
//                $new_data[$k]['ActualStart'] = $v['actual_start']; // 实际开始日期
//                $new_data[$k]['ConstraintDate'] = $v['constraint_date']; // 任务限制日期
//                $new_data[$k]['ConstraintType'] = $v['constraint_type']; // 任务限制类型
//                $new_data[$k]['Critical'] = $v['critical']; // 关键任务
//                $new_data[$k]['Critical2'] = $v['critical2']; // 手动设置关键任务
//                $new_data[$k]['Department'] = $v['department']; // 所属部门
//                $new_data[$k]['FixedDate'] = $v['fixed_date']; // 限制日期(摘要任务专用)
//                $new_data[$k]['Notes'] = empty($v['notes']) ? null : json_decode($v['notes']); // 备注
//                $new_data[$k]['PercentComplete'] = $v['percent_complete']; // 完成百分比
//                $new_data[$k]['Principal'] = $v['principal']; // 任务负责人
//                $new_data[$k]['Priority'] = $v['priority']; // 重要级别
//                $new_data[$k]['Summary'] = $v['summary']; // 摘要任务
//                $new_data[$k]['Weight'] = $v['weight']; // 权重
//                $new_data[$k]['Work'] = $v['work']; // 工时
                // 子任务
                $children = $this->assemblyData($data,$v['id']);
                if(sizeof($children)){
                    $new_data[$k]['children'] = $children;
                }
            }
        }
        // 对象变数组
        $new_da = [];
        foreach ($new_data as $v){
            $new_da[] = $v;
        }
        return $new_da;
    }

    // ht 多层子任务
    public function assemblyData($data,$uid)
    {
        $new_data = [];
        foreach ($data as $k2=>$v2){
            if($v2['parent_task_uid'] == $uid){
                // 自带参数
                $new_data[$k2]['UID'] = $v2['id']; // 任务唯一标识符
                $new_data[$k2]['WBS'] = $v2['wbs']; // WBS编码
                $new_data[$k2]['Name'] = $v2['name']; // 任务名称
                // 自定义列
                $new_data[$k2]['Unit'] = $v2['unit']; // 单位
                $new_data[$k2]['Quantities'] = $v2['quantities']; // 工程量
                // 自带参数
                $new_data[$k2]['Duration'] = $v2['duration']; // 工期
                $new_data[$k2]['Start'] = $v2['start']; // 开始日期
                $new_data[$k2]['Finish'] = $v2['finish']; // 完成日期
                // 未显示参数
                $new_data[$k2]['ID'] = $v2['order_number']; // 序号(是一个数字,体现任务的前后顺序)
                $new_data[$k2]['ParentTaskUID'] = $v2['parent_task_uid']; // 父任务UID(体现树形结构)
                $new_data[$k2]['Milestone'] = $v2['milestone']; // 里程碑
                $new_data[$k2]['PredecessorLink'] = empty($v2['predecessor_link']) ? [] : json_decode($v2['predecessor_link']); // 前置任务（JSON字符串）。如"[{PredecessorUID: 2,Type: 1,LinkLag: 0}, ...]"
                $new_data[$k2]['ProjectUID'] = $v2['project_uid']; // 计划UID

                // 不存在于数据表里的参数
                $new_data[$k2]['OutlineLevel'] = 2; // 行的等级 例如 父级是1 子集 是2
                $new_data[$k2]['OutlineNumber'] = $v2['wbs']; // 行数  例如 父级是1 子集 是 1.1 1.2 1.3 ... 父级是2 子集 是 2.1 2.2 2.3 ...

//                $new_data[$k2]['ActualDuration'] = $v2['actual_duration']; // 实际工期
//                $new_data[$k2]['ActualFinish'] = $v2['actual_finish']; // 实际完成日期
//                $new_data[$k2]['ActualStart'] = $v2['actual_start']; // 实际开始日期
//                if(!empty($v2['assignments'])){
//                    $new_data[$k2]['Assignments'] = json_decode($v2['assignments']); // 资源名称
//                }
//                $new_data[$k2]['ConstraintDate'] = $v2['constraint_date']; // 任务限制日期
//                $new_data[$k2]['ConstraintType'] = $v2['constraint_type']; // 任务限制类型
//                $new_data[$k2]['Critical'] = $v2['critical']; // 关键任务
//                $new_data[$k2]['Critical2'] = $v2['critical2']; // 手动设置关键任务
//                $new_data[$k2]['Department'] = $v2['department']; // 所属部门
//                $new_data[$k2]['FixedDate'] = $v2['fixed_date']; // 限制日期(摘要任务专用)
//                $new_data[$k2]['Notes'] = empty($v2['notes']) ? null : json_decode($v2['notes']); // 备注
//                $new_data[$k2]['PercentComplete'] = $v2['percent_complete']; // 完成百分比
//                $new_data[$k2]['Principal'] = $v2['principal']; // 任务负责人
//                $new_data[$k2]['Priority'] = $v2['priority']; // 重要级别
//                $new_data[$k2]['Summary'] = $v2['summary']; // 摘要任务
//                $new_data[$k2]['Weight'] = $v2['weight']; // 权重
//                $new_data[$k2]['Work'] = $v2['work']; // 工时
                // 子任务
                $children = $this->assemblyData($data,$v2['id']);
                if(sizeof($children)){
                    $new_data[$k2]['children'] = $children;
                }
            }
        }
        // 对象变数组
        $new_da = [];
        foreach ($new_data as $v){
            $new_da[] = $v;
        }
        return $new_da;
    }

    // 获取项目的开始时间和完成时间
    public function startFinishDate($plan_type,$uid)
    {
        // $plan_type 1月计划2年计划3总计划
        $data = $this->where(['plan_type'=>$plan_type,'project_uid'=>$uid])->field('min(start) as start_date,max(actual_finish) as finish_date')->find();
        return $data;
    }

}