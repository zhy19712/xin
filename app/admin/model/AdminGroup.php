<?php
/**
 * Created by PhpStorm.
 * User: sir
 * Date: 2018/3/23
 * Time: 13:58
 */

namespace app\admin\model;


use think\exception\PDOException;
use think\Model;
use think\Db;

class AdminGroup extends Model
{
    //自动写入创建、更新时间 insertGetId和update方法中无效，只能用于save方法
    protected $autoWriteTimestamp = true;

    public function getNodeInfo($type = 'group')
    {
        if($type == 'group'){
            $result = $this->column('id,pid,name,category,sort_id');

            $sortArr = [];
            foreach ($result as $v){
                $sortArr[] = $v['sort_id'];
            }
            asort($sortArr);
            $str = "";
            foreach ($sortArr as $v){
                foreach($result as $key=>$vo){
                    if($v == $vo['sort_id']){
                        $str .= '{ "id": "' . $vo['id'] . '", "pId":"' . $vo['pid'] . '", "name":"' . $vo['name'].'"'.',"category":"'.$vo['category'].'"'.',"sort_id":"'.$vo['sort_id'].'"';
                        $str .= '},';
                    }
                }
            }
        }else{
            $result = Db::name('admin_cate')->field('id,pid,role_name')->select();
            $str = "";
            foreach($result as $key=>$vo){
                $str .= '{ "id": "' . $vo['id'] . '", "pId":"' . $vo['pid'] . '", "name":"' . $vo['role_name'].'"';
                $str .= '},';
            }
        }
        return "[" . substr($str, 0, -1) . "]";
    }

    public function isParent($id)
    {
        $is_exist = $this->where('pid',$id)->find();
        return $is_exist;
    }


    public function insertTb($param)
    {
        try{
            $result = $this->allowField(true)->save($param);
            $id = $this->getLastInsID();
            $result = $this->where('id',$id)->update(['sort_id' => $id]);
            $data = $this->getOne($id);
            if(1 == $result){
                return ['code' => 1,'data' => $data,'msg' => '添加成功'];
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

    /*
     * 查询所有组织机构表中的所有数据
     */

    public function getall()
    {
        return $this->field("id,pid,name")->select();
    }

    //递归获取当前节点的所有子节点
    public function cateTree($id){
        $res=$this->getall();
        if($res){
            $result=$this->sort($res, $id);
            return $result;
        }
    }
    public function sort($data,$id){
        static $arr=array();
        foreach ($data as $key=>$value){
            if($value['pid'] == $id){
                $arr[]=$value['id'];
                $this->sort($data,$value['id']);
            }
        }
        return $arr;
    }

    /**
     * 查询组织机构表中的所有的机构名，丰宁抽水蓄能电站下的所有机构名
     */
    public function getfengning()
    {
        return $this->field("id,name")->where("pid > 0")->select();
    }
}