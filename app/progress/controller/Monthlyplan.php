<?php
/**
 * Created by PhpStorm.
 * User: sir
 * Date: 2018/6/13
 * Time: 9:06
 */

namespace app\progress\controller;


use app\admin\controller\Permissions;
use app\admin\model\Attachment;
use app\contract\model\SectionModel;
use app\progress\model\MonthlyplanModel;
use app\progress\model\PlusProjectModel;
use app\progress\model\PlusTaskModel;
use think\Db;
use think\Session;

/**
 * 月计划管理
 * Class actual
 * @package app\progress\controller
 */
class Monthlyplan extends Permissions
{
    /**
     * 月计划管理
     * @return mixed
     * @author hutao
     */
    public function index()
    {
        if($this->request->isAjax()){
            // 根据当前登陆人的权限获取对应的 -- 标段列表选项
            $section = new SectionModel();
            $data = $section->sectionList();
            return json(['code'=>1,'sectionArr'=>$data,'msg'=>'标段列表选项']);
        }
        return $this->fetch();
    }

    /**
     * 倒序获取
     * 根据选择的标段获取年度
     * @return \think\response\Json
     * @author hutao
     */
    public function planYear()
    {
        if($this->request->isAjax()){
            // 前台需要 传递 标段编号 section_id
            $param = input('param.');
            $section_id = isset($param['section_id']) ? $param['section_id'] : 0;
            if(empty($section_id)){
                return json(['code' => '-1','msg' => '缺少参数']);
            }
            $section = new MonthlyplanModel();
            $data = $section->planYearList($section_id);
            return json(['code'=>1,'data'=>$data,'msg'=>'年度下拉选项']);
        }
    }

    /**
     * 倒序获取
     * 根据选择的标段获取月度
     * @return \think\response\Json
     * @author hutao
     */
    public function planMonthly()
    {
        if($this->request->isAjax()){
            // 前台需要 传递 标段编号 section_id 年度 plan_year
            $param = input('param.');
            $section_id = isset($param['section_id']) ? $param['section_id'] : 0;
            $plan_year = isset($param['plan_year']) ? $param['plan_year'] : 0;
            if(empty($section_id) || empty($plan_year)){
                return json(['code' => '-1','msg' => '缺少参数']);
            }
            $section = new MonthlyplanModel();
            $data = $section->planMonthlyList($section_id,$plan_year);
            return json(['code'=>1,'data'=>$data,'msg'=>'月度下拉选项']);
        }
    }

    /**
     * 月计划列表
     * @return mixed
     * @author hutao
     */
    public function list_table()
    {
        // 前台需要 传递 标段编号 section_id  [作用::刷新列表时使用]
        $param = input('param.');
        $section_id = isset($param['section_id']) ? $param['section_id'] : 0;
        if(empty($section_id)){
            return json(['code' => '-1','msg' => '缺少参数']);
        }
        $this->assign('section_id',$section_id);
        return $this->fetch();
    }

    /**
     * 是否存在计划
     * @return \think\response\Json
     * @author hutao
     */
    public function existPlan()
    {
        // 前台需要 传递 标段编号 section_id 年度 plan_year 月度 plan_monthly
        $param = input('param.');
        $section_id = isset($param['section_id']) ? $param['section_id'] : 0;
        $plan_year = isset($param['plan_year']) ? $param['plan_year'] : 0;
        $plan_monthly = isset($param['plan_monthly']) ? $param['plan_monthly'] : 0;
        if(empty($section_id) || empty($plan_year) || empty($plan_monthly)){
            return json(['code'=>'-1','msg'=>'缺少参数']);
        }
        $monthly = new MonthlyplanModel();
        $is_exist = $monthly->monthlyExist($section_id,$plan_year,$plan_monthly);
        if($is_exist){
            return json(['code'=>'2','msg'=>'存在计划']);
        }
        return json(['code'=>'1','msg'=>'不存在计划']);
    }

    /**
     * 新增月计划
     * @return \think\response\Json
     * @author hutao
     */
    public function add()
    {
        if($this->request->isAjax()){
            // 前台需要传递的参数: 计划名称 plan_name 所属标段编号 section_id 年度 plan_year 月度 plan_monthly 更新方式 update_mode
            // 编辑的时候传递本条记录的编号 mid
            $param = input('param.');
            // 验证规则
            $rule = [
                ['plan_name', 'require', '缺少计划名称'],
                ['section_id', 'require', '缺少所属标段编号'],
                ['plan_year', 'require', '缺少年度'],
                ['plan_monthly', 'require', '缺少月度'],
                ['update_mode', 'require', '缺少更新方式']
            ];
            $validate = new \think\Validate($rule);
            //验证部分数据合法性
            if (!$validate->check($param)) {
                return json(['code' => -1,'msg' => $validate->getError()]);
            }

            // 格式化月度
            $mon = explode('-',$param['plan_monthly']);
            $param['plan_monthly'] = intval($mon[1]);
            // 系统自动生成数据: 编制人 user_id  编制日期 preparation_date
            $param['user_id'] = Session::has('admin') ? Session::get('admin') : 0;
            $param['preparation_date'] = date('Y-m-d');

            // 如果当前选择的年月已经存在月计划,确定后提示覆盖.覆盖则删除原有的计划和与之相关的数据,包括模型关联关系
            $monthly = new MonthlyplanModel();
            $is_exist_id = $monthly->monthlyExist($param['section_id'],$param['plan_year'],$param['plan_monthly']);
            if($is_exist_id){
                $cover = isset($param['cover']) ? $param['cover'] : 0;
                if($cover == 0){
                    return json(['code'=>2,'msg'=>'当前选择的年月已经存在月计划,确定覆盖之前的计划吗?']);
                }else{
                    // 覆盖则删除原有的计划和与之相关的数据,包括模型关联关系
                    $monthly->deleteTb($is_exist_id);
                }
            }

            // 更新方式是导入全新计划版本的话,就验证是否上传了Project或P6格式的文件
            if($param['update_mode'] == 2){ // 1手动 2导入
                $plan_file_id = isset($param['plan_file_id']) ? $param['plan_file_id'] : 0;
                if(empty($plan_file_id)){
                    return json(['code' => -1,'msg' => '缺少Project或P6格式的导入文件']);
                }
                //TODO 导入文件
            }
            $flag = $monthly->insertTb($param);
            return json($flag);
        }
    }

    // 查看
    public function preview(){}

    /**
     * 删除月计划
     * @return \think\response\Json
     * @author hutao
     */
    public function del()
    {
        if($this->request->isAjax()){
            // 前台传递本条记录的编号 plan_id
            $param = input('param.');
            $plan_id = isset($param['plan_id']) ? $param['plan_id'] : 0;
            if(empty($plan_id)){
                return json(['code'=>-1,'msg'=>'缺少参数']);
            }
            //TODO 删除该月计划下的所有甘特图数据

            $monthly = new MonthlyplanModel();
            $flag = $monthly->deleteTb($plan_id);
            return json($flag);
        }
    }

    /**
     * 下载报告
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author hutao
     */
    public function download()
    {
        // 前台需要 传递 文件编号 file_id
        $param = input('param.');
        $file_id = isset($param['file_id']) ? $param['file_id'] : 0;
        if(empty($file_id)){
            return json(['code' => '-1','msg' => '缺少参数']);
        }
        $file_obj = Db::name('attachment')->where('id',$file_id)->field('name,filename,filepath')->find();
        $filePath = '';
        if(!empty($file_obj['filepath'])){
            $filePath = '.' . $file_obj['filepath'];
        }
        if(!file_exists($filePath)){
            return json(['code' => '-1','msg' => '文件不存在']);
        }else if(request()->isAjax()){
            return json(['code' => 1]); // 文件存在，告诉前台可以执行下载
        }else{
            $fileName = empty($file_obj['name']) ? $file_obj['filename'] : $file_obj['name'];
            $file = fopen($filePath, "r"); //   打开文件
            //输入文件标签
            $fileName = iconv("utf-8","gb2312",$fileName);
            Header("Content-type:application/octet-stream ");
            Header("Accept-Ranges:bytes ");
            Header("Accept-Length:   " . filesize($filePath));
            Header("Content-Disposition:   attachment;   filename= " . $fileName);
            //   输出文件内容
            echo fread($file, filesize($filePath));
            fclose($file);
            exit;
        }
    }

    /**
     * 上传报告 和 删除报告 共用接口
     * @return \think\response\Json
     * @author hutao
     */
    public function delOrSaveReport()
    {
        if($this->request->isAjax()){
            // 前台需要 传递本条记录的编号 plan_id  文件编号 file_id  操作类型 plan_type 1 上传 2 删除
            $param = input('param.');
            $plan_id = isset($param['plan_id']) ? $param['plan_id'] : 0;
            $file_id = isset($param['file_id']) ? $param['file_id'] : 0;
            $plan_type = isset($param['plan_type']) ? $param['plan_type'] : 0;
            if(empty($plan_id) || empty($file_id) || empty($plan_type)){
                return json(['code' => '-1','msg' => '缺少参数']);
            }
            $monthly = new MonthlyplanModel();
            if($plan_type == 1){
                $data['id'] = $plan_id;
                $data['plan_report_id'] = $file_id;
                $flag = $monthly->editTb($data);
            }else{
                $flag = $monthly->deleteFile($plan_id,$file_id);
            }
            return json($flag);
        }
    }



    // ***************************** 甘特图 *****************************

    // 月计划根据选择的标段，年度，月度获取甘特图数据 测试 http://www.xin.com/progress/Monthlyplan/monthlyInitialise
    public function monthlyInitialise()
    {
        // 前台传递的参数:标段编号 section_id 年度  plan_year 月度 plan_monthly
        $param = input('param.');
        $section_id = isset($param['section_id']) ? $param['section_id'] : 0;
        $plan_year = isset($param['plan_year']) ? $param['plan_year'] : 0;
        $plan_monthly = isset($param['plan_monthly']) ? $param['plan_monthly'] : 0;
        if(empty($section_id) || empty($plan_year) || empty($plan_monthly)){
            return json(['code' => -1,'msg' => '缺少参数']);
        }
        $monthly = new MonthlyplanModel();
        $uid = $monthly->monthlyExist($section_id,$plan_year,$plan_monthly);
        $project = new PlusProjectModel();
        $project_data = $project->getOne(1,$uid); // project_type 1月计划2年计划3总计划
        $data['UID'] = $project_data['uid']; // 计划的唯一标识符
        $data['Name'] = $project_data['name']; // 计划名称
        $data['StartDate'] = $project_data['start_date']; // 开始时间
        $data['FinishDate'] = $project_data['finish_date']; // 完成日期
        $data['CalendarUID'] = $project_data['calendar_uid']; // 日历数据
        $data['Calendars'] = empty($project_data['calendars']) ? [] : json_decode($project_data['calendars']); // 日历设置数据 json 格式的数据
        $tasks = new PlusTaskModel();
        $data['Tasks'] = $tasks->tasksData(1,$uid); // project_type 1月计划2年计划3总计划

        $tak = '[{
        "UID": "1",
        "ActualDuration": 0,
        "Duration": 8,
        "PercentComplete": 0,
        "Department": null,
        "ProjectUID": "100",
        "Milestone": 0,
        "Finish": "2007-01-10T23:59:59",
        "ConstraintType": 0,
        "Principal": null,
        "ParentTaskUID": "-1",
        "WBS": "1",
        "Start": "2007-01-01T00:00:00",
        "OutlineLevel": 1,
        "OutlineNumber": "1",
        "Critical": 0,
        "Notes": null,
        "Summary": 1,
        "ActualFinish": null,
        "Name": "一期地下工厂",
        "ID": 1,
        "Critical2": null,
        "Weight": 0,
        "FixedDate": 0,
        "Work": 28,
        "ConstraintDate": null,
        "PredecessorLink": [],
        "Priority": 500,
        "ActualStart": null,
        "children": [{
            "UID": "2",
            "ActualDuration": 0,
            "Duration": 1,
            "PercentComplete": 0,
            "Department": null,
            "ProjectUID": "100",
            "Milestone": 0,
            "Finish": "2007-01-01T23:59:59",
            "ConstraintType": 0,
            "Principal": null,
            "ParentTaskUID": "1",
            "WBS": "1.1",
            "Assignments": [{
                "ResourceUID": "1",
                "TaskUID": "2",
                "Units": 100
            }],
            "Start": "2007-01-01T00:00:00",
            "OutlineLevel": 2,
            "OutlineNumber": "1.1",
            "Critical": 0,
            "Notes": null,
            "Summary": 0,
            "ActualFinish": null,
			
            "Name": "确定施工范围",
            "ID": 2,
            "Critical2": null,
            "Weight": 0,
            "FixedDate": 0,
            "Work": 4,
            "ConstraintDate": null,
            "PredecessorLink": [],
            "Priority": 500,
            "ActualStart": null
        },
        {
            "UID": "3",
            "ActualDuration": 0,
            "Duration": 2,
            "PercentComplete": 0,
            "Department": null,
            "ProjectUID": "100",
            "Milestone": 0,
            "Finish": "2007-01-03T23:59:59",
            "ConstraintType": 0,
            "Principal": null,
            "ParentTaskUID": "1",
            "WBS": "1.2",
            "Assignments": [{
                "ResourceUID": "1",
                "TaskUID": "3",
                "Units": 100
            }],
            "Start": "2007-01-02T00:00:00",
            "OutlineLevel": 2,
            "OutlineNumber": "1.2",
            "Critical": 0,
            "Notes": null,
            "Summary": 0,
            "ActualFinish": null,
            "Name": "获取施工预算",
            "ID": 3,
            "Critical2": null,
            "Weight": 0,
            "FixedDate": 0,
            "Work": 8,
            "ConstraintDate": null,
            "PredecessorLink": [{
                "TaskUID": "3",
                "LinkLag": 0,
                "LagFormat": 7,
                "Type": 1,
                "PredecessorUID": "2"
            }],
            "Priority": 500,
            "ActualStart": null
        },
        {
            "UID": "4",
            "ActualDuration": 0,
            "Duration": 2,
            "PercentComplete": 0,
            "Department": null,
            "ProjectUID": "100",
            "Milestone": 0,
            "Finish": "2007-01-05T23:59:59",
            "ConstraintType": 0,
            "Principal": null,
            "ParentTaskUID": "1",
            "WBS": "1.3",
            "Assignments": [{
                "ResourceUID": "2",
                "TaskUID": "4",
                "Units": 100
            }],
            "Start": "2007-01-04T00:00:00",
            "OutlineLevel": 2,
            "OutlineNumber": "1.3",
            "Critical": 0,
            "Notes": null,
            "Summary": 0,
            "ActualFinish": null,
            "Name": "筹备预定义资源",
            "ID": 4,
            "Critical2": null,
            "Weight": 0,
            "FixedDate": 0,
            "Work": 8,
            "ConstraintDate": null,
            "PredecessorLink": [{
                "TaskUID": "4",
                "LinkLag": 0,
                "LagFormat": 7,
                "Type": 1,
                "PredecessorUID": "3"
            }],
            "Priority": 500,
            "ActualStart": null
        },
        {
            "UID": "5",
            "ActualDuration": 0,
            "Duration": 2,
            "PercentComplete": 0,
            "Department": null,
            "ProjectUID": "100",
            "Milestone": 0,
            "Finish": "2007-01-09T23:59:59",
            "ConstraintType": 0,
            "Principal": null,
            "ParentTaskUID": "1",
            "WBS": "1.4",
            "Assignments": [{
                "ResourceUID": "2",
                "TaskUID": "5",
                "Units": 100
            }],
            "Start": "2007-01-08T00:00:00",
            "OutlineLevel": 2,
            "OutlineNumber": "1.4",
            "Critical": 0,
            "Notes": null,
            "Summary": 0,
            "ActualFinish": null,
            "Name": "比对效果",
            "ID": 5,
            "Critical2": null,
            "Weight": 0,
            "FixedDate": 0,
            "Work": 8,
            "ConstraintDate": null,
            "PredecessorLink": [{
                "TaskUID": "5",
                "LinkLag": 0,
                "LagFormat": 7,
                "Type": 1,
                "PredecessorUID": "4"
            }],
            "Priority": 500,
            "ActualStart": null
        },
        {
            "UID": "6",
            "ActualDuration": 0,
            "Duration": 0,
            "PercentComplete": 0,
            "Department": null,
            "ProjectUID": "100",
            "Milestone": 1,
            "Finish": "2007-01-10T00:00:00",
            "ConstraintType": 0,
            "Principal": null,
            "ParentTaskUID": "1",
            "WBS": "1.5",
            "Start": "2007-01-10T00:00:00",
            "OutlineLevel": 2,
            "OutlineNumber": "1.5",
            "Critical": 0,
            "Notes": null,
            "Summary": 0,
            "ActualFinish": null,
            "Name": "完成项目范围规划",
            "ID": 6,
            "Critical2": null,
            "Weight": 0,
            "FixedDate": 0,
            "Work": 0,
            "ConstraintDate": null,
            "PredecessorLink": [{
                "TaskUID": "6",
                "LinkLag": 0,
                "LagFormat": 7,
                "Type": 1,
                "PredecessorUID": "5"
            }],
            "Priority": 500,
            "ActualStart": null
        }]
    },{
        "UID": "7",
        "ActualDuration": 0,
        "Duration": 20,
        "PercentComplete": 0,
        "Department": null,
        "ProjectUID": "100",
        "Milestone": 0,
        "Finish": "2007-02-07T23:59:59",
        "ConstraintType": 0,
        "Principal": null,
        "ParentTaskUID": "-1",
        "WBS": "2",
        "Start": "2007-01-11T00:00:00",
        "OutlineLevel": 1,
        "OutlineNumber": "2",
        "Critical": 0,
        "Notes": null,
        "Summary": 1,
        "ActualFinish": null,
        "Name": "地下隧道施工",
        "ID": 7,
        "Critical2": null,
        "Weight": 0,
        "FixedDate": 0,
        "Work": 120,
        "ConstraintDate": null,
        "PredecessorLink": [],
        "Priority": 500,
        "ActualStart": null,
        "children": [{
            "UID": "8",
            "ActualDuration": 0,
            "Duration": 6,
            "PercentComplete": 0,
            "Department": null,
            "ProjectUID": "100",
            "Milestone": 0,
            "Finish": "2007-01-18T23:59:59",
            "ConstraintType": 0,
            "Principal": null,
            "ParentTaskUID": "7",
            "WBS": "2.1",
            "Assignments": [{
                "ResourceUID": "3",
                "TaskUID": "8",
                "Units": 100
            }],
            "Start": "2007-01-11T00:00:00",
            "OutlineLevel": 2,
            "OutlineNumber": "2.1",
            "Critical": 0,
            "Notes": null,
            "Summary": 0,
            "ActualFinish": null,
            "Name": "分析设计方案",
            "ID": 8,
            "Critical2": null,
            "Weight": 0,
            "FixedDate": 0,
            "Work": 40,
            "ConstraintDate": null,
            "PredecessorLink": [{
                "TaskUID": "8",
                "LinkLag": 0,
                "LagFormat": 7,
                "Type": 1,
                "PredecessorUID": "6"
            }],
            "Priority": 500,
            "ActualStart": null
        },
        {
            "UID": "9",
            "ActualDuration": 0,
            "Duration": 4,
            "PercentComplete": 0,
            "Department": null,
            "ProjectUID": "100",
            "Milestone": 0,
            "Finish": "2007-01-24T23:59:59",
            "ConstraintType": 0,
            "Principal": null,
            "ParentTaskUID": "7",
            "WBS": "2.2",
            "Assignments": [{
                "ResourceUID": "3",
                "TaskUID": "9",
                "Units": 100
            }],
            "Start": "2007-01-19T00:00:00",
            "OutlineLevel": 2,
            "OutlineNumber": "2.2",
            "Critical": 0,
            "Notes": null,
            "Summary": 0,
            "ActualFinish": null,
            "Name": "地下一期动工",
            "ID": 9,
            "Critical2": null,
            "Weight": 0,
            "FixedDate": 0,
            "Work": 24,
            "ConstraintDate": null,
            "PredecessorLink": [{
                "TaskUID": "9",
                "LinkLag": 0,
                "LagFormat": 7,
                "Type": 1,
                "PredecessorUID": "8"
            }],
            "Priority": 500,
            "ActualStart": null
        },
        {
            "UID": "10",
            "ActualDuration": 0,
            "Duration": 3,
            "PercentComplete": 0,
            "Department": null,
            "ProjectUID": "100",
            "Milestone": 0,
            "Finish": "2007-01-29T23:59:59",
            "ConstraintType": 0,
            "Principal": null,
            "ParentTaskUID": "7",
            "WBS": "2.3",
            "Assignments": [{
                "ResourceUID": "2",
                "TaskUID": "10",
                "Units": 100
            }],
            "Start": "2007-01-25T00:00:00",
            "OutlineLevel": 2,
            "OutlineNumber": "2.3",
            "Critical": 0,
            "Notes": null,
            "Summary": 0,
            "ActualFinish": null,
            "Name": "地下隧道二期开工",
            "ID": 10,
            "Critical2": null,
            "Weight": 0,
            "FixedDate": 0,
            "Work": 16,
            "ConstraintDate": null,
            "PredecessorLink": [{
                "TaskUID": "10",
                "LinkLag": 0,
                "LagFormat": 7,
                "Type": 1,
                "PredecessorUID": "9"
            }],
            "Priority": 500,
            "ActualStart": null
        },
        {
            "UID": "11",
            "ActualDuration": 0,
            "Duration": 1,
            "PercentComplete": 0,
            "Department": null,
            "ProjectUID": "100",
            "Milestone": 0,
            "Finish": "2007-01-30T23:59:59",
            "ConstraintType": 0,
            "Principal": null,
            "ParentTaskUID": "7",
            "WBS": "2.4",
            "Assignments": [{
                "ResourceUID": "2",
                "TaskUID": "11",
                "Units": 100
            },
            {
                "ResourceUID": "3",
                "TaskUID": "11",
                "Units": 100
            }],
            "Start": "2007-01-30T00:00:00",
            "OutlineLevel": 2,
            "OutlineNumber": "2.4",
            "Critical": 0,
            "Notes": null,
            "Summary": 0,
            "ActualFinish": null,
            "Name": "工作组共同审阅预算",
            "ID": 11,
            "Critical2": null,
            "Weight": 0,
            "FixedDate": 0,
            "Work": 8,
            "ConstraintDate": null,
            "PredecessorLink": [{
                "LinkLag": 0,
                "Type": 1,
                "LagFormat": 7,
                "PredecessorUID": "10"
            }],
            "Priority": 500,
            "ActualStart": null
        },
        {
            "UID": "12",
            "ActualDuration": 0,
            "Duration": 1,
            "PercentComplete": 0,
            "Department": null,
            "ProjectUID": "100",
            "Milestone": 0,
            "Finish": "2007-01-31T23:59:59",
            "ConstraintType": 0,
            "Principal": null,
            "ParentTaskUID": "7",
            "WBS": "2.5",
            "Assignments": [{
                "ResourceUID": "3",
                "TaskUID": "12",
                "Units": 100
            }],
            "Start": "2007-01-31T00:00:00",
            "OutlineLevel": 2,
            "OutlineNumber": "2.5",
            "Critical": 0,
            "Notes": null,
            "Summary": 0,
            "ActualFinish": null,
            "Name": "地下隧道三期",
            "ID": 12,
            "Critical2": null,
            "Weight": 0,
            "FixedDate": 0,
            "Work": 8,
            "ConstraintDate": null,
            "PredecessorLink": [{
                "LinkLag": 0,
                "Type": 1,
                "LagFormat": 7,
                "PredecessorUID": "11"
            }],
            "Priority": 500,
            "ActualStart": null
        },
        {
            "UID": "13",
            "ActualDuration": 0,
            "Duration": 1,
            "PercentComplete": 0,
            "Department": null,
            "ProjectUID": "100",
            "Milestone": 0,
            "Finish": "2007-02-01T23:59:59",
            "ConstraintType": 0,
            "Principal": null,
            "ParentTaskUID": "7",
            "WBS": "2.6",
            "Assignments": [{
                "ResourceUID": "2",
                "TaskUID": "13",
                "Units": 100
            }],
            "Start": "2007-02-01T00:00:00",
            "OutlineLevel": 2,
            "OutlineNumber": "2.6",
            "Critical": 0,
            "Notes": null,
            "Summary": 0,
            "ActualFinish": null,
            "Name": "制定完工期限",
            "ID": 13,
            "Critical2": null,
            "Weight": 0,
            "FixedDate": 0,
            "Work": 8,
            "ConstraintDate": null,
            "PredecessorLink": [{
                "LinkLag": 0,
                "Type": 1,
                "LagFormat": 7,
                "PredecessorUID": "12"
            }],
            "Priority": 500,
            "ActualStart": null
        },
        {
            "UID": "14",
            "ActualDuration": 0,
            "Duration": 1,
            "PercentComplete": 0,
            "Department": null,
            "ProjectUID": "100",
            "Milestone": 0,
            "Finish": "2007-02-02T23:59:59",
            "ConstraintType": 0,
            "Principal": null,
            "ParentTaskUID": "7",
            "WBS": "2.7",
            "Assignments": [{
                "ResourceUID": "1",
                "TaskUID": "14",
                "Units": 100
            },
            {
                "ResourceUID": "2",
                "TaskUID": "14",
                "Units": 100
            }],
            "Start": "2007-02-02T00:00:00",
            "OutlineLevel": 2,
            "OutlineNumber": "2.7",
            "Critical": 0,
            "Notes": null,
            "Summary": 0,
            "ActualFinish": null,
            "Name": "地下隧道四期开挖",
            "ID": 14,
            "Critical2": null,
            "Weight": 0,
            "FixedDate": 0,
            "Work": 8,
            "ConstraintDate": null,
            "PredecessorLink": [{
                "LinkLag": 0,
                "Type": 1,
                "LagFormat": 7,
                "PredecessorUID": "13"
            }],
            "Priority": 500,
            "ActualStart": null
        },
        {
            "UID": "15",
            "ActualDuration": 0,
            "Duration": 2,
            "PercentComplete": 0,
            "Department": null,
            "ProjectUID": "100",
            "Milestone": 0,
            "Finish": "2007-02-06T23:59:59",
            "ConstraintType": 0,
            "Principal": null,
            "ParentTaskUID": "7",
            "WBS": "2.8",
            "Assignments": [{
                "ResourceUID": "2",
                "TaskUID": "15",
                "Units": 100
            }],
            "Start": "2007-02-05T00:00:00",
            "OutlineLevel": 2,
            "OutlineNumber": "2.8",
            "Critical": 0,
            "Notes": null,
            "Summary": 0,
            "ActualFinish": null,
            "Name": "检验质量标准",
            "ID": 15,
            "Critical2": null,
            "Weight": 0,
            "FixedDate": 0,
            "Work": 8,
            "ConstraintDate": null,
            "PredecessorLink": [{
                "LinkLag": 0,
                "Type": 1,
                "LagFormat": 7,
                "PredecessorUID": "14"
            }],
            "Priority": 500,
            "ActualStart": null
        },
        {
            "UID": "16",
            "ActualDuration": 0,
            "Duration": 0,
            "PercentComplete": 0,
            "Department": null,
            "ProjectUID": "100",
            "Milestone": 1,
            "Finish": "2007-02-07T00:00:00",
            "ConstraintType": 0,
            "Principal": null,
            "ParentTaskUID": "7",
            "WBS": "2.9",
            "Start": "2007-02-07T00:00:00",
            "OutlineLevel": 2,
            "OutlineNumber": "2.9",
            "Critical": 0,
            "Notes": null,
            "Summary": 0,
            "ActualFinish": null,
            "Name": "完成交付工作",
            "ID": 16,
            "Critical2": null,
            "Weight": 0,
            "FixedDate": 0,
            "Work": 0,
            "ConstraintDate": null,
            "PredecessorLink": [{
                "LinkLag": 0,
                "Type": 1,
                "LagFormat": 7,
                "PredecessorUID": "15"
            }],
            "Priority": 500,
            "ActualStart": null
        }]
    },
    {
        "UID": "17",
        "ActualDuration": 0,
        "Duration": 21,
        "PercentComplete": 0,
        "Department": null,
        "ProjectUID": "100",
        "Milestone": 0,
        "Finish": "2007-03-08T23:59:59",
        "ConstraintType": 0,
        "Principal": null,
        "ParentTaskUID": "-1",
        "WBS": "3",
        "Start": "2007-02-08T00:00:00",
        "OutlineLevel": 1,
        "OutlineNumber": "3",
        "Critical": 0,
        "Notes": null,
        "Summary": 1,
        "ActualFinish": null,
        "Name": "拱顶开挖",
        "ID": 17,
        "Critical2": null,
        "Weight": 0,
        "FixedDate": 0,
        "Work": 120,
        "ConstraintDate": null,
        "PredecessorLink": [],
        "Priority": 500,
        "ActualStart": null,
        "children": [{
            "UID": "18",
            "ActualDuration": 0,
            "Duration": 3,
            "PercentComplete": 0,
            "Department": null,
            "ProjectUID": "100",
            "Milestone": 0,
            "Finish": "2007-02-12T23:59:59",
            "ConstraintType": 0,
            "Principal": null,
            "ParentTaskUID": "17",
            "WBS": "3.1",
            "Assignments": [{
                "ResourceUID": "3",
                "TaskUID": "18",
                "Units": 100
            }],
            "Start": "2007-02-08T00:00:00",
            "OutlineLevel": 2,
            "OutlineNumber": "3.1",
            "Critical": 0,
            "Notes": null,
            "Summary": 0,
            "ActualFinish": null,
            "Name": "拱顶一期开挖",
            "ID": 18,
            "Critical2": null,
            "Weight": 0,
            "FixedDate": 0,
            "Work": 16,
            "ConstraintDate": null,
            "PredecessorLink": [{
                "LinkLag": 0,
                "Type": 1,
                "LagFormat": 7,
                "PredecessorUID": "16"
            }],
            "Priority": 500,
            "ActualStart": null
        },
        {
            "UID": "19",
            "ActualDuration": 0,
            "Duration": 6,
            "PercentComplete": 0,
            "Department": null,
            "ProjectUID": "100",
            "Milestone": 0,
            "Finish": "2007-02-20T23:59:59",
            "ConstraintType": 0,
            "Principal": null,
            "ParentTaskUID": "17",
            "WBS": "3.2",
            "Assignments": [{
                "ResourceUID": "3",
                "TaskUID": "19",
                "Units": 100
            }],
            "Start": "2007-02-13T00:00:00",
            "OutlineLevel": 2,
            "OutlineNumber": "3.2",
            "Critical": 0,
            "Notes": null,
            "Summary": 0,
            "ActualFinish": null,
            "Name": "拱顶二期开挖",
            "ID": 19,
            "Critical2": null,
            "Weight": 0,
            "FixedDate": 0,
            "Work": 40,
            "ConstraintDate": null,
            "PredecessorLink": [{
                "LinkLag": 0,
                "Type": 1,
                "LagFormat": 7,
                "PredecessorUID": "18"
            }],
            "Priority": 500,
            "ActualStart": null
        },
        {
            "UID": "20",
            "ActualDuration": 0,
            "Duration": 5,
            "PercentComplete": 0,
            "Department": null,
            "ProjectUID": "100",
            "Milestone": 0,
            "Finish": "2007-02-27T23:59:59",
            "ConstraintType": 0,
            "Principal": null,
            "ParentTaskUID": "17",
            "WBS": "3.3",
            "Assignments": [{
                "ResourceUID": "3",
                "TaskUID": "20",
                "Units": 100
            }],
            "Start": "2007-02-21T00:00:00",
            "OutlineLevel": 2,
            "OutlineNumber": "3.3",
            "Critical": 0,
            "Notes": null,
            "Summary": 0,
            "ActualFinish": null,
            "Name": "拱顶三期开挖",
            "ID": 20,
            "Critical2": null,
            "Weight": 0,
            "FixedDate": 0,
            "Work": 32,
            "ConstraintDate": null,
            "PredecessorLink": [{
                "LinkLag": 0,
                "Type": 1,
                "LagFormat": 7,
                "PredecessorUID": "19"
            }],
            "Priority": 500,
            "ActualStart": null
        },
        {
            "UID": "21",
            "ActualDuration": 0,
            "Duration": 3,
            "PercentComplete": 0,
            "Department": null,
            "ProjectUID": "100",
            "Milestone": 0,
            "Finish": "2007-03-02T23:59:59",
            "ConstraintType": 0,
            "Principal": null,
            "ParentTaskUID": "17",
            "WBS": "3.4",
            "Assignments": [{
                "ResourceUID": "1",
                "TaskUID": "21",
                "Units": 100
            }],
            "Start": "2007-02-28T00:00:00",
            "OutlineLevel": 2,
            "OutlineNumber": "3.4",
            "Critical": 0,
            "Notes": null,
            "Summary": 0,
            "ActualFinish": null,
            "Name": "拱顶四期开挖",
            "ID": 21,
            "Critical2": null,
            "Weight": 0,
            "FixedDate": 0,
            "Work": 16,
            "ConstraintDate": null,
            "PredecessorLink": [{
                "LinkLag": 0,
                "Type": 1,
                "LagFormat": 7,
                "PredecessorUID": "20"
            }],
            "Priority": 500,
            "ActualStart": null
        },
        {
            "UID": "22",
            "ActualDuration": 0,
            "Duration": 2,
            "PercentComplete": 0,
            "Department": null,
            "ProjectUID": "100",
            "Milestone": 0,
            "Finish": "2007-03-06T23:59:59",
            "ConstraintType": 0,
            "Principal": null,
            "ParentTaskUID": "17",
            "WBS": "3.5",
            "Assignments": [{
                "ResourceUID": "1",
                "TaskUID": "22",
                "Units": 100
            }],
            "Start": "2007-03-05T00:00:00",
            "OutlineLevel": 2,
            "OutlineNumber": "3.5",
            "Critical": 0,
            "Notes": null,
            "Summary": 0,
            "ActualFinish": null,
            "Name": "拱顶五期开挖",
            "ID": 22,
            "Critical2": null,
            "Weight": 0,
            "FixedDate": 0,
            "Work": 8,
            "ConstraintDate": null,
            "PredecessorLink": [{
                "LinkLag": 0,
                "Type": 1,
                "LagFormat": 7,
                "PredecessorUID": "21"
            }],
            "Priority": 500,
            "ActualStart": null
        },
        {
            "UID": "23",
            "ActualDuration": 0,
            "Duration": 1,
            "PercentComplete": 0,
            "Department": null,
            "ProjectUID": "100",
            "Milestone": 0,
            "Finish": "2007-03-07T23:59:59",
            "ConstraintType": 0,
            "Principal": null,
            "ParentTaskUID": "17",
            "WBS": "3.6",
            "Assignments": [{
                "ResourceUID": "1",
                "TaskUID": "23",
                "Units": 100
            },
            {
                "ResourceUID": "2",
                "TaskUID": "23",
                "Units": 100
            }],
            "Start": "2007-03-07T00:00:00",
            "OutlineLevel": 2,
            "OutlineNumber": "3.6",
            "Critical": 0,
            "Notes": null,
            "Summary": 0,
            "ActualFinish": null,
            "Name": "拱顶开挖验收",
            "ID": 23,
            "Critical2": null,
            "Weight": 0,
            "FixedDate": 0,
            "Work": 8,
            "ConstraintDate": null,
            "PredecessorLink": [{
                "LinkLag": 0,
                "Type": 1,
                "LagFormat": 7,
                "PredecessorUID": "22"
            }],
            "Priority": 500,
            "ActualStart": null
        },
        {
            "UID": "24",
            "ActualDuration": 0,
            "Duration": 0,
            "PercentComplete": 0,
            "Department": null,
            "ProjectUID": "100",
            "Milestone": 1,
            "Finish": "2007-03-08T00:00:00",
            "ConstraintType": 0,
            "Principal": null,
            "ParentTaskUID": "17",
            "WBS": "3.7",
            "Start": "2007-03-08T00:00:00",
            "OutlineLevel": 2,
            "OutlineNumber": "3.7",
            "Critical": 0,
            "Notes": null,
            "Summary": 0,
            "ActualFinish": null,
            "Name": "完成交付工作",
            "ID": 24,
            "Critical2": null,
            "Weight": 0,
            "FixedDate": 0,
            "Work": 0,
            "ConstraintDate": null,
            "PredecessorLink": [{
                "LinkLag": 0,
                "Type": 1,
                "LagFormat": 7,
                "PredecessorUID": "23"
            }],
            "Priority": 500,
            "ActualStart": null
        }]
    },{
        "UID": "25",
        "ActualDuration": 0,
        "Duration": 35,
        "PercentComplete": 0,
        "Department": null,
        "ProjectUID": "100",
        "Milestone": 0,
        "Finish": "2007-04-26T23:59:59",
        "ConstraintType": 0,
        "Principal": null,
        "ParentTaskUID": "-1",
        "WBS": "4",
        "Start": "2007-03-09T00:00:00",
        "OutlineLevel": 1,
        "OutlineNumber": "4",
        "Critical": 0,
        "Notes": null,
        "Summary": 1,
        "ActualFinish": null,
        "Name": "拱顶浇筑",
        "ID": 25,
        "Critical2": null,
        "Weight": 0,
        "FixedDate": 0,
        "Work": 264,
        "ConstraintDate": null,
        "PredecessorLink": [],
        "Priority": 500,
        "ActualStart": null,
        "children": [{
            "UID": "26",
            "ActualDuration": 0,
            "Duration": 1,
            "PercentComplete": 0,
            "Department": null,
            "ProjectUID": "100",
            "Milestone": 0,
            "Finish": "2007-03-09T23:59:59",
            "ConstraintType": 0,
            "Principal": null,
            "ParentTaskUID": "25",
            "WBS": "4.1",
            "Assignments": [{
                "ResourceUID": "4",
                "TaskUID": "26",
                "Units": 100
            }],
            "Start": "2007-03-09T00:00:00",
            "OutlineLevel": 2,
            "OutlineNumber": "4.1",
            "Critical": 0,
            "Notes": null,
            "Summary": 0,
            "ActualFinish": null,
            "Name": "拱顶浇筑一期",
            "ID": 26,
            "Critical2": null,
            "Weight": 0,
            "FixedDate": 0,
            "Work": 8,
            "ConstraintDate": null,
            "PredecessorLink": [{
                "LinkLag": 0,
                "Type": 1,
                "LagFormat": 7,
                "PredecessorUID": "24"
            }],
            "Priority": 500,
            "ActualStart": null
        },
        {
            "UID": "27",
            "ActualDuration": 0,
            "Duration": 1,
            "PercentComplete": 0,
            "Department": null,
            "ProjectUID": "100",
            "Milestone": 0,
            "Finish": "2007-03-12T23:59:59",
            "ConstraintType": 0,
            "Principal": null,
            "ParentTaskUID": "25",
            "WBS": "4.2",
            "Assignments": [{
                "ResourceUID": "4",
                "TaskUID": "27",
                "Units": 100
            }],
            "Start": "2007-03-12T00:00:00",
            "OutlineLevel": 2,
            "OutlineNumber": "4.2",
            "Critical": 0,
            "Notes": null,
            "Summary": 0,
            "ActualFinish": null,
            "Name": "拱顶浇筑二期",
            "ID": 27,
            "Critical2": null,
            "Weight": 0,
            "FixedDate": 0,
            "Work": 8,
            "ConstraintDate": null,
            "PredecessorLink": [{
                "LinkLag": 0,
                "Type": 1,
                "LagFormat": 7,
                "PredecessorUID": "26"
            }],
            "Priority": 500,
            "ActualStart": null
        },
        {
            "UID": "28",
            "ActualDuration": 0,
            "Duration": 1,
            "PercentComplete": 0,
            "Department": null,
            "ProjectUID": "100",
            "Milestone": 0,
            "Finish": "2007-03-13T23:59:59",
            "ConstraintType": 0,
            "Principal": null,
            "ParentTaskUID": "25",
            "WBS": "4.3",
            "Assignments": [{
                "ResourceUID": "4",
                "TaskUID": "28",
                "Units": 100
            }],
            "Start": "2007-03-13T00:00:00",
            "OutlineLevel": 2,
            "OutlineNumber": "4.3",
            "Critical": 0,
            "Notes": null,
            "Summary": 0,
            "ActualFinish": null,
            "Name": "拱顶浇筑三期",
            "ID": 28,
            "Critical2": null,
            "Weight": 0,
            "FixedDate": 0,
            "Work": 8,
            "ConstraintDate": null,
            "PredecessorLink": [{
                "LinkLag": 0,
                "Type": 1,
                "LagFormat": 7,
                "PredecessorUID": "27"
            }],
            "Priority": 500,
            "ActualStart": null
        },
        {
            "UID": "29",
            "ActualDuration": 0,
            "Duration": 15,
            "PercentComplete": 0,
            "Department": null,
            "ProjectUID": "100",
            "Milestone": 0,
            "Finish": "2007-04-03T23:59:59",
            "ConstraintType": 0,
            "Principal": null,
            "ParentTaskUID": "25",
            "WBS": "4.4",
            "Assignments": [{
                "ResourceUID": "4",
                "TaskUID": "29",
                "Units": 100
            }],
            "Start": "2007-03-14T00:00:00",
            "OutlineLevel": 2,
            "OutlineNumber": "4.4",
            "Critical": 0,
            "Notes": null,
            "Summary": 0,
            "ActualFinish": null,
            "Name": "拱顶浇筑四期",
            "ID": 29,
            "Critical2": null,
            "Weight": 0,
            "FixedDate": 0,
            "Work": 120,
            "ConstraintDate": null,
            "PredecessorLink": [{
                "LinkLag": 0,
                "Type": 1,
                "LagFormat": 7,
                "PredecessorUID": "28"
            }],
            "Priority": 500,
            "ActualStart": null
        },
        {
            "UID": "30",
            "ActualDuration": 0,
            "Duration": 16,
            "PercentComplete": 0,
            "Department": null,
            "ProjectUID": "100",
            "Milestone": 0,
            "Finish": "2007-04-25T23:59:59",
            "ConstraintType": 0,
            "Principal": null,
            "ParentTaskUID": "25",
            "WBS": "4.5",
            "Assignments": [{
                "ResourceUID": "4",
                "TaskUID": "30",
                "Units": 100
            }],
            "Start": "2007-04-04T00:00:00",
            "OutlineLevel": 2,
            "OutlineNumber": "4.5",
            "Critical": 0,
            "Notes": null,
            "Summary": 0,
            "ActualFinish": null,
            "Name": "拱顶浇筑验收",
            "ID": 30,
            "Critical2": null,
            "Weight": 0,
            "FixedDate": 0,
            "Work": 120,
            "ConstraintDate": null,
            "PredecessorLink": [{
                "LinkLag": 0,
                "Type": 1,
                "LagFormat": 19,
                "PredecessorUID": "29"
            }],
            "Priority": 500,
            "ActualStart": null
        },
        {
            "UID": "31",
            "ActualDuration": 0,
            "Duration": 0,
            "PercentComplete": 0,
            "Department": null,
            "ProjectUID": "100",
            "Milestone": 1,
            "Finish": "2007-04-26T00:00:00",
            "ConstraintType": 0,
            "Principal": null,
            "ParentTaskUID": "25",
            "WBS": "4.6",
            "Start": "2007-04-26T00:00:00",
            "OutlineLevel": 2,
            "OutlineNumber": "4.6",
            "Critical": 0,
            "Notes": null,
            "Summary": 0,
            "ActualFinish": null,
            "Name": "完成交付工作",
            "ID": 31,
            "Critical2": null,
            "Weight": 0,
            "FixedDate": 0,
            "Work": 0,
            "ConstraintDate": null,
            "PredecessorLink": [{
                "LinkLag": 0,
                "Type": 1,
                "LagFormat": 7,
                "PredecessorUID": "30"
            }],
            "Priority": 500,
            "ActualStart": null
        }]
    }]';

        $data['Tasks'] = json_decode($tak); // project_type 1月计划2年计划3总计划
        $data['Principals'] = empty($project_data['principals']) ? [] : json_decode($project_data['principals']); // 负责人集合
        $data['Departments'] = empty($project_data['departments']) ? [] : json_decode($project_data['departments']); // 部门集合
        $data['Resources'] = empty($project_data['resources']) ? [] : json_decode($project_data['resources']); // 资源集合
        return json($data);
    }

}