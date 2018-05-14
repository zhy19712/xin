<?php
/**
 * Created by PhpStorm.
 * User: zhifang
 * Date: 2018/3/23
 * Time: 15:32
 */

namespace app\contract\controller;

use \think\Cache;
use \think\Controller;
use think\Loader;
use think\Db;
use \think\Cookie;
use think\Model;
use \think\Session;

class Common extends Controller
{
    /**
     * datatables单表查询搜索排序分页
     * 输入参数$table:表名 string
     * 输入参数$columns:对应列名 array
     * 输入参数$draw:不知道干嘛的，但是必要 int
     * 输入参数$order_column:排序列 int
     * 输入参数$order_dir:升/降序 string
     * 输入参数$search:搜索条件 string
     * 输入参数$start:分页开始 int
     * 输入参数$length:分页长度 int
     * @return [type] [description]
     */
    function datatablesPre()
    {
        //接收表名，列名数组 必要
        $columns = $this->request->param('columns/a');
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
        return $this->$table($draw, $table, $search, $start, $length, $limitFlag, $order, $columns, $columnString);
    }

    function contract($draw, $table, $search, $start, $length, $limitFlag, $order, $columns, $columnString)
    {
        //查询
        //条件过滤后记录数 必要
        $recordsFiltered = 0;
        //表的总记录数 必要
        $recordsTotal = 0;
        $recordsTotal = Db::name($table)->count(0);
        $recordsFilteredResult = array();
        if (strlen($search) > 0) {
            //有搜索条件的情况
            if ($limitFlag) {
                //*****多表查询join改这里******
                $recordsFilteredResult = Db::name($table)->where($columnString, 'like', '%' . $search . '%')->order($order)->limit(intval($start), intval($length))->select();
                $recordsFiltered = sizeof($recordsFilteredResult);
            }
        } else {
            //没有搜索条件的情况
            if ($limitFlag) {
                $recordsFilteredResult = Db::name($table)->order($order)->limit(intval($start), intval($length))->select();
                //*****多表查询join改这里******
                //$recordsFilteredResult = Db::name('datatables_example')->alias('d')->join('datatables_example_join e','d.position = e.id')->field('d.id,d.name,e.name as position,d.office')->select();
                $recordsFiltered = $recordsTotal;
            }
        }
        $temp = array();
        $infos = array();
        foreach ($recordsFilteredResult as $key => $value) {
            //计算列长度
            $length = sizeof($columns);
            for ($i = 0; $i < $length; $i++) {
                array_push($temp, $value[$columns[$i]['name']]);
            }
            $infos[] = $temp;
            $temp = [];
        }
        return json(['draw' => intval($draw), 'recordsTotal' => intval($recordsTotal), 'recordsFiltered' => $recordsFiltered, 'data' => $infos]);
    }

    function section($draw, $table, $search, $start, $length, $limitFlag, $order, $columns, $columnString)
    {
        //查询
        //条件过滤后记录数 必要
        $recordsFiltered = 0;
        //表的总记录数 必要
        $recordsTotal = 0;
        $recordsTotal = Db::name($table)->count(0);
        $recordsFilteredResult = array();
        if (strlen($search) > 0) {
            //有搜索条件的情况
            if ($limitFlag) {
                $wherestr = trim($search);
                //*****多表查询join改这里******
                $recordsFilteredResult = Db::name($table)->alias('a')
                    ->join('admin_group b', 'a.builderId=b.id', 'left')
                    ->join('admin_group c', 'a.constructorId=c.id', 'left')
                    ->join('admin_group d', 'a.designerId=d.id', 'left')
                    ->join('admin_group e', 'a.supervisorId=e.id', 'left')
                    ->field('a.id,a.code,a.name,a.money,b.name as builder,c.name as constructor,d.name as designer,e.name as supervisor')
                    ->whereLike('a.name', '%' . $wherestr . '%', 'or')
                    ->whereLike('a.code', '%' . $wherestr . '%', 'or')
                    ->whereLike('b.name', '%' . $wherestr . '%', 'or')
                    ->whereLike('c.name', '%' . $wherestr . '%', 'or')
                    ->whereLike('d.name', '%' . $wherestr . '%', 'or')
                    ->whereLike('e.name', '%' . $wherestr . '%', 'or')
                    ->order($order)->limit(intval($start), intval($length))->select();
                $recordsFiltered = sizeof($recordsFilteredResult);
            }
        } else {
            //没有搜索条件的情况
            if ($limitFlag) {
                $recordsFilteredResult = Db::name($table)->alias('a')
                    ->join('admin_group b', 'a.builderId=b.id', 'left')
                    ->join('admin_group c', 'a.constructorId=c.id', 'left')
                    ->join('admin_group d', 'a.designerId=d.id', 'left')
                    ->join('admin_group e', 'a.supervisorId=e.id', 'left')
                    ->field('a.id,a.code,a.name,a.money,b.name as builder,c.name as constructor,d.name as designer,e.name as supervisor')
                    ->order($order)->limit(intval($start), intval($length))->select();
                //*****多表查询join改这里******
//                $recordsFilteredResult = Db::name($table)->alias('a')->join('admin_group b','d.position = e.id')->field('d.id,d.name,e.name as position,d.office')->select();
                $recordsFiltered = $recordsTotal;
            }
        }
        $temp = array();
        $infos = array();
        foreach ($recordsFilteredResult as $key => $value) {
            //计算列长度
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