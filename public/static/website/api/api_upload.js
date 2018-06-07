uploader = WebUploader.create({
    auto: true,
    swf: '/static/public/webupload/Uploader.swf',
    server: "/admin/common/upload",
    pick: {
        multiple: false,
        id: "#upload",
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
    var $list = $('#uploadList');
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
    $('#uploadList').css('opacity',0);
});