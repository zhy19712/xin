<?php

namespace app\admin\model;

use think\Db;
use think\exception\PDOException;
use \think\Model;
class Admin extends Model
{
	public function admincate()
    {
        //关联角色表
        return $this->belongsTo('AdminCate');
    }

    public function SignImg()
    {
        return $this->hasOne('Attachment','id','signature');
    }

    /**
     * 头像
     * @return \think\model\relation\HasOne
     */
    public function Thumb()
    {
        return $this->hasOne('Attachment','id','thumb');
    }

    public function log()
    {
        //关联日志表
        return $this->hasOne('AdminLog');
    }

    /**
     * 根据组织机构 编号 关联删除 用户
     * @param $group_id
     * @return array
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * @author hutao
     */
    public function delUserByGroupId($group_id)
    {
        $user = $this->where('admin_group_id',$group_id)->column('id,thumb,signature');
        if(count($user) > 0){
            $idArr = $thumbArr = [];
            foreach($user as $u){
                $idArr[] = $u['id'];
                $thumbArr[] = $u['thumb'];  // 头像
                $thumbArr[] = $u['signature']; // 电子签名
            }
            $thumbArr = array_filter($thumbArr);
            $thumbPath = Db::name('attachment')->whereIn('id',$thumbArr)->column('filepath');
            if(count($thumbPath) > 0){
                // 删除每一个用户的头像 和 电子签名
                foreach($thumbPath as $k=>$v){
                    if(file_exists($v)){
                        unlink($v); //删除文件
                    }
                }
            }
            // 删除用户记录
            Db::name('admin')->whereIn('id',$idArr)->delete();
        }
        return ['code' => 1, 'msg' => '删除成功'];
    }

    /**
     * 删除管理员
     * @param $id
     * @return array
     * @author hutao
     */
    public function delUser($id)
    {
        try{
            $user = $this->where('id',$id)->column('id,thumb,signature');
            if(!empty($user)){
                $thumbArr = [$user[$id]['thumb'],$user[$id]['signature']];
                $thumbPath = Db::name('attachment')->whereIn('id',$thumbArr)->column('filepath');
                if(count($thumbPath) > 0){
                    // 删除每一个用户的头像 和 电子签名
                    foreach($thumbPath as $k=>$v){
                        if(file_exists($v)){
                            unlink($v); //删除文件
                        }
                    }
                }
                $this->where('id',$id)->delete();
            }
            return ['code' => 1, 'msg' => '删除成功'];
        }catch(PDOException $e){
            return ['code' => -1,'msg' => $e->getMessage()];
        }
    }

    /**
     * 根据角色类型管理 编号 关联删除 用户
     * @return array
     */
    public function delUserByCateId($cate_id)
    {
        $user = $this->where('admin_cate_id',$cate_id)->column('id','thumb');
        if(count($user) > 0){
            $idArr = $thumbArr = [];
            foreach($user as $u){
                $idArr[] = $u['id'];
                $thumbArr[] = $u['thumb'];  // 头像
                $thumbArr[] = $u['signature']; // 电子签名
            }
            $thumbArr = array_filter($thumbArr);

            $thumbPath = Db::name('attachment')->where(['id'=>['in',$thumbArr]])->column('filepath');

            if(count($thumbPath) > 0){
                // 删除每一个用户的头像 和 电子签名
                foreach($thumbPath as $k=>$v){
                    if(file_exists($v)){
                        unlink($v); //删除文件
                    }
                }
            }
            // 删除用户记录
            $this->where(['id'=>['in',$idArr]])->delete();
        }
        return ['code' => 1, 'msg' => '删除成功'];
    }

    /**
     * 查询一个用户信息的用户名name
     */

    public function getName($where)
    {
        $data = $this->field("id,name")->where("id = ".$where['id']."  and name like '%" .$where['name']. "%'")->find();
        return $data;
    }

    /**
     * 根据传过来的admin_cate表中的id,admin表中的id,修改admin_cate_id中的值
     */

    public function deladmincateid($param)
    {
        //admin_id是admin表id，id为cate表id
        $admin_cate_id = $this->field("admin_cate_id")->where("id",$param['admin_id'])->find();

        if(!empty($admin_cate_id["admin_cate_id"]))
        {
            $cate_id = explode(",",$admin_cate_id["admin_cate_id"]);
            foreach($cate_id as $k=>$v)
            {
                if($v == $param['id'])
                {
                    unset($cate_id[$k]);
                }
            }
            if(!empty($cate_id))
            {
                $str = implode(",",$cate_id);

            }else{
                $str = "";
            }
        }else
        {
            $str = "";
        }

        //把处理过得数据重新插入数组中
        $result = $this->allowField(true)->update(['admin_cate_id'=>$str],['id' => $param['admin_id']]);

        if($result)
        {
            return ['code' => 1,'msg' => "删除成功"];
        }else{
            return ['code' => -1,'msg' => "删除失败"];
        }

    }

    /**
     * 根据传过来的admin_cate表中的id,删除admin表中的admin_cate_id中的所有的id
     */

    public function deladmincate($param)
    {
        //id为cate表id
        //先删除admin表中的所有的包含有当前传过来cate表id
        try {
            $admin_cate_id = $this->field("id,name,admin_cate_id")->select();
            if (!empty($admin_cate_id)) {
                foreach ($admin_cate_id as $k => $v) {
                    if(!empty($v['admin_cate_id']))
                    {
                        $cate_id = explode(",", $v['admin_cate_id']);

                        foreach ((array)$cate_id as $key => $val) {
                            if ($val == $param['id']) {
                                unset($cate_id[$key]);
                            }
                        }

                        $v['admin_cate_id'] = implode(",", $cate_id);

                        //把新筛选的admin_cate_id重新插入数据库

                        $this->allowField(true)->update(['admin_cate_id' => $v['admin_cate_id']], ['id' => $v['id']]);
                    }
                }

            }
        }catch(PDOException $e){
            return ['code' => -1,'msg' => $e->getMessage()];
        }

    }

    /*
     * 根据admin_cate表的id，查询admin表中的admin_cate_id字段
     */

    public function getAdmincateid()
    {
        $data = $this->field("id,nickname,name,admin_cate_id")->select();

        return $data;
    }

    /*
     * 根据传过来的admin_id用户数组，cate表中的id
     */

    public function insertAdminid($param)
    {
        //先删除admin表中的所有的包含有当前传过来cate表id
        $admin_cate_id = $this->field("id,name,admin_cate_id")->select();


        try {

            if (!empty($admin_cate_id)) {
                foreach ($admin_cate_id as $k => $v) {

                    $cate_id = explode(",", $v['admin_cate_id']);

                    foreach ((array)$cate_id as $key => $val) {
                        if ($val == $param['id']) {
                            unset($cate_id[$key]);
                        }
                    }

                    $v['admin_cate_id'] = implode(",", $cate_id);

                    //把新筛选的admin_cate_id重新插入数据库

                    $this->allowField(true)->update(['admin_cate_id' => $v['admin_cate_id']], ['id' => $v['id']]);

                }

            }


            //再添加传过来的cate表中的id到admin_cate_id字段中


            if (!empty($param["admin_id"])) {
                foreach ($param["admin_id"] as $ke => $va) {

                    //根据admin_id查询admin表中的admin_cate_id,并插入传过来的id
                    $admin_cate_id_info = $this->field("admin_cate_id")->where("id", $va)->find();

                    if ($admin_cate_id_info["admin_cate_id"]) {
                        $admin_cate_id_info_data = explode(",", $admin_cate_id_info["admin_cate_id"]);

                        array_push($admin_cate_id_info_data, $param['id']);

                    }else
                    {
                        $admin_cate_id_info_data = [$param['id']];

                    }

                    $str = implode(",", $admin_cate_id_info_data);

                    //把重新修改的admin_cate_id重新插入数据库

                    $this->allowField(true)->update(['admin_cate_id' => $str], ['id' => $va]);
                }
            }
            return ['code' => 1, 'msg' => '添加成功'];
        }catch(PDOException $e){
            return ['code' => -1,'msg' => $e->getMessage()];
        }


    }
    /**
     * 根据admin表中的id查询用户名，用户id,组织机构admin_group_id
     */

    public function getadmininfo($id)
    {
        $data = $this->field("id,nickname as name,admin_group_id")->where("id",$id)->find();
        return $data;
    }

}
