
//组织结构表格
var tableItem = $('#tableItem').DataTable({
  pagingType: "full_numbers",
  processing: true,
  serverSide: true,
  ajax: {
    "url": "../common/datatablesPre.shtml?tableName=norm_controlpoint&id=-1"
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
      name: "isimportant"
    },
    {
      name: "id"
    }
  ],
  columnDefs: [
    {
      "targets":[0]
    },
    {
      "targets": [1]
    },
    {
      "searchable": false,
      "orderable": false,
      "targets": [2],
      "render": function (data, type, row) {
        if (data == 1) {
          return "是";
        }
        return "否";
      }
    },
    {
      "searchable": false,
      "orderable": false,
      "targets": [3],
      "render": function (data, type, row) {
        var a = data;
        var html = "<a type='button'  class='' style='margin-left: 5px;' onclick='editFile("+row[3]+")'><i title='编辑' class='fa fa-pencil'></i></a>";
        html += "<a type='button' class='' style='margin-left: 5px;' onclick='delFile("+row[3]+")'><i title='删除' class='fa fa-trash'></i></a>";
        return html;
      }
    }

  ],
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
  },
  "fnInitComplete": function (oSettings, json) {
    $('#tableItem_length').insertBefore(".mark");
    $('#tableItem_info').insertBefore(".mark");
    $('#tableItem_paginate').insertBefore(".mark");
  }
});

//点击上传文件
$(".mybtn").html("<div id='test3'><i class='fa fa-plus'></i>新增控制点</div>");

//
layui.use(['form', 'layedit', 'laydate', 'element', 'layer'], function(){
  var form = layui.form
    ,layer = layui.layer;
});

//初始化树节点
var selfid, zTreeObj, sNodes, procedureId;

//初始化树
var setting = {
  view: {
    showLine: true, //设置 zTree 是否显示节点之间的连线。
    selectedMulti: false //设置是否允许同时选中多个节点。
  },
  async: {
    enable: true,
    autoParam: ["pid"],
    type: "post",
    url: "./GetDivsionTree?cat="+cat,
    dataType: "json"
  },
  data: {
    simpleData: {
      enable: true,
      idkey: "id",
      pIdKey: "pid",
      rootPId: null
    }
  },
  callback: {
    onClick: this.nodeClick
  }
};
zTreeObj = $.fn.zTree.init($("#ztree"), setting, null);

//点击获取路径
function nodeClick(e, treeId, node) {
  sNodes = zTreeObj.getSelectedNodes()[0];//选中节点
  selfid = zTreeObj.getSelectedNodes()[0].id;//当前id
  var path = sNodes.name; //选中节点的名字
  var node = sNodes.getParentNode();//获取父节点
  if (node) {
    //判断是否还有父节点
    while (node) {
      path = node.name + "-" + path;
      node = node.getParentNode();
    }
  } else {
    $(".layout-panel-center .panel-title").text(sNodes.name);
  }
  if(sNodes.level != 0){
    if(sNodes.level != 1){
      tableItem.ajax.url("../common/datatablesPre.shtml?tableName=norm_controlpoint&id="+selfid).load();

    }else if(sNodes.level == 1){
      tableItem.ajax.url("../common/datatablesPre.shtml?tableName=norm_controlpoint&id=-1").load();
    }
    $(".layout-panel-center .panel-title").text("当前路径:" + path);
  }else if(sNodes.level == 0){
    tableItem.ajax.url("../common/datatablesPre.shtml?tableName=norm_controlpoint&id=-1").load();
    $(".layout-panel-center .panel-title").text("当前路径:"+ path);
  }
  procedureId = selfid;
}

//点击添加节点
function addNodetree() {
  var pid = selfid ? selfid : 0;
  layer.prompt({title: '请输入节点名称'}, function (value, index, elem) {

    var type =  sNodes ? Number(sNodes.type)+1 : 0;
    $.ajax({
      url: "./adddivsiontree",
      type: "post",
      data: {pid: pid, name: value, cat:cat,type:type},
      success: function (res) {
        console.log(res);
        if (res.code === 1) {
            console.log(sNodes);
          if (sNodes) {
            zTreeObj.addNodes(sNodes, {"id":res.data,"pid":pid,"name":value,type:type});
          } else {
            zTreeObj.addNodes(null, {"id":res.data,"pid":pid,"name":value,type:type});
          }
        }
      }
    });
    layer.close(index);
  });
}

//编辑节点
function editNodetree() {
  if (!selfid) {
    layer.msg("请选择节点", {time: 1500, shade: 0.1});
    return;
  }
  console.log(sNodes);
  layer.prompt({title: '编辑', value: sNodes.name}, function (value, index, elem) {
    $.ajax({
      url: "./adddivsiontree",
      type: "post",
      data: {id: selfid, name: value, cat:2},
      success: function (res) {
        if (res.code === 1) {
          sNodes.name = value;
          zTreeObj.updateNode(sNodes);//更新节点名称
          layer.msg("编辑成功")
        }else{
          layer.msg(res.msg);
        }
      }
    });
    layer.close(index);
  });
}

//删除节点
function delNodetree() {
  if (!selfid) {
    layer.msg("请选择节点");
    return;
  }
  if (!sNodes.children) {
    layer.confirm("该操作会将关联数据同步删除，是否确认删除？", function () {
      $.ajax({
        url: "./deldivsion",
        type: "post",
        data: {id: selfid},
        success: function (res) {
          if (res.code === 1) {
            layer.msg("删除节点成功", {time: 1500, shade: 0.1});
            zTreeObj.removeNode(sNodes);
            selfid = "";
            sNodes  = null;
            //下面是更新列表
            tableItem.ajax.url("../common/datatablesPre.shtml?tableName=norm_controlpoint&id="+selfid).load();
          }else{
            layer.msg(res.msg);
          }
        }
      });
    });
  } else {
    layer.msg("包含下级，无法删除", {time: 1500, shade: 0.1});
  }

}


//全部展开
$('#openNode').click(function () {
  zTreeObj.expandAll(true);
});

//收起所有
$('#closeNode').click(function () {
  zTreeObj.expandAll(false);
});

//点击新增控制节点
$(".mybtn #test3").click(function () {
  layer.open({
    type: 2,
    title: '添加控制点信息',
    shadeClose: true,
    area: ['780px', '550px'],
    content: './addcontrollpoint',
    success: function(layero, index){
      var body = layer.getChildFrame('body', index);
      body.find("#denId").val(procedureId);
      body.find("#type").val(cat);
      body.find("#use").val(cat == 2 ? 3 : 2);
    },
    end:function () {
      tableItem.ajax.url("../common/datatablesPre.shtml?tableName=norm_controlpoint&id="+selfid).load();
    }
  });
});


//点击编辑模板
function editFile(id) {
  console.log(id);
  layer.open({
    type: 2,
    title: '编辑控制点信息',
    shadeClose: true,
    area: ['780px', '550px'],
    content: './addcontrollpoint?id='+id,
    success: function(layero, index){
      var body = layer.getChildFrame('body', index);
      body.find("#use").val(cat == 2 ? 3 : 2);
    },
    end:function () {
      tableItem.ajax.url("../common/datatablesPre.shtml?tableName=norm_controlpoint&id="+selfid).load();
    }
  });
};

//点击删除模板
function delFile(id) {
  console.log(id);
  layer.confirm('该操作会将数据删除，是否确认删除？', function(index){
    $.ajax({
      type: "post",
      url: "./delcontrolpoint",
      data: {id: id,type:cat},
      success: function (res) {
        if(res.code == 1){
          console.log(res)
          layer.msg("删除成功！")
          tableItem.ajax.url("../common/datatablesPre.shtml?tableName=norm_controlpoint&id="+selfid).load();
        }else if(res.code==0){
          layer.msg(res.msg);
        }
      }
    });
    layer.close(index);
  });
};
//节点移动方法
function moveNode(zTreeObj,selectNode,state) {
  var changeNode;
  var change_sort_id; //发生改变的排序id
  var change_id; //发生改变的id
  console.log(selectNode.sort_id)
  var select_sort_id = selectNode.sort_id;//选中的排序id
  var select_id = selectNode.id;//选中的id
  if(state === "next"){
    changeNode = selectNode.getNextNode();
    if (!changeNode){
      layer.msg('已经移到底啦');
      return false;
    }
  }else if(state === "prev"){
    changeNode = selectNode.getPreNode();
    if (!changeNode){
      layer.msg('已经移到顶啦');
      return false;
    }
  }
  change_id = changeNode.id;
  change_sort_id = changeNode.sort_id;
  console.log();
  $.ajax({
    url: "./sortNode",
    type: "post",
    data: {
      change_id:change_id, //影响节点id
      change_sort_id:change_sort_id, //影响节点sort_id
      select_id:select_id,//移动节点id
      select_sort_id:select_sort_id,//移动节点sort_id
    },
    dataType: "json",
    success: function (res) {
      if(res.code==1){
        zTreeObj.moveNode(changeNode, selectNode, state);
        changeNode.sort_id = select_sort_id;
        selectNode.sort_id = change_sort_id;
      }else{
        layer.msg(res.msg);
      }

    }
  });
}
// 点击节点移动
function getmoveNode(state) {
  if(sNodes===undefined||sNodes.length<=0){
    layer.msg("请选择节点");
    return;
  }

  moveNode(zTreeObj,sNodes,state);
}