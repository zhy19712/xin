<?php
/**
 * Created by PhpStorm.
 * User: sir
 * Date: 2018/5/22
 * Time: 11:00
 */

namespace app\approve\controller;


use app\admin\controller\Permissions;
use app\quality\model\SendModel;

/**
 * 收文 -- 签收 和 拒收
 * Class Income
 * @package app\approve\controller
 */
class Income extends Permissions
{
    public function send()
    {
        if($this->request->isAjax()){
            // 前台需要传递的参数有:
            // status 3 签收 4 拒收
            // 当编辑收发文时,传递 主键编号 major_key

            $param = input('param.');
            // 验证规则
            $rule = [
                ['major_key', 'require', '请选择文件'],
                ['status', 'require|between:1,4', '请传递文件状态|文件状态不能大于4']
            ];
            $validate = new \think\Validate($rule);
            //验证部分数据合法性
            if (!$validate->check($param)) {
                return json(['code' => -1,'msg' => $validate->getError()]);
            }

            $send = new SendModel();
            $flag = $send->editTb($param);
            return json($flag);
        }
    }

}