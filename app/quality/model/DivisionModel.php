<?php
/**
 * Created by PhpStorm.
 * User: sir
 * Date: 2018/3/23
 * Time: 13:58
 */

namespace app\quality\model;


use think\Db;
use think\exception\PDOException;
use think\Model;

class DivisionModel extends Model
{
    protected $name = 'quality_division';
    //自动写入创建、更新时间 insertGetId和update方法中无效，只能用于save方法
    protected $autoWriteTimestamp = true;

    /**
     * 标段
     * @return \think\model\relation\HasOne
     */
    public function Section()
    {
        return $this->hasOne("app\contract\model\SectionModel", 'id', 'section_id');
    }

    /**
     * 工程划分 type = 1 ，单位质量管理 type = 2 ，分部质量管理 type = 4 共用 树节点
     * @param int $type
     * @return string
     * @author hutao
     */
    public function getNodeInfo($type = 1)
    {
        $section = Db::name('section')->column('id,code,name'); // 标段列表
        $division = $this->column('id,pid,d_name,section_id,type,en_type,d_code'); // 工程列表
        $num = $this->count() + Db::name('section')->count() + 10000;

        $str = "";
        $open = 'true';
        $str .= '{ "id": "' . -1 . '", "pId":"' . 0 . '", "name":"' . '丰宁抽水蓄能电站' . '"' . ',"open":"' . $open . '"';
        $str .= '},';
        foreach ($section as $v) {
            $id = $v['id'] + $num;
            $str .= '{ "id": "' . $id . '", "pId":"' . -1 . '", "name":"' . $v['name'] . '"' . ',"code":"' . $v['code'] . '"' . ',"section_id":"' . $v['id'] . '"' . ',"add_id":"' . $v['id'] . '"';
            $str .= '},';
            // 单位工程 type = 1 子单位工程 type = 2 分部工程  type = 3 子分部工程 type = 4 分项工程   type = 5 单元工程   type = 6
            foreach ($division as $vo) {
                if ($v['id'] == $vo['section_id']) {
                    if ($vo['type'] == 1) {
                        $str .= '{ "id": "' . $vo['id'] . '", "pId":"' . $id . '", "name":"' . $vo['d_name'] . '"' . ',"d_code":"' . $vo['d_code'] . '"' . ',"section_id":"' . $vo['section_id'] . '"' . ',"add_id":"' . $vo['id'] . '"' . ',"edit_id":"' . $vo['id'] . '"' . ',"type":"' . $vo['type'] . '"' . ',"en_type":"' . $vo['en_type'] . '"';
                        $str .= '},';
                    } else {
                        if ($type == 2) {
//                            if ($vo['type'] == $type) {
//                                $str .= '{ "id": "' . $vo['id'] . '", "pId":"' . $vo['pid'] . '", "name":"' . $vo['d_name'] . '"' . ',"d_code":"' . $vo['d_code'] . '"' . ',"section_id":"' . $vo['section_id'] . '"' . ',"add_id":"' . $vo['id'] . '"' . ',"edit_id":"' . $vo['id'] . '"' . ',"type":"' . $vo['type'] . '"' . ',"en_type":"' . $vo['en_type'] . '"';
//                                $str .= '},';
//                            }
                        } else if ($type == 4) {
                            if ($vo['type'] < 4 && $vo['type'] != 2) {
                                $str .= '{ "id": "' . $vo['id'] . '", "pId":"' . $vo['pid'] . '", "name":"' . $vo['d_name'] . '"' . ',"d_code":"' . $vo['d_code'] . '"' . ',"section_id":"' . $vo['section_id'] . '"' . ',"add_id":"' . $vo['id'] . '"' . ',"edit_id":"' . $vo['id'] . '"' . ',"type":"' . $vo['type'] . '"' . ',"en_type":"' . $vo['en_type'] . '"';
                                $str .= '},';
                            }
                        } else {
                            $str .= '{ "id": "' . $vo['id'] . '", "pId":"' . $vo['pid'] . '", "name":"' . $vo['d_name'] . '"' . ',"d_code":"' . $vo['d_code'] . '"' . ',"section_id":"' . $vo['section_id'] . '"' . ',"add_id":"' . $vo['id'] . '"' . ',"edit_id":"' . $vo['id'] . '"' . ',"type":"' . $vo['type'] . '"' . ',"en_type":"' . $vo['en_type'] . '"';
                            $str .= '},';
                        }
                    }
                }
            }
        }
        return "[" . substr($str, 0, -1) . "]";
    }

    public function isParent($id)
    {
        $is_exist = $this->where('pid', $id)->find();
        return $is_exist;
    }


    public function insertTb($param)
    {
        try {
            $result = $this->allowField(true)->save($param);
            $id = $this->getLastInsID();
            $data = $this->getOne($id);
            $data['name'] = $data['d_name'];
            if (false === $result) {
                return ['code' => -1, 'msg' => $this->getError()];
            } else {

                /**
                 * 批量新增单位，分部的关联控制点 对应关系
                 */
                if(in_array($param['type'],[1,3])){
                    $ma = $con = $insert_data = [];
                    if($param['type'] == 1){
                        $ma = Db::name('norm_materialtrackingdivision')->where(['type'=>2,'cat'=>2])->column('id');
                    }else if($param['type'] == 3){
                        $ma = Db::name('norm_materialtrackingdivision')->where(['type'=>2,'cat'=>3])->column('id');
                    }
                    foreach ($ma as $k=>$v){
                        $con = Db::name('norm_controlpoint')->where(['procedureid'=>['eq',$v]])->column('id');
                        if(sizeof($con)){
                            foreach ($con as $k1=>$v1){
                                $insert_data[$k1]['type'] = 0;
                                $insert_data[$k1]['division_id'] = $id;
                                $insert_data[$k1]['ma_division_id'] = $v;
                                $insert_data[$k1]['control_id'] = $v1;
                                $insert_data[$k1]['checked'] = 0;
                            }
                            $rel = new DivisionControlPointModel();
                            $rel->insertTbAll($insert_data);
                        }
                    }
                }
                return ['code' => 1, 'data' => $data, 'msg' => '添加成功'];
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

    public function getEnType()
    {
        $data = Db::name('norm_materialtrackingdivision')->where(['cat' => 5, 'type' => ['lt', 3]])->column('id,pid,name');
        $str = '';
        foreach ($data as $v) {
            $str .= '{ "id": "' . $v['id'] . '", "pId":"' . $v['pid'] . '", "name":"' . $v['name'] . '"';
            $str .= '},';
        }
        return "[" . substr($str, 0, -1) . "]";
    }



    // 给 每一个 单位或分部或检验批 批量添加  控制点 对应关系
    public function addRelation($ma_division_id,$cid,$genre)
    {
        //类型 1单位2子单位工程 3分部4子分部工程 5分项工程6单元工程
        $arr_1 = [1];
        $arr_2 = [3];
        $arr_3 = [3,4,5,6];

        //type division_id 类型:0单位,分部工程编号 1检验批
        $type = 0;
        $data = $insert_data = [];

        // 单位工程
        if($genre == 2){
            $data = $this->where(['type'=>['in',$arr_1]])->column('id');
        }

        // 分部工程
        if($genre == 3){
            $data = $this->where(['type'=>['in',$arr_2]])->column('id');
        }

        // 检验批
        if($genre == 5){
            $type = 1;
            $arr_4 = $this->where(['type'=>['in',$arr_3]])->column('id');
            $data = Db::name('quality_unit')->where(['division_id'=>['in',$arr_4]])->column('id');
        }

        foreach ($data as $k=>$v) {
            $insert_data[$k]['type'] = $type;
            $insert_data[$k]['division_id'] = $v;
            $insert_data[$k]['ma_division_id'] = $ma_division_id;
            $insert_data[$k]['control_id'] = $cid;
            $insert_data[$k]['checked'] = 0;
        }

        $rel = new DivisionControlPointModel();
        $res = $rel->insertTbAll($insert_data);
        return $res;
    }

    // 给 每一个 单位或分部或检验批 批量删除  控制点 对应关系
    public function delRelation($ma_division_id,$cid,$genre)
    {
        //type division_id 类型:0单位,分部工程编号 1检验批
        $type = 0;
        // 检验批
        if($genre == 5){
            $type = 1;
        }
        $rel = new DivisionControlPointModel();
        $res = $rel->delRelationAll($ma_division_id,$cid,$type);
        return $res;
    }


    // 新增单位，分部 和 检验批 的关联控制点 对应关系
    public function allRelation()
    {
        // 单位
        $arr_1 = $this->where(['type'=>['eq',1]])->column('id');
//        echo '单位 -- 节点';
//        dump($arr_1);
        // 单位下的工序
        $ma_1 = Db::name('norm_materialtrackingdivision')->where(['type'=>2,'cat'=>2])->column('id');
//        echo '单位 -- 工序';
//        dump($ma_1);
        $res = $this->insertAllCon(0,$arr_1,$ma_1);
        if($res['code'] == -1){
            halt('单位错了');
            return $res;
        }
        // 分部
        $arr_2 = $this->where(['type'=>['eq',3]])->column('id');
//        echo '分部 -- 节点';
//        dump($arr_2);
        // 分部下的工序
        $ma_2 = Db::name('norm_materialtrackingdivision')->where(['type'=>2,'cat'=>3])->column('id');
//        echo '分部 -- 工序';
//        dump($ma_2);
        $res = $this->insertAllCon(0,$arr_2,$ma_2);
        if($res['code'] == -1){
            halt('分部错了');
            return $res;
        }
        // 检验批
        $arr_3 = $this->where(['type'=>['in',[3,4,5,6]]])->column('id');
        $arr_4 = Db::name('quality_unit')->where(['division_id'=>['in',$arr_3]])->column('id');
//        echo '检验批 -- 节点';
//        dump($arr_4);
        $ma_3 = Db::name('norm_materialtrackingdivision')->where(['type'=>3,'cat'=>5])->column('id');
//        echo '检验批 -- 工序';
//        dump($ma_3);
        $res = $this->insertAllCon(1,$arr_4,$ma_3);
        if($res['code'] == -1){
            halt('检验批错了');
            return $res;
        }
        return ['code' => 1, 'msg' => '添加成功'];
    }

    public function insertAllCon($type,$arr_1,$ma_1)
    {
        $insert_data = [];
        $rel = new DivisionControlPointModel();

        foreach ($arr_1 as $k=>$v){
            foreach ($ma_1 as $k1=>$v1){
                // 工序下的控制点
                $con_1 = Db::name('norm_controlpoint')->where(['procedureid'=>['eq',$v1]])->column('id');
                foreach ($con_1 as $k2=>$v2){
                    $insert_data[$k2]['type'] = $type;
                    $insert_data[$k2]['division_id'] = $v;
                    $insert_data[$k2]['ma_division_id'] = $v1;
                    $insert_data[$k2]['control_id'] = $v2;
                    $insert_data[$k2]['checked'] = 0;
                }

                $is_exist = Db::name('quality_division_controlpoint_relation')->where(['type'=>$type,'division_id'=>$v])->field('ma_division_id,control_id')->select();
                $insert_data = array_filter($insert_data);
                foreach ($is_exist as $k88=>$v88){
                    foreach ($insert_data as $k99=>$v99){
                        if($v88['ma_division_id'] == $v99['ma_division_id'] && $v88['control_id'] == $v99['control_id']){
                            unset($insert_data[$k99]);
                        }
                    }
                }

                if(sizeof($insert_data)){
                    $res = $rel->insertTbAll($insert_data);
                    if($res['code'] == -1){
                        return $res;
                    }
                }
            }
        }
    }


    /**
     * 质量模型 获取 工程划分树 包含 检验批
     * @param int $node_type
     * @return string
     * @author hutao
     */
    public function getQualityNodeInfo($node_type=0)
    {
        $section = Db::name('section')->order('id asc')->column('id,code,name'); // 标段列表
        $division = $this->order('id asc')->column('id,pid,d_name,section_id,type,en_type,d_code'); // 工程列表

        $id_arr = Db::name('model_quality')->group('unit_id')->column('unit_id'); // 获取所有 已经关联过的 单元工程id编号

        if($node_type == 1){
            $unit = Db::name('quality_unit')->where(['id'=>['in',$id_arr]])->order('id asc')->column('id,division_id,site'); // 已关联
        }else if($node_type == 2){
            $unit = Db::name('quality_unit')->where(['id'=>['not in',$id_arr]])->order('id asc')->column('id,division_id,site'); // 未关联
        }else{
            $unit = Db::name('quality_unit')->order('id asc')->column('id,division_id,site'); // 检验批列表
        }
        $num = $this->count() + Db::name('section')->count() + 10000;

        $str = "";
        $open = 'true';
        $str .= '{ "id": "' . -1 . '", "pId":"' . 0 . '", "name":"' . '丰宁抽水蓄能电站' . '"' . ',"open":"' . $open . '"';
        $str .= '},';
        foreach ($section as $v) {
            $id = $v['id'] + $num;
            $str .= '{ "id": "' . $id . '", "pId":"' . -1 . '", "name":"' . $v['name'] . '"' . ',"code":"' . $v['code'] . '"' . ',"section_id":"' . $v['id'] . '"' . ',"add_id":"' . $v['id'] . '"';
            $str .= '},';
            // 单位工程 type = 1 子单位工程 type = 2 分部工程  type = 3 子分部工程 type = 4 分项工程   type = 5 单元工程   type = 6
            foreach ($division as $vo) {
                if ($v['id'] == $vo['section_id']) {
                    if ($vo['type'] == 1) {
                        $str .= '{ "id": "' . $vo['id'] . '", "pId":"' . $id . '", "name":"' . $vo['d_name'] . '"' . ',"d_code":"' . $vo['d_code'] . '"' . ',"section_id":"' . $vo['section_id'] . '"' . ',"add_id":"' . $vo['id'] . '"' . ',"edit_id":"' . $vo['id'] . '"' . ',"type":"' . $vo['type'] . '"' . ',"en_type":"' . $vo['en_type'] . '"';
                        $str .= '},';
                    } else {
                        $str .= '{ "id": "' . $vo['id'] . '", "pId":"' . $vo['pid'] . '", "name":"' . $vo['d_name'] . '"' . ',"d_code":"' . $vo['d_code'] . '"' . ',"section_id":"' . $vo['section_id'] . '"' . ',"add_id":"' . $vo['id'] . '"' . ',"edit_id":"' . $vo['id'] . '"' . ',"type":"' . $vo['type'] . '"' . ',"en_type":"' . $vo['en_type'] . '"';
                        $str .= '},';
                    }
                    foreach ($unit as $u) {
                        if ($vo['id'] == $u['division_id']) {
                            $str .= '{ "id": "' . $u['id'] . '", "pId":"' . $vo['id'] . '", "name":"' . $u['site'] . '"' . ',"add_id":"' . $u['id'] . '"' ;
                            $str .= '},';
                        }
                    }
                }
            }
        }
        return "[" . substr($str, 0, -1) . "]";
    }

    public function getEl($id)
    {
        $data = $this->getOne($id);
        $new_data['el_val'] = 'EL.' . $data['el_start'] . '-EL.' . $data['el_cease'];
        $new_data['pile_number'] = $data['pile_number'];
        return $new_data;
    }

    //递归获取当前节点的所有子节点
    public function cateTree($id){
        $res=$this->select();
        if($res){
            $result=$this->sort($res, $id);
            return $result;
        }
    }
    public function sort($data,$id,$level=0){
        static $arr=array();
        foreach ($data as $key=>$value){
            if($value['pid'] == $id){
                $value["level"]=$level;
                $arr[]=$value;
                $this->sort($data,$value['id'],$level+1);
            }
        }
        return $arr;
    }

    public function removeRelevanceNode($id)
    {
        // 获取此节点下包含的所有子节点编号
        $child_node_id = [];
        $child_node_obj = $this->cateTree($id);
        foreach ($child_node_obj as $v){
            $child_node_id[] = $v['id'];
        }
        $child_node_id[] = $id;
        // 获取此节点下包含的所有单元工程检验批
        $unit_id = Db::name('quality_unit')->where(['division_id'=>['in',$child_node_id]])->column('id');
        // 解除所有的关联关系
        Db::name('model_quality')->where(['unit_id'=>['in',$unit_id]])->update(['unit_id'=>0]);
        return ['code'=>1,'msg'=>'解除成功'];
    }

}