var uploader;
var fileIds = [];   //新增弹层已上传文件ID
var major_key = ''; //编辑表格当前行ID
var income = {} //收件人信息
var fileInfo = {};//关联文件信息
//发文状态表格
tableIncome = $("#tableIncome").DataTable({
    processing: false,
    serverSide: true,
    ordering: true,
    scrollY: "486px",
    scrollCollapse: true,
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
                if (row[5] == '1'){
                    var strs = '';
                    strs +='<a title="' + data + '" class="layui-btn layui-btn-sm" href="javascript:void(0);" major_key="'+row[6]+'" onclick="edit_send(this)">编辑</a>';
                    strs +='<a title="' + data + '" class="layui-btn layui-btn-danger layui-btn-sm" href="javascript:void(0);" major_key="'+row[6]+'" onclick="del(this)">删除</a>';
                    return strs;
                }else {
                    return  '<a title="' + data + '" class="layui-btn layui-btn-primary layui-btn-sm" href="javascript:void(0);" major_key="'+row[6]+'" onclick="preview(this)">查看</a>';
                }
            }
        }
        ],
    dom: 'fr<"#addSend.mybtn layui-btn layui-btn-md">t<"#pagenations"lp>',
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
            $("#add_file_modal input").attr("disabled",false);
            $("#add_file_modal input").val('');
            $("#add_file_modal textarea").val('');
            $("#add_table_files tbody").empty();
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
    fileLists.push('<td class="layui-col-xs9">'+ file.name +'</td>');
    fileLists.push('<td class="layui-col-xs3">');
    fileLists.push('<a href="javascript:;" class="layui-btn layui-btn-xs" onclick="fileDownload(this)" uid='+ fileId +' name='+ file.name +'>下载</a>');
    fileLists.push('<a href="javascript:;"  class="layui-btn layui-btn-primary layui-btn-xs" onclick="attachmentPreview(this)" uid='+ fileId +' name='+ file.name +'>查看</a>');
    fileLists.push('<a href="javascript:;" class="layui-btn layui-btn-danger layui-btn-xs" onclick="attachmentDel(this)" uid='+ fileId +' name='+ file.name +'>删除</a>');
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
    var id = $(that).attr('uid');
    var url = '/archive/send/fileDownload';
    $.ajax({
        url: url,
        data:{file_id:id},
        type:"post",
        success: function (res) {
            if(res.code != 1){
                layer.msg(res.msg)
            }else {
                $("#form_container").empty();
                var str = "";
                str += ""
                    + "<iframe name=downloadFrame"+ id +" style='display:none;'></iframe>"
                    + "<form name=download"+ id +" action="+ url +" method='get' target=downloadFrame"+ id + ">"
                    + "<span class='file_name' style='color: #000;'>"+str+"</span>"
                    + "<input class='file_url' style='display: none;' name='file_id' value="+ id +">"
                    + "<button type='submit' class=btn" + id +"></button>"
                    + "</form>"
                $("#form_container").append(str);
                $("#form_container").find(".btn" + id).click();
            }
        }
    })

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
        data.field.major_key = '';
        return false;
    });
});

//保存接口
function saveInter(data) {
    data.field.file_ids = fileIds;
    if($("#major_key").val()){
        data.field.major_key = $("#major_key").val();
    }
    $.ajax({
        url: "./send",
        type: "post",
        data: data.field,
        dataType: "json",
        success: function (res) {
            layer.closeAll('page');
            layer.msg(res.msg);
            major_key = '';
            tableIncome.ajax.url("/archive/common/datatablesPre?tableName=archive_income_send&table_type=2").load();
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
        title:'编辑',
        area:["800px","620px"],
        content: $('#add_file_modal'),
        success:function () {
            $.viewFilter(true);
            $('#add_file_modal input').attr("disabled",false);
            $('#major_key').val(major_key);
            $.ajax({
                url:"/archive/send/preview",
                data:{
                    major_key:major_key,
                    see_type:2
                },
                type:'post',
                dataType:'json',
                success:function (res) {
                    console.log(res);
                    var attachment = res.attachment;
                    $("#file_name").val(res.file_name);
                    $("#date").val(res.date);
                    $("#income_name").val(res.income_name);
                    $("#unit_name").val(res.unit_name);
                    $("#remark").val(res.remark);
                    $("#relevance_id").val(res.attchment_id);
                    // $('#file_ids').val(major_key);//附件ID
                    var rowData = '';
                    for (var i=0;i<attachment.length;i++){
                        rowData +='<tr><td class="layui-col-xs9">'+attachment[i].name+'</td><td class="layui-col-xs3">';
                        rowData += '<a href="javascript:;"  class="layui-btn layui-btn-xs" onclick="fileDownload(this)" uid='+ attachment[i].id +' name='+ attachment[i].name +'>下载</a>';
                        rowData += '<a href="javascript:;" onclick="attachmentPreview(this)"  class="layui-btn layui-btn-primary layui-btn-xs"  uid='+ attachment[i].id +' name='+ attachment[i].name +'>查看</a>';
                        rowData += '<a href="javascript:;" onclick="attachmentDel(this)" class="layui-btn layui-btn-danger layui-btn-xs" uid='+ attachment[i].id +' name='+ attachment[i].name +'>删除</a></td></tr>';
                    }
                    $("#add_table_files tbody").empty().append(rowData);
                    major_key = '';
                }
            })
        }
    });
}

//查看
function preview (that) {
    major_key = $(that).attr('major_key');
    layer.open({
        type: 1,
        title:'查看',
        area:["800px","620px"],
        content: $('#add_file_modal'),
        success:function () {
            $.viewFilter(false);
            $('#add_file_modal input').attr("disabled",true);
            $('#file_ids').val(major_key);
            $.ajax({
                url:"/archive/send/preview",
                data:{
                    major_key:major_key,
                    see_type:2
                },
                type:'post',
                dataType:'json',
                success:function (res) {
                    console.log(res);
                    var attachment = res.attachment;

                    $("#file_name").val(res.file_name);
                    $("#date").val(res.date);
                    $("#income_name").val(res.income_name);
                    $("#unit_name").val(res.unit_name);
                    $("#remark").val(res.remark);
                    $("#relevance_id").val(res.attchment_id);
                    var rowData = '';
                    for (var i=0;i<attachment.length;i++){
                        rowData +='<tr><td class="layui-col-xs9">'+attachment[i].name+'</td><td class="layui-col-xs3"><a href="javascript:;" class="layui-btn layui-btn-xs"  onclick="fileDownload(this)" uid='+ attachment[i].id +' name='+ attachment[i].name +'>下载</a>';
                        rowData +='<a href="javascript:;" onclick="attachmentPreview(this)" class="layui-btn layui-btn-primary layui-btn-xs"  uid='+ attachment[i].id +' name='+ attachment[i].name +'>查看</a></td></tr>'
                    }
                    $("#add_table_files tbody").empty().append(rowData);
                    major_key = '';
                }
            })
        }
    });
}
//删除发文
function del(that) {
    major_key = $(that).attr('major_key');
    var parents = $(that).parents('tr');
    $.ajax({
        url: "./del",
        type: "post",
        data: {
            major_key:major_key
        },
        dataType: "json",
        success: function (res) {
            console.log(res);
            parents.remove();
            layer.msg(res.msg);
            major_key = '';
            tableIncome.ajax.url("/archive/common/datatablesPre?tableName=archive_income_send&table_type=2").load();
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
               $("#incomeNameLayer").css("visibility","visible");
               incomeZtree();
           },
           yes:function () {
               save();
               layer.close(layer.index);
           },
           cancel: function(index, layero){
               $("#incomeNameLayer").css("visibility","hidden");
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
        ordering: true,
        scrollY: "250px",
        scrollCollapse: true,
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
        ],
        dom: 'frtp'
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
    $("#incomeNameLayer").css("visibility","hidden");
    $('#income_id').val(income.id);
    $('#income_name').val(income.nickname);
    $('#unit_name').val(income.unit);
}
//关联文件弹层
$('#relevance_name').focus(function () {
    layer.open({
        title:'关联文件',
        id:'1',
        type:'1',
        area:['1024px','600px'],
        content:$('#incomeFileList'),
        btn:['保存'],
        success:function (index) {
            $("#incomeFileList").css("visibility","visible");
            incomeFile();
            tableFile.ajax.url("/archive/common/datatablesPre?tableName=archive_income_send&table_type=3").load();
        },
        yes:function (index) {
            saveFile();
            layer.close(layer.index);
        },
        cancel: function(index, layero){
            $("#incomeFileList").css("visibility","hidden");
            layer.close(layer.index);
        }
    });
});
//关联文件信息
function incomeFile() {
    tableFile = $("#tableFileList").DataTable({
        serverSide: true,
        ordering: true,
        scrollY: "320px",
        scrollCollapse: true,
        ajax: {
            "url":"/archive/common/datatablesPre?tableName=archive_income_send&table_type=3"
        },
        columns:[
            {
                name: "id",
                "render": function(data, type, full, meta) {
                    console.log(full);
                    var ipt = "<input type='radio' name='checkList' fileId="+full[0]+" fileName="+ full[1] +" onclick='getSelectFile(this)'>";
                    return ipt;
                }
            },
            { name: "file_name" },
            { name: "date" },
            { name: "unit_name" },
            { name: "attchment_id"},
            { name: "send_name" },
            {
                name: "status",
                "render":function(data, type, full, meta) {
                    return "已接收";
                }
            },
        ],
        "destroy": true,

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
        },
        dom: 'frt<"#filePagnation"lp>',
    });
}
//选择关联文件
function getSelectFile(that) {
    var fileName = $(that).attr('fileName');
    var fileId = $(that).attr('fileId');
    fileInfo.fileName = fileName;
    fileInfo.fileId = fileId;
}
//保存关联文件
function saveFile() {
    $("#incomeFileList").css("visibility","hidden");
    $('#relevance_name').val(fileInfo.fileName);
    $('#relevance_id').val(fileInfo.fileId);
}