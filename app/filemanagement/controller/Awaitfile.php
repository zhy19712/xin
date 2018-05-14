<?php
/**
 * Created by PhpStorm.
 * User: zhifang
 * Date: 2018/4/28
 * Time: 10:48
 */
/**
 * 档案管理-待整理文件
 * Class Awaitfile
 * @package app\filemanagement\controller
 */
namespace app\filemanagement\controller;
use app\admin\controller\Permissions;
use app\filemanagement\model\ProjectmanagementModel;//档案管理-工程项目管理
use app\filemanagement\model\FilebranchtypeModel;//档案管理-分支目录管理-项目分类-树节点
use app\filemanagement\model\FilebranchModel;//档案管理-分支目录管理-项目分类
use app\filemanagement\model\FileelectronicModel;//档案管理-档案管理-待整理文件-电子文件挂接
use app\archive\model\DocumentTypeModel;//文档管理类型
use app\archive\model\DocumentModel;//文档管理
use app\quality\model\DivisionModel;//工程划分，单元工程
use app\quality\model\DivisionUnitModel;//单元工程
use app\standard\model\MaterialTrackingDivision;//工序表
use think\exception\PDOException;
use think\Loader;
use think\Db;
use think\Request;
use think\Session;

class Awaitfile extends Permissions
{
    /**
     * 待整理文件模板首页
     */
    public function index()
    {
        return $this->fetch();
    }

    /**********************************左侧的目录树*********************/
    /**
     * 获取所有的工程项目管理中的项目名称
     * @return \think\response\Json
     */
    public function getAllCategory()
    {
        //实例化模型类
        $model = new ProjectmanagementModel();
        $category = $model->getAllCategory();
        //定义一个空数组
        if(!empty($category))
        {
            $data = $category;
        }else
        {
            $data = [];
        }
        return json(["code"=>1,"data"=>$data]);
    }

    /**
     * 获取所选的项目名称下的树
     * @return \think\response\Json
     */
    public function getCategoryTree()
    {
        if(request()->isAjax()){
            //实例化模型类
            $model = new ProjectmanagementModel();
            $branch_model = new FilebranchModel();
            $id = input("post.id");
            $id = 1;
            //查询当前的所选的项目名称下的所有目录
            $branch_info = $model->getOne($id);
            $branch_id_list = $branch_info["branch_id"];
            if(!empty($branch_id_list))
            {
                $branch_id_array = explode(",",$branch_id_list);

            }else
            {
                $branch_id_array = [];
            }

            //定义一个空的数组
            $branch_array = array();
            //遍历配置的项目名称数组id,查询分支目录管理-项目分类
            foreach($branch_id_array as $key=>$val)
            {
                $branch_data_info = $branch_model->getOne($val);
                $branch_array[$key]["id"] = $branch_data_info["id"];
                $branch_array[$key]["classifyid"] = $branch_data_info["classifyid"];
                $branch_array[$key]["pid"] = $branch_data_info["pid"];
                $branch_array[$key]["class_name"] = $branch_data_info["class_name"];
            }
            if(!empty($branch_array))
            {
                $result = tree($branch_array);
                foreach ((array)$result as $k => $v) {
                    $v['id'] = strval($v['id']);
                }
            }else
            {
                $result = [];
            }
            return json(["code"=>1,"data"=>$result]);
        }
    }

    /**********************************电子文件挂接选择文件目录树*********************/
    /**
     * 文档管理树
     * @return \think\response\Json
     */
    public function getDocumenttypeTree()
    {
        if(request()->isAjax()) {
            //实例化模型类
            $model = new DocumentTypeModel();
            $data = $model->getall();
            $res = tree($data);
            foreach ((array)$res as $k => $v) {
                $v['id'] = strval($v['id']);
            }
            return json($res);
        }
    }

    /**
     * 工程划分，单元工程管理树
     * @return \think\response\Json
     */
    public function getDivisionTree()
    {
        if(request()->isAjax()){
            $node = new DivisionModel();
            $nodeStr = $node->getNodeInfo();
            return json($nodeStr);
        }
    }

    /**
     * 获取检验批列表
     * @param $id
     * @return \think\response\Json
     * @throws \think\exception\DbException
     */
    public function getDivisionUnitTree()
    {
        if(request()->isAjax()) {
            $id = input("post.id");
//            $id = 4;
            return json(DivisionUnitModel::all(['division_id' => $id]));
        }
    }

    /**
     * 获取工序列表
     * @param $id
     * @return \think\response\Json
     * @throws \think\exception\DbException
     */
    public function getProcedures()
    {
//        if(request()->isAjax()) {
            $id = input("post.en_type");//工程类型
        $id = 4;
            $data = MaterialTrackingDivision::all(['pid' => $id, 'type' => 3]);
            return json(["code"=>1,"data"=>$data]);
//        }
    }

    /**
     * 添加电子文件挂接
     */
    public function electronicFileHang()
    {
        try{
            if(request()->isAjax()){
                //实例化模型类
                $Fileelectronic = new FileelectronicModel();
                $Document = new DocumentModel();

                $param = input('post.');

                //需要的是一个id数组
                //用type区分图纸管理、文档管理、质量管理
                //type=paper，type=doc,type=quality
                $file_id_data = $param["id"];//前台传过来的id数组
                $type = $param["type"];//类型
                $fpd_id = $param["fpd_id"];//档案管理-待整理文件表的自增id

                switch ($type)
                {
                    case "paper":
                        break;
                    case "doc":
                        if(!empty($file_id_data))
                        {
                            foreach ($file_id_data as $key=>$val)
                            {
                                //判断当前控制点是否存在数据库中
                                $result = $Fileelectronic->getid($type,$fpd_id,$val);
                                if($result["id"])
                                {
                                    unset($file_id_data[$key]);
                                }
                            }

                            if(!empty($file_id_data))
                            {
                                foreach ($file_id_data as $k=>$v)
                                {
                                    //根据图纸管理、文档管理、质量管理中的文件id
                                    $document_info = $Document->getOne($v);
                                    if(!empty($document_info))
                                    {
                                        $data = [
                                            "fpd_id" => $fpd_id,//返回的fengning_file_pending_documents表的id
                                            "type" => $type,//paper图纸管理，doc文档管理，quality质量管理
                                            "file_id" => $v,//对应的是图纸管理，文档管理，质量管理表的文件id
                                            "electronic_file_name" => $document_info["docname"],//文件名称
                                            "type_code" => "文档",//类型代码
                                            "date" => date("Y-m-d",time())
                                        ];
                                        $Fileelectronic->insertFe($data);
                                    }
                                }
                            }

                            return ['code' => 1,'msg' => '添加成功'];
                        }
                        else
                        {
                            return ['code' => -1,'msg' => ''];
                        }
                }

            }
        }catch (PDOException $e){
            return ['code' => -1,'msg' => $e->getMessage()];
        }
    }
    /**
     * 新增或编辑待整理文件
     */
    public function addoredit()
    {
        return $this->fetch();
    }
}