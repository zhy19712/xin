<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/4/9
 * Time: 10:33
 */
/**
 * 日常质量管理，监理日志
 * Class Supervisionlog
 * @package app\quality\controller
 */
namespace app\quality\controller;
use app\admin\controller\Permissions;
use app\admin\model\AdminGroup;//组织机构
use app\admin\model\Admin;//用户表
use app\quality\model\SupervisionLogModel;//监理日志模型
use \think\Session;
use think\Db;

class Supervisionlog extends Permissions
{
    /**
     * 模板首页
     * @return mixed
     */
    public function index()
    {
        return $this->fetch();
    }

    /**********************************监理日志类型树************************/
    /**
     * 监理日志类型树
     * @return mixed|\think\response\Json
     */
    public function tree()
    {
        if ($this->request->isAjax()){
            //实例化模型
            $model = new SupervisionLogModel();
            //查询监理日志表
            $data = $model->getall();
            $res = tree($data);

            foreach ((array)$res as $a => $b) {
                $b['id'] = strval($b['id']);
                $res[$a] = json_encode($b);
            }
            return json($res);
        }
    }
    /**********************************监理日志************************/
    /**
     * 获取一条监理日志信息
     */
    public function getindex()
    {
        if(request()->isAjax()){
            //实例化模型类
            $model = new SupervisionLogModel();
            $param = input('post.');
            $data = $model->getOne($param['id']);
            return json(['code'=> 1, 'data' => $data]);
        }
        return $this->fetch();
    }

    /**
     * 上传监理日志
     * @return \think\response\Json
     */
    public function add()
    {
        if(request()->isAjax()){
            //实例化模型类
            $model = new SupervisionLogModel();
            $admin = new Admin();
            $group = new AdminGroup();

            $param = input('post.');

            //获取当前时间的年月日
            $year = date("Y");
            $month = date("m");
            $day = date("d");

            //根据当前的上传时间获取父级节点id
            //1.查询当前的年份是否存在,如果年份不存在时，新建一条年份记录
            $search_info =[
                "year" => $year,
                "month" => "",
                "day" => ""
            ];
            $result = $model->getid($search_info);
            if(!$result['id'])//如果当前的年份不存在就新建当前年的记录
            {
                $data = [
                    "year" => $year,
                    "name" => $year."年",
                    'pid' => 1
                ];
                //新增一条年份
                $model -> insertLog($data);

                //新建一条月份记录
                $search_info1 =[
                    "year" => $year,
                    "month" => "",
                    "day" => ""
                ];
                $result1 = $model->getid($search_info1);
                $data1 = [
                    "year" => $year,
                    "month" => $month,
                    "name" => $month."月",
                    'pid' => $result1['id']
                ];
                //新增一条月份记录
                $model -> insertLog($data1);

                //新建一条日份记录
                $search_info2 =[
                    "year" => $year,
                    "month" => $month,
                    "day" => ""
                ];
                $result2 = $model->getid($search_info2);
                $admin_id = Session::get('current_id');
                $admininfo = $admin->getadmininfo($admin_id);

                $data2 = [
                    "year" => $year,
                    "month" => $month,
                    "day" => $day,
                    "name" => $day."日",
                    "pid" => $result2['id'],
                    "attachment_id" => $param["attachment_id"],//对应attachment文件上传表中的id
                    "filename" => date("YmdHis"),//默认上传的文件名为日期

                    "admin_group_id" => $admininfo["admin_group_id"]
                ];
                $flag = $model->insertLog($data2);
                return json($flag);


            }else{
                //2.查询当前的月份是否存在,如果月份不存在时，新建一条月份记录
                $search_info =[
                    "year" => $year,
                    "month" => $month,
                    "day" => ""
                ];
                $result = $model->getid($search_info);
                if(!$result['id'])//如果当前的月份不存在就新建当前月的记录
                {
                    $search_info1 =[
                        "year" => $year,
                        "month" => "",
                        "day" => ""
                    ];
                    $result1 = $model->getid($search_info1);

                    $data1 = [
                        "year" => $year,
                        "month" => $month,
                        "name" => $month."月",
                        'pid' => $result1['id']
                    ];
                    //新增一条月份
                    $model -> insertLog($data1);
                    //新建一条日份记录
                    $search_info2 =[
                        "year" => $year,
                        "month" => $month,
                        "day" => ""
                    ];
                    $result2 = $model->getid($search_info2);
                    $admin_id = Session::get('current_id');
                    $admininfo = $admin->getadmininfo($admin_id);

                    $data2 = [
                        "year" => $year,
                        "month" => $month,
                        "day" => $day,
                        "name" => $day."日",
                        "pid" => $result2['id'],
                        "attachment_id" => $param["attachment_id"],//对应attachment文件上传表中的id
                        "filename" => date("YmdHis"),//默认上传的文件名为日期
                        "admin_group_id" => $admininfo["admin_group_id"]
                    ];
                    $flag = $model->insertLog($data2);
                    return json($flag);

                }else{
                    //3.如果当前的年份、月份都存在时，新增完整的一条信息
                    //查询当前登录的用户所属的组织机构名
                    $search_info =[
                        "year" => $year,
                        "month" => $month,
                        "day" => ""
                    ];
                    $result = $model->getid($search_info);
                    $admin_id = Session::get('current_id');
                    $admininfo = $admin->getadmininfo($admin_id);

                    $data = [
                        "year" => $year,
                        "month" => $month,
                        "day" => $day,
                        "name" => $day."日",
                        "pid" => $result['id'],
                        "attachment_id" => $param["attachment_id"],//对应attachment文件上传表中的id
                        "filename" => date("YmdHis"),//默认上传的文件名为日期
                        "admin_group_id" => $admininfo["admin_group_id"]
                    ];
                    $flag = $model->insertLog($data);
                    return json($flag);
                }
            }
        }
    }

    /**
     * 编辑一条监理日志信息
     */
    public function edit()
    {
        if(request()->isAjax()){
            //实例化模型类
            $model = new SupervisionLogModel();
            $param = input('post.');
            $data = [
                'id' => $param['id'],//监理日志自增id
                'filename' => $param['filename']//上传文件名
            ];
            $flag = $model->editLog($data);
            return json($flag);
        }
    }

    /**
     * 删除一条监理日志信息
     */
    public function del()
    {
        if (request()->isAjax()){
            //实例化model类型
            $model = new SupervisionLogModel();
            $id = input('post.id');//要删除的监理日志id
            //首先判断一下删除的只一天所属的月份下是否有其他日子
            $data_info = $model->getOne($id);

            $day_count = $model->getcount($data_info['pid']);

            $data_month = $model->getOne($data_info["pid"]);

            //如果一个月份下只有一条的话就删除这个月份
            if($day_count < 2)
            {
                $model -> delLog($data_info['pid']);

            }

            //判断年份下只有一条的话就删除这个年份
            $year_count = $model->getcount($data_month['pid']);
            if($year_count < 1)
            {
                //如果一个年份下只有一条的话就删除这个年份
                $model -> delLog($data_month['pid']);
            }

            //最后删除这条监理日志信息
            //查询attachment表中的文件上传路径
            $attachment = Db::name("attachment")->where("id",$data_info["attachment_id"])->find();
            $path = "." .$attachment['filepath'];
            $pdf_path = './uploads/temp/' . basename($path) . '.pdf';
            if($attachment['filepath'])
            {
                if(file_exists($path)){
                    unlink($path); //删除上传的图片或文件
                }
                if(file_exists($pdf_path)){
                    unlink($pdf_path); //删除生成的预览pdf
                }
            }

            //删除attachment表中对应的记录
            Db::name('attachment')->where("id",$data_info["attachment_id"])->delete();

            //最后删除这一条记录信息
            $flag = $model->delLog($id);
            return $flag;
        }
    }
    /***************************************三维模型******************/
    /**
     * 编辑一条现场图片位置信息
     */
    public function editPosition()
    {
        if(request()->isAjax()){
            //实例化模型类
            $model = new SupervisionLogModel();
            $param = input('post.');
            halt($param);
            $data = [
                'id' => $param['id'],//现场图片自增id
                'position' => $param['position']//位置信息
            ];
            $flag = $model->editLog($data);
            return json($flag);
        }
    }
}