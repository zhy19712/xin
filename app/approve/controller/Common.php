<?php
/**
 * Created by PhpStorm.
 * User: zhifang
 * Date: 2018/4/20
 * Time: 11:39
 */
namespace app\approve\controller;

use app\archive\model\DocumentTypeModel;
use app\archive\model\AtlasCateTypeModel;
use app\archive\model\AtlasCateModel;
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
   public function datatablesPre()
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
     * 获取审批历史
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
  public  function approve($draw, $table, $search, $start, $length, $limitFlag, $order, $columns, $columnString)
    {
        //查询前置条件
        $par=array();
        $par['data_type']=$this->request->param('dataType');
        $par['data_id']=$this->request->param('dataId');
        //查询
        //条件过滤后记录数 必要
        $recordsFiltered = 0;
        //表的总记录数 必要
        $recordsTotal = 0;
        $recordsTotal = Db::name($table)->where($par) ->count(0);
        $recordsFilteredResult = array();
        if (strlen($search) > 0) {
            //有搜索条件的情况
            if ($limitFlag) {
                //*****多表查询join改这里******
                $recordsFilteredResult = Db::name($table)->alias('a')
                    ->join('admin u','a.user_id=u.id','left')
                    ->where($par)
                    ->field('u.nickname,a.create_time,a.result,a.mark')
                    ->order('a.update_time Desc')->limit(intval($start), intval($length))->select();
                $recordsFiltered = sizeof($recordsFilteredResult);
            }
        } else {
            //没有搜索条件的情况
            if ($limitFlag) {
                $recordsFilteredResult = Db::name($table)->alias('a')
                    ->join('admin u','a.user_id=u.id','left')
                    ->where($par)
                    ->field('u.nickname,a.create_time,a.result,a.mark')
                    ->order('a.update_time Desc')->limit(intval($start), intval($length))->select();
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
        //加上待处理人的信息，有下一步审批人的时候才加入
        $form=Db::name('quality_form_info')
             ->where(['id'=>$par['data_id']])
             ->where('ApproveStatus','in','-1,1')
             ->find();
        if(($form['CurrentApproverId']!='NULL')&&($form['CurrentApproverId']!='')&&count($form)>0)
        {
            $approver=Db::name('admin')
                      ->where(['id'=>$form['CurrentApproverId']])
                      ->value('nickname');
            //如果审批人存在
            //审批状态
            if($approver!=''&&$form['ApproveStatus']==1)
            {
                $approver=array($approver,'','待审批','');
                array_unshift($infos,$approver);
            }
            //退回状态
            if($approver!=''&&$form['ApproveStatus']==-1)
            {
                $approver=array($approver,'','待提交','');
                array_unshift($infos,$approver);
            }

        }

        return json(['draw' => intval($draw), 'recordsTotal' => intval($recordsTotal), 'recordsFiltered' => $recordsFiltered, 'data' => $infos]);
    }

    public function admin_group($draw, $table, $search, $start, $length, $limitFlag, $order, $columns, $columnString)
    {
        //查询前置条件
        $par=array();
        $id=$this->request->param('id');
        $columnString = "a.id|a.nickname|g.name|g.p_name";
        //查询
        //条件过滤后记录数 必要
        $recordsFiltered = 0;
        //表的总记录数 必要
        $recordsTotal = 0;
        $recordsFilteredResult = array();
        if (strlen($search) > 0) {
            //有搜索条件的情况
            if ($limitFlag) {
                //*****多表查询join改这里******
                $recordsFilteredResult = Db::name('admin')->alias('a')
                    ->join('admin_group g', 'a.admin_group_id = g.id', 'left')
                    ->where(['g.id'=>$id])
                    ->field('a.id,a.nickname,g.name,g.p_name')
                    ->where($columnString, 'like', '%' . $search . '%')
                    ->limit(intval($start), intval($length))
                    ->select();
                $recordsFiltered = sizeof($recordsFilteredResult);
            }
        } else {
            //没有搜索条件的情况
            if ($limitFlag) {
                $recordsFilteredResult = Db::name('admin')->alias('a')
                    ->join('admin_group g', 'a.admin_group_id = g.id', 'left')
                    ->where(['g.id'=>$id])
                    ->field('a.id,a.nickname,g.name,g.p_name')
                    ->order('a.id')
                    ->limit(intval($start), intval($length))
                    ->select();
                $recordsFiltered = $recordsTotal;
            }
        }
        $recordsTotal=count($recordsFilteredResult);
        $recordsFiltered = $recordsTotal;
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

    //常用联系人表单接口
    public function admin($draw, $table, $search, $start, $length, $limitFlag, $order, $columns, $columnString)
    {
        //查询前置条件

        $par=array();
        //获取常用人id
        $user_id=Session::get('current_id');
        $userlist = Db::name('quality_form_info')
                    ->where(['user_id' => $user_id])
                    ->whereNotNull('ApproveIds', 'and')
                    ->field('ApproveIds')
                    ->select();
        $ids = array();
        foreach ($userlist as $item) {
            $_ids = explode(",", $item['ApproveIds']);
            foreach ($_ids as $_id) {
                if (!in_array($_id, $ids)) {
                    $ids[] = $_id;
                }
            }
        }


        $columnString = "a.id|a.nickname|g.name|g.p_name";
        //查询
        //条件过滤后记录数 必要
        $recordsFiltered = 0;
        //表的总记录数 必要
        $recordsTotal = 0;
        $recordsFilteredResult = array();
        if (strlen($search) > 0) {
            //有搜索条件的情况
            if ($limitFlag) {
                //*****多表查询join改这里******
                $recordsFilteredResult = Db::name('admin')->alias('a')
                    ->join('admin_group g', 'a.admin_group_id = g.id', 'left')
                    ->where('a.id','in',$ids)
                    ->field('a.id,a.nickname,g.name,g.p_name')
                    ->where($columnString, 'like', '%' . $search . '%')
                    ->limit(intval($start), intval($length))
                    ->select();
                $recordsFiltered = sizeof($recordsFilteredResult);
            }
        } else {
            //没有搜索条件的情况
            if ($limitFlag) {
                $recordsFilteredResult = Db::name('admin')->alias('a')
                    ->join('admin_group g', 'a.admin_group_id = g.id', 'left')
                    ->where('a.id','in',$ids)
                    ->field('a.id,a.nickname,g.name,g.p_name')
                    ->order('a.id')
                    ->limit(intval($start), intval($length))
                    ->select();
                $recordsFiltered = $recordsTotal;
            }
        }
        $recordsTotal=count($recordsFilteredResult);
        $recordsFiltered = $recordsTotal;
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