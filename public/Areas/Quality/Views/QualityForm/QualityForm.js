var fileTargetHtmlElementId; // 定义附件上传后要更新的目标html元素Id
var saveStyle = 0; // 保存类型：0保存；1保存并提交；2保存并审批
$(function () {
    if ($("#formData").val()) {
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
    }
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
    });
    if ($("#isView").val() === "True")
        $(".hearBtn").hide();
    if (IsPC() == true) {
        initOperateButton();
        $('.data_2 .input-group.date').datepicker({
            format: 'yyyy年mm月dd日',
            todayBtn: "linked",
            keyboardNavigation: false,
            forceParse: false,
            //calendarWeeks: true,
            autoclose: true
        });
    } else {
        $(".hearBtn").hide();
        var head = document.getElementsByTagName('head')[0];
        var script = document.createElement("script");
        script.type = "text/javascript";
        script.src = "/Areas/Quality/Views/QualityForm/mui.min.js";
        head.appendChild(script);

        window.addEventListener('onLineFormSave', function (e) {
            formSave();//移动端保存表单
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
    var saveAndApproveButtionDom = "<button class='btn btn-success btns' type='button' onclick='formSaveAndApprove()'><i class='fa fa-pencil-square-o' ></i > <span class='bold'>保存并审批</span></button >";
    if ($("#currentStep").val() === "0")
        $(".hearBtn").append(saveAndSubmitButtonDom);
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
function formSave() {
    var canSave = true;
    if (typeof beforeSave === "function") {
        canSave = beforeSave();
    }
    ;
    if (!canSave) {
        saveStyle === 0;
        return;
    }
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
                alert("保存失败。");
            } else {
                $("#id").val(data.result);
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

// 保存并提交
function formSaveAndSubmit() {
    saveStyle = 1;
    formSave();
};

// 保存并审批
function formSaveAndApprove() {
    saveStyle = 2;
    formSave();
};

// 保存成功后处理逻辑
function handleAfterSave(dataId) {
    if (saveStyle === 1) {
        parent.submitOnLine(dataId);
    } else {
        var user = parent.$("#current_user").text().trim().replace("欢迎, ", "");
        parent.approve(dataId, user, $("#currentStep").val());
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
        url: "/admin/common/upload?use=qualityform", // 用于文件上传的服务器端请求地址,其folderName代表附件存放的文件夹名称
        type: "post",
        secureuri: false, // 一般设置为false
        fileElementId: "file", // 文件上传控件的id属性
        dataType: "json",
        success: function (data, status) {
            // if (fileTargetHtmlElementId.split("_")[0] === "img")
            if (data.code == 2)
                $("#" + fileTargetHtmlElementId).attr("src", data.src);
        },
        error: function (data, status, e) {
            alert("上传失败。");
        }
    });
};

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
                    alert("请先上传个人签名。");
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