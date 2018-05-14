//字符解码
function ajaxDataFilter(treeId, parentNode, responseData) {
    if (responseData) {
        for(var i =0; i < responseData.length; i++) {
            responseData[i] = JSON.parse(responseData[i]);
            responseData[i].name = decodeURIComponent(responseData[i].name);
        }
    }
    return responseData;
}

var setting = {
    async: {
        enable : true,
        autoParam: ["pid"],
        type : "post",
        url : "../../archive/Atlas/getOrganization",
        dataType :"json",
        dataFilter: ajaxDataFilter
    },
    data: {
        simpleData: {
            enable: true,
            idKey: "id",
            pIdKey: "pid"
        }
    },
    view:{
        selectedMulti: false
    },
    callback:{
        onDblClick: zTreeOnDblClick
    },
    showLine:true,
    showTitle:true,
    showIcon:true
};
zTreeObj = $.fn.zTree.init($("#userZtree"), setting, null);


function zTreeOnDblClick(event, treeId, treeNode) {
    if(treeNode.isParent==undefined || treeNode.isParent==false){
        var selectedUserName = '<span class="tag">'+ treeNode.name +'<i class="fa fa-close"></i></span>';
        $('#selectedUser').append($(selectedUserName));
        $('#userTittle').html('已选角色 >' + treeNode.name);
    }
}

//搜索树
$('#searchZtreeBtn').click(function() {
    var key = $("#ztreeName").val();
    var nodes = zTreeObj.getNodesByParam("name", key);
    $.each(nodes, function (i, node) {
        zTreeObj.expandNode(node.getParentNode(), true, false, true);
        zTreeObj.selectNode(node,true)
    });
});

//搜索用户
$('#searchUserNameBtn').click(function() {
    var userName = $("#userName").val();
    var tag = $('.tag');
    if(userName.trim()===""){
        users.css("background-color", "")
        return;
    }
    $.each(tag,function (i,n) {
        var name = $(n).text();
        $(n).removeClass('active');
        if($.trim(name).indexOf(userName)!==-1){
            $(n).addClass('active');
        }
    })
});

layui.use('form', function(){
    var form = layui.form;
    form.on('submit(saveUser)', function(data){
        var selectedUser = $.trim($('#selectedUser').html());
        window.opener.document.getElementById('selectedUser').innerHTML=selectedUser;
        window.close();
        return false; //阻止表单跳转。如果需要表单跳转，去掉这段即可。
    });
});

