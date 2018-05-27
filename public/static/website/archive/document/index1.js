//下载的方法
function download(id,url) {
  var url1 = url;
  $.ajax({
    url: url,
    data:{id:id},
    type:"post",
    success: function (res) {
      if(res.code != 1){
        console.log(res);
        layer.msg(res.msg);
      }else {
        $("#form_container").empty();
        var str = "";
        str += ""
          + "<iframe name=downloadFrame"+ id +" style='display:none;'></iframe>"
          + "<form name=download"+ id +" action="+ url1 +" method='get' target=downloadFrame"+ id + ">"
          + "<span class='file_name' style='color: #000;'>"+str+"</span>"
          + "<input class='file_url' style='display: none;' name='id' value="+ id +">"
          + "<button type='submit' class=btn" + id +"></button>"
          + "</form>"
        $("#form_container").append(str);
        $("#form_container").find(".btn" + id).click();
      }
    }
  })
}

//下载调用
function downFile(id){
  download(id,"./download");
}

//预览
function showPdf(id,url) {
  $.ajax({
    url: url,
    type: "post",
    data: {id:id},
    success: function (res) {
      console.log(res);
      if(res.code === 1){
        var path = res.path;
        var houzhui = res.path.split(".");
        if(houzhui[houzhui.length-1]=="pdf"){
          window.open("/static/public/web/viewer.html?file=../../../" + path,"_blank");
        }else{
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
            ,success:function () {
              $('.layui-layer-shade').empty();
            }
          });
        }

      }else {
        layer.msg(res.msg);
      }
    }
  })
}

//预览文件
function previewList(id){
  showPdf(id,'./preview');

}

//点击添加节点
function addNodetree() {
  var pid = selfid ? selfid : 0;
  layer.prompt({title: '请输入节点名称',}, function (value, index, elem) {
    $.ajax({
      url: "../documenttype/addOrEdit",
      type: "post",
      data: {pid: pid, name: value},
      success: function (res) {
        if (res.code === 1) {
          if (sNodes) {
            zTreeObj.addNodes(sNodes, {"id":res.data,"pid":pid,"name":value});
          } else {
            zTreeObj.addNodes(null, {"id":res.data,"pid":pid,"name":value});
          }
        }else if(res.code === -1){
          layer.msg("包含文件，不能添加下级");
        }else{
          layer.msg(res.msg);
        }
      }
    });
    layer.close(index);
  });
};

//编辑节点
function editNodetree() {
  if (!selfid) {
    layer.msg("请选择节点", {time: 1500, shade: 0.1});
    return;
  }
  console.log(sNodes);
  layer.prompt({
    title: '编辑',
    value: sNodes.name
  }, function (value, index, elem) {
    $.ajax({
      url: "../documenttype/addOrEdit",
      type: "post",
      data: {id: selfid, name: value},
      success: function (res) {
        if (res.code === 1) {
          sNodes.name = value;
          zTreeObj.updateNode(sNodes);//更新节点名称
          layer.msg("编辑成功")
        }
      }
    });
    layer.close(index);
  });
};

//删除节点
function delNodetree() {
  if (!selfid) {
    layer.msg("请选择节点");
    return;
  }
  if (!sNodes.children) {
    layer.confirm("是否确认删除？", function () {
      $.ajax({
        url: "../documenttype/del",
        type: "post",
        data: {id: selfid},
        success: function (res) {
          if (res.code === 1) {
            layer.msg("删除节点成功", {time: 1500, shade: 0.1});
            zTreeObj.removeNode(sNodes);
            selfid = "";
            sNodes = '';
          }else if(res.code === -1){
            layer.msg("包含文件不能删除");
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

//删除
function delFileFun(id,url) {
  $.ajax({
    type: "post",
    url: url,
    data: {id: id},
    success: function (res) {
      if(res.code == 1){
        layer.msg("删除成功！");
        var url = "/archive/common/datatablesPre?tableName=archive_document&id=" + selfid;
        tableItem.ajax.url(url).load();
      }else if(res.code==0){
        layer.msg(res.msg);
      }
    },
    error: function (data) {
      debugger;
    }
  });
}

//删除调用
function delFile(id){
  console.log(id);
  delFileFun(id,"./del")
}
//管理信息的tab 切换
layui.use(['element', "layer", 'upload'], function () {
  var $ = layui.jquery
    , element = layui.element //Tab的切换功能，切换事件监听等，需要依赖element模块
    , upload = layui.upload
    , layer = layui.layer;

  //多文件列表
  // var demoListView = $('#demoList')
  //   ,uploadListIns = upload.render({
  //   elem: '#testList'
  //   ,url: '/upload/'
  //   ,accept: 'file'
  //   ,multiple: true
  //   ,auto: false
  //   ,bindAction: '#testListAction'
  //   ,choose: function(obj){
  //     var files = this.files = obj.pushFile(); //将每次选择的文件追加到文件队列
  //     //读取本地文件
  //     obj.preview(function(index, file, result){
  //       var tr = $(['<tr id="upload-'+ index +'">'
  //         ,'<td>'+ file.name +'</td>'
  //         ,'<td>'+ (file.size/1014).toFixed(1) +'kb</td>'
  //         ,'<td>0%</td>'
  //         ,'<td>'
  //         ,'<button class="layui-btn layui-btn-mini demo-reload layui-hide">重传</button>'
  //         ,'<button class="layui-btn layui-btn-mini layui-btn-danger demo-delete">删除</button>'
  //         ,'</td>'
  //         ,'</tr>'].join(''));
  //
  //       //单个重传
  //       tr.find('.demo-reload').on('click', function(){
  //         obj.upload(index, file);
  //       });
  //
  //       //删除
  //       tr.find('.demo-delete').on('click', function(){
  //         delete files[index]; //删除对应的文件
  //         tr.remove();
  //         uploadListIns.config.elem.next()[0].value = ''; //清空 input file 值，以免删除后出现同名文件不可选
  //       });
  //
  //       demoListView.append(tr);
  //     });
  //   }
  //   ,done: function(res, index, upload){
  //     if(res.code == 0){ //上传成功
  //       var tr = demoListView.find('tr#upload-'+ index)
  //         ,tds = tr.children();
  //       tds.eq(2).html('<span style="color: #5FB878;">上传成功</span>');
  //       tds.eq(3).html(''); //清空操作
  //       return delete this.files[index]; //删除文件队列已经上传成功的文件
  //     }
  //     this.error(index, upload);
  //   }
  //   ,error: function(index, upload){
  //     var tr = demoListView.find('tr#upload-'+ index)
  //       ,tds = tr.children();
  //     tds.eq(2).html('<span style="color: #FF5722;">上传失败</span>');
  //     tds.eq(3).find('.demo-reload').removeClass('layui-hide'); //显示重传
  //   }
  // });

});

//组织结构表格
var tableItem = $('#tableItem').DataTable({
  pagingType: "full_numbers",
  processing: true,
  serverSide: true,
  // sScrollX: '600px',
  ajax: {
    "url": "/archive/common/datatablesPre?tableName=archive_document&id=-1"
  },
  dom: 'lf<"assModel layui-btn layui-btn-sm">' +
  '<"mybtn layui-btn layui-btn-sm">rtip',
  columns: [
    {
      name: "docname"
    },
    {
      name: "create_time"
    },
    {
      name: "filesize"
    },
    {
      name: "username"
    },
    {
      name: "remark"
    },
    {
      name: "id"
    }

  ],
  columnDefs: [
    {
      "orderable": false,
      targets:[3]
    },
    {
      targets:[2],
      "render":function (data,type,row) {
        var html = (data / 1024).toFixed(2);
          if(html > 1024 ){
             html = (data / 1024).toFixed(2) + "Mb";
          }else{
            html += "Kb";
          }
        return html;
      }
    },
    {
      targets: [0],
      render: function (data, type, row) {
        return  '<a id="'+row[5]+'" title="' + data + '"  onclick=\"previewList('+row[5]+')\">'+data+'<i class="fa fa-lg-2 fa-file-o"></a>';
      }
    },
    {
      "searchable": false,
      "orderable": false,
      "targets": [5],
      "render": function (data, type, row) {
        var html = "<a type='button' class='' style='margin-left: 5px;' onclick='downFile("+row[5]+")'><i title='下载' class='fa fa-download'></i></a>";
        html += "<a type='button'  class='' style='margin-left: 5px;' onclick='delFile("+row[5]+")'><i title='删除' class='fa fa-trash'></i></a>";
        return html;
      }
    },
    {
      "orderable": false,
      targets: [4],
      render: function (data, type, row) {
        if(data == null){
          data = "";
        }
        var html = '<span>'+data+'</span><input type="text" class="inputRemark" style="display: none">';
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
$(".mybtn").html("<div id='test3'>新增</div>");
$(".assModel").html("<div id='saveRemark'>保存</div>");

//初始化树节点
var selfid, //选中的节点id
  clickId, //选中的行id
  sNodes; //选中的节点

var setting = {
  view: {
    showLine: true, //设置 zTree 是否显示节点之间的连线。
    selectedMulti: false, //设置是否允许同时选中多个节点。
    // dblClickExpand: true //双击节点时，是否自动展开父节点的标识。
  },
  async: {
    enable: true,
    autoParam: ["pid"],
    type: "post",
    url: "../documenttype/getAll",
    dataType: "json",
    // dataFilter: ajaxDataFilter
  },
  data: {
    simpleData: {
      enable: true,
      idkey: "id",
      pIdKey: "pid",
      rootPId: 0
    }
  },
  callback: {
    onClick: this.nodeClick
  }
};
zTreeObj = $.fn.zTree.init($("#ztree"), setting, null);

//点击获取路径
function nodeClick(e, treeId, node) {
  selectData = "";
  clickId = '';
  sNodes = zTreeObj.getSelectedNodes()[0];//选中节点
  selfid = zTreeObj.getSelectedNodes()[0].id;//当前id
  nodeName = zTreeObj.getSelectedNodes()[0].name;//当前name
  nodePid = zTreeObj.getSelectedNodes()[0].pid;//当前pid
  console.log(selfid + '---id');
  console.log(nodeName + '---name');
  console.log(nodePid + '---pid');
  var path = sNodes.name; //选中节点的名字
  node = sNodes.getParentNode();//获取父节点
  //判断是否还有父节点
  if (node) {
    //判断是否还有父节点
    while (node) {
      path = node.name + "-" + path;
      node = node.getParentNode();
    }
  } else {
    $(".layout-panel-center .panel-title").text(sNodes.name);
  }
  groupid = sNodes.pId //父节点的id
  var url = "/archive/common/datatablesPre?tableName=archive_document&id="+selfid;
  tableItem.ajax.url(url).load();
  $("#tableContent .dataTables_wrapper").css('display','block');
  $("#tableContent .tbcontainer").css('display','block');
  $(".layout-panel-center .panel-title").text("当前路径:" + path)
  clickTreeId = selfid;
}

//搜索树
$('#keywords').bind('input propertychange', function() {
  var key = $("#keywords").val();
  var nodes = zTreeObj.getNodesByParam("name", key);
  $.each(nodes, function (i, item) {
    zTreeObj.expandNode(item.getParentNode(), true, false, true);
  });
});

//获取点击行
$("#tableItem").delegate("tbody tr","click",function (e) {
  if($(e.target).hasClass("dataTables_empty")){
    return;
  }
  $(this).addClass("select-color").siblings().removeClass("select-color");
  selectData = tableItem.row(".select-color").data();//获取选中行数据
  console.log(selectData);
  clickId = selectData[5];
  console.log(clickId);
});

//绑定表格点击事件
$("#tableItem").on("click","tbody tr td:nth-child(5)",function () {
  var val = $(this).find('span').hide().text();
  $(this).find("input").show().val(val).focus();
});

//失焦事件
$("#tableItem").on("blur",".inputRemark",function () {
  var val = $(this).hide().val();
  console.log(val);
  $(this).siblings().text(val).show();
});

//新增按钮点击事件
$(".mybtn").on("click",function () {
  if(!selfid){
    layer.msg("请先选择节点！");
    return;
  }
  if(sNodes.children){
    layer.msg('请选择最子节点');
    return;
  }
  layer.open({
    type:1,
    title:"增加附件",
    area:["800px","90%"],
    content:$("#fileListBox")
  })
});

//保存按钮点击事件
$(".assModel").on("click",function () {

  if(!selfid){
    layer.msg("请先选择节点！");
    return;
  }
  if(sNodes.children){
    layer.msg('请选择最子节点');
    return;
  }
  var remarkArr = [];
  $("#tableItem tbody tr").each(function (i,item) {
    var obj = {};
    obj.id= $(item).find("td:first-child a").attr("id");
    obj.remark = $(item).find("td:nth-child(5) span").text();
    remarkArr.push(obj);
  });
  $.ajax({
    url:"./editDocmentRemark",
    type:"POST",
    data:{remarkArr : remarkArr},
    dataType:"JSON",
    success:function (res) {
      if(res.code == 1){
        layer.msg('编辑成功');
        var url = "/archive/common/datatablesPre?tableName=archive_document&id="+selfid;
        tableItem.ajax.url(url).load();
      }else{
        layer.msg(res.msg);
        var url = "/archive/common/datatablesPre?tableName=archive_document&id="+selfid;
        tableItem.ajax.url(url).load();
      }
    }
  });
});

//文件上传
uploader = WebUploader.create({
  auto: false,// 选完文件后，是否自动上传。
  swf: '/static/admin/webupload/Uploader.swf',// swf文件路径
  server: "./upload",// 文件接收服务端。
  chunked: false, //分片
  comress:null, //图片不压缩
  fileSizeLimit: 1000 *1024 *1024,
  // fileNumLimit: 6,
  duplicate :false,// 重复上传图片，true为可重复false为不可重复
  pick: {
    multiple: true,
    id: "#testList"
  },
});

// 当有文件添加进来的时候
uploader.on( 'fileQueued', function( file ) {
  console.log(file);
 var tr = $(['<tr id="'+ file.id +'">'
    ,'<td>'+ file.name +'</td>'
    ,'<td>'+ (file.size/1024).toFixed(1) +'kb</td>'
    ,'<td>0%</td>'
    ,'<td>'
    ,'<button class="layui-btn layui-btn-mini layui-btn-danger demo-delete">删除</button>'
    ,'</td>'
    ,'</tr>'].join(''));
  //删除
  tr.find('.demo-delete').on('click', function(){
    var $ele = $(this);
    var id = $ele.parents("tr").attr("id");
    console.log($ele,id)
    var file = uploader.getFile(id);
    uploader.removeFile(file,true);
    tr.remove();
  });
  $("#demoList").append(tr);
  $('#allsize').text(allsize()+"kb");
});

uploader.on("uploadStart",function () {
  uploader.options.formData.selfid = selfid;
});

// 文件上传过程中创建进度条实时显示。
uploader.on('uploadProgress', function (file, percentage) {
  var $li = $('#' + file.id),
    $percent = $li.find('td:nth-child(3)');
  $percent.text( percentage * 100 + '%');

});
// 文件上传成功
uploader.on( 'uploadSuccess', function( file,res ) {
  // console.log(file)
  // console.log(data)
  if(res.code === 1){
    $("#status").text(processAll());
    if($("#status").text() == "100%"){
      layer.closeAll();
      var url = "/archive/common/datatablesPre?tableName=archive_document&id="+selfid;
      tableItem.ajax.url(url).load();
    }
  }else {
    layer.msg("抱歉，您没有此权限");
  }

});
uploader.on( 'all', function( type ) {
  if ( type === 'startUpload' ) {
    state = 'uploading';
  } else if ( type === 'stopUpload' ) {
    state = 'paused';
  } else if ( type === 'uploadFinished' ) {
    state = 'done';
  }
  if ( state === 'uploading' ) {
    return;
  } else {
    $("#testListAction").text('开始上传');
  }
});

var  state = 'pending';
//开始上传
$("#testListAction").on( 'click', function() {
  if ( state === 'uploading' ) {
    return;
  } else {
    uploader.upload();
  }
});

//获取总进度
function processAll() {
  var num = 0;
  var allnum = $("#demoList tr").length;
  $("#demoList tr td:nth-child(3)").each(function (i,item) {
      if($(item).text() == "100%"){
        num++;
      }
  });
  return parseInt(num / allnum * 100) + "%";
}
//获取总大小
function allsize() {
  var allsize = 0;
  $("#demoList tr td:nth-child(2)").each(function (i,item) {
    allsize += parseFloat($(item).text());
  });
  return allsize;
}

