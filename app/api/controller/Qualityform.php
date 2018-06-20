<?php
/**
 * Created by PhpStorm.
 * User: 98113
 * Date: 2018/5/28
 * Time: 14:25
 */
namespace app\api\controller;

use app\admin\controller\Permissions;
use app\api\controller\Login;
use think\Db;
use think\Session;
use think\Controller;
use think\Request;
use app\quality\model\QualityFormInfoModel;


class Qualityform extends Permissions
{
    //获取某个表单的信息
    public function getFormInfo()
    {
        $param=input('param.');
        if(isset($param['form_id'])&&$param['form_id']!="")
        {
            $form_id = $param['form_id'];
            $info = Db::name('quality_form_info')
                ->where(['id' => $form_id])
                ->find();
            //工序
            $procedure = Db::name('norm_materialtrackingdivision')
                ->where('id', $info['ProcedureId'])
                ->value('name');

            //表头信息
            $qualityModel= new QualityFormInfoModel();
            $output=$qualityModel->getFormBaseInfo($info['DivisionId']);
            $baseData['title']=$info['form_name'];//表单名称
            $baseData['sectionName']=$output['SectionName'];//标段名称
            $baseData['dwName']=$output['DWName'].$output['DWCode'];//单位名称
            $baseData['fbName']=$output['FBName'].$output['FBCode'];//分部名称
            $baseData['dyName']=$output['DYName'].$output['DYCode'];//单元名称
            $baseData['unitId']=$info['DivisionId'];//单元工程段号
            $baseData['procedureName']=$info['DivisionId'];//工序
            $form_data = unserialize($info['form_data']);
            return json(['basedata'=>$baseData,'form_data'=>$form_data]);

        }


    }












}