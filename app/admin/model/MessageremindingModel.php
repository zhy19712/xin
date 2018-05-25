<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/4/9
 * Time: 14:21
 */
/**
 * 控制面板-消息提醒
 * Class MessageremindingModel
 * @package app\admin\model;
 */
namespace app\admin\model;
use think\Model;
use think\exception\PDOException;

class MessageremindingModel extends Model
{
    protected $name='admin_message_reminding';

    /**
     * 获取一条记录信息
     */
    public function getOne($data)
    {
        $data = $this->where($data)->find();
        return $data;
    }

    /**
     * 查询消息表中未处理的数量
     */
    public function getCount($admin_id)
    {
        //查询状态
        $data = $this->where('status = 1')->where("current_approver_id",$admin_id)->count();
        return $data;
    }

    /**
     * 新增一条记录中的信息
     */
    public function insertTb($param)
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
     * 批量添加
     * @param $param
     * @return array
     */
    public function insertTbAll($param)
    {
        try {
            $result = $this->allowField(true)->insertAll($param);
            if (false === $result) {
                return ['code' => -1, 'msg' => $this->getError()];
            } else {
                return ['code' => 1, 'data' => [], 'msg' => '添加成功'];
            }
        } catch (PDOException $e) {
            return ['code' => -1, 'msg' => $e->getMessage()];
        }
    }

    /**
     * 编辑全部信息
     */
    public function editTbAll($param)
    {
        try{
            $result = $this->allowField(true)->saveAll($param);
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
     * 编辑一条信息
     */
    public function editTb($param)
    {
        try {
            $result = $this->allowField(true)->save($param, ['id' => $param['id']]);
            if (false === $result) {
                return ['code' => -1, 'msg' => $this->getError()];
            } else {
                return ['code' => 1, 'msg' => '编辑成功'];
            }
        } catch (PDOException $e) {
            return ['code' => 0, 'msg' => $e->getMessage()];
        }
    }

    /**
     * 删除一条记录
     */
    public function delTb($id)
    {
        try{
            $this->where(["uint_id"=>$id,"type"=>2])->delete();
            return ['code' => 1, 'msg' => '删除成功'];
        }catch(PDOException $e){
            return ['code' => -1,'msg' => $e->getMessage()];
        }
    }
}