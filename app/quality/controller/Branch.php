<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/4/13
 * Time: 11:26
 */
/**
 * 质量管理-分部质量管理
 * Class Branch
 * @package app\quality\controller
 */
namespace app\quality\controller;
use app\admin\controller\Permissions;
use app\admin\model\Admin;//用户表
use app\admin\model\AdminCate;//角色分类表
use app\quality\model\DivisionModel;//工程划分
use app\quality\model\DivisionControlPointModel;//工程划分、工序、控制点关系表
use app\quality\model\UploadModel;//分部管控、单位管控中的控制点文件上传
use app\quality\model\SendModel;//收发文
use think\exception\PDOException;
use think\Loader;
use think\Db;
use think\Request;
use think\Session;

class Branch extends Permissions
{
    /****************************分部策划************************/
    /**
     * 分部策划模板首页
     * @return mixed
     */
    public function plan()
    {
        return $this->fetch();
    }

    /****************************分部管控************************/
    /**
     * 分部管控模板首页
     * @return mixed
     */
    public function control()
    {
        return $this->fetch();
    }

    /**
     * 分部策划 或者 分部管控 初始化左侧树节点
     * @param int $type
     * @return mixed|\think\response\Json
     */
    public function index($type = 1)
    {
        if($this->request->isAjax()){
            //实例化模型类
            $node = new DivisionModel();
            $nodeStr = $node->getNodeInfo(4);
            return json($nodeStr);
        }
        if($type==1){
            return $this->fetch();
        }
        return $this->fetch('control');
    }

    /**
     * 获取分部质量管理-工序
     * @return \think\response\Json
     */
    public function getControlPoint()
    {
        $data = Db::name('norm_materialtrackingdivision')->group("id,name")->order("sort_id asc")->field("id,name")->where(['type'=>2,'cat'=>3])->select();
        if(!empty($data))
        {
            return json(['code'=>1,'data'=>$data]);
        }else
        {
            return json(['code'=>-1,'data'=>""]);
        }
    }

    /**
     * 点击取消勾选后管控处不显示该控制点
     * @return \think\response\Json
     */
    public function checkBox()
    {
        if(request()->isAjax()) {
            //实例化模型类
            $model = new DivisionControlPointModel();
            $param = input('post.');

            //全选
            if($param["checked"] == "All")
            {
                if($param["ma_division_id"] != 0)
                {
                    $search = [
                        "division_id"=>$param["division_id"],
                        "type"=>0,
                        "ma_division_id"=>$param["ma_division_id"]
                    ];
                    $data = [
                        "checked"=>0
                    ];

                }else
                {
                    $search = [
                        "division_id"=>$param["division_id"],
                        "type"=>0,
                        "ma_division_id"=>0

                    ];
                    $data = [
                        "checked"=>0
                    ];
                }

                $flag = $model->editAll($search,$data);
                return json($flag);
            }else if($param["checked"] == "noAll")
            {
                if($param["ma_division_id"] != 0)
                {
                    $search = [
                        "division_id"=>$param["division_id"],
                        "type"=>0,
                        "ma_division_id"=>$param["ma_division_id"]

                    ];
                    $data = [
                        "checked"=>1
                    ];
                }else
                {
                    $search = [
                        "division_id"=>$param["division_id"],
                        "type"=>0,
                        "ma_division_id"=>0
                    ];
                    $data = [
                        "checked"=>1
                    ];
                }
                $flag = $model->editNoAll($search,$data);
                return json($flag);
            }else
            {
                $flag = $model->editRelation($param);
                return json($flag);
            }
        }
    }

    /**
     * 控制点执行情况文件
     * @return \think\response\Json
     */
    public function addFile()
    {
        if(request()->isAjax()){
            //实例化模型类
            $model = new UploadModel();
            $Division = new DivisionControlPointModel();
            $param = input('post.');

            $data = [
                "contr_relation_id" => $param["list_id"],//分部策划列表id
                "attachment_id" => $param["attachment_id"],//对应的是attachment文件上传表中的id
                "type" => 3//1，扫描件，2单位上传附件，3分部上传附件
            ];
            $flag = $model->insertTb($data);

            //文件上传完毕后修改控制点的状态，只有上传控制点执行情况文件时才修改状态

                $info = $Division->getOne($param["list_id"]);

                if($info["status"] == 0)//0表示未执行
                {
                    $change = [
                        "id" => $param["list_id"],
                        "status" => "1"
                    ];
                    $Division->editRelation($change);
                }
            return json($flag);
        }
    }

    /**
     * 删除一条控制点执行情况或者是图像上传信息
     * @return \think\response\Json
     * @throws PDOException
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function delete()
    {
        if(request()->isAjax()){
            //实例化model类型
            $model = new UploadModel();
            $Division = new DivisionControlPointModel();
            $param = input('post.');
                $data = $model->getOne($param['id']);
                if($data["attachment_id"])
                {
                    //先删除图片
                    //查询attachment表中的文件上传路径
                    $attachment = Db::name("attachment")->where("id",$data["attachment_id"])->find();
                    if($attachment["filepath"])
                    {
                        $path = "." .$attachment['filepath'];
                        $pdf_path = './uploads/temp/' . basename($path) . '.pdf';

                        if(file_exists($path)){
                            unlink($path); //删除文件图片
                        }

                        if(file_exists($pdf_path)){
                            unlink($pdf_path); //删除生成的预览pdf
                        }
                    }

                    //删除attachment表中对应的记录
                    Db::name('attachment')->where("id",$data["attachment_id"])->delete();
                }
                $flag = $model->delTb($param['id']);

                //只有执行点执行情况文件删除时进行以下的操作
                //如果控制点执行情况的文件全部删除，修改分部策划表中的状态到未执行，也就是0
                //首先查询控制点文件、图像上传表中是否还存在当前的分部策划表的上传文件记录
                    $result = $model->judge($param["list_id"]);
                    if(empty($result))//为空为真表示已经没有文件,修改status的值
                    {
                        $info = $Division->getOne($param["list_id"]);
                        if($info["status"] == 1)//0表示已执行
                        {
                            $change = [
                                "id" => $param["list_id"],
                                "status" => "0"
                            ];
                            $Division->editRelation($change);
                        }
                    }
                return json($flag);
        }
    }

    /**
     * 关联收发文
     * @return mixed
     */
    public function relationadd()
    {
        return $this->fetch();
    }

    /**
     * 添加关联收发文附件到分部管控、单位管控中的控制点文件上传文件表中
     * @return \think\response\Json
     */
    public function addRelationFile()
    {
        if(request()->isAjax()){
            //实例化模型类
            $model = new UploadModel();
            $send = new SendModel();
            $Division = new DivisionControlPointModel();
            $param = input('post.');
            $send_info = $send->getOne($param["id"],1);

            //遍历数组循环插入分部管控、单位管控中的控制点文件上传文件表中
            //如果当前的数组不为空
            //定义一个空的数组
            $data = array();
            if(!empty($send_info["file_ids"]))
            {
                $file_ids_array = explode(",",$send_info["file_ids"]);

                foreach($file_ids_array as $key=>$val)
                {
                    $data[$key]["contr_relation_id"] = $param["list_id"];
                    $data[$key]["attachment_id"] = $val;
                    $data[$key]["type"] = 3;

                }
                $flag = $model->insertTbAll($data);

                //文件上传完毕后修改控制点的状态，只有上传控制点执行情况文件时才修改状态

                $info = $Division->getOne($param["list_id"]);

                if($info["status"] == 0)//0表示未执行
                {
                    $change = [
                        "id" => $param["list_id"],
                        "status" => "1"
                    ];
                    $Division->editRelation($change);
                }
                return json($flag);
            }else
            {
                return json(['code' => -1,'msg' => '添加失败！']);
            }
        }
    }

    /**
     * 分部管控中的验评
     * @return \think\response\Json
     */
    public function evaluation()
    {
        if(request()->isAjax()){
            //实例化模型类
            $admin = new Admin();
            $admincate = new AdminCate();

            $division_id = input("post.division_id");
            //首先判断当前的登录人是否有验评权限，管理员和监理可以编辑
            $admin_id= Session::has('admin') ? Session::get('admin') : 0;

            $admin_info = $admin->getOne($admin_id);

            $admin_cate_id = $admin_info["admin_cate_id"];

            if(!empty($admin_cate_id))
            {
                $admin_cate_id_array = explode(",",$admin_cate_id);
                //查询角色角色分类表中超级管理员和监理单位中是否有当前登录的用户
                $data = $admincate->getAlladminSupervisor();
                //$flag = 1表示有权限
                $flag = 1;
                foreach ($admin_cate_id_array as $va) {
                    if (in_array($va, $data)) {
                        continue;
                    }else {
                        $flag = 0;
                        break;
                    }
                }

                //查询当前的工程划分的节点的验评状态
                $Division = new DivisionModel();

                $division_info = $Division->getOne($division_id);

                $evaluation_results = $division_info["evaluation_results"];//验评

                $evaluation_time = $division_info["evaluation_time"]?$division_info["evaluation_time"]:"";//验评日期

                if($evaluation_time)
                {
                    $evaluation_time = date("Y-m-d",$evaluation_time);
                }

                return json(["flag"=>$flag,"evaluation_results"=>$evaluation_results,"evaluation_time"=>$evaluation_time]);
            }
        }
    }

    /**
     * 分部管控中的验评
     * @return \think\response\Json
     */
    public function editEvaluation()
    {
        if(request()->isAjax()){
            //实例化模型类
            $Division = new DivisionModel();
            $division_id = input("post.division_id");//工程策划id
            $evaluation_results = input("post.evaluation_results");//验评结果
            $evaluation_time = input("post.evaluation_time");//验评时间

            $data = [
                "id"=>$division_id,
                "evaluation_results"=>$evaluation_results,
                "evaluation_time"=>strtotime($evaluation_time)
            ];
            $flag = $Division->editTb($data);

            return json($flag);
        }
    }

    /**
     *
     * 功能暂时废弃
     * 控制点里的模板文件下载
     * @return \think\response\Json
     */
    public function fileDownload()
    {

        $formPath = ROOT_PATH . 'public' . DS . "data\\form\\quality\\" ."01.02.01岩石地基开挖单元工程质量等级评定表.html";
        $formPath = iconv('UTF-8', 'GB2312', $formPath);
        $filePath = $formPath;
        $fileName = "01.02.01岩石地基开挖单元工程质量等级评定表.html";
        $fileName = iconv("utf-8", "gb2312", $fileName);
        Header("Content-Disposition:   attachment;   filename= " . $fileName);
        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        header('Content-Transfer-Encoding: binary');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Expires: 0');
        $file = fopen($filePath, 'r');
        echo fread($file, filesize($filePath));
        fclose($file);
    }
}