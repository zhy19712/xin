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
                layui.use(['form', 'layedit', 'laydate'], function () {
                    form = layui.form;
                    var laydate = layui.laydate;
                    var layer = layui.layer;
                    form.on('select(aihao)', function(data){
                        console.log(data.value)
                        if(data.value == 1){
                            $("#modelList").css("display",'none');
                        }else if(data.value == 2){
                            $("#modelList").css("display",'block');
                        }
                    });
                });
                $("#secName").val($("#seleBids option:selected").text());
                $("#sec_id").val($("#seleBids").val());
            },
            yes:function () {

            },
            cancel: function(index, layero){
                layer.close(layer.index);
            }
        });
    }else {
        layer.msg("请选择标段！")
    }
});
/*关闭弹层*/
$('.close').click(function () {
    layer.closeAll('page');
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
    form.on('radio(aihao)', function(data){
        console.log(data.value)
        if(data.value == 1){
            $("#modelList").css("display",'none');
        }else if(data.value == 2){
            $("#modelList").css("display",'block');
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
                        console.log(res);
                        layer.msg(res.msg);
                        layer.closeAll('page');
                    }else  if (res.code == 2) {
                        $("#coverId").val(1);
                        $("#saveMonthPlan").text("确认覆盖");
                        console.log(res);
                        layer.msg(res.msg);
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
        content: '/progress/monthlyplan/list_table',
        // end:function () {
        // }
    });
}






/* 业务代码
-----------------------------------------------------------------------------*/

//1)自定义条形图外观显示
/*
project.on("drawitem", function (e) {
    var item = e.item;
    var left = e.itemBox.left,
        top = e.itemBox.top,
        width = e.itemBox.width,
        height = e.itemBox.height;

    if (!item.Summary && !item.Milestone) {
        var percentWidth = width * (item.PercentComplete / 100);

        e.itemHtml = '<div id="' + item._id + '" class="myitem" style="left:' + left + 'px;top:' + top + 'px;width:' + width + 'px;height:' + (height) + 'px;">';
        e.itemHtml += '<div style="width:' + (percentWidth) + 'px;" class="percentcomplete"></div>';

        //根据你自己逻辑，把任务分成几块，注意坐标和宽度
        // e.itemHtml += '<div style="position:absolute;left:0px;top:0;height:100%;width:20px;background:red;"></div>';

        e.itemHtml += '</div>';

        // e.ItemHtml = '<a href="http://www.baidu.com" style="left:'+left+'px;top:'+top+'px;width:'+width+'px;height:'+(height-2)+'px;" class="myitem">111</a>';
    }
});


project.on("drawcell", function (e) {
    var task = e.record, column = e.column, field = e.field;

    //新增
    if (task._state == "added") {
        e.rowCls = "row_added";
    }
    //删除
    if (task.Deleted == true) {
        e.rowCls = "row_deleted";
    }

});
//2)保存
function save() {
    var newTask = project.newTask();
    newTask.Name = '<新增任务>';    //初始化任务属性

    var selectedTask = project.getSelected();
    if (selectedTask) {
        project.addTask(newTask, "before", selectedTask);   //插入到到选中任务之前
        //project.addTask(newTask, "add", selectedTask);       //加入到选中任务之内
    } else {
        project.addTask(newTask);
    }
    //使用jQuery的ajax，把任务数据，发送到服务端进行处理
    //    $.ajax({
    //        url: 'savegant.aspx',
    //        type: "POST",
    //        data: params,
    //        success: function (text) {
    //            alert("保存成功");
    //        }
    //    });
}
//3)加载数据 点击重新加载数据
function load() {
    gantt.loading();
    $.ajax({
        url: "data/taskList.txt",
        cache: false,
        success: function (text) {
            var data = mini.decode(text);

            //列表转树形
            data = mini.arrayToTree(data, "children", "UID", "ParentTaskUID");

            gantt.loadTasks(data);

            gantt.unmask();
        }
    });
}
// load();
//4)更改顶部时间刻度
function changeTopTimeScale(value) {
    project.setTopTimeScale(value)
}
//5)更改低部时间刻度
function changeBottomTimeScale(value) {
    project.setBottomTimeScale(value)
}
//6)放大
function zoomIn() {
    project.zoomIn();
}
//7)缩小
function zoomOut() {
    project.zoomOut();
}
//8)添加
function addTask() {
    var newTask = project.newTask();
    newTask.Name = '<新增任务>';    //初始化任务属性

    var selectedTask = project.getSelected();
    if (selectedTask) {
        project.addTask(newTask, "before", selectedTask);   //插入到到选中任务之前
        //project.addTask(newTask, "add", selectedTask);       //加入到选中任务之内
    } else {
        project.addTask(newTask);
    }
}
//9)删除
function removeTask() {
    var task = project.getSelected();
    if (task) {
        if (confirm("确定删除任务 \"" + task.Name + "\" ？")) {
            project.removeTask(task);
        }
    } else {
        alert("请选中任务");
    }
}
//10)更新
function updateTask() {
    var selectedTask = project.getSelected();
    alert("编辑选中任务:" + selectedTask.Name);
}
//11)升级
function upgradeTask() {
    var task = project.getSelected();
    if (task) {
        project.upgradeTask(task);
    } else {
        alert("请选选中任务");
    }
}
//12)降级
function downgradeTask() {
    var task = project.getSelected();
    if (task) {
        project.downgradeTask(task);
    } else {
        alert("请选选中任务");
    }
}

//13)锁定列
function frozenColumn() {
    project.frozenColumn(0, 1);
}
//14)打印
function printGantt() {
    project.printServer = "__PUBLIC__/gantts/scripts/plusproject/services/snapshot/snapshot.aspx";
    project.printCSS = "__PUBLIC__/gantts/scripts/miniui/themes/default/miniui.css";
    project.print();
}*/