<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/4/27
 * Time: 17:01
 */
/*
 * 档案管理-分支目录管理-项目分类
 * @package app\filemanagement\model
 */
namespace app\filemanagement\model;
use think\exception\PDOException;
use \think\Model;

class FilebranchModel extends Model
{
    protected $name='file_branch_directory';

    /**
     * 新增项目分类
     * @param $param
     * @return array
     */
    public function insertCate($param)
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
     * 编辑项目分类
     * @param $param
     * @return array
     */
    public function editCate($param)
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
     * 删除项目分类
     * @param $id
     * @return array
     */
    public function delCate($id)
    {
        try{
            $this->where("id",$id)->delete();
            return ['code' => 1, 'msg' => '删除成功'];
        }catch(PDOException $e){
            return ['code' => -1,'msg' => $e->getMessage()];
        }
    }

    /**
     * 判断当前节点下是否有数据
     * @param $id
     * @return array
     */
    public function judgeClassifyid($id)
    {
        $data = $this->where("classifyid",$id)->find();
        return $data;
    }

    /**
     * 判断当前节点下是否有数据
     * @param $id
     * @return array
     */
    public function judgeId($id)
    {
        $data = $this->where("pid",$id)->find();
        return $data;
    }

    /**
     * 获取一条项目分类的信息
     * @param $id
     * @throws \think\exception\DbException
     */
    public function getOne($id)
    {
        $data = $this->where("id",$id)->find();
        return $data;
    }

    /**
     * 查询一条图册下的所有的图片信息
     */
    public function getAllChild($id)
    {
        //定义一个空的数组
        $children = array();
        $data = $this
            ->field('serial_number,class_name,id,pid')
            ->where('pid', $id)->order("id","asc")
            ->select();
        if(!empty($data))
        {
            foreach ($data as $k=>$v)
            {
                $children[$k][] = $v['serial_number'];
                $children[$k][] = $v['class_name'];
                $children[$k][] = $v['pid'];
                $children[$k][] = $v['id'];
            }
        }
        return $children;
    }

    /**
     * 返回所有的数据
     */
    public function getAll($search)
    {
        $data = $this->where($search)->order("id","asc")->select();
        return $data;
    }

    /**
     * 返回所有的数据
     */
    public function getDateAll($id)
    {
        $data = $this->where("classifyid",$id)->order("id","asc")->select();
        return $data;
    }
}