<?php
/**
 * Created by PhpStorm.
 * User: zhifang
 * Date: 2018/3/29
 * Time: 9:23
 */

namespace app\archive\model;
use think\exception\PDOException;
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
    public function add($param)
    {
        try{
            $result = $this->allowField(true)->save($param);
            if(false === $result){
                return ['code' => -1,'msg' => $this->getError()];
            }else{
                return ['code' => 1,'msg' => '添加成功'];
            }
        }catch (PDOException $e){
            return ['code' => -1,'msg' => $e->getMessage()];
        }
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

    /**
     * 根据左侧的树节点判断当前节点下是否存在文件
     */
    public function judge($id)
    {
        $data = $this->where('type', $id)->find();
        return $data;
    }

    /**
     * 根据传过来的文档表xin_archive_document表的id,admin表中的admin_id,
     */
    public function delblacklist($param)
    {

        //查询白名单中的用户id
        $users = $this->field("users")->where("id",$param['id'])->find();

        if($users["users"])
        {
            $list = explode("|",$users["users"]);


            foreach($list as $k=>$v)
            {
                if($v == $param['admin_id'])
                {
                    unset($list[$k]);
                }
            }

        }

        if($list)
        {
            $str = implode("|",$list);

        }else
        {
            $str = "";
        }


        //把处理过得数据重新插入数组中
        $result = $this->allowField(true)->save(['users'=>$str],['id' => $param['id']]);

        if($result)
        {
            return ['code' => 1,'msg' => "删除成功"];
        }else{
            return ['code' => -1,'msg' => "删除失败"];
        }

    }

    /**
     * 查询当前图册下的被禁用的白名单
     */
    public function getbalcklist($id)
    {
        $data = $this->field("users")->where("id",$id)->find();
        return $data;
    }

    /**
     * 根据节点id查文档表的信息
     *
     */
    public function getpicinfo($id)
    {
        $data = $this->field("attachmentId")->where("type",$id)->select();
        return $data;
    }

    /*
     * 根据节点的id删除文档
     */
    public function delselfidCate($id)
    {
        try{
            $this->where("type",$id)->delete();
            return ['code' => 1, 'msg' => '删除成功'];
        }catch(PDOException $e){
            return ['code' => -1,'msg' => $e->getMessage()];
        }
    }

    /*
     * 根据文档删除一条文档记录
     */
    public function delCate($id)
    {
        try{
            $this->where("id",$id)->delete();
            return ['code' => 1, 'msg' => '删除成功'];
        }catch(PDOException $e){
            return ['code' => -1,'msg' => $e->getMessage()];
        }
    }

    /**
     * 编辑一条文档信息
     */
    public function editCate($param)
    {
        try{
            $result = $this->allowField(true)->saveAll($param);
            if(false === $result){
                return ['code' => -1,'msg' => $this->getError()];
            }else{
                return ['code' => 1, 'msg' => '编辑成功'];
            }
        }catch(PDOException $e){
            return ['code' => 0, 'msg' => $e->getMessage()];
        }
    }
}

Class DocumentAttachment extends Model
{
    protected $name = 'attachment';
}