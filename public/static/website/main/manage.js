function easyUiPanelToggle() {
    var number = $("#easyuiLayout").layout("panel", "east")[0].clientWidth;
    if(number<=0){
        $('#easyuiLayout').layout('expand','east');
    }
}

function seeOnLine(that) {
    var id = $(that).attr('formId');
    var cprId = $(that).attr('cprId');
    layer.open({
        type: 2,
        title: '在线填报',
        shadeClose: true,
        area: ['980px', '90%'],
        content: '../../../quality/Qualityform/edit?cpr_id='+ cprId + '&id='+ id +'&currentStep=0&isView=True'
    });
}

//初始化手风琴
layui.use('element', function(){
    var element = layui.element;
});

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