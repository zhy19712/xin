$('#admin_table').DataTable({
    pagingType: "full_numbers",
    processing: true,
    serverSide: true,
    "scrollX": true,
    "scrollY": "520px",
    "scrollCollapse": "true",
    "paging": "false",
    ajax: {
        url: "/contract/common/datatablesPre?tableName=section"
    },
    dom: 'lf<"#save.layui-btn layui-btn-warm layui-btn-sm btn-right btn-space"><"#add.layui-btn layui-btn-normal layui-btn-sm btn-right btn-space">tip',
    columns: [
        {
            name: "code"
        },
        {
            name: "name"
        },
        {
            name: "money"
        },
        {
            name: "builder"
        },
        {
            name: "constructor"
        },
        {
            name: "designer"
        },
        {
            name: "supervisor"
        },
        {
            name: "id"
        }
    ],
    columnDefs: [
        {
            searchable: false,
            orderable: false,
            targets: [7],
            render: function (data, type, row) {
                var html = "<i class='fa fa-pencil' title='编辑' onclick='secEdit(this)'></i>";
                html += "<i class='fa fa-trash' title='删除' onclick='secDel(this)'></i>";
                return html;
            }
        }
    ],
    language: {
        "lengthMenu": "_MENU_ ",
        "zeroRecords": "没有找到记录",
        "info": "第 _PAGE_ 页 ( 总共 _PAGES_ 页 )",
        "infoEmpty": "无记录",
        "search": "搜索",
        "sSearchPlaceholder": "请输入关键字",
        "infoFiltered": "(从 _MAX_ 条记录过滤)",
        "paginate": {
            "sFirst": "<<",
            "sPrevious": "上一页",
            "sNext": "下一页",
            "sLast": ">>"
        }
    }
});
$('#add').html('新增');
$('#save').html('保存');