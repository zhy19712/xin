<?php
/**
 * Created by PhpStorm.
 * User: sir
 * Date: 2018/4/11
 * Time: 14:19
 */

namespace app\quality\model;


use think\Db;
use think\exception\PDOException;
use think\Model;

class UnitqualitymanageModel extends Model
{
    protected $name = 'quality_division_controlpoint_relation';

    /**
     * @param $add_id int 节点编号
     * @param $ma_division_id int 工序编号
     * @param $id int 关联表记录编号
     * @return array
     * @throws \think\Exception
     * @author hutao
     */
    public function associationDeletion($add_id,$ma_division_id,$id)
    {
        try{
            /**
             * 删除控制点 注意: 已经执行 的控制点 不能删除
             *
             * 控制点 存在于 quality_division_controlpoint_relation 中
             * quality_division_controlpoint_relation 里包含 新增控制点时 关联添加的对应关系
             * 和 在 单位策划里 后来 新增控制点 时 追加的 对应关系
             *
             * 如果关系记录存在 该控制点 那么就应该先
             * 要关联 删除 记录里的控制点执行情况 和 图像资料  以及它们所包含的文件 以及 预览的pdf文件
             * 最后 删除 这条关系记录
             *
             * type 类型:1 检验批 0 工程划分
             */
            if($id == 0){ // 全部删除
                $relation_id = $this->where(['division_id'=>$add_id,'ma_division_id'=>$ma_division_id,'type'=>0])->column('id');
            }else{
                $relation_id = [$id];
            }

            if(sizeof($relation_id) < 1){
                return ['code' => -1,'msg' => '没有数据可删除!'];
            }

            // 已经执行 的控制点 不能删除
            $status = $this->where(['division_id'=>$add_id,'ma_division_id'=>$ma_division_id,'control_id'=> ['in',$relation_id],'type'=>0,'status'=>1 ])->count();
            if($status > 0){
                return ['code' => -1,'msg' => '已执行控制点:不能删除!'];
            }
            if(is_array($relation_id) && sizeof($relation_id)){
                $data = Db::name('quality_upload')->whereIn('contr_relation_id',$relation_id)->column('id,attachment_id');
                if(is_array($data) && sizeof($data)){
                    $id_arr = array_keys($data);
                    $attachment_id_arr = array_values($data);
                    $att = Db::name('attachment')->whereIn('id',$attachment_id_arr)->column('filepath');
                    foreach ($att as $v){
                        $pdf_path = './uploads/temp/' . basename($v) . '.pdf';
                        if(file_exists($v)){
                            unlink($v); //删除文件
                        }
                        if(file_exists($pdf_path)){
                            unlink($pdf_path); //删除生成的预览pdf
                        }
                    }
                    Db::name('attachment')->delete($attachment_id_arr);
                    Db::name('quality_upload')->delete($id_arr);
                }
            }
            Db::name('quality_division_controlpoint_relation')->delete($relation_id);
            return ['code' => 1, 'msg' => '删除成功'];
        }catch(PDOException $e){
            return ['code' => -1,'msg' => $e->getMessage()];
        }
    }

    public function getNodeInfo()
    {
        // cat = 2 只取单位工程工序树
        $result = Db::name('norm_materialtrackingdivision')->where('cat',2)->column('id,pid,name');
        $str = "";
        foreach($result as $key=>$vo){
            $str .= '{ "id": "' . $vo['id'] . '", "pId":"' . $vo['pid'] . '", "name":"' . $vo['name'].'"' . ',"add_id":"'.$vo['id'].'"';
            $str .= '},';
        }
        return "[" . substr($str, 0, -1) . "]";
    }

    public function insertTb($param)
    {
        try{
            $result = Db::name('quality_division_controlpoint_relation')->insertAll($param);
            if($result > 0){
                return ['code' => 1,'msg' => '添加成功'];
            }else{
                return ['code' => -1,'msg' => $this->getError()];
            }
        }catch (PDOException $e){
            return ['code' => -1,'msg' => $e->getMessage()];
        }
    }

    public function saveTb($param)
    {
        try{
            $result = Db::name('quality_upload')->insert($param);
            if($result > 0){
                $status = $this->where('id',$param['contr_relation_id'])->value('status');
                if($status == 0){
                    $this->where('id',$param['contr_relation_id'])->update(['status'=>1]);
                }
                return ['code' => 1,'msg' => '添加成功'];
            }else{
                return ['code' => -1,'msg' => $this->getError()];
            }
        }catch (PDOException $e){
            return ['code' => -1,'msg' => $e->getMessage()];
        }
    }

    /**
     * 先删除 控制点执行情况文件 或者 图像资料文件
     * 最后 删除 文件记录信息
     * @param $id
     * @return array
     * @throws \think\Exception
     * @author hutao
     */
    public function deleteTb($id)
    {
        try{
            $q_obj = Db::name('quality_upload')->where('id',$id)->field('contr_relation_id,attachment_id')->find();
            $filePath = Db::name('attachment')->where('id',$q_obj['attachment_id'])->column('filepath');
            if(sizeof($filePath) > 0){
                if(file_exists($filePath[0])){
                    unlink($filePath[0]); //删除文件
                }
                $pdf_path = './uploads/temp/' . basename($filePath[0]) . '.pdf';
                if(file_exists($pdf_path)){
                    unlink($pdf_path); //删除生成的预览pdf
                }
            }
            Db::name('attachment')->where('id',$q_obj['attachment_id'])->delete();

            // 如果扫描件被删除了，且没有已经完成审批的在线填报表单，就修改状态为 未执行

            $copy = Db::name('quality_upload')
                ->where(['contr_relation_id'=>$q_obj['contr_relation_id'],'type'=>1])
                ->find();
            $relation=Db::name('quality_division_controlpoint_relation')
                ->where(['id'=>$copy['contr_relation_id']])
                ->find();
            $fm_info=Db::name('quality_form_info')
                ->where(['ControlPointId'=>$relation['control_id'],'DivisionId'=>$relation['division_id'],'ApproveStatus'=>2])
                ->find();

            //判断是否是质量验评控制点
            $search_name='单元工程质量等级评定表';
            $key_controlpoint=Db::name('norm_controlpoint')
                ->where(['id'=>$relation['control_id']])
                ->where('name','like','%'.$search_name.'%')
                ->count();

            Db::name('quality_upload')->where('id',$id)->delete();
            //对扫描件进行计数
            $num = Db::name('quality_upload')
                ->where(['contr_relation_id'=>$q_obj['contr_relation_id'],'type'=>1])
                ->count();
            if(($num== 0)&&(count($fm_info))==0)
            {
                $this->where('id',$q_obj['contr_relation_id'])->update(['status'=>0]);
                // 如果删除的扫描件是单元工程验评控制点下的扫描件，且该扫描件没有已完成审批的在线填报信息，还需要初始化unit表中的验评结果和日期
                if($key_controlpoint>0)
                {
                    Db::name('quality_unit')
                        ->where(['id'=>$relation['division_id']])
                        ->update(['EvaluateResult'=>0,'EvaluateDate'=>0]);
                }
            }
            return ['code' => 1, 'msg' => '删除成功'];
        }catch(PDOException $e){
            return ['code' => -1,'msg' => $e->getMessage()];
        }
    }

}