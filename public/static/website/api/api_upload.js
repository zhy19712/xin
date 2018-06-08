uploaderLay = WebUploader.create({
    auto: true,
    swf: '/static/public/webupload/uploaderLay.swf',
    server: "/admin/common/upload",
    pick: {
        multiple: false,
        id: "#uploadLay",
        innerHTML: "上传"
    },
    accept: {
        title: '',
        extensions: '',
        mimeTypes: ''
    },
    resize: false,
    duplicate: true
});
// 当有文件被添加进队列的时候
uploaderLay.on('fileQueued', function (file) {
    var $list = $('#uploadListLay');
    $list.html('');
    $list.append('<div id="' + file.id + '" class="item"></div>');
});
// 文件上传过程中创建进度条实时显示。
uploaderLay.on('uploadProgress', function (file, percentage) {
    var $li = $('#' + file.id),
        $percent = $li.find('.layui-progress .layui-progress-bar');
    // 避免重复创建
    if (!$percent.length) {
        $('<div class="layui-progress layui-progress-big" lay-showPercent="yes" lay-filter="upload">' +
            '<div class="layui-progress-bar layui-bg-red" id="haha" lay-percent="0%"></div>' +
            '</div>').appendTo($li).find('.layui-progress-bar');
    }
    layui.use('element', function () {
        element = layui.element;
        element.progress('upload', percentage * 100 + '%');
    });
    $('.layui-progress-bar').html(Math.round(percentage * 100) + '%');
});
//上传成功
uploaderLay.on('uploadSuccess', function (file, response) {
    $('#uploadList').css('opacity',0);
});


uploader = WebUploader.create({
    auto: true,
    swf: '/static/public/webupload/Uploader.swf',
    server: "/admin/common/upload",
    pick: {
        multiple: false,
        id: "#uploadDemo",
        innerHTML: "上传"
    },
    accept: {
        title: '',
        extensions: '',
        mimeTypes: ''
    },
    resize: false,
    duplicate: true
});
// 当有文件被添加进队列的时候
uploader.on('fileQueued', function (file) {
    var $list = $('#uploadListDemo');
    $list.html('');
    $list.append('<div id="' + file.id + '" class="item"></div>');
});
// 文件上传过程中创建进度条实时显示。
uploader.on('uploadProgress', function (file, percentage) {
    var $li = $('#' + file.id),
        $percent = $li.find('.layui-progress .layui-progress-bar');
    // 避免重复创建
    if (!$percent.length) {
        $('<div class="layui-progress layui-progress-big" lay-showPercent="yes" lay-filter="upload">' +
            '<div class="layui-progress-bar layui-bg-red" id="haha" lay-percent="0%"></div>' +
            '</div>').appendTo($li).find('.layui-progress-bar');
    }
    layui.use('element', function () {
        element = layui.element;
        element.progress('upload', percentage * 100 + '%');
    });
    $('.layui-progress-bar').html(Math.round(percentage * 100) + '%');
});
//上传成功
uploader.on('uploadSuccess', function (file, response) {
    $('#uploadListDemo').css('opacity',0);
    $('input[name="file"][type="text"]~').val(file.name);
});


//图片
uploadImgDemo = WebUploader.create({
    auto: true,
    swf: '/static/public/webupload/Uploader.swf',
    server: "/admin/common/upload",
    pick: {
        multiple: false,
        id: "#uploadImgDemo",
        innerHTML: "图像上传"
    },
    accept: {
        title: 'Images',
        extensions: 'gif,jpg,jpeg,bmp,png',
        mimeTypes: 'image/jpg,image/jpeg,image/png'
    },
    resize: false,
    duplicate: true
});

//上传开始
uploadImgDemo.on("uploadStart",function (file) {
    $(uploadImgDemo.options.pick.id).find('#thumbnail').remove();
    $(uploadImgDemo.options.pick.id).prepend('<img src="" alt="" class="thumbnail" id="thumbnail">');
});

//上传成功
uploadImgDemo.on('uploadSuccess', function (file, res) {
    $('#thumbnail').attr('src',res.src);
});


//单个按钮
uploadBtnDemo = WebUploader.create({
    auto: true,
    swf: '/static/public/webupload/Uploader.swf',
    server: "/admin/common/upload",
    pick: {
        multiple: false,
        id: "#uploadBtnDemo",
        innerHTML: "上传"
    },
    accept: {
        title: '',
        extensions: '',
        mimeTypes: ''
    },
    resize: false,
    duplicate: true
});

//上传成功
uploadBtnDemo.on('uploadSuccess', function (file, res) {
    layer.msg('上传成功');
});