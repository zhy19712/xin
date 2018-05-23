//收文状态表格
tableItem = $("#tableIncome").DataTable({
    processing: true,
    serverSide: true,
    ordering: false,
    ajax: {
        "url": "/archive/common/datatablesPre?tableName=archive_income_send&table_type=1",
    },
    columns: [
        { name: "file_name" },
        { name: "date" },
        { name: "unit_name" },
        { name: "send_name" },
        { name: "attchment_id"},
        { name: "status" },
        { name: "id" }
    ],
    columnDefs: [
        {
            targets: [5],
            render: function (data, type, row,meta) {
                if (data == '2'){
                    return  '未处理';
                }else if(data == '3'){
                    return  '已签收';
                }else if(data == '4'){
                    return  '已拒收';
                }
            }
        },
        {
            targets: [6],
            render: function (data, type, row,meta) {
                if (row[5] == '2'){
                    return  '<a title="' + data + '" class="layui-btn layui-btn-sm" href="javascript:void(0);" major_key="'+row[6]+'" onclick="handle(this)">处理</a>';
                }else {
                    return  '<a title="' + data + '" class="layui-btn layui-btn-primary layui-btn-sm"  href="javascript:void(0);" major_key="'+row[6]+'" onclick="preview(this)">查看</a>';
                }
            }
        }
    ],
    dom: 'frtp',
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
//收文处理
function handle(that) {
    var major_key = $(that).attr('major_key');
    layer.open({
        type: 1,
        title:'收文处理',
        area:["800px","620px"],
        content: $('#file_modal'),
        success:function () {
            $.viewFilter(true);
            $('#file_modal input').attr("disabled",true);
            $('#file_ids').val(major_key);
            $.ajax({
                url:"/archive/send/preview",
                data:{
                    major_key:major_key,
                    see_type:1
                },
                type:'post',
                dataType:'json',
                success:function (res) {
                    console.log(res);
                    var attachment = res.attachment;
                    $("#file_name").val(res.file_name);
                    $("#date").val(res.date);
                    $("#income_name").val(res.send_name);
                    $("#unit_name").val(res.unit_name);
                    $("#remark").val(res.remark);
                    $("#relevance_id").val(res.attchment_id);
                    var rowData = '';
                    for (var i=0;i<attachment.length;i++){
                        rowData +='<tr><td class="layui-col-xs9">'+attachment[i].name+'</td><td class="layui-col-xs3">';
                        rowData +='<a href="javascript:;"  class="layui-btn layui-btn-xs" onclick="fileDownload(this)" uid='+ attachment[i].id +' name='+ attachment[i].name +'>下载</a>';
                        rowData +='<a href="javascript:;" onclick="attachmentPreview(this)" class="layui-btn layui-btn-primary layui-btn-xs" uid='+ attachment[i].id +' name='+ attachment[i].name +'>查看</a></td></tr>';
                    }
                    $("#add_table_files tbody").empty().append(rowData);
                }
            })
        }
    });
}
//收文查看
function preview(that) {
    var major_key = $(that).attr('major_key');
    layer.open({
        type: 1,
        title:'收文查看',
        area:["800px","620px"],
        content: $('#file_modal'),
        success:function () {
            $.viewFilter(false);
            $('#file_modal input').attr("disabled",true);
            $('#file_ids').val(major_key);
            $.ajax({
                url:"/archive/send/preview",
                data:{
                    major_key:major_key,
                    see_type:1
                },
                type:'post',
                dataType:'json',
                success:function (res) {
                    console.log(res);
                    var attachment = res.attachment;
                    $("#file_name").val(res.file_name);
                    $("#date").val(res.date);
                    $("#income_name").val(res.send_name);
                    $("#unit_name").val(res.unit_name);
                    $("#remark").val(res.remark);
                    $("#relevance_id").val(res.attchment_id);
                    var rowData = '';
                    for (var i=0;i<attachment.length;i++){
                        rowData +='<tr><td class="layui-col-xs9">'+attachment[i].name+'</td><td class="layui-col-xs3">';
                        rowData +='<a href="javascript:;"  class="layui-btn layui-btn-xs" onclick="fileDownload(this)" uid='+ attachment[i].id +' name='+ attachment[i].name +'>下载</a>';
                        rowData +='<a href="javascript:;" class="layui-btn layui-btn-primary layui-btn-xs" onclick="attachmentPreview(this)" uid='+ attachment[i].id +' name='+ attachment[i].name +'>查看</a></td></tr>';
                    }
                    $("#add_table_files tbody").empty().append(rowData);
                }
            })
        }
    });
}
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
        url: '/archive/send/attachmentPreview',
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
//收文处理
layui.use('form', function(){
    var form = layui.form;
    //保存
    form.on('submit(save)', function(data){
        saveInter(3)
        return false;
    });
    //保存并发送
    form.on('submit(cancel)', function(data){
        saveInter(4)
        return false;
    });
});
function saveInter(status) {
    var fileId = $('#file_ids').val();
    $.ajax({
        url: "/approve/income/send",
        type: "post",
        data: {
            major_key:fileId,
            status:status
        },
        dataType: "json",
        success: function (res) {
            layer.closeAll('page');
            layer.msg(res.msg);
            major_key = '';
            tableItem.ajax.url("/archive/common/datatablesPre?tableName=archive_income_send&table_type=1").load();
        }
    });
}