modelTrans = '';        //透明度
modelColor = '';        //选择集颜色
choiceness_pigment = '';       //优良色值
qualified_pigment = '';      //合格色值
un_evaluation_pigment = '';  //不合格色值
modeGroupIds = '';  //模型组ID
controlpoint_id = ''    //控制点Id
currentStep = ''; //审批步骤
$.ajax({
    url: "/modelmanagement/qualitymass/configureInfo",
    type: "post",
    dataType: "json",
    success: function (res) {
        modelTrans = res.configureInfo.quality.pellucidity;
        modelColor = +res.configureInfo.quality.pigment;
        choiceness_pigment = +res.configureInfo.quality.choiceness_pigment;
        qualified_pigment = +res.configureInfo.quality.qualified_pigment;
        un_evaluation_pigment = +res.configureInfo.quality.un_evaluation_pigment;
    }
});

//折叠面板
function easyUiPanelToggle() {
    var number = $("#easyuiLayout").layout("panel", "east")[0].clientWidth;
    if(number<=0){
        $('#easyuiLayout').layout('expand','east');
    }
}

//初始化手风琴
layui.use('element', function(){
    var element = layui.element;
});

//工程划分
function ztree(node_type) {
    var setting = {
        async: {
            enable: true,
            autoParam: ["pid","tid"],
            type: "get",
            url: "/modelmanagement/qualitymass/index?node_type="+node_type,
            dataType: "json"
        },
        data: {
            simpleData: {
                enable: true,
                idKey: "id",
                pIdKey: "pId"
            }
        },
        check:{
            enable: true
        },
        callback:{
            onClick: zTreeOnClick,
            onCheck: zTreeOnCheck
        },
        showLine:true,
        showTitle:true,
        showIcon:false
    };
    zTreeObj = $.fn.zTree.init($("#ztree"), setting, null);
}
ztree(0);

//点击节点
function zTreeOnClick(event, treeId, treeNode) {
    console.log(treeNode);
    nodeId = treeNode.add_id;
    node_type = treeNode.node_type;
    modeGroupIds = nodeModelNumber();
    if(treeNode.level==5){
        window.operateModel(modeGroupIds);
    }
}

//显示隐藏模板事件
function zTreeOnCheck(event, treeId, treeNode) {
    nodeId = treeNode.add_id;
    node_type = treeNode.node_type;
    modeGroupIds = nodeModelNumber();
    var checked = treeNode.checked;
    if(checked){
        //隐藏关联构件
        window.hideModel(modeGroupIds);
    }else {
        window.showModel(modeGroupIds);
    }
}

//模板组ID
function nodeModelNumber() {
    var result;
    $.ajax({
        url: "/modelmanagement/Qualitymass/nodeModelNumber",
        type: "post",
        async:false,
        data: {
            add_id:nodeId,
            node_type:node_type
        },
        dataType: "json",
        success: function (res) {
            result = res.data;
        }
    });
    return result;
}

//验收资料
layui.use('element', function(){
    var element = layui.element;
    element.on('collapse(control)', function(data){
        if(data.show){
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
                    tbody.push('<th>填报人</th>');
                    tbody.push('<th>填报日期</th>');
                    tbody.push('<th>审批状态</th>');
                    tbody.push('<th>操作</th>');
                    tbody.push('</tr>');
                    $('table[uid='+ id +'] tbody').empty();
                    if(res.form_info==''){
                        tbody.push('<tr>');
                        tbody.push('<td class="td-empty" colspan="4">');
                        tbody.push('无在线填报数据');
                        tbody.push('</td>');
                        tbody.push('</tr>');
                    }
                    for(var i = 0;i<res.form_info.length;i++){
                        var data = res.form_info[i];
                        var onLineTableId = data.id;
                        var user_id = data.user_id;     //填报人ID
                        var nickname = data.nickname;    //填报人名字
                        var currentApproverId = data.CurrentApproverId;    //审批人ID
                        var approveStatus = data.ApproveStatus;    //审批状态
                        currentStep = data.CurrentStep; //审批步骤
                        switch(data.ApproveStatus)
                        {
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
                        tbody.push('<td>');
                        tbody.push(nickname);
                        tbody.push('</td>');
                        tbody.push('<td>');
                        tbody.push(data.update_time);
                        tbody.push('</td>');
                        tbody.push('<td>');
                        tbody.push(data.ApproveStatus);
                        tbody.push('</td>');
                        tbody.push('<td class="btnWrap">');
                        if(admin_id==user_id){
                            if(approveStatus==-1){
                                tbody.push('<i class="fa fa-search" onclick="seeOnLine('+ onLineTableId +')"></i><i class="fa fa-edit" onclick="editOnLine('+ onLineTableId +')"></i><i class="fa fa-trash-o" onclick="delOnLine('+ onLineTableId +')"></i>');
                            }
                            if(approveStatus==-2){
                                tbody.push('<i class="fa fa-search" onclick="seeOnLine('+ onLineTableId +')"></i>');
                            }
                            if(approveStatus==0){
                                tbody.push('<i class="fa fa-search" onclick="seeOnLine('+ onLineTableId +')"></i><i class="fa fa-edit" onclick="editOnLine('+ onLineTableId +')"></i><i class="fa fa-trash-o" onclick="delOnLine('+ onLineTableId +')"></i>');
                            }
                            if(approveStatus==1){
                                tbody.push('<i class="fa fa-search" onclick="seeOnLine('+ onLineTableId +')"></i>');
                            }
                            if(approveStatus==2){
                                tbody.push('<i class="fa fa-search" onclick="seeOnLine('+ onLineTableId +')"></i><i class="fa fa-download"></i><i class="fa fa-times"></i>');
                            }
                        }else if(admin_id==currentApproverId){
                            if(approveStatus==1){
                                tbody.push('<i class="fa fa-search" onclick="seeOnLine('+ onLineTableId +')"></i>');
                            }
                        }else{
                            if(approveStatus==-1){
                                tbody.push('<i class="fa fa-search" onclick="seeOnLine('+ onLineTableId +')"></i><i class="fa fa-edit" onclick="editOnLine('+ onLineTableId +')"></i><i class="fa fa-trash-o" onclick="delOnLine('+ onLineTableId +')"></i>');
                            }
                            if(approveStatus==-2){
                                tbody.push('<i class="fa fa-search" onclick="seeOnLine('+ onLineTableId +')"></i>');
                            }
                            if(approveStatus==0){
                                tbody.push('<i class="fa fa-search" onclick="seeOnLine('+ onLineTableId +')"></i>');
                            }
                            if(approveStatus==1){
                                tbody.push('<i class="fa fa-search" onclick="seeOnLine('+ onLineTableId +')"></i>');
                            }
                            if(approveStatus==2){
                                tbody.push('<i class="fa fa-search" onclick="seeOnLine('+ onLineTableId +')"></i><i class="fa fa-download"></i><i class="fa fa-times"></i>');
                            }
                        }
                        tbody.push('</td>');
                        tbody.push('</tr>');
                    }
                    tbody.push('<tr><th class="table-title" colspan="4">扫描上传</th></tr>');
                    tbody.push('<tr>');
                    tbody.push('<th>文件名称</th>');
                    tbody.push('<th>上传人</th>');
                    tbody.push('<th>上传日期</th>');
                    tbody.push('<th>操作</th>');
                    tbody.push('</tr>');
                    if(res.upload_form_sao==''){
                        tbody.push('<tr>');
                        tbody.push('<td class="td-empty" colspan="4">');
                        tbody.push('无扫描上传数据');
                        tbody.push('</td>');
                        tbody.push('</tr>');
                    }
                    for(var j = 0;j<res.upload_form_sao.length;j++){
                        var data = res.upload_form_sao[j];
                        console.log(data);
                        tbody.push('<tr>');
                        tbody.push('<td>');
                        tbody.push(data.data_name);
                        tbody.push('</td>');
                        tbody.push('<td>');
                        tbody.push(data.nickname);
                        tbody.push('</td>');
                        tbody.push('<td>');
                        tbody.push(data.create_time);
                        tbody.push('</td>');
                        tbody.push('<td><i class="fa fa-search" onclick="seeOnLine('+ onLineTableId +')"></i><i class="fa fa-download"></i><i class="fa fa-close"></i></td>');
                        tbody.push('</tr>');
                    }
                    tbody.push('<tr><th class="table-title" colspan="4">附件资料</th></tr>');
                    tbody.push('<tr>');
                    tbody.push('<th>附件名称</th>');
                    tbody.push('<th>上传人</th>');
                    tbody.push('<th>上传日期</th>');
                    tbody.push('<th>操作</th>');
                    tbody.push('</tr>');
                    if(res.upload_form_fu==''){
                        tbody.push('<tr>');
                        tbody.push('<td class="td-empty" colspan="4">');
                        tbody.push('无图像资料数据');
                        tbody.push('</td>');
                        tbody.push('</tr>');
                    }
                    for(var j = 0;j<res.upload_form_fu.length;j++){
                        var data = res.upload_form_fu[j];
                        console.log(data);
                        tbody.push('<tr>');
                        tbody.push('<td>');
                        tbody.push(data.data_name);
                        tbody.push('</td>');
                        tbody.push('<td>');
                        tbody.push(data.nickname);
                        tbody.push('</td>');
                        tbody.push('<td>');
                        tbody.push(data.create_time);
                        tbody.push('</td>');
                        tbody.push('<td><i class="fa fa-search" onclick="seeOnLine('+ onLineTableId +')"></i><i class="fa fa-download"></i><i class="fa fa-close"></i></td>');
                        tbody.push('</tr>');
                    }
                    $('table[uid='+ id +'] tbody').append(tbody.join(''));
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
        content: '/quality/Qualityform/edit?cpr_id='+ controlpoint_id + '&id='+ id +'&currentStep=null&isView=True'
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
        content: '/quality/Qualityform/edit?cpr_id='+ controlpoint_id + '&id='+ id +'&currentStep=' + currentStep
    });
}

//在线填报-删除
function delOnLine(id) {
    layer.confirm("你将删除该数据，是否确认删除？", function () {
        $.ajax({
            url: "/quality/Qualityform/delForm",
            type: "post",
            data: {id: id},
            success: function (res) {
                if (res.code === 1) {
                    layer.msg("删除数据成功！", {time: 1500, shade: 0.1});
                    onlineFill.ajax.url("/quality/common/datatablesPre?tableName=quality_form_info&DivisionId="+nodeUnitId+"&ProcedureId="+procedureId+"&cpr_id="+controlRowId).load();
                }
            }
        });
    });
}

//添加自定义属性
$('#addAttr').click(function () {
    var attrGroup = [];
    attrGroup.push('<div class="layui-input-inline attrGroup">');
    attrGroup.push('<input type="text" name="attrKey" required  lay-verify="required" placeholder="属性名" autocomplete="off" class="layui-input">');
    attrGroup.push('<input type="text" name="attrVal" required  lay-verify="required" placeholder="属性值" autocomplete="off" class="layui-input">');
    attrGroup.push('</div>');
    attrGroup.push('<div class="layui-form-mid layui-word-aux">');
    attrGroup.push('<i class="fa fa-check saveAttr" onclick="saveAttr(this)"></i>');
    attrGroup.push('<i class="fa fa-close closeAttr" onclick="closeAttr(this)"></i>');
    attrGroup.push('</div>');
    $('#attrGroup').append(attrGroup.join(' '));
});

//保存自定义属性
function saveAttr(that) {
    var attrKey = $(that).parents('#attrGroup').find('input[name="attrKey"]').val();
    console.log(attrKey);
    var attrVal = $(that).parents('#attrGroup').find('input[name="attrVal"]').val();
    console.log(attrVal);
    $.ajax({
        url: "/modelmanagement/Qualitymass/addAttr",
        type: "post",
        data: {
            add_id:nodeId,
            attrKey:attrKey,
            attrVal:attrVal
        },
        dataType: "json",
        success: function (res) {
            layer.msg(res.msg);
        }
    });
}

//回显自定义属性
function getOne() {
    $.ajax({
        url: "/modelmanagement/Qualitymass/getOne",
        type: "post",
        data: {
            add_id:nodeId
        },
        dataType: "json",
        success: function (res) {
            $('#attrGroup').empty();
            var attrGroup = [];
            for(var i = 0;i<res.data.length;i++){
                var attrKey = res.data[i].attr_name;
                var attrVal = res.data[i].attr_value;
                attrGroup.push('<div class="layui-input-inline attrGroup">');
                attrGroup.push('<input type="text" name="attrKey" value='+ attrKey +' required  lay-verify="required" placeholder="属性名" autocomplete="off" class="layui-input">');
                attrGroup.push('<input type="text" name="attrVal" value='+ attrVal +' required  lay-verify="required" placeholder="属性值" autocomplete="off" class="layui-input">');
                attrGroup.push('</div>');
                attrGroup.push('<div class="layui-form-mid layui-word-aux">');
                attrGroup.push('<i class="fa fa-check saveAttr" onclick="saveAttr(this)"></i>');
                attrGroup.push('<i class="fa fa-close closeAttr" onclick="closeAttr(this)"></i>');
                attrGroup.push('</div>');
            }
            $('#attrGroup').append(attrGroup.join(' '));
        }
    });
}