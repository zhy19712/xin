<?php
/**
 * Created by PhpStorm.
 * User: zhifang
 * Date: 2018/3/23
 * Time: 13:52
 */

namespace app\contract\controller;

use app\admin\controller\Permissions;
use app\contract\model\ContractModel;
use think\Db;
use think\Exception;
use think\Request;

class Contract extends Permissions

{
    public function index()
    {
        return $this->fetch();
    }

    public function getAll()
    {
        $m=new ContractModel();
        $list= $m->field('id,contractName')->select();
        return json($list);
    }

    public function getOne()
    {
        $m=ContractModel::get(input('id'));
        return json($m);
    }
    /**
     * 视图——合同——添加
     * @return mixed
     */
    public function add()
    {
        return $this->fetch();
    }

    /**
     * 合同——新增或修改
     * @return array
     */
    public function addoredit()
    {
        if ($this->request->isAjax()) {
            try {
                $mod = input('post.');
                $m = new ContractModel();
                $res = $m->AddOrEdit($mod);
                if ($res) {
                    return ['code' => 1];
                } else {
                    return ['code' => -1];
                }
            } catch (Exception $e) {
                return ['code' => -1, 'msg' => $e->getMessage()];
            }
        }
    }

    /**
     * 合同——删除合同
     * @return array
     */
    public function del()
    {
        if ($this->request->isAjax()) {
            try {
                $id = input('id');
                $res = ContractModel::destroy($id);
                if ($res) {
                    return ['code' => 1];
                } else {
                    return ['code' => -1];
                }
            } catch (Exception $e) {
                return ['code' => -1, 'msg' => $e->getMessage()];
            }
        }

    }
}