<?php
/**
 * Created by PhpStorm.
 * User: zhifang
 * Date: 2018/4/4
 * Time: 15:45
 */
/**
 * 表单模板管理
 * Class Templatemanage
 * @package app\standard\controller
 */
namespace app\standard\controller;

use app\admin\controller\Permissions;
use app\standard\model\TemplateModel;
use think\Request;
use think\Db;

class Templatemanage extends Permissions
{
    protected $templateService;

    public function __construct(Request $request = null)
    {
        parent::__construct($request);
        $this->templateService = new TemplateModel();
    }

    public function index()
    {
        return $this->fetch();
    }

    /**
     * 添加编辑模板
     * @param null $id
     * @return mixed|\think\response\Json
     */
    public function add($id = null)
    {
        if ($this->request->isAjax()) {
            $mod = input('post.');
            if (empty($mod['id'])) {
                $res = $this->templateService->allowField(true)->save($mod);
            } else {
                $res = $this->templateService->allowField(true)->save($mod, ['id' => $mod['id']]);
            }
            if(false === $res){
                return ['code' => -1];
            }else{
                return ['code' => 1];
            }
        }
        $this->assign('id',$id);
        return $this->fetch();
    }

    /**
     * 获取一条记录
     * @param $id
     * @return \think\response\Json
     * @throws \think\exception\DbException
     */
    public function getone($id)
    {
        return json(TemplateModel::get($id));
    }

    /**
     * 删除模板
     * @param $id
     * @return \think\response\Json
     * @throws \think\exception\DbException
     */
    public function del($id)
    {
        $mod = TemplateModel::get($id);
        if ($mod->delete()) {
            return json(['code' => 1]);
        } else {
            return json(['code' => -1]);
        }
    }

    /**
     * 模板下载，暂时功能，用完废弃
     * @return \think\response\Json
     */
    public function download()
    {
//        if(request()->isAjax()){
//            // 前台需要 传递 文件编号 id
//            $param = input('param.');
//            $file_id = isset($param['id']) ? $param['id'] : 0;
//            if ($file_id == 0) {
//                return json(['code' => '-1', 'msg' => '编号有误']);
//            }
//            $file_obj = Db::name('norm_template')->where('id', $file_id)->field('code,name')->find();
//            if (empty($file_obj)) {
//                return json(['code' => '-1', 'msg' => '编号无效']);
//            }
//            $formPath = ROOT_PATH . 'public' . DS . "Data\\form\\qualityNew\\" . $file_obj['code'] . $file_obj['name'] . "下载.html";
//            $formPath = iconv('UTF-8', 'GB2312', $formPath);
//            if (!file_exists($formPath)) {
//                return json(['code' => '-1', 'msg' => '文件不存在']);
//            } else {
//                return json(['code' => 1]); // 文件存在，告诉前台可以执行下载
//            }
//        }

        $param = input('param.');
        $file_id = isset($param['id']) ? $param['id'] : 0;

        if ($file_id == 0) {
            return json(['code' => '-1', 'msg' => '编号有误']);
        }
        $file_obj = Db::name('norm_template')->where('id', $file_id)->field('code,name')->find();

            $formPath = ROOT_PATH . 'public' . DS . "Data\\form\\qualityNew\\" . $file_obj['code'] . $file_obj['name'] . "下载.html";

            $formPath = iconv('UTF-8', 'GB2312', $formPath);

            $tempPath = ROOT_PATH . 'public' . DS . "Data\\form\\temp\\";
            if (!file_exists($tempPath)){
                mkdir ($tempPath,0777,true);
            }

            $tempPdf = $tempPath.time().".pdf";
            //清空缓冲区
            ob_end_clean();
            //调用wkhtml工具将html文件生成pdf文件

            shell_exec("wkhtmltopdf ".$formPath." ".$tempPdf);

            $filePath = iconv("utf-8", "gb2312", $tempPdf);
            $fileName = $file_obj['code'] . $file_obj['name'].".pdf";
            $fileName =iconv("utf-8", "gb2312", $fileName);

            if(file_exists($filePath)){
                header("Content-type:application/pdf");
                header("Content-Disposition:attachment;filename=".$fileName);
                $file = fopen($filePath, 'r');
                echo fread($file, filesize($filePath));
                fclose($file);
                //删除临时文件
                unlink($tempPdf);
            }
            else
            {
                return json(['msg'=>'未找到下载文件']);
                exit;
            }
    }

    /**
     * 模板查看，暂时功能，用完废弃
     * @return \think\response\Json
     */
    public function preview()
    {
        $param = input('param.');

        $file_id = isset($param['id']) ? $param['id'] : 0;

        if ($file_id == 0) {
            return json(['code' => '-1', 'msg' => '编号有误']);
        }
        $file_obj = Db::name('norm_template')->where('id', $file_id)->field('code,name')->find();

        $formPath = ROOT_PATH . 'public' . DS . "Data\\form\\qualityNew\\" . $file_obj['code'] . $file_obj['name'] . "下载.html";

        return json(["code"=>1,"url"=>$formPath]);
    }
}