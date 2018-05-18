<?php
/**
 * Created by PhpStorm.
 * User: sir
 * Date: 2018/5/17
 * Time: 9:44
 */

namespace app\archive\controller;

use app\admin\controller\Permissions;
use app\admin\model\AdminGroup;
use app\admin\model\Attachment;
use app\quality\model\SendModel;
use think\Db;
use think\Session;

/**
 * 发文
 * Class Income
 * @package app\participants\controller
 */
class Send extends Permissions
{
    public function index()
    {
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
     * 发文 -- 新增或编辑
     * @return \think\response\Json
     * @author hutao
     */
    public function send()
    {
        if($this->request->isAjax()){
            // 前台需要传递的参数有:
            // file_name 文件名称 date 文件日期 income_id 收件人编号
            // relevance_id 关联收文 file_ids 上传的所有附件编号集合
            // status 1未发送 编辑好了只保存 不发送 (收件人看不到)    2(未处理(收件人显示),已发送(发件人显示))    3已签收4已拒收
            // 当编辑收发文时,传递 主键编号 major_key

            $param = input('param.');
            // 验证规则
            $rule = [
                ['file_name', 'require', '请填写文件名称'],
                ['date', 'require', '请选择文件日期'],
                ['income_id', 'require|number', '请选择收件人|收件人编号只能是数字'],
                ['relevance_id', 'require|number', '请选择关联收文|关联收文编号只能是数字'],
                ['file_ids', 'require|alphaDash', '请上传附件|附件编码只能是字母、数字、下划线 _和破折号 - 的组合'],
                ['status', 'require|between:1,4', '请传递文件状态|文件状态不能大于4']
            ];
            $validate = new \think\Validate($rule);
            //验证部分数据合法性
            if (!$validate->check($param)) {
                return json(['code' => -1,'msg' => $validate->getError()]);
            }

            // 系统自动生成数据
            $param['send_id'] = Session::has('admin') ? Session::get('admin') : 0; // 发件人编号
            $files = explode('',$param['file_ids']);
            $param['attchment_id'] = empty($files) ? $param['file_ids'] : $files[0]; // 文件编号用来关联查询

            $send = new SendModel();
            $major_key = isset($param['major_key']) ? $param['major_key'] : 0;
            if(empty($major_key)){
                $flag = $send->insertTb($param);
            }else{
                $flag = $send->editTb($param);
            }
            return json($flag);
        }
    }


    /**
     * 发文 -- 查看
     * @return \think\response\Json
     * @author hutao
     */
    public function preview()
    {
        if($this->request->isAjax()){
            // 前台需要传递的参数有:  主键编号 major_key
            // 查看就PDF、Word、图片这三种，其他的都不显示查看
            // 前台可以根据我返回的文件后缀来判断是否显示  查看功能

            $param = input('param.');
            // 验证规则
            $rule = [
                ['major_key', 'require', '请选择文件']
            ];
            $validate = new \think\Validate($rule);
            //验证部分数据合法性
            if (!$validate->check($param)) {
                return json(['code' => -1,'msg' => $validate->getError()]);
            }
            $send = new SendModel();
            $flag = $send->getOne($param['major_key'],2);
            return json($flag);
        }
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
        $file_obj = Db::name('attachment')->where('id',$file_id)->field('filename,filepath')->find();
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
     * 附件 -- 查看
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author hutao
     */
    public function attachmentPreview()
    {
        if($this->request->isAjax()){
            // 前台需要传递的参数有:  附件编号 file_id
            // 查看就PDF、Word、图片这三种，其他的都不显示查看
            // 前台可以根据我返回的文件后缀来判断是否显示  查看功能

            $file_id = input('file_id');
            if(empty($file_id)){
                return json(['code' => '-1','msg' => '编号为空']);
            }
            $code = 1;
            $msg = '预览成功';
            $data = Db::name('attachment')->where('id',$file_id)->field('filename,filepath')->find();
            $path = $data['filepath'];
            if(!file_exists('.' .$path)){
                return json(['code' => '-1','msg' => '文件不存在']);
            }
            $extension = strtolower(get_extension(substr($path,1)));
            $pdf_path = './uploads/temp/' . basename($path) . '.pdf';
            $ext_arr = ['pdf','pcx','emf','gif','bmp','tga','jpg','tif','jpeg','png','rle'];
            if(!file_exists($pdf_path)){
                if($extension === 'doc' || $extension === 'docx' || $extension === 'txt'){
                    doc_to_pdf($path);
                }else if($extension === 'xls' || $extension === 'xlsx'){
                    excel_to_pdf($path);
                }else if($extension === 'ppt' || $extension === 'pptx'){
                    ppt_to_pdf($path);
                }else if(in_array($extension,$ext_arr)){
                    $pdf_path = $path;
                }else{
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