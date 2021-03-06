layui.use(['element','layer'],function () {
  var element = layui.element
    ,layer = layui.layer;
  element.on('tab(test1)', function(data){
  })
});
var cpr_id = '';
var id = '';
var currentStep = '';
//代办事件
var tableItem = $('#tableItem').DataTable( {
  pagingType: "full_numbers",
  processing: true,
  serverSide: true,
  "order": [[ 1, "desc" ]],
  // scrollY: 600,
  ajax: {
      // "url":"/approve/common/datatablesPre?tableName=approve&data_id=&data_type=3"
    "url":"/admin/common/datatablesPre?tableName=admin_message_reminding&status=1"
  },
  dom: 'rtlip',
  columns:[
    {
      name : "task_name"
    },
    {
      name : "create_time"
    },
    {
      name : "sender"
    },
    {
      name : "task_category" //任务类别
    },
    {
      name : "type" //1为收发文 2为 单元管控
    },
    {
      name: "id"
    },
    {
      name : "uint_id" //关联主键id
    }
  ],
  columnDefs: [
    {
      "orderable": false,
      "targets": [4],
      "render" :  function(data,type,row) {
        var type = JSON.stringify(data); // 不转 汉字 报错  type
        if(data == 1){
          //收发文
          var html = "<a type='button'  class='con_Style' onclick='conedit("+type+","+row[6]+")'>处理</a>" ;
        }else if(data == 2){
          //单元
          var html = "<a type='button'  class='con_Style' onclick='conedit("+type+","+row[6]+","+row[7].cpr_id+","+row[7].CurrentStep+")'>处理</a>" ;
        }
        else if(data == 3){
            var html = "<a type='button'  class='con_Style' onclick='backedit("+type+","+row[6]+","+row[7].cpr_id+","+row[7].CurrentStep+")'>查看</a>" ;
        }
        return html;
      }
    },
    {
      "targets": [1],
      "render" : function (data , type , row ) {
        var date = new Date(data*1000);
        var Y = date.getFullYear() + '-';
        var M = (date.getMonth()+1 < 10 ? '0'+(date.getMonth()+1) : date.getMonth()+1) + '-';
        var D = (date.getDate() < 10 ? '0' + (date.getDate()) : date.getDate()) + ' ';
        var h = (date.getHours() < 10 ? '0' + (date.getHours()):date.getHours())+ ':' ;
        var m = (date.getMinutes() < 10 ? '0' + (date.getMinutes()):date.getMinutes())+ ':' ;
        var s = (date.getSeconds() < 10 ? '0' + (date.getSeconds()):date.getSeconds());
        return Y + M + D + h + m + s;
      }
    },
    {
      "orderable": false,
      "targets": [5,6],
      visible:false
    },
    {
      "orderable": false,
      "targets": [0,2,3,4],
      orderable:false
    },
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
    $('#tableItem_paginate').insertBefore(".mark");
    $('#tableItem_info').insertBefore(".mark");
    $('.dataTables_wrapper,.tbcontainer').css("display","block");
  }
});

//已办事件
var tableItemDone = $('#tableItemDone').DataTable( {
  pagingType: "full_numbers",
  processing: true,
  serverSide: true,
  "order": [[ 1, "desc" ]],
  // scrollY: 600,
  ajax: {
    "url":"/admin/common/datatablesPre?tableName=admin_message_reminding&status=2"
  },
  dom: 'rtlip',
  columns:[
    {
      name : "task_name"
    },
    {
      name : "create_time"
    },
    {
      name : "sender"
    },
    {
      name : "task_category" //任务类别
    },
    {
      name : "type" //1为收发文 2为 单元管控
    },
    {
      name: "id"
    },
    {
      name : "uint_id" //关联主键id
    }
  ],
  columnDefs: [
    {
      "orderable": false,
      "targets": [4],
      "render" :  function(data,type,row) {
        var type = JSON.stringify(data); // 不转 汉字 报错  type
        if(data == 1){
          //收发文
          var html = "<a type='button'  class='con_Style' onclick='conshow("+type+","+row[6]+")'>查看</a>" ;
        }else if(data == 2){
          //单元
          var html = "<a type='button'  class='con_Style' onclick='conshow("+type+","+row[6]+","+row[7].cpr_id+","+row[7].CurrentStep+")'>查看</a>" ;
        }
        return html;
      }
    },
    {
      "targets": [1],
      "render" : function (data , type , row ) {
        var date = new Date(data*1000);
        var Y = date.getFullYear() + '-';
        var M = (date.getMonth()+1 < 10 ? '0'+(date.getMonth()+1) : date.getMonth()+1) + '-';
        var D = (date.getDate() < 10 ? '0' + (date.getDate()) : date.getDate()) + ' ';
        var h = (date.getHours() < 10 ? '0' + (date.getHours()):date.getHours())+ ':' ;
        var m = (date.getMinutes() < 10 ? '0' + (date.getMinutes()):date.getMinutes())+ ':' ;
        var s = (date.getSeconds() < 10 ? '0' + (date.getSeconds()):date.getSeconds());
        return Y + M + D + h + m + s;
      }
    },
    {
      "orderable": false,
      "targets": [5,6],
      visible:false
    },
    {
      "orderable": false,
      "targets": [0,2,3,4],
      orderable:false
    },
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
    $('#tableItemDone_length').insertBefore(".markDone");
    $('#tableItemDone_info').insertBefore(".markDone");
    $('#tableItemDone_paginate').insertBefore(".markDone");
    $('.dataTables_wrapper,.tbcontainerDone').css("display","block");
  }
});

//点击处理
function conedit(){
console.log(arguments); //0 为type
  if( arguments[0] == 2){
    // 1 为cpr_id 2 为 unit_id 3 为currentStep
    layer.open({
      type: 2,
      title: '在线填报',
      shadeClose: true,
      area: ['980px', '90%'],
      content: '/quality/Qualityform/edit?cpr_id='+arguments[2]+'&id='+arguments[1]+'&currentStep='+arguments[3]+'&isView=true',
      success:function () {
        $('.layui-layer-shade').empty();
      }
    });
  }else if(arguments[0] == 1){
    //1 为major_key
    //拉收文数据
    $.ajax({
      url:'/archive/send/preview',
      data:{
        major_key: arguments[1],
        see_type: 1
      },
      type:"POST",
      dataType:"json",
      success:function (res) {
        console.log(res);
        var attachment = res.attachment;
        $("#major_key").val(res.id);
        $("#file_name").val(res.file_name);
        $("#date").val(res.date);
        $("#income_name").val(res.send_name);
        $("#unit_name").val(res.unit_name);
        $("#remark").val(res.remark);
        $("#relevance_id").val(res.attchment_id);
        var rowData = '';
        for (var i=0;i<attachment.length;i++){
          rowData +='<tr><td>'+attachment[i].name+'</td><td >';
          rowData +='<a   class="layui-btn layui-btn-xs" onclick="fileDownload(this)" uid='+ attachment[i].id +' name='+ attachment[i].name +'>下载</a>';
          rowData +='<a  class="layui-btn layui-btn-primary layui-btn-xs" onclick="attachmentPreview(this)" uid='+ attachment[i].id +' name='+ attachment[i].name +'>查看</a></td></tr>';
        }
        $("#add_table_files tbody").empty().append(rowData);
      }
    });
    layer.open({
      type:1,
      title:"收文处理",
      shadeClose:true,
      area:['800px','90%'],
      content:$("#file_modal"),
      success:function () {
        $('.layui-layer-shade').empty();
      }
    })
  }

}

//点击查看
function conshow(){
  console.log(arguments); //0 为type
  if( arguments[0] == 2){
    layer.open({
      type: 2,
      title: '在线填报',
      shadeClose: true,
      area: ['980px', '90%'],
      content: '/quality/Qualityform/edit?cpr_id='+arguments[2]+'&id='+arguments[1]+'&currentStep='+arguments[3]+'&isView=True',
      success:function () {
        $('.layui-layer-shade').empty();
      }
    });
  }else if( arguments[0] == 1){
    //拉收文数据
    $.ajax({
      url:'/archive/send/preview',
      data:{
        major_key: arguments[1],
        see_type: 1
      },
      type:"POST",
      dataType:"json",
      success:function (res) {
        console.log(res);
        var attachment = res.attachment;
        $("#file_name").val(res.file_name);
        $("#date").val(res.date);
        $("#income_name").val(res.send_name);
        $("#unit_name").val(res.unit_name);
        $("#remark").val(res.remark);
        $("#relevance_id").val(res.attchment_id);
        var rowData = '';
        for (var i=0;i<attachment.length;i++){
          rowData +='<tr><td>'+attachment[i].name+'</td><td >';
          rowData +='<a   class="layui-btn layui-btn-xs" onclick="fileDownload(this)" uid='+ attachment[i].id +' name='+ attachment[i].name +'>下载</a>';
          rowData +='<a  class="layui-btn layui-btn-primary layui-btn-xs" onclick="attachmentPreview(this)" uid='+ attachment[i].id +' name='+ attachment[i].name +'>查看</a></td></tr>';
        }
        $("#add_table_files tbody").empty().append(rowData);
      }
    });
    layer.open({
      type:1,
      title:"收文处理",
      shadeClose:true,
      area:['800px','90%'],
      content:$("#file_modal"),
      success:function () {
        $("#addSubmit").hide();
        $('.layui-layer-shade').empty();
      },
      end:function () {
        $("#addSubmit").show();
      }
    })
  }

}
//退回信息查看
function backedit() {
    console.log(arguments);
    $('input[name="id"]').val(arguments[1]);
    $('input[name="cpr_id"]').val(arguments[2]);
    $('input[name="currentStep"]').val(arguments[3]);
    $.ajax({
        type:'get',
        url:'/admin/dashboard/getrefundData?formId='+arguments[1],
        success:function (res){
            if (res.isCreater == '0'){
                $(".hideInfo").css('display','block');
                $(".showInfo").css('display','none');
            }else{
                $(".hideInfo").css('display','none');
                $(".showInfo").css('display','block');
            };
           if (res.code == '1'){
               var data = res.basedata;
               $("#taskName").val(data.taskName);
               $("#altitude").val(data.altitude);
               $("#pileNo").val(data.pileNo);
               $("#backremark").val(res.remark);
               $("#dwName").val(data.dwName);
           };

        }
    });
    layer.open({
        type: 1,
        area:['650px','550px'],
        content: $('#back_info'),
        success:function () {

        }
    });
    var tableItemChose = $('#tableItemChose').DataTable({
        pagingType: "full_numbers",
        processing: true,
        "scrollY": "220px",
        "scrollCollapse": "true",
        serverSide: true,
        ajax: {
            "url":"/approve/common/datatablesPre?tableName=approve&dataType=app\\quality\\model\\QualityFormInfoModel\n&dataId="+arguments[1]
        },
        dom: 't',
        columns: [
            {
                name: "nickname"
            },
            {
                name: "create_time"
            },
            {
                name: "result"
            },
            {
                name: "mark"
            }
        ],
        columnDefs: [
            {
                targets: [0]
            },
            {
                targets:[1],
                "render": function (data, type, row) {
                    if (data == null || data == undefined || data == '') return '';
                    var time = new Date(data*1000);
                    var y = time.getFullYear();
                    var M = time.getMonth() + 1;
                    M = M < 10 ? '0' + M : M;
                    var d = time.getDate();
                    d = d < 10 ? ('0' + d) : d;
                    var h = time.getHours() ;
                    h = h < 10 ? '0' + h : h;
                    var m = time.getMinutes();
                    m = m < 10 ? '0' + m : m;
                    var s = time.getSeconds();
                    s = s < 10 ? '0' + s : s;
                    return y + '-' + M + '-' + d +' '+ h + ':'+ m + ':' + s;
                }
            },
            {
                targets:[2]
            },
            {
                targets: [3]
            }
        ],
        "destroy": true,
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
            $('#tableItemChose_length').insertBefore(".mark");
            $('#tableItemChose_info').insertBefore(".mark");
            $('#tableItemChose_paginate').insertBefore(".mark");
        }
    });
}
//退回信息处理
function backOperation() {
    id = $('input[name="id"]').val();
    cpr_id = $('input[name="cpr_id"]').val();
    currentStep = $('input[name="currentStep"]').val();
    console.log(id+','+cpr_id+','+currentStep);
    layer.open({
        type: 2,
        title: '在线填报',
        shadeClose: true,
        area: ['980px', '90%'],
        content: '/quality/Qualityform/edit?cpr_id='+ cpr_id + '&id='+ id +'&currentStep=' + currentStep,
        // content: '/quality/Qualityform/edit?cpr_id=' + cpr_id + '&id=' + id + '&currentStep=0&isView=True',
        success: function (layero,index) {
            var body = layer.getChildFrame('body', index);
            body.contents().find(".date input").val('');

        }
    });
    layer.closeAll('page');
}
//在线填报-保存并审批按钮
function approve(id,app,step) {
  console.log(app);
  $.ajax({
    url: "/approve/Approve/CheckBeforeSubmitOrApprove",
    type: "post",
    data: {
      dataId:id,
      dataType:"app\\quality\\model\\QualityFormInfoModel",
      currentStep:step
    },
    success: function (res) {
      console.log(res);
      if(res == ""){
        layer.open({
          type: 2,
          title: '流程审批',
          shadeClose: true,
          area: ['980px', '90%'],
          content: '/approve/approve/Approve?dataId='+ id + '&dataType=app\\quality\\model\\QualityFormInfoModel',
          success: function(layero, index){
            var body = layer.getChildFrame('body', index);
            body.find("#conCode").val(app);
            body.find("#dataId").val(id);
            body.find("#dataType").val('app\\quality\\model\\QualityFormInfoModel');
          },
          end:function () {
            tableItem.ajax.url("/admin/common/datatablesPre?tableName=admin_message_reminding&status=1").load();
            tableItemDone.ajax.url("/admin/common/datatablesPre?tableName=admin_message_reminding&status=2").load();
            layer.closeAll();
          }
        });
      }else if(res != ''){
        layer.alert(res);
      }
    },
    error:function () {
      alert("获取数据完整性检测异常");
    }
  });
  console.log(id);
}

//收文附件下载
function fileDownload(that) {
  var id = $(that).attr('uid');
  var url = '/archive/send/fileDownload';
  $.ajax({
    url: url,
    data:{file_id:id},
    type:"post",
    success: function (res) {
      if(res.code != 1){
        layer.msg(res.msg)
      }else {
        $("#form_container").empty();
        var str = "";
        str += ""
          + "<iframe name=downloadFrame"+ id +" style='display:none;'></iframe>"
          + "<form name=download"+ id +" action="+ url +" method='get' target=downloadFrame"+ id + ">"
          + "<span class='file_name' style='color: #000;'>"+str+"</span>"
          + "<input class='file_url' style='display: none;' name='file_id' value="+ id +">"
          + "<button type='submit' class=btn" + id +"></button>"
          + "</form>"
        $("#form_container").append(str);
        $("#form_container").find(".btn" + id).click();
      }
    }
  })
}

//收文附件查看
function attachmentPreview(that) {
  var uid = $(that).attr('uid');
  var name = $(that).attr('name');
  $.ajax({
    url: '/archive/send/attachmentPreview',
    type: "post",
    data: {
      file_id:uid
    },
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
              "id": uid, //相册id
              "start": 0, //初始显示的图片序号，默认0
              "data": [   //相册包含的图片，数组格式
                {
                  "alt": name,
                  "pid": uid, //图片id
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

// 收文处理 签收 或拒收
function saveInter(status) {
  var fileId = $('#file_ids').val();
  $.ajax({
    url: "/approve/income/send",
    type: "post",
    data: {
      major_key:$("#major_key").val(),
      status:status
    },
    dataType: "json",
    success: function (res) {
      //手动调 刷新
      $.ajax({
        url:'./changeStatus',
        type:'POST',
        data:{
          uint_id:$("#major_key").val(),
          type:1
        },
        dataType:'json',
        success:function(data, textStatus){
            tableItem.ajax.url("/admin/common/datatablesPre?tableName=admin_message_reminding&status=1").load();
            tableItemDone.ajax.url("/admin/common/datatablesPre?tableName=admin_message_reminding&status=2").load();
          layer.closeAll();
          $("#major_key").val("");
        }
      });

    }
  });
}

//点击退回
function returnOnLine(formId) {
    layer.open({
        type: 2,
        title: "表单退回",
        area: ['980px', '70%'],
        content: '/approve/approve/approveRefund?formId=' + formId,
        end:function () {
            tableItem.ajax.url("/admin/common/datatablesPre?tableName=admin_message_reminding&status=1").load();
            tableItemDone.ajax.url("/admin/common/datatablesPre?tableName=admin_message_reminding&status=2").load();
            layer.closeAll();
        }
    });
}