<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/4/8
 * Time: 9:22
 */
/**
 * 日常质量管理，现场图片
 * Class ScenePictureModel
 * @package app\quality\model
 */
namespace app\quality\model;
use think\Model;
use think\exception\PDOException;

class ScenePictureModel extends Model
{
    protected $name='quality_scene_picture';

    /**
     * 查询现场图片管理表中的所有的数据
     */
    public function getall()
    {
        return $this->group('name,pid')->order("month","asc")->order("year","asc")->order("day","asc")->Distinct(true)->field("id,name,pid")->select();
    }

    /**
     * 获取一条现场图片信息
     */
    public function getOne($id)
    {
        $data = $this->where('id', $id)->find();
        return $data;
    }

    /**
     * 查询表中的年、月、日是否存在
     */
    public function getid($search_info)
    {
        $data = $this->field("id,pid")->where($search_info)->find();
        return $data;
    }

    /**
     * 新增一条现场图片表中的信息
     */
    public function insertScene($param)
    {
        try{
            $result = $this->allowField(true)->insert($param);
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
     * 编辑一条现场图片信息
     */
    public function editScene($param)
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
     * 删除一条现场图片信息
     */
    public function delScene($id)
    {
        try{
            $this->where("id",$id)->delete();
            return ['code' => 1, 'msg' => '删除成功'];
        }catch(PDOException $e){
            return ['code' => -1,'msg' => $e->getMessage()];
        }
    }

    /**
     * 获取所属同一个pid下的现场图片的数量
     */
    public function getcount($pid)
    {
        return $this->field("pid")->where("pid",$pid)->count();
    }
}