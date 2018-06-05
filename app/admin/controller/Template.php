<?php
/**
 * Created by PhpStorm.
 * User: waterforest
 * Date: 2018/5/28
 * Time: 14:44
 */

namespace app\admin\controller;

use think\Db;

class Template extends Permissions
{
    public function index()
    {
        $data = Db::name('quality_form_info')->where('id',368)->find();

        $formPath = ROOT_PATH . 'public' . DS . "data". DS ."form". DS ."qualityNew". DS . "01.01.01岩石边坡开挖单元工程质量等级评定表下载.html";
        $formPath = iconv('UTF-8', 'GB2312', $formPath);

        $content = $this->fetch($formPath,[
            'id' => $data['id'],
            'templateId' => $data['TemplateId'],
            'isInspect' => $data['IsInspect'],
            'divisionId'=> $data['DivisionId'],
            'procedureId'=> $data['ProcedureId'],
            'controlPointId'=> $data['ControlPointId'],
            'formName'=> $data['form_name'],
            'currentStep'=> $data['CurrentStep'],
            'isView'=> '1',
            'formData'=> $data['form_data'],
            'SectionName' => '查询对应数据表获取标段编号',
            'ContractCode' => '查询对应合同编号',
            'JYPCode' => '查询编号',
            'DWName' => '艾斯德斯多',
            'DWCode' => '1232',
            'FBName' => '213213',
            'FBCode'=> '213213',
            'PileNo'=> '213213',
            'Altitude'=> '213213',
            'DYName'=> '213213',
            'JYPName'=> '213213',
            'DYCode'=> '213213',
            'Constructor'=> 'sds',
            'start'=>'',
            'end'=>'',
            'img_src' => ROOT_PATH . 'public' . DS . '/static/website/common/images/3D1.png',
        ]);

      //  $content = iconv('UTF-8', 'GB2312', $content);

        $filename = ROOT_PATH . 'public' . DS . "data\\form\\temp\\" . "1.html";
               file_put_contents($filename, $content);


       shell_exec("wkhtmltopdf $filename $filename.pdf");
    }

}