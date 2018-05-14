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