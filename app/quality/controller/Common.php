<?php
/**
 * Created by PhpStorm.
 * User: sir
 * Date: 2018/4/3
 * Time: 9:43
 */

namespace app\quality\controller;


use app\quality\model\UnitqualitymanageModel;
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

    public function archive_atlas_cate($id, $draw, $table, $search, $start, $length, $limitFlag, $order, $columns, $columnString)
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
                $recordsFilteredResult = Db::name($table)
                    ->field('picture_number,picture_name,picture_papaer_num,a1_picture,design_name,check_name,examination_name,FROM_UNIXTIME(completion_time,\'%Y-%m-%d\') as completion_time,section,paper_category,id')
                    ->where($columnString, 'like', '%' . $search . '%')
                    ->order($order)->limit(intval($start), intval($length))->select();
                $recordsFiltered = sizeof($recordsFilteredResult);
            }
        } else {
            //没有搜索条件的情况
            if ($limitFlag) {
                //*****多表查询join改这里******
                $recordsFilteredResult = Db::name($table)
                    ->field('picture_number,picture_name,picture_papaer_num,a1_picture,design_name,check_name,examination_name,FROM_UNIXTIME(completion_time,\'%Y-%m-%d\') as completion_time,section,paper_category,id')
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

    public function quality_unit($id, $draw, $table, $search, $start, $length, $limitFlag, $order, $columns, $columnString)
    {
        //查询
        //条件过滤后记录数 必要
        $recordsFiltered = 0;
        $recordsFilteredResult = array();
        //表的总记录数 必要
        $recordsTotal = Db::name($table)->where('division_id', $id)->count();
        if (strlen($search) > 0) {
            //有搜索条件的情况
            if ($limitFlag) {
                //*****多表查询join改这里******
                $recordsFilteredResult = Db::name($table)
                    ->field('serial_number,site,coding,hinge,pile_number,start_date,completion_date,id,en_type')
                    ->where('division_id', $id)
                    ->where($columnString, 'like', '%' . $search . '%')
                    ->order($order)->select();
//                    ->order($order)->limit(intval($start), intval($length))->select();
                $recordsFiltered = sizeof($recordsFilteredResult);
            }
        } else {
            //没有搜索条件的情况
            if ($limitFlag) {
                //*****多表查询join改这里******
                $recordsFilteredResult = Db::name($table)
                    ->field('serial_number,site,coding,hinge,pile_number,start_date,completion_date,id,en_type')
                    ->where('division_id', $id)
                    ->order($order)->select();
//                    ->order($order)->limit(intval($start), intval($length))->select();
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

    /**
     * 质量管理文件上传
     * @param string $module
     * @param string $use
     * @return \think\response\Json|void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function upload($module = 'quality', $use = 'quality_thumb')
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
//            $res['filename'] = $info->getFilename();//文件名
            $res['src'] = DS . 'uploads' . DS . $module . DS . $use . DS . $info->getSaveName();
            $res['code'] = 2;
            return json($res);
        } else {
            // 上传失败获取错误信息
            return $this->error('上传失败：' . $file->getError());
        }
    }

    /**
     * 质量管理文件下载
     * @return \think\response\Json
     */
    public function download()
    {
        if (request()->isAjax()) {
            $id = input('param.id');//id
            $type_model = input('param.type_model');//model类名
            //拼接model类的地址
            $type_model = "app\\quality\\model\\" . $type_model;
            //实例化model类
            $model = new $type_model;
            $param = $model->getOne($id);
            //查询attachment文件上传表中的文件上传路径
            $attachment = Db::name("attachment")->where("id", $param["attachment_id"])->find();
            //上传文件路径
            $path = $attachment["filepath"];
            if (!$path || !file_exists("." . $path)) {
                return json(['code' => '-1', 'msg' => '文件不存在']);
            }
            return json(['code' => 1]);
        }
        $id = input('param.id');

        $type_model = input('param.type_model');//model类名
        //拼接model类的地址
        $type_model = "app\\quality\\model\\" . $type_model;
        //实例化model类
        $model = new $type_model();
        $param = $model->getOne($id);
        //查询attachment文件上传表中的文件上传路径
        $attachment = Db::name("attachment")->where("id", $param["attachment_id"])->find();
        //上传文件路径
        $path = $attachment["filepath"];

        $filePath = '.' . $path;
        $fileName = $attachment['name'];
        $file = fopen($filePath, "r"); //   打开文件
        //输入文件标签
        $fileName = iconv("utf-8", "gb2312", $fileName);
        Header("Content-type:application/octet-stream ");
        Header("Accept-Ranges:bytes ");
        Header("Accept-Length:   " . filesize($filePath));
        Header("Content-Disposition:   attachment;   filename= " . $fileName);
        //   输出文件内容
        echo fread($file, filesize($filePath));
        fclose($file);
        exit;

    }

    /**
     * 质量管理文件预览
     * @return \think\response\Json
     */
    public function preview()
    {
        if (request()->isAjax()) {
            $param = input('post.');
            $type_model = input('param.type_model');//model类名
            //拼接model类的地址
            $type_model = "app\\quality\\model\\" . $type_model;
            //实例化model类
            $model = new $type_model();
            $code = 1;
            $msg = '预览成功';
            $data = $model->getOne($param['id']);
            //查询attachment文件上传表中的文件上传路径
            $attachment = Db::name("attachment")->where("id", $data["attachment_id"])->find();
            //上传文件路径
            $path = $attachment["filepath"];
            if (!$path || !file_exists("." . $path)) {
                return json(['code' => '-1', 'msg' => '文件不存在']);
            }
            $extension = strtolower(get_extension(substr($path, 1)));
            $pdf_path = './uploads/temp/' . basename($path) . '.pdf';
            if (!file_exists($pdf_path)) {
                if ($extension === 'doc' || $extension === 'docx' || $extension === 'txt') {
                    doc_to_pdf($path);
                } else if ($extension === 'xls' || $extension === 'xlsx') {
                    excel_to_pdf($path);
                } else if ($extension === 'ppt' || $extension === 'pptx') {
                    ppt_to_pdf($path);
                } else if ($extension === 'pdf') {
                    $pdf_path = $path;
                } else if ($extension === "jpg" || $extension === "png" || $extension === "jpeg") {
                    $pdf_path = $path;
                } else {
                    $code = 0;
                    $msg = '不支持的文件格式';
                }
                return json(['code' => $code, 'path' => substr($pdf_path, 1), 'msg' => $msg]);
            } else {
                return json(['code' => $code, 'path' => substr($pdf_path, 1), 'msg' => $msg]);
            }
        }
    }

    /**
     * 日常质量管理-现场图片
     * @throws \think\exception\DbException
     */

    public function quality_scene_picture($id, $draw, $table, $search, $start, $length, $limitFlag, $order, $columns, $columnString)
    {
        //自定义查询条件
        $columnString = "s.filename|n.nickname|g.name";
        //条件过滤后记录数 必要
        $recordsFiltered = 0;
        //表的总记录数 必要
        $year = input('year') ? input('year') : "";//年
        $month = input('month') ? input('month') : "";//月
        $day = input('day') ? input('day') : "";//日

        $admin_group_id = input('admin_group_id') ? input('admin_group_id') : "";
        if ($admin_group_id) {
            $group_data = [
                "s.admin_group_id" => $admin_group_id
            ];
        } else {
            $group_data = [
            ];
        }


        if (!$year && !$month && !$day)//如果年月日都不存在
        {
            $search_data = [
            ];
        } else if ($year && $month && $day)//如果年月日都存在
        {
            $search_data = [
                "s.year" => $year,
                "s.month" => $month,
                "s.day" => $day
            ];
        } else if ($year && !$month && !$day)//如果年都存在
        {
            $search_data = [
                "s.year" => $year
            ];
        } else if ($year && $month && !$day)//如果年月都存在
        {
            $search_data = [
                "s.year" => $year,
                "s.month" => $month
            ];
        }


        //表的总记录数 必要
        $recordsTotal = 0;
        $recordsTotal = Db::name($table)->alias("s")->where($search_data)->where($group_data)->where("s.admin_group_id > 0")->count(0);
        $recordsFilteredResult = array();
        if (strlen($search) > 0) {
            //有搜索条件的情况
            if ($limitFlag) {
                //*****多表查询join改这里******
                $recordsFilteredResult = Db::name($table)
                    ->alias("s")
                    ->join('attachment m', 'm.id = s.attachment_id', 'left')
                    ->join('admin n', 'n.id = m.user_id', 'left')
                    ->join('admin_group g', 'g.id = n.admin_group_id', 'left')
                    ->field("s.filename,m.create_time,n.nickname as owner,g.name as company,s.position,s.id")
                    ->where($search_data)->where("s.admin_group_id > 0")
                    ->where($group_data)->where($columnString, 'like', '%' . $search . '%')
                    ->order($order)->limit(intval($start), intval($length))
                    ->select();
                $recordsFiltered = sizeof($recordsFilteredResult);
            }
        } else {
            //没有搜索条件的情况
            if ($limitFlag) {
                $recordsFilteredResult = Db::name($table)
                    ->alias("s")
                    ->join('attachment m', 'm.id = s.attachment_id', 'left')
                    ->join('admin n', 'n.id = m.user_id', 'left')
                    ->join('admin_group g', 'g.id = n.admin_group_id', 'left')
                    ->field("s.filename,m.create_time,n.nickname as owner,g.name as company,s.position,s.id")
                    ->where($search_data)->where("s.admin_group_id > 0")
                    ->where($group_data)->order($order)->limit(intval($start), intval($length))
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
     * 日常质量管理-监理日志
     * @throws \think\exception\DbException
     */

    public function quality_supervision_log($id, $draw, $table, $search, $start, $length, $limitFlag, $order, $columns, $columnString)
    {
        //自定义查询条件
        $columnString = "s.filename|n.nickname|g.name";
        //条件过滤后记录数 必要
        $recordsFiltered = 0;
        //表的总记录数 必要
        $year = input('year') ? input('year') : "";//年
        $month = input('month') ? input('month') : "";//月
        $day = input('day') ? input('day') : "";//日

        if (!$year && !$month && !$day)//如果年月日都不存在
        {
            $search_data = [
            ];
        } else if ($year && $month && $day)//如果年月日都存在
        {
            $search_data = [
                "s.year" => $year,
                "s.month" => $month,
                "s.day" => $day
            ];
        } else if ($year && !$month && !$day)//如果年都存在
        {
            $search_data = [
                "s.year" => $year
            ];
        } else if ($year && $month && !$day)//如果年月都存在
        {
            $search_data = [
                "s.year" => $year,
                "s.month" => $month
            ];
        }


        //表的总记录数 必要
        $recordsTotal = 0;
        $recordsTotal = Db::name($table)->alias("s")->where($search_data)->where("s.admin_group_id > 0")->count(0);
        $recordsFilteredResult = array();
        if (strlen($search) > 0) {
            //有搜索条件的情况
            if ($limitFlag) {
                //*****多表查询join改这里******
                $recordsFilteredResult = Db::name($table)
                    ->alias("s")
                    ->join('attachment m', 'm.id = s.attachment_id', 'left')
                    ->join('admin n', 'n.id = m.user_id', 'left')
                    ->join('admin_group g', 'g.id = n.admin_group_id', 'left')
                    ->field("s.filename,m.create_time,n.nickname as owner,g.name as company,s.position,s.id")
                    ->where($search_data)->where("s.admin_group_id > 0")
                    ->where($columnString, 'like', '%' . $search . '%')
                    ->order($order)->limit(intval($start), intval($length))
                    ->select();
                $recordsFiltered = sizeof($recordsFilteredResult);
            }
        } else {
            //没有搜索条件的情况
            if ($limitFlag) {
                $recordsFilteredResult = Db::name($table)
                    ->alias("s")
                    ->join('attachment m', 'm.id = s.attachment_id', 'left')
                    ->join('admin n', 'n.id = m.user_id', 'left')
                    ->join('admin_group g', 'g.id = n.admin_group_id', 'left')
                    ->field("s.filename,m.create_time,n.nickname as owner,g.name as company,s.position,s.id")
                    ->where($search_data)
                    ->where("s.admin_group_id > 0")->order($order)->limit(intval($start), intval($length))
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
     * 日常质量管理-巡视记录
     * @throws \think\exception\DbException
     */

    public function quality_patrol_record($id, $draw, $table, $search, $start, $length, $limitFlag, $order, $columns, $columnString)
    {
        //自定义查询条件
        $columnString = "s.filename|n.nickname|g.name";
        //条件过滤后记录数 必要
        $recordsFiltered = 0;
        //表的总记录数 必要
        $year = input('year') ? input('year') : "";//年
        $month = input('month') ? input('month') : "";//月
        $day = input('day') ? input('day') : "";//日

        if (!$year && !$month && !$day)//如果年月日都不存在
        {
            $search_data = [
            ];
        } else if ($year && $month && $day)//如果年月日都存在
        {
            $search_data = [
                "s.year" => $year,
                "s.month" => $month,
                "s.day" => $day
            ];
        } else if ($year && !$month && !$day)//如果年都存在
        {
            $search_data = [
                "s.year" => $year
            ];
        } else if ($year && $month && !$day)//如果年月都存在
        {
            $search_data = [
                "s.year" => $year,
                "s.month" => $month
            ];
        }


        //表的总记录数 必要
        $recordsTotal = 0;
        $recordsTotal = Db::name($table)->alias("s")->where($search_data)->where("s.admin_group_id > 0")->count(0);
        $recordsFilteredResult = array();
        if (strlen($search) > 0) {
            //有搜索条件的情况
            if ($limitFlag) {
                //*****多表查询join改这里******
                $recordsFilteredResult = Db::name($table)
                    ->alias("s")
                    ->join('attachment m', 'm.id = s.attachment_id', 'left')
                    ->join('admin n', 'n.id = m.user_id', 'left')
                    ->join('admin_group g', 'g.id = n.admin_group_id', 'left')
                    ->field("s.filename,m.create_time,n.nickname as owner,g.name as company,s.position,s.id")
                    ->where($search_data)
                    ->where("s.admin_group_id > 0")
                    ->where($columnString, 'like', '%' . $search . '%')->order($order)->limit(intval($start), intval($length))
                    ->select();
                $recordsFiltered = sizeof($recordsFilteredResult);
            }
        } else {
            //没有搜索条件的情况
            if ($limitFlag) {
                $recordsFilteredResult = Db::name($table)
                    ->alias("s")
                    ->join('attachment m', 'm.id = s.attachment_id', 'left')
                    ->join('admin n', 'n.id = m.user_id', 'left')
                    ->join('admin_group g', 'g.id = n.admin_group_id', 'left')
                    ->field("s.filename,m.create_time,n.nickname as owner,g.name as company,s.position,s.id")
                    ->where($search_data)->where("s.admin_group_id > 0")->order($order)->limit(intval($start), intval($length))
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
     * 日常质量管理-旁站记录
     * @throws \think\exception\DbException
     */

    public function quality_side_reporting($id, $draw, $table, $search, $start, $length, $limitFlag, $order, $columns, $columnString)
    {
        //自定义查询条件
        $columnString = "s.filename|n.nickname|g.name";
        //条件过滤后记录数 必要
        $recordsFiltered = 0;
        //表的总记录数 必要
        $year = input('year') ? input('year') : "";//年
        $month = input('month') ? input('month') : "";//月
        $day = input('day') ? input('day') : "";//日

        if (!$year && !$month && !$day)//如果年月日都不存在
        {
            $search_data = [
            ];
        } else if ($year && $month && $day)//如果年月日都存在
        {
            $search_data = [
                "s.year" => $year,
                "s.month" => $month,
                "s.day" => $day
            ];
        } else if ($year && !$month && !$day)//如果年都存在
        {
            $search_data = [
                "s.year" => $year
            ];
        } else if ($year && $month && !$day)//如果年月都存在
        {
            $search_data = [
                "s.year" => $year,
                "s.month" => $month
            ];
        }


        //表的总记录数 必要
        $recordsTotal = 0;
        $recordsTotal = Db::name($table)->alias("s")->where($search_data)->where("s.admin_group_id > 0")->count(0);
        $recordsFilteredResult = array();
        if (strlen($search) > 0) {
            //有搜索条件的情况
            if ($limitFlag) {
                //*****多表查询join改这里******
                $recordsFilteredResult = Db::name($table)
                    ->alias("s")
                    ->join('attachment m', 'm.id = s.attachment_id', 'left')
                    ->join('admin n', 'n.id = m.user_id', 'left')
                    ->join('admin_group g', 'g.id = n.admin_group_id', 'left')
                    ->field("s.filename,m.create_time,n.nickname as owner,g.name as company,s.position,s.id")
                    ->where($search_data)->where("s.admin_group_id > 0")
                    ->where($columnString, 'like', '%' . $search . '%')
                    ->order($order)->limit(intval($start), intval($length))
                    ->select();
                $recordsFiltered = sizeof($recordsFilteredResult);
            }
        } else {
            //没有搜索条件的情况
            if ($limitFlag) {
                $recordsFilteredResult = Db::name($table)
                    ->alias("s")
                    ->join('attachment m', 'm.id = s.attachment_id', 'left')
                    ->join('admin n', 'n.id = m.user_id', 'left')
                    ->join('admin_group g', 'g.id = n.admin_group_id', 'left')
                    ->field("s.filename,m.create_time,n.nickname as owner,g.name as company,s.position,s.id")
                    ->where($search_data)->where("s.admin_group_id > 0")
                    ->order($order)->limit(intval($start), intval($length))
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
     * 日常质量管理-抽检记录
     * @throws \think\exception\DbException
     */

    public function quality_sampling($id, $draw, $table, $search, $start, $length, $limitFlag, $order, $columns, $columnString)
    {
        //自定义查询条件
        $columnString = "s.filename|n.nickname|g.name";
        //条件过滤后记录数 必要
        $recordsFiltered = 0;
        //表的总记录数 必要
        $year = input('year') ? input('year') : "";//年
        $month = input('month') ? input('month') : "";//月
        $day = input('day') ? input('day') : "";//日

        if (!$year && !$month && !$day)//如果年月日都不存在
        {
            $search_data = [
            ];
        } else if ($year && $month && $day)//如果年月日都存在
        {
            $search_data = [
                "s.year" => $year,
                "s.month" => $month,
                "s.day" => $day
            ];
        } else if ($year && !$month && !$day)//如果年都存在
        {
            $search_data = [
                "s.year" => $year
            ];
        } else if ($year && $month && !$day)//如果年月都存在
        {
            $search_data = [
                "s.year" => $year,
                "s.month" => $month
            ];
        }


        //表的总记录数 必要
        $recordsTotal = 0;
        $recordsTotal = Db::name($table)->alias("s")->where($search_data)->where("s.admin_group_id > 0")->count(0);
        $recordsFilteredResult = array();
        if (strlen($search) > 0) {
            //有搜索条件的情况
            if ($limitFlag) {
                //*****多表查询join改这里******
                $recordsFilteredResult = Db::name($table)
                    ->alias("s")
                    ->join('attachment m', 'm.id = s.attachment_id', 'left')
                    ->join('admin n', 'n.id = m.user_id', 'left')
                    ->join('admin_group g', 'g.id = n.admin_group_id', 'left')
                    ->field("s.filename,m.create_time,n.nickname as owner,g.name as company,s.position,s.id")
                    ->where($search_data)
                    ->where("s.admin_group_id > 0")
                    ->where($columnString, 'like', '%' . $search . '%')->order($order)->limit(intval($start), intval($length))
                    ->select();
                $recordsFiltered = sizeof($recordsFilteredResult);
            }
        } else {
            //没有搜索条件的情况
            if ($limitFlag) {
                $recordsFilteredResult = Db::name($table)
                    ->alias("s")
                    ->join('attachment m', 'm.id = s.attachment_id', 'left')
                    ->join('admin n', 'n.id = m.user_id', 'left')
                    ->join('admin_group g', 'g.id = n.admin_group_id', 'left')
                    ->field("s.filename,m.create_time,n.nickname as owner,g.name as company,s.position,s.id")
                    ->where($search_data)
                    ->where("s.admin_group_id > 0")
                    ->order($order)->limit(intval($start), intval($length))
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
     * 日常质量管理-监理指令
     * @throws \think\exception\DbException
     */

    public function quality_supervision_instruction($id, $draw, $table, $search, $start, $length, $limitFlag, $order, $columns, $columnString)
    {
        //自定义查询条件
        $columnString = "s.filename|n.nickname|g.name";
        //条件过滤后记录数 必要
        $recordsFiltered = 0;
        //表的总记录数 必要
        $year = input('year') ? input('year') : "";//年
        $month = input('month') ? input('month') : "";//月
        $day = input('day') ? input('day') : "";//日

        if (!$year && !$month && !$day)//如果年月日都不存在
        {
            $search_data = [
            ];
        } else if ($year && $month && $day)//如果年月日都存在
        {
            $search_data = [
                "s.year" => $year,
                "s.month" => $month,
                "s.day" => $day
            ];
        } else if ($year && !$month && !$day)//如果年都存在
        {
            $search_data = [
                "s.year" => $year
            ];
        } else if ($year && $month && !$day)//如果年月都存在
        {
            $search_data = [
                "s.year" => $year,
                "s.month" => $month
            ];
        }


        //表的总记录数 必要
        $recordsTotal = 0;
        $recordsTotal = Db::name($table)->alias("s")->where($search_data)->where("s.admin_group_id > 0")->count(0);
        $recordsFilteredResult = array();
        if (strlen($search) > 0) {
            //有搜索条件的情况
            if ($limitFlag) {
                //*****多表查询join改这里******
                $recordsFilteredResult = Db::name($table)
                    ->alias("s")
                    ->join('attachment m', 'm.id = s.attachment_id', 'left')
                    ->join('admin n', 'n.id = m.user_id', 'left')
                    ->join('admin_group g', 'g.id = n.admin_group_id', 'left')
                    ->field("s.filename,m.create_time,n.nickname as owner,g.name as company,s.position,s.id")
                    ->where($search_data)->where("s.admin_group_id > 0")
                    ->where($columnString, 'like', '%' . $search . '%')->order($order)->limit(intval($start), intval($length))
                    ->select();
                $recordsFiltered = sizeof($recordsFilteredResult);
            }
        } else {
            //没有搜索条件的情况
            if ($limitFlag) {
                $recordsFilteredResult = Db::name($table)
                    ->alias("s")
                    ->join('attachment m', 'm.id = s.attachment_id', 'left')
                    ->join('admin n', 'n.id = m.user_id', 'left')
                    ->join('admin_group g', 'g.id = n.admin_group_id', 'left')
                    ->field("s.filename,m.create_time,n.nickname as owner,g.name as company,s.position,s.id")
                    ->where($search_data)->where("s.admin_group_id > 0")
                    ->order($order)->limit(intval($start), intval($length))
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

    //单元策划：控制点列表
    public function quality_division_controlpoint_relation($id, $draw, $table, $search, $start, $length, $limitFlag, $order, $columns, $columnString)
    {
        //查询
        //条件过滤后记录数 必要
        $recordsFiltered = 0;
        $recordsFilteredResult = array();
        $par = array();
        $par['type'] = 1;
        $par['division_id'] = $this->request->param('division_id');

        if ($this->request->has('ma_division_id')) {
            $par['ma_division_id'] = $this->request->param('ma_division_id');
        }
        //表的总记录数 必要
        $recordsTotal = Db::name($table)->where($par)->count();
        if (strlen($search) > 0) {
            //有搜索条件的情况
            if ($limitFlag) {
                //*****多表查询join改这里******
                $recordsFilteredResult = Db::name($table)->alias('a')
                    ->join('norm_controlpoint b', 'a.control_id=b.id', 'left')
                    ->where($par)
                    ->field('a.id,b.code,b.name,a.status,a.division_id,a.ma_division_id,a.control_id,a.checked,b.remark')
                    ->order($order)->limit(intval($start), intval($length))->select();
                $recordsFiltered = sizeof($recordsFilteredResult);
            }
        } else {
            //没有搜索条件的情况
            if ($limitFlag) {
                //*****多表查询join改这里******
                $recordsFilteredResult = Db::name($table)->alias('a')
                    ->join('norm_controlpoint b', 'a.control_id=b.id', 'left')
                    ->where($par)
                    ->field('a.id,b.code,b.name,a.status,a.division_id,a.ma_division_id,a.control_id,a.checked,b.remark')
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

    //单元管控：控制点执行情况、附件资料
    public function quality_upload($id, $draw, $table, $search, $start, $length, $limitFlag, $order, $columns, $columnString)
    {
        //查询
        //条件过滤后记录数 必要
        $recordsFiltered = 0;
        $recordsFilteredResult = array();
        $par = array();
        $par['a.type'] = $this->request->has('type') ? $this->request->param('type') : 1;
        $par['a.contr_relation_id'] = $this->request->param('cpr_id');
        //表的总记录数 必要
        $recordsTotal = Db::name($table)->where(['type' => $par['a.type'], 'contr_relation_id' => $par['a.contr_relation_id']])->count();
        if (strlen($search) > 0) {
            //有搜索条件的情况
            if ($limitFlag) {
                //*****多表查询join改这里******
                $recordsFilteredResult = Db::name($table)->alias('a')
                    ->join('attachment b', 'a.attachment_id=b.id', 'left')
                    ->join('admin c', 'b.user_id=c.id', 'left')
                    ->join('admin_group d', 'c.admin_group_id=d.id')
                    ->where($par)
                    ->field('a.id,a.data_name,c.nickname,d.name,b.create_time')
                    ->order($order)->limit(intval($start), intval($length))->select();
                $recordsFiltered = sizeof($recordsFilteredResult);
            }
        } else {
            //没有搜索条件的情况
            if ($limitFlag) {
                //*****多表查询join改这里******
                $recordsFilteredResult = Db::name($table)->alias('a')
                    ->join('attachment b', 'a.attachment_id=b.id', 'left')
                    ->join('admin c', 'b.user_id=c.id', 'left')
                    ->join('admin_group d', 'c.admin_group_id=d.id')
                    ->where($par)
                    ->field('a.id,a.data_name,c.nickname,d.name,b.create_time')
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

    // 分部质量管理 分部策划，分部管控 控制点列表、单位质量管理 单位策划，单位管控 控制点列表
    public function quality_subdivision_planning_list($id, $draw, $table, $search, $start, $length, $limitFlag, $order, $columns, $columnString)
    {
        //自定义查询条件
        $columnString = "c.code|c.name";
        //自定义表名，工程划分、工序、控制点关系表
        $table = "quality_division_controlpoint_relation";
        //查询
        //条件过滤后记录数 必要
        $recordsFiltered = 0;
        //获取筛选条件
        $selfid = input('selfid') ? input('selfid') : "";//左边的树节点的id
        $procedureid = input('procedureid') ? input('procedureid') : "";//工序号
        //是否勾选
        $checked = input('checked') ? input('checked') : "";//是否勾选

        if($checked == "")
        {
            $search_checked = [

            ];
        }else
        {
            $search_checked = [
                "checked"=>0
            ];
        }
        //表的总记录数 必要
        if ($selfid && $procedureid) {
            $search_data = [
                "division_id" => $selfid,
                "ma_division_id" => $procedureid,
//                "checked"=>0
            ];
        } else if ($selfid && !$procedureid) {
            $search_data = [
                "division_id" => $selfid
            ];
        } else if (!$selfid && !$procedureid) {
            $search_data = [
                "division_id" => $selfid,
                "ma_division_id" => $procedureid
            ];
        }

        //表的总记录数 必要
        $recordsTotal = 0;
        $recordsTotal = Db::name($table)->where("type = 0")->where($search_checked)->where($search_data)->count(0);
        $recordsFilteredResult = array();
        if (strlen($search) > 0) {
            //有搜索条件的情况
            if ($limitFlag) {
                //*****多表查询join改这里******
                $recordsFilteredResult = Db::name($table)
                    ->alias("s")
                    ->join('norm_controlpoint c', 's.control_id = c.id', 'left')
                    ->field("c.code,c.name,s.checked,s.status,s.id")
                    //判断在分部管控中是否显示不显示没有勾选的
                    ->where($search_checked)
                    //typedivision_id 类型:0单位,分部工程编号 1检验批
                    ->where("type = 0")
                    ->where($search_data)->where($columnString, 'like', '%' . $search . '%')
                    ->order($order)
//                    ->limit(intval($start), intval($length))
                    ->select();
                $recordsFiltered = sizeof($recordsFilteredResult);
            }
        } else {
            //没有搜索条件的情况
            if ($limitFlag) {
                $recordsFilteredResult = Db::name($table)
                    ->alias("s")
                    ->join('norm_controlpoint c', 's.control_id = c.id', 'left')
                    ->field("c.code,c.name,s.checked,s.status,s.id")
                    //判断在分部管控中是否显示不显示没有勾选的
                    ->where($search_checked)
                    //typedivision_id 类型:0单位,分部工程编号 1检验批
                    ->where("type = 0")
                    ->where($search_data)
                    ->order($order)
//                    ->limit(intval($start), intval($length))
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

    // 分部质量管理 分部策划，分部管控 控制点列表、单位质量管理 单位策划，单位管控 控制点列表执行点执行情况
    public function quality_subdivision_planning_file($id, $draw, $table, $search, $start, $length, $limitFlag, $order, $columns, $columnString)
    {
        //自定义表名，分部管控、单位管控中的控制点文件上传
        $table = "quality_upload";
        //查询
        //条件过滤后记录数 必要
        $recordsFiltered = 0;
        //获取筛选条件
        $list_id = input('list_id') ? input('list_id') : "";//分部策划列表id
        $type = input('type') ? input('type') : "";//type = 2表示单位工程，type = 3表示分部工程

        if(empty($list_id))
        {
            $search_data = [
                "contr_relation_id"=>-1
            ];
        }else
        {
            $search_data = ["contr_relation_id"=>$list_id];
        }
//        $type = input('type') ? input('type') : "";//1表示执行点执行情况，2表示图像资料

        //表的总记录数 必要
        $recordsTotal = 0;
        $recordsTotal = Db::name($table)->where($search_data)->where("type",$type)->count(0);
        $recordsFilteredResult = array();
        if (strlen($search) > 0) {
            //有搜索条件的情况
            if ($limitFlag) {
                //*****多表查询join改这里******
                $recordsFilteredResult = Db::name($table)
                    ->alias("s")
                    ->join('attachment m', 'm.id = s.attachment_id', 'left')
                    ->join('admin n', 'n.id = m.user_id', 'left')
                    ->join('admin_group g', 'g.id = n.admin_group_id', 'left')
                    ->field("m.name as filename,m.create_time,n.nickname as owner,g.name as company,s.id")
                    //搜索条件
                    ->where($search_data)
                    //type = 3表示分部工程
                    ->where("s.type",$type)
                    ->where($columnString, 'like', '%' . $search . '%')
                    ->order($order)->limit(intval($start), intval($length))
                    ->select();
                $recordsFiltered = sizeof($recordsFilteredResult);
            }
        } else {
            //没有搜索条件的情况
            if ($limitFlag) {
                $recordsFilteredResult = Db::name($table)
                    ->alias("s")
                    ->join('attachment m', 'm.id = s.attachment_id', 'left')
                    ->join('admin n', 'n.id = m.user_id', 'left')
                    ->join('admin_group g', 'g.id = n.admin_group_id', 'left')
                    ->field("m.name as filename,m.create_time,n.nickname as owner,g.name as company,s.id")
                    //搜索条件
                    ->where($search_data)
                    //type = 3表示分部工程
                    ->where("s.type",$type)
                    ->order($order)->limit(intval($start), intval($length))
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

    //在线填报表单列表
    public function quality_form_info($id, $draw, $table, $search, $start, $length, $limitFlag, $order, $columns, $columnString)
    {
        $param = input('param.');
        $cpr = Db::name('quality_division_controlpoint_relation')->where(['id' => $param['cpr_id']])->field('division_id,ma_division_id,control_id')->select();
        $whereStr = array();
        $whereStr['DivisionId'] = $cpr[0]['division_id'];
        $whereStr['ProcedureId'] = $cpr[0]['ma_division_id'];
        $whereStr['ControlPointId'] = $cpr[0]['control_id'];
        //查询
        //条件过滤后记录数 必要
        $recordsFiltered = 0;
        $recordsFilteredResult = array();
        //表的总记录数 必要
        $recordsTotal = Db::name($table)->where($whereStr)->count();
        if (strlen($search) > 0) {
            //有搜索条件的情况
            if ($limitFlag) {
                //*****多表查询join改这里******
                $recordsFilteredResult = Db::name($table)->alias('a')
                    ->join('admin u', 'a.user_id = u.id', 'left')
                    ->join('admin c', 'a.CurrentApproverId = c.id', 'left')
                    ->field('a.id,u.nickname as nickname,c.nickname as currentname,a.approvestatus,a.create_time,a.CurrentApproverId,a.CurrentStep,a.user_id')
                    ->where($whereStr)
                    ->order('create_time','desc')->limit(intval($start), intval($length))->select();
                $recordsFiltered = sizeof($recordsFilteredResult);
            }
        } else {
            //没有搜索条件的情况
            if ($limitFlag) {
                //*****多表查询join改这里******
                $recordsFilteredResult = Db::name($table)->alias('a')
                    ->join('admin u', 'a.user_id = u.id', 'left')
                    ->join('admin c', 'a.CurrentApproverId = c.id', 'left')
                    ->field('a.id,u.nickname as nickname,c.nickname as currentname,a.approvestatus,a.create_time,a.CurrentApproverId,a.CurrentStep,a.user_id')
                    ->where($whereStr)
                    ->order('create_time','desc')->limit(intval($start), intval($length))->select();
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
    public function norm_materialtrackingdivision ($id, $draw, $table, $search, $start, $length, $limitFlag, $order, $columns, $columnString)
    {
        //查询
        //条件过滤后记录数 必要
        $recordsFiltered = 0;
        $recordsFilteredResult = array();
        $param = input('param.');
        $en_type=$param['en_type'];
        $unit_id=$param['unit_id'];

        //norm_materialtrackingdivision的id数组
        $nm_arr=Db::name('norm_materialtrackingdivision')
            ->where(['pid'=>$en_type,'type'=>3,'cat'=>5])
            ->column('id');

        //如果传的有工序id
        if($this->request->has('nm_id'))
        {
            $wherestr['procedureid']=$param['nm_id'];
            $id_arr=Db::name('norm_controlpoint')
                ->where($wherestr)
                ->column('id');
        }
        else
         {
             //controlpoint里的id数组

             $id_arr=Db::name('norm_controlpoint')
                 ->where('procedureid','in',$nm_arr)
                 ->column('id');
         }

        $search=Db::name('quality_division_controlpoint_relation')
            ->where(['type'=>1,'division_id'=>$unit_id])
            ->select();
        //如果之前触发了insertalldata函数
        if (count($search) > 0) {
            //是否有传入工序
            if($this->request->has('nm_id'))
            {
                $wherenm['r.ma_division_id']=$param['nm_id'];
            }
            else{
                $wherenm='';
            }
            //是否有传入check判断（区分单元测评/单元管控）
            if($this->request->has('checked_gk'))
            {
                $wherech['r.checked']=$param['checked_gk'];//检查是否是管控传来模块
            }
            else{
                $wherech='';
            }

            //*****多表查询join改这里******
            $recordsFilteredResult = Db::name('norm_controlpoint')->alias('c')
                    ->join('quality_division_controlpoint_relation r', 'r.control_id = c.id', 'left')
                        ->where(['r.type'=>1,'r.division_id'=>$unit_id])
                        ->where('r.control_id','in',$id_arr)//控制点必须对应在当前的工程类型下，防止切换单元类型
                        ->where($wherenm)
                        ->where($wherech)
                        ->order('code')
                        ->limit(intval($start), intval($length))
                    ->select();
                $recordsFiltered = sizeof($recordsFilteredResult);
        } else {
            if ($limitFlag) {
                //*****多表查询join改这里******
                $recordsFilteredResult =  Db::name('norm_controlpoint')->alias('c')
                    ->join('quality_division_controlpoint_relation r', 'r.control_id = c.id', 'left')
                    ->where(['r.type'=>1,'r.division_id'=>$unit_id])
                    ->where('r.control_id','in',$id_arr)//控制点必须对应在当前的工程类型下，防止切换单元类型
                    ->order('code')
                    ->limit(intval($start), intval($length))
                    ->select();
                $recordsFiltered = sizeof($recordsFilteredResult);
            }
        }
        //表的总记录数 必要
        $recordsTotal =count($recordsFiltered);
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
