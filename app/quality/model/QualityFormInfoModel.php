<?php
/**
 * Created by PhpStorm.
 * User: zhifang
 * Date: 2018/4/18
 * Time: 13:48
 */

namespace app\quality\model;

use app\admin\model\Admin;
use app\admin\model\AdminGroup;
use app\approve\model\IApprove;
use app\archive\model\AtlasCateModel;
use app\contract\model\ContractModel;
use think\Exception;
use think\Model;

class QualityFormInfoModel extends Model implements IApprove
{
    protected $name = 'quality_form_info';
    protected $autoWriteTimestamp = true;
    protected $atlasCateService;
    protected $divisionUnitService;

    public function __construct($data = [])
    {
        $this->atlasCateService = new AtlasCateModel();
        $this->divisionUnitService = new DivisionUnitModel();

        parent::__construct($data);
    }

    /**
     * 审批人信息
     * @return \think\model\relation\HasOne
     */
    public function CurrentApprover()
    {
        return $this->hasOne('app\admin\model\Admin', 'id', 'CurrentApproverId');
    }

    /**
     * 控制点信息
     * @return \think\model\relation\HasOne
     */
    public function ControlPoint()
    {
        return $this->hasOne('app\standard\model\ControlPoint', 'id', 'ControlPointId');
    }

    /**
     * 获取表单基本信息
     * @param $qualityUnit_id 检验批
     */
    public function getFormBaseInfo($qualityUnit_id)
    {
        $mod = $this->divisionUnitService->with("Division.Section")->where(['id' => $qualityUnit_id])->find();
        $output = array();
        $output['JYPName'] = $mod['site'];
        $output['JYPCode'] = $output['JJCode'] = $mod['coding'];
        $output['Quantity'] = $mod['quantities'];
        $output['PileNo'] = $mod['pile_number'];
        $output['Altitude'] = $mod['el_start'] . $mod['el_cease'];
        $output['BuildBase'] = $mod['ma_bases'] ? "" : $this->getBuildBaseInfo($mod['ma_bases']) . $mod['su_basis'];
        $output['DYName'] = $mod['Division']['d_name'];
        $output['DYCode'] = $mod['Division']['d_code'];
        //标段信息
        if ($mod['Division']['Section'] != null) {
            $_section = $mod['Division']['Section'];
            $output['Constructor'] = $_section['constructorId'] ? AdminGroup::get($_section['constructorId'])['name'] : "";
            $output ['Supervisor'] = $_section['supervisorId'] ? AdminGroup::get($_section['supervisorId'])['name'] : "";
            $output ['SectionCode'] = $_section['code'];
            $output['SectionName'] = "丰宁抽水蓄能电站";
            $output['ContractCode'] = $_section['contractId'] ? ContractModel::get($_section['contractId'])['contractName'] : "";
        }
        $Info = $this->getDivsionInfo($mod['division_id']);
        $output['FBName'] = $Info['FB']['d_name'];
        $output['FBCode'] = $Info['FB']['d_code'];
        $output['DWName'] = $Info['DW']['d_name'];
        $output['DWCode'] = $Info['DW']['d_code'];
        return $output;
    }

    /**
     * 获取表单内信息
     * @param $formId
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getFormInfo($formId)
    {
        return self::where(['id' => $formId])->find()['form_data'];
    }

    /**
     * 获取工程依据信息
     * @param $ids
     * @return string
     */
    function getBuildBaseInfo($ids)
    {
        $idArr = explode(',', $ids);
        $_str = "";
        foreach ($this->atlasCateService->whereIn('id', $idArr) as $item) {
            $_str .= $item['picture_name'] . "({$item['picture_number']})";
        }
        return $_str;
    }

    /**
     * 获取单元及分布信息
     * @param $id
     */
    function getDivsionInfo($id)
    {
        $__mod = DivisionModel::get($id);
        $_mod = array();

        $_mod['FB'] = DivisionModel::get($__mod['pid']);
        $_mod['DW'] = DivisionModel::get($_mod['FB']['pid']);
        return $_mod;
    }



    ## 审批相关接口实现

    /**
     * 提交审批业务关联逻辑
     * @param $dataId
     * @param \app\approve\model\审批人id串 $approverIds
     * @param \app\approve\model\当前审批人Id $currentApproverId
     * @param int $currentStep
     * @param int $approveStatus
     * @return mixed|void
     */
    public function SubmitHandle($dataId, $approverIds, $currentApproverId, $currentStep = 1, $approveStatus = 1)
    {
        $this->save(['ApproveIds' => $approverIds, 'CurrentApproverId' => $currentApproverId, 'CurrentStep' => $currentStep, 'ApproveStatus' => $approveStatus], ['id' => $dataId]);
    }

    /**
     * 获取常用审批人列表
     * @param \app\approve\model\审批提交人Id $user_id
     * @return mixed|void
     */
    public function FrequentlyUsedApprover($user_id)
    {
        $userlist = self::where(['user_id' => $user_id])->whereNotNull('ApproveIds', 'and')->field('ApproveIds')->select();
        $ids = array();
        foreach ($userlist as $item) {
            $_ids = explode(",", $item['ApproveIds']);
            foreach ($_ids as $_id) {
                if (!in_array($_id, $ids)) {
                    $ids[] = $_id;
                }
            }
        }
        $users = array();
        if (sizeof($ids) > 0) {
            $adminService = new Admin();
            $users = $adminService->whereIn('id', $ids)->field('id,nickname')->select();
        }
        return $users;
    }

    /**
     * 获取业务审批基本信息
     * @param $dataId
     * @return array|false|mixed|\PDOStatement|string|Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function GetApproveInfo($dataId)
    {
        try {
            $mod = self::where(['id' => $dataId])->field('user_id,ApproveIds,CurrentApproverId,CurrentStep')->find();
            return $mod;
        } catch (Exception $exception) {
            return null;
        }
    }

    /**
     * 表单数据完整性检测
     * @param $dataId
     * @param $currentStep
     * @return mixed|void
     */
    public function CheckBeforeSubmitOrApprove($dataId, $currentStep)
    {
        // TODO: Implement CheckBeforeSubmitOrApprove() method.
        $mod = self::get($dataId);
        $options = unserialize($mod['form_data']);
        $res = "";
        foreach ($options as $item) {
            if ($item['Step'] == $currentStep && (!empty($item['Required'])) && empty($item['Value'])) {
                $res .= $item['Required'] . " ";
            }
        }
        return trim($res, ",");
    }

    /**
     * 更新审批信息
     * @param $dataId
     * @param $currentApproveId
     * @param $currentStep
     * @param $approveStatus
     * @return mixed|void
     */
    public function UpdateApproveInfo($dataId, $currentApproveId, $currentStep, $approveStatus)
    {
        self::save([
            'CurrentApproverId' => $currentApproveId,
            'CurrentStep' => $currentStep,
            'ApproveStatus' => $approveStatus
        ], ['id' => $dataId]);
    }

}