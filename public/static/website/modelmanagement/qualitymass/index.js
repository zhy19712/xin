var setting = {
    async: {
        enable: true,
        autoParam: ["pid","tid"],
        type: "post",
        url: "./index",
        dataType: "json"
    },
    data: {
        simpleData: {
            enable: true,
            idKey: "id",
            pIdKey: "pId"
        }
    },
    check:{
        enable: true
    },
    view:{
        selectedMulti: false
    },
    callback:{
    },
    showLine:true,
    showTitle:true,
    showIcon:true
};
zTreeObj = $.fn.zTree.init($("#ztree"), setting, null);

$('input[name="nodeRelation"]').iCheck({
    radioClass: 'iradio_square-green',
    increaseArea: '0' // optional
});

var tableItem = $('#tableItem').DataTable({
    pagingType: "full_numbers",
    processing: true,
    serverSide: true,
    ajax: {
        "url": "/modelmanagement/common/datatablesPre.shtml?tableName=model_quality_search"
    },
    dom: '<".notever layui-btn layui-btn-sm layui-btn-normal btn-right"><".already layui-btn layui-btn-sm layui-btn-normal btn-right">rtip',
    columns: [
        {
            name: "id",
            "render": function(data, type, full, meta) {
                var ipt = "<input type='checkbox' name='checkList' idv="+ data +" unit="+ full[1] +" nickname="+ full[2] +" onclick='getSelectId(this)'>";
                return ipt;
            },
        },
        {
            name: "section"
        },
        {
            name: "unit"
        },
        {
            name: "parcel"
        },
        {
            name: "cell"
        },
        {
            name: "pile_number_1"
        },
        {
            name: "pile_val_1"
        },
        {
            name: "pile_number_2"
        },
        {
            name: "pile_val_2"
        },
        {
            name: "pile_number_3"
        },
        {
            name: "pile_val_3"
        },
        {
            name: "pile_number_4"
        },
        {
            name: "pile_val_4"
        },
        {
            name: "el_start"
        },
        {
            name: "el_cease"
        },
        {
            name: "site"
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

$('.already').html('关联');
$('.notever').html('解除关联');

function getSelectId() {
    
}