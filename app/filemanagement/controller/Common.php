<?php
/**
 * Created by PhpStorm.
 * User: zhifang
 * Date: 2018/3/23
 * Time: 15:32
 */
/**
 * 档案管理
 * Class Common
 */
namespace app\filemanagement\controller;
use app\filemanagement\model\FilebranchModel;//档案管理-分支目录管理
use \think\Controller;
use think\Db;
use think\Request;
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
            if ($columns[$i]['searchable']=='true') {
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
        return $this->$table($draw, $table, $search, $start, $length, $limitFlag, $order, $columns, $columnString);
    }

    /**
     * 分支目录管理-项目分类
     * @param $draw
     * @param $table
     * @param $search
     * @param $start
     * @param $length
     * @param $limitFlag
     * @param $order
     * @param $columns
     * @param $columnString
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function file_branch_directory($draw, $table, $search, $start, $length, $limitFlag, $order, $columns, $columnString)
    {
        //查询
        //条件过滤后记录数 必要
        $recordsFiltered = 0;
        //表的总记录数 必要
        $classifyid = input('classifyid');
        //表的总记录数 必要
        $recordsTotal = 0;
        $recordsTotal = Db::name($table)->where('classifyid',$classifyid)->where("pid = 0")->count(0);
        $recordsFilteredResult = array();
        if(strlen($search)>0){
            //有搜索条件的情况
            if($limitFlag){
                //*****多表查询join改这里******
                $recordsFilteredResult = Db::name($table)->where('classifyid',$classifyid)->where("pid = 0")->where($columnString, 'like', '%' . $search . '%')->order($order)->limit(intval($start),intval($length))->select();
                $recordsFiltered = sizeof($recordsFilteredResult);
            }
        }else{
            //没有搜索条件的情况
            if($limitFlag){
                $recordsFilteredResult = Db::name($table)->where('classifyid',$classifyid)->where("pid = 0")->order($order)->limit(intval($start),intval($length))->select();
                $recordsFiltered = $recordsTotal;
            }
        }
        //实例化model类
        $model = new FilebranchModel();
        $temp = array();
        $infos = array();
        foreach ($recordsFilteredResult as $key => $value) {
            //计算列长度
            $length = sizeof($columns);
            for ($i = 0; $i < $length; $i++) {
                array_push($temp, $value[$columns[$i]['name']]);
            }


            $children_info = $model->getAllChild($temp["2"]);
            halt($children_info);

            array_push($temp, $children_info);

            $infos[] = $temp;

            $temp = [];
        }
        return json(['draw' => intval($draw), 'recordsTotal' => intval($recordsTotal), 'recordsFiltered' => $recordsFiltered, 'data' => $infos]);
    }




    /**
     * 文件上传
     * @param string $module
     * @param string $use
     * @return \think\response\Json|void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function upload($module = 'file', $use = 'file_thumb')
    {
        if ($this->request->file('file')){
            $file = $this->request->file('file');
        } else {
            $res['code'] = -1;
            $res['msg'] = '没有上传文件';
            return json($res);
        }
        $module = $this->request->has('module') ? $this->request->param('module') : $module;//模块
        $web_config = Db::name('webconfig')->where('web', 'web')->find();
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
            $res['filename'] = $info->getFilename();//文件名
            $res['src'] = DS . 'uploads' . DS . $module . DS . $use . DS . $info->getSaveName();
            $res['code'] = 2;
            addlog($res['id']);//记录日志
            return json($res);
        } else {
            // 上传失败获取错误信息
            return $this->error('上传失败：' . $file->getError());
        }
    }

    /**
     * 档案管理-工程项目管理
     * @param $id
     * @param $draw
     * @param $table
     * @param $search
     * @param $start
     * @param $length
     * @param $limitFlag
     * @param $order
     * @param $columns
     * @param $columnString
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function file_project_management($draw, $table, $search, $start, $length, $limitFlag, $order, $columns, $columnString)
    {
        //查询
        //条件过滤后记录数 必要
        $recordsFiltered = 0;
        //获取筛选条件
        //表的总记录数 必要
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