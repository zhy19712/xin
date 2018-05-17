<?php
/**
 * Created by PhpStorm.
 * User: zhifang
 * Date: 2018/4/8
 * Time: 9:46
 */
/*
 * 填报表单模板
 * @package app\standard\model
 */
namespace app\standard\model;
use think\Model;

class  TemplateModel extends  Model
{
    protected $name="norm_template";

    /**
     * 首先查询所有的填报表单模板
     * @throws \think\Exception
     */
    public function getAllTemplate()
    {
        $data = $this->field("id,code,name")->order("id asc")->select();

        //如果数据为空返回空的数组
        if(empty($data))
        {
            return ["code"=>-1,"msg"=>"模板列表为空！"];
        }
        return ["code"=>1,"data"=>$data];
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