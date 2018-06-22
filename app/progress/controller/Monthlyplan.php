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
use app\progress\model\PlanModel;
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
            // 前台需要 传递 标段编号 section_id  计划类型 plan_type 1月计划2年计划3总计划
            $param = input('param.');
            $plan_type = isset($param['plan_type']) ? $param['plan_type'] : 0;
            $section_id = isset($param['section_id']) ? $param['section_id'] : -1;
            if($section_id == 0){
                return json(['code'=>1,'data'=>[],'msg'=>'年度下拉选项']);
            }
            if(empty($plan_type) || empty($section_id)){
                return json(['code' => '-1','msg' => '缺少参数']);
            }
            $section = new PlanModel();
            $data = $section->planYearList($plan_type,$section_id);
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
            // 前台需要 传递 标段编号 section_id 年度 plan_year  计划类型 plan_type 1月计划2年计划3总计划
            $param = input('param.');
            $plan_type = isset($param['plan_type']) ? $param['plan_type'] : 0;
            $section_id = isset($param['section_id']) ? $param['section_id'] : 0;
            $plan_year = isset($param['plan_year']) ? $param['plan_year'] : -1;
            if($plan_year == 0){
                return json(['code'=>1,'data'=>[],'msg'=>'月度下拉选项']);
            }
            if(empty($plan_type) || empty($section_id) || empty($plan_year)){
                return json(['code' => '-1','msg' => '缺少参数']);
            }
            $section = new PlanModel();
            $data = $section->planMonthlyList($plan_type,$section_id,$plan_year);
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
        // 前台需要 传递 标段编号 section_id  [作用::刷新列表时使用]  计划类型 plan_type 1月计划2年计划3总计划
        $param = input('param.');
        $plan_type = isset($param['plan_type']) ? $param['plan_type'] : 0;
        $section_id = isset($param['section_id']) ? $param['section_id'] : 0;
        if(empty($plan_type) || empty($section_id)){
            return json(['code' => '-1','msg' => '缺少参数']);
        }
        $this->assign('section_id',$section_id);
        $this->assign('plan_type',$plan_type);
        return $this->fetch();
    }

    /**
     * 是否存在计划
     * @return \think\response\Json
     * @author hutao
     */
    public function existPlan()
    {
        // 前台需要 传递 标段编号 section_id 年度 plan_year 月度 plan_monthly 计划类型 plan_type 1月计划2年计划3总计划
        $param = input('param.');
        $plan_type = isset($param['plan_type']) ? $param['plan_type'] : 0;
        $section_id = isset($param['section_id']) ? $param['section_id'] : 0;
        $plan_year = isset($param['plan_year']) ? $param['plan_year'] : 0;
        $plan_monthly = isset($param['plan_monthly']) ? $param['plan_monthly'] : 0;
        if(empty($plan_type) || empty($section_id) || empty($plan_year) || empty($plan_monthly)){
            return json(['code'=>'-1','msg'=>'缺少参数']);
        }
        $monthly = new PlanModel();
        $is_exist = $monthly->monthlyExist($plan_type,$section_id,$plan_year,$plan_monthly);
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
            // 系统自动生成数据: 计划类型 plan_type 1月计划2年计划3总计划 编制人 user_id  编制日期 preparation_date
            $param['plan_type'] = 1;
            $param['user_id'] = Session::has('admin') ? Session::get('admin') : 0;
            $param['preparation_date'] = date('Y-m-d');

            // 如果当前选择的年月已经存在月计划,确定后提示覆盖.覆盖则删除原有的计划和与之相关的数据,包括模型关联关系
            $monthly = new PlanModel();
            $is_exist_id = $monthly->monthlyExist(1,$param['section_id'],$param['plan_year'],$param['plan_monthly']);
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
            $monthly = new PlanModel();
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
            // 前台需要 传递本条记录的编号 plan_id  文件编号 file_id  操作类型 operate_type 1 上传 2 删除
            $param = input('param.');
            $plan_id = isset($param['plan_id']) ? $param['plan_id'] : 0;
            $file_id = isset($param['file_id']) ? $param['file_id'] : 0;
            $operate_type = isset($param['operate_type']) ? $param['operate_type'] : 0;
            if(empty($plan_id) || empty($file_id) || empty($operate_type)){
                return json(['code' => '-1','msg' => '缺少参数']);
            }
            $monthly = new PlanModel();
            if($operate_type == 1){
                $data['id'] = $plan_id;
                $data['plan_report_id'] = $file_id;
                $flag = $monthly->editTb($data);
            }else if($operate_type == 2){
                $flag = $monthly->deleteFile($plan_id,$file_id);
            }else{
                return json(['code' => '-1','msg' => '不存在的操作类型']);
            }
            return json($flag);
        }
    }



    // ***************************** 甘特图 *****************************

    // 月计划根据选择的标段，年度，月度获取甘特图数据 测试 http://www.xin.com/progress/Monthlyplan/monthlyInitialise
    public function monthlyInitialise()
    {
        // 前台传递的参数:标段编号 section_id 年度  plan_year 月度 plan_monthly 计划类型 plan_type 1月计划2年计划3总计划
        $param = input('param.');
        $section_id = isset($param['section_id']) ? $param['section_id'] : -1;
        $plan_year = isset($param['plan_year']) ? $param['plan_year'] : -1;
        $plan_monthly = isset($param['plan_monthly']) ? $param['plan_monthly'] : -1;
        $plan_type = isset($param['plan_type']) ? $param['plan_type'] : 0;
        if($section_id == 0 || $plan_year == 0 || $plan_monthly == 0){
            return json(['code' => -1,'msg' => '数据为空']);
        }
        if(empty($section_id) || empty($plan_year) || empty($plan_monthly) || empty($plan_type)){
            return json(['code' => -1,'msg' => '缺少参数']);
        }
        $monthly = new PlanModel();
        $uid = $monthly->monthlyExist($plan_type,$section_id,$plan_year,$plan_monthly);
        $project_data = $monthly->getOne($uid);
        $data['UID'] = $project_data['id']; // 计划的唯一标识符
        $data['Name'] = $project_data['plan_name']; // 计划名称
        $data['CalendarUID'] = 1; // 日历数据
        $calendars = '[{"WeekDays": [{"DayWorking": 1,"DayType": 1},{"DayWorking": 1,"DayType": 2,"WorkingTimes": [{"FromTime": "08:00:00","ToTime": "12:00:00"},{"FromTime": "13:00:00","ToTime": "17:00:00"}]},
                              {"DayWorking": 1,"DayType": 3,"WorkingTimes": [{"FromTime": "08:00:00","ToTime": "12:00:00"},{"FromTime": "13:00:00","ToTime": "17:00:00"}]},
                              {"DayWorking": 1,"DayType": 4,"WorkingTimes": [{"FromTime": "08:00:00","ToTime": "12:00:00"},{"FromTime": "13:00:00","ToTime": "17:00:00"}]},
                              {"DayWorking": 1,"DayType": 5,"WorkingTimes": [{"FromTime": "08:00:00","ToTime": "12:00:00"},{"FromTime": "13:00:00","ToTime": "17:00:00"}]},
                              {"DayWorking": 1,"DayType": 6,"WorkingTimes": [{"FromTime": "08:00:00","ToTime": "12:00:00"},{"FromTime": "13:00:00","ToTime": "17:00:00" }]},
                              {"DayWorking": 1,"DayType": 7}],"Name": "标准","UID": "1","BaseCalendarUID": "-1","IsBaseCalendar": 1,"Exceptions": []}]';
        $data['Calendars'] = json_decode($calendars); // 日历设置数据 json 格式的数据 [主要作用:设置周六日为工作日]
        $tasks = new PlusTaskModel();
        $data['Tasks'] = $tasks->tasksData($plan_type,$uid); // $plan_type 1月计划2年计划3总计划
        // 存在任务,就获取任务里的时间
        if(sizeof($data['Tasks'])){
            $times = $tasks->startFinishDate($plan_type,$uid); // $plan_type 1月计划2年计划3总计划
            if($times['start_date']){
                $data['StartDate'] = $times['start_date']; // 开始时间
            }else{
                $data['StartDate'] = date('Y-m-d').'T08:00:00'; // 开始时间
            }
            if($times['finish_date']){
                $data['FinishDate'] = $times['finish_date']; // 完成日期
            }else{
                $data['FinishDate'] = date('Y-m-d',strtotime("+1 year")).'T59:59:59'; // 完成日期
            }
        }else{
            $data['StartDate'] = date('Y-m-d').'T08:00:00'; // 开始时间
            $data['FinishDate'] = date('Y-m-d',strtotime("+1 year")).'T59:59:59'; // 完成日期
        }
        // todo 资源集合
        $data['Resources'] = json_decode(''); // 资源集合
        return json($data);
    }

    // 新增
    public function tasksAdd()
    {
        // 前台传递的参数:标段编号 section_id 年度  plan_year 月度 plan_monthly 计划类型 plan_type 1月计划2年计划3总计划
        $param = input('param.');
        $section_id = isset($param['section_id']) ? $param['section_id'] : 0;
        $plan_year = isset($param['plan_year']) ? $param['plan_year'] : 0;
        $plan_monthly = isset($param['plan_monthly']) ? $param['plan_monthly'] : 0;
        $plan_type = isset($param['plan_type']) ? $param['plan_type'] : 0;
        if(empty($section_id) || empty($plan_year) || empty($plan_monthly) || empty($plan_type)){
            return json(['code' => -1,'msg' => '缺少参数']);
        }
    }

}