<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/5/5
 * Time: 17:31
 */
/*
 * 档案管理-档案管理-待整理文件
 * @package app\filemanagement\model
 */
namespace app\filemanagement\model;
use think\exception\PDOException;
use \think\Model;

class Filependingdocuments extends Model
{
    protected $name='file_pending_documents';

    /**
     * 新增整理文件
     * @param $param
     * @return array
     */
    public function insertPd($param)
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
     * 编辑整理文件
     * @param $param
     * @return array
     */
    public function editPd($param)
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
     * 删除整理文件
     * @param $id
     * @return array
     */
    public function delPd($id)
    {
        try{
            $this->where("id",$id)->delete();
            return ['code' => 1, 'msg' => '删除成功'];
        }catch(PDOException $e){
            return ['code' => -1,'msg' => $e->getMessage()];
        }
    }

    /**
     * 获取一条整理文件信息
     * @param $id
     * @throws \think\exception\DbException
     */
    public function getOne($id)
    {
        $data = $this->where("id",$id)->find();
        return $data;
    }
}