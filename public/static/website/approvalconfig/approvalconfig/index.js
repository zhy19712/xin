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
  // $("#search").trigger("input propertychange");

  ev = document.createEvent("HTMLEvents");
  ev.initEvent("input", false, true);
  document.getElementById('search').dispatchEvent(ev);

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
    $.ajax({
      url:'./previewTemplate',
      type:'POST',
      data:{id:$(".selectForm").attr('id')},
      dataType:'JSON',
      success:function (res) {
        if ( res.code == 1) {
          var path = res.path;
          window.open("/static/public/web/viewer.html?file=../../../" + path,"_blank");
        }else{
          layer.msg(res.msg);
        }
      }
    })
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
      area:['800px','560px'],
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
// $('#search').bind('input propertychange',function () {
//   var text = $(this).val();
//   var $form = $("#allFrom ul li");
//   if( text == '' ){
//     $form.show();
//     $form.removeClass('selectForm');
//     return ;
//   }
//   $.each($form, function (i, item) {
//     if ($.trim($(item).text()).indexOf(text) !==-1 ) {
//       $(item).show();
//       if($.trim($(item).text()) === text){
//         $(item).addClass('selectForm').siblings().removeClass('selectForm');
//       }
//     }else{
//       $(item).hide();
//     }
//   });
// });

//点击选中
$('#allFrom ul').on('click','li',function () {
  $(this).addClass('selectForm').siblings().removeClass('selectForm');
});



document.getElementById('test').addEventListener('click',function () {
  alert(123)
});

ev = document.createEvent("HTMLEvents");
ev.initEvent("click", false, true);
document.getElementById('test').dispatchEvent(ev);

document.getElementById('search').addEventListener('input',function () {
  var text = $("#search").val();
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

