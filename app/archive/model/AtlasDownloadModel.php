<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/4/2
 * Time: 14:20
 */
/*
 * 记录下载信息
 * @package app\archive\model
 */
namespace app\archive\model;
use think\exception\PDOException;
use \think\Model;

class AtlasDownloadModel extends Model
{
    protected $name='archive_atlas_download_record';

    /**
     * 新增下载记录
     * @param $param
     * @return array
     */
    public function insertDownload($param)
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
     * 获取该条图册的所有的下载记录信息
     * @return array|false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getall($id)
    {
        try{
            $data = $this->where("cate_id",$id)->select();
            return $data;
        }catch (PDOException $e){
            return ['code' => -1,'msg' => $e->getMessage()];
        }

    }

    /**
     * 清除该条图册的所有的下载记录信息
     * @return array|false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function deldownloadall($id)
    {
        try{
            $data = $this->where("cate_id",$id)->delete();
            return $data;
        }catch (PDOException $e){
            return ['code' => -1,'msg' => $e->getMessage()];
        }

    }

    /**
     * 根据类型树id清除该条图册的所有的下载记录信息
     * @return array|false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function delselfidall($selfid)
    {
        try{
            $data = $this->where("selfid",$selfid)->delete();
            return $data;
        }catch (PDOException $e){
            return ['code' => -1,'msg' => $e->getMessage()];
        }

    }
}