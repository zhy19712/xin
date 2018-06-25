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

class ActualModel extends Model
{
    protected $name = 'progress_actual';

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
            $att = new Attachment();
            $att->deleteTb($data['attachment_id']);
            if(file_exists($data['path'])){
                unlink($data['path']); //删除文件
            }

            // 关联删除 --- 模型关联记录
            $model = new QualitymassModel();
            $model->deleteRelationById($id,1);

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

    public function dateScope($section_id)
    {
        $data = $this->where(['section_id'=>$section_id])->field('min(actual_date) as date_start,max(actual_date) as date_end')->find();
        return $data;
    }

    // 当选择了区间时间后,前台传递,选择的标段编号 section_id 和 开始时间 date_start  结束时间 date_end
    // 会多返回最左侧的[未建，在建，完建]的数据
    public function actualInfo($section_id,$date_start,$date_end)
    {
        $data = Db::name('progress_actual')->alias('a')
            ->join('admin u','u.id=a.user_id','left')
            ->where(['a.section_id'=>$section_id,'a.actual_date'=>['between',[$date_start,$date_end]]])->field('a.actual_date,u.name')->select();
        return $data;
    }

    // 填报记录所属标段
    public function sectionCode($id)
    {
        $code = Db::name('progress_actual')->alias('a')
            ->join('section s','s.id = a.section_id','left')
            ->where(['a.id'=>$id])->value('code');
        return $code;
    }
}