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
      url:"./editAtlasCate",
      data:data.field,
      success: function (res) {
        console.log(selfid)
        if(res.code == 1) {
          var url = "/archive/common/datatablespre/tableName/archive_atlas_cate/selfid/"+selfid+".shtml";
          tableItem.ajax.url(url).load();
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

//创建li
function createLi(data) {
  var html = '';
  data.forEach(function (item,i) {
    html += '<li id="'+item.id+'">'+item.code+item.name+'</li>';
  })
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
};

//
function addStatus(){
  layer.open({
    type:1,
    area:['300px','300px'],

  })
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

//点击选中
$('#allFrom ul').on('click','li',function () {
  $(this).addClass('selectForm').siblings().removeClass('selectForm');
});

