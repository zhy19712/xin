$(function () {
    $("#tableIncome").DataTable({
        processing: true,
        ordering: false,
        ajax: {
            "url": "{:url('/archive/common/datatablesPre')}?tableName=archive_income_send&table_type=2"
        },
        columns: [
            { "data": "file_name" },
            { "data": "date" },
            { "data": "unit_name" },
            { "data": "name"},
            { "data": "income_name" },
            { "data": "status" },
            { "data": "id" },
        ],
        columnDefs: [
            {
                targets: [3],
                render: function (data, type, row,meta) {
                    return  'admin';
                }
            },
            {
                targets: [6],
                render: function (data, type, row,meta) {
                    if (data == '未处理'){
                        return  '<a title="' + data + '" href="javascript:void(0);" data-fileId="'+row.id+'" onclick=\"incomeShow(this)\">处理</a>';
                    }else {
                        return  '<a title="' + data + '" href="javascript:void(0);" data-fileId="'+row.id+'" onclick=\"incomeShow(this)\">查看</a>';
                    }
                }
            }
        ],
        dom: 'frtp',
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
        }
    })
})
//查看文件弹出层
function incomeShow(that) {
    var txt = $(that).attr("title");
    layer.open({
        type: 1,
        title:null,
        closeBtn: true,
        shade:0.5,
        shadeClose: true,
        area:["800px","600px"],
        content: $('#file_modal')
    });
    if (txt == "未处理"){
        $("#fileOperation").css("display","block");
    }else{
        $("#fileOperation").css("display","none");
    }
}
