<?php
/**
 * Created by PhpStorm.
 * User: sir
 * Date: 2018/3/30
 * Time: 17:17
 */

namespace app\standard\controller;

use app\admin\controller\Permissions;
use app\standard\model\NormModel;
use think\Db;

class Norm extends Permissions
{
    /**
     * 获取 标准库 左侧的树结构
     * @return mixed|\think\response\Json
     * @author hutao
     */
    public function index()
    {
        if(request()->isAjax()){
            if(request()->isAjax()){
                $node = new NormModel();
                $nodeStr = $node->getNodeInfo();
                return json($nodeStr);
            }
        }
        return $this->fetch();
    }

    /**
     * 新增或者编辑 标准文件
     * @return mixed|\think\response\Json
     * @author hutao
     */
    public function editNode()
    {
        if(request()->isAjax()){
            // 如果提交方式是 get 就是点击编辑获取一条数据 post 就是新增或者编辑后保存的操作
            $node = new NormModel();
            if($this->request->isGet()) {
                $info['data'] = $node->getOne(input('id'));
                $info['filename'] = Db::name('attachment')->where('id',$info['data']['file_id'])->column('filename');
                return json($info);
            }
            $param = input('param.');
            // 验证规则
            $validate = new \think\Validate([
                ['nodeId', 'require|number', '请选择标准分类|标准分类必须是数字'],
                ['standard_number', 'require|max:100|alphaDash', '标准编号不能为空|标准编号不能超过100个字符|标准编号只能是字母、数字、下划线 _ 和破折号 - 的组合'],
                ['standard_name', 'require|max:100', '标准名称不能为空|标准名称不能超过100个字符'],
                ['material_date', 'require|date', '实施日期不能为空|实施日期只能是日期格式'],
                ['alternate_standard', 'max:100|alphaDash', '替代标准不能超过100个字符|替代标准只能是字母、数字、下划线 _和破折号 - 的组合'],
                ['remark', 'max:100', '备注不能超过100个字符'],
                ['file_id', 'number', '文件编号必须是数字']
            ]);
            //验证部分数据合法性
            if (!$validate->check($param)) {
                return json(['code' => -1,'msg' => $validate->getError()]);
            }

            /**
             * 当新增 标准文件的时候
             * 前台需要传递的是
             * 必须参数 : nodeId 标准分类(就是选中的节点的编号) standard_number 标准编号,standard_name 标准名称,material_date 实施日期
             * 可选参数 : alternate_standard 替代标准,remark 备注,上传的文件编号 file_id
             * 编辑 标准文件的时候 一定要传递 id 编号
             */
            if(empty($param['id'])){
                $flag = $node->insertTb($param);
                return json($flag);
            }else{
                $flag = $node->editTb($param);
                return json($flag);
            }
        }
        return $this->fetch();
    }

    /**
     * 下载文件
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author hutao
     */
    public function fileDownload()
    {
        // 前台需要 传递 文件编号 id
        $norm = new NormModel();
        $data = $norm->getOne(input('file_id'));
        $file_obj = Db::name('attachment')->where('id',$data['file_id'])->field('filename,filepath')->find();
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
     * 删除
     * @return \think\response\Json
     * @author hutao
     */
    public function standardDel()
    {
        // 前台需要传递 id 编号
        if(request()->isAjax()) {
            $sd = new NormModel();
            $flag = $sd->deleteTb(input('param.id'));
            return json($flag);
        }
    }

    /**
     * 获取路径
     * @return \think\response\Json
     * @author hutao
     */
    public function getParents()
    {
        /**
         * 前台就传递 当前点击的节点的 id 编号
         */
        if(request()->isAjax()){
            $id = $this->request->has('id') ? $this->request->param('id', 0, 'intval') : 0;
            $node = new NormModel();
            $path = "";
            while($id>0)
            {
                $data = $node->getOne($id);
                $path = $data['name'] . ">>" . $path;
                $id = $data['pid'];
            }
            return json(['code' => 1,'path' => substr($path, 0 , -2),'msg' => "success"]);
        }
    }

}