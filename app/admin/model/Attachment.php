<?php
/**
 * Created by PhpStorm.
 * User: zhifang
 * Date: 2018/4/18
 * Time: 14:31
 */
namespace app\admin\model;
use think\exception\PDOException;
use think\Model;

class Attachment extends Model
{
    protected $name='attachment';

    public function deleteTb($id)
    {
        try {
            $this->where('id', $id)->delete();
            return ['code' => 1, 'msg' => '删除成功'];
        } catch (PDOException $e) {
            return ['code' => -1, 'msg' => $e->getMessage()];
        }
    }

}