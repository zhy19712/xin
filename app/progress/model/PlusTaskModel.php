<?php
/**
 * Created by PhpStorm.
 * User: sir
 * Date: 2018/6/14
 * Time: 15:52
 */

namespace app\progress\model;


use think\Model;

class PlusTaskModel extends Model
{
    protected $name = 'progress_plus_task';

    // $project_type 1月计划2年计划3总计划
    public function tasksData($project_type,$uid)
    {
        $data = $this->where(['project_type'=>$project_type,'project_uid'=>$uid])->select();
        return $data;
    }

}