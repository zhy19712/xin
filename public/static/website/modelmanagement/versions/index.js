//竣工模型表
var completedTable = $('#completedTable').DataTable({
    pagingType: "full_numbers",
    processing: true,
    serverSide: true,
    ajax: {
        "url": "/modelmanagement/common/datatablesPre.shtml?tableName=model_version_management"
    },
    dom: 'lf<"#addBtn.layui-btn layui-btn-sm layui-btn-normal btn-right">rtip',
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
                var rowId = row[0];
                var html = "<a type='button' href='javasrcipt:;' class='' style='margin-left: 5px;' onclick='view("+ rowId +")'><i title='查看' class='fa fa-pencil'></i></a>";
                html += "<a type='button' class='' style='margin-left: 5px;' onclick='delFile("+ rowId +")'><i title='删除' class='fa fa-trash'></i></a>";
                html += "<a type='button' class='' style='margin-left: 5px;' onclick='enable("+ rowId +")'><i title='启用' class='fa fa-trash'></i></a>";
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
            "sPrevious": "<",
            "sNext": ">",
            "sLast": ">>"
        }
    }/*,
    "fnInitComplete": function (oSettings, json) {
        $('#tableItem_length').insertBefore(".mark");
        $('#tableItem_info').insertBefore(".mark");
        $('#tableItem_paginate').insertBefore(".mark");
    }*/
});

$('#addBtn').html('新增');

//施工模型表
var constructionTable = $('#constructionTable').DataTable({
    pagingType: "full_numbers",
    processing: true,
    serverSide: true,
    ajax: {
        "url": "/modelmanagement/common/datatablesPre.shtml?tableName=model_version_management"
    },
    dom: 'lf<"#addBtn.layui-btn layui-btn-sm layui-btn-normal btn-right">rtip',
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
                var html = "<a type='button' href='javasrcipt:;' class='' style='margin-left: 5px;' onclick='view("+row[3]+")'><i title='查看' class='fa fa-pencil'></i></a>";
                html += "<a type='button' class='' style='margin-left: 5px;' onclick='delFile("+row[3]+")'><i title='删除' class='fa fa-trash'></i></a>";
                html += "<a type='button' class='' style='margin-left: 5px;' onclick='enable("+row[3]+")'><i title='启用' class='fa fa-trash'></i></a>";
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
            "sPrevious": "<",
            "sNext": ">",
            "sLast": ">>"
        }
    }/*,
    "fnInitComplete": function (oSettings, json) {
        $('#tableItem_length').insertBefore(".mark");
        $('#tableItem_info').insertBefore(".mark");
        $('#tableItem_paginate').insertBefore(".mark");
    }*/
});

$('#addBtn').html('新增');

//切换模型表
$('#tt').tabs({
    onSelect: function(title,index){
        if(index==0){
            completedTable.ajax.url('/modelmanagement/common/datatablesPre.shtml?tableName=model_version_management&model_type=1').load();
        }
        if(index==1){
            constructionTable.ajax.url('/modelmanagement/common/datatablesPre.shtml?tableName=model_version_management&model_type=2').load();
        }
    }
});

//新增模型
$('#addBtn').click(function () {
    layer.open({
        title:'新增模型',
        id:'1',
        type:'1',
        area:['600px','400px'],
        content:$('#addModelLayer'),
        btn:['保存','关闭'],
        success:function () {
            uploadModel();
        },
        yes:function () {
            layer.close(layer.index);
        },
        cancel: function(index, layero){
            layer.close(layer.index);
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
        console.log(response);
        $('#resource_name').val(file.name);
    });
    //准备上传
    uploader.on("uploadStart",function () {
        uploader.options.formData.model_type = 1;
    });
}

//查看版本
function view(rowId) {

}
//删除版本
function delFile(rowId) {

}
//启用版本
function enable(rowId) {
    
}