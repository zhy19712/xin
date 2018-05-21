<?php
/**
 * Created by PhpStorm.
 * User: zhifang
 * Date: 2018/4/8
 * Time: 15:30
 */
namespace app\standard\model;
use app\quality\model\DivisionModel;
use think\Model;

class ControlPoint extends Model
{
    protected $name='norm_controlpoint';

    public function insertTb($param)
    {
        try{
            $result = $this->allowField(true)->save($param);
            if(1 == $result){

                // 批量添加工程划分--单位工程或者分部工程的对应 控制点   $type 1单位 2分部 3单元
                $division = new DivisionModel();

                $flag = $division->addRelation($this->getLastInsID(),$param['type'],$param["procedureid"]);

                return ['code' => 1,'msg' => '添加成功'];
            }else{
                return ['code' => -1,'msg' => $this->getError()];
            }
        }catch (PDOException $e){
            return ['code' => -1,'msg' => $e->getMessage()];
        }
    }


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