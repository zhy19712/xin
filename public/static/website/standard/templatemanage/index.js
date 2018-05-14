//组织结构表格
var tableItem = $('#tableItem').DataTable({
    pagingType: "full_numbers",
    retrieve: true,
    processing: true,
    serverSide: true,
    "scrollY": "200px",
    "scrollCollapse": "true",
    ajax: {
        "url":"/standard/common/datatablesPre?tableName=quality_template"
    },
    dom: 'lf<"mybtn layui-btn layui-btn-sm">rtip',
    columns: [
        {
            name: "code"
        },
        {
            name: "name"
        },
        {
            name: "type"
        },
        {
            name: "use"
        },
        {
            name: "id"
        }
    ],
    columnDefs: [
        {
            targets: [0],
            width:'250px'
        },
        {
            targets: [1],
            width:'250px'
        },
        {
            "searchable": false,
            targets:[2],
            render: function (data, type, row) {
                if (data == 1) {
                    return "质量";
                }else if(data == 2){
                    return "其他";
                }
            }
        },
        {
            "searchable": false,
            targets: [3],
            render: function (data, type, row) {
                if (data == 1) {
                    return "单元工程质量评定表";
                }else if(data == 2){
                    return "分部工程质量评定表";
                }else if(data == 3){
                    return "单位工程质量评定表";
                }
            }
        },
        {
            "searchable": false,
            "orderable": false,
            "targets": [4],
            "render": function (data, type, row) {
                var a = data;
                var html = "<span class='' style='margin-left: 5px;' onclick='editFile("+row[4]+")'><i title='编辑' class='fa fa-pencil'></i></span>";
                html += "<span class='' style='margin-left: 5px;' onclick='delFile("+row[4]+")'><i title='删除' class='fa fa-trash'></i></span>";
                return html;
            }
        }
    ],
    language: {
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
    "fnInitComplete": function (oSettings, json) {
        $('#tableItem_length').insertBefore(".mark");
        $('#tableItem_info').insertBefore(".mark");
        $('#tableItem_paginate').insertBefore(".mark");
    }
});

//点击上传文件
$(".mybtn").html("<div id='test3'><i class='fa fa-plus'></i>新增模板</div>");

var layer = layui.layer;
var typeId;
var purposeId;
var form;
//查询提交
layui.use(['form', 'layedit', 'laydate','layer'], function(){
    form = layui.form
        ,layer = layui.layer;

    form.on('select(type)', function(data){
        typeId = data.value;
        tableItem.ajax.url("/standard/common/datatablesPre?tableName=quality_template&type="+typeId).load();
    });

    form.on('select(purpose)', function(data){
        purposeId = data.value;
        console.log(typeId);
        console.log(purposeId);

        if(purposeId == ""){
            tableItem.ajax.url("/standard/common/datatablesPre?tableName=quality_template&type="+typeId).load();
        }else{
            tableItem.ajax.url("/standard/common/datatablesPre?tableName=quality_template&type="+typeId+"&use="+purposeId).load();
        }
        tableItem.ajax.url("/standard/common/datatablesPre?tableName=quality_template&use="+purposeId).load();
    });

    //监听提交
    // form.on('submit(demo1)', function(data){
    //     tableItem.ajax.url("/standard/common/datatablesPre?tableName=quality_template&type="+data.field.type+"&use="+data.field.use).load();
    //     return false;
    // });
});

//点击新增模板
$("#tableContent .mybtn").click(function () {
    layer.open({
        type: 2,
        title: '新增模板',
        shadeClose: true,
        area: ['780px', '550px'],
        content: '/standard/templatemanage/add.shtml'
    });
});

//点击编辑模板
function editFile(id) {
    layer.open({
        type: 2,
        title: '编辑模板',
        shadeClose: true,
        area: ['780px', '550px'],
        content: '/standard/templatemanage/add.shtml?id='+id,
        // end:function () {
        //     $("#typeName").val("");
        //     $("#use").val("");
        //     form.render('select');
        // }
    });
};

//点击删除模板
function delFile(id) {
    layer.confirm('该操作会将数据删除，是否确认删除？', function(index){
        console.log(id);
        $.ajax({
            type: "post",
            url: "/standard/templatemanage/del",
            data: {id: id},
            success: function (res) {
                if(res.code == 1){
                    console.log(res)
                    layer.msg("删除成功！")
                    tableItem.ajax.url("/standard/common/datatablesPre?tableName=quality_template").load();
                }else if(res.code==0){
                    layer.msg(res.msg);
                }
            },
            error: function (data) {
                debugger;
            }
        })
        layer.close(index);
    });
};