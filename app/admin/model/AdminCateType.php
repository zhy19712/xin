<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/3/26
 * Time: 10:19
 */
namespace app\admin\model;

use think\Model;
use think\exception\PDOException;

class AdminCateType extends Model
{

    /*
     * 查询所有的角色类型表中的数据
     */

    public function getall()
    {
        return $this->select();
    }

    /*
     * 添加角色类型节点
     */
    public function insertCatetype($param)
    {
        try{
            $result = $this->allowField(true)->save($param);
            if(false === $result){
                return ['code' => -1,'msg' => $this->getError()];
            }else{
                $last_id = $this-> getLastInsID();
                $data = $this->where("id",$last_id)->find();
                return ['code' => 1,'msg' => '添加成功','data'=>$data];
            }
        }catch (PDOException $e){
            return ['code' => -1,'msg' => $e->getMessage()];
        }
    }
    /*
     * 编辑角色类型节点
     */
    public function editCatetype($param)
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

    /*
     * 删除角色类型节点
     */
    public function delCatetype($id)
    {
        try{
            $this->where("id",$id)->delete();
            return ['code' => 1, 'msg' => '删除成功'];
        }catch(PDOException $e){
            return ['code' => -1,'msg' => $e->getMessage()];
        }
    }

    /*
     * 获取一个角色类型节点信息
     */
    public function getOne($id)
    {
        $data = $this->find($id);
        return $data;
    }
}