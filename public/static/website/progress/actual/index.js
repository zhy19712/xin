layui.use(['laydate','form'], function(){
    laydate = layui.laydate;
    form = layui.form;
});

//填报列表
$('#admin_table').DataTable({
    pagingType: "full_numbers",
    processing: true,
    serverSide: true,
    bAutoWidth: false,  //是否自动宽度
    scrollX: true,
    scrollY: "520px",   //表格容器高度
    scrollCollapse: true,
    ajax: {
        url: "/contract/common/datatablesPre?tableName=section"
    },
    dom: 'l<"#add.layui-btn layui-btn-normal layui-btn-sm btn-right btn-space"><"#selectWrap.select-wrap">tip',
    columns: [
        {
            name: "code"
        },
        {
            name: "name"
        },
        {
            name: "money"
        },
        {
            name: "builder"
        },
        {
            name: "constructor"
        },
        {
            name: "designer"
        },
        {
            name: "supervisor"
        },
        {
            name: "id"
        }
    ],
    columnDefs: [
        {
            sWidth:"10%",       //根据按钮个数设置适当的宽度，支持px单位【注：该属性需要和bAutoWidth: false搭配使用】
            searchable: false,
            orderable: false,
            targets: [7],
            render: function (data, type, row) {
                var html = '<i class="fa fa-eye" title="查看" id="view" onclick="view(this)"></i>';
                html += '<i class="fa fa-trash" title="删除" onclick="del(this)"></i>';
                return html;
            }
        }
    ],
    fnCreatedRow:function (nRow, aData, iDataIndex){
        $.fn.dataTable.tables( {visible: true, api: true} ).columns.adjust();
    },
    fnInitComplete:function (oSettings) {
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
$('#add').html('新增');
$('#save').html('保存');

//构建查询条件
function constructingQueryConditions() {
    var selectEm = '<form class="layui-form">' +
        '<div class="layui-form-item">' +
        '<label class="layui-form-label">标段</label>' +
        '<div class="layui-input-inline">' +
        '<select>' +
        '<option value="">标段1</option>' +
        '<option value="">标段2</option>' +
        '</select>' +
        '</div>' +
        '<label class="layui-form-label">起止日期</label>' +
        '<div class="layui-input-inline">' +
        '<input type="text" class="layui-input date" id="startDate">' +
        '</div>' +
        '<div class="layui-form-mid layui-word-aux no-position">--</div>' +
        '<div class="layui-input-inline">' +
        '<input type="text" class="layui-input date" id="endDate">' +
        '</div>' +
        '</div>' +
        '</form>';
    $('#selectWrap').append(selectEm);

    laydate.render({
        //构建开始时间
        elem: '#startDate'
    });
    laydate.render({
        //构建结束时间
        elem: '#endDate'
    });
    form.render();
}

//上传
function upload() {
    uploader = WebUploader.create({
        //构建上传功能
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
    uploader.on('uploadSuccess', function (file, response) {
        //上传成功
        $('#uploadListDemo').css('opacity',0);
    });
}

//新增弹层
function addLayer(openType) {
    addIndex = layer.open({
        title:'新增实时进度',
        id:'1',
        type:'1',
        area:['600px','460px'],
        content:$('#addLayout'),
        success:function () {
            if(openType){
                laydate.render({
                    elem: '#date',
                    value:new Date()
                });
                save();
                upload();
                $('#addLayout').find('input,textarea').attr('disabled',false);
                $('#upload,.save').show();
            }else{
                $('#addLayout').find('input,textarea').attr('disabled',true);
                $('#upload,.save').hide();
            }
            //关闭弹层
            $('#close').click(function(){
                layer.close(addIndex);
            });
        }
    });
}

//保存新增
function save() {
    form.on('submit(save)', function(data){
        $.ajax({
            url: "",
            type: "post",
            data: data.field,
            dataType: "json",
            success: function (res) {
                layer.close(addIndex);
            }
        });
        return false;
    });
}

//触发弹层
$('#add').click(function(){
    addLayer(true);
});

//查看信息
function view() {
    addLayer(false);
}


