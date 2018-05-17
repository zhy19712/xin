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
        return json(MaterialTrackingDivision::all(['pid' => $id, 'type' => 3]));
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
        $res = $this->uploadService->save(['contr_relation_id' => $cpr_id, 'attachment_id' => $att_id, 'data_name' => $filename, 'type' => $type]);
        if ($res) {
            //更新控制点执行情况
            $this->divisionControlPointService->save(['status' => 1], ['id' => $cpr_id]);
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
            $phpword->setValue('{' . $item['Name'] . '}', $item['Value']);
        }
        $docname = $phpword->save();


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
    //勾选时访问的接口，将该条数据的状态更新为未选中
    public function checkout()
    {
        //获得控制点和单元工关联的数据id
        $post = input('post.');
        $id=$post['id'];

        //将该条数据的checked更新为1,1为未勾选状态
        Db::name('quality_division_controlpoint_relation')
            ->where(['id'=>$id])
            ->update(['checked'=>1]);

    }

    //单元管控
    //访问element中的quality_division_controlpoint_realtion表（单元策划访问common下的该方法）
    public function datatablesPre()
    {
        //接收表名，列名数组 必要
        $columns = $this->request->param('columns/a');
        //获取查询条件
        $id = $this->request->has('id') ? $this->request->param('id', 0, 'intval') : 0;
        $table = $this->request->param('tableName');
        //接收查询条件，可以为空
        $columnNum = sizeof($columns);
        $columnString = '';
        for ($i = 0; $i < $columnNum; $i++) {
            $columnString = $columns[$i]['name'] . '|' . $columnString;
        }
        $columnString = substr($columnString, 0, strlen($columnString) - 1);
        //获取Datatables发送的参数 必要
        $draw = $this->request->has('draw') ? $this->request->param('draw', 0, 'intval') : 0;
        //排序列
        $order_column = $this->request->param('order/a')['0']['column'];
        //ase desc 升序或者降序
        $order_dir = $this->request->param('order/a')['0']['dir'];

        $order = "";
        if (isset($order_column)) {
            $i = intval($order_column);
            $order = $columns[$i]['name'] . ' ' . $order_dir;
        }
        //搜索
        //获取前台传过来的过滤条件
        $search = $this->request->param('search/a')['value'];
        //分页
        $start = $this->request->has('start') ? $this->request->param('start', 0, 'intval') : 0;
        $length = $this->request->has('length') ? $this->request->param('length', 0, 'intval') : 0;
        $limitFlag = isset($start) && $length != -1;
        //新建的方法名与数据库表名保持一致
        return $this->$table($id, $draw, $table, $search, $start, $length, $limitFlag, $order, $columns, $columnString);
    }
    public function quality_division_controlpoint_relation($id, $draw, $table, $search, $start, $length, $limitFlag, $order, $columns, $columnString)
    {
        //查询
        //条件过滤后记录数 必要
        $recordsFiltered = 0;
        $recordsFilteredResult = array();
        $par = array();
        $par['type'] = 1;
        $par['checked'] = 0;//0为被选中
        $par['division_id'] = $this->request->param('division_id');
        if ($this->request->has('ma_division_id')) {
            $par['ma_division_id'] = $this->request->param('ma_division_id');
        }
        //表的总记录数 必要
        $recordsTotal = Db::name($table)->where($par)->count();
        if (strlen($search) > 0) {
            //有搜索条件的情况
            if ($limitFlag) {
                //*****多表查询join改这里******
                $recordsFilteredResult = Db::name($table)->alias('a')
                    ->join('norm_controlpoint b', 'a.control_id=b.id', 'left')
                    ->where($par)
                    ->field('a.id,b.code,b.name,a.status,a.division_id,a.ma_division_id,a.control_id,a.checked,b.remark')
                    ->order($order)->limit(intval($start), intval($length))->select();
                $recordsFiltered = sizeof($recordsFilteredResult);
            }
        } else {
            //没有搜索条件的情况
            if ($limitFlag) {
                //*****多表查询join改这里******
                $recordsFilteredResult = Db::name($table)->alias('a')
                    ->join('norm_controlpoint b', 'a.control_id=b.id', 'left')
                    ->where($par)
                    ->field('a.id,b.code,b.name,a.status,a.division_id,a.ma_division_id,a.control_id,a.checked,b.remark')
                    ->order($order)->limit(intval($start), intval($length))->select();
                $recordsFiltered = $recordsTotal;
            }
        }
        $temp = array();
        $infos = array();
        foreach ($recordsFilteredResult as $key => $value) {
            $length = sizeof($columns);
            for ($i = 0; $i < $length; $i++) {
                array_push($temp, $value[$columns[$i]['name']]);
            }
            $infos[] = $temp;
            $temp = [];
        }
        return json(['draw' => intval($draw), 'recordsTotal' => intval($recordsTotal), 'recordsFiltered' => $recordsFiltered, 'data' => $infos]);
    }

}