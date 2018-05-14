<?php
/**
 * Created by PhpStorm.
 * User: zhifang
 * Date: 2018/4/8
 * Time: 15:30
 */
namespace app\standard\model;
use think\Model;

class ControlPoint extends Model
{
    protected $name='norm_controlpoint';
    /**
     * 获取节点下所有子节点
     * @param $id
     * @return array
     * @throws \think\exception\DbException
     */
    public function getChilds($id)
    {
        $list=MaterialTrackingDivision::all();
        if ($list)
        {
            return $this->_getChilds($list,$id);
        }
    }
    function _getChilds($list,$id)
    {
        $nodeArray=array();
        foreach ($list as $item)
        {
            if ($item['pid']==$id){
                $nodeArray[]=$item['id'];
                $nodeArray= array_merge($nodeArray, $this->_getChilds($list,$item['id']));
            }
        }
        return $nodeArray;
    }

    /**
     * 获取一条信息
     */
    public function getOne($id)
    {
        $data = $this->where('id', $id)->find();
        return $data;
    }
}