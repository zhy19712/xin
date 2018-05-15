<?php
/**
 * Created by PhpStorm.
 * User: sir
 * Date: 2018/4/11
 * Time: 14:17
 */

namespace app\quality\controller;


use app\admin\controller\Permissions;
use app\quality\model\DivisionModel;
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
     * 获取节点工序
     * @return \think\response\Json
     * @author hutao
     */
    public function productionProcesses()
    {
        $data = Db::name('norm_materialtrackingdivision')->where(['type'=>3,'cat'=>2])->column('id,name');
        $data[0] = '作业';
        return json(['code' => 1,'data' => $data,'msg' => '工序']);
    }

    /**
     * 批量下载 二维码
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author hutao
     */
    public function exportCode()
    {
        // 前台 传递 要下载 哪个节点 下的所有 二维码 add_id
        $id = $this->request->has('file_id') ? $this->request->param('file_id', 0, 'intval') : 1;
        if($id == 0){
            return json(['code' => '-1','msg' => '请选择工程划分节点']);
        }
        // 获取 工程划分 下 所有的 控制点
        $control = Db::name('quality_division_controlpoint_relation')->alias('d')
            ->join('norm_materialtrackingdivision m','d.ma_division_id = m.id','left')
            ->join('norm_controlpoint c','d.control_id = c.id','left')
            ->where(['d.division_id'=>$id,'d.type'=>0])
            ->field('d.control_id,m.name as m_name,c.name as c_name')->select();
            // ->column('d.control_id,m.name as m_name,c.name as c_name'); // 使用column 会合并相同的数据
        if(empty($control)){
            return json(['code' => '-1','msg' => '没有数据']);
        }
        if($this->request->isAjax()) {
            return json(['code' => 1,'msg'=>'导出成功']); // 文件存在，告诉前台可以执行下载
        }else{
            $attachment_id = [];
            $qrcode_bas_path = ROOT_PATH . 'public' .DS . 'uploads' . DS . 'quality' . DS . 'export-code';//文件路径
            if(!is_dir($qrcode_bas_path)){
                mkdir($qrcode_bas_path, 0777, true);
            }
            foreach ($control as $k=>$v){
                $data['module'] = 'quality';
                // 图片名称 是 工序名称-控制点编号-控制点名称.png
                $data['filename'] = $v['m_name'] . '-' . $v['control_id'] . '-' . $v['c_name'] . '.png';//文件名
                $data['filepath'] = DS . 'uploads' . DS . 'quality' . DS . 'export-code' . DS . $data['filename'];//文件路径
                // 生成二维码图片
                $png_name = iconv("utf-8","gb2312",$data['filename']); // 图片名称
                $png_path = $qrcode_bas_path . DS . $png_name;
                Loader::import('phpqrcode\phpqrcode', EXTEND_PATH);
                $text = url('quality/Unitqualitymanage/fileDownload/'.$v['control_id']);
                if(!file_exists($png_path)) {
                    \QRcode::png($text,$png_path,'L',5,2);
                }
                $data['fileext'] = 'png';//文件后缀
                $data['filesize'] = '';//无法获取文件大小
                $data['create_time'] = time();//时间
                $data['uploadip'] = $this->request->ip();//IP
                $data['user_id'] = Session::has('admin') ? Session::get('admin') : 0;
                if($data['module'] == 'admin') {
                    //通过后台上传的文件直接审核通过
                    $data['status'] = 1;
                    $data['admin_id'] = $data['user_id'];
                    $data['audit_time'] = time();
                }
                $data['use'] = 'exportCode';//用处
                // 首先判断是否已经 生成过 二维码图片
                $insert_id = Db::name('attachment')->where(['module'=>$data['module'],'filename'=>$data['filename']])->value('id');
                if(empty($insert_id) && file_exists($png_path)){
                    $insert_id = Db::name('attachment')->insertGetId($data);
                }
                $attachment_id[] = $insert_id;
            }
            $datalist = Db::name("attachment")->whereIn("id", $attachment_id)->column('filepath');
            $zip = new \ZipArchive;
            // 压缩文件名
            $d_name = Db::name('quality_division')->where('id',$id)->value('d_name');
            $new_png_name = iconv("utf-8", "GB2312//IGNORE", $d_name);
            $zipName = ROOT_PATH . 'public' .DS .'uploads/quality/export-code/'.$new_png_name.'.zip';
            //新建zip压缩包
            if ($zip->open($zipName, \ZIPARCHIVE::CREATE) === TRUE) {
                foreach($datalist as $val){
                    $new_val = '.' . iconv("utf-8", "GB2312//IGNORE", $val);
                    if(file_exists($new_val)){
                        //addFile函数首个参数如果带有路径，则压缩的文件里包含的是带有路径的文件压缩
                        //若不希望带有路径，则需要该函数的第二个参数
                        $zip->addFile($new_val, basename($new_val));//第二个参数是放在压缩包中的文件名称，如果文件可能会有重复，就需要注意一下
                    }
                }
            }

            //打包zip
            $zip->close();
            if(!file_exists($zipName)){
                exit("无法找到文件"); //即使创建，仍有可能失败
            }
            //如果不要下载，下面这段删掉即可，如需返回压缩包下载链接，只需 return $zipName;
            header("Cache-Control: public");
            header("Content-Description: File Transfer");
            header('Content-disposition: attachment; filename='.$new_png_name.'.zip'); //文件名
            header("Content-Type: application/zip"); //zip格式的
            header("Content-Transfer-Encoding: binary"); //告诉浏览器，这是二进制文件
            header('Content-Length: '. filesize($zipName)); //告诉浏览器，文件大小
            @readfile($zipName);
            //最后删除指定改的下载包，防止文件重复
            unlink($zipName);
        }
    }


    /**
     * 下载 控制点里 的模板文件
     * @return \think\response\Json
     * @throws \PhpOffice\PhpWord\Exception\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author hutao
     */
    public function fileDownload()
    {
        // 前台需要 传递 文件编号 id
        $param = input('param.');
        $file_id = isset($param['file_id']) ? $param['file_id'] : 0;
        if($file_id == 0){
            return json(['code' => '-1','msg' => '编号有误']);
        }
        $file_obj = Db::name('quality_division_controlpoint_relation')->alias('r')
            ->join('norm_controlpoint c','c.id=r.control_id','left')
            ->where('r.id',$file_id)->field('c.code,c.name,r.division_id')->find();
        if(empty($file_obj)){
            return json(['code' => '-1','msg' => '编号无效']);
        }
        $new_name = iconv("utf-8","gb2312",$file_obj['code'].$file_obj['name']);
        $filePath = ROOT_PATH . 'public' . DS . 'Data' . DS . 'form' . DS . 'quality' . DS . $new_name . '.docx';
        if(!file_exists($filePath)){
            return json(['code' => '-1','msg' => '文件不存在']);
        }else if(request()->isAjax()){
            return json(['code' => 1,'msg'=>'下载成功']); // 文件存在，告诉前台可以执行下载
        }else{
            //设置临时文件，避免C盘Temp不可写报错
            Settings::setTempDir('temp');
            $phpword = new PhpWord();
            $phpword = $phpword->loadTemplate($filePath);
            $qualityFormInfoService = new QualityFormInfoModel();
            $infos = $qualityFormInfoService->getFormBaseInfo($file_obj['division_id']);
            halt($infos);
            foreach ($infos as $key => $value) {
                $phpword->setValue('{' . $key . '}', $value);
            }
            $docname = $phpword->save();
            header('Content-Disposition: attachment; filename="' . $file_obj['code'].$file_obj['name'] . '.docx"');
            header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
            header('Content-Transfer-Encoding: binary');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Expires: 0');
            $file = fopen($docname, 'r');
            echo fread($file, filesize($docname));
            fclose($file);
            exit;
        }
    }

    /**
     * 打印文件
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author hutao
     */
    public function printDocument()
    {
        // 前台需要 传递 文件编号 id
        $param = input('param.');
        $file_id = isset($param['id']) ? $param['id'] : 0;
        if($file_id == 0){
            return json(['code' => '-1','msg' => '编号有误']);
        }
        $file_obj = Db::name('quality_division_controlpoint_relation')->alias('r')
            ->join('norm_controlpoint c','c.id=r.control_id','left')
            ->where('r.id',$file_id)->field('c.code,c.name')->find();
        if(empty($file_obj)){
            return json(['code' => '-1','msg' => '编号无效']);
        }
        $new_name = iconv("utf-8","gb2312",$file_obj['code'].$file_obj['name']);
        $filePath = ROOT_PATH . 'public' . DS . 'Data' . DS . 'form' . DS . 'quality' . DS . $new_name . '.docx';
        if(!file_exists($filePath)){
            return json(['code' => '-1','msg' => '文件不存在']);
        }
        // 预览里有 打印
        if(request()->isAjax()) {
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

    /**
     * 删除控制点 注意: 已经执行 的控制点 不能删除
     *
     * 控制点 存在于 quality_division_controlpoint_relation 中
     * quality_division_controlpoint_relation 里包含 新增控制点时 关联添加的对应关系
     * 和 在 单位策划里 后来 新增控制点 时 追加的 对应关系
     *
     * 如果关系记录存在 该控制点 那么就应该先
     * 要关联 删除 记录里的控制点执行情况 和 图像资料  以及它们所包含的文件 以及 预览的pdf文件
     * 最后 删除 这条关系记录
     *
     * type 类型:1 检验批 0 工程划分
     * @return \think\response\Json
     * @throws \think\Exception
     * @author hutao
     */
    public function controlDel()
    {
        // 前台需要 传递 节点编号 add_id 工序编号 ma_division_id 控制点编号 id
        $param = input('param.');
        $add_id = isset($param['add_id']) ? $param['add_id'] : 0;
        $ma_division_id = isset($param['ma_division_id']) ? $param['ma_division_id'] : -1; // 工序作业编号是0
        $id = isset($param['id']) ? $param['id'] : -1; // id 等于0 表示 全部删除
        if(($add_id == 0) || ($ma_division_id == -1) || ($id == -1)){
            return json(['code' => '-1','msg' => '编号有误']);
        }
        if(request()->isAjax()) {
            $unit = new UnitqualitymanageModel();
            $flag = $unit->associationDeletion($add_id,$ma_division_id,$id);
            return json($flag);
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
     * 添加控制点
     * @return \think\response\Json
     * @author hutao
     */
    public function addControl()
    {
        // 前台需要 传递 节点编号 add_id 工序编号 ma_division_id
        $param = input('param.');
        $add_id = isset($param['add_id']) ? $param['add_id'] : 0;
        $ma_division_id = isset($param['ma_division_id']) ? $param['ma_division_id'] : 0; // 工序作业编号是0,但是作业没有添加方法
        $idArr = input('idArr/a');
        if(($add_id == 0) || ($ma_division_id == 0) || (empty($idArr))){
            return json(['code' => -1 ,'msg' => '请选择需要新增的控制点']);
        }
        if($this->request->isAjax()){
            $idArr = array_unique($idArr); // 移除数组里的重复控制点主键编号 (前台传递的有可能存在重复的)
            // 首先验证 此控制点 是否已经 添加过
            $old_existed = Db::name('quality_division_controlpoint_relation')->where(['division_id'=>$add_id,'ma_division_id'=>$ma_division_id])->column('control_id');
            $new_add= array_diff($idArr,$old_existed);
            if(empty($new_add)){
                return json(['code'=>1,'msg'=>'已经添加过']);
            }
            $data = [];
            foreach ($new_add as $k=>$v){
                $data[$k]['division_id'] = $add_id;
                $data[$k]['ma_division_id'] = $ma_division_id;
                $data[$k]['type'] = 0;
                $data[$k]['control_id'] = $v;
            }
            $unit = new UnitqualitymanageModel();
            $nodeStr = $unit->insertTb($data);
            return json($nodeStr);
        }
    }

    /**
     * 单位管控 控制点执行情况文件 或者 图像资料文件 上传保存
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author hutao
     */
    public function editRelation()
    {
        // 前台需要 传递 控制点编号 id 上传类型 type 1执行情况 2图像资料 上传的文件 file
        if($this->request->file('file')){
            $file = $this->request->file('file');
        }else{
            return json(['code'=>0,'msg'=>'没有上传文件']);
        }
        $web_config = Db::name('admin_webconfig')->where('web','web')->find();
        $info = $file->validate(['size'=>$web_config['file_size']*1024,'ext'=>$web_config['file_type']])->rule('date')->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . 'quality' . DS . 'Unitqualitymanage');
        if($info) {
            //写入到附件表
            $data = [];
            $data['module'] = 'quality';
            $data['name'] = $info->getInfo('name');//文件名
            $data['filename'] = $info->getFilename();//文件名
            $data['filepath'] = DS . 'uploads' . DS . 'quality' . DS . 'Unitqualitymanage' . DS . $info->getSaveName();//文件路径
            $data['fileext'] = $info->getExtension();//文件后缀
            $data['filesize'] = $info->getSize();//文件大小
            $data['create_time'] = time();//时间
            $data['uploadip'] = $this->request->ip();//IP
            $data['user_id'] = Session::has('admin') ? Session::get('admin') : 0;
            if($data['module'] == 'admin') {
                //通过后台上传的文件直接审核通过
                $data['status'] = 1;
                $data['admin_id'] = $data['user_id'];
                $data['audit_time'] = time();
            }
            $data['use'] = $this->request->has('use') ? $this->request->param('use') : 'Unitqualitymanage';//用处
            $res['id'] = Db::name('attachment')->insertGetId($data);
            $res['src'] = DS . 'uploads' . DS . 'quality' . DS . 'Unitqualitymanage' . DS . $info->getSaveName();
            $res['code'] = 2;


            // 执行上传文件 获取文件编号  attachment_id
            $param = input('param.');
            $param['attachment_id'] = $res['id'];
            // 保存上传文件记录
            $id = isset($param['contr_relation_id']) ? $param['contr_relation_id'] : 0;
            $type = isset($param['file_type']) ? $param['file_type'] : 0; // 1执行情况 2图像资料
            $attachment_id = isset($param['attachment_id']) ? $param['attachment_id'] : 0; // 文件编号
            if(($id == 0) || ($type == 0) || ($attachment_id == 0)){
                return json(['code' => '-1','msg' => '参数有误']);
            }
            $new_data['contr_relation_id'] = $id;
            $new_data['attachment_id'] = $attachment_id;
            $new_data['type'] = $type;
            $unit = new UnitqualitymanageModel();
            $nodeStr = $unit->saveTb($new_data);
            return json($nodeStr);
        }else {
            // 上传失败获取错误信息
            $msg = '上传失败：'.$file->getError();
            return json(['code'=>'-1','msg'=>$msg]);
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
            ->where('q.id',$id)->field('a.filename,a.filepath')->find();
        $filePath = '';
        if(!empty($file_obj['filepath'])){
            $filePath = '.' . $file_obj['filepath'];
        }
        if(!file_exists($filePath)){
            return json(['code' => '-1','msg' => '文件不存在']);
        }else if(request()->isAjax()){
            return json(['code' => 1]); // 文件存在，告诉前台可以执行下载
        }else{
            $fileName = $file_obj['filename'];
            $file = fopen($filePath, "r"); //   打开文件
            //输入文件标签
            $fileName = iconv("utf-8","gb2312",$fileName);
            Header("Content-type:application/octet-stream ");
            Header("Accept-Ranges:bytes ");
            Header("Accept-Length:   " . filesize($filePath));
            Header("Content-Disposition:   attachment;   filename= " . $fileName);
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
            $sd = new UnitqualitymanageModel();
            $flag = $sd->deleteTb($id);
            return json($flag);
        }
    }

}