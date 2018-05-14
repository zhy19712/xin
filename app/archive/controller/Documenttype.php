<?php
/**
 * Created by PhpStorm.
 * User: zhifang
 * Date: 2018/3/29
 * Time: 13:51
 */

namespace app\archive\controller;

use app\admin\controller\Permissions;
use app\archive\model\DocumentTypeModel;
use think\Request;

class Documenttype extends Permissions
{
    /**
     * 获取树
     * @return \think\response\Json
     * @throws \think\exception\DbException
     */
    public function getAll()
    {
        return json(DocumentTypeModel::all());
    }

    protected $documentTypeService;

    public function __construct(Request $request = null)
    {
        parent::__construct($request);
        $this->documentTypeService = new DocumentTypeModel();
    }

    /**
     * 添加或修改
     * @return \think\response\Json
     */
    public function addOrEdit()
    {
        $m = input('post.');
        $res=$this->documentTypeService->addOrEdit($m);
        if ($res) {
            return json(['code' => 1,'data'=>$res]);
        } else {
            return json(['code' => -1]);
        }
    }

    /**
     * 删除节点
     * @return \think\response\Json
     */
    public function del()
    {
        return $this->documentTypeService->del(input('id'));
    }
}