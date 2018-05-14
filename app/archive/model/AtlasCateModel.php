<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/3/29
 * Time: 14:12
 */
/*
 * 图纸文档管理，图册文件
 * @package app\archive\model
 */
namespace app\archive\model;
use think\exception\PDOException;
use \think\Model;

class AtlasCateModel extends Model
{
    protected $name='archive_atlas_cate';

    /**
     * 新增一条图册记录
     */
    public function insertCate($param)
    {
        try{
            $result = $this->allowField(true)->save($param);
            if(false === $result){
                return ['code' => -1,'msg' => $this->getError()];
            }else{
                return ['code' => 1,'msg' => '添加成功'];
            }
        }catch (PDOException $e){
            return ['code' => -1,'msg' => $e->getMessage()];
        }
    }

    /**
     * 查询同一类别，同一selfid下的最大序号cate_number
     */
    public function maxcatenumber($selfid)
    {
        $catenumber = $this->where("selfid",$selfid)->max("cate_number");
        return $catenumber;
    }

    /**
     * 编辑一条图册记录
     */
    public function editCate($param)
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

    /*
     * 根据图册id删除一条图册记录
     */
    public function delCate($id)
    {
        try{
            $this->where("id",$id)->delete();
            return ['code' => 1, 'msg' => '删除成功'];
        }catch(PDOException $e){
            return ['code' => -1,'msg' => $e->getMessage()];
        }
    }

    /**
    * 获取一条图册类型信息
    */
    public function getOne($id)
    {
        $data = $this->where('id', $id)->find();
        return $data;
    }

    /**
     * 根据id查询该id下是否存在子类
     */
    public function judge($id)
    {
        $data = $this->where('pid', $id)->find();
        return $data;
    }

    /**
     * 查询一条图册下的所有的图片信息
     */
    public function getAllpicture($id)
    {
        //定义一个空的数组
        $children = array();

        $data = $this
            ->field('picture_number,picture_name,picture_papaer_num,date,paper_category,owner,completion_date,id,pid')
            ->where('pid', $id)
            ->select();
        if(!empty($data))
        {
            foreach ($data as $k=>$v)
            {
                $children[$k][] = '';
                $children[$k][] = $v['picture_number'];
                $children[$k][] = $v['picture_name'];
                $children[$k][] = $v['picture_papaer_num'];
                $children[$k][] = '';
                $children[$k][] = '';
                $children[$k][] = '';
                $children[$k][] = '';
                $children[$k][] = $v['completion_date'];
                $children[$k][] = '';
                $children[$k][] = $v['paper_category'];
                $children[$k][] = $v['owner'];
                $children[$k][] = $v['date'];
                $children[$k][] = $v['id'];
                $children[$k][] = $v['pid'];
            }
        }


        return $children;
    }

    /**
     * 根据节点id查询图册表的信息
     *
     */
    public function getpicinfo($id)
    {
        $data = $this->field("attachmentId")->where("selfid",$id)->select();
        return $data;
    }

    /*
     * 根据图册id删除一条图册记录
     */
    public function delselfidCate($id)
    {
        try{
            $this->where("selfid",$id)->delete();
            return ['code' => 1, 'msg' => '删除成功'];
        }catch(PDOException $e){
            return ['code' => -1,'msg' => $e->getMessage()];
        }
    }

    /*
     * 查询该图册下的所有的文件路径
     */
    public function  getallattachmentId($id)
    {
        $data = $this->field("attachmentId")->where("pid",$id)->select();

        return $data;
    }

    /**
     * 查询当前图册下的被禁用的黑名单
     */

    public function getbalcklist($id)
    {
        $data = $this->field("blacklist")->where("id",$id)->find();
        return $data;
    }

    /**
     * 查询当前图册下是否有图纸文件
     */
    public function getpic($id)
    {
        $data = $this->where("pid",$id)->find();
        return $data;
    }

    /**
     * 根据传过来的fengning_atlas_cate图册表中的id,admin表中的id,
     */
    public function delblacklist($param)
    {

        //查询白名单中的用户id
        $blacklist = $this->field("blacklist")->where("id",$param['id'])->find();

        if($blacklist["blacklist"])
        {
            $list = explode(",",$blacklist["blacklist"]);


            foreach($list as $k=>$v)
            {
                if($v == $param['admin_id'])
                {
                    unset($list[$k]);
                }
            }

        }

        if($list)
        {
            $str = implode(",",$list);

        }else
        {
            $str = "";
        }


        //把处理过得数据重新插入数组中
        $result = $this->allowField(true)->save(['blacklist'=>$str],['id' => $param['id']]);

        if($result)
        {
            return ['code' => 1,'msg' => "删除成功"];
        }else{
            return ['code' => -1,'msg' => "删除失败"];
        }

    }

    /**
     * 添加用户到白名单
     */
    public function insertAdminid($param)
    {
        try {
            if ($param["admin_id"]) {

                    $str = implode(",", $param["admin_id"]);

                    //把重新修改的blacklist重新插入数据库

                    $this->allowField(true)->save(['blacklist' => $str], ['id' => $param['id']]);

            }
            return ['code' => 1, 'msg' => '添加成功'];
        }catch(PDOException $e){
            return ['code' => -1,'msg' => $e->getMessage()];
        }
    }
}