<?php
// 应用公共文件

/**
 * OBJECT对象转ARRAY数组
 * @param  [object]
 * @return [array]
 */


function object2array(&$object)
{
    $object = json_decode(json_encode($object), true);
    return $object;
}

/**
 * 根据附件表的id返回url地址
 * @param  [type] $id [description]
 * @return [type]     [description]
 */
function geturl($id)
{
    if ($id) {
        $geturl = \think\Db::name("attachment")->where(['id' => $id])->find();
        if ($geturl['status'] == 1) {
            //审核通过
            return $geturl['filepath'];
        } elseif ($geturl['status'] == 0) {
            //待审核
            return '/uploads/xitong/beiyong1.jpg';
        } else {
            //不通过
            return '/uploads/xitong/beiyong2.jpg';
        }
    }
    return false;
}


/**
 * [SendMail 邮件发送]
 * @param [type] $address  [description]
 * @param [type] $title    [description]
 * @param [type] $message  [description]
 * @param [type] $from     [description]
 * @param [type] $fromname [description]
 * @param [type] $smtp     [description]
 * @param [type] $username [description]
 * @param [type] $password [description]
 */
function SendMail($address)
{
    vendor('phpmailer.PHPMailerAutoload');
    //vendor('PHPMailer.class#PHPMailer');
    $mail = new \PHPMailer();
    // 设置PHPMailer使用SMTP服务器发送Email
    $mail->IsSMTP();
    // 设置邮件的字符编码，若不指定，则为'UTF-8'
    $mail->CharSet = 'UTF-8';
    // 添加收件人地址，可以多次使用来添加多个收件人
    $mail->AddAddress($address);

    $data = \think\Db::name('admin_emailconfig')->where('email', 'email')->find();
    $title = $data['title'];
    $message = $data['content'];
    $from = $data['from_email'];
    $fromname = $data['from_name'];
    $smtp = $data['smtp'];
    $username = $data['username'];
    $password = $data['password'];
    // 设置邮件正文
    $mail->Body = $message;
    // 设置邮件头的From字段。
    $mail->From = $from;
    // 设置发件人名字
    $mail->FromName = $fromname;
    // 设置邮件标题
    $mail->Subject = $title;
    // 设置SMTP服务器。
    $mail->Host = $smtp;
    // 设置为"需要验证" ThinkPHP 的config方法读取配置文件
    $mail->SMTPAuth = true;
    //设置html发送格式
    $mail->isHTML(true);
    // 设置用户名和密码。
    $mail->Username = $username;
    $mail->Password = $password;
    // 发送邮件。
    return ($mail->Send());
}


/**
 * 阿里大鱼短信发送
 * @param [type] $appkey    [description]
 * @param [type] $secretKey [description]
 * @param [type] $type      [description]
 * @param [type] $name      [description]
 * @param [type] $param     [description]
 * @param [type] $phone     [description]
 * @param [type] $code      [description]
 * @param [type] $data      [description]
 */
function SendSms($param, $phone)
{
    // 配置信息
    import('dayu.top.TopClient');
    import('dayu.top.TopLogger');
    import('dayu.top.request.AlibabaAliqinFcSmsNumSendRequest');
    import('dayu.top.ResultSet');
    import('dayu.top.RequestCheckUtil');

    //获取短信配置
    $data = \think\Db::name('admin_smsconfig')->where('sms', 'sms')->find();
    $appkey = $data['appkey'];
    $secretkey = $data['secretkey'];
    $type = $data['type'];
    $name = $data['name'];
    $code = $data['code'];

    $c = new \TopClient();
    $c->appkey = $appkey;
    $c->secretKey = $secretkey;

    $req = new \AlibabaAliqinFcSmsNumSendRequest();
    //公共回传参数，在“消息返回”中会透传回该参数。非必须
    $req->setExtend("");
    //短信类型，传入值请填写normal
    $req->setSmsType($type);
    //短信签名，传入的短信签名必须是在阿里大于“管理中心-验证码/短信通知/推广短信-配置短信签名”中的可用签名。
    $req->setSmsFreeSignName($name);
    //短信模板变量，传参规则{"key":"value"}，key的名字须和申请模板中的变量名一致，多个变量之间以逗号隔开。
    $req->setSmsParam($param);
    //短信接收号码。支持单个或多个手机号码，传入号码为11位手机号码，不能加0或+86。群发短信需传入多个号码，以英文逗号分隔，一次调用最多传入200个号码。
    $req->setRecNum($phone);
    //短信模板ID，传入的模板必须是在阿里大于“管理中心-短信模板管理”中的可用模板。
    $req->setSmsTemplateCode($code);
    //发送


    $resp = $c->execute($req);
}


/**
 * 替换手机号码中间四位数字
 * @param  [type] $str [description]
 * @return [type]      [description]
 */
function hide_phone($str)
{
    $resstr = substr_replace($str, '****', 3, 4);
    return $resstr;
}

/**
 * 分类树function
 * @return [type] [description]
 */
function tree($data, $pid = 0,$level = 1)
{
    static $treeList = array();
    foreach ($data as $v) {
        if ($v['pid'] == $pid) {
            $v['level']=$level;
            $treeList[] = $v;//将结果装到$treeList中
            tree($data, $v['id'],$level+1);
        }
    }
    return $treeList;
}

//获取后缀名
function get_extension($file)
{
    return substr(strrchr($file, '.'), 1);
}

//调用MS office DCOM 将文件转换为pdf， 使用pdf.js预览， 要求服务器安装MS office 较高版本，Linux环境下需改用LebreOffice或openOffice
function ppt_to_pdf($path)
{
    $srcfilename = ROOT_PATH . 'public' . $path;
    $filepath = '/uploads/temp/' . basename($path);
    $destfilename = ROOT_PATH . 'public' . $filepath;
    try {
        if (!file_exists($srcfilename)) {
            return json(['code' => 0, 'msg' => '文件不存在']);
        }
        $ppt = new \COM("powerpoint.application") or die("Unable to instantiate Powerpoint");
        $presentation = $ppt->Presentations->Open($srcfilename, false, false, false);
        if (file_exists($destfilename . '.pdf')) {
            unlink($destfilename . '.pdf');
        }
        $presentation->SaveAs($destfilename, 32, 1);
        $presentation->Close();
        $ppt->Quit();
        return json(['code' => 1, 'msg' => '', 'data' => $filepath]);
    } catch (\Exception $e) {
        if (method_exists($ppt, "Quit")) {
            $ppt->Quit();
        }
        return json(['code' => 0, 'msg' => '未知错误']);
    }
}

/**
 * excel转pdf
 * @param $path 文件路径
 * @return \think\response\Json json数据，可直接返回
 */
function excel_to_pdf($path)
{
    $srcfilename = ROOT_PATH . 'public' .$path;
    $filepath = '/uploads/temp/' . basename($path);
    $destfilename = ROOT_PATH . 'public' . $filepath;
    try {
        if (!file_exists($srcfilename)) {
            return json(['code' => 0, 'msg' => '文件不存在']);
        }
        $excel = new \COM("excel.application") or die("Unable to instantiate excel");
        $workbook = $excel->Workbooks->Open($srcfilename, null, false, null, "1", "1", true);
        if (file_exists($destfilename . '.pdf')) {
            unlink($destfilename . '.pdf');
        }
        $workbook->ExportAsFixedFormat(0, $destfilename);
        $workbook->Close();
        $excel->Quit();
        return json(['code' => 1, 'msg' => '', 'data' => $filepath]);
    } catch (\Exception $e) {
        echo("src:$srcfilename catch exception:" . $e->__toString());
        if (method_exists($excel, "Quit")) {
            $excel->Quit();
        }
        return json(['code' => 0, 'msg' => '未知错误']);
    }
}

function doc_to_pdf($path)
{
    $srcfilename = ROOT_PATH . 'public' .$path;
    $filepath = '/uploads/temp/' . basename($path);
    $destfilename = ROOT_PATH . 'public' . $filepath;
    try {
        if (!file_exists($srcfilename)) {
            return json(['code' => 0, 'msg' => '文件不存在']);
        }
        $srcfilename = iconv('gb2312','utf-8',$srcfilename);
        $word = new \COM("word.application") or die("Can't start Word!");
        $word->Visible = 0;
        $word->Documents->Open($srcfilename, false, false, false, "1", "1", true);
        if (file_exists($destfilename . '.pdf')) {
            unlink($destfilename . '.pdf');
        }

        $word->ActiveDocument->final = false;
        $word->ActiveDocument->Saved = true;
        $word->ActiveDocument->ExportAsFixedFormat(
            $destfilename . '.pdf',
            17,                         // wdExportFormatPDF
            false,                      // open file after export
            0,                          // wdExportOptimizeForPrint
            3,                          // wdExportFromTo
            1,                          // begin page
            5000,                       // end page
            7,                          // wdExportDocumentWithMarkup
            true,                       // IncludeDocProps
            true,                       // KeepIRM
            1                           // WdExportCreateBookmarks
        );
        $word->ActiveDocument->Close();
        $word->Quit();
        return json(['code' => 1, 'msg' => '', 'data' => $filepath]);
    } catch (\Exception $e) {
        if (method_exists($word, "Quit")) {
            $word->Quit();
        }
        return json(['code' => 0, 'msg' => '未知错误:'.$e->getMessage()]);
    }

}

/**
 * 数组分页函数 核心函数 array_slice
 * 用此函数之前要先将数据库里面的所有数据按一定的顺序查询出来存入数组中
 * $count  每页多少条数据
 * $page  当前第几页
 * $array  查询出来的所有数组
 * $order 0 - 不变   1- 反序
 */
function page_array($count,$page,$array,$order){
    global $countpage; #定全局变量
    $page=(empty($page))?'1':$page; #判断当前页面是否为空 如果为空就表示为第一页面
    $start=($page-1)*$count; #计算每次分页的开始位置
    if($order==1){
        $array=array_reverse($array);
    }
    $totals=count($array);
    $countpage=ceil($totals/$count); #计算总页面数
    $pagedata=array();
    $pagedata=array_slice($array,$start,$count);
    return $pagedata; #返回查询数据
}