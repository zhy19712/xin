<?php

namespace app\admin\controller;

use app\admin\model\AdminGroup;
use \think\Db;
use \think\Session;
use app\admin\model\Admin as adminModel;//管理员模型
use app\admin\model\AdminMenu;
class Admin extends Permissions
{
    /**
     * 获取 组织机构 左侧的树结构
     * @return mixed|\think\response\Json
     * @author hutao
     */
    public function index()
    {
        // 获取左侧的树结构
        if(request()->isAjax()){
            $node = new AdminGroup();
            $nodeStr = $node->getNodeInfo();
            return json($nodeStr);
        }
        return $this->fetch();
    }

    public function getAdminCate()
    {
        // 获取左侧的树结构
        if(request()->isAjax()){
            $node = new AdminGroup();
            $nodeStr = $node->getNodeInfo('cate');
            return json($nodeStr);
        }
        return $this->fetch();
    }

    /**
     * 点击编辑的时候
     * 获取一条节点信息 和 节点类型
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author hutao
     */
    public function getNode()
    {
        /**
         *  type 是用来判断 编辑 的是机构 还是 部门
         *  当编辑机构的时候 就返回 机构类型 nodeType
         *  提交编辑的时候 把 选择机构的编号提交过来 名称就是 type
         */
        if(request()->isAjax()){
            $param = input('post.');
            $id = isset($param['id']) ? $param['id'] : 0;
            $type = isset($param['type']) ? $param['type'] : 0;
            $node = new AdminGroup();
            $info['node'] = $node->getOne($id);
            if($type != 0){
                $info['nodeType'] = Db::name('admin_group_type')->select();
            }
            return json($info);
        }
    }

    /**
     * 新增 或者 编辑 组织机构的节点
     * @return mixed|\think\response\Json
     * @author hutao
     */
    public function editNode()
    {
        if(request()->isAjax()){
            $node = new AdminGroup();
            $param = input('post.');
            // 验证规则
            if(empty($param['type'])){
                $validate = new \think\Validate([
                    ['name', 'require|max:100', '部门名称不能为空|名称不能超过100个字符'],
                    ['pid', 'require', '请选择组织机构'],
                ]);
            }else{
                $validate = new \think\Validate([
                    ['name', 'require|max:100', '机构名称不能为空|名称不能超过100个字符'],
                    ['type', 'require', '请选择机构类型'],
                ]);
            }
            //验证部分数据合法性
            if (!$validate->check($param)) {
                $this->error('提交失败：' . $validate->getError());
            }

            /**
             * 当新增 机构的时候
             * 前台需要传递的是 pid 父级节点编号,type 机构类型,name 节点名称
             * 编辑 机构的时候 传递 id 自己的编号 pid 父级节点编号,type 机构类型,name 节点名称
             *
             * 当新增 部门的时候
             * 前台需要传递的是 pid 父级节点编号,name 节点名称
             * 编辑 机构的时候 传递 id 自己的编号 pid 父级节点编号,name 节点名称
             *
             * 系统自动判断赋值 category 1 组织机构 2 部门
             */
            if(empty($param['type'])){
                $data = ['pid' => $param['pid'],'category' => '2','name' => $param['name']];
            }else{
                $data = ['pid' => $param['pid'],'category' => '1','type' => $param['type'],'name' => $param['name']];
            }
            if(empty($param['id'])){
                $flag = $node->insertTb($data);
                return json($flag);
            }else{
                $data['id'] = $param['id'];
                $flag = $node->editTb($data);
                return json($flag);
            }
        }
        return $this->fetch();
    }

    /**
     * 删除 组织机构的节点
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * @author hutao
     */
    public function delNode()
    {
        /**
         * 前台只需要给我传递 要删除的 节点自己的id 编号
         */
        $param = input('post.');
        $node = new AdminGroup();
        // 是否包含子节点
        $exist = $node->isParent($param['id']);
        if(!empty($exist)){
            return json(['code' => -1,'msg' => '包含子节点,不能删除']);
        }
        // 先删除节点下的用户
        $user = new AdminModel();
        $user->delUserByGroupId($param['id']);
        // 最后删除此节点
        $flag = $node->deleteTb($param['id']);
        return json($flag);
    }

    /**
     * 获取路径
     * @return \think\response\Json
     * @author hutao
     */
    public function getParents()
    {
        /**
         * 前台就传递 当前点击的节点的 id 编号
         */
        if(request()->isAjax()){
            $id = $this->request->has('id') ? $this->request->param('id', 0, 'intval') : 0;
            $node = new AdminGroup();
            $path = "";
            while($id>0)
            {
                $data = $node->getOne($id);
                $path = $data['name'] . ">>" . $path;
                $id = $data['pid'];
            }
            return json(['code' => 1,'path' => substr($path, 0 , -2),'msg' => "success"]);
        }
    }

    /**
     * 上移下移
     * @return mixed|\think\response\Json
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * @author hutao
     */
    public function sortNode()
    {
        if(request()->isAjax()){
            $prev_id = $this->request->has('prev_id') ? $this->request->param('prev_id', 0, 'intval') : 0; // 前一个节点的编号 没有默认0
            $prev_sort_id = $this->request->has('prev_sort_id') ? $this->request->param('prev_sort_id', 0, 'intval') : 0; // 前一个节点的排序编号 没有默认0

            $id = input('post.id'); // 当前节点的编号
            $id_sort_id = input('post.id_sort_id'); // 当前节点的排序编号

            $next_id = $this->request->has('next_id') ? $this->request->param('next_id', 0, 'intval') : 0; // 后一个节点的编号 没有默认0
            $next_sort_id = $this->request->has('next_sort_id') ? $this->request->param('next_sort_id', 0, 'intval') : 0; // 后一个节点的排序编号 没有默认0

            // 下移
            if(empty($prev_id)){
                Db::name('admin_group')->where('id',$next_id)->update(['sort_id' => $id_sort_id]);
                Db::name('admin_group')->where('id',$id)->update(['sort_id' => $next_sort_id]);
            }else if(empty($next_id)){
                Db::name('admin_group')->where('id',$id)->update(['sort_id' => $prev_sort_id]);
                Db::name('admin_group')->where('id',$prev_id)->update(['sort_id' => $id_sort_id]);
            }

            return json(['code' => 1,'msg' => '成功']);
        }
        return $this->fetch();
    }


    /**
     * 管理员个人资料修改，属于无权限操作，仅能修改昵称和头像，后续可增加其他字段
     * @return mixed|void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function personal()
    {
        //获取管理员id
        $id = Session::get('admin');
        $model = new adminModel();
        if($id > 0) {
            //是修改操作
            if($this->request->isPost()) {
                //是提交操作
                $post = $this->request->post();

                // 验证规则
                $rule = [
                    ['nickname', 'require', '请输入姓名'],
                    ['gender', 'require|number', '请选择性别'],
                    ['mail', 'email', '邮箱格式有误'],
                    ['tele', 'alphaDash', '办公电话格式有误'],
                    ['name', 'alphaDash', '登录名只能是字母、数字、下划线 _和破折号 - 的组合']
                ];
                $validate = new \think\Validate($rule);
                //验证部分数据合法性
                if (!$validate->check($post)) {
                    return $this->error($validate->getError());
                }
                $n = preg_match_all("/^(13|14|15|17|18)[0-9]{9}$/",$post['mobile'],$array);
                if(empty($n)){
                    return $this->error('手机格式有误');
                }


                //验证昵称是否存在
                $nickname = $model->where(['nickname'=>$post['nickname'],'id'=>['neq',$post['id']]])->find();
                if(!empty($nickname)) {
                    return $this->error('提交失败：该昵称已被占用');
                }
                if(false == $model->allowField(true)->save($post,['id'=>$id])) {
                    return $this->error('修改失败');
                } else {
                    addlog($model->id);//写入日志
                    return $this->success('修改个人信息成功','admin/admin/personal');
                }
            } else {
                //非提交操作
                $info['admin'] = $model->where('id',$id)->find();
                $info['thumb'] = Db::name('attachment')->where('id',$info['admin']['thumb'])->value('filepath');
                $info['signature'] = Db::name('attachment')->where('id',$info['admin']['signature'])->value('filepath');
                $this->assign('info',$info);
                return $this->fetch();
            }
        } else {
            return $this->error('id不正确');
        }
    }


    /**
     * 管理员的添加及修改
     * @return mixed|void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function publish()
    {
    	//获取管理员id
    	$id = $this->request->has('id') ? $this->request->param('id', 0, 'intval') : 0;
    	$type = $this->request->has('type') ? $this->request->param('type') : '';
    	$model = new adminModel();
    	if($id > 0) {
    		//是修改操作
    		if($this->request->isPost()) {
    			//是提交操作
    			$post = $this->request->post();

                // 验证规则
                $rule = [
                    ['nickname', 'require', '请输入姓名'],
                    ['gender', 'require|number', '请选择性别'],
                    ['mail', 'email', '邮箱格式有误'],
                    ['tele', 'alphaDash', '办公电话格式有误'],
                    ['name', 'alphaDash', '登录名只能是字母、数字、下划线 _和破折号 - 的组合']
                ];
                $validate = new \think\Validate($rule);
                //验证部分数据合法性
                if (!$validate->check($post)) {
                    return $this->error($validate->getError());
                }
                $n = preg_match_all("/^(13|14|15|17|18)[0-9]{9}$/",$post['mobile'],$array);
                if(empty($n)){
                    return $this->error('手机格式有误');
                }



    			//验证  唯一规则： 表名，字段名，排除主键值，主键名
	            $validate = new \think\Validate([
	                ['name', 'require|alphaDash', '管理员名称不能为空|用户名格式只能是字母、数字、下划线 _ 和破折号 - 的组合']
	            ]);
	            //验证部分数据合法性
	            if (!$validate->check($post)) {
	                $this->error('提交失败：' . $validate->getError());
	            }
	            //验证用户名是否存在
	            $name = $model->where(['name'=>$post['name'],'id'=>['neq',$post['id']]])->find();
	            if(!empty($name)) {
	            	return $this->error('提交失败：该用户名已被注册');
	            }
	            //验证昵称是否存在
	            $nickname = $model->where(['nickname'=>$post['nickname'],'id'=>['neq',$post['id']]])->find();
	            if(!empty($nickname)) {
	            	return $this->error('提交失败：该昵称已被占用');
	            }
	            if(false == $model->allowField(true)->save($post,['id'=>$id])) {
	            	return $this->error('修改失败');
	            } else {
                    addlog($model->id);//写入日志
	            	return $this->success('修改管理员信息成功','admin/admin/index');
	            }
    		} else {
    			//非提交操作
    			$info['admin'] = $model->where('id',$id)->find();
    			$info['admin_cate'] = Db::name('admin_cate')->select();
    			$this->assign('info',$info);
    			if($type == 'group'){
                    $info['attachment'] = Db::name('attachment')->where('id',$info['admin']['signature'])->find();
    			    return json($info);
                }
    			return $this->fetch();
    		}
    	} else {
    		//是新增操作
    		if($this->request->isPost()) {
    			//是提交操作
    			$post = $this->request->post();
    			//验证  唯一规则： 表名，字段名，排除主键值，主键名
	            $validate = new \think\Validate([
	                ['name', 'require|alphaDash', '用户名不能为空|用户名格式只能是字母、数字、下划线 _ 和破折号 - 的组合'],
	                ['password', 'require|confirm', '密码不能为空|两次密码不一致'],
	                ['password_confirm', 'require', '重复密码不能为空']
	            ]);
	            //验证部分数据合法性
	            if (!$validate->check($post)) {
	                $this->error('提交失败：' . $validate->getError());
	            }
	            //验证用户名是否存在
	            $name = $model->where('name',$post['name'])->find();
	            if(!empty($name)) {
	            	return $this->error('提交失败：该用户名已被注册');
	            }
	            //验证昵称是否存在
	            $nickname = $model->where('nickname',$post['nickname'])->find();
	            if(!empty($nickname)) {
	            	return $this->error('提交失败：该昵称已被占用');
	            }
	            //密码处理
	            $post['password'] = password($post['password']);
	            if(false == $model->allowField(true)->save($post)) {
	            	return $this->error('添加管理员失败');
	            } else {
                    addlog($model->id);//写入日志
	            	return $this->success('添加管理员成功','admin/admin/index');
	            }
    		} else {
    			//非提交操作
    			$info['admin_cate'] = Db::name('admin_cate')->select();
    			$this->assign('info',$info);
    			return $this->fetch();
    		}
    	}
    }


    /**
     * 修改密码
     * @return mixed|void
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function editPassword()
    {
    	$id = $this->request->has('id') ? $this->request->param('id', 0, 'intval') : 0;
    	if($id > 0) {
    		if($id == Session::get('admin')) {
    			$post = $this->request->post();
    			//验证  唯一规则： 表名，字段名，排除主键值，主键名
	            $validate = new \think\Validate([
	                ['password', 'require|confirm', '密码不能为空|两次密码不一致'],
	                ['password_confirm', 'require', '重复密码不能为空'],
	            ]);
	            //验证部分数据合法性
	            if (!$validate->check($post)) {
	                $this->error('提交失败：' . $validate->getError());
	            }
    			$admin = Db::name('admin')->where('id',$id)->find();
    			if(password($post['password_old']) == $admin['password']) {
    				if(false == Db::name('admin')->where('id',$id)->update(['password'=>password($post['password'])])) {
    					return $this->error('修改失败');
    				} else {
                        addlog();//写入日志
    					return $this->success('修改成功','admin/main/index');
    				}
    			} else {
    				return $this->error('原密码错误');
    			}
    		} else {
    			return $this->error('不能修改别人的密码');
    		}
    	} else {
            $id = Session::get('admin');
            $this->assign('id',$id);
    		return $this->fetch();
    	}
    }

    /**
     * 重置密码
     * @return mixed|void
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * @author hutao
     */
    public function editPwd()
    {
        $id = $this->request->has('id') ? $this->request->param('id', 0, 'intval') : 0;
        if($id > 0){
            if(Session::get('admin') == 1) {
                // 管理员可以重置他人密码为 '123456'
                if(false === Db::name('admin')->where('id',$id)->update(['password'=>password('123456')])) {
                    return $this->error('重置失败');
                } else {
                    addlog();//写入日志
                    return $this->success('重置成功');
                }
            } else {
                return $this->error('没有权限重置他人密码');
            }
        }
        return $this->fetch();
    }


    /**
     * 删除管理员
     */
    public function delete()
    {
    	if($this->request->isAjax()) {
    		$id = $this->request->has('id') ? $this->request->param('id', 0, 'intval') : 0;
    		if($id == 1) {
    			return $this->error('网站所有者不能被删除');
    		}
    		if($id == Session::get('admin')) {
    			return $this->error('自己不能删除自己');
    		}
            $user = new AdminModel();
    		$flag = $user->delUser($id);
    		if($flag['code'] != 1) {
    			return $this->error('删除失败');
    		} else {
                addlog($id);//写入日志
    			return $this->success('删除成功','admin/admin/index');
    		}
    	}
    }


    /**
     * 管理员权限分组列表
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function adminCate()
    {
    	$model = new \app\admin\model\AdminCate;

        $post = $this->request->param();
        if (isset($post['keywords']) and !empty($post['keywords'])) {
            $where['name'] = ['like', '%' . $post['keywords'] . '%'];
        }
 
        if(isset($post['create_time']) and !empty($post['create_time'])) {
            $min_time = strtotime($post['create_time']);
            $max_time = $min_time + 24 * 60 * 60;
            $where['create_time'] = [['>=',$min_time],['<=',$max_time]];
        }
        
        $cate = empty($where) ? $model->order('create_time desc')->paginate(20) : $model->where($where)->order('create_time desc')->paginate(20,false,['query'=>$this->request->param()]);
        
    	$this->assign('cate',$cate);
    	return $this->fetch();

    }


    /**
     * 管理员角色添加和修改操作
     * @return mixed|void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function adminCatePublish()
    {
        //获取角色id
        $id = $this->request->has('id') ? $this->request->param('id', 0, 'intval') : 0;
        $model = new \app\admin\model\AdminCate();
        $menuModel = new AdminMenu();
        if($id > 0) {
            //是修改操作
            if($this->request->isPost()) {
                //是提交操作
                $post = $this->request->post();
                //验证  唯一规则： 表名，字段名，排除主键值，主键名
                $validate = new \think\Validate([
                    ['name', 'require', '角色名称不能为空'],
                ]);
                //验证部分数据合法性
                if (!$validate->check($post)) {
                    $this->error('提交失败：' . $validate->getError());
                }
                //验证用户名是否存在
                $name = $model->where(['name'=>$post['name'],'id'=>['neq',$post['id']]])->select();
                if(!empty($name)) {
                    return $this->error('提交失败：该角色名已存在');
                }
                //处理选中的权限菜单id，转为字符串
                if(!empty($post['admin_menu_id'])) {
                    $post['permissions'] = implode(',',$post['admin_menu_id']);
                } else {
                    $post['permissions'] = '0';
                }
                if(false == $model->allowField(true)->save($post,['id'=>$id])) {
                    return $this->error('修改失败');
                } else {
                    addlog($model->id);//写入日志
                    return $this->success('修改角色信息成功','admin/admin/adminCate');
                }
            } else {
                //非提交操作
                $info['cate'] = $model->where('id',$id)->find();
                if(!empty($info['cate']['permissions'])) {
                    //将菜单id字符串拆分成数组
                    $info['cate']['permissions'] = explode(',',$info['cate']['permissions']);
                }
                $menus = Db::name('admin_menu')->select();
                $info['menu'] = $this->menulist($menus);
                $this->assign('info',$info);
                return $this->fetch();
            }
        } else {
            //是新增操作
            if($this->request->isPost()) {
                //是提交操作
                $post = $this->request->post();
                //验证  唯一规则： 表名，字段名，排除主键值，主键名
                $validate = new \think\Validate([
                    ['name', 'require', '角色名称不能为空'],
                ]);
                //验证部分数据合法性
                if (!$validate->check($post)) {
                    $this->error('提交失败：' . $validate->getError());
                }
                //验证用户名是否存在
                $name = $model->where('name',$post['name'])->find();
                if(!empty($name)) {
                    return $this->error('提交失败：该角色名已存在');
                }
                //处理选中的权限菜单id，转为字符串
                if(!empty($post['admin_menu_id'])) {
                    $post['permissions'] = implode(',',$post['admin_menu_id']);
                }
                if(false == $model->allowField(true)->save($post)) {
                    return $this->error('添加角色失败');
                } else {
                    addlog($model->id);//写入日志
                    return $this->success('添加角色成功','admin/admin/adminCate');
                }
            } else {
                //非提交操作
                $menus = Db::name('admin_menu')->select();
                $info['menu'] = $this->menulist($menus);
                //$info['menu'] = $this->menulist($info['menu']);
                $this->assign('info',$info);
                return $this->fetch();
            }
        }
    }


    public function preview()
    {
        $id = $this->request->has('id') ? $this->request->param('id', 0, 'intval') : 0;
        $model = new \app\admin\model\AdminCate();
        $info['cate'] = $model->where('id',$id)->find();
        if(!empty($info['cate']['permissions'])) {
            //将菜单id字符串拆分成数组
            $info['cate']['permissions'] = explode(',',$info['cate']['permissions']);
        }
        $menus = Db::name('admin_menu')->select();
        $info['menu'] = $this->menulist($menus);
        $this->assign('info',$info);
        return $this->fetch();
    }


    protected function menulist($menu,$id=0,$level=0){
        
        static $menus = array();
        $size = count($menus)-1;
        foreach ($menu as $value) {
            if ($value['pid']==$id) {
                $value['level'] = $level+1;
                if($level == 0)
                {
                    $value['str'] = str_repeat('',$value['level']);
                    $menus[] = $value;
                }
                elseif($level == 2)
                {
                    $value['str'] = '&emsp;&emsp;&emsp;&emsp;'.'└ ';
                    $menus[$size]['list'][] = $value;
                }
                elseif($level == 3)
                {
                    $value['str'] = '&emsp;&emsp;&emsp;&emsp;&emsp;&emsp;'.'└ ';
                    $menus[$size]['list'][] = $value;
                }
                else
                {
                    $value['str'] = '&emsp;&emsp;'.'└ ';
                    $menus[$size]['list'][] = $value;
                }
                
                $this->menulist($menu,$value['id'],$value['level']);
            }
        }
        return $menus;
    }

    /**
     * 管理员角色删除
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function adminCateDelete()
    {
        if($this->request->isAjax()) {
            $id = $this->request->has('id') ? $this->request->param('id', 0, 'intval') : 0;
            if($id > 0) {
                if($id == 1) {
                    return $this->error('超级管理员角色不能删除');
                }
                if(false == Db::name('admin_cate')->where('id',$id)->delete()) {
                    return $this->error('删除失败');
                } else {
                    addlog($id);//写入日志
                    return $this->success('删除成功','admin/admin/adminCate');
                }
            } else {
                return $this->error('id不正确');
            }
        }
    }

    /**
     * 用户禁用/解禁
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function audit()
    {
        //获取用户id
        $id = $this->request->has('id') ? $this->request->param('id', 0, 'intval') : 0;
        if($id > 0) {
            if($this->request->isPost()) {
                //是提交操作
                $post = $this->request->post();

                if(!isset($post['status'])){
                   $status = Db::name('admin')->where('id',$id)->value('status');
                   $status = ($status == 1) ? 0 : 1;
                   Db::name('admin')->where('id',$id)->update(['status'=>$status]);
                   return json(['code' => 1,'status' => $status,'msg' => '成功']);
                }

                $status = $post['status'];
                if(false == Db::name('admin')->where('id',$id)->update(['status'=>$status])) {
                    return $this->error('操作失败');
                } else {
                    addlog($id);//写入日志
                    return $this->success('操作成功','admin/admin/index');
                }
            }
        } else {
            return $this->error('id不正确');
        }
    }


    public function log()
    {
        $model = new \app\admin\model\AdminLog();

        $post = $this->request->param();
        if (isset($post['admin_menu_id']) and $post['admin_menu_id'] > 0) {
            $where['admin_menu_id'] = $post['admin_menu_id'];
        }
        
        if (isset($post['admin_id']) and $post['admin_id'] > 0) {
            $where['admin_id'] = $post['admin_id'];
        }
 
        if(isset($post['create_time']) and !empty($post['create_time'])) {
            $min_time = strtotime($post['create_time']);
            $max_time = $min_time + 24 * 60 * 60;
            $where['create_time'] = [['>=',$min_time],['<=',$max_time]];
        }
        
        $log = empty($where) ? $model->order('create_time desc')->paginate(20) : $model->where($where)->order('create_time desc')->paginate(20,false,['query'=>$this->request->param()]);
        
        $this->assign('log',$log);
        //身份列表
        $admin_cate = Db::name('admin_cate')->select();
        $this->assign('admin_cate',$admin_cate);
        $info['menu'] = Db::name('admin_menu')->select();
        $info['admin'] = Db::name('admin')->select();
        $this->assign('info',$info);
        return $this->fetch();
    }
}
