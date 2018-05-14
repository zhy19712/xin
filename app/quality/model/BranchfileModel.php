<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/4/14
 * Time: 14:12
 */
/**
 * 质量管理-分部质量管理-执行情况文件 或者 图像资料文件上传
 * Class BranchfileModel
 * @package app\quality\controller
 */
namespace app\quality\model;
use think\exception\PDOException;
use think\Model;
use think\Db;

class BranchfileModel extends Model
{
    protected $name = 'quality_subdivision_planning_file';

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
        $data = $this->where(["list_id"=>$list_id,"type"=>"1"])->find();
        return $data;
    }

    /**
     * 添加分部策划列表文件
     * @throws \think\Exception
     */
    public function insertFile($param)
    {
        try{
            $result = $this->allowField(true)->insert($param);
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
     * 删除分部策划列表文件
     * @throws \think\Exception
     */
    public function delFile($id)
    {
        try{
            $this->where("id",$id)->delete();
            return ['code' => 1, 'msg' => '删除成功'];
        }catch(PDOException $e){
            return ['code' => -1,'msg' => $e->getMessage()];
        }
    }
}