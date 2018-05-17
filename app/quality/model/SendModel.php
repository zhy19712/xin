<?php

namespace app\quality\model;


use think\Db;
use think\exception\PDOException;
use think\Model;

class SendModel extends Model
{
    protected $name = 'archive_income_send';

    public function insertTb($param)
    {
        try {
            $result = $this->allowField(true)->save($param);
            if (false === $result) {
                return ['code' => -1, 'msg' => $this->getError()];
            } else {
                return ['code' => 1,'data'=>'', 'msg' => '添加成功'];
            }
        } catch (PDOException $e) {
            return ['code' => -1, 'msg' => $e->getMessage()];
        }
    }

    public function editTb($param,$type=1)
    {
        try {
            $result = $this->allowField(true)->save($param, ['id' => $param['id']]);
            if (false === $result) {
                return ['code' => -1, 'msg' => $this->getError()];
            } else {
                return ['code' => 1,'data'=>'', 'msg' => '编辑成功'];
            }
        } catch (PDOException $e) {
            return ['code' => 0, 'msg' => $e->getMessage()];
        }
    }

    public function deleteTb($id)
    {
        try {
            // 关联删除附件
            $data = $this->find($id);
            $id_arr = explode(',',$data['file_ids']);
            if(empty($id_arr)){
                Db::name('attachment')->where(['id'=>['eq',$data['file_ids']]])->delete();
            }else{
                Db::name('attachment')->where(['id'=>['in',$id_arr]])->delete();
            }
            $this->where('id', $id)->delete();
            return ['code' => 1, 'msg' => '删除成功'];
        } catch (PDOException $e) {
            return ['code' => -1, 'msg' => $e->getMessage()];
        }
    }

    public function getOne($id,$type)
    {
        // $type 1 收文 2 发文
        if($type == 1){
            // 收文 查询 发件人的名称和单位
            $data = Db::name('archive_income_send')->alias('s')
                ->join('admin u', 's.send_id=u.id', 'left')
                ->join('admin_group g', 'u.admin_group_id=g.id', 'left')
                ->join('admin_group_type t', 'g.type=t.id', 'left')
                ->field('s.id,s.file_name,s.date,t.name as unit_name,u.nickname as send_name,s.status')
                ->where(['id'=>$id])->find();
        }else{
            // 发文 查询 收件人的名称和单位
            $data = Db::name('archive_income_send')->alias('s')
                ->join('admin u', 's.send_id=u.id', 'left')
                ->join('admin_group g', 'u.admin_group_id=g.id', 'left')
                ->join('admin_group_type t', 'g.type=t.id', 'left')
                ->field('s.id,s.file_name,s.date,t.name as unit_name,u.nickname as income_name,s.status')
                ->where(['id'=>$id])->find();
        }
        $id_arr = explode(',',$data['file_ids']);
        if(empty($id_arr)){
            $data['attachment'] = Db::name('attachment')->where(['id'=>['eq',$data['file_ids']]])->field('id,name,fileext')->select();
        }else{
            $data['attachment'] = Db::name('attachment')->where(['id'=>['in',$id_arr]])->field('id,name,fileext')->select();
        }
        return $data;
    }

}