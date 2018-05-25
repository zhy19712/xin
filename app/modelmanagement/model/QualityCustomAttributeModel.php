<?php
/**
 * Created by PhpStorm.
 * User: sir
 * Date: 2018/5/22
 * Time: 13:36
 */
namespace app\modelmanagement\model;

use think\Db;
use think\exception\PDOException;
use think\Model;
class QualityCustomAttributeModel extends Model
{
    protected $name = 'quality_custom_attribute';


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

    // 单元工程编号 或者 模型图编号 number   编号类型 number_type 1 单元工程编号 2 模型编号
    public function getAttrTb($number,$number_type)
    {
        $unit_id = $number;
        if($number_type == 1){
            $unit_id= Db::name('model_quality')->where('model_id',$number)->value('unit_id');
        }
        $attr = $this->where(['unit_id'=>$unit_id])->field('id as attrId,attr_name as attrKey,attr_value as attrVal')->select();
        return ['code'=>1,'attr'=>$attr,'msg'=>'模型图自定义属性'];
    }

}