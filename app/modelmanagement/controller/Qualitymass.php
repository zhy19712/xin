<?php
/**
 * Created by PhpStorm.
 * User: sir
 * Date: 2018/5/22
 * Time: 11:57
 */

namespace app\modelmanagement\controller;


use app\admin\controller\Permissions;
use app\modelmanagement\model\CompleteModel;
use app\modelmanagement\model\QualitymassModel;
use app\modelmanagement\model\VersionsModel;
use app\quality\model\DivisionModel;
use app\quality\model\DivisionUnitModel;

/**
 * 质量模型
 * Class qualitymass
 * @package app\modelmanagement\controller
 * @author hutao
 */
class Qualitymass extends Permissions
{
    /**
     * 左侧的树
     * @return mixed|\think\response\Json
     * @author hutao
     */
    public function index()
    {
        if(request()->isAjax()){
            // 传递 node_type 1 已关联节点 2 未关联节点 不传递默认0查询全部
            $param = input('get.');
            $node_type = isset($param['node_type']) ? $param['node_type'] : 0;
            $node = new DivisionModel();
            if($node_type==1){
                $nodeStr = $node->getQualityNodeInfo($node_type);
            }else if($node_type==2){
                $nodeStr = $node->getQualityNodeInfo($node_type);
            }else{
                $nodeStr = $node->getQualityNodeInfo();
            }
            return json($nodeStr);
        }
       return $this->fetch();
    }

    /**
     * 起止高程和桩号的值
     * @return \think\response\Json
     * @author hutao
     */
    public function elVal()
    {
        if(request()->isAjax()){
            // 传递 单元工程的 add_id
            $id = input('add_id');
            if(empty($id)){
                return json(['code'=>-1,'msg'=>'缺少单元工程的编号']);
            }
            $node = new DivisionUnitModel();
            $data = $node->getEl($id);
            return json(['code'=>1,'el_val'=>$data['el_val'],'pile_number'=>$data['pile_number'],'msg'=>'起止高程和桩号的值']);
        }
    }

    /**
     * 下拉列表的值
     * @return \think\response\Json
     * @author hutao
     */
    public function dropDown()
    {
        // 前台传递 的 $type 值 代表的含义
        // section 标段 unit 单位 parcel 分部 cell 单元
        // pile_number_1 桩号1名 pile_number_2 桩号2名
        // pile_number_3 桩号3名 pile_number_4 桩号4名
        if(request()->isAjax()){
            $type = input('type');
            if(empty($type)){
                return json(['code'=>-1,'msg'=>'缺少构件类型']);
            }
            $node = new QualitymassModel();
            if($type == 'section'){
                $data = $node->getSection();$val = '标段';
            }else if($type == 'unit'){
                $data = $node->getUnit();$val = '单位';
            }else if($type == 'parcel'){
                $data = $node->getParcel();$val = '分部';
            }else if($type == 'cell'){
                $data = $node->getCell();$val = '单元';
            }else if($type == 'pile_number_1'){
                $data = $node->pile_number_1();$val = '桩号1';
            }else if($type == 'pile_number_2'){
                $data = $node->pile_number_2();$val = '桩号2';
            }else if($type == 'pile_number_3'){
                $data = $node->pile_number_3();$val = '桩号3';
            }else if($type == 'pile_number_4'){
                $data = $node->pile_number_4();$val = '桩号4';
            }else{
                return json(['code'=>1,'data'=>'','msg'=>'构件类型不存在']);
            }
            return json(['code'=>1,'data'=>$data,'msg'=>$val.'下拉列表的值']);
        }
    }

    /**
     * 选中的节点 -- 解除关联
     * @return \think\response\Json
     * @author hutao
     */
    public function removeRelevanceNode()
    {
        if(request()->isAjax()){
            // 传递 选中节点的 add_id
            $id = input('add_id');
            if(empty($id)){
                return json(['code'=>-1,'msg'=>'缺少节点的编号']);
            }
            $node = new DivisionModel();
            $flag = $node->removeRelevanceNode($id);
            return json($flag);
        }
    }

    /**
     * 关联构件
     * @return \think\response\Json
     * @author hutao
     */
    public function relevance()
    {
        if(request()->isAjax()){
            // 传递 选中节点的 add_id 和 选中的构件 编号数组 id_arr
            $id = input('add_id');
            $id_arr = input('id_arr/a');
            if(!sizeof($id_arr)){
                return json(['code'=>-1,'msg'=>'缺少构件的编号']);
            }
            $node = new QualitymassModel();
            $flag = $node->relevance($id,$id_arr);
            return json($flag);
        }
    }

    /**
     * 选中的构件 --  解除关联
     * @return \think\response\Json
     * @author hutao
     */
    public function removeRelevance()
    {
        if(request()->isAjax()){
            // 传递 选中的构件 编号数组 id_arr
            $id_arr = input('id_arr/a');
            if(!sizeof($id_arr)){
                return json(['code'=>-1,'msg'=>'缺少构件的编号']);
            }
            $node = new QualitymassModel();
            $flag = $node->removeRelevance($id_arr);
            return json($flag);
        }
    }

    /**
     * 获取选中节点的所有关联模型编号
     * @return \think\response\Json
     * @author hutao
     */
    public function nodeModelNumber()
    {
        if($this->request->isAjax()){
            // 前台 传递 选中节点的 add_id  和 节点的类型 node_type 1 顶级节点 2 标段 3 工程划分节点 4 单元工程段号(检验批编号)
            $param = input('post.');
            $add_id = isset($param['add_id']) ? $param['add_id'] : 0;
            $node_type = isset($param['node_type']) ? $param['node_type'] : 0;
            if(empty($add_id) || empty($node_type)){
                return json(['code'=>-1,'msg'=>'缺少参数']);
            }if(!in_array($node_type,[1,2,3,4])){
                return json(['code'=>-1,'msg'=>'无效的节点类型']);
            }
            $quality = new QualitymassModel();
            $data = $quality->nodeModelNumber($add_id,$node_type);
            return json(['code'=>1,'data'=>$data,'msg'=>'选中节点的所有关联模型编号']);
        }
    }


    // 全景模型 点击模型 获取模型属性 着急先把方法放到这里 后期有时间再转移
    public function attributeArr()
    {
        if($this->request->isAjax()){
            // 前台 传递 选中模型的 编号 uObjSubIDArr
            $model_number_arr = input('uObjSubIDArr/a');
            if(!sizeof($model_number_arr)){
                return json(['code'=>-1,'msg'=>'缺少参数']);
            }

            /**
             * 需求说明 ---
             * 当数组里 只有 一个值的时候
             *          返回 属于该分组的所有模型编号 并且返回该组的所有属性
             *
             * 当数组里 存在多个值的时候
             *          只返回所有分组的模型编号
             */

            $comp= new CompleteModel();
            $data = $comp->attributeArr($model_number_arr);
            return json(['code'=>1,'data'=>$data,'msg'=>'成功']);
        }
    }

}