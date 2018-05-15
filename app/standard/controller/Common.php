<?php
/**
 * Created by PhpStorm.
 * User: sir
 * Date: 2018/3/30
 * Time: 18:28
 */

namespace app\standard\controller;


use app\standard\model\ControlPoint;
use app\standard\model\NormModel;
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
            if ($columns[$i]['searchable'] == 'true') {
                $columnString = $columns[$i]['name'] . '|' . $columnString;
            }
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

    public function norm_file($id, $draw, $table, $search, $start, $length, $limitFlag, $order, $columns, $columnString)
    {
        //查询
        //条件过滤后记录数 必要
        $recordsFiltered = 0;
        $recordsFilteredResult = array();
        $node = new NormModel();
        $idArr = $node->cateTree($id);
        $idArr[] = $id;
        //表的总记录数 必要
        $recordsTotal = Db::name($table)->whereIn('nodeId', $idArr)->count(0);
        if (strlen($search) > 0) {
            //有搜索条件的情况
            if ($limitFlag) {
                //*****多表查询join改这里******
                $recordsFilteredResult = Db::name($table)
                    ->field('standard_number,standard_name,material_date,alternate_standard,remark,id')
                    ->whereIn('nodeId', $idArr)
                    ->where($columnString, 'like', '%' . $search . '%')
                    ->order($order)->limit(intval($start), intval($length))->select();
                $recordsFiltered = sizeof($recordsFilteredResult);
            }
        } else {
            //没有搜索条件的情况
            if ($limitFlag) {
                //*****多表查询join改这里******
                $recordsFilteredResult = Db::name($table)
                    ->field('standard_number,standard_name,material_date,alternate_standard,remark,id')
                    ->whereIn('nodeId', $idArr)
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

    public function quality_template($id, $draw, $table, $search, $start, $length, $limitFlag, $order, $columns, $columnString)
    {
        //查询
         $_type = $this->request->has('type') ? $this->request->param('type') : "";
         $_use = $this->request->has('use') ? $this->request->param('use') : "";
        //条件过滤后记录数 必要
        $recordsFiltered = 0;
        $recordsFilteredResult = array();
        //表的总记录数 必要
        $recordsTotal = Db::name($table)->count(0);
        if (strlen($search) > 0) {

            if ((!empty($_type)) || (!empty($_use))) {
                if ($limitFlag) {
                    if ((!empty($_type)) && (!empty($_use))) {
                        $wherestr['type'] = $_type;
                        $wherestr['use'] = $_use;
                    } else if (!empty($_type)) {
                        $wherestr['type'] = $_type;

                    } else {
                        $wherestr['use'] = $_use;
                    }
                    $recordsTotal = Db::name($table)->where($wherestr)->count(0);
                    $recordsFilteredResult = Db::name($table)
                        ->where($wherestr)
                        ->where($columnString, 'like', '%' . $search . '%')
                        ->order($order)->limit(intval($start), intval($length))->select();
                    $recordsFiltered = sizeof($recordsFilteredResult);
                }
            } else {
                $recordsFilteredResult = Db::name($table)
                    ->where($columnString, 'like', '%' . $search . '%')
                    ->order($order)->limit(intval($start), intval($length))->select();
                $recordsFiltered = sizeof($recordsFilteredResult);
            }
        } else {
            //没有搜索条件的情况
            if ($limitFlag) {
                if ((!empty($_type)) || (!empty($_use))) {
                    if ($limitFlag) {
                        if ((!empty($_type)) && (!empty($_use))) {
                            $wherestr['type'] = $_type;
                            $wherestr['use'] = $_use;
                        } else if (!empty($_type)) {
                            $wherestr['type'] = $_type;

                        } else {
                            $wherestr['use'] = $_use;
                        }
                        $recordsTotal = Db::name($table)->where($wherestr)->count(0);
                        $recordsFilteredResult = Db::name($table)
                            ->where($wherestr)
                            ->order($order)->limit(intval($start), intval($length))->select();
                        $recordsFiltered = $recordsTotal;
                    }
                } else {
                    $recordsFilteredResult = Db::name($table)
                        ->order($order)->limit(intval($start), intval($length))->select();
                    $recordsFiltered = $recordsTotal;
                }
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

    public function upload($module = 'admin', $use = 'admin_thumb')
    {
        if ($this->request->file('file')) {
            $file = $this->request->file('file');
        } else {
            $res['code'] = 1;
            $res['msg'] = '没有上传文件';
            return json($res);
        }
        $module = $this->request->has('module') ? $this->request->param('module') : $module;//模块
        $web_config = Db::name('admin_webconfig')->where('web', 'web')->find();
        $info = $file->validate(['size' => $web_config['file_size'] * 1024, 'ext' => $web_config['file_type']])->rule('date')->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . $module . DS . $use);
        if ($info) {
            //写入到附件表
            $data = [];
            $data['module'] = $module;
            $data['filename'] = $info->getFilename();//文件名
            $data['filepath'] = DS . 'uploads' . DS . $module . DS . $use . DS . $info->getSaveName();//文件路径
            $data['fileext'] = $info->getExtension();//文件后缀
            $data['filesize'] = $info->getSize();//文件大小
            $data['create_time'] = time();//时间
            $data['uploadip'] = $this->request->ip();//IP
            $data['user_id'] = Session::has('admin') ? Session::get('admin') : 0;
            if ($data['module'] == 'admin') {
                //通过后台上传的文件直接审核通过
                $data['status'] = 1;
                $data['admin_id'] = $data['user_id'];
                $data['audit_time'] = time();
            }
            $data['use'] = $this->request->has('use') ? $this->request->param('use') : $use;//用处
            $res['id'] = Db::name('attachment')->insertGetId($data);
            $res['src'] = DS . 'uploads' . DS . $module . DS . $use . DS . $info->getSaveName();
            $res['code'] = 2;

            return json($res);
        } else {
            // 上传失败获取错误信息
            return $this->error('上传失败：' . $file->getError());
        }
    }

    public function norm_controlpoint($id, $draw, $table, $search, $start, $length, $limitFlag, $order, $columns, $columnString)
    {
        //查询
        $c = new ControlPoint();
        $idArr = $c->getChilds($id);
        $idArr[] = $id;
        //条件过滤后记录数 必要
        $recordsFiltered = 0;
        $recordsFilteredResult = array();
        //表的总记录数 必要
        $recordsTotal = Db::name($table)->whereIn('ProcedureId', $idArr)->count(0);
        if (strlen($search) > 0) {
            //有搜索条件的情况
            if ($limitFlag) {
                $recordsFilteredResult = Db::name($table)
                    ->whereIn('ProcedureId', $idArr)
                    ->where($columnString, 'like', '%' . $search . '%')
                    ->order($order)->limit(intval($start), intval($length))->select();
                $recordsFiltered = sizeof($recordsFilteredResult);
            }
        } else {
            //没有搜索条件的情况
            if ($limitFlag) {
                //*****多表查询join改这里******
                $recordsFilteredResult = Db::name($table)
                    ->whereIn('ProcedureId', $idArr)
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