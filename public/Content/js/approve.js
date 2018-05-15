// 流程审批----提交
// dataId     业务数据Id
// dataType   业务数据类型，要传当前业务数据表的表名
// referFlow  参考流程
function submit(dataId, dataType, referFlow, currentStep) {
    if (!currentStep)
        currentStep = 0;
    $.ajax({
        type: "Get",
        url: "/Approve/CheckBeforeSubmitOrApprove?dataId=" + dataId + "&dataType=" + dataType + "&currentStep=" + currentStep + "&_t=" + new Date().getTime(),
        success: function (data) {
            if (data.result) {
                alert(data.result);
                return;
            }

            top.layer.open({
                type: 2,
                title: '提交审批',
                shadeClose: true,
                shade: 0.8,
                area: ['900px', '600px'],
                content: '/Approve/Submit?dataId=' + dataId + '&dataType=' + dataType + '&referFlow=' + referFlow //iframe的url 
            });

        }
    });
};

// 流程审批----审批
function approve(dataId, dataType, currentStep) {
    if (!currentStep)
        currentStep = 0;
    top.layer.open({
        type: 2,
        title: '流程审批',
        shadeClose: true,
        shade: 0.8,
        area: ['900px', '550px'],
        content: '/Approve/Approve?dataId=' + dataId + '&dataType=' + dataType + '&currentStep=' + currentStep//iframe的url
    });
};

// 流程审批----审批历史
function history(dataId, dataType) {
    top.layer.open({
        type: 2,
        title: '审批历史',
        shadeClose: true,
        shade: 0.8,
        area: ['900px', '550px'],
        content: '/Approve/History?dataId=' + dataId + '&dataType=' + dataType //iframe的url
    });
};

// 流程审批----直接归档
function archive(selectedRow, dataType) {
    if (!selectedRow) {
        layerAlert("请选择要归档的文档。");
        return;
    }
    if (selectedRow.ApproveStatus === 1 || selectedRow.ApproveStatus === 2) {
        layerAlert("不符合归档条件。");
        return;
    }
    $.ajax({
        type: "post",
        url: "/Approve/Archive",
        data: { "dataId": selectedRow.Id, "dataType": dataType },
        success: function (data) {
            if (data.result === "Success") {
                refresh();//刷新列表数据
                close();
            }
            else {
                layerAlert("提交失败。");
            };
        }
    });
};

// 签名
function signature() {
    layer.prompt({ title: '密码二次认证', formType: 1 }, function (pass, index) {
        $.ajax({
            type: "Get",
            url: "/Organization/PasswordValidate?password=" + pass + "&_t=" + new Date().getTime(),
            success: function (data) {
                if (data.result.indexOf("/") > -1) {
                    var signatureImg = $(top.document).find("#signatures").val();
                    $("#imgSignature").attr("src", signatureImg);
                    layer.close(index);
                }
                else {
                    layerAlert(data.result);
                }
            }
        });
    });
};

