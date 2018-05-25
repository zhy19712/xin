<?php
/**
 * Created by PhpStorm.
 * User: sir
 * Date: 2018/5/22
 * Time: 13:36
 */
namespace app\modelmanagement\model;

use app\quality\model\DivisionModel;
use think\Db;
use think\exception\PDOException;
use think\Model;
class QualitymassModel extends Model
{
    protected $name = 'model_quality';


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

    public function editTb($param)
    {
        try {
            $result = $this->allowField(true)->save($param, ['id' => $param['id']]);
            if (false === $result) {
                return ['code' => -1, 'msg' => $this->getError()];
            } else {
                return ['code' => 1, 'msg' => '编辑成功'];
            }
        } catch (PDOException $e) {
            return ['code' => 0, 'msg' => $e->getMessage()];
        }
    }

    public function deleteTb($id)
    {
        try {
            $this->where('id', $id)->delete();
            return ['code' => 1, 'msg' => '删除成功'];
        } catch (PDOException $e) {
            return ['code' => -1, 'msg' => $e->getMessage()];
        }
    }

    public function getOne($id)
    {
        $data = $this->find($id);
        return $data;
    }

    public function getSection()
    {
        $data = $this->group('section')->field('section')->select();
        return $data;
    }

    public function getUnit()
    {
        $data = $this->group('unit')->field('unit')->select();
        return $data;
    }

    public function getParcel()
    {
        $data = $this->group('parcel')->field('parcel')->select();
        return $data;
    }

    public function getCell()
    {
        $data = $this->group('cell')->field('cell')->select();
        return $data;
    }

    public function pile_number_1()
    {
        $data = $this->group('pile_number_1')->field('pile_number_1')->select();
        return $data;
    }


    public function pile_number_2()
    {
        $data = $this->group('pile_number_2')->field('pile_number_2')->select();
        return $data;
    }

    public function pile_number_3()
    {
        $data = $this->group('pile_number_3')->field('pile_number_3')->select();
        return $data;
    }

    public function pile_number_4()
    {
        $data = $this->group('pile_number_4')->field('pile_number_4')->select();
        return $data;
    }

    // 解除所有的关联关系
    public function removeRelevance($id_arr)
    {
        $this->where(['id'=>['in',$id_arr]])->update(['unit_id'=>0]);
        return ['code'=>1,'msg'=>'解除成功'];
    }

    public function relevance($id,$id_arr)
    {
        // 关联
        $this->where(['id'=>['in',$id_arr]])->update(['unit_id'=>$id]);
        return ['code'=>1,'msg'=>'关联成功'];
    }

    public function removeVersionsRelevance($version_number)
    {
        $this->where(['version_number'=>['eq',$version_number]])->delete();
        return ['code'=>1,'msg'=>'删除成功'];
    }

//    public function nodeModelNumber($model_type,$add_id,$node_type)
//    {
//        $unit_id = [];
//        // 节点的类型 node_type 1 顶级节点 2 标段 3 工程划分节点 4 单元工程段号(检验批编号)
//
//        $version = new VersionsModel();
//        $version_number = $version->statusOpen($model_type); // 当前启用的版本号
//
//        if($node_type == 1){
//            $model_id = $this->where(['version_number'=>$version_number,'unit_id'=>['neq',0]])->column('model_id');
//            return $model_id;
//        }else if($node_type == 2){
//            // 获取选中标段下包含的所有节点编号
//            $division_id = Db::name('quality_division')->where(['section_id'=>$add_id])->column('id');
//            // 获取节点下包含的所有单元工程检验批
//            $unit_id = Db::name('quality_unit')->where(['division_id'=>['in',$division_id]])->column('id');
//        }else if($node_type == 3){
//            // 获取选中节点下包含的所有子节点编号
//            $division = new DivisionModel();
//            $child_node_id = [];
//            $child_node_obj = $division->cateTree($add_id);
//            foreach ($child_node_obj as $v){
//                $child_node_id[] = $v['id'];
//            }
//            $child_node_id[] = $add_id;
//            // 获取此节点下包含的所有单元工程检验批
//            $unit_id = Db::name('quality_unit')->where(['division_id'=>['in',$child_node_id]])->column('id');
//        }else if($node_type == 4){
//            $unit_id[] = $add_id;
//        }
//        // 获取所有单元工程检验批 所关联的模型编号
//        $model_id = [];
//        if(sizeof($unit_id)){
//            $model_id = $this->where(['version_number'=>$version_number,'unit_id'=>['in',$unit_id]])->column('model_id');
//        }
//        return $model_id;
//    }


    // 选中节点的 add_id  和 节点的类型 node_type 1 顶级节点 2 标段 3 工程划分节点 4 单元工程段号(检验批编号)
    public function qualityNodeInfo($add_id,$node_type)
    {
        $un_evaluation = $unqualified = $qualified = $excellent =[-1];

        $version = new VersionsModel();
        $version_number = $version->statusOpen(2); // 当前启用的版本号 1 全景3D模型(竣工模型) 和 2 质量模型(施工模型)

        if($node_type == 1){
            $total = Db::name('quality_unit')->field('id,EvaluateResult')->select(); // 获取节点下包含的所有单元工程检验批
            foreach ($total as $v){
                if($v['EvaluateResult'] == 0){
                    $un_evaluation[] = $v['id']; // 未验评
                }else if($v['EvaluateResult'] == 1){
                    $unqualified[] = $v['id']; // 不合格
                }else if($v['EvaluateResult'] == 2){
                    $qualified[] = $v['id']; // 合格
                }else if($v['EvaluateResult'] == 3){
                    $excellent[] = $v['id']; // 优良
                }
            }
             // 根据检验批获取 每个验评结果下包含的 模型的编号
            $data['un_evaluation'] = $this->where(['version_number'=>$version_number,'unit_id'=>['in',$un_evaluation]])->column('model_id'); // 未验评
            $data['unqualified'] = $this->where(['version_number'=>$version_number,'unit_id'=>['in',$unqualified]])->column('model_id'); // 不合格
            $data['qualified'] = $this->where(['version_number'=>$version_number,'unit_id'=>['in',$qualified]])->column('model_id'); // 合格
            $data['excellent'] = $this->where(['version_number'=>$version_number,'unit_id'=>['in',$excellent]])->column('model_id'); // 优良
            $data['all'] = $this->where(['version_number'=>$version_number])->column('model_id'); // 所有
            return $data;
        }else if($node_type == 2){
            // 获取选中标段下包含的所有节点编号
            $division_id = Db::name('quality_division')->where(['section_id'=>$add_id])->column('id');
            // 获取节点下包含的所有单元工程检验批
            $un_evaluation = Db::name('quality_unit')->where(['EvaluateResult'=>0,'division_id'=>['in',$division_id]])->column('id'); // 未验评
            $unqualified = Db::name('quality_unit')->where(['EvaluateResult'=>1,'division_id'=>['in',$division_id]])->column('id'); // 不合格
            $qualified = Db::name('quality_unit')->where(['EvaluateResult'=>2,'division_id'=>['in',$division_id]])->column('id'); // 合格
            $excellent = Db::name('quality_unit')->where(['EvaluateResult'=>3,'division_id'=>['in',$division_id]])->column('id'); // 优良
        }else if($node_type == 3){
            // 获取选中节点下包含的所有子节点编号
            $division = new DivisionModel();
            $child_node_id = [];
            $child_node_obj = $division->cateTree($add_id);
            foreach ($child_node_obj as $v){
                $child_node_id[] = $v['id'];
            }
            $child_node_id[] = $add_id;
            // 获取此节点下包含的所有单元工程检验批
            $un_evaluation = Db::name('quality_unit')->where(['EvaluateResult'=>0,'division_id'=>['in',$child_node_id]])->column('id'); // 未验评
            $unqualified = Db::name('quality_unit')->where(['EvaluateResult'=>1,'division_id'=>['in',$child_node_id]])->column('id'); // 不合格
            $qualified = Db::name('quality_unit')->where(['EvaluateResult'=>2,'division_id'=>['in',$child_node_id]])->column('id'); // 合格
            $excellent = Db::name('quality_unit')->where(['EvaluateResult'=>3,'division_id'=>['in',$child_node_id]])->column('id'); // 优良
        }else if($node_type == 4){
            $unit_data = Db::name('quality_unit')->where(['id'=>$add_id])->field('id,EvaluateResult')->find();
            if($unit_data['EvaluateResult'] == 0){
                $un_evaluation[] = $unit_data['id']; // 未验评
            }else if($unit_data['EvaluateResult'] == 1){
                $unqualified[] = $unit_data['id']; // 不合格
            }else if($unit_data['EvaluateResult'] == 2){
                $qualified[] = $unit_data['id']; // 合格
            }else if($unit_data['EvaluateResult'] == 3){
                $excellent[] = $unit_data['id']; // 优良
            }
        }
        // 获取所有单元工程检验批 所关联的模型编号
        $data['un_evaluation'] = $this->where(['version_number'=>$version_number,'unit_id'=>['in',$un_evaluation]])->column('model_id'); // 未验评
        $data['unqualified'] = $this->where(['version_number'=>$version_number,'unit_id'=>['in',$unqualified]])->column('model_id'); // 不合格
        $data['qualified'] = $this->where(['version_number'=>$version_number,'unit_id'=>['in',$qualified]])->column('model_id'); // 合格
        $data['excellent'] = $this->where(['version_number'=>$version_number,'unit_id'=>['in',$excellent]])->column('model_id'); // 优良
        $data['all'] = $this->where(['version_number'=>$version_number])->column('model_id'); // 所有
        return $data;
    }

    public function prevRelevance($version_number)
    {
        $data = $this->where(['version_number'=>$version_number])->field('model_name,unit_id')->select();
        return $data;
    }

    /**
     * 根据模型的编号model_id查询关联的xin_quality_unit单元工程表中的信息
     * @param $model_id
     * @return array|false|\PDOStatement|string|Model
     * @throws \think\exception\DbException
     */
    public function getUnitInfo($model_id)
    {
        $unit_info = Db::name('model_quality')->alias('q')
            ->join('quality_unit u', 'q.unit_id = u.id', 'left')
            ->where("q.model_id",$model_id)
            ->field("u.site,u.coding,u.hinge,u.quantities,u.ma_bases,u.su_basis,u.el_start,u.el_cease,u.pile_number,u.start_date,u.completion_date,u.en_type,u.division_id,u.id")->find();
        return $unit_info;
    }

    /**
     * 根据归属工程en_type编号查询工序号
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\exception\DbException
     */
    public function getProcessInfo($en_type)
    {
        $processinfo = Db::name("norm_materialtrackingdivision")->alias('a')
            ->where(["pid"=>$en_type,"type"=>3,"cat"=>5])
            ->field("a.id,a.name")
            ->order("sort_id asc")
            ->select();
        return $processinfo;
    }

}