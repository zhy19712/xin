//
layui.use(['element',"layer",'form','laypage','laydate','layedit'], function(){
  var $ = layui.jquery
    ,element = layui.element; //Tab的切换功能，切换事件监听等，需要依赖element模块

  var form = layui.form
    ,layer = layui.layer
    ,layedit = layui.layedit
    ,laydate = layui.laydate;

  //监听提交
  form.on('submit(demo1)', function(data){
    $.ajax({
      type: "post",
      url:"./editCate",
      data:data.field,
      success: function (res) {
        if(res.code == 1) {
          tableItem.ajax.url("/filemanagement/common/datatablespre/tableName/file_project_management.shtml").load();
          layer.closeAll();
          layer.msg("保存成功");
        }else{
          layer.msg(res.msg);
        }
      }
    });
    return false;
  });
  form.on('submit(demo2)', function(data){
    $.ajax({
      type: "post",
      url:"./editCate",
      data:data.field,
      success: function (res) {
        if(res.code == 1) {
          tableItem.ajax.url("/filemanagement/common/datatablespre/tableName/file_project_management.shtml").load();
          $("#memberAdd input ,#memberAdd select,#memberAdd textarea").val("");
          $(".datepickers").each(function (index,that) {
            laydate.render({
              elem: that
              ,value: new Date() //参数即为：2018-08-20 20:08:08 的时间戳
            });
          });
        }else{
          layer.msg(res.msg);
        }
      }
    });
    return false;
  });
  //日期选择器
  $(".datepickers").each(function (index,that) {
    laydate.render({
      elem: that
      ,value: new Date() //参数即为：2018-08-20 20:08:08 的时间戳
    });
  });


});

//选中的节点，选中的表格的信息
var sNodes,selectData="";
//初始化表格
var tableItem = $('#tableItem').DataTable( {
  pagingType: "full_numbers",
  processing: true,
  ordering:false,
  serverSide: true,
  ajax: {
    "url":"/filemanagement/common/datatablespre/tableName/file_project_management.shtml"
  },
  dom: 'rtlip',
  columns:[
    {
      name: "directory_code"
    },
    {
      name: "entry_name"
    },
    {
      name: "construction_unit"
    },
    {
      name: "id"
    },
    {
      name: "id"
    },
    {
      name: "id"
    },
    {
      name: "id"
    }
  ],
  columnDefs: [
    {
      "searchable": false,
      "orderable": false,
      "targets": [6],
      "render" :  function(data,type,row) {
        var a = data;
        var html =  "<a type='button' href='javasrcipt:;' class='' style='margin-left: 5px;' onclick='conShow("+data+")'><i class='fa fa-search'></i></a>" ;
        html += "<a type='button' class='' style='margin-left: 5px;' onclick='conEdit("+data+")'><i class='fa fa-pencil'></i></a>" ;
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
    $('.dataTables_wrapper,.tbcontainer').css("display","block");
  }
});

//获取点击行
$("#tableItem").delegate("tbody tr","click",function (e) {
  if($(e.target).hasClass("dataTables_empty")){
    return;
  }
  $(this).addClass("select-color").siblings().removeClass("select-color");
  selectData = tableItem.row(".select-color").data();//获取选中行数据

  if($(e.target).hasClass("fa-pencil")){
  }
});

//新增
function addproject() {
  layer.open({
    type: 1,
    title: '',
    closeBtn:0,
    area: ['100%', '100%'],
    content:$("#memberAdd"),
    success:function (){
      $(".datepickers").each(function (index,that) {
        layui.laydate.render({
          elem: that
          ,value: new Date() //参数即为：2018-08-20 20:08:08 的时间戳
        });
      });
    },
    end:function () {
      $("#memberAdd input ,#memberAdd select,#memberAdd textarea").val("");
    }
  });
}

//添加option 的代码
function addoption(data){
  var html = '';
  for (var i = 0 ; i<data.length;i++){
    html += '<option value="'+data[i]+'">'+data[i]+'</option>';
  }
  return html;
}
//拉取项目类别
function getBranchType() {
  $.ajax({
    url:"./getBranchType",
    type:"GET",
    dataType:"json",
    success:function (res) {
      $("#project_category").append(addoption(res.data));
      layui.form.render('select');
    } 
  })
}
//拉取建设单位,施工单位,设计单位,监理单位
function getGroup() {
  $.ajax({
    url:"./getGroup",
    type:"POST",
    data:{type:1},
    dataType:"json",
    success:function (res) {
      if(res.code==1){
        $("#construction_unit").append(addoption(res.data['建设单位']));
        $("#builder_unit").append(addoption(res.data['施工单位']));
        $("#design_unit").append(addoption(res.data['设计单位']));
        $("#construction_control_unit").append(addoption(res.data['监理单位']));
        layui.form.render('select');
      }else{
        layer.msg(res.msg);
      }

    }
  })
};
getBranchType();
getGroup();

//配置
function setcog() {
  if(selectData==""){
    layer.msg("请先选择项目");
    return ;
  };
  $("#conid").val(selectData[6]);
  zTreeObj.reAsyncChildNodes(null, "refresh",true,function f() {
    $.ajax({
      url:"./getindex",
      type:"POST",
      data:{id:selectData[6]},
      dataType:"JSON",
      success:function (res) {
        if(res.data.branch_id.length>0){
          $.each(res.data.branch_id, function (i, item) {
            var node = zTreeObj.getNodeByParam("id", item);
            zTreeObj.checkNode(node, true, false);
          });
        }
      }
    });
  });

  layer.open({
    type: 1,
    title: '配置',
    area: ['660px', '488px'],
    content:$("#config")
  });
}
//获取配置树
// function getBranchTree() {
//
//   $.ajax({
//     url:'./getBranchTree',
//     data:{type:1},
//     type:"POST",
//     dataType:"JSON",
//     success:function (res) {
//       console.log(res)
//       if(res.code==1){
//           console.log(res)
//       }else{
//         layer.msg(res.msg);
//       }
//     }
//   })
// }
//数据处理
function DataFilter(treeId, parentNode, data) {
  if (data) {
    $.each(data, function (i, item) {
      item.class_name = item.code + "("+item.class_name+")";
    });
  }
  return data;

}
//初始化配置树
var setting = {
  view: {
    showLine: true, //设置 zTree 是否显示节点之间的连线。
    selectedMulti: false, //设置是否允许同时选中多个节点。
    // dblClickExpand: true //双击节点时，是否自动展开父节点的标识。
  },
  async: {
    enable : true,
    autoParam: ["pid","id"],
    type : "post",
    url : "./getBranchTree",
    otherParam: {"id":function () {
        return $("#conid").val();
      }},
    dataType :"json",
    dataFilter:DataFilter

  },
  data:{
    simpleData : {
      enable:true,
      idkey: "id",
      pIdKey: "pid",
      rootPId:0
    },
    key:{
      name:"class_name"
    }
  },
  check:{
    enable: true,
  }
};
//初始化树
zTreeObj = $.fn.zTree.init($("#ztree"), setting, null);

//配置保存
function savecog() {
  var idArr = [];
  var Snodes = zTreeObj.getCheckedNodes(true);
  $.each(Snodes, function (i, item) {
    idArr.push(item.id);
  });
  $.ajax({
    url:'./addConfig',
    type:"POST",
    data:{idArr:idArr,id:$("#conid").val()},
    dataType:"JSON",
    success:function (res) {
      if(res.code==1){
        layer.msg("保存成功");
        layer.closeAll();
      }else{
        layer.msg(res.msg);
      }
    }
  })
  layer.closeAll();
}

//获取点击行
$("#tableItem").delegate("tbody tr","click",function (e) {
  if($(e.target).hasClass("dataTables_empty")){
    return;
  }
  $(this).addClass("select-color").siblings().removeClass("select-color");
  selectData = tableItem.row(".select-color").data()
  console.log(selectData)
});

//展示
function conShow(id) {
  $("#memberAdd input ,#memberAdd select,#memberAdd textarea").attr("disabled",true);
  $("#btnBox").hide();
  $.ajax({
    url:"./getindex",
    type:"POST",
    data:{id:id},
    dataType:"JSON",
    success:function (res) {
      if(res.code == 1){
          $("#directory_code").val(res.data.directory_code);
          $("#project_code").val(res.data.project_code);
          $("#revision_code").val(res.data.revision_code);
          $("#entry_name").val(res.data.entry_name);
          $("#construction_unit").val(res.data.construction_unit);
          $("#project_category").val(res.data.project_category);
          $("#operation_use_unit").val(res.data.operation_use_unit);
          $("#voltage_level").val(res.data.voltage_level);
          $("#main_unit").val(res.data.main_unit);
          $("#standing_time").val(res.data.standing_time);
          $("#tendering_unit").val(res.data.tendering_unit);
          $("#start_time").val(res.data.start_time);
          $("#winning_bid_unit").val(res.data.winning_bid_unit);
          $("#completion_time").val(res.data.completion_time);
          $("#builder_unit").val(res.data.builder_unit);
          $("#design_unit").val(res.data.design_unit);
          $("#construction_control_unit").val(res.data.construction_control_unit);
          $("#remark").val(res.data.remark);
          layui.form.render('select');
          layer.open({
            type: 1,
            title: '查看',
            area: ['100%', '100%'],
            content:$("#memberAdd"),
            end:function () {
              $("#memberAdd input ,#memberAdd select,#memberAdd textarea").attr("disabled",false);
              $("#btnBox").show();
              $("#memberAdd input ,#memberAdd select,#memberAdd textarea").val("");
            }
        });
      } else{
        layer.msg(res.msg);
      }
    }
  });
}

//编辑
function conEdit(id) {
  $.ajax({
    url:"./getindex",
    type:"POST",
    data:{id:id},
    dataType:"JSON",
    success:function (res) {
      if(res.code == 1){
        $("#addId").val(res.data.id);
        $("#directory_code").val(res.data.directory_code);
        $("#project_code").val(res.data.project_code);
        $("#revision_code").val(res.data.revision_code);
        $("#entry_name").val(res.data.entry_name);
        $("#construction_unit").val(res.data.construction_unit);
        $("#project_category").val(res.data.project_category);
        $("#operation_use_unit").val(res.data.operation_use_unit);
        $("#voltage_level").val(res.data.voltage_level);
        $("#main_unit").val(res.data.main_unit);
        $("#standing_time").val(res.data.standing_time);
        $("#tendering_unit").val(res.data.tendering_unit);
        $("#start_time").val(res.data.start_time);
        $("#winning_bid_unit").val(res.data.winning_bid_unit);
        $("#completion_time").val(res.data.completion_time);
        $("#builder_unit").val(res.data.builder_unit);
        $("#design_unit").val(res.data.design_unit);
        $("#construction_control_unit").val(res.data.construction_control_unit);
        $("#remark").val(res.data.remark);
        layui.form.render('select');
        layer.open({
          type: 1,
          title: '查看',
          area: ['100%', '100%'],
          closeBtn:0,
          content:$("#memberAdd"),
          end:function () {
            $("#memberAdd input ,#memberAdd select,#memberAdd textarea").val("");
          }
        });
      } else{
        layer.msg(res.msg);
      }
    }
  });
}

//删除
function conDel(id) {
  $.ajax({
    url:"./delCate",
    type:"POST",
    data:{id:id},
    dataType:"JSON",
    success:function (res) {
      if(res.code==1){
        layer.msg("删除成功");
        tableItem.ajax.url("/filemanagement/common/datatablespre/tableName/file_project_management.shtml").load()
      }else{
        layer.msg(res.msg);
      }
    }
  })
}

//继承设置
function setInherit() {
  layer.open({
    type:1,
    title:'继承设置',
    area:['800px','260px'],
    content:$("#inherit")
  })
}
//全选
$(".checkAll").click(function() {
  $('input[type="checkbox"]').prop("checked", this.checked);
});
$('.checkChild').click(function() {
  $(".checkAll").prop("checked", $('.checkChild').length == $(".checkChild:checked").length ? true : false);
});
