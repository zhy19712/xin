<?php
/**
 * Created by PhpStorm.
 * User: zhifang
 * Date: 2018/3/23
 * Time: 14:43
 */

namespace app\contract\model;

use think\Model;
use think\Session;

class ContractModel extends Model
{
    protected $table='fengning_contract';
    //自动写入创建、更新时间
    protected $autoWriteTimestamp = 'datetime';

    /**
     * 新增或修改
     * @param $mod
     * @return array
     */
    public function AddOrEdit($mod)
    {
        if (empty($mod['id'])) {
            $mod['userName'] = Session::get('current_name');
            $res = ContractModel::allowField(true)->insert($mod);
        } else {
           $res= ContractModel::allowField(true)->save($mod, ['id' => $mod['id']]);
        }

        return $res?true:false;
    }

    /**
     * 删除
     * @param $id
     * @return bool
     */
    public function del($id)
    {
        return ContractModel::destroy($id)?true:false;
    }
}