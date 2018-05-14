//初始化layui组件
var initUi = layui.use('form','laydate');
var form = layui.form;

$.ztree({
    treeId:'controlZtree',
    //点击节点
    zTreeOnClick:function (event, treeId, treeNode){
        $('#enginId').val(treeNode.add_id);
        $.clicknode({
            tableItem:window.tableItem,
            treeNode:treeNode,
            isLoadPath:false,
            isLoadTable:false,
            parentShow:false
        });
        var iShow = treeNode.edit_id;
        var url = "../../productionProcesses";
        if(iShow){
            getControlPoint(url);
        }
    }
});

function unitPlanList() {
    $.datatable({
        tableId:'tableItem',
        ajax:{
            'url':'/quality/common/datatablesPre?tableName=unit_quality_control'
        },
        dom: 'ltipr',
        columns:[
            {
                name: "code"
            },
            {
                name: "name"
            },
            {
                name: "status"
            },
            {
                name: "id"
            }
        ],
        columnDefs:[
            {
                "searchable": false,
                "orderable": false,
                "targets": [3],
                "render" :  function(data,type,row) {
                    var html = "<i class='fa fa-download' uid="+ data +" title='下载' onclick='download(this)'></i>" ;
                    html += "<i class='fa fa-print' uid="+ data +" title='打印' onclick='print(this)'></i>" ;
                    return html;
                }
            }
        ],
    });
}


//控制点执行情况
$.datatable({
    tableId:'implement',
    isPage:false,
    ajax:{
        'url':'/quality/common/datatablesPre?tableName=unit_quality_manage_file'
    },
    dom: 'l<"#implementBtn">tipr',
    columns:[
        {
            name: "filename"
        },
        {
            name: "nickname"
        },
        {
            name: "role_name"
        },
        {
            name: "create_time"
        },
        {
            name: "id"
        }
    ],
    columnDefs:[
        {
            "searchable": false,
            "orderable": false,
            "targets": [4],
            "render" :  function(data,type,row) {
                var html = "<i class='fa fa-eye' uid="+ data +" title='预览' onclick='view(this)'></i>" ;
                html += "<i class='fa fa-download' uid="+ data +" title='下载' onclick='download(this)'></i>" ;
                html += "<i class='fa fa-times' uid="+ data +" title='删除' onclick='del(this)'></i>" ;
                return html;
            }
        }
    ],
});

//图像资料
$.datatable({
    tableId:'imageData',
    isPage:false,
    ajax:{
        'url':'/quality/common/datatablesPre?tableName=unit_quality_manage_file'
    },
    dom: 'l<"#imageDataBtn">tipr',
    columns:[
        {
            name: "filename"
        },
        {
            name: "nickname"
        },
        {
            name: "role_name"
        },
        {
            name: "create_time"
        },
        {
            name: "id"
        }
    ],
    columnDefs:[
        {
            "searchable": false,
            "orderable": false,
            "targets": [4],
            "render" :  function(data,type,row) {
                var html = "<i class='fa fa-eye' uid="+ data +" title='预览' onclick='view(this)'></i>" ;
                html += "<i class='fa fa-download' uid="+ data +" title='下载' onclick='download(this)'></i>" ;
                html += "<i class='fa fa-times' uid="+ data +" title='删除' onclick='del(this)'></i>" ;
                return html;
            }
        }
    ],
});

$('#implementBtn').html('上传');
$('#imageDataBtn').html('上传');

//加载控制点执行情况及图像资料
// TODO 需要重构
$("#tableItem").on("click","tr",function(){//给tr或者td添加click事件
    var data=window.tableItem.row(this).data();//获取值的对象数据
    if(!data){
        return false;
    }
    var controlId = data[3];
    var index = $('#index').val();
    $('#controlId').val(controlId);
    if(index==0){
        var tableItem = $('#implement').DataTable();
        tableItem.ajax.url('/quality/common/datatablesPre?tableName=unit_quality_manage_file&controlId='+ controlId +'&type=1').load();
    }
    if(index==1){
        var tableItem = $('#imageData').DataTable();
        tableItem.ajax.url('/quality/common/datatablesPre?tableName=unit_quality_manage_file&controlId='+ controlId +'&type=2').load();
    }
    $('#implement_wrapper,#imageData_wrapper,.tbcontainer,#subList').show();
    $('#implement_wrapper,#imageData_wrapper').find('.tbcontainer').remove();
    implementUpload();
    imageDataUpload();
});


//tab切换
// TODO 需要重构
$('#tabs').tabs({
    onUpdate:function(title,index){
        $('#index').val(index);
    },
    onSelect:function (title,index) {
        $('#index').val(index);
        $('#implement_wrapper,#imageData_wrapper').next('.tbcontainer').remove();
        var controlId = $('#controlId').val();
        if(index==0){
            var tableItem = $('#implement').DataTable();
            tableItem.ajax.url('/quality/common/datatablesPre?tableName=unit_quality_manage_file&controlId='+ controlId +'&type=1').load();
            implementUpload();
        }
        if(index==1){
            var tableItem = $('#imageData').DataTable();
            tableItem.ajax.url('/quality/common/datatablesPre?tableName=unit_quality_manage_file&controlId='+ controlId +'&type=2').load();
            imageDataUpload();
        }
    }
});


/**
 * 上传
 */
// TODO 需要重构
//控制点执行情况
function implementUpload(){
    var controlId = $('#controlId').val();
    $.upload({
        btnId:'#implementBtn',
        server:'../../editRelation',
        formData:{
            contr_relation_id:'',
            file_type:''
        },
        uploadSuccess:function (res) {
            layer.msg(res.msg);
            var tableItem = $('#implement').DataTable();
            tableItem.ajax.url('/quality/common/datatablesPre?tableName=unit_quality_manage_file&controlId='+ controlId +'&type=1').load();
        },
        uploadStart:function (uploader){
            uploader.options.formData.contr_relation_id = controlId;
            uploader.options.formData.file_type = 1;
        }
    });
}

//图像资料
function imageDataUpload(){
    var controlId = $('#controlId').val();
    $.upload({
        btnId:'#imageDataBtn',
        server:'../../editRelation',
        formData:{
            contr_relation_id:'',
            file_type:''
        },
        uploadSuccess:function (res) {
            layer.msg(res.msg);
            var tableItem = $('#imageData').DataTable();
            tableItem.ajax.url('/quality/common/datatablesPre?tableName=unit_quality_manage_file&controlId='+ controlId +'&type=2').load();
        },
        uploadStart:function (uploader){
            uploader.options.formData.contr_relation_id = controlId;
            uploader.options.formData.file_type = 2;
        }
    });
}

//下载
function download(that) {
    var id = $(that).attr('uid');
    $.download({
        that:that,
        url:'../../fileDownload',
        data:{
            file_id:id
        },
        success: function (res) {
            layer.msg(res.msg);
        }
    });
}

//打印
function print(that) {
    var id = $(that).attr('uid');
    $.ajax({
        url: "../../printDocument",
        type: "post",
        data: {
            id:id
        },
        dataType: "json",
        success: function (res) {
            layer.msg(res.msg);
        }
    })
}