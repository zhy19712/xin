//常用window全局变量(检验批或者单元工程，默认为检验批)
window.InspectName = '检验批';

//消息弹框
var layerAlert = function (content) {
    top.layer.alert(content, {
        skin: 'layui-layer-lan',
        closeBtn: 0//,
        //shift: 4 //动画类型
    });


};

var layerLoading = function () {
    var index = top.layer.load(1, {
        shade: [0.1, '#fff'] //0.1透明度的白色背景
    });
}

var closeLoading = function () {
    var index = top.layer.load();
    top.layer.close(index);
}
//询问弹框
var layerConfirm = function (content, ok, param) {
    top.layer.confirm(content, {
        btn: ["确定", "取消"]
    }, function () {
        top.layer.closeAll('dialog');
        if (param)
            ok(param);
        else ok();
    }, function () {
    });
};

var defaultViewPoint = function () {
    $.ajax({
        type: "Get",
        url: "/PersonalSetting/GetPersonalViewPoint?_t=" + new Date().getTime(),
        success: function (data) {
            if (data.result) { //个人默认视角优先
                var points = data.result.split(",");
                RealBimOcx.LocateCamTo(points[0], points[1], points[2], points[3], points[4], points[5], points[6]);
            }
        }
    });
};

//$.ajaxSetup({
//    complete: function (xhr, status) {
//        alert(1);
//        alert(status);
//        var sessionStatus = xhr.getResponseHeader('sessionstatus');
//        if (sessionStatus == 'timeout') {
//            //var top = getTopWinow();
//            var yes = confirm('由于您长时间没有操作, session已过期, 请重新登录.');
//            if (yes) {
//                window.top.location.href = '/Login/Index';
//            }
//        }
//    }
//});

function imageSaveAs(imgURL) {
    var pagePop = window.open(imgURL, "", "width=1, height=1, top=5000, left=5000");
    for (; pagePop.document.readyState !== "complete";) {
        if (pagePop.document.readyState === "complete")
            break;
    }
    pagePop.document.execCommand("SaveAs");
    pagePop.close();
}

var viewPointSelect = function (selControlId, type) {
    var viewPointObj = "#" + selControlId;
    $.ajax({
        type: "Post",
        url: "/SystemViewPoint/GetAllViewPointDtos?type=" + type + "&_t=" + new Date(),
        dataType: "JSON",
        success: function (data) {
            $(viewPointObj).empty();
            //添加一个空的选项
            $(viewPointObj).append($("<option/>").text("请选择视角").attr("value", ""));
            $(data.result).each(function () {
                $(viewPointObj).append($("<option/>").text(this.name).attr("value", this.viewPoint));
            });
        }
    });
};
var majorSelect = function (selControlId, selectedId) {
    var majorObj = "#" + selControlId;
    $.ajax({
        type: "Post",
        url: "/Major/GetAllMajor",
        dataType: "JSON",
        success: function (data) {
            $(majorObj).empty();
            //添加一个空的选项
            $(majorObj).append($("<option/>").text("").attr("value", "00000000-0000-0000-0000-000000000000"));
            $(data.MajorDtos).each(function () {
                if (selectedId !== null) {
                    if (selectedId === this.Id) {
                        $(majorObj).append($("<option/>").text(this.name).attr("value", this.Id).attr("selected", true));
                    }
                    else {
                        $(majorObj).append($("<option/>").text(this.name).attr("value", this.Id));
                    }
                }
                else {
                    $(majorObj).append($("<option/>").text(this.name).attr("value", this.Id));
                }
            });
            var config = {
                '.chosen-select': {},
                '.chosen-select-deselect': {
                    allow_single_deselect: true
                },
                '.chosen-select-no-single': {
                    disable_search_threshold: 10
                },
                '.chosen-select-no-results': {
                    no_results_text: 'Oops, nothing found!'
                },
                '.chosen-select-width': {
                    width: "95%"
                }
            }
            for (var selector in config) {
                $(majorObj).chosen(config[selector]);
            }
            //$(majorObj).chosen();
        }
    });
};
///绑定标段数据
var sectionSelect = function (selControlId, selectedId, callback) {
    var sectionObj = "#" + selControlId;
    $.ajax({
        type: "Get",
        url: "/Section/List?_t=" + new Date(),
        dataType: "JSON",
        success: function (data) {
            $(sectionObj).empty();
            if (!selectedId && data.result.length > 0)
                selectedId = data.result[0].id;
            $(data.result).each(function () {
                if (selectedId !== null) {
                    if (selectedId === this.id) {
                        $(sectionObj).append($("<option/>").text(this.shortName).attr("value", this.id).attr("selected", true));
                    }
                    else {
                        $(sectionObj).append($("<option/>").text(this.shortName).attr("value", this.id));
                    }
                }
                else {
                    $(sectionObj).append($("<option/>").text(this.shortName).attr("value", this.id));
                }
            });
            var config = {
                '.chosen-select': {},
                '.chosen-select-deselect': {
                    allow_single_deselect: true
                },
                '.chosen-select-no-single': {
                    disable_search_threshold: 10
                },
                '.chosen-select-no-results': {
                    no_results_text: 'Oops, nothing found!'
                },
                '.chosen-select-width': {
                    width: "95%"
                }
            }
            for (var selector in config) {
                $(sectionObj).chosen(config[selector]);
            }
        }
    });
};
var sectionSelectNoCss = function (selControlId, selectedId) {
    var sectionObj = "#" + selControlId;
    $.ajax({
        type: "Get",
        url: "/Section/List?_t=" + new Date(),
        dataType: "JSON",
        success: function (data) {
            $(sectionObj).empty();
            $(data.result).each(function () {
                if (selectedId !== null) {
                    if (selectedId === this.id) {
                        $(sectionObj).append($("<option/>").text(this.shortName).attr("value", this.id).attr("selected", true));
                    }
                    else {
                        $(sectionObj).append($("<option/>").text(this.shortName).attr("value", this.id));
                    }
                }
                else {
                    $(sectionObj).append($("<option/>").text(this.shortName).attr("value", this.id));
                }
            });

        }
    });
};
var sectionAllSelectNoCss = function (selControlId, selectedId) {
    var sectionObj = "#" + selControlId;
    $.ajax({
        type: "Get",
        url: "/Section/List?_t=" + new Date(),
        dataType: "JSON",
        success: function (data) {
            $(sectionObj).empty();
            $(sectionObj).append($("<option/>").text("全部").attr("value", "").attr("selected", true));
            $(data.result).each(function () {
                if (selectedId !== null) {
                    if (selectedId === this.id) {
                        $(sectionObj).append($("<option/>").text(this.shortName).attr("value", this.id).attr("selected", true));
                    }
                    else {
                        $(sectionObj).append($("<option/>").text(this.shortName).attr("value", this.id));
                    }
                }
                else {
                    $(sectionObj).append($("<option/>").text(this.shortName).attr("value", this.id));
                }
            });

        }
    });
};
///绑定合同数据
var contractSelect = function (selControlId, selectedId) {
    var sectionObj = "#" + selControlId;
    $.ajax({
        type: "Get",
        url: "/Contract/Contract/GetAllContracts?_t=" + new Date(),
        dataType: "JSON",
        success: function (data) {
            $(sectionObj).empty();
            $(data.result).each(function () {
                if (selectedId !== null) {
                    if (selectedId === this.id) {
                        $(sectionObj).append($("<option/>").text(this.contractName).attr("value", this.id).attr("selected", true));
                    }
                    else {
                        $(sectionObj).append($("<option/>").text(this.contractName).attr("value", this.id));
                    }
                }
                else {
                    $(sectionObj).append($("<option/>").text(this.contractName).attr("value", this.id));
                }
            });
            var config = {
                '.chosen-select': {},
                '.chosen-select-deselect': {
                    allow_single_deselect: true
                },
                '.chosen-select-no-single': {
                    disable_search_threshold: 10
                },
                '.chosen-select-no-results': {
                    no_results_text: 'Oops, nothing found!'
                },
                '.chosen-select-width': {
                    width: "95%"
                }
            }
            for (var selector in config) {
                $(sectionObj).chosen(config[selector]);
            }
            //callback();

        }
    });
};
///绑定区域数据
var settingSelectById = function (selControlId, selectedId, callback) {
    var settingObj = "#" + selControlId;
    $.ajax({
        type: "Get",
        url: "/Standard/QualityTemplate/GetAllSetting?type=" + encodeURI("区域") + "&_t=" + new Date(),
        dataType: "JSON",
        success: function (data) {
            $(settingObj).empty();
            if (!selectedId && data.result.length > 0)
                selectedId = data.result[0].id;
            $(data.result).each(function () {
                if (selectedId !== null) {
                    if (selectedId === this.id) {
                        $(settingObj).append($("<option/>").text(this.key).attr("value", this.id).attr("selected", true));
                    }
                    else {
                        $(settingObj).append($("<option/>").text(this.key).attr("value", this.id));
                    }
                }
                else {
                    $(settingObj).append($("<option/>").text(this.key).attr("value", this.id));
                }
            });
            var config = {
                '.chosen-select': {},
                '.chosen-select-deselect': {
                    allow_single_deselect: true
                },
                '.chosen-select-no-single': {
                    disable_search_threshold: 10
                },
                '.chosen-select-no-results': {
                    no_results_text: 'Oops, nothing found!'
                },
                '.chosen-select-width': {
                    width: "95%"
                }
            }
            for (var selector in config) {
                $(settingObj).chosen(config[selector]);
            }
        }
    });
};
///绑定表格用途数据
var settingSelect = function (selControlId, selectedId, type) {
    var settingObj = "#" + selControlId;
    $.ajax({
        type: "Get",
        url: "/Standard/QualityTemplate/GetAllSetting?type=" + encodeURI(type) + "&_t=" + new Date(),
        dataType: "JSON",
        success: function (data) {
            $(settingObj).empty();
            if (!selectedId && data.result.length > 0)
                selectedId = data.result[0].id;
            $(data.result).each(function () {
                if (selectedId !== null) {
                    if (selectedId === this.id) {
                        $(settingObj).append($("<option/>").text(this.key).attr("value", this.key).attr("selected", true));
                    }
                    else {
                        $(settingObj).append($("<option/>").text(this.key).attr("value", this.key));
                    }
                }
                else {
                    $(settingObj).append($("<option/>").text(this.key).attr("value", this.key));
                }
            });
        }
    });
};
//selControlId: 要绑定下拉数据控件的值，
//selectedId:   已选择的值，这个在编辑的时候给控件赋值使用；
//IsNeed:       是否需要一个默认的空选项；需要的话传值1，不需要不传值，可空
var projectTypeSelect = function (selControlId, selectedId, IsNeed) {
    var typeObj = "#" + selControlId;
    $.ajax({
        type: "Post",
        url: "/Setting/ProjectType/GetProjectType",
        dataType: "JSON",
        success: function (data) {
            $(typeObj).empty();
            //添加一个空的选项
            if (IsNeed === 1) {
                $(typeObj).append($("<option/>").text("").attr("value", "00000000-0000-0000-0000-000000000000"));
            }
            $(data.ProjectTypes).each(function () {
                if (selectedId !== null) {
                    if (selectedId === this.Id) {
                        $(typeObj).append($("<option/>").text(this.Name).attr("value", this.Id).attr("selected", true));
                    }
                    else {
                        $(typeObj).append($("<option/>").text(this.Name).attr("value", this.Id));
                    }
                }
                else {
                    $(typeObj).append($("<option/>").text(this.Name).attr("value", this.Id));
                }
            });

        }
    });
};
//汉字转码
function utf16to8(str) {
    var out, i, len, c;
    out = "";
    len = str.length;
    for (i = 0; i < len; i++) {
        c = str.charCodeAt(i);
        if ((c >= 0x0001) && (c <= 0x007F)) {
            out += str.charAt(i);
        } else if (c > 0x07FF) {
            out += String.fromCharCode(0xE0 | ((c >> 12) & 0x0F));
            out += String.fromCharCode(0x80 | ((c >> 6) & 0x3F));
            out += String.fromCharCode(0x80 | ((c >> 0) & 0x3F));
        } else {
            out += String.fromCharCode(0xC0 | ((c >> 6) & 0x1F));
            out += String.fromCharCode(0x80 | ((c >> 0) & 0x3F));
        }
    }
    return out;
};

//js时间戳转日期格式,ns格式是：/Date(1293072805000)/
function getLocalTime(nS) {
    if (!nS) return new Date();
    var d = nS.replace(/[^0-9]+/g, '');//只取数值部分
    var dt = new Date(parseInt(d)).toLocaleString().replace(/:\d{1,2}$/, ' ');
    return dt;
};

function getFormatDate(jsondate) {
    jsondate = jsondate.replace("/Date(", "").replace(")/", "");
    if (jsondate.indexOf("+") > 0) {
        jsondate = jsondate.substring(0, jsondate.indexOf("+"));
    }
    else if (jsondate.indexOf("-") > 0) {
        jsondate = jsondate.substring(0, jsondate.indexOf("-"));
    }
    var date = new Date(parseInt(jsondate, 10));
    var month = date.getMonth() + 1 < 10 ? "0" + (date.getMonth() + 1) : date.getMonth() + 1;
    var currentDate = date.getDate() < 10 ? "0" + date.getDate() : date.getDate();
    var hours = date.getHours() < 10 ? "0" + date.getHours() : date.getHours();
    var minutes = date.getMinutes() < 10 ? "0" + date.getMinutes() : date.getMinutes();
    var second = date.getMilliseconds() / 1000 < 10 ? "0" + parseInt(date.getMilliseconds() / 1000) : parseInt(date.getMilliseconds() / 1000);
    return date.getFullYear() + "-" + month + "-" + currentDate + " " + hours + ":" + minutes + ":" + second;
}
//构件闪烁
function twinkle(componentId) {
    RealBimOcx.SetSubObjFlickerBegin(3, 600, 50, 50, 0xffff0000, 60, 50, 0);
    RealBimOcx.AddFlickerSubObjects(componentId);
    RealBimOcx.SetSubObjFlickerEnd();
};

//获取url request 参数
function GetRequest() {
    var url = location.search; //获取url中"?"符后的字串 
    var theRequest = new Object();
    if (url.indexOf("?") != -1) {
        var str = url.substr(1);
        strs = str.split("&");
        for (var i = 0; i < strs.length; i++) {
            theRequest[strs[i].split("=")[0]] = unescape(strs[i].split("=")[1]);
        }
    }
    return theRequest;
}

//预览文件图片
function preview(filePath, id, checkPower) {
    if (checkPower != "false") {
        $.ajax({
            type: "Get",
            url: "/PermissionRelation/CheckPower?menuId=" + $("#hidMenuId", window.parent.document).val() + "&id=" + id + "&type=preview&fileType=qualityUploadFile&_t=" + new Date().getTime(),
            async: false,
            success: function (data) {
                if (data.result == "Faild") {
                    layerAlert("没有授权权限");
                    return;
                }
            }
        });
    }
    var firstStr = filePath.substring(0, 1);
    if (firstStr != "/") {
        filePath = "/" + filePath;
    }
    if (filePath.indexOf(".pdf") > -1 || filePath.indexOf(".PDF") > -1)
    {

        var index = top.layer.open({
            type: 2,
            title: false,
            content: '/document/pdfshow?fileUrl=' + filePath,
            closeBtn: 0, //不显示关闭按钮
        });
        top.layer.full(index);
    }
    else if (filePath.indexOf(".xlsx") > -1 || filePath.indexOf(".xls") > -1 || filePath.indexOf(".doc") > -1 || filePath.indexOf(".docx") > -1 || filePath.indexOf(".txt") > -1)
    {
        $.ajax({
            type: "Get",
            url: "/PermissionRelation/PreView?url=" + filePath,
            success: function (data) {
                if (data.result == "Faild") {
                    layerAlert("你没有权限预览此文件");
                }
                else {
                    var index = top.layer.open({
                        type: 2,
                        title: '文件在线预览',
                        shadeClose: true,
                        shade: 0.8,
                        area: ['980px', '600px'],
                        content: '/document/DocumentPrint?url=' + data.result //iframe的url
                    });
                    top.layer.full(index);
                }
            }
        });
    }
    else {
        var index = top.layer.open({
            type: 2,
            title: false,
            content: '/document/picshow?imgUrl=' + filePath,
            closeBtn: 0, //不显示关闭按钮
        });
        top.layer.full(index);
    }

    //var imagePath = getHostNameWithPort() + "/" + filePath;
    //window.open(imagePath)
}
//下载文件图片
function saveFile(filePath, id, checkPower) {
    if (checkPower != "false") {
        $.ajax({
            type: "Get",
            url: "/PermissionRelation/CheckPower?menuId=" + $("#hidMenuId", window.parent.document).val() + "&id=" + id + "&type=download&fileType=qualityUploadFile&_t=" + new Date().getTime(),
            async: false,
            success: function (data) {
                if (data.result == "Faild") {
                    layerAlert("没有授权权限");
                    return;
                }
            }
        });
    }

    var imagePath = getHostNameWithPort() + "/" + filePath;
    window.win = open(imagePath);
    setTimeout('win.document.execCommand("SaveAs")', 500);
}
//导出二维码压缩包
function exportQRCode(type) {
    $.ajax({
        type: "Get",
        url: "/PermissionRelation/CheckPower?menuId=" + getMenuId() + "&type=down&_t=" + new Date().getTime(),
        async: false,
        success: function (data) {
            if (data.result == "Faild") {
                layerAlert("没有授权权限");
                return;
            }
            else {
                if (TreeNode != null) {
                    window.open('/MaterialTracking/Plan/ExportQRCodeZipFile?inspectId=' + TreeNode.Id + '&type=' + type, '_blank');
                } else {
                    layerAlert("请先选择要导出的划分节点！");
                }
            }
        }
    });

}

function getHostNameWithPort() {
    var url = location.protocol + "//" + location.hostname;
    if (location.port != "") {
        url = url + ":" + location.port;
    }

    return url;
}

//获取页面menuId的值
function getMenuId() {
    var menuId = $("#hidMenuId").val();
    if (menuId == null) {
        menuId = $("#hidMenuId", window.parent.document).val();
    }

    return menuId;
}

//字节转文件大小
function bytesToSize(bytes) {
    if (bytes === 0) return '0 B';
    var k = 1000, // or 1024
        sizes = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'],
        i = Math.floor(Math.log(bytes) / Math.log(k));

    return (bytes / Math.pow(k, i)).toPrecision(3) + ' ' + sizes[i];
}