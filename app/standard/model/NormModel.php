<?php
/**
 * Created by PhpStorm.
 * User: sir
 * Date: 2018/3/30
 * Time: 18:09
 */

namespace app\standard\model;

use think\exception\PDOException;
use think\Model;
use think\Db;

class NormModel extends Model
{
    protected $name = 'norm_file';
    //自动写入创建、更新时间 insertGetId和update方法中无效，只能用于save方法
    protected $autoWriteTimestamp = true;

    public function getNodeInfo()
    {
        $result = Db::name('norm')->column('id,pid,name');
        $str = "";
        foreach($result as $key=>$vo){
            $str .= '{ "id": "' . $vo['id'] . '", "pId":"' . $vo['pid'] . '", "name":"' . $vo['name'].'"';
            $str .= '},';
        }
        return "[" . substr($str, 0, -1) . "]";
    }

    public function insertTb($param)
    {
        try{
            $result = $this->allowField(true)->save($param);
            if(1 == $result){
                return ['code' => 1,'msg' => '添加成功'];
            }else{
                return ['code' => -1,'msg' => $this->getError()];
            }
        }catch (PDOException $e){
            return ['code' => -1,'msg' => $e->getMessage()];
        }
    }

    public function editTb($param)
    {
        try{
            $result = $this->allowField(true)->save($param,['id' => $param['id']]);
            if(false === $result){
                return ['code' => -1,'msg' => $this->getError()];
            }else{
                return ['code' => 1, 'msg' => '编辑成功'];
            }
        }catch(PDOException $e){
            return ['code' => 0, 'msg' => $e->getMessage()];
        }
    }

    public function deleteTb($id)
    {
        try{
            $data = $this->getOne($id);
            $filePath = Db::name('attachment')->where('id',$data['file_id'])->column('filepath');
            if(sizeof($filePath) > 0){
                if(file_exists($filePath[0])){
                    unlink($filePath[0]); //删除文件
                }
            }
            $this->where('id',$id)->delete();
            return ['code' => 1, 'msg' => '删除成功'];
        }catch(PDOException $e){
            return ['code' => -1,'msg' => $e->getMessage()];
        }
    }

    public function getOne($id)
    {
        $data = $this->find($id);
        return $data;
    }

    //递归获取当前节点的所有子节点
    public function cateTree($id){
        $res = Db::name('norm')->column('id,pid');
        if(!empty($res)){
            $result=$this->sort($res, $id);
            return $result;
        }
    }
    public function sort($data,$id){
        static $arr=array();
        foreach ($data as $key=>$value){
            if($value == $id){
                $arr[] = $key;
                $this->sort($data,$key);
            }
        }
        return $arr;
    }

}