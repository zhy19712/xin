<?php
/**
 * Created by PhpStorm.
 * User: zhifang
 * Date: 2018/4/12
 * Time: 14:00
 */
namespace app\quality\model;
use think\exception\PDOException;
use think\Model;

/**
 * 划分树-工序-控制点 关联模型
 * Class DivisionControlPointModel
 * @package app\quality\model
 */
class  DivisionControlPointModel extends Model
{
    protected $name='quality_division_controlpoint_relation';

    public function insertTb($param)
    {
        try {
            $result = $this->allowField(true)->save($param);
            if (false === $result) {
                return ['code' => -1, 'msg' => $this->getError()];
            } else {
                return ['code' => 1, 'data' => [], 'msg' => '添加成功'];
            }
        } catch (PDOException $e) {
            return ['code' => -1, 'msg' => $e->getMessage()];
        }
    }

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

    public function delRelation($id,$type)
    {
        try {
            $this->where(['type'=>$type,'division_id'=>$id])->delete();
            return ['code' => 1, 'msg' => '删除成功'];
        } catch (PDOException $e) {
            return ['code' => -1, 'msg' => $e->getMessage()];
        }
    }

    /**
     * 关联控制点
     */
    public function ControlPoint()
    {
       return $this->hasOne('app\standard\model\ControlPoint','id','control_id');
    }

    /**
     * 关联划分树
     */
    public function Division()
    {
      return  $this->hasOne('DivisionModel','id','division_id');
    }

    /**
     * 关联工序
     */
    public function Procedure()
    {
       return $this->hasOne('app\standard\model\MaterialTrackingDivision','id','ma_division_id');
    }

    /**
     * 编辑工程划分、工序、控制点关系表
     * @param $param
     * @return array
     */
    public function editRelation($param)
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
     * 编辑工程划分、工序、控制点关系表全部编辑
     * @param $param
     * @return array
     */
    public function editAll($search,$data)
    {
        try {
            if($search["ma_division_id"] != 0)
            {
                $result = $this->allowField(true)->save($data, ['division_id' => $search['division_id'],"ma_division_id"=>$search["ma_division_id"],"type"=>$search["type"]]);
            }else
            {
                $result = $this->allowField(true)->save($data, ['division_id' => $search['division_id'],"type"=>$search["type"]]);
            }
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
     * 编辑工程划分、工序、控制点关系表全部编辑
     * @param $param
     * @return array
     */
    public function editNoAll($search,$data)
    {
        try {
            if($search["ma_division_id"] != 0)
            {
                $result = $this->allowField(true)->save($data, ['division_id' => $search['division_id'],"ma_division_id"=>$search["ma_division_id"],"type"=>$search["type"]]);
            }else
            {
                $result = $this->allowField(true)->save($data, ['division_id' => $search['division_id'],"type"=>$search["type"]]);
            }

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
     * 获取一条信息
     */
    public function getOne($id)
    {
        $data = $this->where('id', $id)->find();
        return $data;
    }

    public function delRelationAll($ma_division_id,$cid,$type)
    {
        try {
            $this->where(['type'=>$type,'ma_division_id'=>$ma_division_id,'control_id'=>$cid])->delete();
            return ['code' => 1, 'msg' => '删除成功'];
        } catch (PDOException $e) {
            return ['code' => 0, 'msg' => $e->getMessage()];
        }
    }
}