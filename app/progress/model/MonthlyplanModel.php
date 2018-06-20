<?php
/**
 * Created by PhpStorm.
 * User: sir
 * Date: 2018/6/14
 * Time: 15:52
 */

namespace app\progress\model;


use app\admin\model\Attachment;
use app\modelmanagement\model\QualitymassModel;
use think\Db;
use think\exception\PDOException;
use think\Model;

class MonthlyplanModel extends Model
{
    protected $name = 'progress_monthlyplan';

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

    public function deleteTb($id)
    {
        try {
            // 关联删除上传的附件
            $data = $this->getOne($id);
            $file_data = Db::name('attachment')->where(['id'=>['in',[$data['plan_report_id'],$data['plan_file_id']]]])->column('filepath');
            $att = new Attachment();
            foreach ($file_data as $v){
                if($v){
                    if(file_exists('.'.$v)){
                        unlink('.'.$v); //删除文件
                    }
                }
            }
            $att->deleteTb($data['plan_report_id']);
            $att->deleteTb($data['plan_file_id']);
            // 关联删除 --- 模型关联记录
            $model = new QualitymassModel();
            $model->deleteRelationById($id,2); // type 1 表示是实时进度关联 2 表示月进度关联
            //TODO 删除该月计划下的所有甘特图数据

            $this->where('id', $id)->delete();
            return ['code' => 1, 'msg' => '删除成功'];
        } catch (PDOException $e) {
            return ['code' => -1, 'msg' => $e->getMessage()];
        }
    }

    public function getOne($id)
    {
        $data = $this->find($id);
        return $data;
    }

    // 当前月度是否已经存在计划
    public function monthlyExist($plan_year,$plan_monthly)
    {
        $data = $this->where(['plan_year'=>$plan_year,'plan_monthly'=>$plan_monthly])->value('id');
        return $data;
    }

    // 根据选择的标段获取年度
    public function planYearList($section_id)
    {
        $data = $this->where('section_id',$section_id)->order('plan_year desc')->column('plan_year');
        return $data;
    }

    // 根据选择的标段获取月度
    public function planMonthlyList($section_id,$plan_year)
    {
        $data = $this->where(['section_id'=>$section_id,'plan_year'=>$plan_year])->order('plan_monthly desc')->column('plan_monthly');
        return $data;
    }

    // 月计划根据选择的标段，年度，月度获取甘特图数据
    public function initialiseData($section_id,$plan_year,$plan_monthly)
    {
        $data = $this->where(['section_id'=>$section_id,'plan_year'=>$plan_year])->order('plan_monthly desc')->select();
        return $data;
    }

}