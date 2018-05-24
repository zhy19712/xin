<?php
/**
 * Created by PhpStorm.
 * User: sir
 * Date: 2018/5/22
 * Time: 13:36
 */
namespace app\modelmanagement\model;

use app\quality\model\DivisionModel;
use think\Db;
use think\exception\PDOException;
use think\Model;
class QualitymassModel extends Model
{
    protected $name = 'model_quality';


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

    public function getSection()
    {
        $data = $this->group('section')->field('section')->select();
        return $data;
    }

    public function getUnit()
    {
        $data = $this->group('unit')->field('unit')->select();
        return $data;
    }

    public function getParcel()
    {
        $data = $this->group('parcel')->field('parcel')->select();
        return $data;
    }

    public function getCell()
    {
        $data = $this->group('cell')->field('cell')->select();
        return $data;
    }

    public function pile_number_1()
    {
        $data = $this->group('pile_number_1')->field('pile_number_1')->select();
        return $data;
    }


    public function pile_number_2()
    {
        $data = $this->group('pile_number_2')->field('pile_number_2')->select();
        return $data;
    }

    public function pile_number_3()
    {
        $data = $this->group('pile_number_3')->field('pile_number_3')->select();
        return $data;
    }

    public function pile_number_4()
    {
        $data = $this->group('pile_number_4')->field('pile_number_4')->select();
        return $data;
    }

    // 解除所有的关联关系
    public function removeRelevance($id_arr)
    {
        $this->where(['id'=>['in',$id_arr]])->update(['unit_id'=>0]);
        return ['code'=>1,'msg'=>'解除成功'];
    }

    public function relevance($id,$id_arr)
    {
        // 关联
        $this->where(['id'=>['in',$id_arr]])->update(['unit_id'=>$id]);
        return ['code'=>1,'msg'=>'关联成功'];
    }

    public function removeVersionsRelevance($version_number)
    {
        $this->where(['version_number'=>['eq',$version_number]])->delete();
        return ['code'=>1,'msg'=>'删除成功'];
    }

    public function nodeModelNumber($add_id,$node_type)
    {
        if($node_type == 1){
            // 获取选中节点下包含的所有子节点编号
            $division = new DivisionModel();
            $child_node_id = [];
            $child_node_obj = $division->cateTree($add_id);
            foreach ($child_node_obj as $v){
                $child_node_id[] = $v['id'];
            }
            $child_node_id[] = $add_id;
            // 获取此节点下包含的所有单元工程检验批
            $unit_id = Db::name('quality_unit')->where(['division_id'=>['in',$child_node_id]])->column('id');
        }else{
            $unit_id[] = $add_id;
        }
        // 获取所有单元工程检验批 所关联的模型编号
        $model_id = [];
        if(sizeof($unit_id)){
            $model_id = $this->where(['unit_id'=>['in',$unit_id]])->column('model_id');
        }
        return $model_id;
    }

}