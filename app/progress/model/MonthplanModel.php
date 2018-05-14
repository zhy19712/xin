<?php

namespace app\progress\model;

use think\Model;
use think\exception\PDOException;

class MonthplanModel extends Model
{
    protected $name='progress_monthplan';

    /**
     * 查询监理日志表中的所有的数据
     */
    public function getall()
    {
        return $this->group('id')->order("month","asc")->order("year","asc")->order("day","asc")->Distinct(true)->field("*")->select();
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
     * 新增一条监理日志表中的信息
     */
    public function insertLog($param)
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
     * 编辑一条监理日志记录
     */
    public function editLog($param)
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
     * 删除一条监理日志信息
     */
    public function delLog($id)
    {
        try{
            $this->where("id",$id)->delete();
            return ['code' => 1, 'msg' => '删除成功'];
        }catch(PDOException $e){
            return ['code' => -1,'msg' => $e->getMessage()];
        }
    }

    /**
     * 获取所属同一个pid下的监理日志的数量
     */
    public function getcount($pid)
    {
        return $this->field("pid")->where("pid",$pid)->count();
    }
}