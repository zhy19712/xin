<?php
/**
 * Created by PhpStorm.
 * User: zhifang
 * Date: 2018/4/11
 * Time: 11:43
 */

namespace app\quality\controller;

use app\admin\controller\Permissions;
use app\quality\model\DivisionControlPointModel;
use app\quality\model\DivisionUnitModel;
use app\quality\model\QualityFormInfoModel;
use app\quality\model\UploadModel;
use app\admin\model\AdminGroup;//组织机构
use app\admin\model\Admin;//用户表
use app\admin\model\AdminCate;//角色分类表
use app\standard\model\ControlPoint;
use app\standard\model\MaterialTrackingDivision;
use PhpOffice\Common\Autoloader;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\TemplateProcessor;
use PhpOffice\PhpWord\Writer\PDF\DomPDF;
use think\Db;
use think\Exception;
use think\File;
use think\Request;
use think\Session;

class Element extends Permissions
{
    protected $divisionControlPointService;
    protected $uploadService;
    protected $qualityFormInfoService;

    public function __construct(Request $request = null)
    {
        $this->divisionControlPointService = new DivisionControlPointModel();
        $this->uploadService = new UploadModel();
        $this->qualityFormInfoService = new QualityFormInfoModel();
        parent::__construct($request);
    }
##单元策划

    /**
     * 单元策划
     * @return mixed
     */
    public function plan()
    {
        return $this->fetch();
    }

    /**
     * 新增控制点
     * @param $Division 划分树
     * @param $TrackingDivision 工序
     * @return mixed
     */
    public function addplan($Division = null, $TrackingDivision = null)
    {
        if ($this->request->isAjax()) {
            $mod = input('post.');
            $list = $this->divisionControlPointService->where(['division_id' => $mod['division_id'], 'ma_division_id' => $mod['ma_division_id']])->column('control_id');
            $_mod = array();
            foreach ($mod['control_id'] as $item) {
                //避免重复添加控制点
                if (in_array($item, $list)) {
                    continue;
                }
                $_item = array();
                $_item['division_id'] = $mod['division_id'];
                $_item['ma_division_id'] = $mod['ma_division_id'];
                $_item['type'] = 1;
                $_item['control_id'] = $item;
                $_mod[] = $_item;
            }
            try {
                if (sizeof($_mod) > 0) {
                    $this->divisionControlPointService->allowField(true)->saveAll($_mod);
                }
            } catch (Exception $e) {
                return json(['code' => -1, 'msg' => $e->getMessage()]);
            }

            return json(['code' => 1]);
        }
        $this->assign('Division', $Division);
        $this->assign('TrackingDivision', $TrackingDivision);
        return $this->fetch();
    }


    /**
     * 获取检验批列表
     * @param $id
     * @return \think\response\Json
     * @throws \think\exception\DbException
     */
    public function getDivisionUnitTree($id)
    {
        return json(DivisionUnitModel::all(['division_id' => $id]));
    }

    /**
     * 获取工序列表
     * @param $id
     * @return \think\response\Json
     * @throws \think\exception\DbException
     */
    public function getProcedures($id)
    {
        //按工序类型排序
        $res=Db::name('norm_materialtrackingdivision')
            ->where(['pid' => $id, 'type' => 3])
            ->order('sort_id')
            ->select();
        return json($res);

    }
    public function getUnitProcedures($division_id)
    {
        //获取控制点关联id
       $res=Db::name('quality_unit')
           ->where(['division_id'=>$division_id])
           ->select();
       return json(['msg'=>'success','data'=>$res]);

    }


    /**
     * 删除控制点
     * @param $id
     * @return \think\response\Json
     */
    public function delControlPointRelation($id)
    {
        $mod = DivisionControlPointModel::get($id);
        if ($mod['status'] == 1) {
            return json(['code' => -1, 'msg' => '控制点已执行']);
        }
        if ($mod->delete()) {
            return json(['code' => 1]);
        } else {
            return json(['code' => -1]);
        }
    }


##单元管控

    /**
     * 单位管控
     * @return mixed
     */
    public function controll()
    {
        $this->assign('userId',Session::get('current_id'));
        return $this->fetch();
    }

    /**
     * 新增控制点执行情况及附件资料
     * @param $cpr_id
     * @param $att_id
     * @param $filename
     * @param $type 1、执行情况，2、附件资料
     * @return \think\response\Json
     */
    public function addExecution($cpr_id, $att_id, $filename, $type)
    {
        $data=['contr_relation_id' => $cpr_id, 'attachment_id' => $att_id, 'data_name' => $filename, 'type' => $type];
        $res=Db::name('quality_upload')
            ->insert($data);
        if ($res) {
            if($type==1) {
                //更新控制点执行情况
                Db::name('quality_division_controlpoint_relation')
                    ->where(['id' => $cpr_id])
                    ->update(['status' => 1]);
            }
            return json(['code' => 1]);
        } else {
            return json(['code' => -1]);
        }
    }

    /**
     * 控制点模板下载
     * @param $cpr_id
     * @return string
     * @throws \PhpOffice\PhpWord\Exception\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function download($cpr_id)
    {
        $cp = $this->divisionControlPointService->with('ControlPoint')->where('id', $cpr_id)->find();
        $norm_template=Db::name('norm_template')->alias('t')
            ->join('norm_controlpoint c', 't.id = c.qualitytemplateid', 'left')
            ->join('quality_division_controlpoint_relation r', 'r.control_id = c.id', 'left')
            ->where(['r.id'=>$cpr_id])
            ->find();
        $qualitytemplateid = $norm_template['qualitytemplateid'];
        if ($qualitytemplateid == 0) {
            return json(['code' => -1, 'msg' => '控制点未进行模板关联!']);
        }
        else
         {
            $formPath = ROOT_PATH . 'public' . DS . "data\\form\\quality\\" . $cp['ControlPoint']['code'] . $cp['ControlPoint']['name'] . ".docx";
            $formPath = iconv('UTF-8', 'GB2312', $formPath);
            $flag = file_exists($formPath);
            if ($this->request->isAjax()) {
                if (!$flag) {
                    return json(['code' => -1, 'msg' => '文件不存在!']);
                }
                return json(['code' => 1]);
            }
            if (!$flag) {
                return "文件不存在";
            }
            //设置临时文件，避免C盘Temp不可写报错
            Settings::setTempDir('temp');
            $phpword = new PhpWord();
            $phpword = $phpword->loadTemplate($formPath);
            $infos = $this->qualityFormInfoService->getFormBaseInfo($cp['division_id']);
            foreach ($infos as $key => $value) {
                $phpword->setValue('{' . $key . '}', $value);
            }
            $docname = $phpword->save();


            header('Content-Disposition: attachment; filename="' . $cp['ControlPoint']['code'] . $cp['ControlPoint']['name'] . '.docx"');
            header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
            header('Content-Transfer-Encoding: binary');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Expires: 0');

            $file = fopen($docname, 'r');
            echo fread($file, filesize($docname));
            fclose($file);
        }
    }

    /**
     * 下载表单
     * @param $formId
     * @return string
     * @throws \PhpOffice\PhpWord\Exception\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function formDownload($formId)
    {
        $cp = $this->qualityFormInfoService->with('ControlPoint')->where('id', $formId)->find();
        $formPath = ROOT_PATH . 'public' . DS . "data\\form\\quality\\" . $cp['ControlPoint']['code'] . $cp['ControlPoint']['name'] . ".docx";
        $formPath = iconv('UTF-8', 'GB2312', $formPath);
        $flag = file_exists($formPath);

        if ($this->request->isAjax()) {
            if (!$flag) {
                return json(['code' => -1, 'msg' => '文件不存在!']);
            }
            return json(['code' => 1]);
        }
        if (!$flag) {
            return "文件不存在";
        }

        //设置临时文件，避免C盘Temp不可写报错
        Settings::setTempDir('temp');
        $phpword = new PhpWord();
        //$phpword = $phpword->loadTemplate($formPath);
        $phpword= new TemplateProcessor($formPath);
        $infos = $this->qualityFormInfoService->getFormBaseInfo($cp['DivisionId']);

        foreach ($infos as $key => $value) {
            $phpword->setValue("{{$key}}", $value);
        }
        $formInfo = unserialize($cp['form_data']);
        foreach ($formInfo as $item) {
            $phpword->setValue('{'.$item['Name'].'}', $item['Value']);
        }
        $docname = $phpword->save();

        header( 'Content-type:text/html;charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $cp['ControlPoint']['code'] . $cp['ControlPoint']['name'] . '.docx"');
        //header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        header('Content-Transfer-Encoding: binary');
        header("Content-type:application/octet-stream ");
        header("Accept-Ranges:bytes ");
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Expires: 0');

        $file = fopen($docname, 'r');
        echo fread($file, filesize($docname));
        fclose($file);
    }

    public function printPDF($cpr_id)
    {
        //todo 暂缓
        $cp = $this->divisionControlPointService->with('ControlPoint')->where('id', $cpr_id)->find();
        $formPath = ROOT_PATH . 'public' . DS . "data\\form\\quality\\" . $cp['ControlPoint']['code'] . $cp['ControlPoint']['name'] . ".docx";
        $formPath = iconv('UTF-8', 'GB2312', $formPath);
        Autoloader::register();
        Settings::setTempDir('temp');
        Settings::setPdfRendererName(Settings::PDF_RENDERER_DOMPDF);
        Settings::setPdfRendererPath(ROOT_PATH . 'extend\\PhpOffice\\PhpWord\\Writer\\PDF');
        $phpword = new PhpWord();
        $phpword = IOFactory::load($formPath);
        //$phpword=$phpword->loadTemplate($formPath);
        //$phpword->setValue('SectionName','222');
        $phpword = IOFactory::createWriter($phpword, "PDF");
        return $phpword->save("./1.pdf");
        //$phpword->saveAs('./1.docx');
        ////$phpword->save("1.html");
    }

    public function word2html()
    {
        //$word = new \COM("word.application") or die("Unable to instanciate Word");
        //$word->Visible = 1;
        //$word->Documents->Open('D:\Works\php\fengning\public\1.docx');
        //$word->Documents[1]->SaveAs('./1.html', 8);
        //$word->Quit();
        //$word = null;
        //unset($word);

        $word = new \COM("word.application") or die("Can't start Word!");
        $word->Visible = 0;
        $word->Documents->Open('D:\Works\php\fengning\public\1.docx', false, false, false, "1", "1", true);


        $word->ActiveDocument->final = false;
        $word->ActiveDocument->Saved = true;
        $word->ActiveDocument->ExportAsFixedFormat(
            'D:\Works\php\fengning\public\1.pdf',
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

        //$word=new \COM("Word.Application") or die("无法打开 MS Word");
        //$word->visible = 1 ;
        //$word->Documents->Open('D:\\Works\\php\\fengning\\public\\1.docx')or die("无法打开这个文件");
        //$htmlpath=substr('D:\\Works\\php\\fengning\\public\\1.docx',0,-4);
        //$word->ActiveDocument->SaveAs($htmlpath,8);
        //$word->quit(0);
    }
    ##单元验评

    /**
     * 单位验评
     * @return mixed
     */
    public function check()
    {
        return $this->fetch();
    }

    /**
     * 验评
     * @return \think\response\Json
     * @throws \think\exception\DbException
     */
    public function Evaluate()
    {
        $mod = input('post.');
        $_mod = DivisionUnitModel::get($mod['Unit_id']);
        $_mod['EvaluateResult'] = $mod['EvaluateResult'];
        $_mod['EvaluateDate'] = $mod['EvaluateDate'];
        $res = $_mod->save();
        if ($res) {
            return json(['code' => 1]);
        } else {
            return json(['code' => -1]);
        }
    }

    /**
     * Created by PhpStorm.
     * User: sry
     * Date: 2018/5/17
     * Remark: 单元质量管理新加接口
     */

    //单元策划
    public function insertalldata(){
        $param=input('param.');
        $en_type=$param['en_type'];
        $unit_id=$param['unit_id'];
        $type=1;//1为单元工程
        //找单元工程划分段号下面的所有工序
        $produceid=Db::name('norm_materialtrackingdivision')
                  ->where(['pid'=>$en_type,'cat'=>5])
                  ->column('id');
        //找出工序下的所有控制点
        $id_arr=Db::name('norm_controlpoint')
            ->where('procedureid','in',$produceid)
            ->field('id,procedureid')
            ->select();
        //遍历查找是否quality_implement_status中有控制点，没有的话添加上去
        foreach($id_arr as $v)
        {
            $limit = Db::name('quality_division_controlpoint_relation')
                ->where(['control_id' => $v['id'], 'division_id' => $unit_id,'type'=>1])
                ->find();
            //如果有数据，不插入
            if ($limit)
            {
                continue;
            }
            else
            {
                //如果没有控制点信息，将其插入数据表中
                $data=['type'=>1,'division_id'=>$unit_id,'ma_division_id'=>$v['procedureid'],'control_id'=>$v['id']];
                Db::name('quality_division_controlpoint_relation')
                    ->insert($data);
            }
        }
    }

    //勾选时访问的接口，将该条数据的状态更新
    public function checkout()
    {
        //获得控制点和单元工关联的数据id
        $param = input('param.');
        $id=$param['id'];//relation_id主键
        $unit_id=$param['unit_id'];
        //点击的时候将checked值更新,0为选中，1为不选
        $checked=$param['checked'];
        $res=Db::name('quality_division_controlpoint_relation')
            ->where(['division_id'=>$unit_id,'id'=>$id,'type'=>1])
            ->update(['checked'=>$checked]);
       if($res)
       {
           return json(['msg'=>'success']);
       }

    }

    //检测管控中的控件能否使用
    public function checkform()
    {
            $cp_name='单元工程质量验评';
            $search_name='单元工程质量等级评定表';
            $param = input('param.');
            $unit_id=17;
            $unit= Db::name('quality_unit')
                ->where(['id' =>$unit_id])
                ->find();
            //找工程类型，找验评工序，再找到对应控制点
            $en_type=$unit['en_type'];
            $nm=Db::name('norm_materialtrackingdivision')
                ->where(['pid' =>$en_type])
                ->where('name', 'like', '%'.$search_name)
                ->find();
            $nm_id=$nm['id'];

            $cp=Db::name('norm_controlpoint')
                ->where(['procedureid' =>$nm_id])
                ->where('name', 'like', '%'.$cp_name)
                ->find();
            $cp_id=$cp['id'];

            $res = Db::name('quality_form_info')
                ->where(['ControlPointId' =>$cp_id,'DivisionId' =>$unit_id,'ApproveStatus'=>2])
                ->where('form_name', 'like', '%' . $search_name)
                ->find();

            $cpr=Db::name('quality_division_controlpoint_relation')
                 ->where(['control_id' =>$cp_id,'division_id' =>$unit_id,'type'=>1])
                 ->find();
            $cpr_id=$cpr['id'];//获取cpr_id

            //如果有已审批的质量评定表,说明是线上流程，不给予控件使用权限
            if (count($res)>0) {
                return json(['msg' => 'fail','remark'=>'线上流程']);
            }
            //没有的话去附件表里找是否有扫描件上传，如果有最终评定表，就给权限，没有就不给
            else {
                $copy = Db::name('quality_upload')
                    ->where(['contr_relation_id' => $cpr_id, 'type' => 1])
                    ->where('data_name', 'like', '%'.$search_name .'%')
                    ->find();
                if ($copy) {
                    $flag=$this->evaluatePremission($unit_id);
                    if($flag==1)
                    {
                        return json(['msg' => 'success']);
                    }
                    else
                    {
                        return json(['msg' => 'fail','remark'=>'权限不足']);
                    }
                }
                else {
                    return json(['msg' => 'fail','remark'=>'尚未上传验评扫描件']);
                }
            }
    }
    //将表单中的中文日期转为英文
    public  function setFormattime($timestr)
    {
        $arr = date_parse_from_format('Y年m月d日',$timestr);
        $time = mktime(0,0,0,$arr['month'],$arr['day'],$arr['year']);
        return $time;
    }
    //获取评测结果
    public function saveEvaluation($form_id)
    {
        //判定是否是等级评定表
        $fm_name = '单元工程质量等级评定表';
        $fm = Db::name('quality_form_info')
            ->where(['id' => $form_id])
            ->where('form_name', 'like', '%'.$fm_name .'%')
            ->find();
        if (count($fm) > 0)
        {
            $form_data = unserialize($fm['form_data']);//反序列化
            foreach ($form_data as $v) {
                if ($v['Name'] == 'input_date_1') {
                    $evaluation_date=  $v['Value']==''? '':$v['Value'];//验评日期
                    break;
                }
            }
            foreach ($form_data as $v) {
                if ($v['Name'] == 'input_hgl_result') {
                    $evaluation = $v['Value']==''? "无验评日期":$v['Value'];//验评结果
                    break;
                }
            }
            switch ($evaluation)
            {
                case "无验评日期":
                    $evaluation=0;
                    break;
                case "不合格":
                    $evaluation=1;
                    break;
                case "合格":
                    $evaluation=2;
                    break;
                case "优良":
                    $evaluation=3;
                    break;
            }
            $param['EvaluateDate'] = $this->setFormattime($evaluation_date);
            $param['EvaluateResult'] = $evaluation;
            $unitModel=new DivisionUnitModel();
            $param['id']=$fm['DivisionId'];
            $unitModel->editTb($param);
        }
        else
        {
            return json(['data' => '暂无验评']);
        }
    }

    public function getEvaluation()
    {
      $param=input("param.");
      $unit_id=$param['unit_id'];
      $model=new DivisionUnitModel();
      $data=$model->getOne($unit_id);

      $evaluateDate=$data['EvaluateDate'];
      if($evaluateDate!='')
      {
          $evaluateDatedate=date('Y-m-d',$evaluateDate);
      }

      if($data) {
          return json(['msg'=>'success','evaluateDate'=>$evaluateDate,'evaluateResult'=>$data['EvaluateResult']]);
       }
       else{
          return json(['msg'=>'fail']);
       }
    }


    //检查扫描件回传情况
    public function copycheck()
    {
        if ($this->request->isAjax()) {
            $cpr_id=input('param.')['cpr_id'];
            $res = Db::name('quality_upload')
                ->where(['contr_relation_id'=>$cpr_id,'type'=>1])
                ->find();
            //如果有结果
            if (count($res)>0) {
                return json(['msg' => 'fail', 'remark' => '已上传对应扫描件，如想重新上传请先删除之前的扫描件']);
            } else {
                return json(['msg' => 'success']);
            }
        }
    }
   //判断是否拥有监理和管理员权限
    public function evaluatePremission($unit_id)
    {
        if(request()->isAjax()){
            //实例化模型类
            $admin = new Admin();
            $admincate = new AdminCate();
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
                return $flag;
            }
        }
    }


}