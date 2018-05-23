<?php

namespace app\admin\controller;

use app\admin\model\AdminGroup;
use app\admin\model\DatatablesExample;
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
        if(isset($order_column)){
            $i = intval($order_column);
            $order = $columns[$i]['name'].' '.$order_dir;
        }
        //搜索
        //获取前台传过来的过滤条件
        $search = $this->request->param('search/a')['value'];
        //分页
        $start = $this->request->has('start') ? $this->request->param('start', 0, 'intval') : 0;
        $length = $this->request->has('length') ? $this->request->param('length', 0, 'intval') : 0;
        $limitFlag = isset($start) && $length != -1 ;
        //新建的方法名与数据库表名保持一致
        return $this->$table($id,$draw,$table,$search,$start,$length,$limitFlag,$order,$columns,$columnString);
    }
    /*
     * 角色管理
     */
    public function admin_cate($id,$draw,$table,$search,$start,$length,$limitFlag,$order,$columns,$columnString)
    {

        //查询
        //条件过滤后记录数 必要
        $recordsFiltered = 0;
        //表的总记录数 必要
        $recordsTotal = 0;
        $recordsTotal = Db::name($table)->where('pid',$id)->count(0);
        $recordsFilteredResult = array();
        if(strlen($search)>0){
            //有搜索条件的情况
            if($limitFlag){
                //*****多表查询join改这里******
                $recordsFilteredResult = Db::name($table)->where('pid',$id)->where($columnString, 'like', '%' . $search . '%')->order($order)->limit(intval($start),intval($length))->select();
                $recordsFiltered = sizeof($recordsFilteredResult);
            }
        }else{
            //没有搜索条件的情况
            if($limitFlag){
                $recordsFilteredResult = Db::name($table)->where('pid',$id)->order($order)->limit(intval($start),intval($length))->select();
                //*****多表查询join改这里******
                //$recordsFilteredResult = Db::name('datatables_example')->alias('d')->join('datatables_example_join e','d.position = e.id')->field('d.id,d.name,e.name as position,d.office')->select();
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

    public function admin($id,$draw,$table,$search,$start,$length,$limitFlag,$order,$columns,$columnString)
    {
        //传过来的类别 table_type 发文 选择收件人列表  不传递表示组织管理
        $type = input('param.table_type');
        //查询
        //条件过滤后记录数 必要
        $recordsFiltered = 0;
        $recordsFilteredResult = array();
        $node = new AdminGroup();
        $idArr = $node->cateTree($id);
        $idArr[] = $id;
        //表的总记录数 必要
        $recordsTotal = Db::name($table)->whereIn('admin_group_id',$idArr)->count(0);
        if(strlen($search)>0){
            //有搜索条件的情况
            if($limitFlag){
                if(empty($type)){
                    $exArr = explode('|',$columnString);
                    $newColumnString = '';
                    foreach($exArr as $ex){
                        switch ($ex){
                            case 'g_order':
                                $newColumnString = 'a.order' . '|' . $newColumnString;
                                break;
                            case 'g_name':
                                $newColumnString = 'a.name' . '|' . $newColumnString;
                                break;
                            case 'name':
                                $newColumnString = 'g.name' . '|' . $newColumnString;
                                break;
                            default :
                                $newColumnString = 'a.' . $ex . '|' . $newColumnString;
                        }
                    }
                    $newColumnString = substr($newColumnString,0,strlen($newColumnString)-1);
                    //*****多表查询join改这里******
                    $recordsFilteredResult = Db::name($table)->alias('a')
                        ->join('admin_group g','a.admin_group_id = g.id','left')
                        ->field('a.id,a.order as g_order,a.name,a.nickname,g.name as g_name,a.mobile,a.position,a.status')
                        ->whereIn('a.admin_group_id',$idArr)
                        ->where($newColumnString, 'like', '%' . $search . '%')
                        ->order($order)->limit(intval($start),intval($length))->select();
                }else{
                    $recordsFilteredResult = Db::name($table)->alias('a')
                        ->join('admin_group g','a.admin_group_id = g.id','left')
                        ->field('a.id,g.name,a.nickname,a.mobile,a.position')
                        ->where($columnString, 'like', '%' . $search . '%')
                        ->where(['a.status'=> '1','a.admin_group_id'=>['in',$idArr]])
                        ->order($order)->limit(intval($start),intval($length))->select();
                }


                $recordsFiltered = sizeof($recordsFilteredResult);
            }
        }else{
            //没有搜索条件的情况
            if($limitFlag){
                //*****多表查询join改这里******
                if(empty($type)){
                    $recordsFilteredResult = Db::name('admin')->alias('a')
                        ->join('admin_group g','a.admin_group_id = g.id','left')
                        ->field('a.id,a.order as g_order,a.name,a.nickname,g.name as g_name,a.mobile,a.position,a.status')
                        ->whereIn('a.admin_group_id',$idArr)->order($order)->limit(intval($start),intval($length))->select();
                }else{
                    $recordsFilteredResult = Db::name('admin')->alias('a')
                        ->join('admin_group g','a.admin_group_id = g.id','left')
                        ->field('a.id,g.name,a.nickname,a.mobile,a.position')
                        ->where(['a.status'=> '1','a.admin_group_id'=>['in',$idArr]])->order($order)->limit(intval($start),intval($length))->select();
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

    /**
     * 清除全部缓存
     * @return [type] [description]
     */
    public function clear()
    {
        if(false == Cache::clear()) {
        	return $this->error('清除缓存失败');
        } else {
        	return $this->success('清除缓存成功');
        }
    }


    /**
     * 上传方法
     * @return [type] [description]
     */
    public function upload($module='admin',$use='admin_thumb')
    {
        if($this->request->file('file')){
            $file = $this->request->file('file');
        }else{
            $res['code']=1;
            $res['msg']='没有上传文件';
            return json($res);
        }
        $module = $this->request->has('module') ? $this->request->param('module') : $module;//模块
        $web_config = Db::name('admin_webconfig')->where('web','web')->find();
        $info = $file->validate(['size'=>$web_config['file_size']*1024,'ext'=>$web_config['file_type']])->rule('date')->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . $module . DS . $use);
        if($info) {
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
            if($data['module'] == 'admin') {
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
            return $this->error('上传失败：'.$file->getError());
        }
    }

    /**
     * 登录
     * @return [type] [description]
     */
    public function login()
    {
        if(Session::has('admin') == false) { 
            if($this->request->isPost()) {
                //是登录操作
                $post = $this->request->post();
                //验证  唯一规则： 表名，字段名，排除主键值，主键名
//                $validate = new \think\Validate([
//                    ['name', 'require|alphaDash', '用户名不能为空|用户名格式只能是字母、数字、——或_'],
//                    ['password', 'require', '密码不能为空'],
//                    ['captcha','require|captcha','验证码不能为空|验证码不正确'],
//                ]);
                $validate = new \think\Validate([
                    ['name', 'require|alphaDash', '用户名不能为空|用户名格式只能是字母、数字、——或_'],
                    ['password', 'require', '密码不能为空']
                ]);
                //验证部分数据合法性
                if (!$validate->check($post)) {
                    $this->error('提交失败：' . $validate->getError());
                }
                $name = Db::name('admin')->where('name',$post['name'])->find();
                if(empty($name)) {
                    //不存在该用户名
                    return $this->error('用户名不存在');
                } else {
                    //验证密码
                    $temp = $post['password'];
                    $post['password'] = password($post['password']);
                    if($name['password'] != $post['password']) {
                        return $this->error('密码错误');
                    } else {
                        //是否记住账号
                        if(!empty($post['remember']) and $post['remember'] == 1) {
                            //检查当前有没有记住的账号
                            if(Cookie::has('usermember')) {
                                Cookie::delete('usermember');
                                Cookie::delete('userpass');
                            }
                            //保存新的
                            Cookie::forever('usermember',$post['name']);
                            Cookie::forever('userpass',$temp);

                        } else {
                            //未选择记住账号，或属于取消操作
                            if(Cookie::has('usermember')) {
                                Cookie::delete('usermember');
                                Cookie::delete('userpass');
                            }
                        }
                        Session::set("admin",$name['id']); //保存新的
                        Session::set("current_name",$name['name']); //保存新的
                        Session::set("current_id",$name['id']); //保存新的
                        Session::set("current_nickname",$name['nickname']); //保存新的
                        Session::set("admin_cate_id",$name['admin_cate_id']); //保存新的
                        //记录登录时间和ip
                        Db::name('admin')->where('id',$name['id'])->update(['login_ip' =>  $this->request->ip(),'login_time' => time()]);
                        return $this->success('登录成功,正在跳转...','admin/index/index');
                    }
                }
            } else {
                if(Cookie::has('usermember')) {
                    $this->assign('usermember',Cookie::get('usermember'));
                    $this->assign('userpass',Cookie::get('userpass'));
                }
                return $this->fetch();
            }
        } else {
            $this->redirect('admin/index/index');
        }   
    }

    /**
     * 管理员退出，清除名字为admin的session
     * @return [type] [description]
     */
    public function logout()
    {
        Session::delete('admin');
        Session::delete('admin_cate_id');
        if(Session::has('admin') or Session::has('admin_cate_id')) {
            return $this->error('退出失败');
        } else {
            return $this->success('正在退出...','admin/common/login');
        }
    }

    /**
     * 消息提醒列表
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
    public function admin_message_reminding($id,$draw,$table,$search,$start,$length,$limitFlag,$order,$columns,$columnString)
    {

        //查询
        //条件过滤后记录数 必要
        $recordsFiltered = 0;
        //表的总记录数 必要
        $recordsTotal = 0;
        $recordsTotal = Db::name($table)->count(0);
        $recordsFilteredResult = array();
        if(strlen($search)>0){
            //有搜索条件的情况
            if($limitFlag){
                //*****多表查询join改这里******
                $recordsFilteredResult = Db::name($table)->alias("m")
                    ->join('admin a','m.sender = a.id','left')
                    ->field("m.task_name,m.create_time,a.nickname,m.task_category,m.status,m.id")
                    ->where($columnString, 'like', '%' . $search . '%')
                    ->order($order)->limit(intval($start),intval($length))
                    ->select();
                $recordsFiltered = sizeof($recordsFilteredResult);
            }
        }else{
            //没有搜索条件的情况
            if($limitFlag){
                $recordsFilteredResult = Db::name($table)->alias("m")
                    ->join('admin a','m.sender = a.id','left')
                    ->field("m.task_name,m.create_time,a.nickname,m.task_category,m.status,m.id")
                    ->order($order)->limit(intval($start),intval($length))
                    ->select();
                //*****多表查询join改这里******
                //$recordsFilteredResult = Db::name('datatables_example')->alias('d')->join('datatables_example_join e','d.position = e.id')->field('d.id,d.name,e.name as position,d.office')->select();
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
