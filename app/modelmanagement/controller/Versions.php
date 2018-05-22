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
 * Class Configure
 * @package app\modelmanagement\controller
 * @author hutao
 */
class Versions extends Permissions
{
    public function index()
    {
       if($this->request->isAjax()){
           // -- model_type 1 竣工模型 2 施工模型
           $model_type = input('model_type');
           if(empty($model_type)){
               return json(['code'=>-1,'msg'=>'缺少类型参数']);
           }
           $configure = new VersionsModel();
           $data = $configure->getVersions($model_type);
           return json(['code'=>1,'data'=>$data,'msg'=>'模型版本']);
       }
        return $this->fetch();
    }

    /**
     * 压缩包上传
     * @author hutao
     */
    public function upload()
    {
        $module = '';$use = '';
        if($this->request->file('file')){
            $file = $this->request->file('file');
        }else{
            return json(['code'=>1,'msg'=>'没有上传文件']);
        }
        $module = $this->request->has('module') ? $this->request->param('module') : $module;//模块
        $web_config = Db::name('admin_webconfig')->where('web','web')->find();
        $info = $file->validate(['size'=>$web_config['file_size']*1024,'ext'=>$web_config['file_type']])->rule('date')->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . $module . DS . $use);
        if($info) {
            //写入到附件表
            $data = [];
            $data['module'] = $module;
            $data['name'] = $info->getInfo('name');//原文件名
            $data['filename'] = $info->getFilename();//文件名
            $data['filepath'] = DS . 'uploads' . DS . $module . DS . $use . DS . $info->getSaveName();//文件路径
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
            $data['use'] = $this->request->has('use') ? $this->request->param('use') : $use;//用处
            $res['id'] = Db::name('attachment')->insertGetId($data);
            $res['src'] = DS . 'uploads' . DS . $module . DS . $use . DS . $info->getSaveName();
            $res['code'] = 2;
            return json($res);
        } else {
            // 上传失败获取错误信息
            return $this->error('上传失败：'.$file->getError());
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
            // model_type 1 竣工模型 2 施工模型  resource_name 资源包名称  resource_path 资源路径 version_number 版本 version_date 版本日期 remake 模型备注
            // 当编辑时,要多传递 主键编号 major_key
            $param = input('param.');
            // 验证规则
            $rule = [
                ['model_type', 'require', '缺少类型参数'],
                ['resource_name', 'require', '缺少资源包名称'],
                ['resource_path', 'require', '缺少资源路径'],
                ['version_number', 'require', '请填写版本'],
                ['version_date', 'require', '请填写版本日期']
            ];
            $validate = new \think\Validate($rule);
            //验证部分数据合法性
            if (!$validate->check($param)) {
                return json(['code' => -1,'msg' => $validate->getError()]);
            }

            $send = new VersionsModel();
            $major_key = isset($param['major_key']) ? $param['major_key'] : 0;

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