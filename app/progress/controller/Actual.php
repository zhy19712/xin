<?php
/**
 * Created by PhpStorm.
 * User: sir
 * Date: 2018/6/13
 * Time: 9:06
 */

namespace app\progress\controller;


use app\admin\controller\Permissions;
use app\contract\model\SectionModel;
use app\modelmanagement\model\QualitymassModel;
use app\progress\model\ActualModel;
use think\Session;

/**
 * 实时进度填报
 * Class actual
 * @package app\progress\controller
 */
class Actual extends Permissions
{
    /**
     * 实时进度填报首页
     * ajax方法获取标段列表选项
     * 共用方法
     * @return mixed|\think\response\Json
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
     * 根据选择的标段获取对应的日期区间范围
     * @return \think\response\Json
     * @author hutao
     */
    public function dateScope()
    {
        if($this->request->isAjax()){
            // 前台传递标段编号 section_id
            $param = input('param.');
            $section_id = isset($param['section_id']) ? $param['section_id'] : 0;
            if(empty($section_id)){
                return json(['code'=>-1,'msg'=>'缺少参数']);
            }
            $actual = new ActualModel();
            $data = $actual->dateScope($section_id);
            return json(['code'=>1,'date_start'=>$data['date_start'],'date_end'=>$data['date_end'],'msg'=>'日期区间范围']);
        }
    }

    /**
     * 打开新增窗口,初始化标段和填报人
     * @return \think\response\Json
     * @author hutao
     */
    public function addInitialise()
    {
        if($this->request->isAjax()){
            // 根据当前登陆人的权限获取对应的 -- 标段列表选项
            $section = new SectionModel();
            $data['section'] = $section->sectionList();
            // 填报人
            $user_id = Session::has('admin') ? Session::get('admin') : 0;
            $user_name = Session::has('current_name') ? Session::get('current_name') : '';
            $data['user'] = ['user_id'=>$user_id,'user_name'=>$user_name];
            return json(['code'=>1,'data'=>$data,'msg'=>'标段和填报人数据']);
        }
    }

    /**
     * 新增
     * @return \think\response\Json
     * @author hutao
     */
    public function add()
    {
        if($this->request->isAjax()){
            // 前台需要传递的参数:
            // 所属标段编号 section_id  日期 actual_date 填报人 user_id  附件编号 attachment_id 附件地址 path
            //编辑的时候一定要  --- 传递本条记录的编号 actual_id
            $param = input('param.');
            // 验证规则
            $rule = [
                ['section_id', 'require', '缺少所属标段编号'],
                ['actual_date', 'require', '缺少日期'],
                ['user_id', 'require', '缺少填报人'],
                ['attachment_id', 'require', '缺少附件编号'],
                ['path', 'require', '缺少附件地址']
            ];
            $validate = new \think\Validate($rule);
            //验证部分数据合法性
            if (!$validate->check($param)) {
                return json(['code' => -1,'msg' => $validate->getError()]);
            }
            $actual = new ActualModel();
            if(empty($param['actual_id'])){
                $flag = $actual->insertTb($param);
            }else{
                $param['id'] = $param['actual_id'];
                $flag = $actual->editTb($param);
            }
            return json($flag);
        }
    }

    /**
     * 查看 -- 预览图片 和
     * 打开关联模型弹层 --  初始化实时日进度信息
     * 共用方法
     * @return \think\response\Json
     * @author hutao
     */
    public function preview()
    {
        if($this->request->isAjax()){
            // 前台传递本条记录的编号 actual_id
            $param = input('param.');
            $actual_id = isset($param['actual_id']) ? $param['section_id'] : 0;
            if(empty($actual_id)){
                return json(['code'=>-1,'msg'=>'缺少参数']);
            }
            $actual = new ActualModel();
            $data = $actual->getOne($actual_id);
            return json(['code'=>1,'path'=>$data,'msg'=>'查看']);
        }
    }

    /**
     * 删除
     * @return \think\response\Json
     * @author hutao
     */
    public function del()
    {
        if($this->request->isAjax()){
            // 前台传递本条记录的编号 actual_id
            $param = input('param.');
            $actual_id = isset($param['actual_id']) ? $param['section_id'] : 0;
            if(empty($actual_id)){
                return json(['code'=>-1,'msg'=>'缺少参数']);
            }
            $actual = new ActualModel();
            $flag = $actual->deleteTb($actual_id);
            return json($flag);
        }
    }

    /**
     * 实时进度关联模型
     * @return \think\response\Json
     * @author hutao
     */
    public function relevance()
    {
        if(request()->isAjax()){
            // 传递 选中填报记录的编号 actual_id,选中的所有构件的编号 [数组 id_arr]
            $param = input('param.');
            $id = isset($param['actual_id']) ? $param['actual_id'] : 0;
            if(empty($id)){
                return json(['code'=>-1,'msg'=>'缺少参数']);
            }
            $id_arr = input('id_arr/a');
            if(!sizeof($id_arr)){
                return json(['code'=>-1,'msg'=>'缺少构件的编号']);
            }
            $actual = new ActualModel();
            $data['id'] = $id;
            $data['relevance'] = '是'; // 是否关联
            $actual->editTb($data);
            $node = new QualitymassModel();
            $flag = $node->relevance($id,$id_arr,1); // 0 表示检验批关联 1 表示是实时进度关联 2 表示月进度关联
            return json($flag);
        }
    }

    /**
     * 实时进度解除关联
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
            $flag = $node->removeRelevance($id_arr,1); // 0 表示检验批关联 1 表示是实时进度关联 2 表示月进度关联
            $actual = new ActualModel();
            $data['id'] = $flag['actual_id'];
            $data['relevance'] = '否'; // 是否关联
            $actual->editTb($data);
            return json(['code'=>1,'msg'=>'解除成功']);
        }
    }

}