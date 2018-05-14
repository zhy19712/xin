layui.use(['form', 'layedit', 'laydate', 'element', 'layer'], function(){
    var form = layui.form
        ,layer = layui.layer;
});
var selfid = "",conThisId = "" ;//树节点id, 工序id
//组织结构树
var setting = {
    view: {
        showLine: true, //设置 zTree 是否显示节点之间的连线。
        selectedMulti: false, //设置是否允许同时选中多个节点。
        // dblClickExpand: true //双击节点时，是否自动展开父节点的标识。
    },
    async: {
        enable : true,
        // autoParam: ["pid","id"],
        type : "post",
        url : "./index",
        dataType :"json"
    },
    data:{
        simpleData : {
            enable:true,
            idkey: "id",
            pIdKey: "pId",
            rootPId:0
        }
    },
    callback: {
        onClick: this.onClick
    }
};
//初始化树
zTreeObj = $.fn.zTree.init($("#ztree"), setting, null);
//点击获取路径
function onClick(e, treeId, node) {
    selectData = "";
    sNodes = zTreeObj.getSelectedNodes();//选中节点
    selfid = zTreeObj.getSelectedNodes()[0].id;
    var path = sNodes[0].name; //选中节点的名字
    node = sNodes[0].getParentNode();//获取父节点
    //判断是否还有子节点
    if (!sNodes[0].children) {
        //判断是否还有父节点
        selfidName()
        $("#tableContent .imgList").css('display','block');
    }
    groupid = sNodes[0].pId //父节点的id
    var url = "/quality/common/datatablespre/tableName/quality_subdivision_planning_list/selfid/"+selfid+".shtml";
    tableItem.ajax.url(url).load();
    $(".mybtn").css("display","none");//新增
    $(".alldel").css("display","none");//全部删除

    $("#homeWork").css("color","#2213e9");
}
//点击置灰
$(".imgList").on("click","a",function () {
    $(this).css("color","#2213e9").siblings("a").css("color","#CDCDCD");
    $("#homeWork").css("color","#CDCDCD");
});

//点击作业
$(".imgList").on("click","#homeWork",function () {
    $(".bitCodes").css("display","block");
    $(".mybtn").css("display","none");
    $(".alldel").css("display","none");
    $(this).css("color","#2213e9").parent("span").next("span").children("a").css("color","#CDCDCD");
    tableItem.ajax.url("/quality/common/datatablespre/tableName/quality_subdivision_planning_list/selfid/"+selfid+"/procedureid/"+conThisId+".shtml").load();
});
//点击工序控制点名字
function clickConName(id) {
    conThisId = id;
    $(".bitCodes").css("display","bitCodes");
    $(".mybtn").css("display","block");
    $(".alldel").css("display","block");
    $("#tableContent .imgList").css('display','block');
    tableItem.ajax.url("/quality/common/datatablespre/tableName/quality_subdivision_planning_list/selfid/"+selfid+"/procedureid/"+conThisId+".shtml").load();
}
//初始化表格
var tableItem = $('#tableItem').DataTable( {
    pagingType: "full_numbers",
    processing: true,
    serverSide: true,
    // scrollY: 600,
    ajax: {
        "url":"/quality/common/datatablesPre/tableName/quality_subdivision_planning_list.shtml"
    },
    dom: 'f<"alldel layui-btn layui-btn-sm"><"mybtn layui-btn layui-btn-sm"><"bitCodes layui-btn layui-btn-sm">rtlip',
    columns:[
        {
            name: "controller_point_number"
        },
        {
            name: "controller_point_name"
        },
        {
            name: "id"
        }
    ],
    columnDefs: [
        {
            "searchable": false,
            "orderable": false,
            "targets": [2],
            "render" :  function(data,type,row) {
                var a = data;
                var html =  "<a type='button' href='javasrcipt:;' class='' style='margin-left: 5px;' onclick='conDown("+data+")'><i class='fa fa-download'></i></a>" ;
                // html += "<a type='button' class='' style='margin-left: 5px;' onclick='conPrint("+data+")'><i class='fa fa-print'></i></a>" ;
                html += "<a type='button' class='' style='margin-left: 5px;' onclick='conDel("+data+")'><i class='fa fa-trash'></i></a>" ;
                return html;
            }
        }
    ],
    language: {
        "lengthMenu": "_MENU_",
        "zeroRecords": "没有找到记录",
        "info": "第 _PAGE_ 页 ( 共 _PAGES_ 页, _TOTAL_ 项 )",
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
        // $('.dataTables_wrapper,.tbcontainer').css("display","block");
    }
});
//
$(".bitCodes").html("<div id='bitCodes'><i class='fa fa-download' style='padding-right: 3px;'></i>导出二维码</div>");
$(".mybtn").html("<div id='test3'><i class='fa fa-plus'></i>新增控制点</div>");
$(".alldel").html("<div id='delAll'><i class='fa fa-close'></i>全部删除</div>");

//点击新增控制节点
$("#tableContent").on("click",".mybtn #test3",function () {
    console.log(conThisId);
    layer.open({
        type: 2,
        title: '控制点选择',
        shadeClose: true,
        area: ['980px', '673px'],
        content: './addplan?selfid='+ selfid + '&procedureid='+ conThisId,
        end:function () {
            tableItem.ajax.url("/quality/common/datatablespre/tableName/quality_subdivision_planning_list/selfid/"+selfid+"/procedureid/"+conThisId+".shtml").load();
        }
    });
});
//点删除全部节点
$("#tableContent").on("click","#delAll",function () {
    conDelAll();
});

//删除控制点
function conDel(id) {
    console.log(id);
    $.ajax({
        type: "post",
        url: "./controlDel",
        data: {id:id},
        success: function (res) {
            console.log(res);
            if(res.code ==1){
                layer.msg("删除成功！")
                tableItem.ajax.url("/quality/common/datatablespre/tableName/quality_subdivision_planning_list/selfid/"+selfid+"/procedureid/"+conThisId+".shtml").load();
            }else{
                layer.msg(res.msg);
            }
        }
    })
}
//删除全部
function  conDelAll() {
    $.ajax({
        type: "post",
        url: "./controlAllDel",
        data: {selfid:selfid,procedureid:conThisId},
        success: function (res) {
            console.log(res);
            if(res.code ==1){
                layer.msg("删除成功！")
                tableItem.ajax.url("/quality/common/datatablespre/tableName/quality_subdivision_planning_list/selfid/"+selfid+"/procedureid/"+conThisId+".shtml").load();
            }else{
                layer.msg(res.msg);
            }
        }
    });
};
//下载
function download(id,url,type_model) {
    var url1 = url;
    $.ajax({
        url: url,
        type:"post",
        dataType: "json",
        data:{id:id,type_model : type_model},
        success: function (res) {
            if(res.code != 1){
                layer.msg(res.msg);
            }else {
                $("#form_container").empty();
                var str = "";
                str += ""
                    + "<iframe name=downloadFrame"+ id +" style='display:none;'></iframe>"
                    + "<form name=download"+id +" action="+ url1 +" method='get' target=downloadFrame"+ id + ">"
                    + "<span class='file_name' style='color: #000;'>"+str+"</span>"
                    + "<input class='file_url' style='display: none;' name='id' value="+ id +">"
                    + "<input class='file_type' style='display: none;' name='type_model' value="+ type_model +">"
                    + "<button type='submit' class=btn" + id +"></button>"
                    + "</form>"
                $("#form_container").append(str);
                $("#form_container").find(".btn" + id).click();
            }

        }
    })
}
//点击导出二维码
$("#tableContent").on("click",".bitCodes",function () {
    download(selfid,"./exportCode");
});
//下载模板
function conDown(id) {
    download(id,"./fileDownload","BranchfileModel");
};
//预览
function showPdf(id,url,type_model) {
    $.ajax({
        url: url,
        type: "post",
        data: {id:id,type_model : type_model},
        success: function (res) {
            if(res.code === 1){
                var path = res.path;
              var houzhui = res.path.split(".");
              if(houzhui[houzhui.length-1]=="pdf"){
                    window.open("/static/public/web/viewer.html?file=../../../" + path,"_blank");
                }else if(res.path.split(".")[1]==="png"||res.path.split(".")[1]==="jpg"||res.path.split(".")[1]==="jpeg"){
                    layer.photos({
                        photos: {
                            "title": "", //相册标题
                            "id": id, //相册id
                            "start": 0, //初始显示的图片序号，默认0
                            "data": [   //相册包含的图片，数组格式
                                {
                                    "alt": "图片名",
                                    "pid": id, //图片id
                                    "src": "../../../"+res.path, //原图地址
                                    "thumb": "" //缩略图地址
                                }
                            ]
                        }
                        ,anim: Math.floor(Math.random()*7) //0-6的选择，指定弹出图片动画类型，默认随机（请注意，3.0之前的版本用shift参数）
                    });
                }else{
                    layer.msg("不支持的文件格式");
                }

            }else {
                layer.msg(res.msg);
            }
        }
    })
}
//预览打印
function conPrint(id){
    showPdf(id,'./printDocument',"BranchfileModel");
}