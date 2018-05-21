var uploader;
var fileIds = [];   //新增弹层已上传文件ID
var major_key = ''; //编辑表格当前行ID
var income = {} //收件人信息


//发文状态表格
tableItem = $("#tableIncome").DataTable({
    processing: true,
    serverSide: true,
    ordering: false,
    ajax: {
        "url": "/archive/common/datatablesPre?tableName=archive_income_send&table_type=2",
    },
    columns: [
        { name: "file_name" },
        { name: "date" },
        { name: "unit_name" },
        { name: "attchment_id"},
        { name: "income_name" },
        { name: "status" },
        { name: "id" }
    ],
    columnDefs: [
        {
            targets: [5],
            render: function (data, type, row,meta) {
                if (data == '1'){
                    return  '未发送';
                }else if(data == '2'){
                    return  '已发送';
                }else if(data == '3'){
                    return  '已签收';
                }else{
                    return  '已拒收';
                }
            }
        },
        {
            targets: [6],
            render: function (data, type, row,meta) {
                if (data == '1'){
                    return  '<a title="' + data + '" class="layui-btn layui-btn-sm" href="javascript:void(0);" major_key="'+row[6]+'" onclick="edit_send(this)">编辑</a><a title="' + data + '" class="layui-btn layui-btn-primary layui-btn-sm" href="javascript:void(0);" major_key="'+row[6]+'" onclick="del(this)">删除</a>';
                }else {
                    return  '<a title="' + data + '"  href="javascript:void(0);" major_key="'+row[6]+'" onclick="preview(this)">查看</a>';
                }
            }
        }
        ],
    dom: 'fr<"#addSend.mybtn layui-btn layui-btn-md">tp',
    language: {
        "sProcessing":"数据加载中...",
        "lengthMenu": "_MENU_",
        "zeroRecords": "没有找到记录",
        "info": "第 _PAGE_ 页 ( 总共 _PAGES_ 页 )",
        "infoEmpty": "无记录",
        "search": "搜索：",
        "infoFiltered": "(从 _MAX_ 条记录过滤)",
        "paginate": {
            "sFirst": "<<",
            "sPrevious": "<",
            "sNext": ">",
            "sLast": ">>"
        }
    }
});
$('#addSend').html('新增');

//新增弹层
$("#table_content").on("click","#addSend",function () {
    layer.open({
        type: 1,
        title:'新增',
        area:["800px","620px"],
        content: $('#add_file_modal'),
        success:function () {
            $.viewFilter(true);
            //重置上传按钮
            $('.webuploader-pick').next('div').css({
                width:'86px',
                height:'36px'
            });
        }
    });
})

//文件日期
layui.use('laydate', function(){
    var laydate = layui.laydate;
    laydate.render({
        elem: '#date'
    });
});

//文件上传
uploader = WebUploader.create({
    auto: true,// 选完文件后，是否自动上传。
    swf: '/static/admin/webupload/Uploader.swf',// swf文件路径
    server: "/archive/common/upload?module=archive&use=send",// 文件接收服务端。
    chunked: true,
    duplicate :true,// 重复上传图片，true为可重复false为不可重复
    pick: {
        multiple: true,
        id: "#file_upload",
        innerHTML: "文件上传"
    }
});

// 文件上传成功
uploader.on( 'uploadSuccess', function( file,res ) {
    uploader = null;
    $("#file_per").empty();

    var fileId = res.id;
    var fileLists = [];
    fileIds.push(fileId);
    fileLists.push('<tr>');
    fileLists.push('<td>'+ file.name +'</td>');
    fileLists.push('<td>');
    fileLists.push('<a href="javascript:;" onclick="fileDownload(this)" uid='+ fileId +' name='+ file.name +'>下载</a>');
    fileLists.push('<a href="javascript:;" onclick="attachmentPreview(this)" uid='+ fileId +' name='+ file.name +'>查看</a>');
    fileLists.push('<a href="javascript:;" onclick="attachmentDel(this)" uid='+ fileId +' name='+ file.name +'>删除</a>');
    fileLists.push('</td>');
    fileLists.push('</tr>');
    $('#add_table_files tbody').prepend(fileLists.join(''));
    console.log(fileIds);
});

// 文件上传失败，显示上传出错。
uploader.on( 'uploadError', function( file ,data) {
    var msg = file.name + data.info;
    layer.confirm(msg, function(index){
        layer.close(index);
    });
    $("#file_per").empty();
});

//附件下载
function fileDownload(that) {
    var uid = $(that).attr('uid');
}

//附件查看
function attachmentPreview(that) {
    var uid = $(that).attr('uid');
    var name = $(that).attr('name');
    $.ajax({
        url: './attachmentPreview',
        type: "post",
        data: {
            file_id:uid
        },
        success: function (res) {
            console.log(res);
            if(res.code === 1){
                var path = res.path;
                var houzhui = res.path.split(".");
                if(houzhui[houzhui.length-1]=="pdf"){
                    window.open("/static/public/web/viewer.html?file=../../../" + path,"_blank");
                }else{
                    layer.photos({
                        photos: {
                            "title": "", //相册标题
                            "id": uid, //相册id
                            "start": 0, //初始显示的图片序号，默认0
                            "data": [   //相册包含的图片，数组格式
                                {
                                    "alt": name,
                                    "pid": uid, //图片id
                                    "src": "../../../"+res.path, //原图地址
                                    "thumb": "" //缩略图地址
                                }
                            ]
                        }
                        ,anim: Math.floor(Math.random()*7) //0-6的选择，指定弹出图片动画类型，默认随机（请注意，3.0之前的版本用shift参数）
                        ,success:function () {
                            $('.layui-layer-shade').empty();
                        }
                    });
                }

            }else {
                layer.msg(res.msg);
            }
        }
    })
}

//附件删除
function attachmentDel(that) {
    var uid = $(that).attr('uid');
    var parents = $(that).parents('tr');
    $.ajax({
        url: "./attachmentDel",
        type: "post",
        data: {
            file_id:uid
        },
        dataType: "json",
        success: function (res) {
            fileIds.remove(uid);
            parents.remove();
            layer.msg(res.msg);
        }
    });
}

//保存发文
layui.use('form', function(){
    var form = layui.form;
    //保存
    form.on('submit(save)', function(data){
        data.field.status = 1;
        saveInter(data);
        return false;
    });
    //保存并发送
    form.on('submit(saveAndSend)', function(data){
        data.field.status = 2;
        saveInter(data);
        return false;
    });
});

//保存接口
function saveInter(data) {
    data.field.file_ids = fileIds;
    data.field.major_key = major_key;
    $.ajax({
        url: "./send",
        type: "post",
        data: data.field,
        dataType: "json",
        success: function (res) {
            layer.closeAll('page');
            layer.msg(res.msg);
            major_key = '';
            tableItem.ajax.url("/archive/common/datatablesPre?tableName=archive_income_send&table_type=2").load();
        }
    });
}

//返回
$('#back').click(function () {
    layer.closeAll();
    $('#add_table_files tbody').empty();
    major_key = '';
});

//编辑
function edit_send(that) {
    major_key = $(that).attr('major_key');
    layer.open({
        type: 1,
        title:'查看',
        area:["800px","620px"],
        content: $('#add_file_modal'),
        success:function () {
            $.viewFilter(true);
        }
    });
}

//查看
function preview (that) {
    layer.open({
        type: 1,
        title:'查看',
        area:["800px","620px"],
        content: $('#add_file_modal'),
        success:function () {
            $.viewFilter(false);
        }
    });
}

//收件人弹层
$('#income_name').focus(function () {
   layer.open({
           title:'收件人',
           id:'1',
           type:'1',
           area:['1024px','600px'],
           content:$('#incomeNameLayer'),
           btn:['保存'],
           success:function () {
               incomeZtree();
           },
           yes:function () {
               save();
               layer.close(layer.index);
           },
           cancel: function(index, layero){
               layer.close(layer.index);
           }
       });
});

//收件人树
function incomeZtree() {
    $.ztree({
        ajaxUrl:'/admin/admin/index',
        zTreeOnClick:function (event, treeId, treeNode) {
            incomeInfo();
            tableItem.ajax.url("/admin/common/datatablesPre?tableName=admin&id="+ treeNode.id).load();
        }
    });
}

//收件人信息
function incomeInfo() {
    $.datatable({
        ajax: {
            "url":"/admin/common/datatablesPre?tableName=admin"
        },
        columns:[
            {
                name: "id",
                "render": function(data, type, full, meta) {
                    console.log(full);
                    var ipt = "<input type='radio' name='checkList' idv="+ data +" unit="+ full[1] +" nickname="+ full[2] +" onclick='getSelectId(this)'>";
                    return ipt;
                },
            },
            {
                name: "name"
            },
            {
                name: "nickname"
            },
            {
                name: "mobile"
            },
            {
                name: "position"
            }
        ]
    });
}

//选择收件人
function getSelectId(that) {
    var idv = $(that).attr('idv');
    var unit = $(that).attr('unit');
    var nickname = $(that).attr('nickname');
    income.id = idv
    income.unit = unit;
    income.nickname = nickname;
}

//保存已选收件人
function save() {
    $('#income_id').val(income.id);
    $('#income_name').val(income.nickname);
    $('#unit_name').val(income.unit);
}