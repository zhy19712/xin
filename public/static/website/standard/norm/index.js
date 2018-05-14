//初始化layui组件
var initUi = layui.use('form','laydate');
//工程标准及规范树
$.ztree({
    zTreeOnClick:function(event, treeId, treeNode){
        $.clicknode({
            treeNode:treeNode,
            tablePath:'/standard/common/datatablesPre?tableName=norm_file',
            isLoadPath:false,
        });
    }
});
//点击节点

//工程标准及规范表格
$.datatable({
    ajax:{
        'url':'/standard/common/datatablesPre?tableName=norm_file'
    },
    dom: 'lf<".current-path"<"#add.add layui-btn layui-btn-normal layui-btn-sm">>tipr',
    columns:[
        {
            name: "standard_number"
        },
        {
            name: "standard_name"
        },
        {
            name: "material_date"
        },
        {
            name: "alternate_standard"
        },
        {
            name: "remark"
        },
        {
            name: "id"
        }
    ],
    columnDefs:[
        {
            "searchable": false,
            "orderable": false,
            "targets": [5],
            "render" :  function(data,type,row) {
                var html = "<i class='fa fa-pencil' uid="+ data +" title='编辑' onclick='edit(this)'></i>" ;
                html += "<i class='fa fa-download' uid="+ data +" title='下载' onclick='download(this)'></i>" ;
                html += "<i class='fa fa-trash' uid="+ data +" title='删除' onclick='del(this)'></i>" ;
                return html;
            }
        }
    ],
});
$('#add').html('新增');
//新增弹层
$('#add').click(function () {
    $.add({
        area:['660px','410px'],
        success:function () {
            $('.webuploader-pick').next('div').css({
                width:'100%',
                height:'100%'
            });
        }
    });
});
//关闭弹层
$.close();
//表单提交
$('#save').click(function () {
    $.submit({
        ajaxUrl:'./editNode',
        data:{
            file_id : window.file_id,
            id: window.rowId
        },
        tablePath:'/standard/common/datatablesPre?tableName=norm_file'
    });
});

//日期
layui.use('laydate', function(){
    var laydate = layui.laydate;
    laydate.render({
        elem: '#material_date'
    });
});
//上传
$.upload({
    formData:{
        module:'norm',
        use:'norm_file',
    }
});
//编辑
function edit(that) {
    $.edit({
        formId:'formLayer',
        ajaxUrl:'./editNode',
        area:['660px','410px'],
        that:that,
        others:function(res){
            var data = res.data;
            $('input[name="standard_number"]').val(data.standard_number);
            $('input[name="standard_name"]').val(data.standard_name);
            $('input[name="material_date"]').val(data.material_date);
            $('input[name="alternate_standard"]').val(data.alternate_standard);
            $('input[name="file_name"]').val(res.filename[0]);
            $('textarea[name="remark"]').val(data.remark);
            $('.webuploader-pick').next('div').css({
                width:'100%',
                height:'100%'
            });
        }
    });
}
//下载
function download(that) {
    $.download({
        that:that,
        url:'./fileDownload',
        submitPath:'./fileDownload'
    });
}

//删除
function del(that) {
    $.deleteData({
        that:that,
        tablePath:'/standard/common/datatablesPre?tableName=norm_file&id='+ window.nodeId +''
    });
}