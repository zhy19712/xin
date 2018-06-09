uObjSubIdSingle = '';   //模型ID
modelTrans = '';        //透明度
modelColor = '';        //选择集颜色
choiceness_pigment = '';       //优良色值
qualified_pigment = '';      //合格色值
un_evaluation_pigment = '';  //未验评色值
modeGroupIds = '';  //模型组ID
controlpoint_id = ''    //控制点Id
currentStep = ''; //审批步骤
selectedModeGroupIds = '' //选中的模型组ID 用于显示隐藏按钮操作
selectedTreeNode = '';  //点击模型获取关联的工程划分节点

//获取模型配置的色值
//TODO get请求
$.ajax({
    url: "/modelmanagement/qualitymass/configureInfo",
    type: "get",
    dataType: "json",
    success: function (res) {
        modelTrans = res.configureInfo.quality.pellucidity;
        modelColor = +res.configureInfo.quality.pigment;
        choiceness_pigment = +res.configureInfo.quality.choiceness_pigment;
        qualified_pigment = +res.configureInfo.quality.qualified_pigment;
        un_evaluation_pigment = +res.configureInfo.quality.un_evaluation_pigment;
        var excellent = res.configureInfo.quality.choiceness_pigment.substr(4, 6);
        var qualified = res.configureInfo.quality.qualified_pigment.substr(4, 6);
        var unQualified = res.configureInfo.quality.un_evaluation_pigment.substr(4, 6);
        $('#excellent').css('background', '#' + excellent);
        $('#qualified').css('background', '#' + qualified);
        $('#unQualified').css('background', '#529df8');
        $('#unReview').css('background', '#666');
    }
});

//合格率
//TODO get请求
$.ajax({
    url: "/modelmanagement/qualitymass/examineFruit",
    type: "get",
    dataType: "json",
    success: function (res) {
        $('#excellent_number').text(res.data.excellent);
        $('#excellent_rate').text(res.data.excellent_percent + '%');
        $('#qualified_number').text(res.data.qualified);
        $('#qualified_rate').text(res.data.qualified_percent + '%');
        $('#unchecked_number').text(res.data.unqualified);
        $('#unchecked_rate').text(res.data.unqualified_percent + '%');
    }
});

//折叠面板
function easyUiPanelToggle() {
    var number = $("#easyuiLayout").layout("panel", "east")[0].clientWidth;
    if (number <= 0) {
        $('#easyuiLayout').layout('expand', 'east');
    }
}

//初始化手风琴
layui.use('element', function () {
    var element = layui.element;
});

//初始化标段
//TODO get请求
$.ajax({
    url: "/modelmanagement/qualitymass/section",
    type: "get",
    dataType: "json",
    success: function (res) {
        var data = res.data;
        var options = [];
        options.push('<option value="-1">全部标段</option>');
        for (var n in data) {
            options.push('<option value="' + n + '">' + data[n] + '</option>');
        }
        $('#section').append(options.join(''));
        layui.use('form', function () {
            var form = layui.form;
            form.render('select');
        });
    }
});
//标段切换
layui.use('form', function () {
    var form = layui.form;
    form.on('select(section)', function (data) {
        var val = data.value;
        //加载树
        $.ajax({
            url: "./index",
            type: "post",
            data: {
                section_id: val
            },
            dataType: "json",
            success: function (res) {
                ztree('', val);
            }
        });
        if (val == -1) {
            allModel();
        } else {
            //加载模型
            //TODO get请求
            $.ajax({
                url: "/modelmanagement/qualitymass/sectionModel",
                type: "get",
                data: {
                    section_id: val
                },
                dataType: "json",
                success: function (res) {
                    selectedSectionShowModel(res);
                }
            });
        }
    });
});

//工程划分
function ztree(node_type, section_id) {
    var setting = {
        async: {
            enable: true,
            autoParam: ["pid", "tid"],
            type: "get",
            url: "/modelmanagement/qualitymass/index?node_type=" + node_type + '&section_id=' + section_id,
            dataType: "json"
        },
        data: {
            simpleData: {
                enable: true,
                idKey: "id",
                pIdKey: "pId"
            }
        },
        check: {
            enable: true
        },
        callback: {
            onClick: zTreeOnClick,
            onCheck: zTreeOnCheck
        },
        showLine: true,
        showTitle: true,
        showIcon: false
    };
    zTreeObj = $.fn.zTree.init($("#ztree"), setting, null);
}

ztree(0, '');

//点击节点
function zTreeOnClick(event, treeId, treeNode) {
    console.log(treeNode);
    nodeId = treeNode.add_id;
    node_type = treeNode.node_type;
    selectedTreeNode = treeNode;
    modeGroupIds = nodeModelNumber();
    if (treeNode.level == 5) {
        acceptance(nodeId,1);   //验收资料-工序、控制点
        modelInfo(nodeId,1);    //单元工程信息
        window.operateModel(modeGroupIds);  //模型选择集处理事件
    }
}

//显示隐藏模板事件
function zTreeOnCheck(event, treeId, treeNode) {
    nodeId = treeNode.add_id;
    node_type = treeNode.node_type;
    $.ajax({
        url: "/modelmanagement/qualitymass/concealment",
        type: "post",
        data: {
            add_id: nodeId,
            node_type: node_type
        },
        dataType: "json",
        success: function (res) {
            var checked = treeNode.checked;
            if (checked) {
                window.hideModel(res.data);
            } else {
                window.showModel(res.data);
            }
        }
    });
    /*modeGroupIds = nodeModelNumber();
    var checked = treeNode.checked;
    if(checked){
        //隐藏关联构件
        window.hideModel(modeGroupIds);
    }else {
        window.showModel(modeGroupIds);
    }*/
}

//模板组ID
function nodeModelNumber() {
    var result;
    $.ajax({
        url: "/modelmanagement/Qualitymass/nodeModelNumber",
        type: "post",
        async: false,
        data: {
            number: nodeId,
            number_type: 1
        },
        dataType: "json",
        success: function (res) {
            result = res.data;
        }
    });
    return result;
}

//验收资料-工序、控制点
acceptance = function (number,number_type) {
    $.ajax({
        url: "/modelmanagement/Manage/getAcceptance",
        type: "post",
        data: {
            number: number,
            number_type: number_type
        },
        dataType: "json",
        success: function (res) {
            $('#acceptanceData').empty();
            var data = res.processinfo;
            var acceptanceDataTemp = [];
            var count = 0;
            for (var i = 0; i < data.length; i++) {
                acceptanceDataTemp.push('<div class="layui-colla-item">');
                acceptanceDataTemp.push('<h2 class="layui-colla-title layui-work-title workTitle">工序' + (count++) + '：' + data[i].name + '</h2>');
                for (var j = 0; j < data[i].controlpoint_info.length; j++) {
                    var controlData = data[i].controlpoint_info[j];
                    acceptanceDataTemp.push('<div class="layui-colla-content layui-work-content">');
                    acceptanceDataTemp.push('<div class="layui-collapse layui-control-data" id="controlData" lay-filter="control" lay-accordion>');
                    acceptanceDataTemp.push('<h2 class="layui-colla-title layui-control-title controlTitle" unit_id=' + data[i].unit_id + ' id=' + controlData.id + ' procedureid=' + controlData.procedureid + '>' + controlData.name + '</h2>');
                    acceptanceDataTemp.push('<div class="layui-colla-content layui-control-content">');
                    acceptanceDataTemp.push('<div class="layui-table-title tableTitle"></div>');
                    acceptanceDataTemp.push('<table class="layui-table" uid=' + controlData.id + ' id="online' + controlData.id + '">');
                    acceptanceDataTemp.push('<tbody>');
                    acceptanceDataTemp.push('</tbody>');
                    acceptanceDataTemp.push('</table>');
                    acceptanceDataTemp.push('</div>');
                    acceptanceDataTemp.push('</div>');
                    acceptanceDataTemp.push('</div>');
                }
                acceptanceDataTemp.push('</div>');
            }
            $('#acceptanceData').append(acceptanceDataTemp.join(''));
            layui.use('element', function () {
                var element = layui.element;
                element.render('collapse');
            });
            $('.layui-colla-item:first-child').find('h2.workTitle').click();
        }
    });
}

//验收资料-在线填报、扫描上传、附件资料
layui.use('element', function () {
    var element = layui.element;
    element.on('collapse(control)', function (data) {
        if (data.show) {
            var id = $(data.title).attr('id');      //控制点ID
            var procedureid = $(data.title).attr('procedureid');
            var unit_id = $(data.title).attr('unit_id');
            controlpoint_id = id;
            $.ajax({
                url: "/modelmanagement/Manage/getLineReport",
                type: "post",
                data: {
                    id: id,
                    procedureid: procedureid,
                    unit_id: unit_id
                },
                dataType: "json",
                success: function (res) {
                    //在线填报
                    var tbody = [];
                    var admin_id = res.admin_id;    //当前登录用户ID
                    tbody.push('<tr><th class="table-title" colspan="4">在线填报</th></tr>');
                    tbody.push('<tr>');
                    tbody.push('<th><span class="filename">填报人</span></th>');
                    tbody.push('<th>填报日期</th>');
                    tbody.push('<th>审批状态</th>');
                    tbody.push('<th>操作</th>');
                    tbody.push('</tr>');
                    $('table[uid=' + id + '] tbody').empty();
                    if (res.form_info == '') {
                        tbody.push('<tr>');
                        tbody.push('<td class="td-empty" colspan="4">');
                        tbody.push('无在线填报数据');
                        tbody.push('</td>');
                        tbody.push('</tr>');
                    }
                    for (var i = 0; i < res.form_info.length; i++) {
                        var data = res.form_info[i];
                        var onLineTableId = data.id;
                        var user_id = data.user_id;     //填报人ID
                        var nickname = data.nickname;    //填报人名字
                        var currentApproverId = data.CurrentApproverId;    //审批人ID
                        var approveStatus = data.ApproveStatus;    //审批状态
                        currentStep = data.CurrentStep; //审批步骤
                        switch (data.ApproveStatus) {
                            case -2:
                                data.ApproveStatus = '作废';
                                break;
                            case -1:
                                data.ApproveStatus = '被退回';
                                break;
                            case 0:
                                data.ApproveStatus = '待提交';
                                break;
                            case 1:
                                data.ApproveStatus = '审批中';
                                break;
                            default:
                                data.ApproveStatus = '已审批';
                        }
                        tbody.push('<tr>');
                        tbody.push('<td><span class="filename">');
                        tbody.push(nickname);
                        tbody.push('</span></td>');
                        tbody.push('<td>');
                        tbody.push(data.update_time);
                        tbody.push('</td>');
                        tbody.push('<td>');
                        tbody.push(data.ApproveStatus);
                        tbody.push('</td>');
                        tbody.push('<td class="btnWrap">');
                        if (admin_id == user_id) {
                            if (approveStatus == -1) {
                                tbody.push('<i class="fa fa-search" onclick="seeOnLine(' + onLineTableId + ')"></i><i class="fa fa-edit" onclick="editOnLine(' + onLineTableId + ')"></i><i class="fa fa-trash-o" onclick="delOnLine(this,' + onLineTableId + ')"></i>');
                            }
                            if (approveStatus == -2) {
                                tbody.push('<i class="fa fa-search" onclick="seeOnLine(' + onLineTableId + ')"></i>');
                            }
                            if (approveStatus == 0) {
                                tbody.push('<i class="fa fa-search" onclick="seeOnLine(' + onLineTableId + ')"></i><i class="fa fa-edit" onclick="editOnLine(' + onLineTableId + ')"></i><i class="fa fa-trash-o" onclick="delOnLine(this,' + onLineTableId + ')"></i>');
                            }
                            if (approveStatus == 1) {
                                tbody.push('<i class="fa fa-search" onclick="seeOnLine(' + onLineTableId + ')"></i>');
                            }
                            if (approveStatus == 2) {
                                tbody.push('<i class="fa fa-search" onclick="seeOnLine(' + onLineTableId + ')"></i><i class="fa fa-download" onclick="downOnLine(' + onLineTableId + ')"></i><i class="fa fa-times" onclick="toVoidOnLine(this,' + onLineTableId + ')" pid=' + id + '></i>');
                            }
                        } else if (admin_id == currentApproverId) {
                            if (approveStatus == 1) {
                                tbody.push('<i class="fa fa-search" onclick="seeOnLine(' + onLineTableId + ')"></i>');
                            }
                        } else {
                            if (approveStatus == -1) {
                                tbody.push('<i class="fa fa-search" onclick="seeOnLine(' + onLineTableId + ')"></i><i class="fa fa-edit" onclick="editOnLine(' + onLineTableId + ')"></i><i class="fa fa-trash-o" onclick="delOnLine(this,' + onLineTableId + ')"></i>');
                            }
                            if (approveStatus == -2) {
                                tbody.push('<i class="fa fa-search" onclick="seeOnLine(' + onLineTableId + ')"></i>');
                            }
                            if (approveStatus == 0) {
                                tbody.push('<i class="fa fa-search" onclick="seeOnLine(' + onLineTableId + ')"></i>');
                            }
                            if (approveStatus == 1) {
                                tbody.push('<i class="fa fa-search" onclick="seeOnLine(' + onLineTableId + ')"></i>');
                            }
                            if (approveStatus == 2) {
                                tbody.push('<i class="fa fa-search" onclick="seeOnLine(' + onLineTableId + ')"></i><i class="fa fa-download" onclick="downOnLine(' + onLineTableId + ')"></i><i class="fa fa-times" onclick="toVoidOnLine(this,' + onLineTableId + ')" pid=' + id + '></i>');
                            }
                        }
                        tbody.push('</td>');
                        tbody.push('</tr>');
                    }
                    tbody.push('<tr><th class="table-title" colspan="4">扫描上传</th></tr>');
                    tbody.push('<tr>');
                    tbody.push('<th><span class="filename">文件名称</span></th>');
                    tbody.push('<th>上传人</th>');
                    tbody.push('<th>上传日期</th>');
                    tbody.push('<th>操作</th>');
                    tbody.push('</tr>');
                    if (res.upload_form_sao == '') {
                        tbody.push('<tr>');
                        tbody.push('<td class="td-empty" colspan="4">');
                        tbody.push('无扫描上传数据');
                        tbody.push('</td>');
                        tbody.push('</tr>');
                    }
                    for (var j = 0; j < res.upload_form_sao.length; j++) {
                        var data = res.upload_form_sao[j];
                        var onLineTableId = data.id;
                        console.log(data);
                        tbody.push('<tr>');
                        tbody.push('<td><span class="filename">');
                        tbody.push(data.data_name);
                        tbody.push('</span></td>');
                        tbody.push('<td>');
                        tbody.push(data.nickname);
                        tbody.push('</td>');
                        tbody.push('<td>');
                        tbody.push(data.create_time);
                        tbody.push('</td>');
                        tbody.push('<td><i class="fa fa-search" onclick="printConFile(' + onLineTableId + ')"></i><i class="fa fa-download" onclick="downConFileImp(' + onLineTableId + ')"></i><i class="fa fa-trash-o" onclick="delFile(this,' + onLineTableId + ')"></i></td>');
                        tbody.push('</tr>');
                    }
                    tbody.push('<tr><th class="table-title" colspan="4">附件资料</th></tr>');
                    tbody.push('<tr>');
                    tbody.push('<th><span class="filename">附件名称</span></th>');
                    tbody.push('<th>上传人</th>');
                    tbody.push('<th>上传日期</th>');
                    tbody.push('<th>操作</th>');
                    tbody.push('</tr>');
                    if (res.upload_form_fu == '') {
                        tbody.push('<tr>');
                        tbody.push('<td class="td-empty" colspan="4">');
                        tbody.push('无图像资料数据');
                        tbody.push('</td>');
                        tbody.push('</tr>');
                    }
                    for (var j = 0; j < res.upload_form_fu.length; j++) {
                        var data = res.upload_form_fu[j];
                        var onLineTableId = data.id;
                        console.log(data);
                        tbody.push('<tr>');
                        tbody.push('<td><span class="filename">');
                        tbody.push(data.data_name);
                        tbody.push('</span></td>');
                        tbody.push('<td>');
                        tbody.push(data.nickname);
                        tbody.push('</td>');
                        tbody.push('<td>');
                        tbody.push(data.create_time);
                        tbody.push('</td>');
                        tbody.push('<td><i class="fa fa-search" onclick="printConFile(' + onLineTableId + ')"></i><i class="fa fa-download" onclick="downConFileData(' + onLineTableId + ')"></i><i class="fa fa-trash-o" onclick="delFile(this,' + onLineTableId + ')"></i></td>');
                        tbody.push('</tr>');
                    }
                    $('table[uid=' + id + '] tbody').append(tbody.join(''));
                }
            });
        }
    });
});

//在线填报-查看
function seeOnLine(id) {
    layer.open({
        type: 2,
        title: '在线填报',
        shadeClose: true,
        area: ['980px', '90%'],
        content: '/quality/Qualityform/edit?cpr_id=' + controlpoint_id + '&id=' + id + '&currentStep=null&isView=True'
    });
}

//在线填报-编辑
function editOnLine(id) {
    isView = true;
    layer.open({
        type: 2,
        title: '在线填报',
        shadeClose: true,
        area: ['980px', '90%'],
        content: '/quality/Qualityform/edit?cpr_id=' + controlpoint_id + '&id=' + id + '&currentStep=' + currentStep
    });
}

//在线填报-删除
function delOnLine(that, id) {
    layer.confirm("你将删除该数据，是否确认删除？", function () {
        $.ajax({
            url: "/quality/Qualityform/delForm",
            type: "post",
            data: {id: id},
            success: function (res) {
                layer.msg("删除数据成功！");
                $(that).parents('tr').remove();
            }
        });
    });
}

//查看附件
function showPdf(id, url) {
    $.ajax({
        url: url,
        type: "post",
        data: {id: id},
        success: function (res) {
            console.log(res);
            if (res.code === 1) {
                var path = res.path;
                var houzhui = res.path.split(".");
                if (houzhui[houzhui.length - 1] == "pdf") {
                    window.open("/static/public/web/viewer.html?file=../../../" + path, "_blank");
                } else if (res.path.split(".")[1] === "png" || res.path.split(".")[1] === "jpg" || res.path.split(".")[1] === "jpeg") {
                    layer.photos({
                        photos: {
                            "title": "", //相册标题
                            "id": id, //相册id
                            "start": 0, //初始显示的图片序号，默认0
                            "data": [   //相册包含的图片，数组格式
                                {
                                    "alt": "图片名",
                                    "pid": id, //图片id
                                    "src": "../../../" + res.path, //原图地址
                                    "thumb": "" //缩略图地址
                                }
                            ]
                        }
                        , anim: Math.floor(Math.random() * 7) //0-6的选择，指定弹出图片动画类型，默认随机（请注意，3.0之前的版本用shift参数）
                        , success: function () {
                            $(".layui-layer-shade").empty();
                        }
                    });
                } else {
                    layer.msg("不支持的文件格式");
                }

            } else {
                layer.msg(res.msg);
            }
        }
    })
}

//点击打印模板
function printConFile(id) {
    showPdf(id, "/quality/Unitqualitymanage/relationPreview");
}

//删除封装的方法
function delData(that, id, url) {
    layer.confirm('是否删除该数据?', function (index) {
        $.ajax({
            type: "post",
            url: url,
            data: {id: id},
            success: function (res) {
                if (res.code == 1) {
                    $(that).parents('tr').remove();
                    layer.msg("删除成功！");
                } else if (res.code != 1) {
                    layer.msg('返回数据错误！')
                }
            }
        });
    });
}

//扫描件回传、附件资料的删除
function delFile(that, id) {
    delData(that, id, "/quality/Unitqualitymanage/relationDel");
}

//在线填报-点击作废
function toVoidOnLine(that, id) {
    var pid = $(that).attr('pid');
    layer.confirm('是否作废该数据? 如果作废,会生成一个新的待提交表单', function (index) {
        $.ajax({
            url: "/quality/qualityform/cancel",
            type: "post",
            data: {id: id},
            success: function (res) {
                $('.controlTitle[id=' + pid + ']').click();
                $('.controlTitle[id=' + pid + ']').click();
                layer.msg("该数据已作废了！");
            },
            error: function () {
                layer.msg("作废操作异常");
            }
        });
        layer.close(index);
    });

}

//下载list封装的方法
function downloadList(id, url) {
    $.ajax({
        url: url,
        type: "post",
        dataType: "json",
        data: {id: id},
        success: function (res) {
            if (res.code != 1) {
                layer.msg(res.msg);
            } else {
                $("#form_container").empty();
                var str = "";
                str += ""
                    + "<iframe name=downloadFrame" + id + " style='display:none;'></iframe>"
                    + "<form name=download" + id + " action=" + url + " method='get' target=downloadFrame" + id + ">"
                    + "<span class='file_name' style='color: #000;'>" + str + "</span>"
                    + "<input class='file_url' style='display: none;' name='id' value=" + id + ">"
                    + "<button type='submit' class=btn" + id + "></button>"
                    + "</form>"
                $("#form_container").append(str);
                $("#form_container").find(".btn" + id).click();
            }

        }
    })
}

//点击下面的列表的下载
function downConFileImp(id) {
    downloadList(id, "/quality/Unitqualitymanage/relationDownload");
}

//点击下面的列表的下载
function downConFileData(id) {
    downloadList(id, "/quality/Unitqualitymanage/relationDownload");
}

//下载封装的方法
function downloadFrom(id, url) {
    $.ajax({
        url: url,
        type: "post",
        dataType: "json",
        data: {formId: id},
        success: function (res) {
            if (res.code != 1) {
                layer.msg(res.msg);
            } else {
                $("#form_container_from").empty();
                var str = "";
                str += ""
                    + "<iframe name=downloadFrame" + id + " style='display:none;'></iframe>"
                    + "<form name=download" + id + " action=" + url + " method='get' target=downloadFrame" + id + ">"
                    + "<span class='file_name' style='color: #000;'>" + str + "</span>"
                    + "<input class='file_url' style='display: none;' name='formId' value=" + id + ">"
                    + "<button type='submit' class=btn" + id + "></button>"
                    + "</form>"
                $("#form_container_from").append(str);
                $("#form_container_from").find(".btn" + id).click();
            }
        }
    })
}

//在线填报-下载
function downOnLine(id) {
    downloadFrom(id, "/quality/element/formDownload");
}

//添加自定义属性
$('#addAttr').click(function () {
    var attrGroup = [];
    attrGroup.push('<div class="attrGroup"><div class="layui-input-inline">');
    attrGroup.push('<input type="text" name="attrKey" required  lay-verify="required" placeholder="属性名" autocomplete="off" class="layui-input">');
    attrGroup.push('<input type="text" name="attrVal" required  lay-verify="required" placeholder="属性值" autocomplete="off" class="layui-input">');
    attrGroup.push('</div>');
    attrGroup.push('<div class="layui-form-mid layui-word-aux">');
    attrGroup.push('<i class="fa fa-check saveAttr" attrId="" onclick="saveAttr(this)"></i>');
    attrGroup.push('<i class="fa fa-close closeAttr" onclick="delAttr(this)"></i>');
    attrGroup.push('</div></div>');
    $('#attrGroup').append(attrGroup.join(' '));
});

//保存自定义属性
function saveAttr(that) {
    var attrId = $(that).attr('attrId');
    var attrKey = $(that).parents('.attrGroup').find('input[name="attrKey"]').val();
    var attrVal = $(that).parents('.attrGroup').find('input[name="attrVal"]').val();
    console.log(attrVal);
    $.ajax({
        url: "/modelmanagement/Qualitymass/addAttr",
        type: "post",
        data: {
            attrId:attrId,
            add_id: nodeId,
            attrKey: attrKey,
            attrVal: attrVal
        },
        dataType: "json",
        success: function (res) {
            $(that).attr('attrId',res.attrId);
            layer.msg(res.msg);
        }
    });
}

//删除自定义属性
function delAttr(that) {
    var attrId = $(that).attr('attrId');
    layer.confirm('确定删除该属性?', {icon: 3, title:'提示'}, function(index){
        $.ajax({
            url: "/modelmanagement/Qualitymass/delAttr",
            type: "post",
            data: {
                attrId: attrId
            },
            dataType: "json",
            success: function (res) {
                $(that).parents('div.attrGroup').remove();
                layer.msg(res.msg);
            }
        });
        layer.close(index);
    });
}

//模板信息
modelInfo = function (uObjSubID,number_type) {
    $.ajax({
        url: "./getManageInfo",
        type: "post",
        data: {
            number: uObjSubID,
            number_type:number_type
        },
        dataType: "json",
        success: function (res) {
            if(res.unit_info!=null){
                $('#unitTitle').text(res.unit_info.site);
                $('#site').text(res.unit_info.site);
                $('#serial_number').text(res.unit_info.coding);
                $('#hinge').text(res.unit_info.hinge);
                $('#quantities').text(res.unit_info.quantities);
                $('#en_type').text(res.unit_info.en_type);
                $('#ma_bases').text(res.unit_info.ma_bases);
                $('#su_basis').text(res.unit_info.su_basis);
                $('#el_start').text(res.unit_info.el_start);
                $('#el_cease').text(res.unit_info.el_cease);
                $('#pile_number').text(res.unit_info.pile_number);
                $('#start_date').text(res.unit_info.start_date);
                $('#completion_date').text(res.unit_info.completion_date);
            }
            //回显自定义属性
            if(res.attr_info.length>0){
                $('#attrGroup').empty();
                var attrGroup = [];
                for (var i = 0; i < res.attr_info.length; i++) {
                    var attrKey = res.attr_info[i].attrKey;
                    var attrVal = res.attr_info[i].attrVal;
                    attrGroup.push('<div class="attrGroup"><div class="layui-input-inline">');
                    attrGroup.push('<input type="text" name="attrKey" value=' + attrKey + ' required  lay-verify="required" placeholder="属性名" autocomplete="off" class="layui-input">');
                    attrGroup.push('<input type="text" name="attrVal" value=' + attrVal + ' required  lay-verify="required" placeholder="属性值" autocomplete="off" class="layui-input">');
                    attrGroup.push('</div>');
                    attrGroup.push('<div class="layui-form-mid layui-word-aux">');
                    attrGroup.push('<i class="fa fa-check saveAttr" attrId='+ res.attr_info[i].attrId +' onclick="saveAttr(this)"></i>');
                    attrGroup.push('<i class="fa fa-close closeAttr" attrId='+ res.attr_info[i].attrId +' onclick="delAttr(this)"></i>');
                    attrGroup.push('</div></div>');
                }
                $('#attrGroup').append(attrGroup.join(' '));
            }
        }
    });
}