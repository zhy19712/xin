<?php
/**
 * Created by PhpStorm.
 * User: zhifang
 * Date: 2018/4/19
 * Time: 16:55
 */

namespace app\approve\controller;

use app\admin\controller\Permissions;
use app\quality\controller\Element;
use app\admin\model\Admin;
use app\approve\model\ApproveModel;
use app\quality\model\QualityFormInfoModel;
use think\Request;
use think\Session;
use think\Db;

use app\admin\model\JpushModel;
vendor('JPush.autoload');

/**
 * 流程审批
 * Class Approve
 * @package app\approve\controller
 */
class Approve extends Permissions
{
    protected $approveService;
    protected $adminService;

    public function __construct(Request $request = null)
    {
        $this->approveService = new ApproveModel();
        $this->adminService = new Admin();
        parent::__construct($request);
    }

    /**
     * 提交审批
     * @param $dataId
     * @param $dataType
     * @return mixed
     */
    public function submit($dataId, $dataType, $referFlow = null)
    {
        if ($this->request->isAjax()) {
            $par = input('post.');
            $res = $this->approveService->submit($par['dataId'], new $par['dataType'], Session::get('current_id'), $par['approveids']);
            return json($res);
        }
        $this->assign("dataId", $dataId);
        $this->assign("dataType", $dataType);
        $this->assign("referFolw", $referFlow);
        return $this->fetch();
    }

    /**
     * 审批流程
     * @return mixed
     */
    public function Approve()
    {
        $par = input("param.");
        if ($this->request->isAjax()) {
            if ($this->approveService->Approve($par['dataId'], new $par['dataType'], $par['res'], $par['mark'])) {
                $next_approverid=$par['next_approverid'];//下一个审批人的id
                //去approve表里找审批历史 更新审批人id到审批人串里
                $approveHistory=Db::name('quality_form_info')
                    ->where(['id'=>$par['dataId']])
                    ->find();
                if($approveHistory['ApproveIds']==''){
                    $ApproveIds=array();//为空的话重新定义一个数组
                }else {
                    $ApproveIds = explode(',', $approveHistory['ApproveIds']);
                }
                //审批结果
                if($par['res']=='1')
                {
                    if($next_approverid>0)
                    {
                        $ApproveStatus = 1;
                        $ApproveIds[]=$next_approverid;
                    }
                    else
                    {
                        $ApproveStatus = 2;//没有下一步审批人且通过审批，状态为2；
                        //自动提取表单中的验评结果和日期更新到数据库里
                        $elementModel=new Element();
                        $res=$elementModel->saveEvaluation($par['dataId']);

                        //更新到relation表中 状态为已执行
                        Db::name('quality_division_controlpoint_relation')
                            ->where(['control_id'=>$approveHistory['ControlPointId'],'division_id'=>$approveHistory['DivisionId'],'type'=>1])
                            ->update(['status'=>1]);

                     }
                     $CurrentStep= $approveHistory['CurrentStep']+1;
                }
                else
                 {
                    $ApproveStatus = -1;//被退回
                    $newApproveIds=$approveHistory['ApproveIds'];//被退回审批人不变,因为涉及到审批历史
                    $next_approverid=$approveHistory['user_id'];
                    $ApproveIds[]=$next_approverid;//审批人变为起草人
                    $CurrentStep= 0;
                 }
                $newApproveIds=implode(',', $ApproveIds);
                 //按审批人数量算步骤，创建人的步骤为0
                Db::name('quality_form_info')
                    ->where(['id'=>$par['dataId']])
                    ->update(['CurrentApproverId'=>$next_approverid,'ApproveStatus'=>$ApproveStatus,'ApproveIds'=>$newApproveIds,'CurrentStep'=>$CurrentStep,'update_time'=>time()]);

            //判断当前的下一个审批人的是否已经登录
                $admin_model = new Admin();
                $admin_info = $admin_model->getOne($next_approverid);

//                if(!empty($admin_info["token"]))
//                {
//                    //查询关联表quality_division_controlpoint_relation的信息
//                    $form_info = Db::name("quality_form_info")->where("id",$par['dataId'])->find();
//                    $relation_info = Db::name("quality_division_controlpoint_relation")
//                        ->where(["division_id"=>$form_info["DivisionId"],"ma_division_id"=>$form_info["ProcedureId"],"control_id"=>$form_info["ControlPointId"]])
//                        ->find();
////                极光推送给下一个审批人消息
//                    $jpush = new JpushModel();
////                获取当前的用户名
//                    $admin_name = Session::has('current_name') ? Session::get('current_name') : 0; //收件人姓名
//                    $alias = $admin_name;
//                    $alert = "id:{$par['dataId']},type:单元工程,CurrentApproverId:{$next_approverid},CurrentStep:{$form_info["CurrentStep"]},cpr_id:{$relation_info["id"]}";
//                    $jpush->push_a($alias,$alert);
//                }
                return json(['code' => 1]);
            } else {
                return json(['code' => -1]);
            }
        }
        $this->assign('dataId', $par['dataId']);
        $this->assign('dataType', $par['dataType']);
        $this->assign('ApproveInfo', json_encode($this->approveService->getApproveInfo($par['dataId'], new $par['dataType'])));
        return $this->fetch();
    }

    /**
     * 审批历史
     * @return mixed
     */
    public function ApproveHistory($dataId, $dataType)
    {
        $info = $this->approveService->getApproveInfo($dataId, new $dataType);
        $_userlist = $this->adminService->whereIn('id', explode(',', $info->approveIds))->with('Thumb')->select();
        $res=Db::name('quality_form_info')
            ->where(['id'=>$dataId])
            ->find();
        //如果状态大于0，将起草人也加入进去
        //如果有审批串，就将起草人也算进去
        $approverArr=explode(',', $res['ApproveIds']);

        //每个审批历史中将起草人放在第一位，并去掉审批串的待审批人
        if(count($approverArr)>=0&&($res['CurrentApproverId']!='null'&&$res['CurrentApproverId']!=0))
        {
            array_unshift($approverArr,$res['user_id']);
            array_pop($approverArr);
        }
        //如果是已完成或者已经作废，加入创建者，不去掉最终审批人
        if($res['ApproveStatus']==2||$res['ApproveStatus']==-2)
        {
            array_unshift($approverArr,$res['user_id']);
        }
        //防止只出现一次审批的情况
         if($res['ApproveStatus']==2&&($res['CurrentApproverId']=='null'||$res['CurrentApproverId']==0)&&($res['ApproveIds']=='null'||$res['ApproveIds']==0))
        {
            $approverArr=array();
            $approverArr[]=$res['user_id'];
        }
        //待提交时将审批人串归0，无历史记录，防止出现null
         if($res['ApproveStatus']==0&&($res['CurrentApproverId']=='null'||$res['CurrentApproverId']==0))
        {
            $approverArr=array();
        }

        $userlist = array();
        foreach ($approverArr as $item) {
            $u = $this->adminService->where('id', $item)->with('Thumb')->find();
            $_u = array();
            $_u = [
                'id' => $u['id'],
                'nickname' => $u['nickname']
            ];
            if (is_null($u['Thumb']))
                $_u['thumb'] = $u['Thumb']['filepath'];
            $userlist[] = $_u;
        }
        //foreach ($_userlist as $user) {
        //    $_user = array();
        //    $_user = [
        //        'id' => $user['id'],
        //        'nickname' => $user['nickname'],
        //        'thumb' => ''
        //    ];
        //    if (!is_null($user['Thumb'])) {
        //        $_user['thumb'] = $user['Thumb']['filepath'];
        //    }
        //    $userlist[] = $_user;
        //}
        $this->assign('dataId', $dataId);
        $this->assign('dataType', $dataType);
        $this->assign('users', json_encode($userlist));
        $this->assign('approveinfo', json_encode($info));
        return $this->fetch();
    }

    /**
     * 业务数据完整性检测
     * @param $dataId
     * @param $dataType
     * @param $currentStep
     */
    public function CheckBeforeSubmitOrApprove($dataId, $dataType, $currentStep)
    {


        $res = $this->approveService->CheckBeforeSubmitOrApprove($dataId, new $dataType, $currentStep);
        return $res;
    }

    /**
     * 选择人员
     * @return mixed
     */
    public function selectMumber($dataType = null)
    {
        $this->assign('dataType', $dataType);
        return $this->fetch();
    }

    /**
     * 获取常用审批人
     * @param $dataType 带有命名空间的业务模型
     * @return \think\response\Json
     */
    public function FrequentlyUsedApprover($dataType)
    {
        //QualityFormInfoModel::
        $userlist = $this->approveService->FrequentlyUsedApprover(new $dataType);
        return json($userlist);
    }
    /**
     * 在线填报中获取该用户应该审批的信息
     */
    public function approveMessage()
    {
        if ($this->request->isAjax()) {
            //取缓存中的用户信息
            $user_id = $_SESSION['think']['current_id'];
            //去quality_form_info表中查,待审批
            $approving =
                Db::name('quality_form_info')
                    ->where(['CurrentApproverId' => $user_id,'ApproveStatus' => 1])
                    ->select();
            $approvingnum = count($approving);

            //去quality_form_info表中查,已退回
            //如果当前用户在审批人串里就返回
            $refund =
                Db::name('quality_form_info')
                    ->where(['ApproveStatus' => -1])
                    ->where('find_in_set(:userid,ApproveIds)', ['userid' => $user_id])
                    ->select();
            $refund_num = count($refund);

            //创建人返回
            $creatinfo =
                Db::name('quality_form_info')
                    ->where(['ApproveStatus' => -1,'user_id' => $user_id])
                    ->select();
            $ci_num = count($refund);
        }
    }
    //获取控制点的流程
    public function approveProcedure()
    {
        if ($this->request->isAjax()) {
            $param = input("param.");
            $fi_id = $param['fi_id'];//form_info表中的主键，控制点不唯一
            //去quality_form_info表中查已审批完的表单,取出审批串和创建人
            $procedure =
                Db::name('quality_form_info')
                    ->where(['id' => $fi_id])
                    ->field('ApproveIds,user_id')
                    ->find();
            //去admin表中找出对应人信息
            $creater = Db::name('admin')
                ->where(['id' => $procedure['user_id']])
                ->field('nickname,name,thumb,step_id,mobile')
                ->find();
            $approverarr = explode(',', $procedure['ApproveIds']);
            foreach ($approverarr as $v) {
                $approver[] = Db::name('admin')
                    ->where(['id' => $v])
                    ->field('id,nickname,name,thumb,step_id,mobile')
                    ->select();
            }
            return json(['msg' => 'success', 'creater' => $creater, 'approver' => $approver]);

        }
    }

}