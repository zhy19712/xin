<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/5/15
 * Time: 11:40
 */
/**
 * 审批流程配置
 * Class Approvalconfig
 * @package app\approvalconfig\controller
 */
namespace app\approvalconfig\controller;
use app\admin\controller\Permissions;
use app\standard\model\TemplateModel;//在线填报表单模板
use app\quality\model\QualityFormInfoModel;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Settings;
use think\exception\PDOException;
use think\Loader;
use think\Db;
use think\Request;
use think\Session;

class Approvalconfig extends Permissions
{
//    protected $qualityFormInfoService;
//
//    public function __construct(Request $request = null)
//    {
//        $this->qualityFormInfoService = new QualityFormInfoModel();
//        parent::__construct($request);
//    }


    /**
     * 模板首页
     * @return mixed
     */
    public function index()
    {
        return $this->fetch();
    }

    /**
     * 获取所有的表单模板
     * @return \think\response\Json
     * @throws \think\Exception
     */
    public function getAllTemplate()
    {
        //实例化模型类
        $model = new TemplateModel();

        $data = $model->getAllTemplate();

        return json($data);
    }

    /**
     * 表单模板预览
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function previewTemplate()
    {
        if(request()->isAjax())
        {
                //实例化模型类
                $model = new TemplateModel();
                // 前台需要 传递 文件编号 id
                $param = input('param.');
                $file_id = isset($param['id']) ? $param['id'] : 0;
                if($file_id == 0){
                    return json(['code' => '-1','msg' => '编号有误']);
                }
        //        $file_obj = Db::name('quality_division_controlpoint_relation')->alias('r')
        //            ->join('norm_controlpoint c','c.id=r.control_id','left')
        //            ->where('r.id',$file_id)->field('c.code,c.name,r.division_id')->find();
                $file_obj = $model->getOne($file_id);
                if(empty($file_obj)){
                    return json(['code' => '-1','msg' => '编号无效']);
                }
                $new_name = iconv("utf-8","gb2312",$file_obj['code'].$file_obj['name']);
                $filePath = ROOT_PATH . 'public' . DS . 'Data' . DS . 'form' . DS . 'quality' . DS . $new_name . '.docx';
                if(!file_exists($filePath)){
                    return json(['code' => '-1','msg' => '文件不存在']);
                }

                //设置临时文件，避免C盘Temp不可写报错
        //        Settings::setTempDir('temp');
        //        $phpword = new PhpWord();
        //        $phpword = $phpword->loadTemplate($filePath);
        //        $infos = $this->qualityFormInfoService->getFormBaseInfo($file_obj['division_id']);
        //        foreach ($infos as $key => $value) {
        //            $phpword->setValue('{' . $key . '}', $value);
        //        }
        //        $docname = $phpword->save();

                // 预览
                    $code = 1;
                    $msg = '预览成功';
                    $path = $filePath;
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
}