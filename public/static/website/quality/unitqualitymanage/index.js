//初始化layui组件
layui.use(['form', 'layedit', 'laydate', 'element', 'layer'], function(){
  var form = layui.form
    ,layer = layui.layer;
});
//ztree

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
  sNodes = zTreeObj.getSelectedNodes();//选中节点
  console.log(sNodes)
  selfid = zTreeObj.getSelectedNodes()[0].id;
  var path = sNodes[0].name; //选中节点的名字
  node = sNodes[0].getParentNode();//获取父节点
  //判断是否还有子节点
  if (!sNodes[0].children) {
    //判断是否还有父节点
    selfidName()
    $("#tableContent .imgList").css('display','block');
  }
  var url = "/quality/common/datatablesPre?tableName=unit_quality_control&add_id="+selfid;
  tableItem.ajax.url(url).load();
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
  tableItem.ajax.url("/quality/common/datatablesPre?tableName=unit_quality_control&add_id="+selfid+"/workId/"+conThisId+".shtml").load();
}


//初始化表格
var tableItem = $('#tableItem').DataTable( {
  pagingType: "full_numbers",
  processing: true,
  serverSide: true,
  // scrollY: 600,
  ajax: {
    'url':'/quality/common/datatablesPre?tableName=unit_quality_control'
  },
  // dom: 'f<"alldel layui-btn layui-btn-sm"><"mybtn layui-btn layui-btn-sm"><"bitCodes layui-btn layui-btn-sm">rti',
  dom:'frti',
  columns:[
    {
      name: "id"
    },
    {
      name: "code"
    },
    {
      name: "name"
    }
  ],
  columnDefs: [
    {
      "searchable": false,
      "orderable": false,
      "targets": [0],
      "render" :  function(data,type,row) {
        var html = "<input type='checkbox' class='checkList' id='"+data+"'>";
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
    $('.dataTables_wrapper,.tbcontainer').css("display","block");
  }
});