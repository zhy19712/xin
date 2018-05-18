var uploader;
/*$.datatable({
    tableId:'tableIncome',
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
            targets: [3],
            render: function (data, type, row,meta) {
                return  'admin';
            }
        },
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
                    return  '<a title="' + data + '" class="layui-btn layui-btn-normal layui-btn-sm" href="javascript:void(0);" data-fileId="'+row[7]+'" onclick=\"incomeShow('+row[6]+')\">编辑</a><a title="' + data + '" class="layui-btn layui-btn-primary layui-btn-sm" href="javascript:void(0);" data-fileId="'+row.id+'" onclick=\"incomeShow(this)\">删除</a>';
                }else {
                    return  '<a title="' + data + '"  href="javascript:void(0);" data-fileId="'+row[7]+'" onclick=\"incomeShow(this)\">查看</a>';
                }
            }
        }
    ],
    dom: 'fr<"#addSend layui-btn layui-btn-normal layui-btn-md">tp',
});*/

$("#tableIncome").DataTable({
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
                    return  '<a title="' + data + '" class="layui-btn layui-btn-sm" href="javascript:void(0);" data-fileId="'+row[7]+'" onclick="incomeShow('+row[6]+')">编辑</a><a title="' + data + '" class="layui-btn layui-btn-primary layui-btn-sm" href="javascript:void(0);" data-fileId="'+row.id+'" onclick=\"incomeShow(this)\">删除</a>';
                }else {
                    return  '<a title="' + data + '"  href="javascript:void(0);" data-fileId="'+row[7]+'" onclick="incomeShow(this)">查看</a>';
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
$("#table_content").on("click","#addSend",function () {
    layer.open({
        type: 1,
        title:'新增',
        area:["800px","620px"],
        content: $('#add_file_modal'),
        success:function () {
            //重置上传按钮
            $('.webuploader-pick').next('div').css({
                width:'86px',
                height:'36px'
            });
        }
    });
})
//文件上传
uploader = WebUploader.create({
    auto: true,// 选完文件后，是否自动上传。
    swf: '/static/admin/webupload/Uploader.swf',// swf文件路径
    server: "/archive/common/upload?module=archive&use=send",// 文件接收服务端。
    chunked: false,
    duplicate :true,// 重复上传图片，true为可重复false为不可重复
    pick: {
        multiple: false,
        id: "#file_upload",
        innerHTML: "文件上传"
    }
});

// 文件上传成功
var fileIds = [];   //新增弹层已上传文件ID
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
    //监听提交
    form.on('submit(save)', function(data){
        //layer.msg(JSON.stringify(data.field));
        data.field.file_ids = fileIds;
        $.ajax({
            url: "./send",
            type: "post",
            data: data.field,
            dataType: "json",
            success: function (res) {
                layer.msg(res.msg);
            }
        });
        return false;
    });
});

//返回
$('#back').click(function () {
    layer.closeAll();
    $('#add_table_files tbody').empty();
});

function incomeShow(that) {
    layer.open({
        type: 1,
        title:null,
        closeBtn: true,
        shade:0.5,
        shadeClose: true,
        area:["800px","600px"],
        content: $('#file_modal')
    });
    if (that == "1"){
        $("#fileOperation").css("display","block");
        $("#file_modal input").attr("readonly",false);
    }else{
        $("#fileOperation").css("display","none");
        $("#file_modal input").attr("readonly",true);
    }
}