<?php
/**
 * Created by PhpStorm.
 * User: sir
 * Date: 2018/4/11
 * Time: 14:17
 */
/**
 * 质量管理-单位质量管理
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
use app\quality\model\UnitqualitymanageModel;
use think\Db;
use app\quality\model\QualityFormInfoModel;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\PhpWord;
use think\Loader;
use think\Session;

// 单位质量管理

class Unitqualitymanage extends Permissions
{
    /**
     * 单位策划 或者 单位管控 初始化左侧树节点
     * 这里的 树节点 是从工程划分 树节点里取来的(而且只取到 子单位工程)
     *
     * 工序是从materialtrackingdivision表里取出来的 取的是 单位工程下的三级节点
     *
     * 注意:作业下的控制点 是 materialtrackingdivision 工序表 关联 controlpoint 控制点表 的全部数据
     *
     * 其他工序下的控制点 是 根据 quality_division_controlpoint_relation 对应关系表 关联 controlpoint 的数据
     * 可以新增，删除，全部删除，删除的是 对应关系表里的对应信息，不是真正的删除controlpoint里的数据
     *
     * @param int $type
     * @return mixed|\think\response\Json
     * @author hutao
     */
    public function index($type = 1)
    {
        if($this->request->isAjax()){
            $node = new DivisionModel();
            $nodeStr = $node->getNodeInfo(2); // 2 只取到子单位工程
            return json($nodeStr);
        }
        if($type==1){
            return $this->fetch();
        }
        return $this->fetch('control');
    }

    /**
     * 单位管控模板首页
     * @return mixed
     */
    public function control()
    {
        return $this->fetch();
    }

    /**
     * 获取节点工序
     * @return \think\response\Json
     * @author hutao
     */
    public function productionProcesses()
    {
        $data = Db::name('norm_materialtrackingdivision')->group("id,name")->order("sort_id asc")->field("id,name")->where(['type'=>2,'cat'=>2])->select();
        if(!empty($data))
        {
            return json(['code'=>1,'data'=>$data]);
        }else
        {
            return json(['code'=>-1,'data'=>""]);
        }
    }

    /**
     * 获取单位工程工序树
     * @return \think\response\Json
     * @author hutao
     */
    public function unitTree()
    {
        if($this->request->isAjax()){
            $node = new UnitqualitymanageModel();
            $nodeStr = $node->getNodeInfo();
            return json($nodeStr);
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
     * 控制点执行情况文件 或者 图像资料文件 预览
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author hutao
     */
   public function relationPreview()
   {
      // 前台 传递 id 编号
       $param = input('post.');
       $id = isset($param['id']) ? $param['id'] : 0;
       if($id  == 0){
          return json(['code' => 1,  'path' => '', 'msg' => '编号有误']);
       }
        if(request()->isAjax()) {
           $code = 1;
           $msg = '预览成功';
            $data = Db::name('quality_upload')->alias('q')
                ->join('attachment a','a.id=q.attachment_id','left')
                ->where('q.id',$id)->field('a.filepath')->find();
            if(!$data['filepath'] || !file_exists("." .$data['filepath'])){
               return json(['code' => '-1','msg' => '文件不存在']);
            }
            $path = $data['filepath'];
           $extension = strtolower(get_extension(substr($path,1)));
            $pdf_path = './uploads/temp/' . basename($path) . '.pdf';
            if(!file_exists($pdf_path)){
                if($extension === 'doc' || $extension === 'docx' || $extension === 'txt'){
                    doc_to_pdf($path);
                }else if($extension === 'xls' || $extension === 'xlsx'){
                   excel_to_pdf($path);
               }else if($extension === 'ppt' || $extension === 'pptx'){
                   ppt_to_pdf($path);
                }else if($extension === 'pdf'){
                   $pdf_path = $path;
                }else if($extension === "jpg" || $extension === "png" || $extension === "jpeg"){
                   $pdf_path = $path;
                }else {
                    $code = 0;
                    $msg = '不支持的文件格式';
                }
                return json(['code' => $code, 'path' => substr($pdf_path,1), 'msg' => $msg]);
            }else{
                return json(['code' => $code,  'path' => substr($pdf_path,1), 'msg' => $msg]);
            }
        }
    }

    /**
     * 控制点执行情况文件 或者 图像资料文件 下载
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author hutao
     */
    public function relationDownload()
   {
       // 前台需要 传递 id 编号
       $param = input('param.');
       $id = isset($param['id']) ? $param['id'] : 0;
       if($id == 0){
            return json(['code' => '-1','msg' => '编号有误']);
       }
        $file_obj = Db::name('quality_upload')->alias('q')
           ->join('attachment a','a.id=q.attachment_id','left')
            ->where('q.id',$id)->field('a.filename,a.filepath,q.data_name')->find();
       $filePath = '';
        if(!empty($file_obj['filepath'])){
            $filePath = '.' . $file_obj['filepath'];
        }
       if(!file_exists($filePath)){
           return json(['code' => '-1','msg' => '文件不存在']);
        }else if(request()->isAjax()){
            return json(['code' => 1]); // 文件存在，告诉前台可以执行下载
        }else{
            $fileName = $file_obj['data_name'];
            $file = fopen($filePath, "r"); //   打开文件
            //输入文件标签
            $fileName = iconv("utf-8","gb2312",$fileName);
            Header("Content-type:application/octet-stream ");
            Header("Accept-Ranges:bytes ");
            Header("Accept-Length:   " . filesize($filePath));
            Header('Content-Disposition: attachment; filename='.$fileName);
            Header('Content-Type: application/octet-stream; name='.$fileName);
            //   输出文件内容
            echo fread($file, filesize($filePath));
            fclose($file);
            exit;
       }
    }

    /**
     * 控制点执行情况文件 或者 图像资料文件 删除
     * 首先 删除 上传的文件 和 预览 的pdf 文件
     * 然后 删除 上传记录
     * 最后 删除 对应关系表记录
     * @return \think\response\Json
     * @throws \think\Exception
     * @author hutao
     */
  public function relationDel()
   {
       // 前台需要 传递 id 编号
      $param = input('param.');

       $id = isset($param['id']) ? $param['id'] : 0;
       if($id == 0){
           return json(['code' => '-1','msg' => '编号有误']);
       }
      if(request()->isAjax()) {
          //删除扫描件的时候判断是否有对应已经完成表单，如果没有则删掉验评结果和验评日期
          $qu=Db::name('quality_upload')
              ->where(['id'=>$id])
              ->find();
          if($qu['type']==1) {
              $relation = Db::name('quality_division_controlpoint_relation')
                  ->where(['id' => $qu['contr_relation_id']])
                  ->find();
              $form_info=Db::name('quality_form_info')
                  ->where(['DivisionId'=>$relation['division_id'],'ControlPointId'=>$relation['control_id'],'ApproveStatus'=>2])
                  ->count();

              if ($form_info<1)
              {
                  Db::name('quality_unit')
                      ->where(['id' => $relation['division_id']])
                      ->update(['EvaluateResult' => 0, 'EvaluateDate' => 0]);
              }
          }

          $sd = new UnitqualitymanageModel();
           $flag = $sd->deleteTb($id);
           return json($flag);
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
                "type" => 2//1，扫描件，2单位上传附件，3分部上传附件
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
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
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
                    $data[$key]["type"] = 2;

                }
                Db::name("quality_upload")->insertAll($data);

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
                return json(['code' => 1,'msg' => '添加成功！']);
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
}