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
use app\quality\model\BranchModel;//分部质量管理
use app\quality\model\BranchfileModel;//分部质量管理文件上传
use app\admin\model\AdminGroup;//组织机构
use app\admin\model\Admin;//用户表
use app\quality\model\DivisionModel;//工程划分
use app\standard\model\ControlPoint;//控制点
use app\quality\model\QualityFormInfoModel;
use app\quality\model\DivisionControlPointModel;//工程划分、工序、控制点关系表
use app\quality\model\UploadModel;//分部管控、单位管控中的控制点文件上传
use app\quality\model\SendModel;//收发文
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Settings;
use think\exception\PDOException;
use think\Loader;
use think\Db;
use think\Request;
use think\Session;

class Branch extends Permissions
{
    protected $qualityFormInfoService;

    public function __construct(Request $request = null)
    {
        $this->qualityFormInfoService = new QualityFormInfoModel();
        parent::__construct($request);
    }
    /****************************分部策划************************/
    /**
     * 分部策划模板首页
     * @return mixed
     */
    public function plan()
    {
        return $this->fetch();
    }

    /**
     * 分部策划添加控制点
     * @return mixed
     */
    public function addPlan()
    {
        $param = input('param.');
        $selfid = $param["selfid"];//左侧节点树id
        $procedureid = $param["procedureid"];//工序号
        $this->assign('selfid', $selfid);
        $this->assign('procedureid', $procedureid);
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
     * 分部管控添加控制点
     * @return mixed
     */
    public function addControl()
    {
        $param = input('param.');
        $selfid = $param["selfid"];//左侧节点树id
        $procedureid = $param["procedureid"];//工序号
        $this->assign('selfid', $selfid);
        $this->assign('procedureid', $procedureid);
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
        $data = Db::name('norm_materialtrackingdivision')->group("id,name")->field("id,name")->where(['type'=>2,'cat'=>3])->select();
        if($data)
        {
            return json(['code'=>1,'data'=>$data]);
        }else
        {
            return json(['code'=>-1,'data'=>""]);
        }
    }

    /**ToDo
     * 功能暂时废弃
     * 分部策划列表-导出二维码
     * @return \think\response\Json
     */
//    public function exportCode()
//    {
//        // 前台 传递 要下载 哪个节点 下的所有 二维码
//        if(request()->isAjax()){
//            $id = $this->request->has('id') ? $this->request->param('id', 0, 'intval') : 1;
//            if($id == 0){
//                return json(['code' => '-1','msg' => '请选择工程划分节点']);
//            }else
//            {
//                // 获取 工程划分 下 所有的 控制点
//                $control = Db::name('quality_subdivision_planning_list')->alias('s')
//                    ->join('norm_materialtrackingdivision m','s.procedureid = m.id','left')
//                    ->join('controlpoint c','s.controller_point_id = c.id','left')
//                    ->where(['s.selfid'=>$id,'s.type'=>0])->column('s.controller_point_id,m.name as m_name,c.name as c_name');
//                if(empty($control)){
//                    return json(['code' => '-1','msg' => '没有数据']);
//                }
//
//                return json(['code' => 1]);
//            }
//        }
//            $id = $this->request->has('id') ? $this->request->param('id', 0, 'intval') : 1;
//            $attachment_id = [];
//            // 获取 工程划分 下 所有的 控制点
//            $control = Db::name('quality_subdivision_planning_list')->alias('s')
//                ->join('norm_materialtrackingdivision m','s.procedureid = m.id','left')
//                ->join('controlpoint c','s.controller_point_id = c.id','left')
//                ->where(['s.selfid'=>$id,'s.type'=>0])->column('s.controller_point_id,m.name as m_name,c.name as c_name');
//            $qrcode_bas_path = ROOT_PATH . 'public' .DS . 'uploads' . DS . 'quality' . DS . 'export-code';//文件路径
//            if(!is_dir($qrcode_bas_path)){
//                mkdir($qrcode_bas_path, 0777, true);
//            }
//            foreach ($control as $k=>$v){
//                $data['module'] = 'quality';
//                // 图片名称 是 工序名称-控制点编号-控制点名称.png
//                $data['filename'] = $v['m_name'] . '-' . $v['controller_point_id'] . '-' . $v['c_name'] . '.png';//文件名
//                $data['filepath'] = DS . 'uploads' . DS . 'quality' . DS . 'export-code' . DS . $data['filename'];//文件路径
//                // 生成二维码图片
//                $png_name = iconv("utf-8","gb2312",$data['filename']); // 图片名称
//                $png_path = $qrcode_bas_path . DS . $png_name;
//                Loader::import('phpqrcode\phpqrcode', EXTEND_PATH);
//                $text = url('quality/Unitqualitymanage/fileDownload/'.$v['controller_point_id']);
//                if(!file_exists($png_path)) {
//                    \QRcode::png($text,$png_path,'L',5,2);
//                }
//                $data['fileext'] = 'png';//文件后缀
//                $data['filesize'] = '';//无法获取文件大小
//                $data['create_time'] = time();//时间
//                $data['uploadip'] = $this->request->ip();//IP
//                $data['user_id'] = Session::has('admin') ? Session::get('admin') : 0;
//                if($data['module'] == 'admin') {
//                    //通过后台上传的文件直接审核通过
//                    $data['status'] = 1;
//                    $data['admin_id'] = $data['user_id'];
//                    $data['audit_time'] = time();
//                }
//                $data['use'] = 'exportCode';//用处
//                // 首先判断是否已经 生成过 二维码图片
//                $insert_id = Db::name('attachment')->where(['module'=>$data['module'],'filename'=>$data['filename']])->value('id');
//                if(empty($insert_id) && file_exists($png_path)){
//                    $insert_id = Db::name('attachment')->insertGetId($data);
//                }
//                $attachment_id[] = $insert_id;
//            }
//            $datalist = Db::name("attachment")->whereIn("id", $attachment_id)->column('filepath');
//            $zip = new \ZipArchive;
//            // 压缩文件名
//            $d_name = Db::name('quality_division')->where('id',$id)->value('d_name');
//            $new_png_name = iconv("utf-8", "GB2312//IGNORE", $d_name);
//            $zipName = ROOT_PATH . 'public' .DS .'uploads/quality/export-code/'.$new_png_name.'.zip';
//            //新建zip压缩包
//            if ($zip->open($zipName, \ZIPARCHIVE::CREATE) === TRUE) {
//                foreach($datalist as $val){
//                    $new_val = '.' . iconv("utf-8", "GB2312//IGNORE", $val);
//                    if(file_exists($new_val)){
//                        //addFile函数首个参数如果带有路径，则压缩的文件里包含的是带有路径的文件压缩
//                        //若不希望带有路径，则需要该函数的第二个参数
//                        $zip->addFile($new_val, basename($new_val));//第二个参数是放在压缩包中的文件名称，如果文件可能会有重复，就需要注意一下
//                    }
//                }
//            }
//
//            //打包zip
//            $zip->close();
//            if(!file_exists($zipName)){
//                exit("无法找到文件"); //即使创建，仍有可能失败
//            }
//            //如果不要下载，下面这段删掉即可，如需返回压缩包下载链接，只需 return $zipName;
//            header("Cache-Control: public");
//            header("Content-Description: File Transfer");
//            header('Content-disposition: attachment; filename='.basename($zipName)); //文件名
//            header("Content-Type: application/zip"); //zip格式的
//            header("Content-Transfer-Encoding: binary"); //告诉浏览器，这是二进制文件
//            header('Content-Length: '. filesize($zipName)); //告诉浏览器，文件大小
//            @readfile($zipName);
//            //最后删除指定改的下载包，防止文件重复
//            unlink($zipName);
//
//    }

    /**
     * ToDo
     * 功能暂时废弃
     * 控制点里的模板文件下载
     * @return \think\response\Json
     */
//    public function fileDownload()
//    {
//        if(request()->isAjax()){
//            // 前台需要 传递 文件编号 id
//            $param = input('param.');
//            $file_id = isset($param['id']) ? $param['id'] : 0;
//            if ($file_id == 0) {
//                return json(['code' => '-1', 'msg' => '编号有误']);
//            }
//            $file_obj = Db::name('quality_subdivision_planning_list')->alias('s')
//                ->join('controlpoint c', 'c.id=s.controller_point_id', 'left')
//                ->where('s.id', $file_id)->field('c.code,c.name,s.selfid')->find();
//            if (empty($file_obj)) {
//                return json(['code' => '-1', 'msg' => '编号无效']);
//            }
//            $formPath = ROOT_PATH . 'public' . DS . "data\\form\\quality\\" . $file_obj['code'] . $file_obj['name'] . ".docx";
//            $formPath = iconv('UTF-8', 'GB2312', $formPath);
//            if (!file_exists($formPath)) {
//                return json(['code' => '-1', 'msg' => '文件不存在']);
//            } else {
//                return json(['code' => 1]); // 文件存在，告诉前台可以执行下载
//            }
//        }
//            $param = input('param.');
//            $file_id = isset($param['id']) ? $param['id'] : 0;
//
//            $file_obj = Db::name('quality_subdivision_planning_list')->alias('s')
//            ->join('controlpoint c', 'c.id=s.controller_point_id', 'left')
//            ->where('s.id', $file_id)->field('c.code,c.name,s.selfid')->find();
//
//        $formPath = ROOT_PATH . 'public' . DS . "data\\form\\quality\\" . $file_obj['code'] . $file_obj['name'] . ".docx";
//        $formPath = iconv('UTF-8', 'GB2312', $formPath);
//        //设置临时文件，避免C盘Temp不可写报错
//        Settings::setTempDir('temp');
//        $phpword = new PhpWord();
//        $phpword = $phpword->loadTemplate($formPath);
//        $infos = $this->qualityFormInfoService->getFormBaseInfo($file_obj['selfid']);
//        foreach ($infos as $key => $value) {
//            $phpword->setValue('{' . $key . '}', $value);
//        }
//        $docname = $phpword->save();
//
//
//        header('Content-Disposition: attachment; filename="' . $file_obj['code'] . $file_obj['name'] . '.docx"');
//        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
//        header('Content-Transfer-Encoding: binary');
//        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
//        header('Expires: 0');
//
//        $file = fopen($docname, 'r');
//        echo fread($file, filesize($docname));
//        fclose($file);
//    }

    /**
     * 控制点里的模板文件打印
     * @return \think\response\Json
     */
//    public function  printDocument()
//    {
//        if(request()->isAjax()){
//            // 前台需要 传递 文件编号 id
//            $param = input('param.');
//            $file_id = isset($param['id']) ? $param['id'] : 0;
//            if($file_id == 0){
//                return json(['code' => '-1','msg' => '编号有误']);
//            }
//            $file_obj = Db::name('quality_subdivision_planning_list')->alias('s')
//                ->join('controlpoint c','c.id=s.controller_point_id','left')
//                ->where('s.id',$file_id)->field('c.code,c.name,s.selfid')->find();
//            if(empty($file_obj)){
//                return json(['code' => '-1','msg' => '编号无效']);
//            }
////            \uploads\admin\admin_thumb\20180323\3452ba9d4fc1794d4325edf2365aee25.jpg
//            $formPath = ROOT_PATH . 'public' . DS . "data\\form\\quality\\" . $file_obj['code'] . $file_obj['name'] . ".docx";
//            $formPath = iconv('UTF-8', 'GB2312', $formPath);
//
//            if(!file_exists($formPath)){
//                return json(['code' => '-1','msg' => '文件不存在']);
//            }
//
////            设置临时文件，避免C盘Temp不可写报错
////            Settings::setTempDir('temp');
////            $phpword = new PhpWord();
////            $phpword = $phpword->loadTemplate($formPath);
////            $infos = $this->qualityFormInfoService->getFormBaseInfo($file_obj['selfid']);
////            foreach ($infos as $key => $value) {
////                $phpword->setValue('{' . $key . '}', $value);
////            }
////            $docname = $phpword->save();
//            // 预览里有 打印
//                $code = 1;
//                $msg = '预览成功';
//                $path =   DS."data\\form\\quality\\".$file_obj['code'].$file_obj['name'].".docx";
//                $extension = strtolower(get_extension(substr($path,1)));
//                $pdf_path = './uploads/temp/' . basename($path) . '.pdf';
//                if(!file_exists($pdf_path)){
//                    if($extension === 'doc' || $extension === 'docx' || $extension === 'txt'){
//                        doc_to_pdf($path);
//                    }else if($extension === 'xls' || $extension === 'xlsx'){
//                        excel_to_pdf($path);
//                    }else if($extension === 'ppt' || $extension === 'pptx'){
//                        ppt_to_pdf($path);
//                    }else if($extension === 'pdf'){
//                        $pdf_path = $path;
//                    }else if($extension === "jpg" || $extension === "png" || $extension === "jpeg"){
//                        $pdf_path = $path;
//                    }else {
//                        $code = 0;
//                        $msg = '不支持的文件格式';
//                    }
//
//                    return json(['code' => $code, 'path' => substr($pdf_path,1), 'msg' => $msg]);
//                }else{
//                    return json(['code' => $code,  'path' => substr($pdf_path,1), 'msg' => $msg]);
//                }
//        }
//    }

    /**
     * TODO
     * 功能废弃
     * 删除控制点
     * 删除控制点 注意: 已经执行 的控制点 不能删除
     *
     * 控制点 存在于 controlpoint 表 和 fengning_quality_subdivision_planning_list 中
     * controlpoint 表里的数据 是原始的，fengning_quality_subdivision_planning_list 是 在 分部策划里 后来 新增的关系记录
     *
     * 如果关系记录存在 该控制点 那么就应该先
     * 要关联 删除 记录里的控制点执行情况 和 图像资料  以及它们所包含的文件 以及 预览的pdf文件
     * 然后 删除 这条关系记录
     *
     * 最后 删除 原始数据
     *
     * type 类型:1 检验批 0 工程划分
     * @return \think\response\Json
     * @throws \think\Exception
     */
//    public function controlDel()
//    {
//        if(request()->isAjax()){
//            //实例化模型类
//            $model = new BranchModel();
//            //分部策划列表id
//            $id = input('param.id');
//            //查询一条分部策划列表中的信息
//            $info = $model->getOne($id);
//            if($info["status"] > 0)
//            {
//                return ['code' => -1,'msg' => '已执行控制点,不能删除!'];
//            }
//            $flag = $model->associationDeletion($id);
//            return json($flag);
//        }
//    }

    /**
     * Todo
     * 功能废弃
     * 全部删除控制点
     * type 类型:1 检验批 0 工程划分
     * @return \think\response\Json
     * @throws \think\Exception
     */
//    public function controlAllDel()
//    {
//        try{
//            if(request()->isAjax()){
//                //实例化模型类
//                $model = new BranchModel();
//                //分部策划列表id
//                $selfid = input('param.selfid');//左侧树节点的id
//                $procedureid = input('param.procedureid');//所属工序号
//
//                // 已经执行 的控制点 不能删除
//                $count = $model->getAllcount($selfid,$procedureid);
//                if($count > 0)
//                {
//                    return ['code' => -1,'msg' => '已执行控制点,不能删除!'];
//                }
//                //根据所属工序号查询所有的分部策划列表中的数据
//                $data = $model->getAllid($selfid,$procedureid);
//                if(!empty($data))
//                {
//                    foreach ($data as $k=>$v)
//                    {
//                        $model->associationDeletion($v['id']);
//                    }
//                    return ['code' => 1, 'msg' => '删除成功'];
//                }
//                else
//                {
//                    return ["code" => -1,"msg" => ""];
//                }
//            }
//        }catch (PDOException $e){
//            return ['code' => -1,'msg' => $e->getMessage()];
//        }
//    }

    /**
     * 添加控制点
     * @return \think\response\Json
     */
//    public function addControlPoint()
//    {
//        try{
//
//            if(request()->isAjax()){
//                //实例化模型类
//                $model = new BranchModel();
//                $point = new ControlPoint();
//                $param = input('post.');
//                //前台传过来要添加的控制点数组，包含工程划分树的节点id，所属工序号procedureid，控制点id
//                $selfid = $param["selfid"];//工程划分树的节点id
//                $procedureid = $param["procedureid"];//所属工序号，对应的是materialtrackingdivision表中的id
//
//                //需要的是一个二维数组
//                $control_data = $param["control_id"];
//
//                if(!empty($control_data))
//                {
//                    foreach ($control_data as $key=>$val)
//                    {
//                        //判断当前控制点是否存在数据库中
//                        $result = $model->getid($selfid,$procedureid,$val);
//                        if($result["id"])
//                        {
//                            unset($control_data[$key]);
//                        }
//                    }
//
//                    if(!empty($control_data))
//                    {
//                        foreach ($control_data as $k=>$v)
//                        {
//                            //根据控制点id查询fengning_controlpoint表中的信息
//                            $controlpoint_info = $point->getOne($v);
//                            if(!empty($controlpoint_info))
//                            {
//                                $data = [
//                                    "division_id" => $selfid,
//                                    "ma_division_id" => $procedureid,//工序号
//                                    "control_id" => $v,//控制点id
//                                ];
//                                $model->insertSu($data);
//                            }
//                        }
//                    }
//
//                    return ['code' => 1,'msg' => '添加成功'];
//                }
//                else
//                {
//                    return ['code' => -1,'msg' => ''];
//                }
//            }
//        }catch (PDOException $e){
//            return ['code' => -1,'msg' => $e->getMessage()];
//        }
//    }
    /**
     * 点击取消勾选后管控处不显示该控制点
     */
    public function checkBox()
    {
        if(request()->isAjax()) {
            //实例化模型类
            $model = new DivisionControlPointModel();
            $param = input('post.');
            $flag = $model->editRelation($param);
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
                "type" => 3//2表示单位工程，3表示分部工程，5表示单元工程
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
     */
    public function relationadd()
    {
        return $this->fetch();
    }

    /**
     * 添加关联收发文附件到分部管控、单位管控中的控制点文件上传文件表中
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
            if(!empty($send_info["attachment"]))
            {
                foreach($send_info["attachment"] as $key=>$val)
                {
                   $data = [
                       "contr_relation_id"=>$param["list_id"],
                       "attachment_id" =>$val["id"],
                       "type" => 3//2表示单位工程，3表示分部工程，5表示单元工程
                   ];
                    $model->insertTb($data);
                }
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
     */
    public function evaluation()
    {
        if(request()->isAjax()){
            //首先判断当前的登录人是否有验评权限，管理员和监理可以编辑

        }
    }
}