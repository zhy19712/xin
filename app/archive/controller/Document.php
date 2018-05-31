<?php
/**
 * Created by PhpStorm.
 * User: zhifang
 * Date: 2018/3/28
 * Time: 16:02
 */

namespace app\archive\controller;

use app\admin\controller\Permissions;
use app\admin\model\Admin;
use app\archive\model\DocumentAttachment;
use app\archive\model\DocumentDownRecord;
use app\archive\model\DocumentModel;
use app\archive\model\DocumentTypeModel;
use think\Db;
use think\Request;
use think\Session;

/**
 * 文档管理
 * Class Document
 * @package app\archive\controller
 */
class Document extends Permissions
{
    public function __construct(Request $request = null)
    {
        parent::__construct($request);
        $this->documentService = new DocumentModel();
        $this->documentTypeService = new DocumentTypeModel();
        $this->documentDownRecord = new DocumentDownRecord();
    }

    protected $documentService;
    protected $documentTypeService;
    protected $documentDownRecord;

    /**
     * 首页
     * @return mixed
     */
    public function index()
    {
        return $this->fetch();
    }
    public function association()
    {
        return $this->fetch();
    }

    /**
     * 属性
     * @return \think\response\Json
     * @throws \think\exception\DbException
     */
    public function getOne()
    {
        return json(
            DocumentModel::get(input('id'), ['documentType', 'attachmentInfo'])
        );
    }

    /**
     * 删除文档
     * @return \think\response\Json
     * @throws \think\exception\DbException
     */
    public function del()
    {
        if(request()->isAjax()){
            //实例化model类型
            $model = new DocumentModel();

            $param = input('post.');

                $data = $model->getOne($param['id']);
                if($data["attachmentId"])
                {
                    //先删除图片
                    //查询attachment表中的文件上传路径
                    $attachment = Db::name("attachment")->where("id",$data["attachmentId"])->find();
                    $path = "." .$attachment['filepath'];
                    $pdf_path = './uploads/temp/' . basename($path) . '.pdf';

                    if(file_exists($path)){
                        unlink($path); //删除文件图片
                    }

                    if(file_exists($pdf_path)){
                        unlink($pdf_path); //删除生成的预览pdf
                    }

                    //删除attachment表中对应的记录
                    Db::name('attachment')->where("id",$data["attachmentId"])->delete();
                }

                $flag = $model->delCate($param['id']);

                return $flag;
        }
    }

    /**
     * 下载——权限验证
     * 部门下载部门内，若文件有权限设置则按规则
     */
    public function download()
    {
        if (request()->isAjax()) {
            $id = input('param.id');//id
            //实例化model类
            $model = new DocumentModel();
            $param = $model->getOne($id);
            //查询attachment文件上传表中的文件上传路径
            $attachment = Db::name("attachment")->where("id", $param["attachmentId"])->find();
            //上传文件路径
            $path = $attachment["filepath"];
            if (!$path || !file_exists("." . $path)) {
                return json(['code' => '-1', 'msg' => '文件不存在']);
            }
            return json(['code' => 1]);
        }
        $id = input('param.id');

        //实例化model类
        $model = new DocumentModel();
        $param = $model->getOne($id);
        //查询attachment文件上传表中的文件上传路径
        $attachment = Db::name("attachment")->where("id", $param["attachmentId"])->find();
        //上传文件路径
        $path = $attachment["filepath"];

        $filePath = '.' . $path;
        $fileName = $attachment['name'];
        $file = fopen($filePath, "r"); //   打开文件
        //输入文件标签
        $fileName = iconv("utf-8", "gb2312", $fileName);
        Header("Content-type:application/octet-stream ");
        Header("Accept-Ranges:bytes ");
        Header("Accept-Length:   " . filesize($filePath));
        Header("Content-Disposition:   attachment;   filename= " . $fileName);
        //   输出文件内容
        echo fread($file, filesize($filePath));
        fclose($file);
        exit;

    }

    /**
     * 预览一条文档信息
     * @return \think\response\Json
     */
    public function preview()
    {
        if(request()->isAjax()){
            //实例化模型类
            $model = new DocumentModel();
            $param = input('post.');
            $code = 1;
            $msg = '预览成功';
            $data = $model->getOne($param['id']);

            //查询attachment表中的文件上传路径
            $attachment = Db::name("attachment")->where("id",$data["attachmentId"])->find();
            $path = "." .$attachment['filepath'];

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
     * 文档文件上传
     * @param string $module
     * @param string $use
     * @return \think\response\Json|void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function upload($module = 'atlas', $use = 'atlas_thumb')
    {
        if ($this->request->file('file')) {
            $file = $this->request->file('file');
        } else {
            $res['code'] = 1;
            $res['msg'] = '没有上传文件';
            return json($res);
        }
        //接收前台传过来的文件
        $accept_file = $_FILES["file"];

        $module = $this->request->has('module') ? $this->request->param('module') : $module;//模块
        $web_config = Db::name('admin_webconfig')->where('web', 'web')->find();
        $info = $file->validate(['size' => $web_config['file_size'] * 1024, 'ext' => $web_config['file_type']])->rule('date')->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . $module . DS . $use);
        if ($info) {
            //写入到附件表
            $data = [];
            $data['module'] = $module;
            $data['name'] = $accept_file["name"];//上传原文件名
            $data['filename'] = $info->getFilename();//文件名
            $data['filepath'] = DS . 'uploads' . DS . $module . DS . $use . DS . $info->getSaveName();//文件路径
            $data['fileext'] = $info->getExtension();//文件后缀
            $data['filesize'] = $info->getSize();//文件大小
            $data['create_time'] = time();//时间
            $data['uploadip'] = $this->request->ip();//IP
            $data['user_id'] = Session::has('admin') ? Session::get('admin') : 0;
            if ($data['module'] == 'admin') {
                //通过后台上传的文件直接审核通过
                $data['status'] = 1;
                $data['admin_id'] = $data['user_id'];
                $data['audit_time'] = time();
            }
            $data['use'] = $this->request->has('use') ? $this->request->param('use') : $use;//用处

            //返回上传的文件的id,attachment表中的主键id
            $attachmentId = Db::name('attachment')->insertGetId($data);

            //把上传的文件信息再插入到xin_archive_document文档表
            $type = input("post.selfid");
            $doc = [
                "attachmentId"=>$attachmentId,
                "type"=>$type
            ];
            //实例化模型类
            $model = new DocumentModel();

            $flag = $model->add($doc);

            return json($flag);

        } else {
            // 上传失败获取错误信息
            return $this->error('上传失败：' . $file->getError());
        }
    }

    /**
     * 编辑上传的文档备注
     */
    public function editDocmentRemark()
    {
        if(request()->isAjax()){
            //实例化模型类
            $model = new DocumentModel();

            $param = input("post.remarkArr/a");

            $flag = $model->editCate($param);

            return json($flag);
        }
    }
}