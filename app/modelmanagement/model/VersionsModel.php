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
class VersionsModel extends Model
{
    protected $name = 'model_version_management';


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
            $data = $this->getOne($param['id']);
            $count = $this->where(['model_type'=>$data['model_type']])->count();
            if($count == 1 && $data['status'] == 1){
                return ['code' => -1,'status'=>1, 'msg' => '不能禁用所有版本,至少保留一个版本为启用状态'];
            }

            $result = $this->where(['id' => ['neq',$param['id']],'model_type'=>$data['model_type']])->update(['status'=>0]);
            $result = $this->where(['id' => $param['id'],'model_type'=>$data['model_type']])->update(['status'=>1]);

            if (false === $result) {
                return ['code' => -1, 'msg' => $this->getError()];
            } else {
                return ['code' => 1,'status'=>1, 'msg' => '启用成功'];
            }
        } catch (PDOException $e) {
            return ['code' => 0, 'msg' => $e->getMessage()];
        }
    }

    public function deleteTb($id)
    {
        try {
            $data = $this->getOne($id);
            if($data['resource_path']){
                //TODO 先测试E盘能否成功，成功后 修改为 G盘

                $file_path = 'E:\WebServer'.$data['resource_path'];
                if(file_exists($file_path)){
                    unlink($file_path);
                }
            }
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

    public function versionNumber()
    {
        $data = $this->order('id desc')->field('version_number')->find();
        if(empty($data)){
            $version_number = 'V1.0';
        }else{
            $arr = explode('.',$data['version_number']);
            $num = $arr[1] + 1;
            if($num <= 9){
                $version_number = $arr[0] . '.' . $num;
            }else{
                $new_arr = explode('V',$arr[0]);
                $version_number = 'V' . ($new_arr[0]+1) . '.0';
            }
        }
        return $version_number;
    }

    public function isOnly($id)
    {
        $data = $this->getOne($id);
        $count = $this->where(['model_type'=>$data['model_type']])->count();
        if($count == 1){
            return ['code' => -1,'msg' => '当前版本是唯一的,不能删除'];
        }
        return ['code' => 1,'version_number'=>$data['version_number'],'msg' => '当前版本号'];
    }

    public function statusOpen($model_type)
    {
        $data = $this->where(['model_type'=>$model_type,'status'=>1])->value('version_number');
        return $data;
    }

}