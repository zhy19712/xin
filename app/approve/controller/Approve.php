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
}