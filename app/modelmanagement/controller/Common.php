<?php

namespace app\modelmanagement\controller;


use think\Controller;
use think\Db;

class Common extends Controller
{

    function datatablesPre()
    {
        //接收表名，列名数组 必要
        $columns = $this->request->param('columns/a');
        //获取查询条件
        $id = $this->request->has('id') ? $this->request->param('id', 0, 'intval') : 0;
        $table = $this->request->param('tableName');
        //接收查询条件，可以为空
        $columnNum = sizeof($columns);
        $columnString = '';
        for ($i = 0; $i < $columnNum; $i++) {
            $columnString = $columns[$i]['name'] . '|' . $columnString;
        }
        $columnString = substr($columnString, 0, strlen($columnString) - 1);
        //获取Datatables发送的参数 必要
        $draw = $this->request->has('draw') ? $this->request->param('draw', 0, 'intval') : 0;
        //排序列
        $order_column = $this->request->param('order/a')['0']['column'];
        //ase desc 升序或者降序
        $order_dir = $this->request->param('order/a')['0']['dir'];

        $order = "";
        if (isset($order_column)) {
            $i = intval($order_column);
            $order = $columns[$i]['name'] . ' ' . $order_dir;
        }
        //搜索
        //获取前台传过来的过滤条件
        $search = $this->request->param('search/a')['value'];
        //分页
        $start = $this->request->has('start') ? $this->request->param('start', 0, 'intval') : 0;
        $length = $this->request->has('length') ? $this->request->param('length', 0, 'intval') : 0;
        $limitFlag = isset($start) && $length != -1;
        //新建的方法名与数据库表名保持一致
        return $this->$table($id, $draw, $table, $search, $start, $length, $limitFlag, $order, $columns, $columnString);
    }

    // ht 模型版本管理表
    public function model_version_management($id, $draw, $table, $search, $start, $length, $limitFlag, $order, $columns, $columnString)
    {
        // 前台 传递 model_type 1 竣工模型 2 施工模型
        $param = input('param.');
        $model_type = isset($param['model_type']) ? $param['model_type'] : 1; // 不传递 默认 1 竣工模型
        if(empty($model_type)){
            return json(['draw' => intval($draw), 'recordsTotal' => intval(0), 'recordsFiltered' => 0, 'data' => array()]);
        }
        //查询
        //条件过滤后记录数 必要
        $recordsFiltered = 0;
        $recordsFilteredResult = array();
        //表的总记录数 必要
        $recordsTotal = Db::name($table)->count();
        if (strlen($search) > 0) {
            //有搜索条件的情况
            if ($limitFlag) {
                //*****多表查询join改这里******
                $recordsFilteredResult = Db::name($table)
                    ->field('id,resource_name,resource_path,version_number,version_date,remake,status')
                    ->where($columnString, 'like', '%' . $search . '%')
                    ->where(['model_type'=>$model_type])
                    ->order($order)->limit(intval($start), intval($length))->select();
                $recordsFiltered = sizeof($recordsFilteredResult);
            }
        } else {
            //没有搜索条件的情况
            if ($limitFlag) {
                //*****多表查询join改这里******
                $recordsFilteredResult = Db::name($table)
                    ->field('id,resource_name,resource_path,version_number,version_date,remake,status')
                    ->where(['model_type'=>$model_type])
                    ->order($order)->limit(intval($start), intval($length))->select();
                $recordsFiltered = $recordsTotal;
            }
        }
        $temp = array();
        $infos = array();
        foreach ($recordsFilteredResult as $key => $value) {
            $length = sizeof($columns);
            for ($i = 0; $i < $length; $i++) {
                array_push($temp, $value[$columns[$i]['name']]);
            }
            $infos[] = $temp;
            $temp = [];
        }
        return json(['draw' => intval($draw), 'recordsTotal' => intval($recordsTotal), 'recordsFiltered' => $recordsFiltered, 'data' => $infos]);
    }

    // ht 质量模型表 模型图上的 只查询选中节点已经关联的构件
    public function model_quality($id, $draw, $table, $search, $start, $length, $limitFlag, $order, $columns, $columnString)
    {
//        $param = input('param.');
//        $model_type = isset($param['model_type']) ? $param['model_type'] : 0; // 0 默认是 只查询选中节点已经关联的构件 1 已关联构件 2 未关联构件
//        if($model_type == 0){
//            $search_data = ['q.unit_id'=>-1];
//            if($id){
//                $search_data = ['q.unit_id'=>$id];
//            }
//        }else if($model_type == 1){
//            $search_data = ['q.unit_id'=>['neq',0]];
//        }else{
//            $search_data = ['q.unit_id'=>['eq',0]];
//        }
        $search_data = ['q.unit_id'=>$id];
        //查询
        //条件过滤后记录数 必要
        $recordsFiltered = 0;
        $recordsFilteredResult = array();
        //表的总记录数 必要
        $recordsTotal = Db::name($table)->alias('q')->where($search_data)->count();
        if (strlen($search) > 0) {
            //有搜索条件的情况
            if ($limitFlag) {
                //*****多表查询join改这里******
                $recordsFilteredResult = Db::name($table)->alias('q')
                    ->join('quality_unit u', 'u.id = q.unit_id', 'left')
                    ->field('q.id,q.section,q.unit,q.parcel,q.cell,q.pile_number_1,q.pile_val_1,q.pile_number_2,q.pile_val_2,q.pile_number_3,q.pile_val_3,q.pile_number_4,q.pile_val_4,q.el_start,q.el_cease,u.site,u.id as uid')
                    ->where($columnString, 'like', '%' . $search . '%')
                    ->where($search_data)
                    ->order($order)->limit(intval($start), intval($length))->select();
                $recordsFiltered = sizeof($recordsFilteredResult);
            }
        } else {
            //没有搜索条件的情况
            if ($limitFlag) {
                //*****多表查询join改这里******
                $recordsFilteredResult = Db::name($table)->alias('q')
                    ->join('quality_unit u', 'u.id = q.unit_id', 'left')
                    ->field('q.id,q.section,q.unit,q.parcel,q.cell,q.pile_number_1,q.pile_val_1,q.pile_number_2,q.pile_val_2,q.pile_number_3,q.pile_val_3,q.pile_number_4,q.pile_val_4,q.el_start,q.el_cease,u.site,u.id as uid')
                    ->where($search_data)
                    ->order($order)->limit(intval($start), intval($length))->select();
                $recordsFiltered = $recordsTotal;
            }
        }
        $temp = array();
        $infos = array();
        foreach ($recordsFilteredResult as $key => $value) {
            $length = sizeof($columns);
            for ($i = 0; $i < $length; $i++) {
                array_push($temp, $value[$columns[$i]['name']]);
            }
            $infos[] = $temp;
            $temp = [];
        }
        return json(['draw' => intval($draw), 'recordsTotal' => intval($recordsTotal), 'recordsFiltered' => $recordsFiltered, 'data' => $infos]);
    }

    // 模型构建列表下面的勾选 已关联构件 和 未关联构件
    // ht 质量模型表 根据选中的值 叠加查询  共用方法
    public function model_quality_search($id, $draw, $table, $search, $start, $length, $limitFlag, $order, $columns, $columnString)
    {
        $table = 'model_quality';

        // 前台 可以选择组合传递的参数有
        // section 标段 unit 单位 parcel 分部 cell 单元
        // pile_number_1 桩号1名 pile_val_1 桩号1值
        // pile_number_2 桩号2名 pile_val_2 桩号2值
        // pile_number_3 桩号3名 pile_val_3 桩号3值
        // pile_number_4 桩号4名 pile_val_4 桩号4值
        // el_start 高程起 el_cease 高程止
        $search_data = [];
        $param = input('param.');

        $model_type = isset($param['model_type']) ? $param['model_type'] : 0; // 0 默认是 查所有的构件 1 已关联构件 2 未关联构件
        if($model_type == 1){
            $search_data['q.unit_id'] = ['gt',0];
        }else if($model_type == 2){
            $search_data['q.unit_id'] = ['eq',0];
        }

        $section = isset($param['section']) ? $param['section'] : '';
        $unit = isset($param['unit']) ? $param['unit'] : '';
        $parcel = isset($param['parcel']) ? $param['parcel'] : '';
        $cell = isset($param['cell']) ? $param['cell'] : '';
        $pile_number_1 = isset($param['pile_number_1']) ? $param['pile_number_1'] : '';
        $pile_val_1 = isset($param['pile_val_1']) ? $param['pile_val_1'] : '';
        $pile_number_2 = isset($param['pile_number_2']) ? $param['pile_number_2'] : '';
        $pile_val_2 = isset($param['pile_val_2']) ? $param['pile_val_2'] : '';
        $pile_number_3 = isset($param['pile_number_3']) ? $param['pile_number_3'] : '';
        $pile_val_3 = isset($param['pile_val_3']) ? $param['pile_val_3'] : '';
        $pile_number_4 = isset($param['pile_number_4']) ? $param['pile_number_4'] : '';
        $pile_val_4 = isset($param['pile_val_4']) ? $param['pile_val_4'] : '';
        $el_start = isset($param['el_start']) ? $param['el_start'] : '';
        $el_cease = isset($param['el_cease']) ? $param['el_cease'] : '';

        /**
         *  桩号1名桩号2名 里面控制着 CS (场上) 和 CX (场下)
         * 当 只存在 一个值的 时候   桩号1值 是大于等于 桩号2值 是小于等于
         * 当 两个值都存在 并且 选择的桩号名一致 的 时候  桩号1值 <= 结果<=桩号2值 取两个值的区间值
         * 当 两个值都存在 并且 选择的桩号名不一致 的 时候  0<= 结果<=桩号1值      0<= 结果<=桩号2值
         *
         *  桩号3名桩号4名 里面控制着 CZ (场左) 和 CY (场右) 同上
         */
        if($section){
            $search_data['q.section'] = $section;
        }
        if($unit){
            $search_data['q.unit'] = $unit;
        }
        if($parcel){
            $search_data['q.parcel'] = $parcel;
        } if($cell){
            $search_data['q.cell'] = $cell;
        } if($pile_number_1 && $pile_val_1 && !$pile_val_2){
            $search_data['q.pile_number_1'] = $pile_number_1;
            $search_data['q.pile_val_1'] = ["egt",$pile_val_1];
        } if($pile_number_2 && $pile_val_2 && !$pile_val_1){
            $search_data['q.pile_number_2'] = $pile_number_2;
            $search_data['q.pile_val_2'] = ["elt",$pile_val_2];
        } if($pile_number_1 && $pile_val_1 && $pile_number_2 && $pile_val_2){
            $search_data['q.pile_number_1'] = $pile_number_1;
            $search_data['q.pile_number_2'] = $pile_number_2;
            if($pile_number_1 == $pile_number_2){
                $search_data['q.pile_val_1'] = ["egt",$pile_val_1];
                $search_data['q.pile_val_1'] = ["elt",$pile_val_2];
                $search_data['q.pile_val_2'] = ["egt",$pile_val_1];
                $search_data['q.pile_val_2'] = ["elt",$pile_val_2];
            }else{
                $search_data['q.pile_val_1'] = ["egt",0];
                $search_data['q.pile_val_1'] = ["elt",$pile_val_1];
                $search_data['q.pile_val_2'] = ["egt",0];
                $search_data['q.pile_val_2'] = ["elt",$pile_val_2];
            }
        } if($pile_number_3 && $pile_val_3 && !$pile_val_4){
            $search_data['q.pile_number_3'] = $pile_number_3;
            $search_data['q.pile_val_3'] = ["egt",$pile_val_3];
        } if($pile_number_4 && $pile_val_4 && !$pile_val_3){
            $search_data['q.pile_number_4'] = $pile_number_4;
            $search_data['q.pile_val_4'] = ["elt",$pile_val_4];
        } if($pile_number_3 && $pile_val_3 && $pile_number_4 && $pile_val_4){
            $search_data['q.pile_number_3'] = $pile_number_3;
            $search_data['q.pile_number_4'] = $pile_number_4;
            if($pile_number_3 == $pile_number_4){
                $search_data['q.pile_val_3'] = ["egt",$pile_val_3];
                $search_data['q.pile_val_3'] = ["elt",$pile_val_4];
                $search_data['q.pile_val_4'] = ["egt",$pile_val_3];
                $search_data['q.pile_val_4'] = ["elt",$pile_val_4];
            }else{
                $search_data['q.pile_val_3'] = ["egt",0];
                $search_data['q.pile_val_3'] = ["elt",$pile_val_3];
                $search_data['q.pile_val_4'] = ["egt",0];
                $search_data['q.pile_val_4'] = ["elt",$pile_val_4];
            }
        } if($el_start){
            $search_data['q.el_start'] = ["egt",$el_start];
        } if($el_cease){
            $search_data['q.el_cease'] = ["elt",$el_cease];
        }

        //查询
        //条件过滤后记录数 必要
        $recordsFiltered = 0;
        $recordsFilteredResult = array();
        //表的总记录数 必要
        $recordsTotal = Db::name($table)->count();
        if (sizeof($search_data) > 0) {
            //有搜索条件的情况
            if ($limitFlag) {
                //*****多表查询join改这里******
                $recordsFilteredResult = Db::name($table)->alias('q')
                    ->join('quality_unit u', 'u.id = q.unit_id', 'left')
                    ->field('q.id,q.section,q.unit,q.parcel,q.cell,q.pile_number_1,q.pile_val_1,q.pile_number_2,q.pile_val_2,q.pile_number_3,q.pile_val_3,q.pile_number_4,q.pile_val_4,q.el_start,q.el_cease,u.site,u.id as uid')
                    ->where($search_data)
                    ->order($order)->limit(intval($start), intval($length))->select();
                $recordsFiltered = Db::name($table)->alias('q')->join('quality_unit u', 'u.id = q.unit_id', 'left')->where($search_data)->count();
            }
        } else {
            //没有搜索条件的情况
            if ($limitFlag) {
                //*****多表查询join改这里******
                $recordsFilteredResult = Db::name($table)->alias('q')
                    ->join('quality_unit u', 'u.id = q.unit_id', 'left')
                    ->field('q.id,q.section,q.unit,q.parcel,q.cell,q.pile_number_1,q.pile_val_1,q.pile_number_2,q.pile_val_2,q.pile_number_3,q.pile_val_3,q.pile_number_4,q.pile_val_4,q.el_start,q.el_cease,u.site,u.id as uid')
                    ->order($order)->limit(intval($start), intval($length))->select();
                $recordsFiltered = $recordsTotal;
            }
        }
        $temp = array();
        $infos = array();
        foreach ($recordsFilteredResult as $key => $value) {
            $length = sizeof($columns);
            for ($i = 0; $i < $length; $i++) {
                array_push($temp, $value[$columns[$i]['name']]);
            }
            $infos[] = $temp;
            $temp = [];
        }
        return json(['draw' => intval($draw), 'recordsTotal' => intval($recordsTotal), 'recordsFiltered' => $recordsFiltered, 'data' => $infos]);
    }

}
