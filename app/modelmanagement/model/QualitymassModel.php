<?php
/**
 * Created by PhpStorm.
 * User: sir
 * Date: 2018/5/22
 * Time: 13:36
 */
namespace app\modelmanagement\model;

use app\admin\model\AdminGroup;
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

    // 解除关联
    public function removeRelevance($id_arr,$type=0)
    {
        $version = new VersionsModel();
        $version_number = $version->statusOpen(2); // 当前启用的版本号 1 全景3D模型(竣工模型) 和 2 质量模型(施工模型)
        if($type==0){
            $this->where(['id'=>['in',$id_arr],'version_number'=>$version_number])->update(['unit_id'=>0]);
        }else{
            $actual_id = $this->where(['id'=>['eq',$id_arr[0]],'version_number'=>$version_number])->value('actual_id');
            $this->where(['id'=>['in',$id_arr],'version_number'=>$version_number])->update(['actual_id'=>0]);
            return ['code'=>1,'actual_id'=>$actual_id,'msg'=>'解除成功'];
        }
        return ['code'=>1,'msg'=>'解除成功'];
    }

    // ht 获取所有选中的构件的标段
    public function sectionCode($id_arr)
    {
        $version = new VersionsModel();
        $version_number = $version->statusOpen(2); // 当前启用的版本号 1 全景3D模型(竣工模型) 和 2 质量模型(施工模型)
        $section = $this->where(['id'=>['in',$id_arr],'version_number'=>$version_number])->column('section');
        return $section;
    }


    // 关联
    public function relevance($id,$id_arr,$type=0)
    {
        $version = new VersionsModel();
        $version_number = $version->statusOpen(2); // 当前启用的版本号 1 全景3D模型(竣工模型) 和 2 质量模型(施工模型)
        if($type==0){
            $this->where(['id'=>['in',$id_arr],'version_number'=>$version_number])->update(['unit_id'=>$id]);
        }else{
            $this->where(['id'=>['in',$id_arr],'version_number'=>$version_number])->update(['actual_id'=>$id]);
        }
        return ['code'=>1,'msg'=>'关联成功'];
    }

    // 解除该版本的关联关系
    public function removeVersionsRelevance($version_number)
    {
        $this->where(['version_number'=>['eq',$version_number]])->delete();
        return ['code'=>1,'msg'=>'删除成功'];
    }


    // 显示或隐藏 -- 所有关联模型编号
    public function concealment($add_id,$node_type)
    {
        // 节点的类型 node_type 1 顶级节点 2 标段 3 工程划分节点 4 单元工程段号(检验批编号)
        $unit_id = [];
        $version = new VersionsModel();
        $version_number = $version->statusOpen(2); // 当前启用的版本号
        if($node_type == 1){
            $model_id = $this->where(['version_number'=>$version_number,'unit_id'=>['neq',0]])->column('model_id');
            return $model_id;
        }else if($node_type == 2){
            // 获取选中标段下包含的所有节点编号
            $division_id = Db::name('quality_division')->where(['section_id'=>$add_id])->column('id');
            // 获取节点下包含的所有单元工程检验批
            $unit_id = Db::name('quality_unit')->where(['division_id'=>['in',$division_id]])->column('id');
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
            $unit_id = Db::name('quality_unit')->where(['division_id'=>['in',$child_node_id]])->column('id');
        }else if($node_type == 4){
            $unit_id[] = $add_id;
        }
        // 获取所有单元工程检验批 所关联的模型编号
        $model_id = [];
        if(sizeof($unit_id)){
            $model_id = $this->where(['version_number'=>$version_number,'unit_id'=>['in',$unit_id]])->column('model_id');
        }
        return $model_id;
    }


    // 前台 传递 选中节点的 number  和 编号的类型 number_type 1 单元工程段号(检验批编号) 2 模型编号
    public function qualityNodeInfo($number,$number_type)
    {
        $version = new VersionsModel();
        $version_number = $version->statusOpen(2); // 当前启用的版本号 1 全景3D模型(竣工模型) 和 2 质量模型(施工模型)
        if($number_type == 1){
            $data['unit_id'] = $number; // 单元工程编号
            $data['model_id'] = $this->where(['version_number'=>$version_number,'unit_id'=>$number])->column('model_id'); // 所有关联模型编号
            $data['model_type'] = Db::name('quality_unit')->where(['id'=>$number])->value('EvaluateResult'); // 验评结果：0未验评，1不合格，2合格，3优良
            return $data;
        }else{
            $data['unit_id'] = $this->where(['version_number'=>$version_number,'model_id'=>$number])->value('unit_id'); // 单元工程编号
            if($data['unit_id'] == 0){
                $data['model_id'] = []; // 没有关联 单元工程 的模型
            }else{
                $data['model_id'] =  $this->where(['version_number'=>$version_number,'unit_id'=>$data['unit_id']])->column('model_id'); // 所有关联模型编号
            }
            $data['model_type'] = Db::name('quality_unit')->where(['id'=>$data['unit_id']])->value('EvaluateResult'); // 验评结果：0未验评，1不合格，2合格，3优良
            return $data;
        }
    }

    // 按照 [优良，合格，不合格，未验评] 分组
    public function sectionModelInfo($section_id)
    {
        $un_evaluation = $unqualified = $qualified = $excellent =[-1];
        $version = new VersionsModel();
        $version_number = $version->statusOpen(2); // 当前启用的版本号 1 全景3D模型(竣工模型) 和 2 质量模型(施工模型)
        if($section_id == -1){
            $total = Db::name('quality_unit')->field('id,EvaluateResult')->select(); // 获取所有单元工程检验批的编号和检验审批状态
        }else{
            $division = Db::name('quality_division')->where(['section_id'=>$section_id])->column('id'); // 获取对应标段下的 所有工程划分节点
            $total = Db::name('quality_unit')->where(['division_id'=>['in',$division]])->field('id,EvaluateResult')->select(); // 获取所有工程划分节点下的 所有单元工程检验批的编号和检验审批状态
        }
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
        $data['all']= $this->where(['version_number'=>$version_number])->column('model_id'); // 所有
        $data['section_all'] = $this->sectionChange($section_id);
        return $data;
    }

    public function prevRelevance($version_number)
    {
        $data = $this->where(['version_number'=>$version_number])->field('model_name,unit_id')->select();
        return $data;
    }

    /**
     * 根据模型的编号model_id查询关联的xin_quality_unit单元工程表中的信息
     * @param $number
     * @param $number_type
     * @return array|false|\PDOStatement|string|Model
     * @throws \think\exception\DbException
     */
    public function getUnitInfo($number,$number_type)
    {
        $version = new VersionsModel();
        $version_number = $version->statusOpen(2); // 当前启用的版本号 1 全景3D模型(竣工模型) 和 2 质量模型(施工模型)
        $attr = new QualityCustomAttributeModel();
        // $number_type 1 单元工程编号 2 模型编号
        if($number_type == 1){
            $unit_info = Db::name('model_quality')->alias('q')
                ->join('quality_unit u', 'q.unit_id = u.id', 'left')
                ->where(["u.id"=>$number,'q.version_number'=>$version_number])
                ->field("u.site,u.coding,u.hinge,u.quantities,u.ma_bases,u.su_basis,u.el_start,u.el_cease,u.pile_number,u.start_date,u.completion_date,u.en_type,u.division_id,u.id")->find();
        }else{
            $unit_info = Db::name('model_quality')->alias('q')
                ->join('quality_unit u', 'q.unit_id = u.id', 'left')
                ->where(["q.model_id"=>$number,'q.version_number'=>$version_number])
                ->field("u.site,u.coding,u.hinge,u.quantities,u.ma_bases,u.su_basis,u.el_start,u.el_cease,u.pile_number,u.start_date,u.completion_date,u.en_type,u.division_id,u.id")->find();
        }
        // $number_type 1 单元工程编号 2 模型编号
        $attr_info = $attr->getAttrTb($number,$number_type,$version_number);
        return ['unit_info'=>$unit_info,'attr_info'=>$attr_info];
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

    // ht 删除单元工程节点时 --- 批量删除 该节点包含的单元工程段号(检验批)所对应的关联关系
    public function deleteRelation($unit_id_arr)
    {
        $version = new VersionsModel();
        $version_number = $version->statusOpen(2); // 当前启用的版本号 1 全景3D模型(竣工模型) 和 2 质量模型(施工模型)
        $this->where(['unit_id'=>['in',$unit_id_arr],'version_number'=>$version_number])->update(['unit_id'=>0]);
        return ['code'=>1,'msg'=>'解除成功'];
    }

    // ht 解除与此编号有关联的关联关系 --- [实时进度,月进度]
    public function deleteRelationById($relation_id,$type)
    {
        $version = new VersionsModel();
        $version_number = $version->statusOpen(2); // 当前启用的版本号 1 全景3D模型(竣工模型) 和 2 质量模型(施工模型)
        if($type==1){ // type 1 表示是实时进度关联 2 表示月进度关联
            $this->where(['actual_id'=>['eq',$relation_id],'version_number'=>$version_number])->update(['actual_id'=>0]);
        }else{
            $this->where(['mon_progress_id'=>['eq',$relation_id],'version_number'=>$version_number])->update(['mon_progress_id'=>0]);
        }
        return ['code'=>1,'msg'=>'解除成功'];
    }

    // ht 切换标段的时候，返回该标段下所有的模型编号[不只是有关联关系的模型]
    public function sectionChange($section_id)
    {
        $version = new VersionsModel();
        $version_number = $version->statusOpen(2); // 当前启用的版本号 1 全景3D模型(竣工模型) 和 2 质量模型(施工模型)
        if($section_id == -1){ // 全部标段
            // 获取当前登陆人的组织机构
            $group = new AdminGroup();
            $relation_id = $group->relationId();
            if($relation_id == 1){ // 管理员可以看所有的标段
                $section = Db::name('section')->order('id asc')->column('code'); // 标段列表
            }else if($relation_id > 1){
                $section = Db::name('section')->where('builderId',$relation_id)->whereOr('supervisorId',$relation_id)
                    ->whereOr('constructorId',$relation_id)->whereOr('designerId',$relation_id)->whereOr('otherId',$relation_id)
                    ->order('id asc')->column('code'); // 标段列表
            }else{
                $section = [];
            }
           $data = $this->where(['section'=>['in',$section],'version_number'=>$version_number])->column('model_id');
        }else{
            $section = Db::name('section')->where('id',$section_id)->value('code');
            $data = $this->where(['section'=>['eq',$section],'version_number'=>$version_number])->column('model_id');
        }
        return $data;
    }

    // ht 获取所有模型编号和显示样式
    public function modelData($type,$section=0)
    {
        // type 1 所有 2 标段所有 3 最近日期
        if($type == 1){
            $data = $this->field('model_id,display_style')->select();
        }else if($type == 2){
            $data = $this->where(['section'=>$section])->field('model_id,display_style')->select();
        }else{
            $data = $this->where(['section'=>$section,'actual_date'=>['elt',date('Y-m-d')]])->field('model_id,display_style')->select();
        }
        return $data;
    }

}