<?php

namespace app\quality\controller;


use think\Controller;
use think\Db;
use think\Session;

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

    // ht 质量模型表 模型图上的 只查询已经关联的构件
    public function model_quality($id, $draw, $table, $search, $start, $length, $limitFlag, $order, $columns, $columnString)
    {
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
                $recordsFilteredResult = Db::name($table)->alias('q')
                    ->join('quality_unit u', 'u.id = q.unit_id', 'left')
                    ->field('q.id,q.section,q.unit,q.parcel,q.cell,q.pile_number_1,q.pile_val_1,q.pile_number_2,q.pile_val_2,q.pile_number_3,q.pile_val_3,q.pile_number_4,q.pile_val_4,q.el_start,q.el_cease,u.site')
                    ->where($columnString, 'like', '%' . $search . '%')
                    ->where('q.unit_id',$id)
                    ->order($order)->limit(intval($start), intval($length))->select();
                $recordsFiltered = sizeof($recordsFilteredResult);
            }
        } else {
            //没有搜索条件的情况
            if ($limitFlag) {
                //*****多表查询join改这里******
                $recordsFilteredResult = Db::name($table)->alias('q')
                    ->join('quality_unit u', 'u.id = q.unit_id', 'left')
                    ->field('q.id,q.section,q.unit,q.parcel,q.cell,q.pile_number_1,q.pile_val_1,q.pile_number_2,q.pile_val_2,q.pile_number_3,q.pile_val_3,q.pile_number_4,q.pile_val_4,q.el_start,q.el_cease,u.site')
                    ->where('q.unit_id',$id)
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

    // ht 质量模型表 根据选中的值 叠加查询
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
        $param = input('param.');
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

        $search_data = [
            "contr_relation_id"=>-1
        ];

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
                $recordsFilteredResult = Db::name($table)->alias('q')
                    ->join('quality_unit u', 'u.id = q.unit_id', 'left')
                    ->field('q.id,q.section,q.unit,q.parcel,q.cell,q.pile_number_1,q.pile_val_1,q.pile_number_2,q.pile_val_2,q.pile_number_3,q.pile_val_3,q.pile_number_4,q.pile_val_4,q.el_start,q.el_cease,u.site')
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
                    ->field('q.id,q.section,q.unit,q.parcel,q.cell,q.pile_number_1,q.pile_val_1,q.pile_number_2,q.pile_val_2,q.pile_number_3,q.pile_val_3,q.pile_number_4,q.pile_val_4,q.el_start,q.el_cease,u.site')
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
