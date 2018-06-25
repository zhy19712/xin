<?php
/**
 * Created by PhpStorm.
 * User: zhifang
 * Date: 2018/4/8
 * Time: 11:22
 */
/**
 * 标准库管理，质量验评标准库
 * Class Library
 * @package app\standard\controller
 */
namespace app\standard\controller;

use app\admin\controller\Permissions;
use app\quality\model\DivisionModel;
use app\standard\model\ControlPoint;
use app\standard\model\MaterialTrackingDivision;
use app\standard\model\TemplateModel;
use think\Request;
use \think\Db;
use think\exception\PDOException;

/**
 * 标准库
 * Class Library
 * @package app\standard\controller
 */
class Library extends Permissions
{
    protected $controlPointService;
    protected $materialTrackingDivesionService;

    public function __construct(Request $request = null)
    {
        parent::__construct($request);
        $this->materialTrackingDivesionService = new MaterialTrackingDivision();
        $this->controlPointService = new ControlPoint();
    }

    public function index()
    {
        return $this->fetch();
    }

    public function com()
    {
        return $this->fetch();
    }

    public function branch()
    {
        return $this->fetch();
    }

    public function item()
    {
        return $this->fetch();
    }

    public function unit()
    {
        return $this->fetch();
    }

    public function special()
    {
        return $this->fetch();
    }

    /**
     * 新增编辑控制点
     * @return mixed
     */
    public function addcontrollpoint($id = null)
    {
        if ($this->request->isAjax()) {
            //实例化模型类
            $model = new ControlPoint();
            $mod = input('post.');
            if (empty($mod['id'])) {
                $res = $model->insertTb($mod);
            } else {
                $res = $model->editTb($mod);
            }
            return json($res);
        }

        $this->assign('id', $id);
        return $this->fetch();
    }

    /**
     * 编辑划分树
     */
    public function adddivsiontree()
    {
        //实例化模型类
        $model = new MaterialTrackingDivision();
        $mod = input('post.');
        if (empty($mod['id'])) {
            $flag = $model->insertMa($mod);
            return json($flag);
        } else {
            $flag =  $model->editMa($mod);
            return json($flag);
        }
    }

    /**
     * 标准库划分树
     * @param $cat 标准库分类
     * @return false|static[]
     * @throws \think\exception\DbException
     */
    public function GetDivsionTree($cat)
    {
        //实例化模型类
        $model = new MaterialTrackingDivision();
        //查询所有的数据
        $data = $model->getAllData($cat);
        //定义空字符串
        $str = "";
        if(!empty($data))
        {
            foreach($data as $key=>$vo){
                    $str .= '{ "id": "' . $vo['id'] . '", "pid":"' . $vo['pid'] . '", "name":"' . $vo['name'].'"'.',"sort_id":"'.$vo['sort_id'].'","type":"'.$vo['type'].'","cat":"'.$vo['cat'].'"';
                    $str .= '},';
            }
        }
        return "[" . substr($str, 0, -1) . "]";
    }

    /**
     * 上移下移
     * @return mixed|\think\response\Json
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function sortNode()
    {
        if(request()->isAjax()){
            try {
                $change_id = $this->request->has('change_id') ? $this->request->param('change_id', 0, 'intval') : 0; //影响节点id,包括上移下移,没有默认0
                $change_sort_id = $this->request->has('change_sort_id') ? $this->request->param('change_sort_id', 0, 'intval') : 0; //影响节点的排序编号sort_id 没有默认0

                $select_id = input('post.select_id'); // 当前节点的编号
                $select_sort_id = input('post.select_sort_id'); // 当前节点的排序编号

                Db::name('norm_materialtrackingdivision')->where('id', $select_id)->update(['sort_id' => $change_sort_id]);
                Db::name('norm_materialtrackingdivision')->where('id', $change_id)->update(['sort_id' => $select_sort_id]);

                return json(['code' => 1,'msg' => '移动成功']);

            }catch (PDOException $e){
                return ['code' => -1,'msg' => $e->getMessage()];
            }
        }
    }

    /**
     * 删除划分树节点
     * @param $id
     * @return \think\response\Json
     * @throws \think\exception\DbException
     */
    public function deldivsion($id)
    {
        if (count(MaterialTrackingDivision::all(['pid' => $id]))) {
            return json(['code' => -1, 'msg' => '请先删除子节点']);
        }
        if (count(ControlPoint::all(['procedureid' => $id]))) {
            return json(['code' => -1, 'msg' => '请先删除节点下控制点']);
        }

        //先删除工程划分、工序、控制点关系表中的相关的数据
        $flag = Db::name("quality_division_controlpoint_relation")->where("ma_division_id",$id)->delete();

        return MaterialTrackingDivision::destroy($id) ? json(['code' => 1]) : json(['code' => -1, 'msg' => '删除失败']);
    }

    /**
     * 选择模板
     * @return mixed
     */
    public function chosetemplate()
    {
        $param = input('get.');
        $this->assign("type",$param["type"]);
        $this->assign("use",$param["use"]);
        return $this->fetch();
    }

    /**
     * 获取控制点
     * @param $id
     * @return \think\response\Json
     * @throws \think\exception\DbException
     */
    public function getcontrolpoint($id)
    {
        return json(ControlPoint::get($id));
    }

    /**
     * 删除控制点
     * @param $id
     * @return \think\response\Json
     */
    public function delcontrolpoint()
    {
        if(request()->isAjax()){
            //实例化model类型
            $model = new ControlPoint();

            $param = input('post.');

            //查询所属的工序

            $procedureid = $model->getOne($param["id"]);

            $param["ma_division_id"] = $procedureid["procedureid"];

            $flag = $model->delTb($param);

            return json($flag);
        }
    }

    /**
     * 获取模板名
     * @param $id
     * @return \think\response\Json
     * @throws \think\exception\DbException
     */
    public function gettemplatename($id)
    {
        return json(TemplateModel::get($id));
    }
}