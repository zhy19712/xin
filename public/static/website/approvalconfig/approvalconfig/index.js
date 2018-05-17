//
layui.use(['element',"layer",'form'], function(){
  var $ = layui.jquery
    ,element = layui.element; //Tab的切换功能，切换事件监听等，需要依赖element模块

  var form = layui.form
    ,layer = layui.layer
    ,layedit = layui.layedit;

  //监听提交
  form.on('submit(demo1)', function(data){
    $.ajax({
      type: "post",
      url:"./1",
      data:data.field,
      success: function (res) {
        if(res.code == 1) {
          parent.layer.msg('保存成功！');
          layer.closeAll();
        }else{
          layer.msg(res.msg);
        }
      },
      error: function (data) {
        debugger;
      }
    });
    return false;
  });
  form.on('submit(demo2)', function(data){
    $.ajax({
      type: "post",
      url:"./1",
      data:data.field,
      success: function (res) {
        if(res.code == 1) {

          parent.layer.msg('保存成功！');
          layer.closeAll();
        }else{
          layer.msg(res.msg);
        }
      },
      error: function (data) {
        debugger;
      }
    });
    return false;
  });
});

var level1Id = ''  //选中的工程划分id
  , level2Id = '' ; //选中的单元工Id
/************************************************工程划分树********************************************/
var setting = {
  view: {
    showLine: true, //设置 zTree 是否显示节点之间的连线。
    selectedMulti: false //设置是否允许同时选中多个节点。
  },
  async: {
    enable: true,
    autoParam: ["pId"],
    type: "post",
    url: "../../quality/division/index",
    dataType: "json"
  },
  data: {
    simpleData: {
      enable: true,
      idkey: "id",
      pIdKey: "pId",
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
  level2Id = '';
  $('#level3').hide();
  $('#allFrom ul li').removeClass('selectForm');
 var sNodes = zTreeObj.getSelectedNodes()[0];//选中节点
  level1Id = sNodes.id;
  initData(level1Id);//调用单元工
}
/*******************************************************************************************************/

/************************************************单元工树********************************************/

//名字拼接过滤方法
function ajaxDataFilter(treeId, parentNode, responseData) {
  if (responseData) {
    for(var i =0; i < responseData.length; i++) {
      responseData[i].name = responseData[i].el_start + responseData[i].el_cease + responseData[i].pile_number + responseData[i].site;
      eTypeId = responseData[i].en_type;
    }
  }
  return responseData;
}
//
function initData(selfid){
  var settingUnit = {
    view: {
      showLine: true, //设置 zTree 是否显示节点之间的连线。
      selectedMulti: false //设置是否允许同时选中多个节点。
    },
    async: {
      enable: true,
      autoParam: ["pid"],
      type: "post",
      url: "../../quality/element/getDivisionUnitTree?id="+selfid,
      dataType: "json",
      dataFilter: ajaxDataFilter
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
      onClick: this.nodeClickUnit
    }
  };
  zTreeObjUnit = $.fn.zTree.init($("#ztreeUnit"), settingUnit, null);
}

//点击获取路径
function nodeClickUnit(e, treeId, node) {
  $('#search').val('');
  $("#search").trigger("input propertychange");

  // ev = document.createEvent("HTMLEvents");
  // ev.initEvent("input", false, true);
  // document.getElementById('search').dispatchEvent(ev);

  $('#allFrom ul li').removeClass('selectForm');
  var sNodes = zTreeObjUnit.getSelectedNodes()[0];//选中节点
  level2Id = sNodes.id;
  $('#level3').show();
}
/*******************************************************************************************************/




//创建li
function createLi(data) {
  var html = '';
  data.forEach(function (item) {
    html += '<li id="'+item.id+'">'+item.code+item.name+'</li>';
  });
  $("#allFrom ul").append(html);
}

//查看表单
function lookform(){

  if( $(".selectForm").length == 0 ){
    layer.msg('请先选择模板表单');
    return ;
  }else{
    layer.open({
      type: 2,
      title: '查看',
      shadeClose: true,
      area: ['980px', '90%'],
      content: 'edit?id='+ $(".selectForm").attr('id') + '&currentStep=0&isView=True'
    });
  }
}


//添加状态
function addStatus(){
  if( $(".selectForm").length == 0 ) {
    layer.msg('请先选择模板表单');
    return;
  }else{
    layer.open({
      type:1,
      area:['600px','260px'],
      content:$('#addStatus')
    });
  }
}

//添加步骤
function addStep(){
  if( $(".selectForm").length == 0 ) {
    layer.msg('请先选择模板表单');
    return;
  }else{
    layer.open({
      type:1,
      title:'步骤',
      area:['1000px','700px'],
      content:$('#addStep')
    });
  }
}

//拉取全部模板
$.ajax({
  url : './getAllTemplate',
  type : 'GET',
  dataType : 'JSON',
  success : function (res) {
    if (res.code == 1){
      createLi(res.data);
    }else{
      layer.msg(res.msg);
    }
  }
});

//搜索
$('#search').bind('input propertychange',function () {
  var text = $(this).val();
  var $form = $("#allFrom ul li");
  if( text == '' ){
    $form.show();
    $form.removeClass('selectForm');
    return ;
  }
  $.each($form, function (i, item) {
    if ($.trim($(item).text()).indexOf(text) !==-1 ) {
      $(item).show();
      if($.trim($(item).text()) === text){
        $(item).addClass('selectForm').siblings().removeClass('selectForm');
      }
    }else{
      $(item).hide();
    }
  });
});

/**************************************添加人员***************************************************/
//点击选中
$('#allFrom ul').on('click','li',function () {
  $(this).addClass('selectForm').siblings().removeClass('selectForm');
});

$("#tgd a").click(function () {
  $(this).siblings('a').removeClass('select');
  $(this).addClass('select');
});
// 常用联系人
function frequentlyUsedDivShow() {
  $("#frequentlyUsedDiv").show();
  $("#selectDiv").hide();
}
// 人员选择
function selectDivShow() {
  $("#frequentlyUsedDiv").hide();
  $("#selectDiv").show();
}

//字符解码
function ajaxDataFilter2(treeId, parentNode, responseData) {

  if (responseData) {
    for(var i =0; i < responseData.length; i++) {
      responseData[i] = JSON.parse(responseData[i]);
      responseData[i].name = decodeURIComponent(responseData[i].name);
      ztreeIcon(responseData[i]);
    }
  }
  return responseData;
}
//处理结构树图标
function ztreeIcon(data) {
  if (!data.level){
    data.icon =  "/static/public/ztree/css/ztreeadmin/img/people.png";
    return ;
  }
  switch (Number(data.level)) {
    case 1 :
      data.icon =  "/static/public/ztree/css/ztreeadmin/img/top.png";
      break;
    case 2 :
      data.icon =  "/static/public/ztree/css/ztreeadmin/img/jigou.png";
      break;
    case 3 :
      data.icon =  "/static/public/ztree/css/ztreeadmin/img/bumen.png";
      break;
    default:
      data.icon =  "/static/public/ztree/css/ztreeadmin/img/bumen.png";
      break;
  }
}
//初始化组织结构树
var settingOrganize = {
  view: {
    showLine: true, //设置 zTree 是否显示节点之间的连线。
    selectedMulti: false, //设置是否允许同时选中多个节点。
    // dblClickExpand: true //双击节点时，是否自动展开父节点的标识。
  },
  async: {
    enable : true,
    autoParam: ["pid"],
    type : "post",
    url : "../../admin/rolemanagement/getindex",
    dataType :"json",
    dataFilter: ajaxDataFilter2
  },
  data:{
    keep: {
      leaf : true,
      parent : true
    },
    simpleData : {
      enable:true,
      idkey: "id",
      pIdKey: "pid",
      rootPId:0
    }
  },
  callback: {
    onClick: this.onClick,
  }
};
treeObj = $.fn.zTree.init($("#treeDemo"), settingOrganize, null);

// 初始化常用联系人树
function initFrequentlyUsedTree() {
  var setting = {
    view: {
      selectedMulti: false
    },
    data: {
      simpleData: {
        enable: true
      }
    },
    callback: {
      onClick: onClick
    }
  };
  $.ajax({
    type: "Get",
    url: "../../approve/Approve/FrequentlyUsedApprover?dataType=app\\quality\\model\\QualityFormInfoModel",
    success: function (res) {
      var initArr =[];
      for(var i=0;i<res.length;i++){
        var initObj = {
          id:'',
          name:''
        };
        initObj.id = res[i].id+10000;
        initObj.name = res[i].nickname;
        initArr.push(initObj)
      }
      $.fn.zTree.init($("#treeFrequentlyUsed"), setting, initArr);
    }
  });
};
initFrequentlyUsedTree()

//搜索树
$('#keywords').bind('input propertychange', function() {
  var key = $("#keywords").val();
  var nodes = treeObj.getNodesByParam("name", key);
  $.each(nodes, function (i, item) {
    treeObj.expandNode(item.getParentNode(), true, false, true);
  });
});

//左侧组织机构树双击事件处理
function onClick(event, treeId, treeNode) {
  var element = $("#selectedUserDiv").find("input[value='" + (treeNode.id) + "']");
  if (element.length > 0)
    return;
  var html = "";
  if (treeNode.id < 10000) //选择的是单位信息
    return;
  else  //选择的是人员信息
    html = "<p2 id='p" + treeNode.id + "'>" + treeNode.name + "&nbsp;<a id='a" + treeNode.id + "'><i class='fa fa-times'></i></a><input type='hidden' value='" + treeNode.id + "' data-type='" + treeNode.componentId + "'></p2>"
  $("#selectedUserDiv").append(html);
  $("#a" + treeNode.id).click(function () {
    $("#p" + treeNode.id).remove();
  })
}
//搜索用户
$('#username_key').bind('input propertychange', function() {
  var keyword = $("#username_key").val();
  var users = $("#selectedUserDiv p2");
  if(keyword.trim()===""){
    users.css("background-color", "")
    return;
  }
  $.each(users, function (i, item) {
    $(item).css("background-color", "");
    if ($.trim($(item).text()).indexOf(keyword) !==-1 ) {
      $(item).css("background-color", "#ed5565");
    }
  })
});
/***************************************************************************************************/