<?php
/**
 * Created by PhpStorm.
 * User: sir
 * Date: 2018/5/17
 * Time: 9:44
 */

namespace app\api\controller;

use app\admin\controller\Permissions;
use app\admin\model\AdminGroup;
use app\admin\model\Attachment;
use app\quality\model\SendModel;
use app\admin\model\MessageremindingModel;//消息记录
use think\Db;
use think\Session;
use app\admin\model\JpushModel;
vendor('JPush.autoload');

/**
 * 发文
 * Class Income
 * @package app\participants\controller
 */
class Send extends Login
{
    public function index()
    {
        $user_name = Session::has('current_nickname') ? Session::get('current_nickname') : 0;
        $this->assign('user_name',$user_name);
        return $this->fetch();
    }

    /**
     * 收文单位
     * @return \think\response\Json
     * @author hutao
     */
    public function incomeGroupType()
    {
        if($this->request->isAjax()){
            // 前台需要传递的参数有:传递 收件人主键编号 major_key
            $param = input('param.');
            $user = new AdminGroup();
            $unit_name = $user->incomeGroupType($param['major_key']);
            return json([''=>1,'unit_name'=>$unit_name,'msg'=>'收件人单位']);
        }
    }

    /**
     * 发文-签收或拒收
     * @return \think\response\Json
     * @author hutao
     */
    public function editSend()
    {
//        if($this->request->isAjax()){
            // status 1未发送 编辑好了只保存 不发送 (收件人看不到)    2(未处理(收件人显示),已发送(发件人显示))    3已签收4已拒收
            // 当编辑收发文时,传递 主键编号 major_key,status状态值3已签收4已拒收
            $param = input('post.');

            $send = new SendModel();

            $major_key = isset($param['major_key']) ? $param['major_key'] : 0;

            if(!empty($major_key)){

                $param['id'] = $major_key;

                $flag = $send->editTb($param);

                $uint_id = $major_key;

                $type = 1;//1为收发文，2为单元管控
                //获取当前的登录人的id
                $admin_id = Session::has('admin') ? Session::get('admin') : 0;
                //实例化模型类
                $message = new MessageremindingModel();

                $message_info = $message->getOne(["uint_id" => $uint_id, "current_approver_id" => $admin_id, "type" => $type]);

                if ($message_info["status"] == 1) {
                    $data = [
                        "id" => $message_info["id"],
                        "status" => 2
                    ];
                    $flag = $message->editTb($data);
                }
            }
            return json($flag);
//        }
    }

    /**
     * 发文或收文 -- 查看
     * @return \think\response\Json
     * @author hutao
     */
    public function preview()
    {
//        if($this->request->isAjax()){
            // 前台需要传递的参数有:  主键编号 major_key 文件类型 see_type 1 收文 2 发文
            // 查看就PDF、Word、图片这三种，其他的都不显示查看
            // 前台可以根据我返回的文件后缀来判断是否显示  查看功能

            $param = input('param.');
            // 验证规则
            $rule = [
                ['major_key', 'require', '请选择文件'],
                ['see_type', 'require', '请选择文件类型']
            ];
            $validate = new \think\Validate($rule);
            //验证部分数据合法性
            if (!$validate->check($param)) {
                return json(['code' => -1,'msg' => $validate->getError()]);
            }
            $send = new SendModel();
            $flag = $send->getOne($param['major_key'],$param['see_type']);

            foreach($flag["attachment"] as $key=>$val)
            {

                    $upload_info = Db::name('attachment')->where('id',$val["id"])->field('filepath')->find();

                    $flag["attachment"][$key]["url"] = $_SERVER['HTTP_HOST'].$upload_info["filepath"];
            }

            return json($flag);
//        }
    }

    /**
     * 发文 -- 删除
     * @return \think\response\Json
     * @author hutao
     */
    public function del()
    {
        if($this->request->isAjax()){
            // 前台需要传递的参数有:  主键编号 major_key

            $param = input('param.');
            // 验证规则
            $rule = [
                ['major_key', 'require', '请选择需要删除的文件']
            ];
            $validate = new \think\Validate($rule);
            //验证部分数据合法性
            if (!$validate->check($param)) {
                return json(['code' => -1,'msg' => $validate->getError()]);
            }

            $send = new SendModel();
            $flag = $send->deleteTb($param['major_key']);
            return json($flag);
        }
    }


    /**
     * 附件 -- 下载
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author hutao
     */
    public function fileDownload()
    {
        // 前台需要 传递 附件编号 file_id
        $file_id = input('file_id');
        if(empty($file_id)){
            return json(['code' => '-1','msg' => '编号为空']);
        }
        $file_obj = Db::name('attachment')->where('id',$file_id)->field('name,filepath')->find();
        $filePath = '';
        if(!empty($file_obj['filepath'])){
            $filePath = '.' . $file_obj['filepath'];
        }
        if(!file_exists($filePath)){
            return json(['code' => '-1','msg' => '文件不存在']);
        }else{
            $fileName = $file_obj['name'];
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
     * 附件 -- 查看
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author hutao
     */
    public function attachmentPreview()
    {
//        if($this->request->isAjax()){
            // 前台需要传递的参数有:  附件编号 file_id
            // 查看就PDF、Word、图片这三种，其他的都不显示查看
            // 前台可以根据我返回的文件后缀来判断是否显示  查看功能

            $file_id = input('file_id');
            if(empty($file_id) || $file_id == 'undefined'){
                return json(['code' => '-1','msg' => '编号为空']);
            }
            $code = 1;
            $msg = '预览成功';
            $data = Db::name('attachment')->where('id',$file_id)->field('filename,filepath')->find();
            $path = '.'.$data['filepath'];
            if(!file_exists($path)){
                return json(['code' => '-1','msg' => '文件不存在']);
            }
            $extension = strtolower(get_extension(substr($path,1)));
            $pdf_path = './uploads/temp/' . basename($path) . '.pdf';
            $ext_arr = ['pdf','pcx','emf','gif','bmp','tga','jpg','tif','jpeg','png','rle'];
            if(!file_exists($pdf_path)){
                if($extension == 'doc' || $extension == 'docx' || $extension == 'txt'){
                    doc_to_pdf($path);
                }else if($extension == 'xls' || $extension == 'xlsx'){
                    excel_to_pdf($path);
                }else if($extension == 'ppt' || $extension == 'pptx'){
                    ppt_to_pdf($path);
                }else if(in_array($extension,$ext_arr)){
                    $pdf_path = $path;
                }else{
                    $code = 0;
                    $msg = '不支持的文件格式';
                }
                $pdf_path = $_SERVER['HTTP_HOST'].substr($pdf_path,1);
                return json(['code' => $code, 'path' =>$pdf_path, 'msg' => $msg]);
            }else{
                $pdf_path = $_SERVER['HTTP_HOST'].substr($pdf_path,1);
                return json(['code' => $code, 'path' => $pdf_path, 'msg' => $msg]);
            }
//        }
    }

    /**
     * 附件 -- 删除
     * @return \think\response\Json
     * @author hutao
     */
    public function attachmentDel()
    {
        if($this->request->isAjax()){
            // 前台需要传递的参数有:  附件编号 file_id
            $file_id = input('file_id');
            if(empty($file_id)){
                return json(['code' => '-1','msg' => '编号为空']);
            }
            $send = new Attachment();
            $flag = $send->deleteTb($file_id);
            return json($flag);
        }
    }


}