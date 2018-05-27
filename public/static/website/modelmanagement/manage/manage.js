modelTrans = '';        //透明度
modelColor = '';        //选择集颜色
choiceness_pigment = '';       //优良色值
qualified_pigment = '';      //合格色值
un_evaluation_pigment = '';  //不合格色值
modeGroupIds = '';  //模型组ID
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

//验收资料
layui.use('element', function(){
    var element = layui.element;
    element.on('collapse(control)', function(data){
        if(data.show){
            var id = $(data.title).attr('id');
            var procedureid = $(data.title).attr('procedureid');
            var unit_id = $(data.title).attr('unit_id');
            $.ajax({
                url: "/modelmanagement/Manage/getLineReport",
                type: "post",
                data: {
                    id: 56,
                    procedureid: 22,
                    unit_id: 18
                },
                dataType: "json",
                success: function (res) {
                    //在线填报
                    var tbody = [];
                    tbody.push('<tr><th class="table-title" colspan="4">在线填报</th></tr>');
                    tbody.push('<tr>');
                    tbody.push('<th>填报人</th>');
                    tbody.push('<th>填报日期</th>');
                    tbody.push('<th>审批状态</th>');
                    tbody.push('<th>操作</th>');
                    tbody.push('</tr>');
                    $('table[uid='+ id +'] tbody').empty();
                    $('table[uid='+ id +'] tbody').empty();
                    for(var i = 0;i<res.form_info.length;i++){
                        var data = res.form_info[i];
                        tbody.push('<tr>');
                        tbody.push('<td>');
                        tbody.push(data.nickname);
                        tbody.push('</td>');
                        tbody.push('<td>');
                        tbody.push(data.update_time);
                        tbody.push('</td>');
                        tbody.push('<td>');
                        tbody.push(data.ApproveStatus);
                        tbody.push('</td>');
                        tbody.push('<td><i class="fa fa-search"></i><i class="fa fa-download"></i><i class="fa fa-close"></i></td>');
                        tbody.push('</tr>');
                    }
                    tbody.push('<tr><th class="table-title" colspan="4">扫描上传</th></tr>');
                    tbody.push('<tr>');
                    tbody.push('<th>文件名称</th>');
                    tbody.push('<th>上传人</th>');
                    tbody.push('<th>上传日期</th>');
                    tbody.push('<th>操作</th>');
                    tbody.push('</tr>');
                    for(var j = 0;j<res.upload_form_sao.length;j++){
                        var data = res.upload_form_sao[j];
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
                        tbody.push('<td><i class="fa fa-search"></i><i class="fa fa-download"></i><i class="fa fa-close"></i></td>');
                        tbody.push('</tr>');
                    }
                    tbody.push('<tr><th class="table-title" colspan="4">附件资料</th></tr>');
                    tbody.push('<tr>');
                    tbody.push('<th>附件名称</th>');
                    tbody.push('<th>上传人</th>');
                    tbody.push('<th>上传日期</th>');
                    tbody.push('<th>操作</th>');
                    tbody.push('</tr>');
                    for(var j = 0;j<res.upload_form_fu.length;j++){
                        var data = res.upload_form_fu[j];
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
                        tbody.push('<td><i class="fa fa-search"></i><i class="fa fa-download"></i><i class="fa fa-close"></i></td>');
                        tbody.push('</tr>');
                    }
                    $('table[uid='+ id +'] tbody').append(tbody.join(''));
                }
            });
        }
    });
});