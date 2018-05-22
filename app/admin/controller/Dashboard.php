<?php
/**
 * Created by PhpStorm.
 * User: waterforest
 * Date: 2018/5/22
 * Time: 9:43
 */

/**
 * 控制面板-消息提醒
 * Class Dashboard
 * @package app\admin\controller
 */
namespace app\admin\controller;
use app\admin\model\MessageremindingModel;//消息记录
use app\quality\model\QualityFormInfoModel;//单元工程审批表
use \think\Db;
use \think\Session;
use think\exception\PDOException;

class Dashboard extends Permissions
{
    /**
     * 消息提醒模板首页
     * @return mixed
     */
    public function index()
    {
        return $this->fetch();
    }

    public function buildMessage()
    {
        //实例化模型类
        $qualityform = new QualityFormInfoModel();
        $message = new MessageremindingModel();
        //获取当前登录的用户id
        $admin_id= Session::has('admin') ? Session::get('admin') : 0;
        //查询单元工程审批人状态表
        $form_info = $qualityform->getAdminapproval($admin_id);
        $data = array();
        $edit_data = array();
        if(!empty($form_info))
        {
            foreach($form_info as $key=>$val)
            {
                $result = $message->getOne(["uint_id"=>$val["id"],"current_approver_id"=>$val["CurrentApproverId"]]);

                if(!empty($result))
                {
                    $edit_data[$key]["id"] = $result["id"];
                    $edit_data[$key]["status"] = $val["ApproveStatus"];

                }
                else
                {
                    $data[$key]["uint_id"] = $val["id"];
                    $data[$key]["task_name"] = $val["form_name"];
                    $data[$key]["create_time"] = time();
                    $data[$key]["sender"] = substr($val["ApproveIds"], -1);
                    $data[$key]["task_category"] = "单元质量验评";
                    $data[$key]["status"] = $val["ApproveStatus"];
                    $data[$key]["current_approver_id"] = $val["CurrentApproverId"];
                }
            }
            $flag = $message->insertTbAll($data);

            $flag = $message->editTbAll($edit_data);

            //查询信息表中的未处理的消息

            return json($flag);
        }

    }

    /**
     * 轮询
     * @return \think\response\Json
     */
    public function lunxun()
    {
        //实例化模型类
        if ($this->request->isAjax()) {
            $message = new MessageremindingModel();

            $count = input("post.count");

            $time = 30;
            set_time_limit(0);//无限请求超时时间
            $i = 0;
            while (true) {
                usleep(500000);//0.5秒
                $i++;
                if ($i <= $time) {

                    $this->buildMessage();

                    $count_data = $message->getCount();

                    if ($count_data != $count) {

                        return json(["count"=>$count_data]);

                        exit;
                    }
                } else {

                    exit;
                }

            }
        }
    }
}