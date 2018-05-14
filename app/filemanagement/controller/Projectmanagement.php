<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/5/2
 * Time: 15:06
 */
/**
 * 档案管理-工程项目管理
 * Class Projectmanagement
 * @package app\filemanagement\controller
 */
namespace app\filemanagement\controller;
use app\admin\controller\Permissions;
use app\filemanagement\model\ProjectmanagementModel;//档案管理-工程项目管理
use app\filemanagement\model\FilebranchtypeModel;//档案管理-分支目录管理-项目分类-树节点
use app\filemanagement\model\FilebranchModel;//档案管理-分支目录管理-项目分类
use think\exception\PDOException;
use think\Loader;
use think\Db;
use think\Request;
use think\Session;

class Projectmanagement extends Permissions
{
    /**
     * 模板首页
     * @return mixed
     */
    public function index()
    {
        return $this->fetch();
    }

    /**
     *  获取一条信息
     * @return \think\response\Json
     */
    public function getindex()
    {
        if(request()->isAjax()){
            //实例化模型类
            $model = new ProjectmanagementModel();
            $id = input('post.id');
            $data = $model->getOne($id);
            if(!empty($data["branch_id"]))
            {
                $data["branch_id"] = explode(",",$data["branch_id"]);
            }else
            {
                $data["branch_id"] =[];
            }
            return json(['code'=> 1, 'data' => $data]);
        }
    }

    /**
     * 新增/编辑
     * @return mixed|\think\response\Json
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function editCate()
    {
        if(request()->isAjax()){
            //实例化模型类
            $model = new ProjectmanagementModel();
            $param = input('post.');

            //前台传过来的id
            if(empty($param['id']))//id为空时表示新增
            {
                $flag = $model->insertPro($param);
                return json($flag);
            }else{
                $flag = $model->editPro($param);
                return json($flag);
            }
        }
    }

    /**
     * 删除
     * @return array
     */
    public function delCate()
    {
        if(request()->isAjax()){
            //实例化model类型
            $model = new ProjectmanagementModel();
            $param = input('post.');
            $flag = $model->delPro($param['id']);
            return $flag;
        }
    }

    /**
     * 获取组织机构下的机构名称
     * @return \think\response\Json
     */
    public function getGroup()
    {
        //定义一个查询的数组
        $name = array(1=>"建设单位",2=>"施工单位",3=>"监理单位",4=>"设计单位");
        //定义两个空数组
        $res = array();
        $result = array();
        foreach($name as $k=>$v)
        {
            $group_data = Db::name('admin_group')->alias('g')
                ->join('admin_group_type gt', 'g.type = gt.id', 'left')
                ->where("gt.name",$v)
                ->field("g.name")
                ->order("g.id asc")
                ->select();

            $result[$v] = $group_data;
        }
            foreach ((array)$result as $key=>$val)
            {
               foreach ($val as $a=>$b)
               {
                   $res[$key][$a] = $b["name"];
               }
            }
        return (json(["code"=>1,"data"=>$res]));
    }

    /**
     * 获取所有的项目类别
     * @return \think\response\Json
     */
    public function getBranchType()
    {
        //实例化模型类
        $model = new FilebranchtypeModel();
        $data = $model->getall();
        //定义一个空数组用来存放数据
        $result = array();
        foreach ($data as $key => $val)
        {
            if($val["id"] == 1)
            {
                unset($data[$key]);//去除最顶级的目录名
            }else
            {
                $result[] = $val["name"];
            }

        }
        return json(["data"=>$result]);
    }

    /**
     * 配置目录树
     * @return \think\response\Json
     */
    public function getBranchTree()
    {
        if(request()->isAjax()){
            //实例化模型类
            $model = new FilebranchModel();
            //当前一条数据的id
            $id = input('post.id');
            //获取项目类别,获取项目分类树节点id
            $classifyid = Db::name('file_project_management')->alias('p')
                ->join('file_branch_directory_type t', 't.name = p.project_category', 'left')
                ->join('file_branch_directory b', 'b.classifyid = t.id', 'left')
                ->where("p.id",$id)
                ->field("b.classifyid")
                ->find();
            //获取项目分类表中的所有的数据
            $data = $model->getDateAll($classifyid["classifyid"]);
            if (!empty($data)) {
                //调取tree函数处理数据
                $res = tree($data);
                foreach ((array)$res as $k => $v) {
                    $v['id'] = strval($v['id']);
                }
            } else {
                $res = [];
            }
            return json($res);
        }
    }

    /**
     * 工程项目管理添加配置项
     * @return \think\response\Json
     */
    public function addConfig()
    {
        if(request()->isAjax()){
            //实例化模型类
            $model = new ProjectmanagementModel();
            $id = input("post.id");//id
            $idArr = input("post.idArr/a");//项目类别的数组
            if(!empty($idArr))
            {
                $idarr = implode(",",$idArr);

            }else
            {
                $idarr = "";
            }
            //要更新的数组
            $data =["id"=>$id,"branch_id"=>$idarr];

            $flag = $model->editPro($data);

            return json($flag);
        }
    }
}