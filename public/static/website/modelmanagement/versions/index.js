var model_type; //模型类型： 1为竣工模型 2为施工模型
var attachment_id; //保存文件编号
//竣工模型表
var completedTable = $('#completedTable').DataTable({
    pagingType: "full_numbers",
    processing: true,
    serverSide: true,
    ajax: {
        "url": "/modelmanagement/common/datatablesPre.shtml?tableName=model_version_management"
    },
    dom: 'lf<".addBtn layui-btn layui-btn-sm layui-btn-normal btn-right">rtip',
    columns: [
        {
            name: "id"
        },{
            name: "resource_name"
        },
        {
            name: "resource_path"
        },
        {
            name: "version_number"
        },
        {
            name: "version_date"
        },
        {
            name: "remake"
        },
        {
            name: "status"
        }
    ],
    columnDefs: [
        {
            "searchable": false,
            "orderable": false,
            "targets": [6],
            "render": function (data, type, row) {
                var rowId = row[0];     //序号（主键编号）
                var status = row[6];    //启用状态  1为启用  0为禁用
                var className = status==1?'fa-eye':'fa-eye-slash';
                var html = "<a type='button' style='margin-left: 5px;' onclick='view(this,"+ rowId +")'><i title='查看' class='fa fa-search'></i></a>";
                html += "<a type='button' style='margin-left: 5px;' onclick='delFile(this,"+ rowId +")'><i title='删除' class='fa fa-trash'></i></a>";
                html += "<a type='button' style='margin-left: 5px;' onclick='enable("+ rowId +")'><i title='禁用' class='fa "+ className +"'></i></a>";
                return html;
            }
        }

    ],
    language: {
        "sProcessing":"数据加载中...",
        "lengthMenu": "_MENU_",
        "zeroRecords": "没有找到记录",
        "info": "第 _PAGE_ 页 ( 总共 _PAGES_ 页 )",
        "infoEmpty": "无记录",
        "search": "搜索",
        "infoFiltered": "(从 _MAX_ 条记录过滤)",
        "paginate": {
            "sFirst": "<<",
            "sPrevious": "上一页",
            "sNext": "下一页",
            "sLast": ">>"
        }
    }/*,
    "fnInitComplete": function (oSettings, json) {
        $('#tableItem_length').insertBefore(".mark");
        $('#tableItem_info').insertBefore(".mark");
        $('#tableItem_paginate').insertBefore(".mark");
    }*/
});

$('.addBtn').html('新增');

//施工模型表
var constructionTable = $('#constructionTable').DataTable({
    pagingType: "full_numbers",
    processing: true,
    serverSide: true,
    ajax: {
        "url": "/modelmanagement/common/datatablesPre.shtml?tableName=model_version_management"
    },
    dom: 'lf<".addBtn layui-btn layui-btn-sm layui-btn-normal btn-right">rtip',
    columns: [
        {
            name: "id"
        },{
            name: "resource_name"
        },
        {
            name: "resource_path"
        },
        {
            name: "version_number"
        },
        {
            name: "version_date"
        },
        {
            name: "remake"
        },
        {
            name: "status"
        }
    ],
    columnDefs: [
        {
            "searchable": false,
            "orderable": false,
            "targets": [6],
            "render": function (data, type, row) {
                var rowId = row[0];     //序号（主键编号）
                var status = row[6];   //启用状态  1为启用  0为禁用
                var className = status==1?'fa-eye':'fa-eye-slash';
                var html = "<a type='button' style='margin-left: 5px;' onclick='view(this,"+ rowId +")'><i title='查看' class='fa fa-search'></i></a>";
                html += "<a type='button' style='margin-left: 5px;' onclick='delFile(this,"+ rowId +")'><i title='删除' class='fa fa-trash'></i></a>";
                html += "<a type='button' style='margin-left: 5px;' onclick='enable("+ rowId +")'><i title='禁用' class='fa "+ className +"'></i></a>";
                return html;
            }
        }

    ],
    language: {
        "sProcessing":"数据加载中...",
        "lengthMenu": "_MENU_",
        "zeroRecords": "没有找到记录",
        "info": "第 _PAGE_ 页 ( 总共 _PAGES_ 页 )",
        "infoEmpty": "无记录",
        "search": "搜索",
        "infoFiltered": "(从 _MAX_ 条记录过滤)",
        "paginate": {
            "sFirst": "<<",
            "sPrevious": "上一页",
            "sNext": "下一页",
            "sLast": ">>"
        }
    }/*,
    "fnInitComplete": function (oSettings, json) {
        $('#tableItem_length').insertBefore(".mark");
        $('#tableItem_info').insertBefore(".mark");
        $('#tableItem_paginate').insertBefore(".mark");
    }*/
});

$('.addBtn').html('新增');

//切换模型表
$('#tt').tabs({
    onSelect: function(title,index){
        //切换至竣工模型表
        if(index==0){
            model_type = 1;
            completedTable.ajax.url('/modelmanagement/common/datatablesPre.shtml?tableName=model_version_management&model_type='+ model_type +'').load();
        }
        //切换至施工模型表
        if(index==1){
            model_type = 2;
            constructionTable.ajax.url('/modelmanagement/common/datatablesPre.shtml?tableName=model_version_management&model_type='+ model_type +'').load();
        }
    }
});

//打开新增模型弹层
$('.addBtn').click(function () {
    index = layer.open({
        title:'新增模型',
        id:'1',
        type:1,
        anim:'4',
        area:['600px','355px'],
        content:$('#addModelLayer'),
        success:function () {
            uploadModel();
        },
        cancel: function(index){
            layer.close(index);
        }
    });
});

//上传模型
function uploadModel() {
    uploader = WebUploader.create({
        // 选完文件后，是否自动上传。
        auto: true,
        // swf文件路径
        swf: '/static/public/webupload/Uploader.swf',

        // 文件接收服务端。
        //server: applicationPath + '/ModelFile/UpLoadProcess?modelTypeId=' + $("#hidModeFileTypeId").val() + "&flag=" + flag,
        server: './upload',
        // 选择文件的按钮。可选。
        // 内部根据当前运行是创建，可能是input元素，也可能是flash.
        pick: {
            multiple: false,
            id: '#addModel',
            innerHTML: '上传'
        },
        formData: {}

    });
    //模型上传成功
    uploader.on('uploadSuccess', function (file, response) {
        $('#resource_name').val(file.name);
        attachment_id = response.id;
        layer.msg(response.msg);
    });
    //准备上传
    uploader.on("uploadStart",function () {
        uploader.options.formData.model_type = model_type;
    });
}

//保存模型
layui.use('form', function(){
    var form = layui.form;
    form.on('submit(save)', function(data){
        data.field.model_type = model_type;
        data.field.attachment_id = attachment_id;
        $.ajax({
            url: "./add",
            type: "post",
            data: data.field,
            dataType: "json",
            success: function (res) {
                if(model_type==1){
                    completedTable.ajax.url('/modelmanagement/common/datatablesPre.shtml?tableName=model_version_management&model_type=1').load();
                }
                if(model_type==2){
                    constructionTable.ajax.url('/modelmanagement/common/datatablesPre.shtml?tableName=model_version_management&model_type=2').load();
                }
                layer.msg(res.msg);
                layer.close(index);
            }
        })
        return false;
    });
});

//关闭新增模型弹层
$('#close').click(function(){
    layer.close(index);
});

//查看版本
function view(rowId) {
    layer.open({
        title:'查看模型',
        id:'2',
        type:2,
        area:['100%','100%'],
        content:['./viewmodel','no'],
        success:function (layero, index) {
        },
        cancel: function(index, layero){
            layer.close(layer.index);
        }
    });
}
//删除版本
function delFile(that,rowId) {
    layer.confirm('确认删除该模型版本?', {icon: 3, title:'提示'}, function(index){
        $.ajax({
            url: "./del",
            type: "post",
            data: {
                major_key:rowId
            },
            dataType: "json",
            success: function (res) {
                $(that).parents('tr').remove();
                layer.msg(res.msg);
                if(model_type==1){
                    completedTable.ajax.url('/modelmanagement/common/datatablesPre.shtml?tableName=model_version_management&model_type=1').load();
                }
                if(model_type==2){
                    constructionTable.ajax.url('/modelmanagement/common/datatablesPre.shtml?tableName=model_version_management&model_type=2').load();
                }
            }
        });
        layer.close(index);
    });

}
//启用版本
function enable(rowId) {
    $.ajax({
        url: "./enabledORDisable",
        type: "post",
        data: {
            major_key:rowId
        },
        dataType: "json",
        success: function (res) {
            if(model_type==1){
                completedTable.ajax.url('/modelmanagement/common/datatablesPre.shtml?tableName=model_version_management&model_type=1').load();
            }
            if(model_type==2){
                constructionTable.ajax.url('/modelmanagement/common/datatablesPre.shtml?tableName=model_version_management&model_type=2').load();
            }
            layer.msg(res.msg);
        }
    })
}