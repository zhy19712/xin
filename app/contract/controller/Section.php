<?php
/**
 * Created by PhpStorm.
 * User: zhifang
 * Date: 2018/3/23
 * Time: 16:13
 */

namespace app\contract\controller;

use app\admin\controller\Permissions;
use app\admin\model\AdminGroup;
use app\contract\model\SectionModel;
use think\Db;

class Section extends Permissions
{

    public function index()
    {
        return $this->fetch();
    }

    /**
     * 标段——新增
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function add()
    {
        if (empty(input('id'))) {
            $id = "";
        } else {
            $id = input('id');
        }
        $orgs = AdminGroup::all(['category' => 1]);
        $this->assign('orgs', json_encode($orgs));
        $this->assign('id', $id);
        return $this->fetch();
    }

    /**
     * 标段——获取
     * @return \think\response\Json
     * @throws \think\exception\DbException
     */
    public function getOne()
    {
        $m = SectionModel::get(input('id'));
        return json($m);
    }

    /**
     * 标段——新增或修改
     * @return array
     */
    public function addoredit()
    {
        if ($this->request->isAjax()) {
            try {
                $mod = input('post.');
                $m = new SectionModel();
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
     * 标段——删除
     * @return array
     */
    public function del()
    {
        if ($this->request->isAjax()) {
            try {
                $id = input('id');
                $res = SectionModel::destroy($id);
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