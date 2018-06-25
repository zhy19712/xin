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
    public function getFormInfo($dataId,$type)
    {
      if($type==1)//如果传来的是表单id
      {
          $info = Db::name('quality_form_info')
              ->where(['id' => $dataId])
              ->find();
          //工序
          $procedure = Db::name('norm_materialtrackingdivision')
                      ->where('id', $info['ProcedureId'])
                      ->value('name');
          $cpr_id = Db::name("quality_division_controlpoint_relation")
                     ->where (["control_id"=>$info['ControlPointId'],'division_id'=>$info['DivisionId'],'type'=>1])
                     ->value("id");

          //表头信息
          $qualityModel = new QualityFormInfoModel();
          $output = $qualityModel->getFormBaseInfo($info['DivisionId']);
          $baseData['title'] = $info['form_name'];//表单名称
          $baseData['sectionName'] = $output['SectionName'];//标段名称
          $baseData['dwName'] = $output['DWName'] . $output['DWCode'];//单位名称
          $baseData['fbName'] = $output['FBName'] . $output['FBCode'];//分部名称
          $baseData['dyName'] = $output['DYName'] . $output['DYCode'];//单元名称
          $baseData['unitId'] = $info['DivisionId'];//单元工程段号
          $baseData['cpr_id'] = $info['cpr_id'];
          $baseData['procedureName'] = $procedure;//工序
          $form_data = unserialize($info['form_data']);
          return json(['basedata' => $baseData, 'form_data' => $form_data]);
      }
      else if($type==2)//如果传来的是cpr_id
      {
            $norm_template=Db::name('norm_template')->alias('t')
                ->join('norm_controlpoint c', 't.id = c.qualitytemplateid', 'left')
                ->join('quality_division_controlpoint_relation r', 'r.control_id = c.id', 'left')
                ->where(['r.id'=>$dataId])
                ->find();

          $template=Db::name('quality_form_info')
              ->where(['DivisionId'=>$norm_template['division_id'],'ControlPointId'=>$norm_template['control_id'],'ApproveStatus'=>2])
              ->find();
          $id= $template['id'];
          $qualitytemplateid = $norm_template['qualitytemplateid'];
          if ($qualitytemplateid == 0) {
              return  '控制点未进行模板关联!';
          }
          $template_name=Db::name('norm_template')
              ->where('id',$qualitytemplateid)
              ->value('name');

            $relation=Db::name("quality_division_controlpoint_relation")
                      ->where('id',$dataId)
                      ->find();
            $procedure=Db::name('norm_controlpoint')
                       ->where("id",$relation['control_id'])
                       ->value('procedureid');
            //工序
            $procedure = Db::name('norm_materialtrackingdivision')
                ->where('id', $procedure)
                ->value('name');

            //表头信息
            $qualityModel = new QualityFormInfoModel();
            $output = $qualityModel->getFormBaseInfo($relation['division_id']);
            $baseData['title'] = $template_name;//表单名称
            $baseData['sectionName'] = $output['SectionName'];//标段名称
            $baseData['dwName'] = $output['DWName'] . $output['DWCode'];//单位名称
            $baseData['fbName'] = $output['FBName'] . $output['FBCode'];//分部名称
            $baseData['dyName'] = $output['DYName'] . $output['DYCode'];//单元名称
            $baseData['unitId'] = $relation['division_id'];
            $baseData['cpr_id'] = $dataId;//单元工程段号
            $baseData['procedureName'] = $procedure;//工序
            $form_data = "";
            return json(['basedata' => $baseData, 'form_data' => $form_data]);
      }



    }












}