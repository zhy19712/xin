<?php
/**
 * Created by PhpStorm.
 * User: sir
 * Date: 2018/5/22
 * Time: 13:36
 */
namespace app\modelmanagement\model;

use think\exception\PDOException;
use think\Model;
class ConfigureModel extends Model
{
    protected $name = 'model_configure';


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

    public function configureId($model_type)
    {
        $id = $this->where('model_type',$model_type)->value('id');
        return $id;
    }

    // type 1 全景3D模型 和 质量3D模型 2 进度模拟 和 进度对比 3 实时进度展示
    public function getConfigure($type)
    {
        // model_type 1 全景3D模型 2 质量3D模型 3 进度模拟 4 进度对比 5 实时进度展示
        if($type == 1){
            $data['panorama'] = $this->where(['model_type'=>1])->find(); // 全景3D模型
            $data['quality'] = $this->where(['model_type'=>2])->find(); // 质量3D模型
        }else if($type == 2){
            $data['imitate'] = $this->where(['model_type'=>3])->find(); // 进度模拟
            $data['contrast'] = $this->where(['model_type'=>4])->find(); // 进度对比
        }else{
            $data['actual'] = $this->where(['model_type'=>5])->find(); // 实时进度展示
        }
        return $data;
    }
}