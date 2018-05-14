<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/3/29
 * Time: 9:34
 */
/*
 * 图纸文档管理，图册类型
 * @package app\archive\model
 */
namespace app\archive\model;
use think\exception\PDOException;
use \think\Model;

class AtlasCateTypeModel extends Model
{

    protected $name='archive_atlas_cate_type';

    /**
     * 查询所有的图册类型表中的数据
     */
    public function getall()
    {
        return $this->select();
    }

    /**
     * @param string $type
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getNodeInfo()
    {

            $result = $this->column('id,pid,name,sort_id');

            $result = tree($result);

            $sortArr = [];
            foreach ($result as $v){
                $sortArr[] = $v['sort_id'];
            }
            asort($sortArr);
            $str = "";
            foreach ($sortArr as $v){
                foreach($result as $key=>$vo){
                    if($v == $vo['sort_id']){
                        $str .= '{ "id": "' . $vo['id'] . '", "pid":"' . $vo['pid'] . '", "name":"' . $vo['name'].'"'.',"sort_id":"'.$vo['sort_id'].'","level":"'.$vo['level'].'"';
                        $str .= '},';
                    }
                }
            }

        return "[" . substr($str, 0, -1) . "]";
    }

    /**
     * 添加图册类型节点
     */
    public function insertCatetype($param)
    {
        try{
            $result = $this->allowField(true)->save($param);
            if(false === $result){
                return ['code' => -1,'msg' => $this->getError()];
            }else{
                $last_id = $this-> getLastInsID();

                $res = $this->where('id',$last_id)->update(['sort_id' => $last_id]);

                $data = $this->where("id",$last_id)->find();

                if($res){
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
     * 编辑图册类型节点
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

    /**
     * 删除图册类型节点
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

    /**
     * 获取一个图册类型节点信息
     */
    public function getOne($id)
    {
        $data = $this->find($id);
        return $data;
    }

    /**
     * 采用递归方法获得所有的节点
     * @param $id
     * @return array
     */
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
}