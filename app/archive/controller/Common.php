<?php
/**
 * Created by PhpStorm.
 * User: zhifang
 * Date: 2018/3/23
 * Time: 15:32
 */

namespace app\archive\controller;

use app\admin\model\AdminGroup;
use app\archive\model\DocumentTypeModel;
use app\archive\model\AtlasCateTypeModel;
use app\archive\model\AtlasCateModel;
use \think\Controller;
use think\Db;
use think\Request;
use \think\Session;

class Common extends Controller
{
    protected $documentTypeService;

    public function __construct(Request $request = null)
    {
        parent::__construct($request);
        $this->documentTypeService = new DocumentTypeModel();
    }


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

    function archive_document($draw, $table, $search, $start, $length, $limitFlag, $order, $columns, $columnString)
    {
        //查询
        //条件过滤后记录数 必要
        $recordsFiltered = 0;
        //表的总记录数 必要
        $id = input('id');
        $idArr = $this->documentTypeService->getChilds($id);
        $idArr[] = $id;
        $recordsTotal = 0;
        $recordsTotal = Db::name($table)->whereIn('type', $idArr)->count(0);
        $recordsFilteredResult = array();
        if (strlen($search) > 0) {
            //有搜索条件的情况
            if ($limitFlag) {
                //*****多表查询join改这里******
                $recordsFilteredResult = Db::name($table)->alias('a')
                    ->join('attachment f', 'a.attachmentId = f.id', 'left')
                    ->join('admin u', 'f.user_id=u.id', 'left')
                    ->field('a.id,a.docname,u.nickname,FROM_UNIXTIME(f.create_time) as create_time,a.status')
                    ->whereIn('a.type', $idArr)
                    ->where('a.docname|u.nickname', 'like', '%' . $search . '%')->order($order)->limit(intval($start), intval($length))->select();
                $recordsFiltered = sizeof($recordsFilteredResult);
            }
        } else {
            //没有搜索条件的情况
            if ($limitFlag) {
                $recordsFilteredResult = Db::name($table)->alias('a')
                    ->join('attachment f', 'a.attachmentId = f.id', 'left')
                    ->join('admin u', 'f.user_id=u.id', 'left')
                    ->field('a.id,a.docname,u.nickname,FROM_UNIXTIME(f.create_time) as create_time,a.status')
                    ->whereIn('a.type', $idArr)
                    ->select();
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

    /**
     * 图册文件表
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

    public function archive_atlas_cate($draw, $table, $search, $start, $length, $limitFlag, $order, $columns, $columnString)
    {
        //查询
        //条件过滤后记录数 必要
        $recordsFiltered = 0;
        //表的总记录数 必要
        $id = input('selfid');
        //表的总记录数 必要
        $recordsTotal = 0;
        $recordsTotal = Db::name($table)->where('selfid',$id)->where("pid = 0")->count(0);
        $recordsFilteredResult = array();
        if(strlen($search)>0){
            //有搜索条件的情况
            if($limitFlag){
                //*****多表查询join改这里******
                $recordsFilteredResult = Db::name($table)->where('selfid',$id)->where("pid = 0")->where($columnString, 'like', '%' . $search . '%')->order($order)->limit(intval($start),intval($length))->select();
                $recordsFiltered = sizeof($recordsFilteredResult);
            }
        }else{
            //没有搜索条件的情况
            if($limitFlag){
                $recordsFilteredResult = Db::name($table)->where('selfid',$id)->where("pid = 0")->order($order)->limit(intval($start),intval($length))->select();
                $recordsFiltered = $recordsTotal;
            }
        }


        //实例化model类
        $atlascate = new AtlasCateModel();
        $temp = array();
        $infos = array();
        foreach ($recordsFilteredResult as $key => $value) {
            //计算列长度
            $length = sizeof($columns);
            for ($i = 0; $i < $length; $i++) {
                array_push($temp, $value[$columns[$i]['name']]);
            }
            $children_info = $atlascate->getAllpicture($temp['13']);

            array_push($temp, $children_info);

            $infos[] = $temp;

            $temp = [];

        }


        return json(['draw' => intval($draw), 'recordsTotal' => intval($recordsTotal), 'recordsFiltered' => $recordsFiltered, 'data' => $infos]);
    }

    /**
     * 图册下载记录
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
    public function archive_atlas_download_record($draw,$table,$search,$start,$length,$limitFlag,$order,$columns,$columnString)
    {

        //查询
        //条件过滤后记录数 必要
        $recordsFiltered = 0;
        //表的总记录数 必要
        $recordsTotal = 0;
        //传过来的id,类别id
        $id = input('param.id');
        $recordsTotal = Db::name($table)->where('cate_id',$id)->count(0);
        $recordsFilteredResult = array();
        //没有搜索条件的情况
        $recordsFilteredResult = Db::name($table)->where('cate_id',$id)->order("create_time desc")->limit(intval($start), intval($length))->select();

        //*****多表查询join改这里******
        //$recordsFilteredResult = Db::name('datatables_example')->alias('d')->join('datatables_example_join e','d.position = e.id')->field('d.id,d.name,e.name as position,d.office')->select();
        $recordsFiltered = $recordsTotal;


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

    /**
     * 文档下载记录
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
    public function archive_document_downrecord($draw,$table,$search,$start,$length,$limitFlag,$order,$columns,$columnString)
    {

        //查询
        //条件过滤后记录数 必要
        $recordsFiltered = 0;
        //表的总记录数 必要
        $recordsTotal = 0;
        //传过来的id,类别id
        $id = input('param.id');
        $recordsTotal = Db::name($table)->where('docId',$id)->count(0);
        $recordsFilteredResult = array();
        //没有搜索条件的情况
        $recordsFilteredResult = Db::name($table)->where('docId',$id)->order("create_time desc")->limit(intval($start), intval($length))->select();

        //*****多表查询join改这里******
        //$recordsFilteredResult = Db::name('datatables_example')->alias('d')->join('datatables_example_join e','d.position = e.id')->field('d.id,d.name,e.name as position,d.office')->select();
        $recordsFiltered = $recordsTotal;


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
    /**
     * 图册图片文件上传
     * @param string $module
     * @param string $use
     * @return \think\response\Json|void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function upload($module = 'atlas', $use = 'atlas_thumb')
    {
        if ($this->request->file('file')) {
            $file = $this->request->file('file');
        } else {
            $res['code'] = 1;
            $res['msg'] = '没有上传文件';
            return json($res);
        }
        //接收前台传过来的文件
        $accept_file = $_FILES["file"];

        $module = $this->request->has('module') ? $this->request->param('module') : $module;//模块
        $web_config = Db::name('admin_webconfig')->where('web', 'web')->find();
        $info = $file->validate(['size' => $web_config['file_size'] * 1024, 'ext' => $web_config['file_type']])->rule('date')->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . $module . DS . $use);
        if ($info) {
            //写入到附件表
            $data = [];
            $data['module'] = $module;
            $data['name'] = $accept_file["name"];//上传原文件名
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
            return json($res);
        } else {
            // 上传失败获取错误信息
            return $this->error('上传失败：' . $file->getError());
        }
    }

    // 收文 或 发文 列表 和 发文 选择关联文件列表
    function archive_income_send($draw, $table, $search, $start, $length, $limitFlag, $order, $columns, $columnString)
    {
        //条件过滤后记录数 必要
        $recordsFiltered = 0;
        //表的总记录数 必要
        //传过来的类别 table_type 1 收文 2 发文  3 关联文件列表
        $type = input('param.table_type');
        $uid = Session::has('admin') ? Session::get('admin') : 0;
        if($type == 1){
            $recordsTotal = Db::name($table)->where('income_id', $uid)->count();
        }else if($type == 2){
            $recordsTotal = Db::name($table)->where('send_id', $uid)->count();
        }else{
            $recordsTotal = Db::name($table)->where(['send_id'=>$uid,'status'=>3])->count();
        }

        switch ($order){
            case 'create_time asc':
                $order = 's.create_time desc';
                break;
            case 'create_time desc':
                $order = 's.create_time asc';
                break;
            case 'file_name asc':
                $order = 's.file_name asc';
                break;
            case 'file_name desc':
                $order = 's.file_name desc';
                break;
            case 'date asc':
                $order = 's.date asc';
                break;
            case 'date desc':
                $order = 's.date desc';
                break;
            case 'unit_name asc':
                $order = 't.name asc';
                break;
            case 'unit_name desc':
                $order = 't.name desc';
                break;
            case 'attchment_id asc':
                $order = 'u.nickname asc';
                break;
            case 'attchment_id desc':
                $order = 'u.nickname desc';
                break;
            case 'income_name asc':
                $order = 'u.nickname asc';
                break;
            case 'income_name desc':
                $order = 'u.nickname desc';
                break;
            default :
        }

        $recordsFilteredResult = array();

        if (strlen($search) > 0) {
            //有搜索条件的情况
            if ($limitFlag) {
                //*****多表查询join改这里******
                $exArr = explode('|',$columnString);
                $newColumnString = '';
                foreach($exArr as $ex){
                    switch ($ex){
                        case 'id':
                            $newColumnString = 's.id' . '|' . $newColumnString;
                            break;
                        case 'file_name':
                            $newColumnString = 's.file_name' . '|' . $newColumnString;
                            break;
                        case 'date':
                            $newColumnString = 's.date' . '|' . $newColumnString;
                            break;
                        case 'unit_name':
                            $newColumnString = 't.name' . '|' . $newColumnString;
                            break;
                        case 'attchment_id':
                            $newColumnString = 'u.nickname' . '|' . $newColumnString;
                            break;
                        case 'send_name':
                            $newColumnString = 'u.nickname' . '|' . $newColumnString;
                            break;
                        case 'status':
                            $newColumnString = 's.status' . '|' . $newColumnString;
                            break;
                        default :
                    }
                }
                $newColumnString = substr($newColumnString,0,strlen($newColumnString)-1);

                if(in_array($search,['未','未发','未发送'])){
                    $search = 1;$newColumnString = 's.status';
                }if(in_array($search,['已发','已发送'])){
                    $search = 2;$newColumnString = 's.status';
                }if(in_array($search,['签','签收','已签收'])){
                    $search = 3;$newColumnString = 's.status';
                }if(in_array($search,['拒','拒收','已拒收'])){
                    $search = 4;$newColumnString = 's.status';
                }

                if($type == 1){
                    // 收文 查询 发件人的名称和单位
                    $recordsFilteredResult = Db::name($table)->alias('s')
                        ->join('admin u', 's.send_id=u.id', 'left')
                        ->join('admin_group g', 'u.admin_group_id=g.id', 'left')
                        ->join('admin_group_type t', 'g.type=t.id', 'left')
                        ->field('s.id,s.file_name,s.date,t.name as unit_name,s.attchment_id,u.nickname as send_name,s.status,FROM_UNIXTIME(s.create_time) as create_time')
                        ->where($newColumnString, 'like', '%' . $search . '%')
                        ->where(['s.income_id'=>$uid,'s.status'=>['neq',1]])
                        ->order($order)->limit(intval($start), intval($length))->select();
                }else if($type == 2){
                    // 发文 查询 收件人的名称和单位
                    $recordsFilteredResult = Db::name($table)->alias('s')
                        ->join('admin u', 's.income_id=u.id', 'left')
                        ->join('admin_group g', 'u.admin_group_id=g.id', 'left')
                        ->join('admin_group_type t', 'g.type=t.id', 'left')
                        ->field('s.id,s.file_name,s.date,t.name as unit_name,u.name,s.attchment_id,u.nickname as income_name,FROM_UNIXTIME(s.create_time) as create_time')
                        ->where($newColumnString, 'like', '%' . $search . '%')
                        ->where(['s.send_id'=>$uid])
                        ->order($order)->limit(intval($start), intval($length))->select();
                }else{
                    $recordsFilteredResult = Db::name($table)->alias('s')
                        ->join('admin u', 's.send_id=u.id', 'left')
                        ->join('admin_group g', 'u.admin_group_id=g.id', 'left')
                        ->join('admin_group_type t', 'g.type=t.id', 'left')
                        ->field('s.id,s.file_name,s.date,t.name as unit_name,s.attchment_id,u.nickname as send_name,s.status,FROM_UNIXTIME(s.create_time) as create_time')
                        ->where($newColumnString, 'like', '%' . $search . '%')
                        ->where(['s.income_id'=>$uid,'s.status'=>['eq',3]])
                        ->order($order)->limit(intval($start), intval($length))->select();
                }
                $recordsFiltered = sizeof($recordsFilteredResult);
            }
        } else {
            //没有搜索条件的情况
            if ($limitFlag) {
                if($type == 1){
                    // 收文 查询 发件人的名称和单位
                    $recordsFilteredResult = Db::name($table)->alias('s')
                        ->join('admin u', 's.send_id=u.id', 'left')
                        ->join('admin_group g', 'u.admin_group_id=g.id', 'left')
                        ->join('admin_group_type t', 'g.type=t.id', 'left')
                        ->field('s.id,s.file_name,s.date,t.name as unit_name,s.attchment_id,u.nickname as send_name,s.status,FROM_UNIXTIME(s.create_time) as create_time')
                        ->where(['s.income_id'=>$uid,'s.status'=>['neq',1]])
                        ->order($order)->limit(intval($start), intval($length))->select();
                }else if($type == 2){
                    // 发文 查询 收件人的名称和单位
                    $recordsFilteredResult = Db::name($table)->alias('s')
                        ->join('admin u', 's.income_id=u.id', 'left')
                        ->join('admin_group g', 'u.admin_group_id=g.id', 'left')
                        ->join('admin_group_type t', 'g.type=t.id', 'left')
                        ->field('s.id,s.file_name,s.date,t.name as unit_name,s.attchment_id,u.nickname as income_name,s.status,FROM_UNIXTIME(s.create_time) as create_time')
                        ->where(['s.send_id'=>$uid])
                        ->order($order)->limit(intval($start), intval($length))->select();
                }else{
                    $recordsFilteredResult = Db::name($table)->alias('s')
                        ->join('admin u', 's.send_id=u.id', 'left')
                        ->join('admin_group g', 'u.admin_group_id=g.id', 'left')
                        ->join('admin_group_type t', 'g.type=t.id', 'left')
                        ->field('s.id,s.file_name,s.date,t.name as unit_name,s.attchment_id,u.nickname as send_name,s.status,FROM_UNIXTIME(s.create_time) as create_time')
                        ->where(['s.income_id'=>$uid,'s.status'=>['eq',3]])
                        ->order($order)->limit(intval($start), intval($length))->select();
                }
                $recordsFiltered = $recordsTotal;
            }
        }
        $temp = array();
        $infos = array();
        foreach ($recordsFilteredResult as $key => $value) {
            //计算列长度
            $length = sizeof($columns);
            for ($i = 0; $i < $length; $i++) {
                if($columns[$i]['name'] == 'attchment_id'){
                    $user_name = Session::has('current_nickname') ? Session::get('current_nickname') : '';
                    array_push($temp, $user_name);
                }else{
                    array_push($temp, $value[$columns[$i]['name']]);
                }
            }
            $infos[] = $temp;
            $temp = [];
        }
        return json(['draw' => intval($draw), 'recordsTotal' => intval($recordsTotal), 'recordsFiltered' => $recordsFiltered, 'data' => $infos]);
    }

}