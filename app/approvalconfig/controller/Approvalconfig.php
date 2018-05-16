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
        if(request()->isAjax()){
            //实例化模型类
            $model = new TemplateModel();
            // 前台需要 传递 文件编号 id
            $param = input('param.');
            $file_id = isset($param['id']) ? $param['id'] : 0;
            if($file_id == 0){
                return json(['code' => '-1','msg' => '编号有误']);
            }
//            $file_obj = Db::name('quality_subdivision_planning_list')->alias('s')
//                ->join('controlpoint c','c.id=s.controller_point_id','left')
//                ->where('s.id',$file_id)->field('c.code,c.name,s.selfid')->find();
            $file_obj = $model->getOne($file_id);
            if(empty($file_obj)){
                return json(['code' => '-1','msg' => '编号无效']);
            }
//            \uploads\admin\admin_thumb\20180323\3452ba9d4fc1794d4325edf2365aee25.jpg
            $formPath = ROOT_PATH . 'public' . DS . "data\\form\\quality\\" . $file_obj['code'] . $file_obj['name'] . ".docx";
            $formPath = iconv('UTF-8', 'GB2312', $formPath);

            if(!file_exists($formPath)){
                return json(['code' => '-1','msg' => '文件不存在']);
            }

//            设置临时文件，避免C盘Temp不可写报错
//            Settings::setTempDir('temp');
//            $phpword = new PhpWord();
//            $phpword = $phpword->loadTemplate($formPath);
//            $infos = $this->qualityFormInfoService->getFormBaseInfo($file_obj['selfid']);
//            foreach ($infos as $key => $value) {
//                $phpword->setValue('{' . $key . '}', $value);
//            }
//            $docname = $phpword->save();
            // 预览里有 打印
            $code = 1;
            $msg = '预览成功';


//            $path =    ".".DS."Data\\form\\quality\\".$file_obj['code'].$file_obj['name'].".docx";

//            $new_name = iconv("utf-8","gb2312",$file_obj['code'].$file_obj['name']);

            $file_names = $file_obj['code'].$file_obj['name'];




//            dump($file_names);
//            $new_name = iconv("utf-8","gb2312",$file_names);
            $new_name = iconv("utf-8","GB2312//IGNORE",$file_names);

//            var_dump($new_name);
//            dump($new_name);

//            $new_name = "1.0.8.0";

            $path =    ".".DS."Data\\form\\quality\\".$new_name.".docx";

//            halt($path);




//            $path =    ".".DS."Data\\form\\quality\\". "1.0.8.0" .".docx";

//            .\uploads\atlas\atlas_thumb\20180515\a5b8852135aa7e9e3f9d821110534eb7.docx


//            $path =   '.' ."/public/uploads/atlas/atlas_thumb/20180515" . "a5b8852135aa7e9e3f9d821110534eb7" .".docx";

//            halt($path);




            $extension = strtolower(get_extension(substr($path,1)));
            $pdf_path = './uploads/temp/' . basename($path) . '.pdf';

            if(!file_exists($pdf_path)){
                if($extension === 'doc' || $extension === 'docx' || $extension === 'txt'){
                    $this->doc_to_pdf($path);
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


    protected function doc_to_pdf($path)
    {
        $srcfilename = ROOT_PATH . 'public' .$path;
        $filepath = '/uploads/temp/' . basename($path);
        $destfilename = ROOT_PATH . 'public' . $filepath;
        try {
            if (!file_exists($srcfilename)) {
                return json(['code' => 0, 'msg' => '文件不存在']);
            }
//            $srcfilename = iconv('gb2312','utf-8',$srcfilename);
            $word = new \COM("word.application") or die("Can't start Word!");
            $word->Visible = 0;
            $word->Documents->Open($srcfilename, false, false, false, "1", "1", true);
            if (file_exists($destfilename . '.pdf')) {
                unlink($destfilename . '.pdf');
            }

            $word->ActiveDocument->final = false;
            $word->ActiveDocument->Saved = true;
            $word->ActiveDocument->ExportAsFixedFormat(
                $destfilename . '.pdf',
                17,                         // wdExportFormatPDF
                false,                      // open file after export
                0,                          // wdExportOptimizeForPrint
                3,                          // wdExportFromTo
                1,                          // begin page
                5000,                       // end page
                7,                          // wdExportDocumentWithMarkup
                true,                       // IncludeDocProps
                true,                       // KeepIRM
                1                           // WdExportCreateBookmarks
            );
            $word->ActiveDocument->Close();
            $word->Quit();
            return json(['code' => 1, 'msg' => '', 'data' => $filepath]);
        } catch (\Exception $e) {
            if (method_exists($word, "Quit")) {
                $word->Quit();
            }
            return json(['code' => 0, 'msg' => '未知错误:'.$e->getMessage()]);
        }
    }

    /**
     * 编辑质量表单
     * @param $cpr_id 控制点
     * @param $currentStep 当前审批步骤
     * @param bool $isView 是否查看
     * @param null $id 表单id
     * @return bool|mixed|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function edit($cpr_id, $currentStep, $isView = false, $id = null)
    {
        //获取模板路径
        //获取控制点信息，组合模板路径
        $model = new TemplateModel();
        // 前台需要 传递 文件编号 id
        $param = input('param.');
        $file_id = isset($param['id']) ? $param['id'] : 0;

        $file_obj = $model->getOne($file_id);
        if(empty($file_obj)){
            return json(['code' => '-1','msg' => '编号无效']);
        }

//        $cp = $this->divisionControlPointService->with('controlpoint')->where('id', $cpr_id)->find();

        $formPath = ROOT_PATH . 'public' . DS . "data\\form\\quality\\" . $file_obj['code'] . $file_obj['name'] . ".html";
        $formPath = iconv('UTF-8', 'GB2312', $formPath);
        if (!file_exists($formPath)) {
            return "模板文件不存在";
        }
        $htmlContent = file_get_contents($formPath);
//        $htmlContent = str_replace("{id}", $id, $htmlContent);
//        $htmlContent = str_replace("{templateId}", $cp['controlpoint']['qualitytemplateid'], $htmlContent);
//        $htmlContent = str_replace('{divisionId}', $cp['division_id'], $htmlContent);
//        $htmlContent = str_replace('{isInspect}', $cp['type'] ? 'true' : 'false', $htmlContent);
//        $htmlContent = str_replace('{procedureId}', $cp['ma_division_id'], $htmlContent);
//        $htmlContent = str_replace('{controlPointId}', $cp['control_id'], $htmlContent);
        $htmlContent = str_replace('{formName}', $file_obj['name'], $htmlContent);
        $htmlContent = str_replace('{currentStep}', $currentStep, $htmlContent);
        $htmlContent = str_replace('{isView}', $isView, $htmlContent);

        //输出模板内容
        //Todo 暂时使用replace替换，后期修改模板使用fetch自定义模板渲染
//        $htmlContent = $this->setFormInfo($cp['division_id'], $htmlContent);

        //获取表单基本信息
        $formdata = "";
//        if (!is_null($id)) {
//            $_formdata = $this->qualityFormInfoService->where(['id' => $id])->find()['form_data'];
//            $formdata = json_encode(unserialize($_formdata));
//        }
        $htmlContent = str_replace('{formData}', $formdata, $htmlContent);
        $htmlContent .= "<input type='hidden' id='cpr' value=''>";
        return $htmlContent;
    }
}