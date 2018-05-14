<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/5/2
 * Time: 17:32
 */
/*
 * 档案管理-工程项目管理
 * @package app\filemanagement\model
 */
namespace app\filemanagement\model;
use think\exception\PDOException;
use \think\Model;

class ProjectmanagementModel extends Model
{
    protected $name='file_project_management';

    /**
     * 获取一条项目的信息
     * @param $id
     * @throws \think\exception\DbException
     */
    public function getOne($id)
    {
        $data = $this->where("id",$id)->find();
        return $data;
    }

    /**
     * 新增项目
     * @param $param
     * @return array
     */
    public function insertPro($param)
    {
        try{
            $result = $this->allowField(true)->save($param);
            if(false === $result){
                return ['code' => -1,'msg' => $this->getError()];
            }else{
                return ['code' => 1,'msg' => '添加成功'];
            }
        }catch (PDOException $e){
            return ['code' => -1,'msg' => $e->getMessage()];
        }
    }

    /**
     * 编辑项目
     * @param $param
     * @return array
     */
    public function editPro($param)
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
     * 删除项目
     * @param $id
     * @return array
     */
    public function delPro($id)
    {
        try{
            $this->where("id",$id)->delete();
            return ['code' => 1, 'msg' => '删除成功'];
        }catch(PDOException $e){
            return ['code' => -1,'msg' => $e->getMessage()];
        }
    }

    /**
     * 获取所有的项目类别
     * @return array
     */
    public function getAllCategory()
    {
        $data = $this->field("id,project_category")->order("id asc")->select();//项目类别
        return $data;
    }
}