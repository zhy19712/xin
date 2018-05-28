<?php
/**
 * Created by PhpStorm.
 * User: zhifang
 * Date: 2018/3/29
 * Time: 9:32
 */

namespace app\archive\model;
use think\Db;
use think\Model;
use think\exception\PDOException;

class DocumentTypeModel extends Model
{
    protected $name = 'archive_documenttype';

    public function addOrEdit($mod)
    {
        if (empty($mod['id'])) {

            $result = Db::name("archive_document")->where("type",$mod["pid"])->find();
            //当前节点下存在文件无法添加下级节点
            if(!empty($result))
            {
                return false;
            }
            $res = $this->allowField(true)->insertGetId($mod);
        } else {
            $res = $this->allowField(true)->save($mod, ['id' => $mod['id']]);
        }
        return $res ? $res : false;
    }

    /**
     * 删除节点
     * @param $id
     * @return \think\response\Json
     */
    public function del($id)
    {
        $count = $this->where(['pid' => $id])->count();
        if ($count) {
            return json(['code' => -1, 'msg' => '请先删除子节点']);
        }
        try{
            $this->where("id",$id)->delete();
            return ['code' => 1, 'msg' => '删除成功'];
        }catch(PDOException $e){
            return ['code' => -1,'msg' => $e->getMessage()];
        }
    }


    /**
     * 获取节点下所有子节点
     * @param $id
     * @return array
     * @throws \think\exception\DbException
     */
    public function getChilds($id)
    {
        $list=DocumentTypeModel::all();
        if ($list)
        {
            return $this->_getChilds($list,$id);
        }
    }
    function _getChilds($list,$id)
    {
       $nodeArray=array();
        foreach ($list as $item)
        {
            if ($item['pid']==$id){
                $nodeArray[]=$item['id'];
                $nodeArray= array_merge($nodeArray, $this->_getChilds($list,$item['id']));
            }
        }
        return $nodeArray;
    }

    /**
     * 获取文档管理表下的全部数据
     * @return false|\PDOStatement|string|\think\Collection
     */
    public function getall()
    {
        return $this->select();
    }
}