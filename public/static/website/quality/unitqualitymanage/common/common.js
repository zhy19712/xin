//初始化layui组件
var initUi = layui.use('form','laydate');
var form = layui.form;

//获取控制点
function getControlPoint(url) {
    $.ajax({
        url: url,
        type: "post",
        data: {
            add_id:window.treeNode.add_id
        },
        dataType: "json",
        success: function (res) {
            $('#controlPointItem').empty();
            if(res.code=1){
                if(res.data==''){
                    return false;
                }
                var pointItemArr = [];
                for(var i in res.data){
                    pointItemArr.push('<a href="javascript:;" class="pointItem" uid='+ i +' onclick="loadTableData(this)"><i class="fa fa-file-text-o"></i>'+ res.data[i] +'</a><i class="fa fa-long-arrow-right"></i>');
                }
                $('#controlPointItem').append(pointItemArr.join(''));
                $('.pointItem:last').next('i').remove();
                $('#controlPointItem a:first').click();
            }
        }
    });
}
//控制点数据
function loadTableData(that) {
    var nodeId = $('#enginId').val();
    var workId = $(that).attr('uid');
    var type = $('#type').val();
    $('#workId').val(workId);
    unitPlanList();
    window.tableItem.ajax.url('/quality/common/datatablesPre?tableName=unit_quality_control&add_id='+ nodeId +'&workId='+ workId +'&type='+ type +'').load();
    $('#tableItem_wrapper,.tbcontainer,#subList').show();
    $(that).addClass('active').siblings('a').removeClass();
    $('#implement_wrapper,#imageData_wrapper').next('.tbcontainer').remove();
    btnToggle(that);
}

//按钮切换展示
function btnToggle(that) {
    if($(that).index()==0){
        $('.subBtn').hide();
        $('.frtBtn').show();
    }else{
        $('.frtBtn').hide();
        $('.subBtn').show();
    }
}
