layui.use(['laydate', 'form'], function () {
    laydate = layui.laydate;
    form = layui.form;
});

//构建填报列表
var admin_table = $('#admin_table').DataTable({
    pagingType: "full_numbers",
    processing: true,
    serverSide: true,
    bAutoWidth: false,  //是否自动宽度
    scrollX: true,
    scrollY: "520px",   //表格容器高度
    scrollCollapse: true,
    ajax: {
        url: "/progress/common/datatablesPre?tableName=progress_actual"
    },
    dom: '<"#add.layui-btn layui-btn-normal layui-btn-sm btn-right btn-space"><"#selectWrap.select-wrap">tlip',
    columns: [
        {
            name: "section_name"
        },
        {
            name: "actual_date"
        },
        {
            name: "user_name"
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
            sWidth: "10%",       //根据按钮个数设置适当的宽度，支持px单位【注：该属性需要和bAutoWidth: false搭配使用】
            searchable: false,
            orderable: false,
            targets: [4],
            render: function (data, type, row) {
                var rowId = row[4];
                var html = '<i class="fa fa-eye" title="查看" id="view" onclick="view('+ rowId +')"></i>';
                html += '<i class="fa fa-trash" title="删除" onclick="del('+ rowId +')"></i>';
                return html;
            }
        }
    ],
    fnCreatedRow: function (nRow, aData, iDataIndex) {
        $.fn.dataTable.tables({visible: true, api: true}).columns.adjust();
    },
    fnInitComplete: function (oSettings) {
        $('#add').html('新增');
        constructingQueryConditions();
    },
    language: {
        "lengthMenu": "_MENU_ ",
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
    }
});

//构建查询条件
function constructingQueryConditions() {
    var selectEm = '<form class="layui-form">' +
        '<div class="layui-form-item">' +
        '<label class="layui-form-label">标段</label>' +
        '<div class="layui-input-inline">' +
        '<select id="segment" lay-filter="searchSegment"></select>' +
        '</div>' +
        '<label class="layui-form-label">选择日期</label>' +
        '<div class="layui-input-inline">' +
        '<input type="text" class="layui-input date" id="startDate">' +
        '</div>' +
        '</div>' +
        '</form>';
    $('#selectWrap').append(selectEm);
    fillSearchSegment();
    form.render();
}

//填充筛选标段
function fillSearchSegment() {
    $.ajax({
        url: "./index",
        type: "post",
        dataType: "json",
        success: function (res) {
            $('#segment').empty();
            if(res.code==1){
                for (i in res.sectionArr) {
                    if (res.sectionArr.hasOwnProperty(i)) {
                        $('#segment').append('<option value=' + i + '>' + res.sectionArr[i] + '</option>');
                    }
                }
                form.render();
                selectDate();
                dateScope();
            }
        }
    });
}

//根据选择的标段获取对应的日期区间范围
function dateScope() {
    var section_id = $('dd.layui-this').attr('lay-value');
    $('#startDate').attr('segmentId',section_id);
    form.on('select(searchSegment)', function(data){
        $('#startDate').attr('segmentId',data.value);
        $.ajax({
            url: "./dateScope",
            type: "post",
            data: {
                section_id:data.value
            },
            dataType: "json",
            success: function (res) {
                $('#startDate').val('');
            }
        });
    });
}

//选择日期
function selectDate() {
    laydate.render({
        //构建起止时间
        elem: '#startDate',
        done: function(value, date, endDate){
            var section_id = $('#startDate').attr('segmentId');
            admin_table.ajax.url('/progress/common/datatablesPre?tableName=progress_actual&section_id='+section_id+'&actual_date='+value).load();
        }
    });
}

//触发新增弹层
$('#add').click(function () {
    addLayer();
});

//新增弹层
function addLayer() {
    addIndex = layer.open({
        title: '新增实时进度',
        id: '1',
        type: '1',
        area: ['600px', '460px'],
        content: $('#addLayout'),
        success: function () {
            laydate.render({
                elem: '#date',
                value: new Date()
            });
            save();
            upload();
            getSegmentAndUserInfo();
            //关闭弹层
            $('#close').click(function () {
                layer.close(addIndex);
            });
            uploader.reset();
            $('#remark').val('');
            $('#attachment_name').val('');
            $('#attachment_id').val('');
            $('#path').val('');
        }
    });
}

//获取标段及用户信息
function getSegmentAndUserInfo() {
    $.ajax({
        url: "./addInitialise",
        type: "post",
        dataType: "json",
        success: function (res) {
            $('#section_id').empty();
            var section = res.data.section;
            var user_id = res.data.user.user_id;
            var user_name = res.data.user.user_name;
            if (res.code == 1) {
                for (i in section) {
                    if (section.hasOwnProperty(i)) {
                        $('#section_id').append('<option value=' + i + '>' + section[i] + '</option>');
                    }
                }
                $('#user_id').val(user_id);
                $('#user_name').val(user_name);
                form.render();
            }
        }
    });
}

//上传
function upload() {
    uploader = WebUploader.create({
        //构建上传功能
        auto: true,
        swf: '/static/public/webupload/Uploader.swf',
        server: "/admin/common/upload?module=progress&use=actual",
        pick: {
            multiple: false,
            id: "#upload",
            innerHTML: "上传"
        },
        accept: {
            title: 'Images',
            extensions: 'gif,jpg,jpeg,bmp,png',
            mimeTypes: 'image/jpg,image/jpeg,image/png'
        },
        resize: false,
        duplicate: true
    });
    uploader.on('fileQueued', function (file) {
        // 当有文件被添加进队列的时候
        var $list = $('#uploadListDemo');
        $list.html('');
        $list.append('<div id="' + file.id + '" class="item"></div>');
    });
    uploader.on('uploadProgress', function (file, percentage) {
        // 文件上传过程中创建进度条实时显示。
        var $li = $('#' + file.id),
            $percent = $li.find('.layui-progress .layui-progress-bar');
        if (!$percent.length) {
            // 避免重复创建
            $('<div class="layui-progress layui-progress-big" lay-showpercent="yes" lay-filter="upload">' +
                '<div class="layui-progress-bar layui-bg-red" id="haha" lay-percent="0%" style="width: 0%;"></div>' +
                '</div>').appendTo($li).find('.layui-progress-bar');
        }
        layui.use('element', function () {
            element = layui.element;
            element.progress('upload', percentage * 100 + '%');
        });
        $('.layui-progress-bar').html(Math.round(percentage * 100) + '%');
    });
    uploader.on('uploadSuccess', function (file, res) {
        //上传成功
        $('#attachment_id').val(res.id);
        $('#attachment_name').val(file.name);
        $('#path').val(res.src);
        $('#uploadListDemo').css('opacity', 0);
    });
}

//保存新增
function save() {
    form.on('submit(save)', function (data) {
        $.ajax({
            url: "./add",
            type: "post",
            data: data.field,
            dataType: "json",
            success: function (res) {
                if(res.code==1){
                    admin_table.ajax.url('/progress/common/datatablesPre?tableName=progress_actual').load();
                }
                layer.msg(res.msg);
                if(res.code==-1){
                    return false;
                }
                layer.close(addIndex);
            }
        });
        return false;
    });
}

//查看
function view(actual_id) {
    $.ajax({
        url: "./preview",
        type: "post",
        data: {
            actual_id:actual_id
        },
        dataType: "json",
        success: function (res) {
            layer.photos({
                photos: {
                    "title": "", //相册标题
                    "id": 1, //相册id
                    "start": 0, //初始显示的图片序号，默认0
                    "data": [   //相册包含的图片，数组格式
                        {
                            "alt": "旁站记录表照片",
                            "pid": 666, //图片id
                            "src": res.path.path, //原图地址
                            "thumb": "" //缩略图地址
                        }
                    ]
                },
                anim: Math.floor(Math.random()*7),
                shade: [0.8, '#333'],
                shadeClose:true,
                closeBtn:1
            });
        }
    });
}

//删除
function del(actual_id) {
    layer.confirm('确定删除该条填报数据么？', {icon: 3, title:'提示'}, function(index){
        $.ajax({
            url: "./del",
            type: "post",
            data: {
                actual_id:actual_id
            },
            dataType: "json",
            success: function (res) {
                if(res.code==1){
                    admin_table.ajax.url('/progress/common/datatablesPre?tableName=progress_actual').load();
                }
                layer.msg(res.msg);
            }
        });
        layer.close(index);
    });

}