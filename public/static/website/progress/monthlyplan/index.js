/*点击新增月计划*/
$("#addPlanTask").click(function () {
    if($("#seleBids").val() != ''){
        var index = layer.open({
            title:'新增月进度计划',
            id:'1001',
            type:'1',
            area:['760px','650px'],
            content:$('#addPlan'),
            success:function () {
                $("#secName").val($("#seleBids option:selected").text());
                $("#sec_id").val($("#seleBids").val());
            },
            yes:function () {

            },
            cancel: function(index, layero){
                layer.close(layer.index);
                $("#addPlan input[name='plan_name']").val("");
                $("#addPlan input[name='report_id']").val("");
                $("textarea").val("");
                $("#coverId").val(0);
                $("#saveMonthPlan").text("保存");
            }
        });
    }else {
        layer.msg("请选择标段！")
    }
});

/*关闭弹层*/
$('.close').click(function () {
    layer.closeAll('page');
    $("#addPlan input[name='plan_name']").val("");
    $("#addPlan input[name='report_id']").val("");
    $("textarea").val("");
    $("#coverId").val(0);
    $("#saveMonthPlan").text("保存");
});

var form;
layui.use(['form', 'layedit', 'laydate'], function () {
    form = layui.form;
    var laydate = layui.laydate;
    var layer = layui.layer;
    //年选择器
    laydate.render({
        elem: '#testYear'
        ,type: 'year'
        ,value: new Date().getFullYear() //必须遵循format参数设定的格式
    });

    //年月选择器
    laydate.render({
        elem: '#testMonth'
        ,type: 'month'
        ,value: ""+new Date().getFullYear()+ "-" + (new Date().getMonth()+1)+""
    });
    /*显隐上传*/
    form.on('radio(aihao)', function(data){
        console.log(data.value)
        if(data.value == 1){
            $("#modelListSelect").hide();
            $("#modelList").hide();
        }else if(data.value == 2){
            $("#modelListSelect").show();
            $("#modelList").show();
        }
    });
});

/*上传计划报告*/
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
    formData:{
        module:'progress',
        use:'monthlyplan'
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
//上传成功
uploader.on('uploadSuccess', function (file, res) {
    $('#uploadListDemo').css('opacity',0);
    $('#report_id').val(file.name);
    if(res.code == 2){
        $('#plan_report_id_hidden').val(res.id);
    }else{
        layer.msg("数据异常！")
    }
});

/*点击保存*/
$("#saveMonthPlan").click(function () {
    layui.use(['form', 'layedit', 'laydate'], function () {
        var form = layui.form;
        var layer = layui.layer

        //监听提交
        form.on('submit(save)', function (data) {
            $.ajax({
                type: "Post",
                url: "/progress/monthlyplan/add",
                data: data.field,
                success: function (res) {
                    if (res.code == 1) {
                        layer.msg(res.msg);
                        layer.closeAll('page');
                        $("#addPlan input[name='plan_name']").val("");
                        $("#addPlan input[name='report_id']").val("");
                        $("#coverId").val(0);
                        $("#saveMonthPlan").text("保存");
                        $("textarea").val("");
                        yearFun($("#seleBids").val());
                        monthFun($("#seleBids").val(),$("#testYear").val());
                    }else if (res.code == 2) {
                        layer.confirm(res.msg, function(index){
                            $("#coverId").val(1);
                            $("#saveMonthPlan").text("确认覆盖");
                            layer.close(index);
                            yearFun($("#seleBids").val());
                            monthFun($("#seleBids").val(),$("#testYear").val());
                        });
                    }else {
                        layer.msg(res.msg);
                    }
                }
            })
            return false;
        });
    });
})

/*点击月计划列表*/
function monthlyPlanList() {
    layer.open({
        type: 2,
        title: "标段"+$("#seleBids option:selected").text()+"-月进度计划",
        area: ['100%', '100%'],
        content: '/progress/monthlyplan/list_table?plan_type=1&section_id='+$("#seleBids").val(),
    });
}











//提醒配置
function remindConfig() {
    if($("#seleBids").val() != ''){
        var index = layer.open({
            title: $("#seleBids option:selected").text()+'标段-月进度填报提醒配置',
            id:'1003',
            type:'1',
            area:['760px','350px'],
            content:$('#remConfiglayer'),
            success:function () {
                $("#secName").val($("#seleBids option:selected").text());
                $("#sec_id").val($("#seleBids").val());
            },
            yes:function () {

            },
            cancel: function(index, layero){
                layer.close(layer.index);
                $("#addPlan input[name='plan_name']").val("");
                $("#addPlan input[name='report_id']").val("");
                $("textarea").val("");
                $("#coverId").val(0);
                $("#saveMonthPlan").text("保存");
            }
        });
    }else {
        layer.msg("请选择标段！")
    }
};
layui.use(['form', 'layedit', 'laydate'], function () {
    var form = layui.form;
    var laydate = layui.laydate;
    var layer = layui.layer;
    //月选择器
    laydate.render({
        elem: '#dateUpdate'
        ,format: 'd' //可任意组合
        ,value: new Date().getDate() //必须遵循format参数设定的格式
    });

    //月选择器
    laydate.render({
        elem: '#fillUpdate'
        ,format: 'd' //可任意组合
        ,value: new Date().getDate() //必须遵循format参数设定的格式
    });

});


//更新提醒ing
function updateRemind() {
    if($("#seleBids").val() != ''){
        var index = layer.open({
            title: $("#seleBids option:selected").text()+'标段-月进度填报提醒配置',
            id:'1004',
            type:'1',
            area:['760px','350px'],
            content:$('#updateRemindLayer'),
            success:function () {
                $("#secName").val($("#seleBids option:selected").text());
                $("#sec_id").val($("#seleBids").val());
            },
            yes:function () {

            },
            cancel: function(index, layero){
                layer.close(layer.index);
                $("#addPlan input[name='plan_name   ']").val("");
                $("#addPlan input[name='report_id']").val("");
                $("textarea").val("");
                $("#coverId").val(0);
                $("#saveMonthPlan").text("保存");
            }
        });
    }else {
        layer.msg("请选择标段！")
    }
};