<?php
/**
 * Created by PhpStorm.
 * User: sir
 * Date: 2018/4/11
 * Time: 14:19
 */
/**
 * 质量管理-分部质量管理
 * Class BranchModel
 * @package app\quality\controller
 */
namespace app\quality\model;
use think\exception\PDOException;
use think\Model;
use think\Db;

class BranchModel extends Model
{
    protected $name = 'quality_subdivision_planning_list';

    /**
     * 获取一条信息
     */
    public function getOne($id)
    {
        $data = $this->where('id', $id)->find();
        return $data;
    }

    /**
     * 根据所属工序号查询所有的分部策划列表中的id
     */
    public function getAllid($selfid,$procedureid)
    {
        $data = $this->field("id")->where(['selfid'=>$selfid,'procedureid'=>$procedureid])->select();
        return $data;
    }

    /**
     * 根据节点id和工序id查询已执行的数量
     */
    public function getAllcount($selfid,$procedureid)
    {
        $data = $this->where(['selfid'=>$selfid,'procedureid'=>$procedureid])->where("status > 0")->count();
        return $data;
    }

    /**
     * 添加分部策划列表
     * @throws \think\Exception
     */
    public function insertSu($param)
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
     * 编辑一条信息
     */
    public function editSu($param)
    {
        try{
            $result = $this->allowField(true)->save($param,['id' => $param['id']]);
            if(false === $result){
                return ['code' => -1,'msg' => $this->getError()];
            }else{
                return ['code' => 1, 'msg' => '编辑成功'];
            }
        }catch(PDOException $e){
            return ['code' => 0, 'msg' => $e->getMessage()];
        }
    }

    /**
     * 删除分部策划列表
     * @throws \think\Exception
     */
    public function associationDeletion($id)
    {
        try{
            /**
             * 删除控制点 注意: 已经执行 的控制点 不能删除
             * 控制点 存在于 controlpoint 表 和 fengning_quality_subdivision_planning_list 中
             * controlpoint 表里的数据 是原始的，fengning_quality_subdivision_planning_list 是 在 分部策划里 后来 新增的关系记录
             *
             * 如果关系记录存在 该控制点 那么就应该先
             * 要关联 删除 记录里的控制点执行情况 和 图像资料  以及它们所包含的文件 以及 预览的pdf文件
             * 然后 删除 这条关系记录
             * 其他工序下的控制点 是 根据 quality_division_controlpoint_relation 对应关系表 关联 controlpoint 的数据
             * 可以新增，删除，全部删除，删除的是 对应关系表里的对应信息，不是真正的删除controlpoint里的数据
             * 最后 删除 原始数据
             *
             * type 类型:1 检验批 0 工程划分
             */
                $data = Db::name('quality_subdivision_planning_file')->where('list_id',$id)->column('id,attachment_id');
                if(!empty($data)){
                    $attachment_id_arr = array_values($data);
                    $att = Db::name('attachment')->whereIn('id',$attachment_id_arr)->column('filepath');
                    foreach ($att as $v){
                        $pdf_path = './uploads/temp/' . basename($v) . '.pdf';
                        if(file_exists('.' .$v)){
                            unlink('.' .$v); //删除文件
                        }
                        if(file_exists($pdf_path)){
                            unlink($pdf_path); //删除生成的预览pdf
                        }
                    }
                    Db::name('attachment')->whereIn('id',$attachment_id_arr)->delete();
                    $this->where("id",$id)->delete();
                }else
                {
                    $this->where("id",$id)->delete();
                }
            return ['code' => 1, 'msg' => '删除成功'];
        }catch(PDOException $e){
            return ['code' => -1,'msg' => $e->getMessage()];
        }
    }

    /**
     * 判断是否有当前添加的节点
     * @param $selfid
     * @param $procedureid
     * @param $procedureid
     */
    public function getid($selfid,$procedureid,$id)
    {
        $data = $this->where(["selfid"=>$selfid,"procedureid"=>$procedureid,"controller_point_id"=>$id])->find();
        return $data;
    }
}