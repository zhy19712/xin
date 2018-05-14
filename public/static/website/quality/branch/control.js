var selfid = "",conThisId = "" ,list_id = '' ;//树节点id, 工序id，选中的id
var attachment_id = "",file_name='',type=1;//文件id，文件名,控制点或者图像
layui.use(['form', 'layedit', 'laydate', 'element', 'layer','upload'], function(){
    var form = layui.form
        ,upload = layui.upload
        ,layer = layui.layer;

    upload.render({
        elem: '.uploadBox',
        url: "/quality/common/upload?module=quality&use=quality_thumb",
        accept: 'file',//普通文件
        size:8192,
        before: function(obj){
            obj.preview(function(index, file, result){
                attachment_id = file.id;
                file_name = file.name.split('.')[0];
            })
        },
        done:function (res) {
          if(res.code!=2){
            layer.msg("上传失败");
            return ;
          }
            attachment_id = res.id;
            if($(".tabs-selected a span:first-child").html()==="图像资料"){
                type = 2;
            }
           $.ajax({
               url:"./addFile",
               type:"POST",
               data:{
                   filename:file_name,
                   attachment_id:attachment_id,
                   list_id:list_id,
                   type:type
               },
               dataType:"JSON",
               success :function (res) {
                   if(res.code===1){
                       tableItem.ajax.url("/quality/common/datatablespre/tableName/quality_subdivision_planning_list/selfid/"+selfid+"/procedureid/"+conThisId+".shtml").load();
                       tableSituation.ajax.url("/quality/common/datatablesPre/tableName/quality_subdivision_planning_file/type/1/list_id/"+list_id+".shtml").load();
                       tableImage.ajax.url("/quality/common/datatablesPre/tableName/quality_subdivision_planning_file/type/2/list_id/"+list_id+".shtml").load();
                   }else{
                     layer.msg(res.msg);
                   }
               }
           })
        }
    });

});

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
    conThisId = 0;
    list_id= "";
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
  tableSituation.ajax.url("/quality/common/datatablesPre/tableName/quality_subdivision_planning_file/type/1/list_id/"+list_id+".shtml").load();
  tableImage.ajax.url("/quality/common/datatablesPre/tableName/quality_subdivision_planning_file/type/2/list_id/"+list_id+".shtml").load();
    $("#homeWork").css("color","#2213e9");
}
//点击置灰
$(".imgList").on("click","a",function () {
    $(this).css("color","#2213e9").siblings("a").css("color","#CDCDCD");
    $("#homeWork").css("color","#CDCDCD");
});

//点击作业
$(".imgList").on("click","#homeWork",function () {
    $(this).css("color","#2213e9").parent("span").next("span").children("a").css("color","#CDCDCD");
    tableItem.ajax.url("/quality/common/datatablespre/tableName/quality_subdivision_planning_list/selfid/"+selfid+"/procedureid/"+conThisId+".shtml").load();
    $(".uploadBox").hide();
});
//点击工序控制点名字
function clickConName(id) {
    conThisId = id;
  list_id= "";
  $("#tableContent .imgList").css('display','block');
  tableSituation.ajax.url("/quality/common/datatablesPre/tableName/quality_subdivision_planning_file/type/1/list_id/"+list_id+".shtml").load();
  tableImage.ajax.url("/quality/common/datatablesPre/tableName/quality_subdivision_planning_file/type/2/list_id/"+list_id+".shtml").load();
  tableItem.ajax.url("/quality/common/datatablespre/tableName/quality_subdivision_planning_list/selfid/"+selfid+"/procedureid/"+conThisId+".shtml").load();
}
//初始化表格
var tableItem = $('#tableItem').DataTable( {
    processing: true,
    serverSide: true,
    iDisplayLength:1000,
    "scrollY": "200px",
    "scrollCollapse": "true",
    "paging": "false",
    ajax: {
        "url":"/quality/common/datatablesPre/tableName/quality_subdivision_planning_list"
    },
    dom: 'rt',
    columns:[
        {
            name: "controller_point_number"
        },
        {
            name: "controller_point_name"
        },
        {
            name:"status",
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
                if(data==0){
                    var html = '<span style="color: red;">未执行</span>'
                }else if(data==1) {
                    var html = '<span style="color: green;">已执行</span>'
                }
                return html;
            }
        },
        {
            "searchable": false,
            "orderable": false,
            "targets": [3],
            "render" :  function(data,type,row) {
                var a = data;
                var html =  "<a type='button' href='javasrcipt:;' class='' style='margin-left: 5px;' onclick='conDown("+data+")'><i class='fa fa-download'></i></a>" ;
                // html += "<a type='button' class='' style='margin-left: 5px;' onclick='conPrint("+data+")'><i class='fa fa-print'></i></a>" ;
                return html;
            }
        }
    ],
    language: {
        "zeroRecords": "没有找到记录"
    }
});
//初始化表格
var tableSituation = $('#tableSituation').DataTable( {
    pagingType: "full_numbers",
    processing: true,
    serverSide: true,
    // scrollY: 600,
    ajax: {
        "url":"/quality/common/datatablesPre/tableName/quality_subdivision_planning_file/type//list_id/.shtml"
    },
    dom: 'rtlip',
    columns:[
        {
            name: "filename"
        },
        {
            name: "owner"
        },
        {
            name:"company",
        },
        {
            name:"date",
        },
        {
            name: "id"
        }
    ],
    columnDefs: [
        {
            "searchable": false,
            "orderable": false,
            "targets": [4],
            "render" :  function(data,type,row) {
                var a = data;
                var html =  "<a type='button' href='javasrcipt:;' class='' style='margin-left: 5px;' onclick='conPicshow("+data+")'><i class='fa fa-search'></i></a>" ;
                html += "<a type='button' class='' style='margin-left: 5px;' onclick='conDown2("+data+")'><i class='fa fa-download'></i></a>" ;
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
        $('#tableSituation_length').insertBefore(".markSituation");
        $('#tableSituation_info').insertBefore(".markSituation");
        $('#tableSituation_paginate').insertBefore(".markSituation");
        $('.dataTables_wrapper,.tbcontainer').css("display","block");
    }
});//初始化表格
//初始化表格
var tableImage = $('#tableImage').DataTable( {
    pagingType: "full_numbers",
    processing: true,
    serverSide: true,
    // scrollY: 600,
    ajax: {
        "url":"/quality/common/datatablesPre/tableName/quality_subdivision_planning_file/type//list_id/.shtml"
    },
    dom: 'rtlip',
    columns:[
        {
            name: "filename"
        },
        {
            name: "owner"
        },
        {
            name:"company",
        },
        {
            name:"date",
        },
        {
            name: "id"
        }
    ],
    columnDefs: [
        {
            "searchable": false,
            "orderable": false,
            "targets": [4],
            "render" :  function(data,type,row) {
                var a = data;
                var html =  "<a type='button' href='javasrcipt:;' class='' style='margin-left: 5px;' onclick='conPicshow("+data+")'><i class='fa fa-search'></i></a>" ;
                html += "<a type='button' class='' style='margin-left: 5px;' onclick='conDown2("+data+")'><i class='fa fa-download'></i></a>" ;
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
        $('#tableImage_length').insertBefore(".markImage");
        $('#tableImage_info').insertBefore(".markImage");
        $('#tableImage_paginate').insertBefore(".markImage");
        $('.dataTables_wrapper,.tbcontainer').css("display","block");
    }
});
//删除控制点
function conDel(id) {
    if($(".tabs-selected a span:first-child").html()==="图像资料"){
        type=2;
    }
    $.ajax({
        type: "post",
        url: "./delete",
        data: {id:id,type:type,list_id:list_id},
        success: function (res) {
            console.log(res);
            if(res.code ==1){
                layer.msg("删除成功！");
                tableItem.ajax.url("/quality/common/datatablespre/tableName/quality_subdivision_planning_list/selfid/"+selfid+"/procedureid/"+conThisId+".shtml").load();
                tableSituation.ajax.url("/quality/common/datatablesPre/tableName/quality_subdivision_planning_file/type/1/list_id/"+list_id+".shtml").load();
                tableImage.ajax.url("/quality/common/datatablesPre/tableName/quality_subdivision_planning_file/type/2/list_id/"+list_id+".shtml").load();
            }else{
                layer.msg(res.msg);
            }
        }
    })
}
//获取点击行
$("#tableItem").delegate("tbody tr","click",function (e) {
    if($(e.target).hasClass("dataTables_empty")){
        return;
    }
    if(conThisId!=0){
        $(".uploadBox").show();
    }
    $(this).addClass("select-color").siblings().removeClass("select-color");
    selectData = tableItem.row(".select-color").data();//获取选中行数据
    list_id = selectData[3];
    tableSituation.ajax.url("/quality/common/datatablesPre/tableName/quality_subdivision_planning_file/type/1/list_id/"+list_id+".shtml").load();
    tableImage.ajax.url("/quality/common/datatablesPre/tableName/quality_subdivision_planning_file/type/2/list_id/"+list_id+".shtml").load();
});

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
//下载控制点情况 或图像
function conDown2(id) {

    download(id,"../Common/download","BranchfileModel")
}
//下载 控制点模板
function conDown(id) {
    download(id,"./fileDownload","BranchfileModel")
}
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
//预览
function conPicshow(id){
    showPdf(id,'../Common/preview',"BranchfileModel");
}
//打印
function conPrint(id) {
    showPdf(id,'./printDocument',"BranchfileModel");
}