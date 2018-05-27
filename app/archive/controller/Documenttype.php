<?php
/**
 * Created by PhpStorm.
 * User: zhifang
 * Date: 2018/3/29
 * Time: 13:51
 */

namespace app\archive\controller;

use app\admin\controller\Permissions;
use app\archive\model\DocumentTypeModel;
use app\archive\model\DocumentModel;
use think\Request;
use \think\Db;

class Documenttype extends Permissions
{
    /**
     * 获取树
     * @return \think\response\Json
     * @throws \think\exception\DbException
     */
    public function getAll()
    {
        return json(DocumentTypeModel::all());
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
        $result = $model->getOne($id);
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