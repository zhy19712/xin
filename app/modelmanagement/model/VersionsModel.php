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

    public function editTb($id)
    {
        try {
            $data = $this->getOne($id);
            $count = $this->where(['model_type'=>$data['model_type']])->count();
            if($count == 1 && $data['status'] == 1){
                return ['code' => -1,'status'=>1, 'msg' => '不能禁用所有版本,至少保留一个版本为启用状态'];
            }

            $result = $this->where(['id' => ['neq',$id],'model_type'=>$data['model_type']])->update(['status'=>0]);
            $result = $this->where(['id' => $id,'model_type'=>$data['model_type']])->update(['status'=>1]);

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
                $file_path = 'E:\WebServer'.$data['resource_path'];
                if(file_exists($file_path)){
                    unlink($file_path);
                }

                // 删除解压的文件夹 和 文件
                $file_name = $data['resource_name'];
                if($data['model_type'] == 1){ // 1竣工模型 2施工模型
                    $path = 'E:\WebServer\Resources' . '\\' . $file_name . '\\';
                }else{
                    $path = 'E:\WebServer\Resources' . '\\' . $file_name .'\\';
                }
                $this->deldir($path);
                @rmdir($path);
                Db::name('attachment')->where(['id'=>$data['attachment_id']])->delete();
            }
            $this->where('id', $id)->delete();
            return ['code' => 1, 'msg' => '删除成功'];
        } catch (PDOException $e) {
            return ['code' => -1, 'msg' => $e->getMessage()];
        }
    }

    function deldir($path){
        //如果是目录
        if(is_dir($path)){
            //扫描一个文件夹内的所有文件夹和文件并返回数组
            $p = scandir($path);
            foreach($p as $val){
                //排除目录中的.和..
                if($val !="." && $val !=".."){
                    //如果是目录则递归子目录，继续操作
                    if(is_dir($path.$val)){
                        //子目录中操作删除文件夹和文件
                        $this->deldir($path.$val.'/');
                        //目录清空后删除空文件夹
                        @rmdir($path.$val.'/');
                    }else{
                        //如果是文件直接删除
                        unlink($path.$val);
                    }
                }
            }
        }
    }

    public function getOne($id)
    {
        $data = $this->find($id);
        return $data;
    }

    public function versionNumber($model_type)
    {
        $data = $this->where(['model_type'=>$model_type])->order('id desc')->field('version_number')->find();
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
        return ['code' => 1,'version_number'=>$data['version_number'],'model_type'=>$data['model_type'],'msg' => '当前版本号'];
    }

    public function statusOpen($model_type)
    {
        $data = $this->where(['model_type'=>$model_type,'status'=>1])->value('version_number');
        return $data;
    }

    public function prevVersionNumber($current_version_number)
    {
        $arr = explode('V',$current_version_number);
        $version_number = 'V' . ($arr[1] - 0.1);
        if(!strpos($version_number,'.')){
            $version_number = $version_number . '.0';
        }
        $prev_version_number = $this->where(['version_number'=>$version_number])->value('version_number');
        return $prev_version_number;
    }

    public function getPagName($model_type)
    {
        // 获取当前启用的模型
        $version = new VersionsModel();
        $version_number = $version->statusOpen($model_type);
        // 获取资源包名称
        $resource_name = $this->where(['model_type'=>$model_type,'version_number'=>$version_number])->value('resource_name');
        return $resource_name;
    }

}