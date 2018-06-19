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
            $data = $this->find($id);
            if(file_exists('.'.$data['filepath'])){
                unlink('.'.$data['filepath']); //删除文件
            }
            $this->where('id', $id)->delete();
            return ['code' => 1, 'msg' => '删除成功'];
        } catch (PDOException $e) {
            return ['code' => -1, 'msg' => $e->getMessage()];
        }
    }

}