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
  if (sNodes[0].type == '1') {
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

  $("#homeWork").css("color","#2213e9").siblings().css("color","#CDCDCD");
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
  ajax: {
    "url":"/quality/common/datatablesPre/tableName/quality_subdivision_planning_list.shtml"
  },
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
    $('#tableItem_info').insertBefore(".mark");
    $('.dataTables_wrapper,.tbcontainer').css("display","block");
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