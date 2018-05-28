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
class CompleteGroupModel extends Model
{
    protected $name = 'model_complete_group';


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

    public function attributeArr($group_name)
    {
        // 获取当前启用的模型
        $version = new VersionsModel();
        $version_number = $version->statusOpen(1);
        // 获取该分组的所有属性
        $data = $this->where(['version_number'=>$version_number,'group_name'=>$group_name])->field('group_name,attribute_name,attribute_val')->select();
        return $data;
    }

    public function removeVersionsRelevance($version_number)
    {
        $this->where(['version_number'=>['eq',$version_number]])->delete();
        return ['code'=>1,'msg'=>'删除成功'];
    }

}