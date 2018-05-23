<?php
/**
 * Created by PhpStorm.
 * User: sir
 * Date: 2018/5/22
 * Time: 11:57
 */

namespace app\modelmanagement\controller;


use app\admin\controller\Permissions;
use app\modelmanagement\model\VersionsModel;
use think\Db;
use think\Session;

/**
 * 模型版本管理
 * Class Versions
 * @package app\modelmanagement\controller
 * @author hutao
 */
class Versions extends Permissions
{
    public function index()
    {
       if($this->request->isAjax()){
           //  资源包主键
           $major_key = input('major_key');
           if(empty($major_key)){
               return json(['code'=>-1,'msg'=>'缺少资源包主键']);
           }
           $configure = new VersionsModel();
           $data = $configure->getOne($major_key);
           return json(['code'=>1,'data'=>$data,'msg'=>'模型版本']);
       }
        return $this->fetch();
    }

    /**
     * 查看模型
     * @return mixed
     * @author hutao
     */
    public function viewModel()
    {
        return $this->fetch('viewmodel');
    }

    /**
     * 压缩包上传
     * @author hutao
     */
    public function upload()
    {
        // model_type 1 竣工模型 2 施工模型
        $model_type = input('model_type');
        if(empty($model_type)){
            return json(['code'=>1,'msg'=>'缺少类型']);
        }
        if($this->request->file('file')){
            $file = $this->request->file('file');
        }else{
            return json(['code'=>1,'msg'=>'没有上传文件']);
        }
        $web_config = Db::name('admin_webconfig')->where('web','web')->find();

        //TODO 先测试E盘能否上传成功，成功后 修改为 G盘

        $path = 'E:\WebServer\Resources\jungong'; //文件路径
        if($model_type == 2){
            $path = 'E:\WebServer\Resources\shigong';
        }
        if(!is_dir($path)){
            mkdir($path, 0777, true);
        }
        $info = $file->validate(['size'=>$web_config['file_size']*1024,'ext'=>$web_config['file_type']])->rule('date')->move($path);
        if($info) {
            //写入到附件表
            $data = [];
            $data['module'] = 'modelmanagement';
            $data['use'] = 'versions';
            $data['name'] = $info->getInfo('name');//原文件名
            $data['filename'] = $info->getFilename();//文件名
            $data['filepath'] = $path . DS . $info->getSaveName();//文件路径
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
            $res['id'] = Db::name('attachment')->insertGetId($data);
            $res['src'] = $path . DS . $info->getSaveName();
            $res['code'] = 2;
            return json($res);
        } else {
            // 上传失败获取错误信息
            $msg = '上传失败：'.$file->getError();
            return json(['code'=>'-1','msg'=>$msg]);
        }
    }

    /**
     * 新增
     * @return \think\response\Json
     * @author hutao
     */
    public function add()
    {
        if($this->request->isAjax()){
            // 前台需要传递的参数有:
            // model_type 1 竣工模型 2 施工模型  resource_name 资源包名称 remake 模型备注 attachment_id 文件编号

            // 当编辑时,要多传递 主键编号 major_key
            $param = input('param.');
            // 验证规则
            $rule = [
                ['model_type', 'require', '缺少类型参数'],
                ['resource_name', 'require', '缺少资源包名称'],
                ['attachment_id', 'require', '缺少资源包编号']
            ];
            $validate = new \think\Validate($rule);
            //验证部分数据合法性
            if (!$validate->check($param)) {
                return json(['code' => -1,'msg' => $validate->getError()]);
            }

            $send = new VersionsModel();
            $major_key = isset($param['major_key']) ? $param['major_key'] : 0;

            // 系统自动生成参数
            //  resource_path 资源路径 version_number 版本 version_date 版本日期
            $param['resource_path'] = Db::name('attachment')->where(['id'=>$param['attachment_id']])->value('filepath');
            $version_number = $send->versionNumber();
            $param['version_number'] = $version_number;
            $param['version_date'] = date('Y-m-d H:i:s');

            if(empty($major_key)){
                $flag = $send->insertTb($param);
            }else{
                $param['id'] = $major_key;
                $flag = $send->editTb($param);
            }
            return json($flag);
        }
    }

    /**
     * 启用或禁用
     * @return \think\response\Json
     * @author hutao
     */
    public function enabledORDisable()
    {
        if($this->request->isAjax()){
            // 前台需要传递的参数有:
            // 主键编号 major_key 和 状态 status 0禁用1启用
            $param = input('param.');
            // 验证规则
            $rule = [
                ['major_key', 'require', '缺少主键编号'],
                ['status', 'require', '缺少资源状态']
            ];
            $validate = new \think\Validate($rule);
            //验证部分数据合法性
            if (!$validate->check($param)) {
                return json(['code' => -1,'msg' => $validate->getError()]);
            }

            //TODO 当启用时，要判断是否已经存在过关联关系
            //TODO 如果不存在就 把上一个版本的关联关系都复制一份，继承过来，然后禁用上一个版本状态，启用当前版本
            //TODO 如果存在就   直接禁用上一个版本状态，启用当前版本

            $send = new VersionsModel();
            $param['id'] = $param['major_key'];
            $flag = $send->editTb($param);
            return json($flag);
        }
    }


    /**
     * 删除
     * @return \think\response\Json
     * @author hutao
     */
    public function del()
    {
        if($this->request->isAjax()){
            // 前台需要传递的参数有:
            // 主键编号 major_key
            $param = input('param.');
            if(!isset($param['major_key'])){
                return json(['code'=>-1,'msg'=>'缺少主键编号']);
            }
            $send = new VersionsModel();
            $flag = $send->deleteTb($param['major_key']);
            return json($flag);
        }
    }

}