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
                    ->field('picture_number,picture_name,picture_papaer_num,a1_picture,design_name,check_name,examination_name,completion_date,section,paper_category,id')
                    ->where($columnString, 'like', '%' . $search . '%')
                    ->order($order)->limit(intval($start), intval($length))->select();
                $recordsFiltered = sizeof($recordsFilteredResult);
            }
        } else {
            //没有搜索条件的情况
            if ($limitFlag) {
                //*****多表查询join改这里******
                $recordsFilteredResult = Db::name($table)
                    ->field('picture_number,picture_name,picture_papaer_num,a1_picture,design_name,check_name,examination_name,completion_date,section,paper_category,id')
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
                    ->field('serial_number,site,coding,hinge,pile_number,start_date,completion_date,id')
                    ->where('division_id', $id)
                    ->where($columnString, 'like', '%' . $search . '%')
                    ->order($order)->limit(intval($start), intval($length))->select();
                $recordsFiltered = sizeof($recordsFilteredResult);
            }
        } else {
            //没有搜索条件的情况
            if ($limitFlag) {
                //*****多表查询join改这里******
                $recordsFilteredResult = Db::name($table)
                    ->field('serial_number,site,coding,hinge,pile_number,start_date,completion_date,id')
                    ->where('division_id', $id)
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
//            $res['filename'] = $info->getFilename();//文件名
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
        $fileName = $attachment['filename'];
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

    public function quality_scene_picture($id, $draw, $table, $search, $start, $length, $limitFlag, $order, $columns, $columnString)
    {
        //查询
        //条件过滤后记录数 必要
        $recordsFiltered = 0;
        //表的总记录数 必要
        $year = input('year') ? input('year') : "";//年
        $month = input('month') ? input('month') : "";//月
        $day = input('day') ? input('day') : "";//日

        $admin_group_id = input('admin_group_id') ? input('admin_group_id') : "";
        if ($admin_group_id) {
            $group_data = [
                "admin_group_id" => $admin_group_id
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
                "year" => $year,
                "month" => $month,
                "day" => $day
            ];
        } else if ($year && !$month && !$day)//如果年都存在
        {
            $search_data = [
                "year" => $year
            ];
        } else if ($year && $month && !$day)//如果年月都存在
        {
            $search_data = [
                "year" => $year,
                "month" => $month
            ];
        }


        //表的总记录数 必要
        $recordsTotal = 0;
        $recordsTotal = Db::name($table)->where($search_data)->where($group_data)->where("admin_group_id > 0")->count(0);
        $recordsFilteredResult = array();
        if (strlen($search) > 0) {
            //有搜索条件的情况
            if ($limitFlag) {
                //*****多表查询join改这里******
                $recordsFilteredResult = Db::name($table)->field("filename,date,owner,company,position,id")->where($search_data)->where("admin_group_id > 0")->where($group_data)->where($columnString, 'like', '%' . $search . '%')->order($order)->limit(intval($start), intval($length))->select();
                $recordsFiltered = sizeof($recordsFilteredResult);
            }
        } else {
            //没有搜索条件的情况
            if ($limitFlag) {
                $recordsFilteredResult = Db::name($table)->field("filename,date,owner,company,position,id")->where($search_data)->where("admin_group_id > 0")->where($group_data)->order($order)->limit(intval($start), intval($length))->select();
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

    public function quality_supervision_log($id, $draw, $table, $search, $start, $length, $limitFlag, $order, $columns, $columnString)
    {
        //查询
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
                "year" => $year,
                "month" => $month,
                "day" => $day
            ];
        } else if ($year && !$month && !$day)//如果年都存在
        {
            $search_data = [
                "year" => $year
            ];
        } else if ($year && $month && !$day)//如果年月都存在
        {
            $search_data = [
                "year" => $year,
                "month" => $month
            ];
        }


        //表的总记录数 必要
        $recordsTotal = 0;
        $recordsTotal = Db::name($table)->where($search_data)->where("admin_group_id > 0")->count(0);
        $recordsFilteredResult = array();
        if (strlen($search) > 0) {
            //有搜索条件的情况
            if ($limitFlag) {
                //*****多表查询join改这里******
                $recordsFilteredResult = Db::name($table)->field("filename,date,owner,company,position,id")->where($search_data)->where("admin_group_id > 0")->where($columnString, 'like', '%' . $search . '%')->order($order)->limit(intval($start), intval($length))->select();
                $recordsFiltered = sizeof($recordsFilteredResult);
            }
        } else {
            //没有搜索条件的情况
            if ($limitFlag) {
                $recordsFilteredResult = Db::name($table)->field("filename,date,owner,company,position,id")->where($search_data)->where("admin_group_id > 0")->order($order)->limit(intval($start), intval($length))->select();
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

    public function quality_patrol_record($id, $draw, $table, $search, $start, $length, $limitFlag, $order, $columns, $columnString)
    {
        //查询
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
                "year" => $year,
                "month" => $month,
                "day" => $day
            ];
        } else if ($year && !$month && !$day)//如果年都存在
        {
            $search_data = [
                "year" => $year
            ];
        } else if ($year && $month && !$day)//如果年月都存在
        {
            $search_data = [
                "year" => $year,
                "month" => $month
            ];
        }


        //表的总记录数 必要
        $recordsTotal = 0;
        $recordsTotal = Db::name($table)->where($search_data)->where("admin_group_id > 0")->count(0);
        $recordsFilteredResult = array();
        if (strlen($search) > 0) {
            //有搜索条件的情况
            if ($limitFlag) {
                //*****多表查询join改这里******
                $recordsFilteredResult = Db::name($table)->field("filename,date,owner,company,position,id")->where($search_data)->where("admin_group_id > 0")->where($columnString, 'like', '%' . $search . '%')->order($order)->limit(intval($start), intval($length))->select();
                $recordsFiltered = sizeof($recordsFilteredResult);
            }
        } else {
            //没有搜索条件的情况
            if ($limitFlag) {
                $recordsFilteredResult = Db::name($table)->field("filename,date,owner,company,position,id")->where($search_data)->where("admin_group_id > 0")->order($order)->limit(intval($start), intval($length))->select();
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

    public function quality_side_reporting($id, $draw, $table, $search, $start, $length, $limitFlag, $order, $columns, $columnString)
    {
        //查询
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
                "year" => $year,
                "month" => $month,
                "day" => $day
            ];
        } else if ($year && !$month && !$day)//如果年都存在
        {
            $search_data = [
                "year" => $year
            ];
        } else if ($year && $month && !$day)//如果年月都存在
        {
            $search_data = [
                "year" => $year,
                "month" => $month
            ];
        }


        //表的总记录数 必要
        $recordsTotal = 0;
        $recordsTotal = Db::name($table)->where($search_data)->where("admin_group_id > 0")->count(0);
        $recordsFilteredResult = array();
        if (strlen($search) > 0) {
            //有搜索条件的情况
            if ($limitFlag) {
                //*****多表查询join改这里******
                $recordsFilteredResult = Db::name($table)->field("filename,date,owner,company,position,id")->where($search_data)->where("admin_group_id > 0")->where($columnString, 'like', '%' . $search . '%')->order($order)->limit(intval($start), intval($length))->select();
                $recordsFiltered = sizeof($recordsFilteredResult);
            }
        } else {
            //没有搜索条件的情况
            if ($limitFlag) {
                $recordsFilteredResult = Db::name($table)->field("filename,date,owner,company,position,id")->where($search_data)->where("admin_group_id > 0")->order($order)->limit(intval($start), intval($length))->select();
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

    public function quality_sampling($id, $draw, $table, $search, $start, $length, $limitFlag, $order, $columns, $columnString)
    {
        //查询
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
                "year" => $year,
                "month" => $month,
                "day" => $day
            ];
        } else if ($year && !$month && !$day)//如果年都存在
        {
            $search_data = [
                "year" => $year
            ];
        } else if ($year && $month && !$day)//如果年月都存在
        {
            $search_data = [
                "year" => $year,
                "month" => $month
            ];
        }


        //表的总记录数 必要
        $recordsTotal = 0;
        $recordsTotal = Db::name($table)->where($search_data)->where("admin_group_id > 0")->count(0);
        $recordsFilteredResult = array();
        if (strlen($search) > 0) {
            //有搜索条件的情况
            if ($limitFlag) {
                //*****多表查询join改这里******
                $recordsFilteredResult = Db::name($table)->field("filename,date,owner,company,position,id")->where($search_data)->where("admin_group_id > 0")->where($columnString, 'like', '%' . $search . '%')->order($order)->limit(intval($start), intval($length))->select();
                $recordsFiltered = sizeof($recordsFilteredResult);
            }
        } else {
            //没有搜索条件的情况
            if ($limitFlag) {
                $recordsFilteredResult = Db::name($table)->field("filename,date,owner,company,position,id")->where($search_data)->where("admin_group_id > 0")->order($order)->limit(intval($start), intval($length))->select();
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

    public function quality_supervision_instruction($id, $draw, $table, $search, $start, $length, $limitFlag, $order, $columns, $columnString)
    {
        //查询
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
                "year" => $year,
                "month" => $month,
                "day" => $day
            ];
        } else if ($year && !$month && !$day)//如果年都存在
        {
            $search_data = [
                "year" => $year
            ];
        } else if ($year && $month && !$day)//如果年月都存在
        {
            $search_data = [
                "year" => $year,
                "month" => $month
            ];
        }


        //表的总记录数 必要
        $recordsTotal = 0;
        $recordsTotal = Db::name($table)->where($search_data)->where("admin_group_id > 0")->count(0);
        $recordsFilteredResult = array();
        if (strlen($search) > 0) {
            //有搜索条件的情况
            if ($limitFlag) {
                //*****多表查询join改这里******
                $recordsFilteredResult = Db::name($table)->field("filename,date,owner,company,position,id")->where($search_data)->where("admin_group_id > 0")->where($columnString, 'like', '%' . $search . '%')->order($order)->limit(intval($start), intval($length))->select();
                $recordsFiltered = sizeof($recordsFilteredResult);
            }
        } else {
            //没有搜索条件的情况
            if ($limitFlag) {
                $recordsFilteredResult = Db::name($table)->field("filename,date,owner,company,position,id")->where($search_data)->where("admin_group_id > 0")->order($order)->limit(intval($start), intval($length))->select();
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


    // ht 单位质量管理 单位策划，单位管控 控制点列表
    public function unit_quality_control($id, $draw, $table, $search, $start, $length, $limitFlag, $order, $columns, $columnString)
    {
        // 前台 传递 左侧的 节点 add_id 和 当前 点击的 工序 编号 workId
        $param = input('param.');
        $division_id = isset($param['add_id']) ? $param['add_id'] : -1; // 这里存放 工程划分 单位工程编号
        $id = isset($param['workId']) ? $param['workId'] : -1; // 工序编号
        $type = isset($param['type']) ? $param['type'] : 0; // 不传递 说明 是 单位策划 传递 说明是 单位管控
        if ($division_id == -1 || $id == -1) {
            return json(['draw' => intval($draw), 'recordsTotal' => intval(0), 'recordsFiltered' => 0, 'data' => array()]);
        }
        $table = 'quality_division_controlpoint_relation'; // 控制点表
        //查询
        //条件过滤后记录数 必要
        $recordsFiltered = 0;
        $recordsFilteredResult = array();
        /**
         * 注意 ：这里的控制点是 ，存在于 quality_division_controlpoint_relation 单位质量管理 对应关系表里的 关联对应数据
         * 所以即使和 其他 工序下 的控制点重复也是正常的
         * type 类型:1 检验批 0 工程划分
         */
        if ($id == 0) { // 等于0 说明工序 是 作业 那就获取全部的 控制点
            //表的总记录数 必要
            $recordsTotal = Db::name('quality_division_controlpoint_relation')->where(['division_id' => $division_id, 'type' => 0,])->count();
        } else {
            //表的总记录数 必要
            $recordsTotal = Db::name('quality_division_controlpoint_relation')->where(['division_id' => $division_id, 'type' => 0, 'ma_division_id' => $id])->count();
        }
        $field_val = 'c.code,c.name,r.status,r.id';
        if ($type == 0) {
            $field_val = 'c.code,c.name,r.id';
        }
        if (strlen($search) > 0) {
            //有搜索条件的情况
            if ($limitFlag) {
                //*****多表查询join改这里******
                if ($id == 0) {
                    $recordsFilteredResult = Db::name($table)->alias('r')
                        ->field($field_val)
                        ->join('controlpoint c', 'r.control_id = c.id', 'left')
                        ->where(['r.division_id' => $division_id, 'type' => 0])
                        ->where($columnString, 'like', '%' . $search . '%')
                        ->order($order)->limit(intval($start), intval($length))->select();
                } else {
                    $recordsFilteredResult = Db::name($table)->alias('r')
                        ->field($field_val)
                        ->join('controlpoint c', 'r.control_id = c.id', 'left')
                        ->where(['r.division_id' => $division_id, 'type' => 0, 'ma_division_id' => $id])
                        ->where($columnString, 'like', '%' . $search . '%')
                        ->order($order)->limit(intval($start), intval($length))->select();
                }
                $recordsFiltered = sizeof($recordsFilteredResult);
            }
        } else {
            //没有搜索条件的情况
            if ($limitFlag) {
                //*****多表查询join改这里******
                if ($id == 0) {
                    $recordsFilteredResult = Db::name($table)->alias('r')
                        ->field($field_val)
                        ->join('controlpoint c', 'r.control_id = c.id', 'left')
                        ->where(['r.division_id' => $division_id, 'type' => 0])
                        ->order($order)->limit(intval($start), intval($length))->select();
                } else {
                    $recordsFilteredResult = Db::name($table)->alias('r')
                        ->field($field_val)
                        ->join('controlpoint c', 'r.control_id = c.id', 'left')
                        ->where(['r.division_id' => $division_id, 'type' => 0, 'ma_division_id' => $id])
                        ->order($order)->limit(intval($start), intval($length))->select();
                }
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

    //单元管控：控制点列表
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
                    ->join('controlpoint b', 'a.control_id=b.id', 'left')
                    ->where($par)
                    ->field('a.id,b.code,b.name,a.status,a.division_id,a.ma_division_id,a.control_id,b.remark')
                    ->order($order)->limit(intval($start), intval($length))->select();
                $recordsFiltered = sizeof($recordsFilteredResult);
            }
        } else {
            //没有搜索条件的情况
            if ($limitFlag) {
                //*****多表查询join改这里******
                $recordsFilteredResult = Db::name($table)->alias('a')
                    ->join('controlpoint b', 'a.control_id=b.id', 'left')
                    ->where($par)
                    ->field('a.id,b.code,b.name,a.status,a.division_id,a.ma_division_id,a.control_id,b.remark')
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

    // 分部质量管理 分部策划，分部管控 控制点列表
    public function quality_subdivision_planning_list($id, $draw, $table, $search, $start, $length, $limitFlag, $order, $columns, $columnString)
    {
        //查询
        //条件过滤后记录数 必要
        $recordsFiltered = 0;
        //获取筛选条件
        $selfid = input('selfid') ? input('selfid') : "";//左边的树节点的id
        $procedureid = input('procedureid') ? input('procedureid') : "";//工序号
        //表的总记录数 必要
        if ($selfid && $procedureid) {
            $search_data = [
                "selfid" => $selfid,
                "procedureid" => $procedureid
            ];
        } else if ($selfid && !$procedureid) {
            $search_data = [
                "selfid" => $selfid
            ];
        } else if (!$selfid && !$procedureid) {
            $search_data = [
                "selfid" => $selfid,
                "procedureid" => $procedureid
            ];
        }

        //表的总记录数 必要
        $recordsTotal = 0;
        $recordsTotal = Db::name($table)->where($search_data)->count(0);
        $recordsFilteredResult = array();
        if (strlen($search) > 0) {
            //有搜索条件的情况
            if ($limitFlag) {
                //*****多表查询join改这里******
                $recordsFilteredResult = Db::name($table)->where($search_data)->where($columnString, 'like', '%' . $search . '%')->order($order)->limit(intval($start), intval($length))->select();
                $recordsFiltered = sizeof($recordsFilteredResult);
            }
        } else {
            //没有搜索条件的情况
            if ($limitFlag) {
                $recordsFilteredResult = Db::name($table)->where($search_data)->order($order)->limit(intval($start), intval($length))->select();
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

    // 分部质量管理 控制点执行情况，图像资料
    public function quality_subdivision_planning_file($id, $draw, $table, $search, $start, $length, $limitFlag, $order, $columns, $columnString)
    {
        //查询
        //条件过滤后记录数 必要
        $recordsFiltered = 0;
        //获取筛选条件
        $list_id = input('list_id') ? input('list_id') : "";//分部策划列表id
        $type = input('type') ? input('type') : "";//1表示执行点执行情况，2表示图像资料

        //表的总记录数 必要
        $recordsTotal = 0;
        $recordsTotal = Db::name($table)->where(["list_id" => $list_id, "type" => $type])->where("admin_group_id > 0")->count(0);
        $recordsFilteredResult = array();
        if (strlen($search) > 0) {
            //有搜索条件的情况
            if ($limitFlag) {
                //*****多表查询join改这里******
                $recordsFilteredResult = Db::name($table)->where(["list_id" => $list_id, "type" => $type])->where($columnString, 'like', '%' . $search . '%')->order($order)->limit(intval($start), intval($length))->select();
                $recordsFiltered = sizeof($recordsFilteredResult);
            }
        } else {
            //没有搜索条件的情况
            if ($limitFlag) {
                $recordsFilteredResult = Db::name($table)->where(["list_id" => $list_id, "type" => $type])->order($order)->limit(intval($start), intval($length))->select();
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


    // ht 单位质量管理 单位策划 新增 控制点 获取 单位工程下的每一个工序所对应的控制点
    public function unit_quality_add_control($id, $draw, $table, $search, $start, $length, $limitFlag, $order, $columns, $columnString)
    {
        $table = 'controlpoint'; // 控制点表
        //查询
        //条件过滤后记录数 必要
        $recordsFiltered = 0;
        $recordsFilteredResult = array();
        //表的总记录数 必要
        $recordsTotal = Db::name($table)->where('procedureid', $id)->count();
        if (strlen($search) > 0) {
            //有搜索条件的情况
            if ($limitFlag) {
                //*****多表查询join改这里******
                $recordsFilteredResult = Db::name($table)
                    ->field('code,name,id')
                    ->where('procedureid', $id)
                    ->where($columnString, 'like', '%' . $search . '%')
                    ->order($order)->limit(intval($start), intval($length))->select();
                $recordsFiltered = sizeof($recordsFilteredResult);
            }
        } else {
            //没有搜索条件的情况
            if ($limitFlag) {
                //*****多表查询join改这里******
                $recordsFilteredResult = Db::name($table)
                    ->field('code,name,id')
                    ->where('procedureid', $id)
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

    // ht 单位质量管理 单位管控 获取 控制点执行情况，图像资料 列表
    public function unit_quality_manage_file($id, $draw, $table, $search, $start, $length, $limitFlag, $order, $columns, $columnString)
    {
        $param = input('param.');
        $id = isset($param['controlId']) ? $param['controlId'] : -1; // 控制点编号
        $type = isset($param['type']) ? $param['type'] : 1; // 1执行情况 2图像资料 (不传递是执行情况 传递 是 图像资料)
        if ($id == -1) {
            return json(['draw' => intval($draw), 'recordsTotal' => intval(0), 'recordsFiltered' => 0, 'data' => array()]);
        }
        $table = 'quality_upload'; // 文件表
        //查询
        //条件过滤后记录数 必要
        $recordsFiltered = 0;
        $recordsFilteredResult = array();
        //表的总记录数 必要
        $recordsTotal = Db::name('quality_upload')->where(['contr_relation_id' => $id, 'type' => $type])->count();
        if (strlen($search) > 0) {
            //有搜索条件的情况
            if ($limitFlag) {
                //*****多表查询join改这里******
                $recordsFilteredResult = Db::name($table)->alias('u')
                    ->field('t.name as filename,a.nickname,c.role_name,t.create_time,u.id')
                    ->join('attachment t', 'u.attachment_id = t.id', 'left')
                    ->join('admin a', 't.user_id = a.id', 'left')
                    ->join('admin_cate c', 'a.admin_cate_id = c.id', 'left')
                    ->where(['u.contr_relation_id' => $id, 'u.type' => $type])
                    ->where($columnString, 'like', '%' . $search . '%')
                    ->order($order)->limit(intval($start), intval($length))->select();
                $recordsFiltered = sizeof($recordsFilteredResult);
            }
        } else {
            //没有搜索条件的情况
            if ($limitFlag) {
                //*****多表查询join改这里******
                $recordsFilteredResult = Db::name($table)->alias('u')
                    ->field('t.name as filename,a.nickname,c.role_name,t.create_time,u.id')
                    ->join('attachment t', 'u.attachment_id = t.id', 'left')
                    ->join('admin a', 't.user_id = a.id', 'left')
                    ->join('admin_cate c', 'a.admin_cate_id = c.id', 'left')
                    ->where(['u.contr_relation_id' => $id, 'u.type' => $type])
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
                    ->field('a.id,u.nickname as nickname,c.nickname as currentname,a.approvestatus,a.create_time,a.CurrentApproverId,a.CurrentStep')
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
                    ->field('a.id,u.nickname as nickname,c.nickname as currentname,a.approvestatus,a.create_time,a.CurrentApproverId,a.CurrentStep')
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

}