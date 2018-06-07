layui.use(['form', 'layedit', 'laydate', 'element', 'layer'], function(){
    var form = layui.form
        ,layer = layui.layer;
});
var selfid = "",conThisId = "0" ;//树节点id, 工序id
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
  conThisId = "0";
    sNodes = zTreeObj.getSelectedNodes();//选中节点
    selfid = zTreeObj.getSelectedNodes()[0].id;
    var path = sNodes[0].name; //选中节点的名字
    node = sNodes[0].getParentNode();//获取父节点
    //判断是否是分部
    if (sNodes[0].type == '3') {
        //
        selfidName()
        $("#tableContent .imgList").css('display','block');
      var url = "/quality/common/datatablespre/tableName/quality_subdivision_planning_list/checked//selfid/"+selfid+".shtml";
      tableItem.ajax.url(url).load();
    }else{
      $("#tableContent .imgList").hide()
      var url = "/quality/common/datatablesPre/tableName/quality_subdivision_planning_list.shtml";
      tableItem.ajax.url(url).load();
    }
    $(".mybtn").css("display","none");//新增
    $(".alldel").css("display","none");//全部删除

    $("#homeWork").css("color","#00c0ef").siblings().css("color","#333333");
}
//点击置灰
$(".imgList").on("click","a",function () {
    $(this).css("color","#00c0ef").siblings("a").css("color","#333333");
    $("#homeWork").css("color","#333333");
});

//点击作业
$(".imgList").on("click","#homeWork",function () {
    $(".bitCodes").css("display","block");
    $(".mybtn").css("display","none");
    $(".alldel").css("display","none");
    $(this).css("color","#00c0ef").parent("span").next("span").children("a").css("color","#333333");
    tableItem.ajax.url("/quality/common/datatablespre/tableName/quality_subdivision_planning_list/checked//selfid/"+selfid+"/procedureid/"+conThisId+".shtml").load();
});
//点击工序控制点名字
function clickConName(id) {
    conThisId = id;
    $(".bitCodes").css("display","bitCodes");
    $(".mybtn").css("display","block");
    $(".alldel").css("display","block");
    $("#tableContent .imgList").css('display','block');
    tableItem.ajax.url("/quality/common/datatablespre/tableName/quality_subdivision_planning_list/checked//selfid/"+selfid+"/procedureid/"+conThisId+".shtml").load();
}
//初始化表格
var tableItem = $('#tableItem').DataTable( {
    pagingType: "full_numbers",
    processing: true,
    serverSide: true,
    iDisplayLength:1000,
    "scrollY": "450px",
    "order": [[ 1, "asc" ]],
    // scrollY: 600,
    ajax: {
        "url":"/quality/common/datatablesPre/tableName/quality_subdivision_planning_list.shtml"
    },
    // dom: 'f<"alldel layui-btn layui-btn-sm"><"mybtn layui-btn layui-btn-sm"><"bitCodes layui-btn layui-btn-sm">rti',
    dom:'frti',
    columns:[
        {
            name: "checked"
        },
        {
            name: "code"
        },
        {
            name: "name"
        },
      {
              name:"id"
      }

    ],
    columnDefs: [
        {
            "searchable": false,
            "orderable": false,
            "targets": [0],
            "render" :  function(data,type,row) {
                if(data == 0){
                  var html = "<input type='checkbox' class='checkList' checked id='"+row[3]+"'>";
                }else{
                  var html = "<input type='checkbox' class='checkList' id='"+row[3]+"'>";
                }
                return html;
            }
        },
      {
        targets:[3],
        "visible": false
      }
    ],
    language: {
        "lengthMenu": "_MENU_",
        "zeroRecords": "没有找到记录",
        "info": "( 共_TOTAL_ 项 )",
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
        $('.dataTables_wrapper,.tbcontainer').css("display","block");
      //表头固定的滚动条
      $('#tableContent .dataTables_scroll').on('scroll',function(){
        $("#tableContent .dataTables_scrollHead").css("top",$(this).scrollTop())
      });
    },
  "fnDrawCallback":function () {
    var lock = 1;
    $(".checkList").each(function (i,item) {
      if(!$(item).is(":checked")){
        lock = 0;
        return;
      }
    });
    if( lock == 0){
      $('#all_checked').prop("checked",false);
    }else{
      if($(".checkList").length == 0){
        $('#all_checked').prop("checked",false);
      }else{
        $('#all_checked').prop("checked",true);
      }
    }
  }
});
//
// $(".bitCodes").html("<div id='bitCodes'><i class='fa fa-download' style='padding-right: 3px;'></i>导出二维码</div>");
// $(".mybtn").html("<div id='test3'><i class='fa fa-plus'></i>新增控制点</div>");
// $(".alldel").html("<div id='delAll'><i class='fa fa-close'></i>全部删除</div>");

//点击新增控制节点
$("#tableContent").on("click",".mybtn #test3",function () {
    layer.open({
        type: 2,
        title: '控制点选择',
        shadeClose: true,
        area: ['980px', '673px'],
        content: './addplan?selfid='+ selfid + '&procedureid='+ conThisId,
        end:function () {
            tableItem.ajax.url("/quality/common/datatablespre/tableName/quality_subdivision_planning_list/checked//selfid/"+selfid+"/procedureid/"+conThisId+".shtml").load();
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


//全选 全不选
$("#all_checked").on('click',function () {
  var checked;
    if($(this).is(':checked')){
        $(".checkList").prop("checked",true);
        checked = "All";
    }else{
        $(".checkList").prop("checked",false);
       checked = "noAll";
    }
    $.ajax({
      url: './checkBox',
      data: {
        division_id: selfid,
        ma_division_id: conThisId,
        checked: checked
      },
      type: "POST",
      dataType: "JSON",
      success: function (res) {
        if(res.code == 1){
          if(conThisId != 0){
            tableItem.ajax.url("/quality/common/datatablespre/tableName/quality_subdivision_planning_list/checked//selfid/"+selfid+"/procedureid/"+conThisId+".shtml").load();
          }
        }else{
          layer.msg(res.msg);
          tableItem.ajax.url("/quality/common/datatablespre/tableName/quality_subdivision_planning_list/checked//selfid/"+selfid+"/procedureid/"+conThisId+".shtml").load();
        }
      }
    })
});

// 选中或取消 checkBox
$("#tableItem").on('click','.checkList',function () {
    var id = $(this).attr('id');
    var checked;
  if($(this).is(':checked')){
    checked = 0;
  }else{
    checked = 1;
  }
  $.ajax({
    url:'./checkBox',
    data:{
      id:id,
      checked:checked
    },
    type:"POST",
    dataType:"JSON",
    success:function (res) {
      if(res.code == 1){
        if(checked == 1 ){
            $('#all_checked').prop("checked",false);
        }else{
            var lock = 1;
            $(".checkList").each(function (i,item) {
                if(!$(item).is(":checked")){
                 lock = 0;
                  return;
                }
              });
          if( lock == 0){
            $('#all_checked').prop("checked",false);
          }else{
            $('#all_checked').prop("checked",true);
          }
        }

        if(conThisId != 0){
          tableItem.ajax.url("/quality/common/datatablespre/tableName/quality_subdivision_planning_list/checked//selfid/"+selfid+"/procedureid/"+conThisId+".shtml").load();
        }
      }else{
          layer.msg(res.msg);
        tableItem.ajax.url("/quality/common/datatablespre/tableName/quality_subdivision_planning_list/checked//selfid/"+selfid+"/procedureid/"+conThisId+".shtml").load();
      }
    }
  })
});

//