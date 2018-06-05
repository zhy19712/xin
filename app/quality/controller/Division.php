<?php
/**
 * Created by PhpStorm.
 * User: zhifang
 * Date: 2018/3/27
 * Time: 16:16
 */

namespace app\quality\controller;

use app\admin\controller\Permissions;
use app\quality\model\DivisionControlPointModel;
use app\quality\model\DivisionModel;
use app\quality\model\DivisionUnitModel;
use app\quality\model\PictureModel;
use app\quality\model\PictureRelationModel;
use app\standard\model\ControlPoint;
use think\Db;
use think\Loader;
/**
 * 工程划分
 * Class Division
 * @package app\quality\controller
 */
class Division extends Permissions{

    /**
     * 初始化左侧树节点
     * @return mixed|\think\response\Json
     * @author hutao
     */
    public function index()
    {
        if(request()->isAjax()){
            $node = new DivisionModel();
            $nodeStr = $node->getNodeInfo();
            return json($nodeStr);
        }
        return $this->fetch();
    }

    /**
     * 获取工程分类
     * @return string
     * @author hutao
     */
    public function getEnType()
    {
        if(request()->isAjax()){
            $division = new DivisionModel();
            $node = $division->getEnType();
            return json($node);
        }
    }

    /**
     * 获取系统编码
     * @return \think\response\Json
     * @author hutao
     */
    public function getCodeing()
    {
        $add_id = $this->request->has('add_id') ? $this->request->param('add_id', 0, 'intval') : 0;
        if($add_id){
            $node = new DivisionModel();
            $data = $node->getOne($add_id);
            $code_arr = Db::name('quality_unit')->where('division_id',$add_id)->column('coding');
            if(sizeof($code_arr)){
                foreach ($code_arr as $k=>$v){
                    $arr = explode('-',$v);
                    $num_arr[] = end($arr)+1;
                }
                $num = max($num_arr);
            }else{
                $num = 1;
            }
            if($num >= 10 && $num < 100){
                $new_num = '0'.$num;
            }else if($num < 10){
                $new_num = '00'.$num;
            }else{
                $new_num = $num;
            }
            $codeing = $data['d_code'] . '-' . $new_num;
            return json(['code' => 1,'codeing' => $codeing,'en_type'=>$data['en_type'],'msg' => '系统编码']);
        }else{
            return json(['code' => -1,'msg' => '编号有误']);
        }
    }


    /**
     * GET 提交方式 :  获取一条节点数据
     * POST 提交方式 : 新增 或者 编辑 工程划分的节点
     * @return mixed|\think\response\Json
     * @author hutao
     */
    public function editNode()
    {
        if(request()->isAjax()){
            $node = new DivisionModel();
            $param = input('param.');
            $add_id = $this->request->has('add_id') ? $this->request->param('add_id', 0, 'intval') : 0;
            $edit_id = $this->request->has('edit_id') ? $this->request->param('edit_id', 0, 'intval') : 0;
            $type = $this->request->has('type') ? $this->request->param('type', 0, 'intval') : 0;
            $en_type = isset($param['en_type']) ? $param['en_type'] : '';
            if(request()->isGet()){
                $data = $node->getOne($edit_id);
                $data['en_type_name'] = '';
                // 工程分类名称
                if(!empty($data['en_type'])){
                    $data['en_type_name'] = Db::name('norm_materialtrackingdivision')->where('id',$data['en_type'])->value('name');
                }
                return json($data);
            }

            // 验证规则
            $rule = [
                ['section_id', 'require|number|gt:0', '请选择标段|标段只能是数字|请选择标段'],
                ['add_id', 'number', 'pid只能是数字'],
                ['edit_id', 'number', 'pid只能是数字'],
                ['d_code', 'require|alphaDash', '编码不能为空|编码只能是字母、数字、下划线 _和破折号 - 的组合'],
                ['d_name', 'require|max:100', '名称不能为空|名称不能超过100个字符'],
                ['type', 'require|number|gt:0', '请选择分类|分类只能是数字|请选择分类'],
                ['primary', 'number|egt:0', '请选择是否是主要工程|是否是主要工程只能是数字|请选择是否是主要工程']
            ];
            // 分类 1单位,2子单位工程,3分部,4子分部工程,5分项工程,6单元工程
            if($type < 4 && empty($en_type)){
                $validate = new \think\Validate($rule);
            }else{
                array_push($rule,['en_type', 'require|number', '请选择工程分类|工程类型只能是数字']);
                $validate = new \think\Validate($rule);
            }
            //验证部分数据合法性
            if (!$validate->check($param)) {
                return json(['code' => -1,'msg' => $validate->getError()]);
            }

            /**
             * 节点 层级
             *
             * 顶级节点 -》标段  不允许 增删改,它们是从其他表格获取的
             *
             * 顶级节点 -》标段 下面 新增的是 -》单位工程 type = 1 , d_code 是自定义的
             *                                   单位工程 下面 新增的是 =》 子单位工程 type = 2 , d_code 前一部分 继承父级节点的编码,后面拼接自己的编码
             *                                                           =》 分部工程  type = 3 , d_code 前一部分 继承父级节点的编码,后面拼接自己的编码
             *                                                               分部工程 下面 新增的是 -》 子分部工程 type = 4 ,d_code 前一部分 继承父级节点的编码,后面拼接自己的编码
             *                                                                                      -》 分项工程   type = 5 ,d_code 前一部分 继承父级节点的编码,后面拼接自己的编码
             *                                                                                      -》 单元工程   type = 6 ,d_code 前一部分 继承父级节点的编码,后面拼接自己的编码
             *                                                                                           ——》 这三项 可以 新建 单元工程段号(单元划分) (也就是右侧的table列表)
             *
             *          注意:如果分部工程直接新建 -》 单元工程段号(单元划分) 的时候,  也必须 选择 工程分类
             *               ( 这个可以前台判断 type = 3 时新增 单元工程段号(单元划分) 提示 请先给分部工程选择 工程分类 )
             *               ( 然后再在后台判断 type = 3 时新增 单元工程段号(单元划分) 提示 请先给分部工程选择 工程分类 或者 继续保存 分部工程的工程分类默认与当前单元工程段号(单元划分) 一致 )
             *
             * 当新增 单位工程 , 子单位工程 ,分部工程  的时候
             * 前台需要传递的是 section_id 标段编号 ,d_code 编码,d_name 名称,type 分类,primary 是否主要工程,remark 描述
             * 编辑 的时候 一定要 传递 edit_id 编号
             *
             * 当新增 子分部工程,分项工程 和 单元工程 的时候 必须 选择 工程分类
             * 前台需要传递的是 section_id 标段编号 ,d_code 编码,d_name 名称,type 分类,en_type 工程分类,primary 是否主要工程,remark 描述
             * 编辑 的时候 一定要 传递 edit_id 编号
             *
             */
            $data = ['section_id' => $param['section_id'],'d_code' => $param['d_code'],'d_name' => $param['d_name'],'type' => $param['type'],'primary' => $param['primary'],'remark' => $param['remark']];
            if(!empty($add_id)){
                $data['pid'] = $add_id; // 新增的时候，把当前节点的编号作为子节点的pid
            }
            if($param['type'] == 1){
                $data['pid'] = 0; // 单位工程的pid 默认是0 [前台的树节点是后台拼接起来的]
            }
            if($type > 3){
                if(empty($en_type)){
                    return json(['code' => -1,'msg' => '请选择工程分类']);
                }
                $data['en_type'] = $en_type; // 子分部工程,分项工程 和 单元工程 必须选择 工程分类
            }

            if($type == 3 && !empty($en_type)){
                $data['en_type'] = $en_type; // 如果分部工程直接新建 -》 单元工程段号(单元划分) 的时候,  也可以 选择 工程分类
            }

            if(!empty($add_id) && !empty($edit_id)){
                return json(['code' => -1,'msg' => '无法辨别是新增或编辑']);
            }

            if(!empty($add_id)){
                // 在同一个标段下 编码 和 名称 必须 是 唯一的
                $is_unique_code = Db::name('quality_division')->where([ 'd_code' => $param['d_code'],'section_id' => $param['section_id'] ])->value('id');
                if(!empty($is_unique_code)){
                    return json(['code' => -1,'msg' => '编码已存在']);
                }
                $is_unique_name = Db::name('quality_division')->where([ 'd_name' => $param['d_name'],'section_id' => $param['section_id'] ])->value('id');
                if(!empty($is_unique_name)){
                    return json(['code' => -1,'msg' => '名称已存在']);
                }
                $flag = $node->insertTb($data);
                return json($flag);
            }else{
                $data['id'] = $edit_id;
                // 在同一个标段下 编码 和 名称 必须 是 唯一的
                $is_unique_code = Db::name('quality_division')->where([ 'id'=>['neq',$edit_id],'d_code' => $param['d_code'],'section_id' => $param['section_id'] ])->value('id');
                if(!empty($is_unique_code)){
                    return json(['code' => -1,'msg' => '编码已存在']);
                }
                $is_unique_name = Db::name('quality_division')->where([ 'id' => ['neq',$edit_id],'d_name' => $param['d_name'],'section_id' => $param['section_id'] ])->value('id');
                if(!empty($is_unique_name)){
                    return json(['code' => -1,'msg' => '名称已存在']);
                }
                $flag = $node->editTb($data);
                return json($flag);
            }
        }
    }

    /**
     * 删除 工程划分的节点
     * @return \think\response\Json
     * @author hutao
     */
    public function delNode()
    {
        // 前台只需要给我传递 要删除的 节点的 id 编号
        $id = $this->request->has('id') ? $this->request->param('id', 0, 'intval') : 0;
        if($id != 0){
            $node = new DivisionModel();
            // 是否包含子节点
            $exist = $node->isParent($id);
            if(!empty($exist)){
                return json(['code' => -1,'msg' => '包含子节点,不能删除']);
            }

            // 关联删除 此 工程划分的节点 与 控制点的关联记录
            $con = new DivisionControlPointModel();
            $con->delRelation($id,'0');

            // 批量删除 包含的 单元工程段号(单元划分) 与 模型图的关联记录
            $idArr = Db::name('quality_unit')->where('division_id',$id)->column('id');
            if(sizeof($idArr)){
                $picture = new PictureRelationModel();
                $picture->deleteRelation($idArr);
                // 批量删除 包含的 单元工程段号(单元划分)
                $unit = new DivisionUnitModel();
                $unit->batchDel($id);
            }

            // 最后删除此节点
            $flag = $node->deleteTb($id);
            return json($flag);
        }else{
            return json(['code' => 0,'msg' => '编号有误']);
        }
    }

    /**
     * 获取路径
     * @return \think\response\Json
     * @author hutao
     */
    public function getParents()
    {
        /**
         * 前台就传递 当前点击的节点的 id 编号
         */
        if(request()->isAjax()){
            $id = $this->request->has('id') ? $this->request->param('id', 0, 'intval') : 0;
            if($id == 0){ return json(['code' => 0,'path' => '','msg' => '编号有误']); }
            $node = new DivisionModel();
            $section_id = $path =  '';
            while($id>0)
            {
                $data = $node->getOne($id);
                $path = $data['d_name'] . ">>" . $path;
                $id = $data['pid'];
                $section_id = $data['section_id'];
            }
            $path = "丰宁抽水蓄能电站>>" .Db::name('section')->where('id',$section_id)->value('name') . ">>" . $path;
            return json(['code' => 1,'path' => substr($path, 0 , -2),'msg' => "success"]);
        }
    }


    /**
     * 模板下载
     * @return \think\response\Json
     * @author hutao
     */
    public function excelDownload()
    {
        $filePath = './static/division/division.xlsx';
        if(!file_exists($filePath)){
            return json(['code' => '-1','msg' => '文件不存在']);
        }else if(request()->isAjax()){
            return json(['code' => 1]); // 文件存在，告诉前台可以执行下载
        }else{
            $fileName = '工程划分导入模板.xlsx';
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
     * 导入工程划分的节点
     * @return \think\response\Json
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author hutao
     */
    public function importExcel(){
        $section_id = input('param.add_id');// 标段编号
        if(empty($section_id)){
            return  json(['code' => 0,'data' => '','msg' => '请选择标段']);
        }
        $file = request()->file('file');
        $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads/quality/division/import');
        if($info){
            // 调用插件PHPExcel把excel文件导入数据库
            Loader::import('PHPExcel\Classes\PHPExcel', EXTEND_PATH);
            $exclePath = $info->getSaveName();  //获取文件名
            $file_name = ROOT_PATH . 'public' . DS . 'uploads/quality/division/import' . DS . $exclePath;   //上传文件的地址
            // 当文件后缀是xlsx 或者 csv 就会报：the filename xxx is not recognised as an OLE file错误
            $extension = substr(strrchr($file_name, '.'), 1);
            if ($extension =='xlsx') {
                $objReader = new \PHPExcel_Reader_Excel2007();
                $obj_PHPExcel = $objReader->load($file_name);
            } else if ($extension =='xls') {
                $objReader = new \PHPExcel_Reader_Excel5();
                $obj_PHPExcel = $objReader->load($file_name);
            } else if ($extension=='csv') {
                $PHPReader = new \PHPExcel_Reader_CSV();
                //默认输入字符集
                $PHPReader->setInputEncoding('GBK');
                //默认的分隔符
                $PHPReader->setDelimiter(',');
                //载入文件
                $obj_PHPExcel = $PHPReader->load($file_name);
            }else{
                return  json(['code' => 0,'data' => '','msg' => '请选择正确的模板文件']);
            }
            if(!is_object($obj_PHPExcel)){
                return  json(['code' => 0,'data' => '','msg' => '请选择正确的模板文件']);
            }
            $excel_array= $obj_PHPExcel->getsheet(0)->toArray();   // 转换第一页为数组格式
            // 验证格式 ---- 去除顶部菜单名称中的空格，并根据名称所在的位置确定对应列存储什么值
            $section_index = $s_code_index =  $name_index = $code_index = $z_name_index = $z_code_index = $fname_index = $fcode_index = $z_fname_index = $z_fcode_index = $f_xname_index = $f_xcode_index = -1;
            foreach ($excel_array[0] as $k=>$v){
                $str = preg_replace('/[ ]/', '', $v);
                switch ($str){
                    case '标段名称':
                        $section_index = $k;
                        break;
                    case '标段编码':
                        $s_code_index = $k;
                        break;
                    case '单位工程名称':
                        $name_index = $k;
                        break;
                    case '单位工程编码':
                        $code_index = $k;
                        break;
                    case '子单位工程名称':
                        $z_name_index = $k;
                        break;
                    case '子单位工程编码':
                        $z_code_index = $k;
                        break;
                    case '分部工程名称':
                        $fname_index = $k;
                        break;
                    case '分部工程编码':
                        $fcode_index = $k;
                        break;
                    case '子分部工程名称':
                        $z_fname_index = $k;
                        break;
                    case '子分部工程编码':
                        $z_fcode_index = $k;
                        break;
                    case '分项工程名称':
                        $f_xname_index = $k;
                        break;
                    case '分项工程编码':
                        $f_xcode_index = $k;
                        break;
                    default :
                }
            }
            if($section_index == -1 || $s_code_index == -1 || $name_index == -1 || $code_index == -1 || $z_name_index == -1 || $z_code_index == -1 || $fname_index == -1 || $fcode_index == -1 || $z_fname_index == -1 || $z_fcode_index == -1 || $f_xname_index == -1 || $f_xcode_index == -1){
                return json(['code' => 0,'data' => '','msg' => '请检查标题名称']);
            }

            // 1、名称和编码必须同时存在；2、没有子单位（子分部）工程时，保持单位（子分部）工程名称和单位（子分部）工程编码为空即可；3、同一节点下编码不能重复；
            $insert_unit_data = []; // 单位工程
            $insert_subunit_data = []; // 子单位工程名称
            $insert_parcel_data = []; // 分部工程名称
            $insert_subdivision_data = []; // 子分部工程名称
            $insert_subitem_data = []; // 单元工程名称
            $new_excel_array = $this->delArrayNull($excel_array); // 删除空数据
            $path = './uploads/quality/division/import/' . str_replace("\\","/",$exclePath);
            $pid = $zpid = 0;

            $section_code = Db::name('section')->where(['id'=>$section_id])->value('code'); // 标段编码

            // 单位工程 type = 1 子单位工程 type = 2 分部工程  type = 3 子分部工程 type = 4 分项工程 type = 5
            foreach($new_excel_array as $k=>$v){
                if($k > 1 && $section_code == $v[$s_code_index]){ // 前两行都是标题和说明
                    // 单位工程
                    $insert_unit_data['d_name'] = $v[$name_index];
                    $insert_unit_data['d_code'] = $v[$code_index];
                    $insert_unit_data['section_id'] = $section_id; // 标段编号
                    $insert_unit_data['filepath'] = $path;
                    $insert_unit_data['pid'] = '0';
                    $insert_unit_data['type'] = '1';
                    // 已经插入了，就不需要重复插入了
                    $flag = Db::name('quality_division')->where(['d_name'=>$v[$name_index],'d_code'=>$v[$code_index],'section_id'=>$section_id,'type'=>'1','pid'=>0])->find();
                    if(empty($flag) && !empty($v[$name_index])){
                        $node = new DivisionModel();
                        $flag = $node->insertTb($insert_unit_data);
                        if($flag['code'] == -1){
                            return json(['code' => 0,'data' => '','msg' => '单位工程-导入失败']);
                        }
                        $pid = $flag['data']['id'];
                    }else{
                        $pid = $flag['id'];
                    }

                    // 子单位工程名称
                    $insert_subunit_data['d_name'] = $v[$z_name_index];
                    $insert_subunit_data['d_code'] = $v[$z_code_index];
                    $insert_subunit_data['section_id'] = $section_id; // 标段编号
                    $insert_subunit_data['filepath'] = $path;
                    $insert_subunit_data['type'] = '2';
                    // 已经插入了，就不需要重复插入了
                    $flag = Db::name('quality_division')->where(['d_name'=>$v[$z_name_index],'d_code'=>$v[$z_code_index],'section_id'=>$section_id,'type'=>'2'])->find();
                    if(empty($flag) && !empty($v[$z_name_index])){
                        $insert_subunit_data['pid'] = $pid;
                        $node = new DivisionModel();
                        $flag = $node->insertTb($insert_subunit_data);
                        if($flag['code'] == -1){
                            return json(['code' => 0,'data' => '','msg' => '子单位工程-导入失败']);
                        }
                        $zpid = $flag['data']['id'];
                    }else{
                        $zpid = $flag['id'];
                    }

                    // 分部工程名称
                    $insert_parcel_data['d_name'] = $v[$fname_index];
                    $insert_parcel_data['d_code'] = $v[$fcode_index];
                    $insert_parcel_data['section_id'] = $section_id; // 标段编号
                    $insert_parcel_data['filepath'] = $path;
                    $insert_parcel_data['type'] = '3';
                    // 已经插入了，就不需要重复插入了
                    $flag = Db::name('quality_division')->where(['d_name'=>$v[$fname_index],'d_code'=>$v[$fcode_index],'section_id'=>$section_id,'type'=>'3'])->find();
                    if(empty($flag) && !empty($v[$fname_index])){
                        $insert_parcel_data['pid'] = $pid;
                        $node = new DivisionModel();
                        $flag = $node->insertTb($insert_parcel_data);
                        if($flag['code'] == -1){
                            return json(['code' => 0,'data' => '','msg' => '分部工程-导入失败']);
                        }
                        $zpid = $flag['data']['id'];
                    }else{
                        $zpid = $flag['id'];
                    }

                    // 子分部工程名称
                    $insert_subdivision_data['d_name'] = $v[$z_fname_index];
                    $insert_subdivision_data['d_code'] = $v[$z_fcode_index];
                    $insert_subdivision_data['section_id'] = $section_id; // 标段编号
                    $insert_subdivision_data['filepath'] = $path;
                    $insert_subdivision_data['type'] = '4';
                    // 已经插入了，就不需要重复插入了
                    $flag = Db::name('quality_division')->where(['d_name'=>$v[$z_fname_index],'d_code'=>$v[$z_fcode_index],'section_id'=>$section_id,'type'=>'4'])->find();
                    if(empty($flag) && !empty($v[$z_fname_index])){
                        $insert_subdivision_data['pid'] = $zpid;
                        $node = new DivisionModel();
                        $flag = $node->insertTb($insert_subdivision_data);
                        if($flag['code'] == -1){
                            return json(['code' => 0,'data' => '','msg' => '子分部工程-导入失败']);
                        }
                    }
                    // 分项工程名称
                    $insert_subitem_data['d_name'] = $v[$f_xname_index];
                    $insert_subitem_data['d_code'] = $v[$f_xcode_index];
                    $insert_subitem_data['section_id'] = $section_id; // 标段编号
                    $insert_subitem_data['filepath'] = $path;
                    $insert_subitem_data['type'] = '5';
                    // 已经插入了，就不需要重复插入了
                    $flag = Db::name('quality_division')->where(['d_name'=>$v[$f_xname_index],'d_code'=>$v[$f_xcode_index],'section_id'=>$section_id,'type'=>'5'])->find();
                    if(empty($flag) && !empty($v[$f_xname_index])){
                        $insert_subitem_data['pid'] = $zpid;
                        $node = new DivisionModel();
                        $flag = $node->insertTb($insert_subitem_data);
                        if($flag['code'] == -1){
                            return json(['code' => 0,'data' => '','msg' => '单元工程-导入失败']);
                        }
                    }
                }
            }


            // 导入成功后，关联对应的 控制点
            $division = new DivisionModel();
            $flag = $division->allRelation();
            if($flag['code'] == -1){
                return json($flag);
            }

            return  json(['code' => 1,'data' => '','msg' => '导入成功']);
        }

    }

    // 之前金寨的导入方法备份 确定不需要后可以删除
    public function importExcel_Back(){
        $section_id = input('param.add_id');// 标段编号
        if(empty($section_id)){
            return  json(['code' => 0,'data' => '','msg' => '请选择标段']);
        }
        $file = request()->file('file');
        $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads/quality/division/import');
        if($info){
            // 调用插件PHPExcel把excel文件导入数据库
            Loader::import('PHPExcel\Classes\PHPExcel', EXTEND_PATH);
            $exclePath = $info->getSaveName();  //获取文件名
            $file_name = ROOT_PATH . 'public' . DS . 'uploads/quality/division/import' . DS . $exclePath;   //上传文件的地址
            // 当文件后缀是xlsx 或者 csv 就会报：the filename xxx is not recognised as an OLE file错误
            $extension = substr(strrchr($file_name, '.'), 1);
            if ($extension =='xlsx') {
                $objReader = new \PHPExcel_Reader_Excel2007();
                $obj_PHPExcel = $objReader->load($file_name);
            } else if ($extension =='xls') {
                $objReader = new \PHPExcel_Reader_Excel5();
                $obj_PHPExcel = $objReader->load($file_name);
            } else if ($extension=='csv') {
                $PHPReader = new \PHPExcel_Reader_CSV();
                //默认输入字符集
                $PHPReader->setInputEncoding('GBK');
                //默认的分隔符
                $PHPReader->setDelimiter(',');
                //载入文件
                $obj_PHPExcel = $PHPReader->load($file_name);
            }else{
                return  json(['code' => 0,'data' => '','msg' => '请选择正确的模板文件']);
            }
            if(!is_object($obj_PHPExcel)){
                return  json(['code' => 0,'data' => '','msg' => '请选择正确的模板文件']);
            }
            $excel_array= $obj_PHPExcel->getsheet(0)->toArray();   // 转换第一页为数组格式
            // 验证格式 ---- 去除顶部菜单名称中的空格，并根据名称所在的位置确定对应列存储什么值
            $name_index = $code_index = -1;
            $z_name_index = $z_code_index = -1;
            $fname_index = $fcode_index = -1;
            $z_fname_index = $z_fcode_index = -1;
            $f_xname_index = $f_xcode_index = -1;
            foreach ($excel_array[0] as $k=>$v){
                $str = preg_replace('/[ ]/', '', $v);
                switch ($str){
                    case '单位工程名称':
                        $name_index = $k;
                        break;
                    case '单位工程编码':
                        $code_index = $k;
                        break;
                    case '子单位工程名称':
                        $z_name_index = $k;
                        break;
                    case '子单位工程编码':
                        $z_code_index = $k;
                        break;
                    case '分部工程名称':
                        $fname_index = $k;
                        break;
                    case '分部工程编码':
                        $fcode_index = $k;
                        break;
                    case '子分部工程名称':
                        $z_fname_index = $k;
                        break;
                    case '子分部工程编码':
                        $z_fcode_index = $k;
                        break;
                    case '分项工程名称':
                        $f_xname_index = $k;
                        break;
                    case '分项工程编码':
                        $f_xcode_index = $k;
                        break;
                    default :
                }
            }
            if($name_index == -1 || $code_index == -1 || $z_name_index == -1 || $z_code_index == -1 || $fname_index == -1 || $fcode_index == -1 || $z_fname_index == -1 || $z_fcode_index == -1 || $f_xname_index == -1 || $f_xcode_index == -1){
                return json(['code' => 0,'data' => '','msg' => '请检查标题名称']);
            }

            $insert_unit_data = []; // 单位工程
            $insert_subunit_data = []; // 子单位工程名称
            $insert_parcel_data = []; // 分部工程名称
            $insert_subdivision_data = []; // 子分部工程名称
            $insert_subitem_data = []; // 分项工程名称
            $new_excel_array = $this->delArrayNull($excel_array); // 删除空数据
            $path = './uploads/quality/division/import/' . str_replace("\\","/",$exclePath);
            $pid = $zpid = 0;
            // 单位工程 type = 1 子单位工程 type = 2 分部工程  type = 3 子分部工程 type = 4 分项工程   type = 5 单元工程   type = 6
            foreach($new_excel_array as $k=>$v){
                if($k > 1){ // 前两行都是标题和说明
                    // 单位工程
                    $insert_unit_data['d_name'] = $v[$name_index];
                    $insert_unit_data['d_code'] = $v[$code_index];
                    $insert_unit_data['section_id'] = $section_id; // 标段编号
                    $insert_unit_data['filepath'] = $path;
                    $insert_unit_data['pid'] = '0';
                    $insert_unit_data['type'] = '1';
                    // 已经插入了，就不需要重复插入了
                    $flag = Db::name('quality_division')->where(['d_name'=>$v[$name_index],'d_code'=>$v[$code_index],'section_id'=>$section_id,'type'=>'1'])->find();
                    if(empty($flag) && !empty($v[$name_index])){
                        $node = new DivisionModel();
                        $flag = $node->insertTb($insert_unit_data);
                        if($flag['code'] == -1){
                            return json(['code' => 0,'data' => '','msg' => '单位工程-导入失败']);
                        }
                        $pid = $flag['data']['id'];
                    }else{
                        $pid = $flag['id'];
                    }

                    // 子单位工程名称
                    $insert_subunit_data['d_name'] = $v[$z_name_index];
                    $insert_subunit_data['d_code'] = $v[$z_code_index];
                    $insert_subunit_data['section_id'] = $section_id; // 标段编号
                    $insert_subunit_data['filepath'] = $path;
                    $insert_subunit_data['type'] = '2';
                    // 已经插入了，就不需要重复插入了
                    $flag = Db::name('quality_division')->where(['d_name'=>$v[$z_name_index],'d_code'=>$v[$z_code_index],'section_id'=>$section_id,'type'=>'2'])->find();
                    if(empty($flag) && !empty($v[$z_name_index])){
                        $insert_subunit_data['pid'] = $pid;
                        $node = new DivisionModel();
                        $flag = $node->insertTb($insert_subunit_data);
                        if($flag['code'] == -1){
                            return json(['code' => 0,'data' => '','msg' => '子单位工程-导入失败']);
                        }
                        $zpid = $flag['data']['id'];
                    }else{
                        $zpid = $flag['id'];
                    }

                    // 分部工程名称
                    $insert_parcel_data['d_name'] = $v[$fname_index];
                    $insert_parcel_data['d_code'] = $v[$fcode_index];
                    $insert_parcel_data['section_id'] = $section_id; // 标段编号
                    $insert_parcel_data['filepath'] = $path;
                    $insert_parcel_data['type'] = '3';
                    // 已经插入了，就不需要重复插入了
                    $flag = Db::name('quality_division')->where(['d_name'=>$v[$fname_index],'d_code'=>$v[$fcode_index],'section_id'=>$section_id,'type'=>'3'])->find();
                    if(empty($flag) && !empty($v[$fname_index])){
                        $insert_parcel_data['pid'] = $pid;
                        $node = new DivisionModel();
                        $flag = $node->insertTb($insert_parcel_data);
                        if($flag['code'] == -1){
                            return json(['code' => 0,'data' => '','msg' => '分部工程-导入失败']);
                        }
                        $zpid = $flag['data']['id'];
                    }else{
                        $zpid = $flag['id'];
                    }

                    // 子分部工程名称
                    $insert_subdivision_data['d_name'] = $v[$z_fname_index];
                    $insert_subdivision_data['d_code'] = $v[$z_fcode_index];
                    $insert_subdivision_data['section_id'] = $section_id; // 标段编号
                    $insert_subdivision_data['filepath'] = $path;
                    $insert_subdivision_data['type'] = '4';
                    // 已经插入了，就不需要重复插入了
                    $flag = Db::name('quality_division')->where(['d_name'=>$v[$z_fname_index],'d_code'=>$v[$z_fcode_index],'section_id'=>$section_id,'type'=>'4'])->find();
                    if(empty($flag) && !empty($v[$z_fname_index])){
                        $insert_subdivision_data['pid'] = $zpid;
                        $node = new DivisionModel();
                        $flag = $node->insertTb($insert_subdivision_data);
                        if($flag['code'] == -1){
                            return json(['code' => 0,'data' => '','msg' => '子分部工程-导入失败']);
                        }
                    }
                    // 分项工程名称
                    $insert_subitem_data['d_name'] = $v[$f_xname_index];
                    $insert_subitem_data['d_code'] = $v[$f_xcode_index];
                    $insert_subitem_data['section_id'] = $section_id; // 标段编号
                    $insert_subitem_data['filepath'] = $path;
                    $insert_subitem_data['type'] = '5';
                    // 已经插入了，就不需要重复插入了
                    $flag = Db::name('quality_division')->where(['d_name'=>$v[$f_xname_index],'d_code'=>$v[$f_xcode_index],'section_id'=>$section_id,'type'=>'5'])->find();
                    if(empty($flag) && !empty($v[$f_xname_index])){
                        $insert_subitem_data['pid'] = $zpid;
                        $node = new DivisionModel();
                        $flag = $node->insertTb($insert_subitem_data);
                        if($flag['code'] == -1){
                            return json(['code' => 0,'data' => '','msg' => '分项工程-导入失败']);
                        }
                    }
                }
            }


            // 导入成功后，关联对应的 控制点
            $division = new DivisionModel();
            $flag = $division->allRelation();
            if($flag['code'] == -1){
                return json($flag);
            }

            return  json(['code' => 1,'data' => '','msg' => '导入成功']);
        }

    }

    /**
     * 清除数组中的空数组
     * @param $ar
     * @return mixed
     * @author hutao
     */
    public function delArrayNull($ar){
        foreach($ar as $k=>$v){
            $v = array_filter($v);
            if(empty($v) || is_null($v)){
                unset($ar[$k]);
            }
        }
        return $ar;
    }

    /**
     * GET 提交方式 :  获取一条 单元工程段号(单元划分) 数据
     * POST 提交方式 : 新增 或者 编辑 单元工程段号(单元划分)
     * @return \think\response\Json
     * @author hutao
     */
    public function editUnit()
    {
        if(request()->isAjax()){
            $unit = new DivisionUnitModel();
            $param = input('param.');
            $id = $this->request->has('id') ? $this->request->param('id', 0, 'intval') : 0;

            if(request()->isGet()){
                $data = $unit->getOne($id);


                // 流水号在页面里是分开的,所以这里要截取分开
                $parent_d_code = Db::name('quality_division')->where('id',$data['division_id'])->value('d_code');
                $serial_number = explode($parent_d_code.'-',$data['serial_number']);
                $data['serial_number_before'] = $parent_d_code;
                $data['serial_number'] = $serial_number[1];
                /**
                 * 工程高程（起）高程（止）  都要加上 EL.
                 * 在这个界面上 还 分开 展示
                 * 在别的地方就是连在一起的
                 */
                if(!empty($data['el_start'])){
                    $el_start = explode('EL.',$data['el_start']);
                    $data['el_start'] = $el_start[1];
                }
                if(!empty($data['el_cease'])){
                    $el_cease = explode('EL.',$data['el_cease']);
                    $data['el_cease'] = $el_cease[1];
                }
                // 工程类型名称
                if(!empty($data['en_type'])){
                    $data['en_type_name'] = Db::name('norm_materialtrackingdivision')->where('id',$data['en_type'])->value('name');
                }
                return json($data);
            }

            // 验证规则
            $rule = [
                ['division_id', 'require|number|gt:0', '请选择归属工程|归属工程编号只能是数字|请选择归属工程'],
                ['serial_number', 'alphaDash', '流水号只能是字母、数字、下划线 _和破折号 - 的组合'],
                ['site', 'require|max:100', '部位不能为空|部位不能超过100个字符'],
                ['coding', 'require|alphaDash', '系统编码不能为空|系统编码只能是字母、数字、下划线 _和破折号 - 的组合'],
                ['ma_bases', 'require', '请选择施工依据'],
                ['hinge', 'require|number', '请选择是否是关键部位|关键部位只能是数字'],
                ['en_type', 'require|number|gt:0', '请选择工程类型|工程类型只能是数字|请选择工程类型'],
                ['start_date', 'date', '开工日期格式有误']
            ];
            $validate = new \think\Validate($rule);
            //验证部分数据合法性
            if (!$validate->check($param)) {
                return json(['code' => -1,'msg' => $validate->getError()]);
            }

            /**
             * type= 2 时新增 单元工程段号(单元划分)
             * 提示 请先给分部工程选择 工程分类 或者 继续保存 分部工程的工程分类默认与当前单元工程段号(单元划分) 一致
             */
            $type = Db::name('quality_division')->where('id',$param['division_id'])->value('type');
            $again_save = isset($param['again_save']) ? $param['again_save'] : '';
            if($type == 2 && $again_save != 'again_save'){
                return json(['code' => -1,'msg' => '继续保存： 当前所选的分部工程的工程分类,将默认与当前单元工程段号(单元划分) 一致']);
            }

            /**
             * 单元工程段号(单元划分) (注意 这是归属于所选工程下的 多条数据信息 它不是节点)
             *
             * 当新增 单元工程段号(单元划分) 的时候
             * 前台需要传递的是
             * 必传参数 : division_id 归属的工程编号 serial_number 单元工程段号(单元划分)流水号,
             *            site 单元工程段号(单元划分)部位,coding 系统编码,ma_bases 施工依据(注意这里是可以多选的，选中的编号以下划线连接 例如：1_2_3_4_5 ),
             *            hinge 关键部位(1 是 0 否),en_type 工程类型 (如果 type=2 还需要 传递 again_save 继续保存 值就等于 again_save)
             *
             * 可选参数 : su_basis 补充依据,el_start 高程（起）,el_cease 高程（止）,quantities 工程量,pile_number 起止桩号,start_date 开工日期,completion_date 完工日期
             *
             * 编辑 的时候 一定要 传递 id 编号
             *
             */

            /**
             * 工程高程（起）高程（止）  都要加上 EL.
             * 在这个界面上 还 分开 展示
             * 在别的地方就是连在一起的
             */
            $param['el_start'] = 'EL.' . $param['el_start'];
            $param['el_cease'] = 'EL.' . $param['el_cease'];


            if(empty($id)){
                // 同一个归属工程下的 单元工程段号(单元划分)流水号 和 系统编码 必须 是 唯一的
                $is_unique_number = Db::name('quality_unit')->where([ 'serial_number' => $param['serial_number'],'division_id' => $param['division_id'] ])->value('id');
                if(!empty($is_unique_number)){
                    return json(['code' => -1,'msg' => '流水号已存在']);
                }
                $is_unique_code = Db::name('quality_unit')->where([ 'coding' => $param['coding'],'division_id' => $param['division_id'] ])->value('id');
                if(!empty($is_unique_code)){
                    return json(['code' => -1,'msg' => '系统编码已存在']);
                }
                $flag = $unit->insertTb($param);
                return json($flag);
            }else{
                // 同一个归属工程下的 单元工程段号(单元划分)流水号 和 系统编码 必须 是 唯一的
                $is_unique_number = Db::name('quality_unit')->where([ 'id' => ['neq',$param['id']],'serial_number' => $param['serial_number'],'division_id' => $param['division_id'] ])->value('id');
                if(!empty($is_unique_number)){
                    return json(['code' => -1,'msg' => '流水号已存在']);
                }
                $is_unique_code = Db::name('quality_unit')->where(['id' => ['neq',$param['id']],'coding' => $param['coding'],'division_id' => $param['division_id'] ])->value('id');
                if(!empty($is_unique_code)){
                    return json(['code' => -1,'msg' => '系统编码已存在']);
                }
                $flag = $unit->editTb($param);
                return json($flag);
            }
        }
    }

    //获取施工依据的信息
    public function getMabases()
    {
        $unit = new DivisionUnitModel();
        $param = input('param.');
        $id = $param['unit_id'];
        $data = $unit->getOne($id);
        //从图纸表里拉取数据
        $atlas_id=$data['ma_bases'];
        $atlas_id=explode(',',$atlas_id);
        foreach ($atlas_id as $id)
        {
            $atlas= Db::name('archive_atlas_cate ')
                ->where('id',$id)
                ->find();
            //拼接信息：图名+图号
            $bases[]=$atlas['picture_name'].$atlas['picture_number'];
        }
        $ma_bases_name=implode(',',$bases);//取出图纸信息并转为字符串
        return json(['code'=>1,'msg'=>'success','data'=>$data]);

    }


    /**
     * 删除 单元工程段号(单元划分)
     * @return \think\response\Json
     * @author hutao
     */
    public function delUnit()
    {
        // 前台只需要给我传递 要删除的 单元工程段号(单元划分) 的 id 编号
        $id = $this->request->has('id') ? $this->request->param('id', 0, 'intval') : 0;
        if($id != 0){
            // 关联删除 此 单元工程段号 与 控制点的关联记录
            $con = new DivisionControlPointModel();
            $con->delRelation($id,'1');

            // 关联删除 此 单元工程段号 与 模型图的关联记录
            $picture = new PictureRelationModel();
            $picture->deleteRelation([$id]);
            $unit = new DivisionUnitModel();
            $flag = $unit->deleteTb($id);
            return json($flag);
        }else{
            return json(['code' => 0,'msg' => '编号有误']);
        }
    }

    /**
     * 二维码
     * @return \think\response\Json|void
     * @author hutao
     */
    public function qrCode()
    {
        $id = $this->request->has('id') ? $this->request->param('id', 0, 'intval') : 0;
        if($this->request->isAjax()){
            return json(['code' => 1,'name' => '','msg' => '二维码']);
        }
        $text = url('quality/division/editUnit/'.$id);
        Loader::import('phpqrcode\phpqrcode', EXTEND_PATH);
        $enc = \QRencode::factory('L', 5, 4);
        return $enc->encodePNG($text, false, false);
    }


    /**
     * 获取 左侧工程划分下 所有的模型图编号
     * 点击 工程划分 获取该 节点 包含的所有单元工程段号(单元划分) 对应的模型图编号 一对多
     * 这里展示的是 完整的模型图
     * @return \think\response\Json
     * @author hutao
     */
    public function modelPictureAllNumber()
    {
        // 前台 传递 选中的 工程划分 编号 add_id
        if($this->request->isAjax()){
            $param = input('param.');
            $add_id = isset($param['add_id']) ? $param['add_id'] : -1;
            if($add_id == -1){
                return json(['code' => 0,'msg' => '编号有误']);
            }
            $id = Db::name('quality_unit')->where('division_id',$add_id)->column('id');
            // 获取关联的模型图
            $picture = new PictureRelationModel();
            $data= $picture->getAllNumber($id);
            $picture_number = $data['picture_number_arr'];
            return json(['code'=>1,'numberArr'=>$picture_number,'msg'=>'工程划分-模型图编号']);
        }
    }

    /**
     * 点击 单元工程段号(单元划分) 展示关联的模型图 一对一关联
     * 这里展示的是部分模型图
     * @return \think\response\Json
     * @author hutao
     */
    public function modelPicturePreview()
    {
        // 前台 传递 选中的 单元工程段号 编号 id
        if($this->request->isAjax()){
            $param = input('param.');
            $id = isset($param['id']) ? $param['id'] : -1;
            if($id == -1){
                return json(['code' => 0,'msg' => '编号有误']);
            }
            // 获取关联的模型图
            $picture = new PictureRelationModel();
            $data = $picture->getAllNumber([$id]);
            $picture_number = $data['picture_number_arr'];
            return json(['code'=>1,'number'=>$picture_number,'msg'=>'单元工程段号-模型图编号']);
        }
    }

    /**
     * 打开关联模型 页面 openModelPicture
     * @return mixed|\think\response\Json
     * @author hutao
     */
    public function openModelPicture()
    {
        // 前台 传递 选中的 单元工程段号的 id编号
        if($this->request->isAjax()){
            $param = input('param.');
            $id = isset($param['id']) ? $param['id'] : -1;
            if($id == -1){
                return json(['code' => 0,'msg' => '编号有误']);
            }
            // 获取工程划分下的 所有的模型图主键,编号,名称
            $picture = new PictureModel();
            $data = $picture->getAllName($id);
            return json(['code'=>1,'one_picture_id'=>$data['one_picture_id'],'data'=>$data['str'],'msg'=>'模型图列表']);
        }
        return $this->fetch('relationview');
    }

    /**
     * 关联模型图
     * @return \think\response\Json
     * @author hutao
     */
    public function addModelPicture()
    {
        // 前台 传递 单元工程段号(单元划分) 编号id  和  模型图主键编号 picture_id
        if($this->request->isAjax()){
            $param = input('param.');
            $relevance_id = isset($param['id']) ? $param['id'] : -1;
            $picture_id = isset($param['picture_id']) ? $param['picture_id'] : -1;
            if($relevance_id == -1 || $picture_id == -1){
                return json(['code' => 0,'msg' => '参数有误']);
            }
            // 是否已经关联过 picture_type  1工程划分模型 2 建筑模型 3三D模型
            $is_related = Db::name('quality_model_picture_relation')->where(['type'=>1,'relevance_id'=>$relevance_id])->value('id');
            $data['type'] = 1;
            $data['relevance_id'] = $relevance_id;
            $data['picture_id'] = $picture_id;
            $picture = new PictureRelationModel();
            if(empty($is_related)){
                // 关联模型图 一对一关联
                $flag = $picture->insertTb($data);
                return json($flag);
            }else{
                $data['id'] = $is_related;
                $flag = $picture->editTb($data);
                return json($flag);
            }
        }
    }

    // 此方法只是临时 导入模型图 编号和名称的 txt文件时使用
    // 不存在于 功能列表里面 后期可以删除掉
    // 获取txt文件内容并插入到数据库中 insertTxtContent
    public function insertTxtContent()
    {
        $filePath = './static/division/GolIdTable.txt';
        if(!file_exists($filePath)){
            return json(['code' => '-1','msg' => '文件不存在']);
        }
        $files = fopen($filePath, "r") or die("Unable to open file!");
        $contents = $new_contents =[];
        while(!feof($files)) {
            $txt = iconv('gb2312','utf-8//IGNORE',fgets($files));
            $txt = str_replace('[','',$txt);
            $txt = str_replace(']','',$txt);
            $txt = str_replace("\r\n",'',$txt);
            $contents[] = $txt;
        }

        foreach ($contents as $v){
            $new_contents[] = explode(' ',$v);
        }

        $data = [];
        foreach ($new_contents as $k=>$val){
            $data[$k]['picture_name'] = trim(next($val));
            $data[$k]['picture_number'] = trim(next($val));
        }

        array_pop($data);

        $picture = new PictureModel();
        $picture->saveAll($data); // 使用saveAll 是因为 要 自动插入 时间
        fclose($files);
    }


    // 此方法只是临时 用来关联 所有的 工程划分节点 与 控制点表的 对应关系
    // 对应关系不存在的 新增一条
    public function addProjectRelation()
    {
        $division = new DivisionModel();
        $flag = $division->allRelation();
        return json($flag);
    }

    /**
     * 搜索模型
     * @return \think\response\Json
     * @author hutao
     */
    public function searchModel()
    {
        // 前台 传递  选中的 单元工程段号的 id编号  和  搜索框里的值 search_name
        if($this->request->isAjax()){
            $param = input('param.');
            $id = isset($param['id']) ? $param['id'] : -1;
            $search_name = isset($param['search_name']) ? $param['search_name'] : -1;
            if($id == -1){
                return json(['code' => 0,'msg' => '请传递选中的单元工程段号的编号']);
            }if($id == -1 || $search_name == -1){
                return json(['code' => 0,'msg' => '请写入需要搜索的值']);
            }
            // 获取搜索的模型图主键,编号,名称
            $picture = new PictureModel();
            $data = $picture->getAllName($id,$search_name);
            return json(['code'=>1,'one_picture_id'=>$data['one_picture_id'],'data'=>$data['str'],'msg'=>'模型图列表']);
        }
    }


}