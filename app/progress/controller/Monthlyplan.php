<?php
/**
 * Created by PhpStorm.
 * User: sir
 * Date: 2018/6/13
 * Time: 9:06
 */

namespace app\progress\controller;


use app\admin\controller\Permissions;
use app\admin\model\Attachment;
use app\progress\model\MonthlyplanModel;
use think\Db;

/**
 * 月计划管理
 * Class actual
 * @package app\progress\controller
 */
class Monthlyplan extends Permissions
{
    /**
     * 月计划管理
     * @return mixed
     * @author hutao
     */
    public function index()
    {
        return $this->fetch();
    }

    /**
     * 月计划列表
     * @return mixed
     * @author hutao
     */
    public function list_table()
    {
        return $this->fetch();
    }

    /**
     * 新增月计划
     * @return \think\response\Json
     * @author hutao
     */
    public function add()
    {
        if($this->request->isAjax()){
            // 前台需要传递的参数: 计划名称 plan_name 所属标段编号 section_id 年度 plan_year 月度 plan_monthly 更新方式 update_mode
            // 编辑的时候传递本条记录的编号 mid
            $param = input('param.');
            // 验证规则
            $rule = [
                ['plan_name', 'require', '缺少计划名称'],
                ['section_id', 'require', '缺少所属标段编号'],
                ['plan_year', 'require', '缺少年度'],
                ['plan_monthly', 'require', '缺少月度'],
                ['update_mode', 'require', '缺少更新方式']
            ];
            $validate = new \think\Validate($rule);
            //验证部分数据合法性
            if (!$validate->check($param)) {
                return json(['code' => -1,'msg' => $validate->getError()]);
            }

            // 更新方式是导入全新计划版本的话,就验证是否上传了Project或P6格式的文件
            if($param['update_mode'] == 2){ // 1手动 2导入
                $plan_file_id = isset($param['plan_file_id']) ? $param['plan_file_id'] : 0;
                if(empty($plan_file_id)){
                    return json(['code' => -1,'msg' => '缺少Project或P6格式的导入文件']);
                }
                //TODO 导入文件
            }

            // 如果当前选择的年月已经存在月计划,确定后提示覆盖.覆盖则删除原有的计划和与之相关的数据,包括模型关联关系
            $monthly = new MonthlyplanModel();
            $is_exist = $monthly->monthlyExist($param['plan_year'],$param['plan_monthly']);
            if($is_exist){
                $cover = isset($param['cover']) ? $param['cover'] : 0;
                if($cover){
                    // TODO 覆盖则删除原有的计划和与之相关的数据,包括模型关联关系
                }
            }

            $id = isset($param['mid']) ? $param['mid'] : 0;
            if(empty($id)){
                $flag = $monthly->insertTb($param);
            }else{
                $param['id'] = $id;
                $flag = $monthly->editTb($param);
            }
            return json($flag);
        }
    }

    // 查看
    public function preview(){}

    /**
     * 删除月计划
     * @return \think\response\Json
     * @author hutao
     */
    public function del()
    {
        if($this->request->isAjax()){
            // 前台传递本条记录的编号 plan_id
            $param = input('param.');
            $plan_id = isset($param['plan_id']) ? $param['plan_id'] : 1;
            if(empty($plan_id)){
                return json(['code'=>-1,'msg'=>'缺少参数']);
            }
            //TODO 删除该月计划下的所有甘特图数据

            $actual = new MonthlyplanModel();
            $flag = $actual->deleteTb($plan_id);
            return json($flag);
        }
    }

    /**
     * 下载报告
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author hutao
     */
    public function download()
    {
        // 前台需要 传递 文件编号 file_id
        $param = input('param.');
        $file_id = isset($param['file_id']) ? $param['file_id'] : 0;
        if(empty($file_id)){
            return json(['code' => '-1','msg' => '缺少参数']);
        }
        $file_obj = Db::name('attachment')->where('id',$file_id)->field('name,filename,filepath')->find();
        $filePath = '';
        if(!empty($file_obj['filepath'])){
            $filePath = '.' . $file_obj['filepath'];
        }
        if(!file_exists($filePath)){
            return json(['code' => '-1','msg' => '文件不存在']);
        }else if(request()->isAjax()){
            return json(['code' => 1]); // 文件存在，告诉前台可以执行下载
        }else{
            $fileName = empty($file_obj['name']) ? $file_obj['filename'] : $file_obj['name'];
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
     * 删除报告
     * @return \think\response\Json
     * @author hutao
     */
    public function delReport()
    {
        if($this->request->isAjax()){
            // 前台需要 传递 文件编号 file_id
            $param = input('param.');
            $file_id = isset($param['file_id']) ? $param['file_id'] : 6;
            if(empty($file_id)){
                return json(['code' => '-1','msg' => '缺少参数']);
            }
            $att = new Attachment();
            $flag = $att->deleteTb($file_id);
            return json($flag);
        }
    }


}