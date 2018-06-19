<?php
/**
 * Created by PhpStorm.
 * User: sir
 * Date: 2018/5/22
 * Time: 11:57
 */

namespace app\modelmanagement\controller;


use app\admin\controller\Permissions;
use app\modelmanagement\model\CompleteModel;
use app\modelmanagement\model\ConfigureModel;
use app\modelmanagement\model\QualityCustomAttributeModel;
use app\modelmanagement\model\QualitymassModel;
use app\modelmanagement\model\VersionsModel;
use app\quality\model\DivisionModel;
use app\quality\model\DivisionUnitModel;
use think\Db;

/**
 * 质量模型
 * Class qualitymass
 * @package app\modelmanagement\controller
 * @author hutao
 */
class Qualitymass extends Permissions
{

    /**
     * 顶部 -- 质量3D
     * 模型管理 -- 质量模型关联
     * 公用 --  左侧的树
     * @return mixed|\think\response\Json
     * @author hutao
     */
    public function index()
    {
        if(request()->isAjax()){
            // 第一次进来 什么也不传递 默认查询全部
            // 传递 node_type 1 已关联节点 2 未关联节点
            // 如果 传递了 section_id 标段编号 只查询该标段下的节点
            $param = input('get.');
            $node_type = isset($param['node_type']) ? $param['node_type'] : -1;
            $section_id = isset($param['section_id']) ? $param['section_id'] : -1;
            $node = new DivisionModel();
            $nodeStr = $node->getQualityNodeInfo($node_type,$section_id);
            return json($nodeStr);
        }
       return $this->fetch();
    }

    /**
     * 切换标段的时候，返回该标段下所有的模型编号[不只是有关联关系的模型]
     * @return \think\response\Json
     * @author hutao
     */
    public function sectionChange()
    {
        if($this->request->isAjax()){
            // 前台 传递 选中标段的编号 section_id
            $param = input('post.');
            $section_id = isset($param['section_id']) ? $param['section_id'] : -1;
            $quality = new QualitymassModel();
            $data = $quality->sectionChange($section_id);
            return json(['code'=>1,'data'=>$data,'msg'=>'切换标段的时候,返回该标段下所有的模型编号[不只是有关联关系的模型]']);
        }
    }

    /**
     * 标段下拉数据
     * @return \think\response\Json
     * @author hutao
     */
    public function section()
    {
        if(request()->isAjax()){
            $data = Db::name('section')->column('id,name');
            return json(['code'=>1,'data'=>$data,'msg'=>'标段下拉数据']);
        }
    }

    /**
     * 起止高程和桩号的值
     * @return \think\response\Json
     * @author hutao
     */
    public function elVal()
    {
        if(request()->isAjax()){
            // 传递 单元工程的 add_id
            $id = input('add_id');
            if(empty($id)){
                return json(['code'=>-1,'msg'=>'缺少单元工程的编号']);
            }
            $node = new DivisionUnitModel();
            $data = $node->getEl($id);
            return json(['code'=>1,'el_val'=>$data['el_val'],'pile_number'=>$data['pile_number'],'msg'=>'起止高程和桩号的值']);
        }
    }

    /**
     * 下拉列表的值
     * @return \think\response\Json
     * @author hutao
     */
    public function dropDown()
    {
        // 前台传递 的 $type 值 代表的含义
        // section 标段 unit 单位 parcel 分部 cell 单元
        // pile_number_1 桩号1名 pile_number_2 桩号2名
        // pile_number_3 桩号3名 pile_number_4 桩号4名
        if(request()->isAjax()){
            $type = input('type');
            if(empty($type)){
                return json(['code'=>-1,'msg'=>'缺少构件类型']);
            }
            $node = new QualitymassModel();
            if($type == 'section'){
                $data = $node->getSection();$val = '标段';
            }else if($type == 'unit'){
                $data = $node->getUnit();$val = '单位';
            }else if($type == 'parcel'){
                $data = $node->getParcel();$val = '分部';
            }else if($type == 'cell'){
                $data = $node->getCell();$val = '单元';
            }else if($type == 'pile_number_1'){
                $data = $node->pile_number_1();$val = '桩号1';
            }else if($type == 'pile_number_2'){
                $data = $node->pile_number_2();$val = '桩号2';
            }else if($type == 'pile_number_3'){
                $data = $node->pile_number_3();$val = '桩号3';
            }else if($type == 'pile_number_4'){
                $data = $node->pile_number_4();$val = '桩号4';
            }else{
                return json(['code'=>1,'data'=>'','msg'=>'构件类型不存在']);
            }
            return json(['code'=>1,'data'=>$data,'msg'=>$val.'下拉列表的值']);
        }
    }

    /**
     * 选中的节点 -- 解除关联
     * @return \think\response\Json
     * @author hutao
     */
    public function removeRelevanceNode()
    {
        if(request()->isAjax()){
            // 传递 选中节点的 add_id 和 节点的类型 node_type 1 顶级节点 2 标段 3 工程划分节点 4 单元工程段号(检验批编号)
            $param = input('param.');
            $add_id = isset($param['add_id']) ? $param['add_id'] : -1;
            $node_type = isset($param['node_type']) ? $param['node_type'] : -1;
            if($add_id == -1 || $node_type == -1){
                return json(['code'=>-1,'data'=>[],'msg'=>'缺少参数']);
            }
            $node = new DivisionModel();
            $flag = $node->removeRelevanceNode($add_id,$node_type);
            return json($flag);
        }
    }

    /**
     * 关联构件
     * @return \think\response\Json
     * @author hutao
     */
    public function relevance()
    {
        if(request()->isAjax()){
            // 传递 选中节点的 add_id 和 选中的构件 编号数组 id_arr
            $param = input('param.');
            $id = isset($param['add_id']) ? $param['add_id'] : 0;
            if(empty($id)){
                return json(['code'=>-1,'msg'=>'缺少单元工程的编号']);
            }
            $id_arr = input('id_arr/a');
            if(!sizeof($id_arr)){
                return json(['code'=>-1,'msg'=>'缺少构件的编号']);
            }

            /**
             * 关联模型时，要验证，选择的检验批所属的标段和模型的标段是否一致，
             * 不一致的时候给出，标段不符提示，并取消本次关联
             */
            $check_result = false;
            $unit = new DivisionUnitModel();
            $code = $unit->sectionCode($id); // 获取单元工厂(检验批)归属的标段
            $node = new QualitymassModel();
            $code_arr = $node->sectionCode($id_arr); // 获取所有选中的构件的标段
            foreach ($code_arr as $v){
                if($code != $v){
                    $check_result = true;
                    break;
                }
            }
            if($check_result){
                return json(['code'=>-1,'msg'=>'所选择的构件中存在其他标段下的构件,与当前关联的单元工程所属标段不符,此次关联无效']);
            }

            // 执行关联操作
            $flag = $node->relevance($id,$id_arr,0); // 0 表示检验批关联 1 表示是实时进度关联 2 表示月进度关联
            return json($flag);
        }
    }

    /**
     * 选中的构件 --  解除关联
     * @return \think\response\Json
     * @author hutao
     */
    public function removeRelevance()
    {
        if(request()->isAjax()){
            // 传递 选中的构件 编号数组 id_arr
            $id_arr = input('id_arr/a');
            if(!sizeof($id_arr)){
                return json(['code'=>-1,'msg'=>'缺少构件的编号']);
            }
            $node = new QualitymassModel();
            $flag = $node->removeRelevance($id_arr,0); // 0 表示检验批关联 1 表示是实时进度关联 2 表示月进度关联
            return json($flag);
        }
    }

    /**
     * 页面第一次进来不用传递参数 默认返回所有模型
     * 根据所选标段 返回 与该标段下的所有单元工程节点有关联关系的模型编号
     * 并且 按照 [优良，合格，不合格，未验评] 分组
     * @return \think\response\Json
     * @author hutao
     */
    public function sectionModel()
    {
        if($this->request->isAjax()){
            // 第一次进来不用传递参数 默认返回所有
            // 前台 传递 选中标段的编号 section_id
            $param = input('post.');
            $section_id = isset($param['section_id']) ? $param['section_id'] : -1;
            $quality = new QualitymassModel();
            $data = $quality->sectionModelInfo($section_id);
            return json(['code'=>1,'data'=>$data,'msg'=>'质量模型: [优良，合格，不合格，未验评]']);
        }
    }

    /**
     * 点击眼睛图标显示隐藏模型
     *
     * 1 顶级节点      -- 隐藏所有模型
     * 2 标段          -- 隐藏 该标段下 的所有关联模型
     * 3 工程划分节点  -- 隐藏 该节点下 的所有关联模型
     * 4 单元工程段号(检验批编号) -- 隐藏与 该单元工程 有关联的模型
     * @return \think\response\Json
     * @author hutao
     */
    public function concealment()
    {
        if($this->request->isAjax()){
            // 前台 传递 选中眼睛节点的 add_id  和 眼睛节点的类型 node_type 1 顶级节点 2 标段 3 工程划分节点 4 单元工程段号(检验批编号)
            $param = input('post.');
            $add_id = isset($param['add_id']) ? $param['add_id'] : -100;
            $node_type = isset($param['node_type']) ? $param['node_type'] : -100;
            if(empty($add_id) || empty($node_type)){
                return json(['code'=>-1,'data'=>[],'msg'=>'缺少参数']);
            }
            $quality = new QualitymassModel();
            $data = $quality->concealment($add_id,$node_type);
            return json(['code'=>1,'data'=>$data,'msg'=>'质量模型: [显示或隐藏 -- 所有关联模型编号]']);
        }
    }

    /**
     * 当点击节点或者模型的时候 ---
     * 通过 -- 选中节点或者选中的模型编号
     * 获取 -- 所有关联模型编号 -- 模型状态[优良，合格，不合格，未验评] -- 单元工程的编号
     *
     * @return \think\response\Json
     * @author hutao
     */
    public function nodeModelNumber()
    {
        if($this->request->isAjax()){
            // 前台 传递 选中节点的 number  和 编号的类型 number_type 1 单元工程段号(检验批编号) 2 模型编号
            $param = input('post.');
            $number = isset($param['number']) ? $param['number'] : -100;
            $number_type = isset($param['number_type']) ? $param['number_type'] : -100;
            if($number == -100 || empty($number_type)){
                return json(['code'=>-1,'data'=>[],'msg'=>'缺少参数']);
            }
            $quality = new QualitymassModel();
            $data = $quality->qualityNodeInfo($number,$number_type);
            return json(['code'=>1,'data'=>$data,'msg'=>'质量模型: [所有关联模型编号,模型状态,单元工程的编号]']);
        }
    }

    // ============================   着急先把方法放到这里 后期有时间再转移

    // ht 前台根据资源包名称 显示该包下的模型
    public function resourcePagName()
    {
        if($this->request->isAjax()){
            // 模型类型 model_type 1 全景3D模型(竣工模型) 2 质量模型(施工模型)
            $param= input('post.');
            $model_type = isset($param['model_type']) ? $param['model_type'] : 0;
            if(empty($model_type)){
                return json(['code'=>-1,'msg'=>'缺少参数']);
            }

            $version = new VersionsModel();
            $pag_name = $version->getPagName($model_type);

            // 最新版本号
            $filePath = './uploads/3Dapp/version.json';
            if(!file_exists($filePath)){
                return ['code' => '-1','msg' => '最新版本号文件不存在'];
            }
            $files = file_get_contents($filePath);
            $txt = str_replace("{",'',$files);
            $txt = str_replace("}",'',$txt);
            $txt = str_replace(array("\r\n", "\r", "\n"),'',$txt);
            $txt = str_replace(" ",'',$txt);
            $txt = explode(':',$txt);
            $new_version_number = $txt[1];
            return json(['code'=>1,'pag_name'=>$pag_name,'new_version_number'=>$new_version_number,'msg'=>'资源包名称和最新版本号']);
        }
    }

    // ht 模型效果配置信息
    public function configureInfo()
    {
        if($this->request->isAjax()){
            $version = new ConfigureModel();
            $configureInfo = $version->getConfigure(1);// 1 全景3D模型 和 质量3D模型 2 进度模拟 和 进度对比 3 实时进度展示
            return json(['code'=>1,'configureInfo'=>$configureInfo,'msg'=>'模型效果配置信息']);
        }
    }

    // ht 顶部 --  优良，合格，不合格 个数和百分率
    public function examineFruit()
    {
        if($this->request->isAjax()){
            // 前台传递的参数是 标段编号 section_id 默认是0 表示全部
            // 这里的 优良|合格|未验评 是统计该标段下的 检验批的验评结果，和模型是否关联无关
            $param = input('param.');
            $section_id = isset($param['section_id']) ? $param['section_id'] : 0;
            $unit = new DivisionUnitModel();
            $data = $unit->examineFruit($section_id);
            return json(['code'=>1,'data'=>$data,''=>'[优良|合格|未验评]的个数和百分率']);
        }
    }

    // ht 顶部 --  全景模型 点击模型 获取模型属性
    public function attributeArr()
    {
        if($this->request->isAjax()){
            // 前台 传递 选中模型的 编号 uObjSubIDArr
            $model_number_arr = input('uObjSubIDArr/a');
            if(!sizeof($model_number_arr)){
                return json(['code'=>-1,'msg'=>'缺少参数']);
            }

            /**
             * 需求说明 ---
             * 当数组里 只有 一个值的时候
             *          返回 属于该分组的所有模型编号 并且返回该组的所有属性
             *
             * 当数组里 存在多个值的时候
             *          只返回所有分组的模型编号
             */

            $comp= new CompleteModel();
            $data = $comp->attributeArr($model_number_arr);
            return json(['code'=>1,'data'=>$data,'msg'=>'成功']);
        }
    }

    // ht 顶部 -- 质量模型 -- 管理信息 -- 自定义属性
    // ht 每个版本 每个模型下 都有 不固定的 自定义属性
    public function addAttr()
    {
        /**
         * 之前的自定义属性是关联到模型图上的
         * 现在是关联到单元工程上
         *
         * 当点击单元工程 -- 显示所有的自定义属性
         * 当点击模型图时 -- 查到与该模型关联的 单元工程 再根据单元工程查自定义属性
         */

        // 新增 前台需要传递 的是  单元工程编号 add_id 属性名 attrKey  属性值 attrVal
        // 编辑 前台需要传递 的是  单元工程编号 add_id 属性名 attrKey  属性值 attrVal   和 这条属性的主键 attrId
        if($this->request->isAjax()){
            $param = input('param.');
            // 验证规则
            $rule = [
                ['add_id', 'require', '请选择单元工程'],
                ['attrKey', 'require', '属性名不能为空'],
                ['attrVal', 'require', '属性值不能为空']
            ];
            $validate = new \think\Validate($rule);
            //验证部分数据合法性
            if (!$validate->check($param)) {
                return json(['code' => -1,'msg' => $validate->getError()]);
            }
            $data = [];
            $data['unit_id'] = $param['add_id'];
            $data['attr_name'] = $param['attrKey'];
            $data['attr_value'] = $param['attrVal'];
            $custom = new QualityCustomAttributeModel();
            $id = isset($param['attrId']) ? $param['attrId'] : 0;
            if(empty($id)){
                $flag = $custom->insertTb($data);
            }else{
                if(empty($id)){
                    return json(['code' => -1, 'msg' => '属性的主键编号只能是数字']);
                }
                $data['id'] = $id;
                $flag = $custom->editTb($data);
            }
            return json($flag);
        }
    }

    // ht 删除属性
    public function delAttr()
    {
        // 前台只需要给我传递 要删除的 属性的主键 attrId
        $param = input('param.');
        // 验证规则
        $rule = [
            ['attrId', 'require|number|gt:-1', '请选择要删除的属性|属性编号只能是数字|属性编号不能为负数']
        ];
        $validate = new \think\Validate($rule);
        //验证部分数据合法性
        if (!$validate->check($param)) {
            return json(['code' => -1,'msg' => $validate->getError()]);
        }
        $node = new QualityCustomAttributeModel();
        $flag = $node->deleteTb($param['attrId']);
        return json($flag);
    }

}