<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/3/29
 * Time: 9:19
 */
/**
 * 图纸文档管理，图册管理
 * Class Atlas
 * @package app\archive\controller
 */
namespace app\archive\controller;
use app\admin\controller\Permissions;
use app\archive\model\AtlasCateTypeModel;//左侧节点树
use app\archive\model\AtlasCateModel;//右侧图册类型
use app\archive\model\AtlasDownloadModel;//下载记录
use app\admin\model\Admin as adminModel;//管理员模型
use app\admin\model\AdminGroup;//组织机构
use \think\Db;
use \think\Session;

class Atlas extends Permissions
{
    /**
     * 模板首页
     * @return mixed
     */
    public function index()
    {
        return $this->fetch();
    }
    /**********************************左侧图册类型树************************/
    /**
     * 图册分类树
     * @return mixed|\think\response\Json
     */
    public function atlastree()
    {
        // 获取左侧的树结构
        if(request()->isAjax()){
            $node = new AtlasCateTypeModel();
            $nodeStr = $node->getNodeInfo();
            return json($nodeStr);
        }
    }

    /**
     * 新增 或者 编辑 图册类型的节点树
     * @return mixed|\think\response\Json
     */
    public function editCatetype()
    {
        if(request()->isAjax()){
            $model = new AtlasCateTypeModel();
            $param = input('post.');
            /**
             * 前台需要传递的是 pid 父级节点编号,id图册类型树自增,name图册节点树分类名
             */
            if(empty($param['id']))//id为空时表示新增图册类型节点
            {
                $data = [
                    'pid' => $param['pid'],
                    'name' => $param['name']
                ];
                $flag = $model->insertCatetype($data);
                return json($flag);
            }else{
                $data = [
                    'id' => $param['id'],
                    'name' => $param['name']
                ];
                $flag = $model->editCatetype($data);
                return json($flag);
            }
        }
    }

    /**
     * 删除图册类型的节点树
     * @return \think\response\Json
     */
    public function delCatetype()
    {
        if(request()->isAjax()){
            //实例化图册类型AtlasCateTypeModel
            $model = new AtlasCateTypeModel();
            $catemodel = new AtlasCateModel();
            $down = new AtlasDownloadModel;
            $param = input('post.');
            //删除图册图片
            //根据节点id查询图片路径
            $data = $catemodel->getpicinfo($param['id']);
            if($data)
            {
                foreach ($data as $k=>$v)
                {
                    $attachment = Db::name("attachment")->where("id",$v["attachmentId"])->find();
                    $path = "." .$attachment['filepath'];
                    $pdf_path = './uploads/temp/' . basename($path) . '.pdf';
                    if($attachment['filepath'])
                    {
                        if(file_exists($path)){
                            unlink($path); //删除上传的图片或文件
                        }
                        if(file_exists($pdf_path)){
                            unlink($pdf_path); //删除生成的预览pdf
                        }
                    }
                    //删除attachment表中对应的记录
                    Db::name('attachment')->where("id",$v["attachmentId"])->delete();
                }
            }
            //根据传过来的节点id删除图册
            $catemodel->delselfidCate($param['id']);

            //根据类型树id删除下载记录
            $down->delselfidall($param['id']);

            //最后删除图册类型树节点

            $flag = $model->delCatetype($param['id']);
            return json($flag);
        }
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

                Db::name('archive_atlas_cate_type')->where('id', $select_id)->update(['sort_id' => $change_sort_id]);
                Db::name('archive_atlas_cate_type')->where('id', $change_id)->update(['sort_id' => $select_sort_id]);

                return json(['code' => 1,'msg' => '移动成功']);

            }catch (PDOException $e){
                return ['code' => -1,'msg' => $e->getMessage()];
            }
        }
    }
    /**********************************右侧图册表************************/
    /*
     * 获取一条图册信息
     */
    public function getindex()
    {
        if(request()->isAjax()){
            $model = new AtlasCateModel();
            $param = input('post.');
            $data = $model->getOne($param['id']);
            return json(['code'=> 1, 'data' => $data]);
        }
    }

    /**
     * 获取标段信息
     */
    public function getAllsecname()
    {
        $data = Db::name("section")->field("name")->select();
        return json(['code'=>1,'data'=>$data]);
    }

    /**
     * 新增/编辑图册
     * @return mixed|\think\response\Json
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function editAtlasCate()
    {
        if(request()->isAjax()){
            //实例化模型类
            $model = new AtlasCateModel();
            $param = input('post.');

            //首先查询选择的图册节点树的id，在此分类下的最大的序号cate_number
            //然后在此基础上自动加1
            $max_cate_number = $model->maxcatenumber($param['selfid']);

            //前台传过来的角色类型id
            if(empty($param['id']))//id为空时表示新增图册类型
            {
                $data = [
                    'selfid' => $param['selfid'],//admin_cate_type表中的id,区分图册节点树
                    'cate_number' => $max_cate_number + 1,//序号
                    'picture_number' => $param['picture_number'],//图号
                    'picture_name' => $param['picture_name'],//图名
                    'picture_papaer_num' => $param['picture_papaer_num'],//图纸张数(输入数字)
                    'a1_picture' => $param['a1_picture'],//折合A1图纸
                    'design_name' => $param['design_name'],//设计
                    'check_name' => $param['check_name'],//校验
                    'examination_name' => $param['examination_name'],//审查
                    'completion_date' => $param['completion_date'],//完成日期
                    'section' => $param['section'],//标段
                    'paper_category' => $param['paper_category'],//图纸类别
                    'owner' => Session::get('current_nickname'),//上传人
                    'date' => date("Y-m-d")//上传日期
                ];
                $flag = $model->insertCate($data);
                return json($flag);
            }else{
                $data = [
                    'id' => $param['id'],//图册类型表自增id
                    'picture_number' => $param['picture_number'],//图号
                    'picture_name' => $param['picture_name'],//图名
                    'picture_papaer_num' => $param['picture_papaer_num'],//图纸张数(输入数字)
                    'a1_picture' => $param['a1_picture'],//折合A1图纸
                    'design_name' => $param['design_name'],//设计
                    'check_name' => $param['check_name'],//校验
                    'examination_name' => $param['examination_name'],//审查
                    'completion_date' => $param['completion_date'],//完成日期
                    'section' => $param['section'],//标段
                    'paper_category' => $param['paper_category']//图纸类别
                ];
                $flag = $model->editCate($data);
                return json($flag);
            }
        }
    }

    /**
     * 删除一条图册信息
     */
    public function delCateone()
    {
            if(request()->isAjax()){
                //实例化model类型
                $model = new AtlasCateModel();
                $param = input('post.');
                //首先判断一下删除的该图册是否存在下级
                $info = $model ->judge($param['id']);
                if(empty($info))//没有下级直接删除
                {
                    $data = $model->getOne($param['id']);
                    if($data["attachmentId"])
                    {
                        //先删除图片
                        //查询attachment表中的文件上传路径
                        $attachment = Db::name("attachment")->where("id",$data["attachmentId"])->find();
                        $path = "." .$attachment['filepath'];
                        $pdf_path = './uploads/temp/' . basename($path) . '.pdf';

                        if(file_exists($path)){
                            unlink($path); //删除文件图片
                        }

                        if(file_exists($pdf_path)){
                            unlink($pdf_path); //删除生成的预览pdf
                        }

                        //删除attachment表中对应的记录
                        Db::name('attachment')->where("id",$data["attachmentId"])->delete();

                        //清除下载记录
                        $down = new AtlasDownloadModel();
                        $down->deldownloadall($param['id']);
                    }
                    $flag = $model->delCate($param['id']);
                    return $flag;
                }else
                {
                    return ['code' => -1, 'msg' => '当前图册下已有图纸，请先删除图纸！'];
                }
            }
    }

    /**
     * 上传图纸
     * @return \think\response\Json
     */
    public function addPicture()
    {
        if(request()->isAjax()){
            //实例化模型类
            $model = new AtlasCateModel();
            $param = input('post.');
            $id = $param['id'];//图册id

            $info = $model->getOne($id);
                $data = [
                    'attachmentId'=>$param['attachmentId'],//文件关联attachment表中的id
                    'selfid' => $info['selfid'],//admin_cate_type表中的id,区分图册节点树
                    'pid' => $id,//pid为父级id
                    'picture_number' => $param['picture_number'],//图号
                    'picture_name' => $param['picture_name'],//图名
                    'picture_papaer_num' => 1,//图纸张数(输入数字),默认1
                    'completion_date' => date("Y-m"),//完成日期
                    'paper_category' => $info['paper_category'],//图纸类别
                    'owner' => Session::get('current_nickname'),//上传人
                    'filename' => $param['filename'],
                    'date' => date("Y-m-d")//上传日期
                ];
                $flag = $model->insertCate($data);
                return json($flag);
        }
    }

    /**
     * 获取所有的下载记录信息
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getAlldownrec()
    {
        if(request()->isAjax()){
            //实例化模型类
            $model = new AtlasDownloadModel;
            $id = input('param.id');
            $data = $model->getall($id);
            return json(['code' => 1, 'data' => $data]);
        }
    }

    /**
     * 预览一条图册图片信息
     * @return \think\response\Json
     */
    public function atlascatePreview()
    {
        if(request()->isAjax()){
            //实例化模型类
            $model = new AtlasCateModel();
            $param = input('post.');
            $code = 1;
            $msg = '预览成功';
            $data = $model->getOne($param['id']);

            //查询attachment表中的文件上传路径
            $attachment = Db::name("attachment")->where("id",$data["attachmentId"])->find();
            $path = "." .$attachment['filepath'];

            $extension = strtolower(get_extension(substr($path,1)));
            $pdf_path = './uploads/temp/' . basename($path) . '.pdf';
            if(!file_exists($pdf_path)){
                if($extension === 'doc' || $extension === 'docx' || $extension === 'txt'){
                    doc_to_pdf($path);
                }else if($extension === 'xls' || $extension === 'xlsx'){
                    excel_to_pdf($path);
                }else if($extension === 'ppt' || $extension === 'pptx'){
                    ppt_to_pdf($path);
                }else if($extension === 'pdf'){
                    $pdf_path = $path;
                }else if($extension === "jpg" || $extension === "png" || $extension === "jpeg"){
                    $pdf_path = $path;
                }else {
                    $code = 0;
                    $msg = '不支持的文件格式';
                }
                return json(['code' => $code, 'path' => substr($pdf_path,1), 'msg' => $msg]);
            }else{
                return json(['code' => $code,  'path' => substr($pdf_path,1), 'msg' => $msg]);
            }
        }
    }

    /**
     * 下载一条图册文件图片
     * @return \think\response\Json
     */
    public function atlascateDownload()
    {
        if(request()->isAjax()){
            //实例化模型类
            $model = new AtlasCateModel();
            $id = input('param.id');

            //查看文件路径是否存在
            $param = $model->getOne($id);
            //查询attachment文件上传表中的文件上传路径
            $attachment = Db::name("attachment")->where("id", $param["attachmentId"])->find();
            //上传文件路径
            $path = $attachment["filepath"];
            if(!$path || !file_exists("." .$path)){
                return json(['code' => '-1','msg' => '文件不存在']);
            }
            //查询当前用户是否被禁用下载图册
            $blacklist = $model->getbalcklist($id);
            if($blacklist['blacklist'])
            {
                $list = explode(",",$blacklist['blacklist']);
                if(!(in_array(Session::get('current_id'),$list)))
                {
                    return json(['code' => -1, 'msg' => "没有下载权限"]);
                }else
                {
                    return json(['code' => 1]);
                }
            }else
            {
                return json(['code' => 1]);
            }

        }
        $id = input('param.id');
        $download = new AtlasDownloadModel();
        $model = new AtlasCateModel();
        $param = $model->getOne($id);
        $data = [
            "selfid" => $param['selfid'],
            "cate_id" => $id,
            "date" => date("Y-m-d H:i:s"),//下载时间
            "user_name" => Session::get('current_nickname')//下载人
        ];

        $download->insertDownload($data);

            // 前台需要 传递 文件编号 id
            //查询attachment文件上传表中的文件上传路径
            $attachment = Db::name("attachment")->where("id", $param["attachmentId"])->find();
            //上传文件路径
            $path = $attachment["filepath"];
            $filePath = '.' . $path;
            $fileName = $param['filename'];
            $file = fopen($filePath, "r"); //   打开文件
            //输入文件标签
            $fileName = iconv("utf-8","gb2312",$fileName);
            Header("Content-type:application/octet-stream ");
            Header("Accept-Ranges:bytes ");
            Header("Accept-Length:   " . filesize($filePath));
            Header("Content-Disposition:   attachment;   filename= " . $fileName);
            //   输出文件内容
            echo fread($file, filesize($filePath));
            fclose($file);
            exit;
    }

    /**
     * 图册打包下载
     */
    public function allDownload()
    {
        if(request()->isAjax()){
            //实例化模型类
            $model = new AtlasCateModel();
            $id = input('param.id');

            //查询当前用户是否被禁用下载图册
            $blacklist = $model->getbalcklist($id);
            if($blacklist['blacklist'])
            {
                $list = explode(",",$blacklist['blacklist']);
                if(!(in_array(Session::get('current_id'),$list)))
                {
                    return json(['code' => -1, 'msg' => "没有下载权限"]);
                }else
                {
                    $res = $model->getpic($id);
                    if(!$res['pid'])
                    {
                        return json(['code' => -1,'msg'=>"当前图册下没有图纸文件！"]);
                    }else
                    {
                        return json(['code' => 1]);
                    }
                }
            }else
            {
                $res = $model->getpic($id);

               if(!$res['pid'])
               {
                   return json(['code' => -1,'msg'=>"当前图册下没有图纸文件！"]);
               }else
               {
                   return json(['code' => 1]);
               }
            }
        }
        //获取文件列表
        $id = input('param.id');
        //添加下载记录
        $download = new AtlasDownloadModel();
        $AtlasCate = new AtlasCateModel();
        $param = $AtlasCate->getOne($id);
        $data = [
            "selfid" => $param['selfid'],
            "cate_id" => $id,
            "date" => date("Y-m-d H:i:s"),//下载时间
            "user_name" => Session::get('current_nickname')//下载人
        ];

        $download->insertDownload($data);
        //实例化模型类
        $model = new AtlasCateModel();
        $allattachmentId = $model->getallattachmentId($id);
        //定义一个空数组
        $datalist = array();
        foreach($allattachmentId as $k=>$v)
        {
            //查询attachment文件上传表中的文件上传路径
            $attachment = Db::name("attachment")->where("id", $v["attachmentId"])->find();
            //上传文件路径
            $datalist[] = $attachment;

        }

        $zip = new \ZipArchive;
        //压缩文件名
        $newname = iconv("utf-8", "GB2312//IGNORE", $param["picture_name"]);
        $zipName =  ROOT_PATH . 'public' .DS .'uploads/atlas/atlas_thumb/'.$newname.'.zip';

        //新建zip压缩包
        if ($zip->open($zipName, \ZIPARCHIVE::CREATE)==TRUE) {

            foreach($datalist as $key=>$val){
                //$attachfile = $attachmentDir . $val['filepath']; //获取原始文件路径
                if(file_exists('.'.$val['filepath'])){
                    //addFile函数首个参数如果带有路径，则压缩的文件里包含的是带有路径的文件压缩
                    //若不希望带有路径，则需要该函数的第二个参数
                    $zip->addFile('.'.$val['filepath'], basename('.'.$val['filepath']));//第二个参数是放在压缩包中的文件名称，如果文件可能会有重复，就需要注意一下
                }
            }
        }

        //打包zip
        $zip->close();

        if(!file_exists($zipName)){
            exit("无法找到文件"); //即使创建，仍有可能失败
        }
        //如果不要下载，下面这段删掉即可，如需返回压缩包下载链接，只需 return $zipName;
        header("Cache-Control: public");
        header("Content-Description: File Transfer");
        header('Content-disposition: attachment; filename='.basename($zipName)); //文件名
        header("Content-Type: application/zip"); //zip格式的
        header("Content-Transfer-Encoding: binary"); //告诉浏览器，这是二进制文件
        header('Content-Length: '. filesize($zipName)); //告诉浏览器，文件大小
        @readfile($zipName);
        //最后删除指定位置的下载压缩包，防止文件重复
        unlink($zipName);
    }

    /**********************************下载白名单************************/
    /**
     * 白名单首页
     */
    public function addBlacklist()
    {
        return $this->fetch();
    }

    /**
     * 根据图册信息查询该图册下所有的白名单用户
     * @return \think\response\Json
     */
    public function getAdminname()
    {
        if(request()->isAjax()){
            //实例化模型类
            $model = new AtlasCateModel();
            $admin = new adminModel;
            $id = input('post.id');
            //定义一个空数组
            $res = array();
            //先查询admin表中的所有的admin_cate_id
            $datainfo = $model->getOne($id);

            if($datainfo['blacklist'])
            {
                $blacklist = explode(",",$datainfo['blacklist']);

                foreach ($blacklist as $v)
                {
                    $res[] = $admin->getadmininfo($v);
                }

                return json(['code' => 1, 'data' => $res]);//返回json数据
            }else
            {
                return json(['code' => -1, 'msg' => "没有白名单用户！"]);//返回json数据
            }
        }
    }

    /**
     * 删除下载白名单下的用户
     * @return \think\response\Json
     */
    public function delAdminname()
    {
        if(request()->isAjax()){
            //实例化model类型
            $model = new AtlasCateModel();
            $param = input('post.');
            $flag = $model->delblacklist($param);
            return json($flag);
        }
    }

    /**
     * 获取 组织机构 左侧的树结构
     * @return mixed|\think\response\Json
     */
    public function getOrganization()
    {
        if(request()->isAjax()){
            // 获取左侧的树结构
            $model = new AdminGroup();
            //定义一个空的字符串
            $str = "";
            $data = $model->getall();
            $res = tree($data);

            foreach ((array)$res as $k => $v) {

                $v['id'] = strval($v['id']);
                $v['pid'] = strval($v['pid']);
                $res[$k] = json_encode($v);
            }

            $user = Db::name('admin')->field('id,admin_group_id,nickname')->select();
            if(!empty($user))//如果$user不为空时
            {
                foreach((array)$user as $key=>$vo){
                    $id = $vo['id'] + 10000;
                    $str .= '{ "id": "' . $id . '", "pid":"' . $vo['admin_group_id'] . '", "name":"' . $vo['nickname'].'"';
                    $str .= '}*';
                }
                $str = substr($str, 0, -1);

                $str = explode("*",$str);

                //$res,$str这两个数组都存在时，才可以合并

                if($res && $str)
                {
                    $merge = array_merge($res,$str);
                }

                return json($merge);
            }
        }
    }

    /**
     * 添加用户到白名单
     * @return \think\response\Json
     */
    public function addAdminname()
    {
        if(request()->isAjax()){
            //实例化模型类
            $model = new AtlasCateModel();
            $param = input('post.');//需要前台传过来用户数组admin_id,cate表中的id
            $data = $model->insertAdminid($param);
            return json($data);
        }
    }
}