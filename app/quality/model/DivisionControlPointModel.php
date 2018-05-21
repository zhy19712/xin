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
     * 获取一条信息
     */
    public function getOne($id)
    {
        $data = $this->where('id', $id)->find();
        return $data;
    }
}