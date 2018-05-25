//下载的方法
function download(id,url) {
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
          + "<form name=download"+ id +" action="+ url +" method='get' target=downloadFrame"+ id + ">"
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
  record(id);
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

//下载的历史记录
function record(id) {
  $.ajax({
    type: "post",
    url: "./downloadrecord",
    data: {id: id},
    success: function (res) {
      // if(res.code == 1){
      console.log(res);
      $("#downInfo").html("");
      var strTr = '';
      for (var i = 0; i < res.length; i++) {
        strTr += '<tr>\n' +
          '       <td>' + res[i].create_time + '</td>\n' +
          '       <td>' + res[i].user + '</td>\n' +
          '    </tr>'
      }
      $("#downInfo").append(strTr);
      // }
      if(res.code == 0){
        layer.msg('历史记录' + res.msg);
      }
    }
  })
}