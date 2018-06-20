<?php
/**
 * Created by PhpStorm.
 * User: sir
 * Date: 2018/6/14
 * Time: 15:52
 */

namespace app\progress\model;


use app\admin\model\Attachment;
use app\modelmanagement\model\QualitymassModel;
use think\Db;
use think\exception\PDOException;
use think\Model;

class PlusProjectModel extends Model
{
    protected $name = 'progress_plus_project';

    public function projectObj()
    {
        return $this->select();
    }

    public function projectData($project_uid)
    {
        $data = $this->where(['uid'=>$project_uid])->find();
        return $data;
    }

}