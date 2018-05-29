<?php
/**
 * Created by PhpStorm.
 * User: zhifang
 * Date: 2018/3/29
 * Time: 13:51
 */

/**
 * 文档类型树管理
 */
namespace app\archive\controller;

use app\admin\controller\Permissions;
use app\archive\model\DocumentTypeModel;
use app\archive\model\DocumentModel;
use think\Request;
use \think\Db;
use think\Session;

class Documenttype extends Permissions
{
    /**
     * 获取树
     * @return \think\response\Json
     * @throws \think\exception\DbException
     */
    public function getAll()
    {
        //根据当前登录的人所在的组织机构查询相应的文档类型树
        $admin_id = $admin_id= Session::has('admin') ? Session::get('admin') : 0;
        $group_info = Db::name("admin")
            ->alias("a")
            ->join('admin_group g','a.admin_group_id = g.id','left')
            ->field("g.pid")
            ->where("a.id",$admin_id)
            ->find();
        if($group_info["pid"] == 0)
        {
            return json(DocumentTypeModel::all());
        }else
        {
            $group_name = Db::name("admin_group")
                ->field("id,name")
                ->where("id",$group_info["pid"])
                ->find();
            $document_type_id = Db::name("archive_documenttype")
                ->where("name like '%".$group_name["name"]."%'")
                ->find();


            $document_type_info = Db::name("archive_documenttype")
                ->where(" id = 1 OR id = ".$document_type_id["id"]." OR pid = ".$document_type_id["id"])
                ->order("id asc")
                ->select();

            return json(["code"=>1,$document_type_info]);

        }
    }

    protected $documentTypeService;

    public function __construct(Request $request = null)
    {
        parent::__construct($request);
        $this->documentTypeService = new DocumentTypeModel();
    }

    /**
     * 添加或修改
     * @return \think\response\Json
     */
    public function addOrEdit()
    {
        $m = input('post.');
        $res=$this->documentTypeService->addOrEdit($m);
        if ($res) {
            return json(['code' => 1,'data'=>$res]);
        } else {
            return json(['code' => -1]);
        }
    }

    /**
     * 删除节点
     * @return \think\response\Json
     */
    public function del()
    {
        //判断当前节点下是否有文件
        //实例化模型类
        $model = new DocumentModel();

        $doctype = new DocumentTypeModel();

        $id = input("post.id");

        $result = $model->judge($id);
        //如果当前节点下存在文件则无法直接删除、必须先删除节点下的所有文件
        if(!empty($result))
        {
            return json(["code"=>-1,'msg'=>"当前节点下有文件无法删除！"]);
        }

        $data = $model->getpicinfo($id);
        if(!empty($data))
        {
            foreach ($data as $k=>$v)
            {
                $attachment = Db::name("attachment")->where("id",$v["attachmentId"])->find();
                $path = "." .$attachment['filepath'];
                $pdf_path = './uploads/temp/' . basename($path) . '.pdf';
                if($attachment['filepath'])
                {
                    if(file_exists($path)){
                        unlink($path); //删除上传的图片或文件
                    }
                    if(file_exists($pdf_path)){
                        unlink($pdf_path); //删除生成的预览pdf
                    }
                }
                //删除attachment表中对应的记录
                Db::name('attachment')->where("id",$v["attachmentId"])->delete();
            }
        }

        $model->delselfidCate($id);

//        return $this->documentTypeService->del(input('id'));
        $flag = $doctype->del($id);

        return json($flag);

    }
}