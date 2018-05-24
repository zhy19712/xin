<?php
/**
 * Created by PhpStorm.
 * User: sir
 * Date: 2018/5/22
 * Time: 11:57
 */

namespace app\modelmanagement\controller;


use app\admin\controller\Permissions;
use app\modelmanagement\model\QualitymassModel;
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
        $file_name = input('file_name');
        if(empty($file_name)){
            return json(['code'=>1,'msg'=>'缺少原文件名称']);
        }

        // 资源包名称不能重复
        $is_exist = Db::name('attachment')->where('name',$file_name)->value('id');
        if($is_exist){
            return json(['code'=>'-1','msg'=>'资源包名称不能重复']);
        }

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
            $resource_path = Db::name('attachment')->where(['id'=>$param['attachment_id']])->value('filepath');

            //TODO 先测试E盘能否成功，成功后 修改为 G盘

            $path_arr = explode('E:\WebServer',$resource_path);
            $param['resource_path'] = $path_arr[1];
            $version_number = $send->versionNumber($param['model_type']);
            $param['version_number'] = $version_number;
            $param['version_date'] = date('Y-m-d H:i:s');

            if(empty($major_key)){

                /**
                 * 1 竣工模型 -- 全景3D模型 操作的表是 model_complete
                 * 当新增时，首先解压缩，存在2个txt文件
                 * 读取txt1 文件 里面有3个值 [第一个参数没用]  [组名-id] [模型编号]
                 *
                 * 例如:
                 *     [Test] [N-1] [9]
                 *     [Test] [组九-2] [10]
                 *     存入数据的时候只读取 组九 作为组名 10 作为模型编号
                 *
                 * 注意 -- 如果 组名是 N 那么代表他自己没有组,每一个N 都在 后台随机生成一个组 例如: N1 N2 N3...
                 *
                 * 读取txt2 文件 里面也是有3个值 [组 组的属性名 组的属性值]
                 *
                 * 例如:
                 *      组一 组一的属性名1 组一的属性值1
                 *      组一 组一的属性名2 组一的属性值2
                 *      组一 组一的属性名3 组一的属性值3
                 * 存入数据的时候只读取 组九 作为组名 10 作为模型编号
                 *
                 * 2 施工模型 -- 质量模型 操作的表是 model_quality
                 * 压缩包里面只有一个txt文件
                 *
                 * [没用数据] [标段-单位-分部-单元+桩号1+桩号1+桩号2+桩号2+桩号3+桩号3+桩号4+桩号4+高程起+高程止]      [模型编号]
                 * [1]      [C3-DXCF-ZCF-SFDW-CX+0009.100-CX+0011.100-CZ+0377.000-CZ+0378.000-EL+0995.500-EL+1004.161]    [0]
                 */
                //TODO 当新增时，首先解压缩，读取txt文件 插入数据


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
            // 主键编号 major_key
            $param = input('param.');
            // 验证规则
            $rule = [
                ['major_key', 'require', '缺少主键编号']
            ];
            $validate = new \think\Validate($rule);
            //验证部分数据合法性
            if (!$validate->check($param)) {
                return json(['code' => -1,'msg' => $validate->getError()]);
            }

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
            $id = isset($param['major_key']) ? $param['major_key'] : 0;
            if(empty($id)){
                return json(['code'=>-1,'msg'=>'缺少主键编号']);
            }

            $send = new VersionsModel();
            // 如果当前版本是唯一的,提示不能删除
            $flag = $send->isOnly($id);
            if($flag['code'] == -1){
                return json($flag);
            }

            //TODO  1 竣工模型 如果存在关联关系就 把关联关系全部删除

            // 施工模型 删除此版本号 下 所有的关联关系
            $quality = new QualitymassModel();
            $flag = $quality->removeVersionsRelevance($flag['version_number']);
            if($flag['code'] == -1){
                return json($flag);
            }

            // 最后删除版本记录和资源包文件
            //TODO 注意删除里的E盘 修改为 G盘
            $flag = $send->deleteTb($param['major_key']);
            return json($flag);
        }
    }


    /**
     * 此方法只是 读取 竣工模型 -- 全景3D模型 txt文件时使用
     * 获取txt文件内容并插入到数据库中
     * @param $filePath
     * @param $filePath2
     * @return \think\response\Json
     * @author hutao
     */
    public function completeInsertTxtContent($filePath,$filePath2)
    {
        if(!file_exists($filePath)){
            return json(['code' => '-1','msg' => '文件不存在']);
        }
        $files = fopen($filePath, "r") or die("Unable to open file!");
        $contents = $new_contents = $new_ids = [];
        while(!feof($files)) {
            $txt = fgets($files);
            $txt = str_replace('[','',$txt);
            $txt = str_replace(']','',$txt);
            $txt = str_replace("\r\n",'',$txt);
            $txt_arr = explode(' ',$txt);
            $contents[] = $txt_arr;
        }

        $data = [];
        $i=0;
        foreach ($contents as $item){
            foreach ($item as $k=>$v){
                if($k==1){
                    $data[$i]['model_name'] = $v;
                    $new_contents[$i] = explode('-',$v);
                }else if ($k==2){
                    array_push($new_contents[$i],$v);
                }
            }
            $i++;
        }

        $send = new VersionsModel();
        $version_number = $send->versionNumber(1); // 1全景3D模型 2质量3D模型

        foreach ($new_contents as $k=>$val){
            $data[$k]['version_number'] = $version_number;
            $data[$k]['section'] = $val[0];
            $data[$k]['unit'] = trim(next($val));
            $data[$k]['parcel'] = trim(next($val));
            $data[$k]['cell'] = trim(next($val));
            $arr_1 = explode('+',trim(next($val)));
            $data[$k]['pile_number_1'] = $arr_1[0];
            $data[$k]['pile_val_1'] = $arr_1[1];
            $arr_2 = explode('+',trim(next($val)));
            $data[$k]['pile_number_2'] = $arr_2[0];
            $data[$k]['pile_val_2'] = $arr_2[1];
            $arr_3 = explode('+',trim(next($val)));
            $data[$k]['pile_number_3'] = $arr_3[0];
            $data[$k]['pile_val_3'] = $arr_3[1];
            $arr_4 = explode('+',trim(next($val)));
            $data[$k]['pile_number_4'] = $arr_4[0];
            $data[$k]['pile_val_4'] = $arr_4[1];
            $arr_5 = explode('+',trim(next($val)));
            $data[$k]['el_start'] = $arr_5[1];
            $arr_6 = explode('+',trim(next($val)));
            $data[$k]['el_cease'] = $arr_6[1];
            $data[$k]['model_id'] = trim(next($val));
        }

        //TODO 复制上一个版本的关联关系

        $picture = new QualitymassModel();
        $picture->insertAll($data);
        fclose($files);
    }


    /**
     * 此方法只是 读取施工模型 质量模型 txt文件时使用
     * 获取txt文件内容并插入到数据库中
     * @param $filePath
     * @return \think\response\Json
     * @author hutao
     */
    public function qualityInsertTxtContent($filePath)
    {
        if(!file_exists($filePath)){
            return json(['code' => '-1','msg' => '文件不存在']);
        }
        $files = fopen($filePath, "r") or die("Unable to open file!");
        $contents = $new_contents = $new_ids = [];
        while(!feof($files)) {
            $txt = fgets($files);
            $txt = str_replace('[','',$txt);
            $txt = str_replace(']','',$txt);
            $txt = str_replace("\r\n",'',$txt);
            $txt_arr = explode(' ',$txt);
            $contents[] = $txt_arr;
        }

        $data = [];
        $i=0;
        foreach ($contents as $item){
            foreach ($item as $k=>$v){
                if($k==1){
                    $data[$i]['model_name'] = $v;
                    $new_contents[$i] = explode('-',$v);
                }else if ($k==2){
                    array_push($new_contents[$i],$v);
                }
            }
            $i++;
        }

        $version = new VersionsModel();
        $version_number = $version->versionNumber(2); // 1全景3D模型 2质量3D模型

        foreach ($new_contents as $k=>$val){
            $data[$k]['version_number'] = $version_number;
            $data[$k]['section'] = $val[0];
            $data[$k]['unit'] = trim(next($val));
            $data[$k]['parcel'] = trim(next($val));
            $data[$k]['cell'] = trim(next($val));
            $arr_1 = explode('+',trim(next($val)));
            $data[$k]['pile_number_1'] = $arr_1[0];
            $data[$k]['pile_val_1'] = $arr_1[1];
            $arr_2 = explode('+',trim(next($val)));
            $data[$k]['pile_number_2'] = $arr_2[0];
            $data[$k]['pile_val_2'] = $arr_2[1];
            $arr_3 = explode('+',trim(next($val)));
            $data[$k]['pile_number_3'] = $arr_3[0];
            $data[$k]['pile_val_3'] = $arr_3[1];
            $arr_4 = explode('+',trim(next($val)));
            $data[$k]['pile_number_4'] = $arr_4[0];
            $data[$k]['pile_val_4'] = $arr_4[1];
            $arr_5 = explode('+',trim(next($val)));
            $data[$k]['el_start'] = $arr_5[1];
            $arr_6 = explode('+',trim(next($val)));
            $data[$k]['el_cease'] = $arr_6[1];
            $data[$k]['model_id'] = trim(next($val));
        }

        // 继承当前版本的上一个版本的关联关系
        $previous_version_number = $version->prevVersionNumber($version_number); // 当前版本的上一个版本号
        if(!empty($previous_version_number)){
            $quality = new QualitymassModel();
            $prev_data = $quality->prevRelevance($previous_version_number);
            foreach ($prev_data as $prev_v){
                foreach ($data as $e_k=>$every_v){
                    if($every_v['model_name'] == $prev_v['model_name']){
                        $data[$e_k]['unit_id'] = $prev_v['unit_id'];
                    }
                }
            }
        }

        $picture = new QualitymassModel();
        $picture->insertAll($data);
        fclose($files);
    }

}