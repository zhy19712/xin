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

namespace app\api\controller;
use app\admin\model\MessageremindingModel;//消息记录
use app\quality\model\QualityFormInfoModel;//单元工程审批表
use app\quality\model\SendModel;//收文
use think\Controller;
use \think\Db;
use \think\Session;
use think\exception\PDOException;
use app\admin\model\JpushModel;
use app\admin\model\JpushTestModel;
use think\Loader;
vendor('JPush.autoload');

class Dashboard extends Log
{

    /**
     * 消息提醒模板首页
     * @return mixed
     */
    public function index()
    {
        return $this->fetch();
    }

    /**
     * 单元工程质量验评
     * @return \think\response\Json
     */
    public function buildMessage()
    {
        //实例化模型类
        $qualityform = new QualityFormInfoModel();
        $message = new MessageremindingModel();
        //获取当前登录的用户id

        $admin_id = Session::has('admin') ? Session::get('admin') : 0;


        //查询单元工程审批人状态表
        $form_info = $qualityform->getAdminapproval($admin_id);

        //定义两个空的数组用来存储值
        $data = array();
        $edit_data = array();
        if (!empty($form_info)) {
            foreach ($form_info as $key => $val) {
                $result = $message->getOne(["uint_id" => $val["id"], "current_approver_id" => $val["CurrentApproverId"]]);

                if (!empty($result)) {
                    $edit_data[$key]["id"] = $result["id"];

                    $edit_data[$key]["status"] = $val["ApproveStatus"];

                } else {
                    $data[$key]["uint_id"] = $val["id"];
                    $data[$key]["task_name"] = $val["form_name"];

                    $data[$key]["create_time"] = strtotime($val["update_time"]);


                    if ($val["ApproveIds"]) {
                        $ids = explode(",", $val["ApproveIds"]);

                        $data[$key]["sender"] = $ids[count($ids) - 1];
                    }

                    $data[$key]["task_category"] = "单元质量验评";
                    $data[$key]["status"] = $val["ApproveStatus"];
                    $data[$key]["current_approver_id"] = $val["CurrentApproverId"];
                    $data[$key]["type"] = 2;//单元工程质量验评
                }
            }

            if (!empty($data)) {
                foreach ($data as $a => $b) {
                    $message->insertTb($b);
                }
            }
            if (!empty($edit_data)) {
                $message->saveTb($edit_data);
            }
        }
    }

    /**
     * 收发文
     * @return \think\response\Json
     */
    public function buildSendMessage()
    {
        //实例化模型类
        $send = new SendModel();
        $message = new MessageremindingModel();
        //获取当前登录的用户id

        $admin_id = Session::has('admin') ? Session::get('admin') : 0;
        //查询收文
        $form_info = $send->getIncomeid($admin_id);

        //定义两个空的数组用来存储值
        $data = array();
        $edit_data = array();
        if (!empty($form_info)) {
            foreach ($form_info as $key => $val) {
                $result = $message->getOne(["uint_id" => $val["id"], "current_approver_id" => $val["income_id"]]);

                if (!empty($result)) {
                    $edit_data[$key]["id"] = $result["id"];
                    //如果收发文中的status状态为2表示未执行
                    if ($val["status"] == 2) {
                        $edit_data[$key]["status"] = 1;//未执行
                    } else//3、4表示已执行
                    {
                        $edit_data[$key]["status"] = 2;//已执行
                    }

                } else {
                    $data[$key]["uint_id"] = $val["id"];
                    $data[$key]["task_name"] = $val["file_name"];
                    $data[$key]["create_time"] = strtotime($val["update_time"]);
                    $data[$key]["sender"] = $val["send_id"];
                    $data[$key]["task_category"] = "收文";
                    //如果收发文中的status状态为2表示未执行
                    if ($val["status"] == 2) {
                        $data[$key]["status"] = 1;//未执行
                    } else//3、4表示已执行
                    {
                        $data[$key]["status"] = 2;//已执行
                    }
                    $data[$key]["current_approver_id"] = $val["income_id"];
                    $data[$key]["type"] = 1;//type=1表示收发文
                }
            }

            if (!empty($data)) {
                foreach ($data as $a => $b) {
                    $message->insertTb($b);
                }
            }

            if (!empty($edit_data)) {
                $message->saveTb($edit_data);
            }
        }
    }

    /**
     * 查询当前消息表中状态status=1的条数
     * @return \think\response\Json
     */
    public function queryMessage()
    {
        if ($this->request->isAjax()) {
            //实例化模型类
            $message = new MessageremindingModel();
            //获取当前的登录人的id
            $admin_id = Session::has('admin') ? Session::get('admin') : 0;
            //单元工程质量验评
            $this->buildMessage();
            //收发文
            $this->buildSendMessage();

            $count_data = $message->getCount($admin_id);

            return json(["count" => $count_data]);
        }
    }

    /**
     * 消息中的单元工程改变当前的状态
     */
    public function changeStatus()
    {
        if ($this->request->isAjax()) {
            $uint_id = input("post.uint_id");

            $type = input("post.type");//1为收发文，2为单元管控

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
            return json(["code" => 1]);
        }
    }

    /**
     * 别名推送
     */
    public function test()
    {
        $jpush = new JpushModel();
        $alias = "admin";
        $alert = "id:1,pid:2,cid:3";
        $jpush->push_a($alias,$alert);
    }

    /**
     * 推送给指定的设备
     * 最新测试极光推送
     * @return \think\response\Json
     */
    public function test5()
    {
        $model = new JpushTestModel();
        //向特定用户进行推送—单播
        //$regid可以是一个单个regid组成的字符串，也可以是多个regid组成的数组
        //$data['content']是你所需要推送的内容
        $regid = "140fe1da9ef8282cb5c";
        $data["content"] = "333333";
        $result_s = $model->sendNotifySpecial($regid,$data['content']);
        return json(["code"=>1,"data"=>$result_s]);
        //想所有用户进行推送—广播
//        $result_a = sendNotifyAll($data['content']);

        //获取统计用户是否获取推送消息的信息(或者有多少用户收到了推送消息)
        //$msgids是你推送消息的消息id
//        $result_r = reportNotify($msgIds);
    }

    /**
     * app端获取所有的消息列表
     * @return \think\response\Json
     * @throws \think\exception\DbException
     */
    public function getAllMessage()
    {
        //实例化模型类
        $model = new QualityFormInfoModel();

        //获取传过来的所有的值
        $param = $_POST;
        //查询不同的状态1为未执行，2为已执行
        $status = $param["status"];
        //当前登录人的id
        $admin_id = $param["id"];
        //分页的条数
        $count = $param["count"];
        //当前的页码
        $page = $param["page"];

        $message_list = Db::name("admin_message_reminding")->alias("m")
            ->join('admin a','m.sender = a.id','left')
            ->field("m.task_name,m.create_time,a.nickname as sender,m.task_category,m.status,m.id,m.type,m.uint_id")
            ->where("m.status",$status)
            ->where("m.current_approver_id",$admin_id)
            ->select();

        if(!empty($message_list))
        {
            foreach($message_list as $key=>$val)
            {
                if($val["type"] == 2)//等于2代表单元工程
                {
                    $form_info = $model->getInfomation($val["uint_id"]);

                    $cpr_id = Db::name("quality_division_controlpoint_relation")
                        ->field("id")
                        ->where(["division_id"=>$form_info["DivisionId"],"ma_division_id"=>$form_info["ProcedureId"],"control_id"=>$form_info["ControlPointId"]])
                        ->find();

                    $form_data["cpr_id"] = $cpr_id["id"];

                    $form_data["CurrentStep"] = $form_info["CurrentStep"];

                    $message_list[$key]["form_info"] = $form_data;
                }else
                {
                    $message_list[$key]["form_info"] = [];
                }

            }
        }
        $order = 0;
        //当前的所有的数据条数
        $pageCount = count($message_list);

        $pageArray = page_array($count,$page,$message_list,$order);

        return json(["code"=>1,"pageCount"=>$pageCount,"pageArray"=>$pageArray]);
    }
}

