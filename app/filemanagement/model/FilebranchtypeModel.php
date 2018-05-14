<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/4/27
 * Time: 15:26
 */
/*
 * 档案管理-分支目录管理-项目分类-树节点
 * @package app\filemanagement\model
 */
namespace app\filemanagement\model;
use think\exception\PDOException;
use \think\Model;

class FilebranchtypeModel extends Model
{
    protected $name='file_branch_directory_type';

    /**
     * 查询项目分类表中的所有的数据
     * @throws \think\exception\DbException
     */
    public function getall()
    {
        return $this->select();
    }

    /**
     * 新增节点
     * @throws \think\exception\DbException
     */
    public function insertNode($param)
    {
        try{
            $result = $this->allowField(true)->save($param);
            if(false === $result){
                return ['code' => -1,'msg' => $this->getError()];
            }else{
                $last_id = $this->getLastInsID();
                $data = $this->where("id",$last_id)->find();

                if(!empty($data)){
                    return ['code' => 1,'msg' => '添加成功','data'=>$data];
                }else{
                    return ['code' => -1,'msg' => $this->getError()];
                }
            }
        }catch (PDOException $e){
            return ['code' => -1,'msg' => $e->getMessage()];
        }
    }

    /**
     * 编辑节点
     * @param $param
     * @return array
     */
    public function editNode($param)
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
     * 删除节点
     * @param $id
     * @return array
     */
    public function delNode($id)
    {
        try{
            $this->where("id",$id)->delete();
            return ['code' => 1, 'msg' => '删除成功'];
        }catch(PDOException $e){
            return ['code' => -1,'msg' => $e->getMessage()];
        }
    }

    /**
     * 获取一个节点的信息
     * @throws \think\exception\DbException
     */
    public function getOne($id)
    {
        $data = $this->find($id);
        return $data;
    }
}