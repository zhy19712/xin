<?php
/**
 * Created by PhpStorm.
 * User: zhifang
 * Date: 2018/3/29
 * Time: 9:23
 */

namespace app\archive\model;

use think\Model;

Class DocumentModel extends Model
{
    protected $name = 'archive_document';

    public function documentType()
    {
        return $this->hasOne("DocumentTypeModel", 'id', 'type');
    }

    public function attachmentInfo()
    {
        return $this->hasOne("DocumentAttachment", "id", "attachmentId");
    }

    /**
     * 新增
     * @param $mod
     * @return array
     */
    public function add($mod)
    {
        $res = $this->allowField(true)->save($mod);
        return $res;
    }

    /**
     * 移动文档
     * @param $parms
     * @return $this
     */
    public function move($parms)
    {
        return DocumentModel::update(['type' => $parms['type']], ['id' => $parms['id']]);
    }

    /**
     * 编辑关键字
     * @param $parms
     * @return $this
     */
    public function remark($parms)
    {
        return DocumentModel::update(['remark' => $parms['remark']], ['id' => $parms['id']]);
    }

    /**
     * 删除文档
     * @param $par
     * @return int
     * @throws \think\exception\DbException
     */
    public function deleteDoc($par)
    {

        $mod = DocumentModel::get($par, 'attachmentInfo');
        if (file_exists($mod['attachment_info']['filepath'])) {
            unlink($mod['attachment_info']['filepath']); //删除上传的图片
        }
        return $mod->delete();
    }

    /**
     * 用户是否含有指定文档的权限
     * @param $docUsers
     * @param $userId
     * @return bool
     */
    public function havePermission($docUsers, $userId)
    {
        if (empty($docUsers))
        {
            return true;
        }
        if (in_array($userId, explode($docUsers, "|"))) {
            return true;
        }
        return false;
    }

    /**
     * 获取一条信息
     */
    public function getOne($id)
    {
        $data = $this->where('id', $id)->find();
        return $data;
    }
}

Class DocumentAttachment extends Model
{
    protected $name = 'attachment';
}