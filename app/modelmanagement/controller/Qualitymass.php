<?php
/**
 * Created by PhpStorm.
 * User: sir
 * Date: 2018/5/22
 * Time: 11:57
 */

namespace app\modelmanagement\controller;


use app\admin\controller\Permissions;
use app\modelmanagement\model\QualitymassModel;
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
            $param = input('post.');
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
            if(empty($id)){
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
            if(sizeof($id_arr)){
                return json(['code'=>-1,'msg'=>'缺少构件的编号']);
            }
            $node = new QualitymassModel();
            $flag = $node->removeRelevance($id_arr);
            return json($flag);
        }
    }


    // 此方法只是临时 导入质量模型 txt文件时使用
    // 不存在于 功能列表里面 后期可以删除掉
    // 获取txt文件内容并插入到数据库中 insertTxtContent
    public function insertTxtContent()
    {
        $filePath = './static/division/GolIdTable2.txt';
        if(!file_exists($filePath)){
            return json(['code' => '-1','msg' => '文件不存在']);
        }
        $files = fopen($filePath, "r") or die("Unable to open file!");
        $contents = $new_contents = $new_ids = [];
        while(!feof($files)) {
            $txt = fgets($files);
            $txt = str_replace('[','',$txt);
            $txt = str_replace(']','',$txt);
            $txt = str_replace("\r\n",'',$txt);
            $txt_arr = explode(' ',$txt);
            $contents[] = $txt_arr;
        }

        $i=0;
        foreach ($contents as $item){
            foreach ($item as $k=>$v){
                if($k==1){
                    $new_contents[$i] = explode('-',$v);
                }else if ($k==2){
                    array_push($new_contents[$i],$v);
                }
            }
            $i++;
        }

        $data = [];
        foreach ($new_contents as $k=>$val){
            $data[$k]['section'] = $val[0];
            $data[$k]['unit'] = trim(next($val));
            $data[$k]['parcel'] = trim(next($val));
            $data[$k]['cell'] = trim(next($val));
            $arr_1 = explode('+',trim(next($val)));
            $data[$k]['pile_number_1'] = $arr_1[0];
            $data[$k]['pile_val_1'] = $arr_1[1];
            $arr_2 = explode('+',trim(next($val)));
            $data[$k]['pile_number_2'] = $arr_2[0];
            $data[$k]['pile_val_2'] = $arr_2[1];
            $arr_3 = explode('+',trim(next($val)));
            $data[$k]['pile_number_3'] = $arr_3[0];
            $data[$k]['pile_val_3'] = $arr_3[1];
            $arr_4 = explode('+',trim(next($val)));
            $data[$k]['pile_number_4'] = $arr_4[0];
            $data[$k]['pile_val_4'] = $arr_4[1];
            $arr_5 = explode('+',trim(next($val)));
            $data[$k]['el_start'] = $arr_5[1];
            $arr_6 = explode('+',trim(next($val)));
            $data[$k]['el_cease'] = $arr_6[1];
            $data[$k]['model_id'] = trim(next($val));
        }

        $picture = new QualitymassModel();
        $picture->insertAll($data);
        fclose($files);
    }

}