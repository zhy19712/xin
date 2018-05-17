<?php
/**
 * Created by PhpStorm.
 * User: zhifang
 * Date: 2018/4/8
 * Time: 14:59
 */

namespace app\standard\model;

use think\Model;

class MaterialTrackingDivision extends Model
{
    protected $name = "norm_materialtrackingdivision";

    /**
     * 添加左侧树节点节点
     */
    public function insertma($param)
    {
        try{
            $result = $this->allowField(true)->save($param);
            if(false === $result){
                return ['code' => -1,'msg' => $this->getError()];
            }else{
                $last_id = $this-> getLastInsID();

                $res = $this->where('id',$last_id)->update(['sort_id' => $last_id]);

                if($res){
                    return ['code' => 1,'msg' => '添加成功','data'=>$last_id];
                }else{
                    return ['code' => -1,'msg' => $this->getError()];
                }

            }
        }catch (PDOException $e){
            return ['code' => -1,'msg' => $e->getMessage()];
        }
    }
}