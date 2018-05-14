uObjSubIdSingle = new Number();      //已选非隐藏单个模型ID
uObjSubIDArr = [];      //已选非隐藏模型ID数组
hiddenArr = [];         //已选隐藏模型ID
isCtrlDown = false;
$(document).keydown(function (event) {
    var KeyCode = (navigator.appname=="Netscape")?event.which:window.event.keyCode;
    if(KeyCode==17){
        isCtrlDown = true;
    }
});
$(document).keyup(function (event) {
    var KeyCode = (navigator.appname=="Netscape")?event.which:window.event.keyCode;
    if(KeyCode==17){
        isCtrlDown = false;
    }
});

//标注图片滚动
window.tagSwiper = new Swiper ('#tag', {
    nextButton: '.tag-button-next',
    prevButton: '.tag-button-prev',
    slidesPerView : 4,
    spaceBetween: 30,
    centeredSlides: false,
    paginationClickable: true,
    mousewheelControl : true,
    keyboardControl : true
});

//快照图片滚动
window.snapshotSwiper = new Swiper ('#snapshot', {
    nextButton: '.snapshot-button-next',
    prevButton: '.snapshot-button-prev',
    slidesPerView : 4,
    spaceBetween: 30,
    centeredSlides: false,
    paginationClickable: true,
    mousewheelControl : true,
    keyboardControl : true
});

//初始化标注/快照滚动
$('#tt').tabs({
    onSelect: function(title,index){
        tagSwiper.update();
        snapshotSwiper.update();
    }
});

//添加快照
window.addSnapshot = function (base64RenData) {
    var img =
        '<div class="swiper-slide swiperSlide">' +
            '<img src="data:image/png;base64,'+ base64PicData +'" alt="">' +
            '<div class="mask">' +
                '<div class="right">' +
                    '<a href="javascript:;" onclick="imageSaveAs(base64PicData)" title="快照另存为">' +
                        '<i class="fa fa-save"></i>' +
                    '</a>' +
                    '<a href="javascript:;" title="删除快照">' +
                        '<i class="fa fa-close" id="del"></i>' +
                    '</a>' +
                '</div>'+
                '<div class="scenter">' +
                    '<a href="javascript:;" title="查看快照">' +
                     '<i class="fa fa-search"></i>' +
                    '</a>' +
                '</div>' +
                '<div class="center">2018-04-20</div>'+
            '</div>'+
        '</div>';
    return img;
}
//标注/快照操作蒙版
$('.swiper-wrapper').on('mouseover mouseleave','div',function (e) {
    if(e.type == 'mouseover'){
        $(this).find('div.mask').stop(true,true).fadeIn(500);
    }else {
        $(this).find('div.mask').stop(true,true).fadeOut(500);
    }
});
//标注/快照保存成图片
function imageSaveAs(imgURL) {
    var pagePop = window.open(imgURL, "", "width=1, height=1, top=5000, left=5000");
    for (; pagePop.document.readyState !== "complete";) {
        if (pagePop.document.readyState === "complete")
            break;
    }
    pagePop.document.execCommand("SaveAs");
    pagePop.close();
}

layui.use('element', function(){
    var element = layui.element;
});
//属性切换
$('#toogleAttr li').click(function () {
    var uid = $(this).attr('uid');
    $(this).addClass('active').siblings().removeClass('active');
    $('#'+ uid).show().siblings('div').hide();
});

//上传附件
$.upload({
    btnText:''
});

//评论@人员选择
$('#at').click(function () {
    window.open("./selectperson", "人员选择", "height=560, width=1000, top=200,left=400, toolbar=no, menubar=no, scrollbars=no, resizable=no,location=no,status=no");
});

//添加自定义属性
$('#addAttr').click(function () {
    var attrGroup = [];
    attrGroup.push('<div class="layui-input-inline attrGroup">');
    attrGroup.push('<input type="text" name="attrKey" required  lay-verify="required" placeholder="属性名" autocomplete="off" class="layui-input">');
    attrGroup.push('<input type="text" name="attrVal" required  lay-verify="required" placeholder="属性值" autocomplete="off" class="layui-input">');
    attrGroup.push('<div class="layui-form-mid layui-word-aux">');
    attrGroup.push('<i class="fa fa-check saveAttr" onclick="saveAttr(this)"></i>');
    attrGroup.push('<i class="fa fa-close closeAttr" onclick="closeAttr(this)"></i>');
    attrGroup.push('</div>');
    attrGroup.push('</div>');
    $('#attrGroup').append(attrGroup.join(' '));
});

//保存自定义属性
function saveAttr(that) {
    var picture_id = uObjSubIdSingle;
    var attrKey = $(that).parents('.attrGroup').find('input[name="attrKey"]').val();
    var attrVal = $(that).parents('.attrGroup').find('input[name="attrVal"]').val();
    $.ajax({
        url: "./addAttr",
        type: "post",
        data: {
            picture_id:picture_id,
            attrKey:attrKey,
            attrVal:attrVal
        },
        dataType: "json",
        success: function (res) {
            layer.msg(res.msg);
        }
    });
}

//回显自定义属性
function getAttr() {
    var picture_id = uObjSubIdSingle;
    $.ajax({
        url: "./getAttr",
        type: "post",
        data: {
            picture_id:picture_id,
        },
        dataType: "json",
        success: function (res) {
            $('#attrGroup').empty();
            var attrGroup = [];
            for(var i = 0;i<res.attr.length;i++){
                var attrKey = res.attr[i].attrKey;
                var attrVal = res.attr[i].attrVal;
                attrGroup.push('<div class="layui-input-inline attrGroup">');
                attrGroup.push('<input type="text" name="attrKey" value='+ attrKey +' required  lay-verify="required" placeholder="属性名" autocomplete="off" class="layui-input">');
                attrGroup.push('<input type="text" name="attrVal" value='+ attrVal +' required  lay-verify="required" placeholder="属性值" autocomplete="off" class="layui-input">');
                attrGroup.push('<div class="layui-form-mid layui-word-aux">');
                attrGroup.push('<i class="fa fa-check saveAttr" onclick="saveAttr(this)"></i>');
                attrGroup.push('<i class="fa fa-close closeAttr" onclick="closeAttr(this)"></i>');
                attrGroup.push('</div>');
                attrGroup.push('</div>');
            }
            $('#attrGroup').append(attrGroup.join(' '));
        }
    });
}

//删除自定义属性
function closeAttr(that) {
    $(that).parents('.layui-input-inline').remove();
}

//添加备注信息
$('#addRemark').click(function () {
    if(!uObjSubIdSingle){
        layer.msg('请选择模型');
        return false;
    }
    var picture_id = uObjSubIdSingle;
    var remarkVal = $('#remark').text();
    $.ajax({
        url: "./addRemark",
        type: "post",
        data: {
            picture_id:picture_id,
            remark:remarkVal
        },
        dataType: "json",
        success: function (res) {
            layer.msg(res.msg);
        }
    })
});

//回显备注信息
function getRemark() {
    var picture_id = uObjSubIdSingle;
    $.ajax({
        url: "./getRemark",
        type: "post",
        data: {
            picture_id:picture_id
        },
        dataType: "json",
        success: function (res) {
            $('#remark').text(res.remark);
        }
    })
}

// 添加锚点
$('#saveAnchor').click(function () {
    var picture_id = uObjSubIdSingle;
    var anchorName = $('#anchorName').html();
    var user_name = $('div[uid="createName"]').html();
    var create_time = $('div[uid="createDate"]').html();
    var componentName = $('#componentName').html();
    var remark = $('textarea[name="anchorRemark"]').text();
    console.log(remark);
    var fObjSelX = $('#fObjSelX').val();
    var fObjSelY = $('#fObjSelY').val();
    var fObjSelZ = $('#fObjSelZ').val();
    $.ajax({
       url: "./anchorPoint",
       type: "post",
       data: {
           picture_id:picture_id,
           anchorName:anchorName,
           user_name:user_name,
           create_time:create_time,
           componentName:componentName,
           remark:remark,
           fObjSelX:fObjSelX,
           fObjSelY:fObjSelY,
           fObjSelZ:fObjSelZ
       },
       dataType: "json",
       success: function (res) {
           layer.msg(res.msg);
       }
    });
});

//删除锚点
$('#delAnchor').click(function () {
    var anchorName = $('#anchorName').html();
    var anchor_point_id = $(this).attr('uid');
    delAnchor(anchorName,anchor_point_id);
});

//点击锚点
function getAnchorPoint(anchorName) {
    $.ajax({
        url: "./getAnchorPoint",
        type: "post",
        data: {
            anchorName:anchorName
        },
        dataType: "json",
        success: function (res) {
            $('#anchorName').html(res[0].anchor_name);
            $('#componentName').html(res[0].component_name);
            $('textarea[name="anchorRemark"]').text(res[0].remark);
            $('#delAnchor').attr('uid',res[0].anchor_point_id);
            $('#fObjSelX').val(res[0].coordinate_x);
            $('#fObjSelY').val(res[0].coordinate_y);
            $('#fObjSelZ').val(res[0].coordinate_z);
            console.log(res.img_arr);
            var anchor_point_id = res[0].anchor_point_id;
            for(var i = 0;i<res.img_arr.length;i++){
                var attachment_id = res.img_arr[i].attachment_id;
                uploadImage(res.img_arr[i].filepath,anchor_point_id,attachment_id);
            }

            for(var j = 0;j<res.file_arr.length;j++){
                var attachment_id = res.file_arr[j].attachment_id;
                uploadFile(res.file_arr[j].filepath,anchor_point_id,attachment_id);
            }
        }
    })
}

//返回
$('#backAnchor,#back').click(function(){
    $('#defaultAttr').show();
    $('#anchorLayer').hide();
});

/**
 * easyui面板显隐
 */

//管理信息
function easyUiPanelToggle() {
    var number = $("#easyuiLayout").layout("panel", "east")[0].clientWidth;
    if(number<=0){
        $('#easyuiLayout').layout('expand','east');
    }
}

//协同信息
function easyUiPanelToggleSouth() {
    var number = $("#centent").layout("panel", "south")[0].clientWidth;
    if(number<=0){
        $('#centent').layout('expand','south');
    }
}

//新增图片
function addImageFun() {
    addImage = WebUploader.create({
        auto: true,
        swf:  '/static/public/webupload/Uploader.swf',
        server: './uploadAnchorPoint',
        pick: {
            multiple: false,
            id: '#addImage',
            innerHTML: '新增图片'
        },
        formData:{
            anchor_point_id:''
        },
        accept: {
            title: 'Images',
            extensions: 'gif,jpg,jpeg,bmp,png',
            mimeTypes: 'image/jpg,image/jpeg,image/png'
        },
        resize: false
    });
    addImage.on( 'uploadSuccess', function( file ,res) {
        for(key in res.data){
            if(res.data.hasOwnProperty(key)){
                var imgPath = res.data[key];
                uploadImage(imgPath);
            }
        }
    });
    addImage.on( 'uploadError', function( file ,code) {
        console.log(code);
    });
    addImage.on("uploadStart",function () {
        var anchor_point_id = $('#delAnchor').attr('uid');
        addImage.options.formData.anchor_point_id = anchor_point_id;
    });
}

//新增文档
function addFileFun() {
    var addFile = WebUploader.create({
        auto: true,
        swf:  '/static/public/webupload/Uploader.swf',
        server: './uploadAnchorPoint',
        pick: {
            multiple: false,
            id: '#addFile',
            innerHTML: '新增文档'
        },
        formData:{
            anchor_point_id:''
        },
        accept: {
            title: 'excel',
            extensions: 'xls,xlsx',
            mimeTypes: '.xls,.xlsx'
        },
        resize: false
    });

    addFile.on( 'uploadSuccess', function( file ,res) {
        uploadFile(file.name);
    });
    addFile.on( 'uploadError', function( file ,code) {
        console.log(code);
    });
    addFile.on("uploadStart",function () {
        var anchor_point_id = $('#delAnchor').attr('uid');
        addFile.options.formData.anchor_point_id = anchor_point_id;
    });
}

//显示图片
function uploadImage(imgPath,anchor_point_id,attachment_id) {
    console.log(imgPath);
    var img = '<div class="img-item imgItem" >' +
        '<img src='+ imgPath +' alt="">' +
        '<a href="javascript:;">' +
        '<i class="fa fa-close" onclick="delAttachmentImg(this)"  pointId='+ anchor_point_id +' attachmentId = '+ attachment_id +'></i>' +
        '</a>' +
        '<span></span>'+
        '</div>';
    $('#imgList').append(img);
}

//显示文档
function uploadFile(fileName,anchor_point_id,attachment_id) {
    var file = '<div class="file-item">' +
        '<div class="file-list">' +
        '<p>'+ fileName +'</p>' +
        '<a href="javascript:;">' +
        '<i class="fa fa-download"  onclick="relationDownload(this)" attachmentId = '+ attachment_id +'></i>' +
        '</a>' +
        '<a href="javascript:;">' +
        '<i class="fa fa-close" onclick="delAttachmentImg(this)"  pointId='+ anchor_point_id +' attachmentId = '+ attachment_id +'></i>' +
        '</a>' +
        '</div>' +
        '<div class="file-info">' +
        '<span>name</span>' +
        '<span>date</span>' +
        '</div>' +
        '</div>';
    $('#fileList').append(file);
}

//添加附件标签页
function addAttachment() {
    $('#anchorLayerTab').tabs('close','附件');
    var attachmentHtml = '<ul class="toggle-attr" id="anchorFile">' +
                    '        <li uid="image" class="active">图片</li>' +
                    '        <li uid="file">文档</li>' +
                    '    </ul>' +
                    '    <div id="image">' +
                    '        <div id="addImage"></div>' +
                    '        <div id="imgList">' +
                    '        </div>' +
                    '    </div>' +
                    '    <div id="file" style="display: none">' +
                    '        <div id="addFile"></div>' +
                    '        <div id="fileList"></div>' +
                    '    </div>';
    $('#anchorLayerTab').tabs('add',{
        title: '附件',
        selected: false,
        content:attachmentHtml
    });
    //加载图片上传插件
    addImageFun();
    //加载文档上传插件
    addFileFun();
    //图片文档切换
    $('#anchorFile li').click(function () {
        var uid = $(this).attr('uid');
        $(this).addClass('active').siblings().removeClass('active');
        $('#'+ uid).show().siblings('div').hide();
    });
    //添加样式
    $('#anchorFile').parent().css('padding','20px');
    $('.webuploader-pick').next().css({
        width:'60px',
        height:'20px',
        right:0,
        left:'auto'
    });
}
//显示删除按钮
$('.imgItem').bind('mouseover mouseleave',function (e) {
    if(e.type == 'mouseover'){
        $(this).find("a,span").show();
    }else{
        $(this).find('a,span').hide();
    }
});


//删除附件
function delAttachmentImg(that) {
    var anchor_point_id = $(that).attr('pointId');
    var attachment_id = $(that).attr('attachmentId');
    $.ajax({
        url: "./delAttachment",
        type: "post",
        data: {
            anchor_point_id:anchor_point_id,
            attachment_id:attachment_id
        },
        dataType: "json",
        success: function (res) {
            $(that).parents('.imgItem').remove();
        }
    })
}

//下载文档
function relationDownload(that) {
    var attachment_id = $(that).attr('attachmentId');
    $.download({
        url:'./relationDownload',
        data:{
            attachment_id:attachment_id
        }
    })
}

