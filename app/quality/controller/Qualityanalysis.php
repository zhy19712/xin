<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/4/18
 * Time: 10:44
 */
/**
 * 质量管理-统计分析
 * Class Qualityanalysis
 * @package app\quality\controller
 */
namespace app\quality\controller;
use app\admin\controller\Permissions;
use think\exception\PDOException;
use think\Db;

class Qualityanalysis extends Permissions
{
    /**
     * 模板首页
     * @return mixed
     */
    public function index()
    {
        return $this->fetch();
    }

    /**
     * 查询数据库中的所有的年份月份
     * @return \think\response\Json
     */
    public function getAllMonth()
    {
        //查询数据库中的最小时间
        $min_time = Db::name("quality_unit")->where("EvaluateDate > 0")->min("EvaluateDate");
        //查询数据库中的最大的时间
        $max_time = Db::name("quality_unit")->where("EvaluateDate > 0")->max("EvaluateDate");

        //定义一个空的数组
        $timeline = array();
        $month = array();
        $StartMonth = date("Y-m-d",$min_time); //开始日期
        $EndMonth = date("Y-m-d",$max_time); //结束日期
        $ToStartMonth = strtotime( $StartMonth ); //转换一下
        $ToEndMonth   = strtotime( $EndMonth ); //一样转换一下
        $i            = false; //开始标示
        while( $ToStartMonth < $ToEndMonth ){
            $NewMonth = !$i ? date('Y-m', strtotime('+0 Month', $ToStartMonth)) : date('Y-m', strtotime('+1 Month', $ToStartMonth));
            $ToStartMonth = strtotime( $NewMonth );
            $i = true;
            $timeline[] = $NewMonth;//时间

        }

        array_pop($timeline);//去除掉多余的月份

        foreach ($timeline as $key=>$val)
        {
            //设定时间点查询，时间点为每个月的26号的0:0:0到下个月的25号的23:59:59
            //结束日期,这个月的25号23:59:59
            $end = mktime(23,59,59,date("m",strtotime($val)),25,date("Y",strtotime($val)));
            $end = date("Y.m.d",$end);

            //开始日期,上个月的26号0:0:0
            $start = mktime(0,0,0,date('m',strtotime("-1 month",strtotime($val))),26,date('Y',strtotime("-1 month",strtotime($val))));
            $start = date("Y.m.d",$start);
            $month[] = $start."-".$end;

        }
        return json(["code"=>1,"data"=>$timeline,"month"=>$month]);
    }

    /**
     * 左边的树状图和表格
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getIndexLeft()
    {

            $search_time = input('post.time_slot');

//            $search_time = "2018.05.26-2018.06.25";

            if(empty($search_time))
            {
                return json(["code" => -1,"msg" =>"查询时间错误！"]);
            }
            //截取开始时间和结束时间
            $time = explode("-",$search_time);
            //处理下时间用"-"，代替"."
            $start = str_replace(".","-",$time["0"]);
            $end = str_replace(".","-",$time["1"]);

            //开始时间
            $start_time = $start = mktime(0,0,0,date('m',strtotime($start)),date('d',strtotime($start)),date('Y',strtotime($start)));
            //结束时间
            $end_time = mktime(23,59,59,date("m",strtotime($end)),date('d',strtotime($end)),date("Y",strtotime($end)));

            //定义空数组
            $section_form_data = array();

            //柱状图
            //标段
            $section = Db::name("section")->order("id asc")->column("shortName,id");//标段名
            foreach ($section as $aa => $bb) {
                $section_name[] = strval($aa);
            }

            foreach ($section as $cc => $dd) {

                $temp_data = Db::name('quality_unit')->alias('r')
                    ->join('quality_division c', 'c.id = r.division_id', 'left')
                    ->join('section s', 'c.section_id = s.id', 'left')
                    ->where('s.id', $dd)
                    ->where("r.EvaluateDate >= ".$start_time. " AND r.EvaluateDate <= ".$end_time)
                    ->field("r.EvaluateResult,r.id,r.EvaluateDate,s.id as section_id")->order("s.id asc")->select();

                if ($temp_data) {
                    $section_form_data[] = $temp_data;
                } else {
                    $section_form_data[] = [];
                }
            }

            //定义一个空的数组
            $form_result_result = array();
            $section_rate_number = array();

            //验评结果：0未验评2合格，3优良
            foreach ($section_form_data as $qq => $rr) {

                $count = array_count_values(array_column($rr, "EvaluateResult"));//统计优良、合格

                if (isset($count["3"]) && isset($count["2"])) {
                    $section_rate_number["excellent_number"] = $count["3"];
                    $section_rate_number["qualified_number"] = $count["2"];
                    $count["3"] = $count["3"] ? $count["3"] : 0;
                    $count["2"] = $count["2"] ? $count["2"] : 0;
                    $section_rate_number["total"] = $count["3"] + $count["2"];

                    $form_result_result[$qq]["section_rate_number"] = $section_rate_number;

                    //计算优良率，合格率
                    $form_result_result[$qq]['excellent'] = round($count["3"] / ($count["3"] + $count["2"]) * 100);//优良率

                } else if (!isset($count["3"]) && isset($count["2"])) {
                    $section_rate_number["excellent_number"] = 0;
                    $section_rate_number["qualified_number"] = $count["2"];
                    $count["2"] = $count["2"] ? $count["2"] : 0;
                    $section_rate_number["total"] = 0 + $count["2"];

                    $form_result_result[$qq]["section_rate_number"] = $section_rate_number;

                    //计算优良率，合格率
                    $form_result_result[$qq]['excellent'] = 0;//优良率
                } else if (isset($count["3"]) && !isset($count["2"])) {
                    $section_rate_number["excellent_number"] = $count["3"];
                    $section_rate_number["qualified_number"] = 0;
                    $count["3"] = $count["3"] ? $count["3"] : 0;
                    $section_rate_number["total"] = $count["3"] + 0;

                    $form_result_result[$qq]["section_rate_number"] = $section_rate_number;

                    //计算优良率，合格率
                    $form_result_result[$qq]['excellent'] = round($count["3"] / ($count["3"] + 0) * 100);//优良率

                } else {
                    $section_rate_number["excellent_number"] = 0;
                    $section_rate_number["qualified_number"] = 0;
                    $section_rate_number["total"] = 0;

                    $form_result_result[$qq]["section_rate_number"] = $section_rate_number;

                    //计算优良率，合格率不合格率
                    $form_result_result[$qq]['excellent'] = 0;//优良率

                }
            }
            $result = ["section" => $section_name, "form_result_result" => $form_result_result];//柱状图表格

            return json(["code" => 1, "data" => $result]);

    }

    /**
     * 查询数据库中的所有的年份
     * @return \think\response\Json
     */
    public function getAllYear()
    {
        //查询数据库中的最小时间
        $min_time = Db::name("quality_unit")->where("EvaluateDate > 0")->min("EvaluateDate");
        //查询数据库中的最大的时间
        $max_time = Db::name("quality_unit")->where("EvaluateDate > 0")->max("EvaluateDate");

        //定义一个空的数组
        $timeline = array();
        $StartMonth = date("Y-m-d",$min_time); //开始日期
        $EndMonth = date("Y-m-d",$max_time); //结束日期
        $ToStartMonth = strtotime( $StartMonth ); //转换一下
        $ToEndMonth   = strtotime( $EndMonth ); //一样转换一下
        $i            = false; //开始标示
        while( $ToStartMonth < $ToEndMonth ){
            $NewMonth = !$i ? date('Y-m', strtotime('+0 Month', $ToStartMonth)) : date('Y-m', strtotime('+1 Month', $ToStartMonth));
            $ToStartMonth = strtotime( $NewMonth );
            $i = true;
            $timeline[] = $NewMonth;//时间
        }
        array_pop($timeline);//去除掉多余的月份

        foreach ($timeline as $key=>$val)
        {
            $year[] = substr($val,0,4);
        }

        //定义一个空数组
        $newyear=array();

        foreach($year as $v){
            if(!in_array($v,$newyear)){
                $newyear[]=$v;
            }
        }

        return json(["code"=>1,"data"=>$newyear]);
    }

    /**
     * 右边的折线图
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getIndexRight()
    {
        //查询的年份
        $search_year = input('post.year');

        //开始时间为每一年的1月1号0点0时0分
        $start_time = mktime(0,0,0,1,1,$search_year);
        //结束时间为每一年的12月31号23点59分59秒
        $end_time = mktime(23,59,59,12,31,$search_year);

        //定义一个空的数组
        $timeline = array();

        $StartMonth = date("Y-m-d",$start_time); //开始日期
        $EndMonth = date("Y-m-d",$end_time); //结束日期
        $ToStartMonth = strtotime( $StartMonth ); //转换一下
        $ToEndMonth   = strtotime( $EndMonth ); //一样转换一下
        $i            = false; //开始标示
        while( $ToStartMonth < $ToEndMonth ){
            $NewMonth = !$i ? date('Y-m', strtotime('+0 Month', $ToStartMonth)) : date('Y-m', strtotime('+1 Month', $ToStartMonth));
            $ToStartMonth = strtotime( $NewMonth );
            $i = true;
            $timeline[] = $NewMonth;//时间

        }

        array_pop($timeline);//去除掉多余的月份

        //定义空数组
        $section_form_data = array();

        //柱状图
        $section = Db::name("section")->order("id asc")->column("shortName,id");//标段名

        foreach ($section as $aa => $bb) {
            $section_name[] = strval($aa);
            $section_id[] = $bb;
        }

        foreach ($section as $aaaa=>$bbbb)
        {
            foreach ($timeline as $cc => $dd) {

                //开始日期
                $start = mktime(0,0,0,date("m",strtotime($dd)),1,date("Y",strtotime($dd)));

                //结束日期
                $end = mktime(23,59,59,date('m',strtotime($dd)),date('t',strtotime($dd)),date('Y',strtotime($dd)));

                $temp_data = Db::name('quality_unit')->alias('r')
                    ->join('quality_division c', 'c.id = r.division_id', 'left')
                    ->join('section s', 'c.section_id = s.id', 'left')
                    ->where("s.id",$bbbb)
                    ->where("r.EvaluateDate >= ".$start. " AND r.EvaluateDate <= ".$end)
                    ->field("r.EvaluateResult,r.id,r.EvaluateDate,s.id as section_id")->order("s.id asc")->select();

                if ($temp_data) {
                    $section_form_data[] = $temp_data;
                } else {
                    $section_form_data[] = [];
                }
            }
        }


        //定义一个空的数组
        //验评结果：0未验评2合格，3优良
        $form_result_result = array();

        foreach ($section_form_data as $qq => $rr) {

            $count = array_count_values(array_column($rr, "EvaluateResult"));//统计优良、合格

            if (isset($count["3"]) && isset($count["2"])) {

                $count["3"] = $count["3"] ? $count["3"] : 0;
                $count["2"] = $count["2"] ? $count["2"] : 0;

                //计算优良率，合格率
                $form_result_result[$qq] = round($count["3"] / ($count["3"] + $count["2"]) * 100);//优良率

            } else if (!isset($count["3"]) && isset($count["2"])) {

                $count["2"] = $count["2"] ? $count["2"] : 0;


                //计算优良率，合格率
                $form_result_result[$qq] = 0;//优良率

            } else if (isset($count["3"]) && !isset($count["2"])) {

                $count["3"] = $count["3"] ? $count["3"] : 0;

                //计算优良率，合格率
                $form_result_result[$qq] = round($count["3"] / ($count["3"] + 0) * 100);//优良率

            } else {

                //计算优良率，合格率不合格率
                $form_result_result[$qq] = 0;//优良率

            }
        }

        $result = ["section" => $section_name, "form_result_result" => $form_result_result];//柱状图表格

        return json(["code" => 1, "data" => $result]);
    }
}