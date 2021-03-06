//组织结构表格
var tableItem = $('#tableItem').DataTable({
    pagingType: "full_numbers",
  // "aLengthMenu" : [10, 20, 60,100], //更改显示记录数选项
  "pageLength": 25,
  retrieve: true,
  processing: true,
  serverSide: true,
    "scrollY": "200px",
    "scrollCollapse": "true",
    ajax: {
        "url":"/standard/common/datatablesPre?tableName=norm_template"
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
          "orderable":false,
            width:'250px'
        },
        {
            "searchable": false,
          "orderable":false,
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
          "orderable":false,
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
                var html = "<span class='' style='margin-left: 5px;' onclick='editFile("+row[4]+")'><i title='编辑' class='fa fa-pencil'></i></span>";
                html += "<span class='' style='margin-left: 5px;' onclick='delFile("+row[4]+")'><i title='删除' class='fa fa-trash'></i></span>";
                html += "<span class='' style='margin-left: 5px;' onclick='downloadFile("+row[4]+")'><i title='下载' class='fa fa-download'></i></span>";
                // html += "<span class='' style='margin-left: 5px;' onclick='seatemplate("+row[4]+")'><i title='查看' class='fa fa-search'></i></span>";
                return html;
            }
        }
    ],
    language: {
        "lengthMenu": "_MENU_",
        "zeroRecords": "没有找到记录",
        "info": "第 _PAGE_ 页 ( 总共 _PAGES_ 页 )",
        "infoEmpty": "无记录",
      "sSearchPlaceholder":"请输入关键字",
        "search": "搜索",
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
      //表头固定的滚动条
      $('#tableContent .dataTables_scroll').on('scroll',function(){
        $("#tableContent .dataTables_scrollHead").css("top",$(this).scrollTop())
      });
    }
});

//点击上传文件
$(".mybtn").html("<div id='test3'>新增模板</div>");

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
        tableItem.ajax.url("/standard/common/datatablesPre?tableName=norm_template&type="+typeId+"&use="+$("#use").val()).load();
    });

    form.on('select(purpose)', function(data){
        purposeId = data.value;
        console.log(typeId);
        console.log(purposeId);

        if(purposeId == ""){
            tableItem.ajax.url("/standard/common/datatablesPre?tableName=norm_template&type="+typeId).load();
        }else{
            tableItem.ajax.url("/standard/common/datatablesPre?tableName=norm_template&type="+typeId+"&use="+purposeId).load();
        }
        tableItem.ajax.url("/standard/common/datatablesPre?tableName=norm_template&use="+purposeId+"&type="+$("#typeName").val()).load();
    });

    //监听提交
    // form.on('submit(demo1)', function(data){
    //     tableItem.ajax.url("/standard/common/datatablesPre?tableName=norm_template&type="+data.field.type+"&use="+data.field.use).load();
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
                    tableItem.ajax.url("/standard/common/datatablesPre?tableName=norm_template").load();
                }else{
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

//测试用
function downloadFile(id) {
        $("#form_container").empty();
        var str = "";
        str += ""
          + "<iframe name=downloadFrame"+ id +" style='display:none;'></iframe>"
          + "<form name=download"+ id +" action='./download' method='get' target=downloadFrame"+ id + ">"
          + "<span class='file_name' style='color: #000;'>"+str+"</span>"
          + "<input class='file_url' style='display: none;' name='id' value="+ id +">"
          + "<button type='submit' class=btn" + id +"></button>"
          + "</form>"
        $("#form_container").append(str);
        $("#form_container").find(".btn" + id).click();

}
// var datainfo = [
// ];
// var iiiii = 0;
// //添加数据
// function addData() {
//  var code = datainfo[iiiii].substring(0,8),name = datainfo[iiiii].substring(8);
//   $.ajax({
//     type: "Post",
//     url: "./add",
//     data: {
//         id:"",
//         code:code,
//         name: name,
//         type: 1,
//         use: 1
//     },
//     success: function (res) {
//         console.log(code);
//         iiiii++;
//         if(iiiii<datainfo.length){
//           addData();
//         }
//
//     }
//   })
// }

//TODO 修改模板样式专用 以后要删除
function seatemplate(id){
    $.ajax({
        type: "post",
        url: "/standard/templatemanage/preview",
        data: {id: id},
        success: function (res) {

            // console.log(res.url)
            // window.open('file:///E:/phpStudy/WWW/xin/public/Data/form/qualityNew/01.02.04%E5%B2%A9%E7%9F%B3%E5%9C%B0%E5%9F%BA%E5%BC%80%E6%8C%96%E5%9C%B0%E8%B4%A8%E7%BC%BA%E9%99%B7%E5%A4%84%E7%90%86%E5%B7%A5%E5%BA%8F%E8%B4%A8%E9%87%8F%E7%AD%89%E7%BA%A7%E8%AF%84%E5%AE%9A%E8%A1%A8%E4%B8%8B%E8%BD%BD.html')
            layer.open({
                type: 2,
                title: '查看',
                shadeClose: true,
                area: ['780px', '550px'],
                content: 'file:///E:/phpStudy/WWW/xin/public/Data/form/qualityNew/01.01.01%E5%B2%A9%E7%9F%B3%E8%BE%B9%E5%9D%A1%E5%BC%80%E6%8C%96%E5%8D%95%E5%85%83%E5%B7%A5%E7%A8%8B%E8%B4%A8%E9%87%8F%E7%AD%89%E7%BA%A7%E8%AF%84%E5%AE%9A%E8%A1%A8%E4%B8%8B%E8%BD%BD.html'
            });
        }
    })
}