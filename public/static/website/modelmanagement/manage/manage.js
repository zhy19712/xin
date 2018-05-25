modelTrans = '';
modelColor = '';
choiceness_pigment = '';
qualified_pigment = '';      //合格颜色
un_evaluation_pigment = '';  //不合格颜色
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
    var data = nodeModelNumber();
    if(treeNode.level==5){
        window.operateModel(data);
    }
}

//显示隐藏模板事件
function zTreeOnCheck(event, treeId, treeNode) {
    console.log(111);
    nodeId = treeNode.add_id;
    node_type = treeNode.node_type;
    var data = nodeModelNumber();
    console.log(data);
    var checked = treeNode.checked;
    if(checked){
        //隐藏关联构件
        window.hideModel(data);
    }else {
        window.showModel(data);
    }
}

//显示隐藏模板函数
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