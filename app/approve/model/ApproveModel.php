<?php
/**
 * Created by PhpStorm.
 * User: zhifang
 * Date: 2018/4/20
 * Time: 10:06
 */

namespace app\approve\model;

use app\admin\model\Admin;
use think\Db;
use think\Exception;
use think\Model;
use think\Session;

class ApproveModel extends Model
{
    protected $name = 'approve';
    protected $autoWriteTimestamp = true;

    /**
     * 审批人
     * @return \think\model\relation\HasOne
     */
    public function User()
    {
        return $this->hasOne('app\admin\Admin', 'id', 'user_id');
    }

    /**
     * 提交审批
     * @param $dataId 业务id
     * @param $dataType 业务类型
     * @param $userId 提交人id
     * @param $approveIds 审批人列表Id串，“,”分割
     */
    public function submit($dataId, IApprove $dataType, $userId, $approveIds)
    {
        try {
            $mod = array();
            $mod['data_id'] = $dataId;
            $mod['data_type'] = $dataType->class;
            $mod['user_id'] = $userId;
            $mod['result'] = "提交";
            $mod['mark'] = "提交审批";
            $this->save($mod);
            $dataType->SubmitHandle($dataId, $approveIds, explode(",", $approveIds)[0]);
            return ['code' => 1];
        } catch (Exception $exception) {
            return ['code' => -1, 'msg' => $exception->getMessage()];
        }
    }

    /**
     * 审批
     * @param $dataId
     * @param $dataType
     * @param $approveResult 审批结果：1、通过，-1、退回
     * @param $approveMark
     * @throws \think\exception\DbException
     */
    public function Approve($dataId, IApprove $dataType, $approveResult, $approveMark)
    {
        Db::startTrans();
        /// 审批逻辑：根据审批结果为通过或不通过 将业务数据标的CurrentApproverId修改为NextApproverId/PreApproverId，同时修改业务数据状态。
        /// 通过，且下一步审核人为空，表示已结束，业务数据状态改为2，下一步审核人不为空，只需修改CurrentApproverId，状态不需要修改，仍在审批中
        /// 不通过，CurrentApproverId修改为PreApproverId，状态修改为-1，标识已退回。
        try {
            $ApproveInfo = $this->getApproveInfo($dataId, new $dataType);
            $approveStatus = 1;
            $currentApproverId = $ApproveInfo->NextApproverId;
            $currentStep = intval($ApproveInfo->CurrentStep) + 1;
            $resultStr = "";
            if ($approveResult == -1) {
                //退回上一审批人
                $currentApproverId = $ApproveInfo->PreApproverId;
                $currentStep = intval($ApproveInfo->CurrentStep) - 1;
                $approveStatus = $currentStep == 0 ? -1 : 1;
                $resultStr = "未通过";
            } else {
                //审批通过，判断流程是否结束
                if (empty($ApproveInfo->NextApproverId)) {
                    //审批完成
                    $approveStatus = 2;
                }
                $resultStr = "通过";
            }
            //更新数据
            $dataType->UpdateApproveInfo($dataId, $currentApproverId, $currentStep, $approveStatus);
            //记录审批历史
            self::save([
                'data_type' => $dataType->class,
                'data_id' => $dataId,
                'user_id' => Session::get('current_id'),
                'create_time' => time(),
                'result' => $resultStr,
                'mark' => $approveMark
            ]);
            return true;
        } catch (Exception $exception) {
            $this->rollback();
            return false;
        }
    }


    /**
     * 获取审批信息
     * @param $dataId
     * @param IApprove $dataType
     * @return ApproveInfo|int
     * @throws \think\exception\DbException
     */
    public function getApproveInfo($dataId, IApprove $dataType)
    {
        //得到业务基本信息  user_id approverIds,CurrentApproverId,CurrentStep
        $info = $dataType->GetApproveInfo($dataId);
        if (empty($info)) {
            return -1;
        }
        $mod = new ApproveInfo();
        $mod->approveIds = $info['ApproveIds'];
        $approveIds = explode(',', $info['ApproveIds']);
        //流程结尾判断
        if ($info['CurrentStep'] < sizeof($approveIds)) {
            $mod->NextApproverId = $approveIds[$info['CurrentStep']];
            $mod->NextApproverName = Admin::get($mod->NextApproverId)['nickname'];
        }
        $mod->PreApproverId = $info['user_id'];
        $mod->PreApproverName = Admin::get($info['user_id'])['nickname'];
        $mod->CurrentStep = $info['CurrentStep'];
        return $mod;
    }

    /**
     * 获取业务下常用审批人
     * @param IApprove $dataType 业务Model对象
     * @return \think\response\Json
     */
    public function FrequentlyUsedApprover(IApprove $dataType)
    {
        $userlist = $dataType->FrequentlyUsedApprover(Session::get('current_id'));
        return $userlist;
    }

    /**
     * 业务数据完整性检测
     * @param $dataId
     * @param IApprove $dataType
     * @param $currentStep
     * @return mixed
     */
    public function CheckBeforeSubmitOrApprove($dataId, IApprove $dataType, $currentStep)
    {
        return $dataType->CheckBeforeSubmitOrApprove($dataId, $currentStep);
    }
}

class ApproveInfo
{
    public $approveIds = "";
    //当前审批人
    public $CurrentApproverId = "";
    public $CurrentApproverName = "";
    //审批时间
    public $ApproveTime;
    //下一步审批人
    public $NextApproverId = "";
    public $NextApproverName = "审批完成";
    //上一步审批人
    public $PreApproverId = "";
    public $PreApproverName = "";
    //审批人ID串
    public $ApproverIds = "";
    //创建人
    public $CreateUserId = "";
    //当前步骤
    public $CurrentStep = "";
}