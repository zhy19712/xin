var fileTargetHtmlElementId; // 定义附件上传后要更新的目标html元素Id
var saveStyle = 0; // 保存类型：0保存；1保存并提交；2保存并审批
$(function () {
    if ($("#formData").val() && $("#formData").val() != "false") {
        console.log( $("#formData").val()!= "false")
        var str = $("#formData").val().replace(/(\r\n|\n|\r)/gm, '☆');
        str = str.replace(/\s+/g, "");
        var formData = JSON.parse(str); // 换行符处理
        $.each(formData, function (i, item) {
            var elementId = item.Name;
            var elementType = elementId.split("_")[0];
            switch (elementType) {
                case "input":
                    $("#" + elementId).val(item.Value.replace(/☆/g, '\r\n')); // 换行符处理
                    break;
                case "img":
                    $("#" + elementId).attr("src", item.Value);
                    break;
                case "checkbox":
                    if (item.Value === "true")
                        $("#" + elementId).attr("checked", item.Value);
                    break;
                case "select":
                    $("#" + elementId + " Option").each(function () {
                        if ($(this).text() === item.Value)
                            $(this).attr("selected", true);
                    });
                    break;
                case "multiSelect":
                    if (elementId.indexOf("_chosen") > 0)
                        break;
                    $("#" + elementId).val(item.Value.split(","));
                    $("#" + elementId).trigger("chosen:updated");
                    break;
                case "span":
                    $("#" + elementId).text(item.Value);
                    break;

            }
        });
    } //从后台获取formdata数据
    var permissionByStepElements = $(".A4").find("[step]"); // 取所有设置有step属性的Html元素
    $.each(permissionByStepElements, function (i, item) {
        if ($(item).attr("step").indexOf($("#currentStep").val()) < 0) { // 如果设置的步骤不等于当前步骤 该html元素禁用
            $(item).attr("disabled", "disabled");
            var elementId = $(item).attr("id");
            if (typeof elementId !== "undefined" && elementId.indexOf("multiSelect") > -1) {
                $(item).prop("disabled", true);
                $(item).trigger("chosen:updated");
            }
        }
    });//设置按钮禁用功能的开启与闭合
    if ($("#isView").val() === "True")
        $(".hearBtn").hide();
    if (IsPC() == true) { //pc端的初始化页面
        initOperateButton();
        $('.data_2 .input-group.date').datepicker({
            format: 'yyyy年mm月dd日',
            todayBtn: "linked",
            keyboardNavigation: false,
            forceParse: false,
            //calendarWeeks: true,
            autoclose: true
        });
        //项目开始时间结束时间控制
        $('.data_2 .input-group .dateStart').datepicker({
            format: 'yyyy年mm月dd日',
            todayBtn: "linked",
            keyboardNavigation: false,
            forceParse: false,
            //calendarWeeks: true,
            autoclose: true
        }).on('changeDate',function(){
            var startTime= $(".data_2 .input-group .dateStart").val();
            $(".data_2 .input-group .dateEnd").datepicker('setStartDate',startTime);
            $(".data_2 .input-group .dateStart").datepicker('hide');
        });

        $('.data_2 .input-group .dateEnd').datepicker({
            format: 'yyyy年mm月dd日',
            todayBtn: "linked",
            keyboardNavigation: false,
            forceParse: false,
            //calendarWeeks: true,
            autoclose: true
        }).on('changeDate',function(){
            var startTime = $(".data_2 .input-group .dateStart").val();
            var endtime = $(".data_2 .input-group .dateEnd").val();
            $(".data_2 .input-group .dateStart").datepicker('setEndDate',endtime);
            $(".data_2 .input-group .dateEnd").datepicker('hide');
        });
        $('.data_2 .input-group .dateStart2').datepicker({
            format: 'yyyy年mm月dd日',
            todayBtn: "linked",
            keyboardNavigation: false,
            forceParse: false,
            //calendarWeeks: true,
            autoclose: true
        }).on('changeDate',function(){
            var startTime= $(".data_2 .input-group .dateStart").val();
            $(".data_2 .input-group .dateEnd2").datepicker('setStartDate',startTime);
            $(".data_2 .input-group .dateStart2").datepicker('hide');
        });

        $('.data_2 .input-group .dateEnd2').datepicker({
            format: 'yyyy年mm月dd日',
            todayBtn: "linked",
            keyboardNavigation: false,
            forceParse: false,
            //calendarWeeks: true,
            autoclose: true
        }).on('changeDate',function(){
            var startTime = $(".data_2 .input-group .dateStart2").val();
            var endtime = $(".data_2 .input-group .dateEnd2").val();
            $(".data_2 .input-group .dateStart2").datepicker('setEndDate',endtime);
            $(".data_2 .input-group .dateEnd2").datepicker('hide');
        });
        $('.data_2 .input-group .dateStart3').datepicker({
            format: 'yyyy年mm月dd日',
            todayBtn: "linked",
            keyboardNavigation: false,
            forceParse: false,
            //calendarWeeks: true,
            autoclose: true
        }).on('changeDate',function(){
            var startTime= $(".data_2 .input-group .dateStart3").val();
            $(".data_2 .input-group .dateEnd3").datepicker('setStartDate',startTime);
            $(".data_2 .input-group .dateStart3").datepicker('hide');
        });

        $('.data_2 .input-group .dateEnd3').datepicker({
            format: 'yyyy年mm月dd日',
            todayBtn: "linked",
            keyboardNavigation: false,
            forceParse: false,
            //calendarWeeks: true,
            autoclose: true
        }).on('changeDate',function(){
            var startTime = $(".data_2 .input-group .dateStart3").val();
            var endtime = $(".data_2 .input-group .dateEnd3").val();
            $(".data_2 .input-group .dateStart3").datepicker('setEndDate',endtime);
            $(".data_2 .input-group .dateEnd3").datepicker('hide');
        });
        $('.data_2 .input-group .dateStart4').datepicker({
            format: 'yyyy年mm月dd日',
            todayBtn: "linked",
            keyboardNavigation: false,
            forceParse: false,
            //calendarWeeks: true,
            autoclose: true
        }).on('changeDate',function(){
            var startTime= $(".data_2 .input-group .dateStart4").val();
            $(".data_2 .input-group .dateEnd4").datepicker('setStartDate',startTime);
            $(".data_2 .input-group .dateStart4").datepicker('hide');
        });

        $('.data_2 .input-group .dateEnd4').datepicker({
            format: 'yyyy年mm月dd日',
            todayBtn: "linked",
            keyboardNavigation: false,
            forceParse: false,
            //calendarWeeks: true,
            autoclose: true
        }).on('changeDate',function(){
            var startTime = $(".data_2 .input-group .dateStart4").val();
            var endtime = $(".data_2 .input-group .dateEnd4").val();
            $(".data_2 .input-group .dateStart4").datepicker('setEndDate',endtime);
            $(".data_2 .input-group .dateEnd4").datepicker('hide');
        });
        $('.data_2 .input-group .dateStart5').datepicker({
            format: 'yyyy年mm月dd日',
            todayBtn: "linked",
            keyboardNavigation: false,
            forceParse: false,
            //calendarWeeks: true,
            autoclose: true
        }).on('changeDate',function(){
            var startTime= $(".data_2 .input-group .dateStart5").val();
            $(".data_2 .input-group .dateEnd5").datepicker('setStartDate',startTime);
            $(".data_2 .input-group .dateStart5").datepicker('hide');
        });

        $('.data_2 .input-group .dateEnd5').datepicker({
            format: 'yyyy年mm月dd日',
            todayBtn: "linked",
            keyboardNavigation: false,
            forceParse: false,
            //calendarWeeks: true,
            autoclose: true
        }).on('changeDate',function(){
            var startTime = $(".data_2 .input-group .dateStart5").val();
            var endtime = $(".data_2 .input-group .dateEnd5").val();
            $(".data_2 .input-group .dateStart5").datepicker('setEndDate',endtime);
            $(".data_2 .input-group .dateEnd5").datepicker('hide');
        });
        $('.data_2 .input-group .dateStart6').datepicker({
            format: 'yyyy年mm月dd日',
            todayBtn: "linked",
            keyboardNavigation: false,
            forceParse: false,
            //calendarWeeks: true,
            autoclose: true
        }).on('changeDate',function(){
            var startTime= $(".data_2 .input-group .dateStart6").val();
            $(".data_2 .input-group .dateEnd6").datepicker('setStartDate',startTime);
            $(".data_2 .input-group .dateStart6").datepicker('hide');
        });

        $('.data_2 .input-group .dateEnd6').datepicker({
            format: 'yyyy年mm月dd日',
            todayBtn: "linked",
            keyboardNavigation: false,
            forceParse: false,
            //calendarWeeks: true,
            autoclose: true
        }).on('changeDate',function(){
            var startTime = $(".data_2 .input-group .dateStart6").val();
            var endtime = $(".data_2 .input-group .dateEnd6").val();
            $(".data_2 .input-group .dateStart6").datepicker('setEndDate',endtime);
            $(".data_2 .input-group .dateEnd6").datepicker('hide');
        });
    } else {
        $(".hearBtn").hide();
        var head = document.getElementsByTagName('head')[0];
        var script = document.createElement("script");
        script.type = "text/javascript";
        script.src = "/Areas/Quality/Views/QualityForm/mui.min.js";
        head.appendChild(script);

        window.addEventListener('onLineFormSave', function (e) {
            formSaveData();//移动端保存表单
        })
        $('.data_2 .form-control').on('click', function () {
            var $nowInput = $(this);
            $nowInput.attr("readOnly", "true");//不弹出软键盘
            var dDate = new Date();
            plus.nativeUI.pickDate(function (e) {
                var d = e.date;
                $nowInput.val(d.getFullYear() + "年" + (d.getMonth() + 1) + "月" + d.getDate() + "日");
            }, {
                title: "请选择日期",
                date: dDate,
            });
            $nowInput.removeAttr("readOnly");
        })
    }
});

// 初始化操作按钮
function initOperateButton() {
    var saveAndSubmitButtonDom = "<button class='btn btn-success btns' type='button' onclick='formSaveAndSubmit()'><i class='fa fa-check-square-o' ></i > <span class='bold'>保存并提交</span></button >";
    var saveAndApproveButtionDom = "<button class='btn btn-success btns' type='button' onclick='formSaveAndApprove()'><i class='fa fa-pencil-square-o' ></i > <span class='bold'>保存并处理</span></button >";
    if ($("#currentStep").val() === "0")
        // $(".hearBtn").append(saveAndSubmitButtonDom);
        $(".hearBtn").append(saveAndApproveButtionDom);
    else
        $(".hearBtn").append(saveAndApproveButtionDom);
};

//检测设备：true为PC端，false为手机端
function IsPC() {
    var userAgentInfo = navigator.userAgent;
    var Agents = ["Android", "iPhone",
        "SymbianOS", "Windows Phone",
        "iPad", "iPod"];
    var flag = true;
    for (var v = 0; v < Agents.length; v++) {
        if (userAgentInfo.indexOf(Agents[v]) > 0) {
            flag = false;
            break;
        }
    }
    return flag;
}

// 表单数据保存
function formSaveData() {
    // var canSave = true;
    // if (typeof beforeSave === "function") {
    //     canSave = beforeSave();
    // }
    // ;
    // console.log(canSave)
    // if (!canSave) {
    //     saveStyle === 0;
    //     return;
    // }
    var formData = [];
    var htmlElements = $(".A4").find("[id]");
    $.each(htmlElements, function (i, item) {
        var elementId = $(item).attr("id");
        var elementType = elementId.split("_")[0];
        var elementValue = "";
        var step = 0;
        var required = "";
        switch (elementType) {
            case "input":
                elementValue = $("#" + elementId).val();
                if (!elementValue && $("#" + elementId)[0].hasAttribute("default")) {
                    if ($("#" + elementId)[0].hasAttribute("step")) { // 有步骤属性，则必须是在当前步骤时才取默认值
                        var stepStrs = $("#" + elementId).attr("step");
                        var inputStep = stepStrs.split(',').pop();
                        if (inputStep === $("#currentStep").val()) {
                            elementValue = $("#" + elementId).attr("default");
                        }
                    } else {
                        elementValue = $("#" + elementId).attr("default");
                    }

                }
                break;
            case "img":
                elementValue = $("#" + elementId).attr("src");
                break;
            case "checkbox":
                elementValue = $("#" + elementId).is(':checked');
                break;
            case "select":
                elementValue = $("#" + elementId + " option:selected").text();
                break;
            case "multiSelect":
                elementValue = $("#" + elementId).val().toString();
                break;
            case "span":
                elementValue = $("#" + elementId).text();
                break;
        }
        if ($("#" + elementId)[0].hasAttribute("step"))
            step = $("#" + elementId).attr("step");
        if ($("#" + elementId)[0].hasAttribute("form-required")) // 包含表单必填属性
            required = $("#" + elementId).attr("form-required");

        formData.push({
            "Name": elementId,
            "Value": elementValue,
            "Required": required,
            "Step": step
        });
    });
    var formInfo = {
        "Id": $("#id").val(),
        "TemplateId": $("#templateId").val(),
        "IsInspect": $("#isInspect").val(),
        "DivisionId": $("#divisionId").val(),
        "ProcedureId": $("#procedureId").val(),
        "ControlPointId": $("#controlPointId").val(),
        "FormName": $("#formName").val(),
        "QualityFormDatas": formData
    }
    $.ajax({
        type: "Post",
        url: "/Quality/QualityForm/InsertOrUpdate",
        data: {
            "dto": formInfo
        },
        success: function (data) {
            if (data.result === "Faild") {
                layer.confirm('保存失败。', function(index){
                    layer.close(index);
                });
            }
            else if(data.result === "Refund") {
                layer.confirm('已经有对应文件。', function(index){
                    layer.close(index);
                });
            }
            else {
                $("#id").val(data.result);
                console.log(saveStyle)
                if (saveStyle === 0)
                    parent.layer.closeAll();
                else
                    handleAfterSave(data.result);
                // 刷新父页面列表
                // if (parent.window.frames["web"].document.frames["webas"])
                //     parent.window.frames["web"].document.frames["webas"].loadQualityFormInfoData();
                // else
                //     parent.window.frames["web"].refresh();
            }
            ;
        }
    });
};

function formSave() {
    saveStyle = 0;
    formSaveData();
}

// 保存并提交
function formSaveAndSubmit() {
    saveStyle = 1;
    formSaveData();
};

// 保存并审批
function formSaveAndApprove() {
    saveStyle = 2;
    formSaveData();
};

// 保存成功后处理逻辑
function handleAfterSave(dataId) {
    if (IsPC() == true) {
        if (saveStyle === 1) {
            parent.submitOnLine(dataId);
        } else {
            var user = parent.$("#current_user").text().trim().replace("欢迎, ", "");
            parent.approve(dataId, user, $("#currentStep").val());
        }
    }else{
        var user = parent.$("#current_user").text().trim().replace("欢迎, ", "");
        approve(dataId, user, $("#currentStep").val());
    }

};
// 选择文件按钮事件
// htmlElement参数为当前选择文件按钮dom对象
function fileSelect(htmlElement) {
    fileTargetHtmlElementId = $(htmlElement).attr("for"); // 取到for属性的值 （附件上传后要更新的目标html元素Id）
    $("#file").click();
};

// 文件选择完毕后触发
// 首先进行选择的文件上传操作
// 文件上传完成后，根据“附件上传后要更新的目标html元素Id”的类型，进行操作，如img就设置目标元素的src进行图片显示
function fileChange() {
    $.ajaxFileUpload({
        url: "/admin/common/autographUpload?use=qualityform", // 用于文件上传的服务器端请求地址,其folderName代表附件存放的文件夹名称
        type: "post",
        secureuri: false, // 一般设置为false
        fileElementId: "file", // 文件上传控件的id属性
        dataType: "xml",
        data:{
            uploadType:1
        },
        processData:false,
        success: function (data, status) {
            var res = data.documentElement.innerText;
            var reg = res.replace(/\\/g, "\\\\");
            var dataObj = $.parseJSON(reg);
            console.log(dataObj);
            // if (fileTargetHtmlElementId.split("_")[0] === "img")
            // data = (new Function("return " + data))();
            $("#" + fileTargetHtmlElementId).attr("src", dataObj.src);
        },
        error: function (data, status, e) {
            console.log(data);
            console.log(e);
            /*var reg = /<pre.+?>(.+)<\/pre>/g;
            var result = data.match(reg);
            data = RegExp.$1;
            console.log(data)
            if (data.code == 2)
                $("#" + fileTargetHtmlElementId).attr("src", data.src);*/
            layer.confirm('上传失败。', function(index){
                layer.close(index);
            });
        }
    });
};

//附件资料上传点击
// function fileChange(){
//     var uploadName;
//     layui.use(['element', "layer", 'form', 'upload'], function () {
//         var $ = layui.jquery
//             , element = layui.element
//             , upload = layui.upload;
//
//         var form = layui.form
//             , layer = layui.layer
//             , layedit = layui.layedit
//             , laydate = layui.laydate;
//     upload.render({
//         elem: '#file',// 文件上传控件的id属性
//         url: "/admin/common/upload?module=quality&use=element",
//         accept:"file",
//         before: function(obj){
//             console.log(obj)
//             obj.preview(function(index, file, result){
//                 uploadName = file.name;
//                 console.log(file.name);//获取文件名，result就是base64
//             })
//             return false;
//         },
//         done: function(res){
//             if (res.code == 2)
//             $("#" + fileTargetHtmlElementId).attr("src", res.src);
//         },
//     });
//     });
// }


// 电子签名
function signature(htmlElement) {
    var targetId = $(htmlElement).attr("for");
    var userSignature = $(top.document).find("#signatures").val();
    if (userSignature)
        $("#" + targetId).attr("src", userSignature);
    else {
        $.ajax({
            type: "Get",
            url: "/Quality/QualityForm/GetCurrentUserSignature?id=" + $("#id").val(),
            success: function (data) {
                userSignature = data;
                if (userSignature)
                    $("#" + targetId).attr("src", userSignature);
                else
                    layer.confirm('请先上传个人签名。', function(index){
                        layer.close(index);
                    });
            }
        });
    }
};
// 表单附件
function formAttachments() {
    var cpr = $("#cpr").val();
    top.layer.open({
        type: 2,
        title: "表单附件",
        area: ['800px', '400px'],
        content: '/Quality/QualityForm/QalityFormAttachment?cpr_id=' + cpr,
    });
};
//二维码
function getQrcode() {
    var link = $("#getQrcode").val();
    $('#qrcode').qrcode({
        render: "canvas", //也可以替换为table
        width: 90,
        height: 90,
        text: link
    });
}

//手机端-在线填报-流程审批
function approve(id,app,step) {
    $.ajax({
        url: "/approve/Approve/CheckBeforeSubmitOrApprove",
        type: "post",
        data: {
            dataId:id,
            dataType:"app\\quality\\model\\QualityFormInfoModel",
            currentStep:step
        },
        success: function (res) {
            console.log(res);
            if(res == ""){
                layer.open({
                    type: 2,
                    title: '流程处理',
                    shadeClose: true,
                    area: ['980px', '90%'],
                    content: '/approve/approve/Approve?dataId='+ id + '&dataType=app\\quality\\model\\QualityFormInfoModel',
                    success: function(layero, index){
                        var body = layer.getChildFrame('body', index);
                        body.find("#conCode").val(app);
                        body.find("#dataId").val(id);
                        body.find("#dataType").val('app\\quality\\model\\QualityFormInfoModel');
                    },
                    end:function () {
                        funOnLine(nodeUnitId,procedureId,controlRowId);
                        onlineFill.ajax.url("/quality/common/datatablesPre?tableName=quality_form_info&DivisionId="+nodeUnitId+"&ProcedureId="+procedureId+"&cpr_id="+controlRowId).load();
                    }
                });
            }else if(res != ''){
                layer.alert(res);
            }
        },
        error:function () {
            alert("获取数据完整性检测异常")
        }
    });
    console.log(id);
}