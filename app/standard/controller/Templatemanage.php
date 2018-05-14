<?php
/**
 * Created by PhpStorm.
 * User: zhifang
 * Date: 2018/4/4
 * Time: 15:45
 */

namespace app\standard\controller;

use app\admin\controller\Permissions;
use app\standard\model\TemplateModel;
use think\Request;

class Templatemanage extends Permissions
{
    protected $templateService;

    public function __construct(Request $request = null)
    {
        parent::__construct($request);
        $this->templateService = new TemplateModel();
    }

    public function index()
    {
        return $this->fetch();
    }

    /**
     * 添加编辑模板
     * @param null $id
     * @return mixed|\think\response\Json
     */
    public function add($id = null)
    {
        if ($this->request->isAjax()) {
            $mod = input('post.');
            if (empty($mod['id'])) {
                $res = $this->templateService->allowField(true)->save($mod);
            } else {
                $res = $this->templateService->allowField(true)->save($mod, ['id' => $mod['id']]);
            }
            if ($res) {
                return json(['code' => 1, 'data' => $res]);
            } else {
                return json(['code' => -1, 'data' => $res]);
            }
        }
        $this->assign('id',$id);
        return $this->fetch();
    }

    /**
     * 获取一条记录
     * @param $id
     * @return \think\response\Json
     * @throws \think\exception\DbException
     */
    public function getone($id)
    {
        return json(TemplateModel::get($id));
    }

    /**
     * 删除模板
     * @param $id
     * @return \think\response\Json
     * @throws \think\exception\DbException
     */
    public function del($id)
    {
        $mod = TemplateModel::get($id);
        if ($mod->delete()) {
            return json(['code' => 1]);
        } else {
            return json(['code' => -1]);
        }
    }
}