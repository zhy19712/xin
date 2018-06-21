<?php
/**
 * Created by PhpStorm.
 * User: sir
 * Date: 2018/6/14
 * Time: 15:52
 */

namespace app\progress\model;


use think\exception\PDOException;
use think\Model;

class PlusProjectModel extends Model
{
    protected $name = 'progress_plus_project';

    public function insertTb($param)
    {
        try {
            $result = $this->allowField(true)->save($param);
            if (false === $result) {
                return ['code' => -1, 'msg' => $this->getError()];
            } else {
                return ['code' => 1, 'msg' => '添加成功'];
            }
        } catch (PDOException $e) {
            return ['code' => -1, 'msg' => $e->getMessage()];
        }
    }

    public function deleteTb($project_type,$uid)
    {
        try {
            //TODO 删除该月计划下的所有甘特图数据

            $this->where(['project_type'=>$project_type,'uid'=>$uid])->delete();
            return ['code' => 1, 'msg' => '删除成功'];
        } catch (PDOException $e) {
            return ['code' => -1, 'msg' => $e->getMessage()];
        }
    }

    public function getOne($project_type,$uid)
    {
        return $this->where(['project_type'=>$project_type,'uid'=>$uid])->find();
    }

}