var uploader;
var fileIds = [];   //新增弹层已上传文件ID
var major_key = ''; //编辑表格当前行ID
var income = {} //收件人信息
//发文状态表格
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
        { name: "attchment_id"},
        { name: "send_name" },
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
                    return  '<a title="' + data + '"  href="javascript:void(0);" major_key="'+row[6]+'" onclick="preview(this)">查看</a>';
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
    major_key = $(that).attr('major_key');
    layer.open({
        type: 1,
        title:'收文处理',
        area:["800px","620px"],
        content: $('#file_modal'),
        success:function () {
            $.viewFilter(true);

        }
    });
}
//收文查看
function preview(that) {
    major_key = $(that).attr('major_key');
    layer.open({
        type: 1,
        title:'收文查看',
        area:["800px","620px"],
        content: $('#file_modal'),
        success:function () {
            $.viewFilter(false);
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
                }
            })
        }
    });
}