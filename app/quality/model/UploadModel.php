<?php
/**
 * Created by PhpStorm.
 * User: zhifang
 * Date: 2018/4/16
 * Time: 9:59
 */

/**
 * 分部管控、单位管控中的控制点文件上传
 */
namespace app\quality\model;
use think\exception\PDOException;
use think\Model;

class UploadModel extends Model
{
    protected $name = 'quality_upload';

    /**
     * 添加上传文件
     * @param $param
     * @return array
     */
    public function insertTb($param)
    {
        try {
            $result = $this->allowField(true)->save($param);
            if (false === $result) {
                return ['code' => -1, 'msg' => $this->getError()];
            } else {
                return ['code' => 1, 'data' => [], 'msg' => '添加成功'];
            }
        } catch (PDOException $e) {
            return ['code' => -1, 'msg' => $e->getMessage()];
        }
    }

    /**
     * 批量添加上传文件
     * @param $param
     * @return array
     */
    public function insertTbAll($param)
    {
        try {
            $result = $this->allowField(true)->insertAll($param);
            if (false === $result) {
                return ['code' => -1, 'msg' => $this->getError()];
            } else {
                return ['code' => 1, 'data' => [], 'msg' => '添加成功'];
            }
        } catch (PDOException $e) {
            return ['code' => -1, 'msg' => $e->getMessage()];
        }
    }

    /**
     * 删除上传文件
     * @param $id
     * @return array
     */
    public function delTb($id)
    {
        try{
            $this->where("id",$id)->delete();
            return ['code' => 1, 'msg' => '删除成功'];
        }catch(PDOException $e){
            return ['code' => -1,'msg' => $e->getMessage()];
        }
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
     * 根据分部策划列表id查询表中是否还有数据
     */
    public function judge($list_id)
    {
        $data = $this->where(["contr_relation_id"=>$list_id,"type"=>3])->find();
        return $data;
    }
}