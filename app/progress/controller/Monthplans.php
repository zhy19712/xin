<?php
/**
 * Created by PhpStorm.
 * User: 19113
 * Date: 2018/4/19
 * Time: 11:03
 */
/**
 * 进度管理-月度计划
 * Class Progressversion
 */
namespace app\progress\controller;

use app\admin\controller\Permissions;
use think\Session;
use think\Request;
use think\exception\PDOException;
use app\admin\model\AdminGroup;//组织机构
use app\admin\model\Admin;//用户表
use think\Loader;
use think\Db;
use think\Controller;
use app\progress\model\MonthplanModel;
use app\progress\model\PictureModel;
use app\progress\model\PictureRelationModel;

class Monthplans extends Permissions
{
    /**
     * 进度版本管理模板首页
     * @return mixed
     */
    public function index()
    {
        return $this->fetch();
    }

    public function assview()
    {
        return $this->fetch();
    }

    public function progress()
    {
        return $this->fetch();
    }


    /**********************************月度计划************************/
    /**
     * 获取一条信息
     */
    public function getindex()
    {
        if(request()->isAjax()){
            //实例化模型类
            $model = new MonthPlanModel();
            $param = input('post.');
            $data = $model->getOne($param['id']);
            return json(['code'=> 1, 'data' => $data]);
        }
        return $this->fetch();
    }



    public function getalldata()
    {
        if(request()->isAjax()){

            return $this->datatablesPre();


        }
    }

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



    public function progress_monthplan($id, $draw, $table, $search, $start, $length, $limitFlag, $order, $columns, $columnString)
    {
        //查询
        //$_type = $this->request->has('type') ? $this->request->param('type') : "";
        //$_use = $this->request->has('use') ? $this->request->param('use') : "";
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




   //获取模型图下的所有模型图和工程计划

    public function modelPictureAllNumber()
    {
        // 前台 传递 选中的 工程划分 编号 add_id
        if($this->request->isAjax()) {
            $param = input('param.');
            $add_id = isset($param['add_id']) ? $param['add_id'] : -1;

            if ($add_id == -1) {
                return json(['code' => 0, 'msg' => '编号有误']);
            }
            $id = Db::name('monthplan_unit')->where('monthplan_id', $add_id)->column('id');

            // 获取关联的模型图
            $picture = new PictureRelationModel();
            $data = $picture->getAllNumber($id);
            $picture_number = $data['picture_number_arr'];
            $picture_id = $data['picture_id_arr'];
            //循环获取工程信息
            foreach ($picture_id as $k => $value) {
                $relation = Db::name('progress_model_picture_relation')
                    ->where('picture_id', $value)
                    ->field('relevance_id,picture_id')
                    ->select();

                $reid = $relation[0]['relevance_id'];
                $pid = $relation[0]['picture_id'] - 1;

                $unit_data = Db::name('monthplan_unit')
                    ->where('id', $reid)
                    ->field('start_date,completion_date,type')
                    ->select();


                $moduleData[$pid] = $unit_data;

                //遍历，分别单独取出开始时间和结束时间
                $startDate[$k] = strtotime($unit_data[0]['start_date']);
                $completionDate[$k] = strtotime($unit_data[0]['completion_date']);


            }

            //合并所有工程（unit）的开始时间和结束时间，并进行比较取出最早时间和最晚时间作为总计划中的开始点和结束点
            $dateSection = array_merge($startDate, $completionDate);
            $startPoint = date("Y-m-d", min($dateSection));
            $completionPoint = date("Y-m-d", max($dateSection));


            return json(['code' => 1, 'moduleId' => $picture_number, 'startPoint' => $startPoint, 'completionPoint' => $completionPoint, 'moduleData' => $moduleData, 'msg' => '工程划分-模型图编号']);
        }
    }




    public function modelPicturePreview()
    {
        // 前台 传递 选中的 单元工程段号 编号 id
        if($this->request->isAjax()){
            $param = input('param.');
            $id = isset($param['id']) ? $param['id'] : -1;
            if($id == -1){
                return json(['code' => 0,'msg' => '编号有误']);
            }
            // 获取关联的模型图
            $picture = new PictureRelationModel();
            $data = $picture->getAllNumber([$id]);
            $picture_number = $data['picture_number_arr'];
            return json(['code'=>1,'number'=>$picture_number,'msg'=>'单元工程段号-模型图编号']);
        }
    }

    /**
     *
     *
     *
     *
     * //模型功能
     * 打开关联模型 页面 openModelPicture
     * @return mixed|\think\response\Json
     * @author hutao
     */
        public function openModelPicture()
    {
        // 前台 传递 选中的 单元工程段号的 id编号
        if($this->request->isAjax()){
            $param = input('param.');
            $id = isset($param['id']) ? $param['id'] : -1;
            if($id == -1){
                return json(['code' => 0,'msg' => '编号有误']);
            }
            // 获取工程划分下的 所有的模型图主键,编号,名称
            $picture = new PictureModel();
            $data = $picture->getAllName($id);
            return json(['code'=>1,'one_picture_id'=>$data['one_picture_id'],'data'=>$data['str'],'msg'=>'模型图列表']);
        }
        return $this->fetch('');
    }

    /**
     * 关联模型图
     * @return \think\response\Json
     * @author hutao
     */
    public function addModelPicture()
    {
        // 前台 传递 单元工程段号(单元划分) 编号id  和  模型图主键编号 picture_id
        if($this->request->isAjax()){
            $param = input('param.');
            $relevance_id = isset($param['id']) ? $param['id'] : -1;
            $picture_id = isset($param['picture_id']) ? $param['picture_id'] : -1;
            if($relevance_id == -1 || $picture_id == -1){
                return json(['code' => 0,'msg' => '参数有误']);
            }
            // 是否已经关联过 picture_type  1工程划分模型 2 建筑模型 3三D模型
            $is_related = Db::name('progress_model_picture_relation')->where(['type'=>1,'relevance_id'=>$relevance_id])->value('id');
            $data['type'] = 1;
            $data['relevance_id'] = $relevance_id;
            $data['picture_id'] = $picture_id;
            $picture = new PictureRelationModel();
            if(empty($is_related)){
                // 关联模型图 一对一关联
                $flag = $picture->insertTb($data);
                return json($flag);
            }else{
                $data['id'] = $is_related;
                $flag = $picture->editTb($data);
                return json($flag);
            }
        }
    }

    // 此方法只是临时 导入模型图 编号和名称的 txt文件时使用
    // 不存在于 功能列表里面 后期可以删除掉
    // 获取txt文件内容并插入到数据库中 insertTxtContent
    public function insertTxtContent()
    {
        $filePath = './static/monthplan/GolIdTable.txt';
        if(!file_exists($filePath)){
            return json(['code' => '-1','msg' => '文件不存在']);
        }
        $files = fopen($filePath, "r") or die("Unable to open file!");
        $contents = $new_contents =[];
        while(!feof($files)) {
            $txt = iconv('gb2312','utf-8//IGNORE',fgets($files));
            $txt = str_replace('[','',$txt);
            $txt = str_replace(']','',$txt);
            $txt = str_replace("\r\n",'',$txt);
            $contents[] = $txt;
        }

        foreach ($contents as $v){
            $new_contents[] = explode(' ',$v);
        }

        $data = [];
        foreach ($new_contents as $k=>$val){
            $data[$k]['picture_name'] = trim(next($val));
            $data[$k]['picture_number'] = trim(next($val));
        }

        array_pop($data);

        $picture = new PictureModel();
        $picture->saveAll($data); // 使用saveAll 是因为 要 自动插入 时间
        fclose($files);
    }

  //模型功能
    /**
     * 搜索模型
     * @return \think\response\Json
     * @author hutao
     */
    public function searchModel()
    {
        // 前台 传递  选中的 单元工程段号的 id编号  和  搜索框里的值 search_name
        if($this->request->isAjax()){
            $param = input('param.');
            $id = isset($param['id']) ? $param['id'] : -1;
            $search_name = isset($param['search_name']) ? $param['search_name'] : -1;
            if($id == -1){
                return json(['code' => 0,'msg' => '请传递选中的单元工程段号的编号']);
            }if($id == -1 || $search_name == -1){
                return json(['code' => 0,'msg' => '请写入需要搜索的值']);
            }
            // 获取搜索的模型图主键,编号,名称
            $picture = new PictureModel();
            $data = $picture->getAllName($id,$search_name);
            return json(['code'=>1,'one_picture_id'=>$data['one_picture_id'],'data'=>$data['str'],'msg'=>'模型图列表']);
        }
    }






}

