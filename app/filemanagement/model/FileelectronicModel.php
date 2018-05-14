<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/5/8
 * Time: 10:52
 */
/*
 * 档案管理-档案管理-待整理文件-电子文件挂接
 * @package app\filemanagement\model
 */
namespace app\filemanagement\model;
use think\exception\PDOException;
use \think\Model;

class FileelectronicModel extends Model
{
    protected $name='file_electronic_file_connection';

    /**
     * 新增电子文件挂接
     * @param $param
     * @return array
     */
    public function insertFe($param)
    {
        try{
            $result = $this->allowField(true)->save($param);
            if(false === $result){
                return ['code' => -1,'msg' => $this->getError()];
            }else{
                $id = $this->getLastInsID();
                return ['code' => 1,'id'=>$id,'msg' => '添加成功'];
            }
        }catch (PDOException $e){
            return ['code' => -1,'msg' => $e->getMessage()];
        }
    }

    /**
     * 编辑电子文件挂接
     * @param $param
     * @return array
     */
    public function editFe($param)
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

    /**
     * 删除电子文件挂接
     * @param $id
     * @return array
     */
    public function delFe($id)
    {
        try{
            $this->where("id",$id)->delete();
            return ['code' => 1, 'msg' => '删除成功'];
        }catch(PDOException $e){
            return ['code' => -1,'msg' => $e->getMessage()];
        }
    }

    /**
     * 获取一条电子文件挂接
     * @param $id
     * @throws \think\exception\DbException
     */
    public function getOne($id)
    {
        $data = $this->where("id",$id)->find();
        return $data;
    }

    /**
     * 判断是否有当前添加的节点
     * @param $selfid
     * @param $procedureid
     * @param $procedureid
     */
    public function getid($type,$fpd_id,$val)
    {
        $data = $this->where(["fpd_id"=>$fpd_id,"type"=>$type,"file_id"=>$val])->find();
        return $data;
    }
}