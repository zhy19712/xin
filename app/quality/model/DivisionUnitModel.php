<?php
/**
 * Created by PhpStorm.
 * User: sir
 * Date: 2018/4/3
 * Time: 11:20
 */

namespace app\quality\model;


use think\Db;
use think\exception\PDOException;
use think\Model;

class DivisionUnitModel extends Model
{
    protected $name = 'quality_unit';
    //自动写入创建、更新时间 insertGetId和update方法中无效，只能用于save方法
    protected $autoWriteTimestamp = true;

    public function Division()
    {
        return $this->hasOne("DivisionModel",'id','division_id');
    }
    public function insertTb($param)
    {
        try{
            $result = $this->allowField(true)->save($param);
            if(false === $result){
                return ['code' => -1,'msg' => $this->getError()];
            }else{
                return ['code' => 1,'data' => '','msg' => '添加成功'];
            }
        }catch (PDOException $e){
            return ['code' => -1,'msg' => $e->getMessage()];
        }
    }

    public function editTb($param)
    {
        try{
            $result = $this->allowField(true)->save($param,['id' => $param['id']]);
            if(false === $result){
                return ['code' => -1,'msg' => $this->getError()];
            }else{
                return ['code' => 1, 'msg' => '编辑成功'];
            }
        }catch(PDOException $e){
            return ['code' => 0, 'msg' => $e->getMessage()];
        }
    }

    public function deleteTb($id)
    {
        try{
            $this->where('id',$id)->delete();
            return ['code' => 1, 'msg' => '删除成功'];
        }catch(PDOException $e){
            return ['code' => -1,'msg' => $e->getMessage()];
        }
    }

    public function getOne($id)
    {
        $data = $this->find($id);
        return $data;
    }

    public function batchDel($id)
    {
        try{
            $this->where('division_id',$id)->delete();
            return ['code' => 1, 'msg' => '删除成功'];
        }catch(PDOException $e){
            return ['code' => -1,'msg' => $e->getMessage()];
        }
    }

    public function getEl($id)
    {
        $data = $this->getOne($id);
        $new_data['el_val'] = 'EL.' . $data['el_start'] . '-EL.' . $data['el_cease'];
        $new_data['pile_number'] = $data['pile_number'];
        return $new_data;
    }

    public function examineFruit()
    {
        //顶部 --  优良，合格，不合格 个数和百分率
        // 0未验评，1不合格，2合格，3优良
        $total = $this->count(); // 总数据条数

        // 数量
//        $data['un_evaluation'] = $this->where(['EvaluateResult'=>0])->count(); // 未验评
        $data['unqualified'] = $this->where(['EvaluateResult'=>1])->count(); // 不合格
        $data['qualified'] = $this->where(['EvaluateResult'=>2])->count(); // 合格
        $data['excellent'] = $this->where(['EvaluateResult'=>3])->count(); // 优良

        // 百分率
//        $data['un_evaluation_percent'] = round($data['un_evaluation']/$total,2); // 未验评
        $data['unqualified_percent'] = round($data['unqualified']/$total,2); // 不合格
        $data['qualified_percent'] = round($data['qualified']/$total,2); // 合格
        $data['excellent_percent'] = round($data['excellent']/$total,2); // 优良

        return $data;
    }

    // ht 获取单元工厂(检验批)归属的标段
    public function sectionCode($id)
    {
        $code = Db::name('quality_unit')->alias('u')
            ->join('quality_division d','d.id = u.division_id','left')
            ->join('section s','s.id = d.section_id','left')
            ->where(['u.id'=>$id])->value('code');
        return $code;
    }

}