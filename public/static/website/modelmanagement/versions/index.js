var model_type; //模型类型： 1为竣工模型 2为施工模型
var attachment_id; //保存文件编号
//竣工模型表
var completedTable = $('#completedTable').DataTable({
    pagingType: "full_numbers",
    processing: true,
    serverSide: true,
    ajax: {
        "url": "/modelmanagement/common/datatablesPre.shtml?tableName=model_version_management"
    },
    dom: 'lf<".addBtn layui-btn layui-btn-normal btn-right btn-space">rtip',
    columns: [
        {
            name: "id"
        }, {
            name: "resource_name"
        },
        {
            name: "resource_path"
        },
        {
            name: "version_number"
        },
        {
            name: "version_date"
        },
        {
            name: "remake"
        },
        {
            name: "status"
        }
    ],
    columnDefs: [
        {
            "searchable": false,
            "orderable": false,
            "targets": [6],
            "render": function (data, type, row) {
                var rowId = row[0];     //序号（主键编号）
                var status = row[6];    //启用状态  1为启用  0为禁用
                var className = status == 1 ? 'fa-eye' : 'fa-eye-slash';
                var txt = status == 1 ? '禁用' : '启用';
                var html = "<i title='查看' class='fa fa-search' onclick='view(this," + rowId + ")'></i>";
                html += "<i title='删除' class='fa fa-trash' onclick='delFile(this," + rowId + ")'></i>";
                html += "<i title=" + txt + " class='fa " + className + "' onclick='enable(" + rowId + ")'></i>";
                return html;
            }
        }

    ],
    language: {
        "sProcessing": "数据加载中...",
        "lengthMenu": "_MENU_",
        "zeroRecords": "没有找到记录",
        "info": "第 _PAGE_ 页 ( 总共 _PAGES_ 页 )",
        "infoEmpty": "无记录",
        "search": "搜索",
        "sSearchPlaceholder": "请输入关键字",
        "infoFiltered": "(从 _MAX_ 条记录过滤)",
        "paginate": {
            "sFirst": "<<",
            "sPrevious": "上一页",
            "sNext": "下一页",
            "sLast": ">>"
        }
    }/*,
    "fnInitComplete": function (oSettings, json) {
        $('#tableItem_length').insertBefore(".mark");
        $('#tableItem_info').insertBefore(".mark");
        $('#tableItem_paginate').insertBefore(".mark");
    }*/
});

$('.addBtn').html('新增');

//施工模型表
var constructionTable = $('#constructionTable').DataTable({
    pagingType: "full_numbers",
    processing: true,
    serverSide: true,
    ajax: {
        "url": "/modelmanagement/common/datatablesPre.shtml?tableName=model_version_management"
    },
    dom: 'lf<".addBtn layui-btn layui-btn-normal btn-right">rtip',
    columns: [
        {
            name: "id"
        }, {
            name: "resource_name"
        },
        {
            name: "resource_path"
        },
        {
            name: "version_number"
        },
        {
            name: "version_date"
        },
        {
            name: "remake"
        },
        {
            name: "status"
        }
    ],
    columnDefs: [
        {
            "searchable": false,
            "orderable": false,
            "targets": [6],
            "render": function (data, type, row) {
                var rowId = row[0];     //序号（主键编号）
                var status = row[6];   //启用状态  1为启用  0为禁用
                var className = status == 1 ? 'fa-eye' : 'fa-eye-slash';
                var txt = status == 1 ? '禁用' : '启用';
                var html = "<i title='查看' class='fa fa-search' onclick='view(this," + rowId + ")'></i>";
                html += "<i title='删除' class='fa fa-trash' onclick='delFile(this," + rowId + ")'></i>";
                html += "<i title="+ txt +" class='fa " + className + "' onclick='enable(" + rowId + ")'></i>";
                return html;
            }
        }

    ],
    language: {
        "sProcessing": "数据加载中...",
        "lengthMenu": "_MENU_",
        "zeroRecords": "没有找到记录",
        "info": "第 _PAGE_ 页 ( 总共 _PAGES_ 页 )",
        "infoEmpty": "无记录",
        "search": "搜索",
        "sSearchPlaceholder": "请输入关键字",
        "infoFiltered": "(从 _MAX_ 条记录过滤)",
        "paginate": {
            "sFirst": "<<",
            "sPrevious": "上一页",
            "sNext": "下一页",
            "sLast": ">>"
        }
    }/*,
    "fnInitComplete": function (oSettings, json) {
        $('#tableItem_length').insertBefore(".mark");
        $('#tableItem_info').insertBefore(".mark");
        $('#tableItem_paginate').insertBefore(".mark");
    }*/
});

$('.addBtn').html('新增');

//切换模型表
$('#tt').tabs({
    onSelect: function (title, index) {
        //切换至竣工模型表
        if (index == 0) {
            model_type = 1;
            completedTable.ajax.url('/modelmanagement/common/datatablesPre.shtml?tableName=model_version_management&model_type=' + model_type + '').load();
        }
        //切换至施工模型表
        if (index == 1) {
            model_type = 2;
            constructionTable.ajax.url('/modelmanagement/common/datatablesPre.shtml?tableName=model_version_management&model_type=' + model_type + '').load();
        }
    }
});

//打开新增模型弹层
$('.addBtn').click(function () {
    index = layer.open({
        title: '新增模型',
        id: '1',
        type: 1,
        anim: '4',
        area: ['600px', '355px'],
        content: $('#addModelLayer'),
        success: function () {
            uploadModel();
            $('#save').removeClass('layui-btn-disabled').attr('lay-submit');
        },
        cancel: function (index) {
            $('#addModelLayer').hide();
            layer.close(index);
        }
    });
});

layui.use('element', function () {
    element = layui.element;
});


function uploadModel() {
    // 创建上传
    uploader = WebUploader.create({
        auto: true,
        swf: '/static/public/webupload/Uploader.swf',
        server: './upload',      // 服务端地址
        pick: {
            multiple: false,
            id: "#picker",
            innerHTML: "上传"
        },
        resize: false,
        duplicate: true,
        chunked: true,            //开启分片上传
        chunkSize: 1024 * 1024 * 100,   //每一片的大小
        chunkRetry: 5,          // 如果遇到网络错误,重新上传次数
        threads: 1,               // [默认值：3] 上传并发数。允许同时最大上传进程数。
        fileNumLimit: 500,
        fileSizeLimit: 1024 * 1024 * 1024 * 10,
        fileSingleSizeLimit: 1024 * 1024 * 1024 * 10,
        formData: {model_type: model_type},
        accept: {
            title: 'Rar,Zip',
            extensions: 'rar,zip',
            mimeTypes: '.rar,.zip'
        },
    });

    uploader.on('uploadStart', function (file) {
        console.log(file);
        $.ajax({
            url: "./checkUpload",
            type: "post",
            data: {
                file_name: file.name
            },
            dataType: "json",
            success: function (res) {
                console.log(res);
                if (res.code == -1) {
                    uploader.reset();
                    layer.msg(res.msg);
                }
            }
        });
    });

    // 当有文件被添加进队列的时候
    uploader.on('fileQueued', function (file) {
        var $list = $('#thelist');
        $list.html('');
        $list.append('<div id="' + file.id + '" class="item">' +
            '</div>');
    });
    // 文件上传过程中创建进度条实时显示。
    uploader.on('uploadProgress', function (file, percentage) {
        $('#save').hide();
        var $li = $('#' + file.id),
            $percent = $li.find('.layui-progress .layui-progress-bar');
        // 避免重复创建
        if (!$percent.length) {
            $('<div class="layui-progress layui-progress-big" lay-showpercent="yes" lay-filter="upload">' +
                '<div class="layui-progress-bar layui-bg-red" id="haha" lay-percent="0%" style="width: 0%;"></div>' +
                '</div>').appendTo($li).find('.layui-progress-bar');
        }
        layui.use('element', function () {
            element = layui.element;
            element.progress('upload', percentage * 100 + '%');
        });
        $('.layui-progress-bar').html(Math.round(percentage * 100) + '%');
        if (percentage * 100 == 100) {
            loading = layer.load(1, {
                shade: [0.5, '#000'],
                content: '合并和解压中...请稍后'
            });
        }
    });

    //模型上传成功
    uploader.on('uploadSuccess', function (file, response) {
        $.ajax({
            url: "./saveFile",
            type: "post",
            data: {
                oldName: response.oldName,
                uploadPath: response.filePaht,
                extension: response.fileSuffixes,
                path: response.path
            },
            dataType: "json",
            success: function (res) {
                if (res.code == 2) {
                    $('.upload-list').empty();
                    $('#resource_name').val(file.name);
                    $('#save').show();
                    attachment_id = res.id;
                    layer.close(loading);
                    layer.msg(res.msg);
                } else {
                    layer.msg('合并解压出错,请重新上传');
                }
            }
        });
    });

    uploader.on('uploadError', function (file) {
        layer.msg('上传出错');
    });
}

//保存模型
layui.use('form', function () {
    var form = layui.form;
    form.on('submit(save)', function (data) {
        data.field.model_type = model_type;
        data.field.attachment_id = attachment_id;
        var load = layer.load(1, {
            shade: [0.5, '#000'],
            content: '保存中...请稍后'
        });
        $.ajax({
            url: "./add",
            type: "post",
            data: data.field,
            dataType: "json",
            success: function (res) {
                if (model_type == 1) {
                    completedTable.ajax.url('/modelmanagement/common/datatablesPre.shtml?tableName=model_version_management&model_type=1').load();
                }
                if (model_type == 2) {
                    constructionTable.ajax.url('/modelmanagement/common/datatablesPre.shtml?tableName=model_version_management&model_type=2').load();
                }
                $('.upload-list').empty();
                $('#resource_name').val('');
                layer.msg(res.msg);
                layer.close(index);
                layer.close(load);
                uploader.reset();
            }
        })
        return false;
    });
});

//关闭新增模型弹层
$('#close').click(function () {
    $('#addModelLayer').hide();
    $('#resource_name').val('');
    layer.close(index);
    $('.upload-list').empty();
    uploader.reset();
});

//查看版本
function view(rowId) {
    layer.open({
        title: '查看模型',
        id: '2',
        type: 2,
        area: ['100%', '100%'],
        content: ['./viewmodel', 'no'],
        success: function (layero, index) {
        },
        cancel: function (index, layero) {
            layer.close(layer.index);
        }
    });
}

//删除版本
function delFile(that, rowId) {
    layer.confirm('确认删除该模型版本?', {icon: 3, title: '提示'}, function (index) {
        $.ajax({
            url: "./del",
            type: "post",
            data: {
                major_key: rowId
            },
            dataType: "json",
            success: function (res) {
                $(that).parents('tr').remove();
                layer.msg(res.msg);
                if (model_type == 1) {
                    completedTable.ajax.url('/modelmanagement/common/datatablesPre.shtml?tableName=model_version_management&model_type=1').load();
                }
                if (model_type == 2) {
                    constructionTable.ajax.url('/modelmanagement/common/datatablesPre.shtml?tableName=model_version_management&model_type=2').load();
                }
            }
        });
        layer.close(index);
    });

}

//启用版本
function enable(rowId) {
    $.ajax({
        url: "./enabledORDisable",
        type: "post",
        data: {
            major_key: rowId
        },
        dataType: "json",
        success: function (res) {
            if (model_type == 1) {
                completedTable.ajax.url('/modelmanagement/common/datatablesPre.shtml?tableName=model_version_management&model_type=1').load();
            }
            if (model_type == 2) {
                constructionTable.ajax.url('/modelmanagement/common/datatablesPre.shtml?tableName=model_version_management&model_type=2').load();
            }
            layer.msg(res.msg);
        }
    })
}
