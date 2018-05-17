$(function () {
    $("#tableIncome").DataTable({
        processing: true,
        ordering: false,
        data:[
            { "fileId": "1","fileName": "测试文件", "fileDate": "2018-02-14", "fromUnit": "监理单位", "fromPeople": "张三", "toPeople": "李四", "status": "未处理", "operation": "处理" },
            { "fileId": "2","fileName": "测试文件", "fileDate": "2018-02-14", "fromUnit": "监理单位", "fromPeople": "张三", "toPeople": "李四", "status": "已拒收", "operation": "处理" },
            { "fileId": "3","fileName": "测试文件", "fileDate": "2018-02-14", "fromUnit": "监理单位", "fromPeople": "张三", "toPeople": "李四", "status": "未处理", "operation": "处理" },
            { "fileId": "4","fileName": "测试文件", "fileDate": "2018-02-14", "fromUnit": "监理单位", "fromPeople": "张三", "toPeople": "李四", "status": "已拒收", "operation": "处理" },
            { "fileId": "5","fileName": "测试文件", "fileDate": "2018-02-14", "fromUnit": "监理单位", "fromPeople": "张三", "toPeople": "李四", "status": "未处理", "operation": "处理" },
            { "fileId": "6","fileName": "测试文件", "fileDate": "2018-02-14", "fromUnit": "监理单位", "fromPeople": "张三", "toPeople": "李四", "status": "已拒收", "operation": "处理" }
        ],
        columns: [
            { "data": "fileName" },
            { "data": "fileDate" },
            { "data": "fromUnit" },
            { "data": "fromPeople"},
            { "data": "toPeople" },
            { "data": "status" },
            { "data": "status" },
        ],
        columnDefs: [
            {
                targets: [6],
                render: function (data, type, row,meta) {
                    console.log(row[5]);
                    if (data == '未处理'){
                        return  '<a title="' + data + '" href="javascript:void(0);" data-fileId="'+row.fileName+'" onclick=\"incomeShow(this)\">处理</a>';
                    }else {
                        return  '<a title="' + data + '" href="javascript:void(0);" data-fileId="'+row.fileName+'" onclick=\"incomeShow(this)\">查看</a>';
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
