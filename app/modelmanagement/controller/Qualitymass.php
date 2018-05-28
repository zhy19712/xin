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
     * 质量模型 --
     * 左侧的树
     * @return mixed|\think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
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
            // 传递 选中节点的 add_id
            $id = input('add_id');
            if(empty($id)){
                return json(['code'=>-1,'msg'=>'缺少节点的编号']);
            }
            $node = new DivisionModel();
            $flag = $node->removeRelevanceNode($id);
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
            $id = input('add_id');
            $id_arr = input('id_arr/a');
            if(!sizeof($id_arr)){
                return json(['code'=>-1,'msg'=>'缺少构件的编号']);
            }
            $node = new QualitymassModel();
            $flag = $node->relevance($id,$id_arr);
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
            $flag = $node->removeRelevance($id_arr);
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
            if(empty($number) || empty($number_type)){
                return json(['code'=>-1,'data'=>[],'msg'=>'缺少参数']);
            }
            $quality = new QualitymassModel();
            $data = $quality->qualityNodeInfo($number,$number_type);
            return json(['code'=>1,'data'=>$data,'msg'=>'质量模型: [所有关联模型编号,模型状态,单元工程的编号]']);
        }
    }

    // ============================   着急先把方法放到这里 后期有时间再转移

    // 前台根据资源包名称 显示该包下的模型
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
            return json(['code'=>1,'pag_name'=>$pag_name,'msg'=>'资源包名称']);
        }
    }

    // 模型效果配置信息
    public function configureInfo()
    {
        if($this->request->isAjax()){
            $version = new ConfigureModel();
            $configureInfo = $version->getConfigure();
            return json(['code'=>1,'configureInfo'=>$configureInfo,'msg'=>'模型效果配置信息']);
        }
    }

    // 顶部 --  优良，合格，不合格 个数和百分率
    public function examineFruit()
    {
        if($this->request->isAjax()){
            $unit = new DivisionUnitModel();
            $data = $unit->examineFruit();
            return json(['code'=>1,'data'=>$data,''=>'[优良|合格|不合格]的个数和百分率']);
        }
    }

    // 顶部 --  全景模型 点击模型 获取模型属性
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

    // 顶部 -- 质量模型 -- 管理信息 -- 自定义属性
    // 每个版本 每个模型下 都有 不固定的 自定义属性
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

    // 删除属性
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