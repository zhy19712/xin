/*
fileList:存放添加文件的区域ID
filePicker：要点击的元素ID，点击过该元素，弹出文件选择框
showFileName:显示上传文件控件的ID，一般为span，div等，所以使用的时候是：$("#showFileName").html(fileName)
fileName:记录上传文件名称的控件ID;赋值是val()
filePath：记录上传到服务器端文件存放路径的控件ID;赋值是val()
fileSize：上传文件的大小的控件ID;赋值是val()
fileExtend：文件扩展名的控件ID;赋值是val()
serverPath:服务端接收处理文件的路径。
IsMultiple:是否上传多个文件，需要上传多个传入true，只上传单个文件为false
*/
var applicationPath = window.applicationPath === "" ? "" : window.applicationPath || "../../";
var uploadFile = function (fileList, filePicker,showFileName, fileName, filePath, fileSize, fileExtension, serverPath, IsMultiple) {
    var $ = jQuery,
    $list = $('#' + fileList),
    // 优化retina, 在retina下这个值是2
    ratio = window.devicePixelRatio || 1,

    // Web Uploader实例
    uploader;
    uploader = WebUploader.create({
        // 选完文件后，是否自动上传。
        auto: true,

        disableGlobalDnd: false,
        // swf文件路径
        swf: applicationPath + '../Content/plugins/webuploader/Uploader.swf',

        // 文件接收服务端。
        //server: applicationPath + '/ModelFile/UpLoadProcess?modelTypeId=' + $("#hidModeFileTypeId").val() + "&flag=" + flag,
        server: applicationPath + serverPath,

        // 选择文件的按钮。可选。
        // 内部根据当前运行是创建，可能是input元素，也可能是flash.
        pick: { id: '#' + filePicker, multiple: IsMultiple },
        duplicate: true,
        formdata: {},

    });

    // 当有文件添加进来的时候
    uploader.on('fileQueued', function (file) {
        var $li = $(
                '<div id="' + file.id + '" class="cp_img">' +
                    '<img>' +
                '<div class="cp_img_jian"></div></div>'
                ),
            $img = $li.find('img');

        // $list为容器jQuery实例
        $list.append($li);
    });
    //var data = [];
    // 文件上传成功，记录上传文件的信息。
    uploader.on('uploadSuccess', function (file, response) {
        console.log(response);
        $("#showFileName").html(response.result.fileName)
        ////
        $("#" + fileName).val(response.result.fileName);
        $("#" + filePath).val(response.result.filePath);
        $("#" + fileSize).val(response.result.fileSize);
        $("#" + fileExtension).val(response.result.extension);
    });

    // 文件上传失败，显示上传出错。
    uploader.on('uploadError', function (file) {
        layerAlert("上传失败了！");
    });

    //所有文件上传完毕
    uploader.on("uploadFinished", function () {
        //提交表单
        //alert("ok");
    });
};


var uploadFile11 = function (fileList, filePicker, showFileName, fileArray, serverPath) {
    var $ = jQuery,
    $list = $('#' + fileList),
    // 优化retina, 在retina下这个值是2
    ratio = window.devicePixelRatio || 1,

    // Web Uploader实例
    uploader;
    uploader = WebUploader.create({
        // 选完文件后，是否自动上传。
        auto: true,

        disableGlobalDnd: false,
        // swf文件路径
        swf: applicationPath + '../Content/plugins/webuploader/Uploader.swf',

        // 文件接收服务端。
        //server: applicationPath + '/ModelFile/UpLoadProcess?modelTypeId=' + $("#hidModeFileTypeId").val() + "&flag=" + flag,
        server: applicationPath + serverPath,

        // 选择文件的按钮。可选。
        // 内部根据当前运行是创建，可能是input元素，也可能是flash.
        pick: { id: '#' + filePicker, multiple: true },
        duplicate: true
    });

    // 当有文件添加进来的时候
    uploader.on('fileQueued', function (file) {
        var $li = $(
                '<div id="' + file.id + '" class="cp_img">' +
                    '<img>' +
                '<div class="cp_img_jian"></div></div>'
                ),
            $img = $li.find('img');

        // $list为容器jQuery实例
        $list.append($li);
    });
    // 文件上传成功，记录上传文件的信息。
    uploader.on('uploadSuccess', function (file, response) {
        //$("#showFileName").html(response.result.fileName)
        //
        //$("#" + fileName).val(response.result.fileName);
        //$("#" + filePath).val(response.result.filePath);
        //$("#" + fileSize).val(response.result.fileSize);
        //$("#" + fileExtension).val(response.result.extension);
        var str = { "fileName": response.result.fileName, "filePath": response.result.filePath, "fileSize": response.result.fileSize, "fileExtension": response.result.extension, "id": response.result.id, "IsFromDb": 0 };
        fileArray.push(str);
    });

    // 文件上传失败，显示上传出错。
    uploader.on('uploadError', function (file) {
        layerAlert("上传失败了！");
    });

    //所有文件上传完毕
    uploader.on("uploadFinished", function () {
        //提交表单
        //alert("ok");
    });
};