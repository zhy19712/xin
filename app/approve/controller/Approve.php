<?php
/**
 * Created by PhpStorm.
 * User: zhifang
 * Date: 2018/4/19
 * Time: 16:55
 */

namespace app\approve\controller;

use app\admin\controller\Permissions;
use app\admin\model\Admin;
use app\approve\model\ApproveModel;
use app\quality\model\QualityFormInfoModel;
use think\Request;
use think\Session;

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

        $userlist = array();
        foreach (explode(',', $info->approveIds) as $item) {
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
            $post = input("post.");
            $fi_id = $post['fi_id'];//form_info表中的主键，控制点不唯一
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