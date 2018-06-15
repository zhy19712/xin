<?php
/**
 * Created by PhpStorm.
 * User: zhifang
 * Date: 2018/3/23
 * Time: 16:14
 */

namespace app\contract\model;

use app\admin\model\AdminGroup;
use think\Db;
use think\Model;

class SectionModel extends Model
{
    protected $name='section';
    /**
     * 外键——合同
     * @return \think\model\relation\HasOne
     */
    public function contract()
    {
        return $this->hasOne('ContractModel', 'id', 'contractId');
    }

    /**
     * 外键——业主现场管理机构
     * @return \think\model\relation\HasOne
     */
    public function builder()
    {

        return $this->hasOne('app\admin\model\AdminGroup', 'id', 'builderId' )->field('id,name');
    }
    /**
     * 外键——监理现场管理机构
     * @return \think\model\relation\HasOne
     */
    public function supervisor()
    {
        return $this->hasOne('app\admin\model\AdminGroup', 'id', 'supervisorId');
    }
    /**
     * 外键——施工现场管理机构
     * @return \think\model\relation\HasOne
     */
    public function constructor()
    {
        return $this->hasOne('app\admin\model\AdminGroup', 'id', 'constructorId');
    }
    /**
     * 外键——设计现场管理机构
     * @return \think\model\relation\HasOne
     */
    public function designer()
    {
        return $this->hasOne('app\admin\model\AdminGroup', 'id', 'designerId');
    }
    /**
     * 外键——其他现场管理机构
     * @return \think\model\relation\HasOne
     */
    public function otherId()
    {
        return $this->hasOne('app\admin\model\AdminGroup', 'id', 'otherId');
    }
    /**
     * 外键——验评用户
     * @return \think\model\relation\HasOne
     */
    public function eveluateUser()
    {
        return $this->hasOne('app\admin\model\Admin', 'id', 'eveluateUserId');
    }


    /**
     * 标段——新增或修改
     * @param $mod
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function AddOrEdit($mod)
    {
        if (empty($mod['id'])) {
            $res = SectionModel::allowField(true)->insert($mod);
        } else {
//            $res = true;
//            $data = $this->find($mod['id']);
//
//            foreach ($mod as $k=>$v){
//
//                if($v != $data[$k]){
//
//                    $res = false;
//                    break;
//                }
//            }

            $res = SectionModel::allowField(true)->save($mod, ['id' => $mod['id']]);
//            if(!$res){
//
//            }
        }
        return $res ? true : false;
    }


    /**
     * 根据当前登陆人的权限获取对应的 -- 标段列表
     * @return array
     * @author hutao
     */
    public function sectionList()
    {
        // 获取当前登陆人的组织机构
        $group = new AdminGroup();
        $relation_id = $group->relationId();
        if($relation_id == 1){ // 管理员可以看所有的标段
            $section = $this->order('id asc')->column('id,name'); // 标段列表
        }else if($relation_id > 1){
            $section = $this->where('builderId|supervisorId|constructorId|designerId|otherId',$relation_id)->order('id asc')->column('id,name'); // 标段列表
        }else{
            $section = [];
        }
        return $section;
    }

}