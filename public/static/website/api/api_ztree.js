/* 常规zTree */
var setting = {
    async: {
        enable : true,
        type : 'post',
        url : '/admin/admin/index',
        dataType :'json'
    },
    data: {
        simpleData: {
            enable: true,
            idKey: "id",
            pIdKey: "pId"
        }
    },
    view:{
        selectedMulti: false
    },
    callback:{
        onClick: zTreeOnClick
    }
};
zTreeObj = $.fn.zTree.init($("#ztree"), setting,null);

//点击树节点
function zTreeOnClick() {

}

/* 带眼镜的zTree👀 */
var setting = {
    async: {
        enable : true,
        type : 'post',
        url : '/admin/admin/index',
        dataType :'json'
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
    view:{
        selectedMulti: false,
        showIcon: false
    },
    callback:{
        onClick: zTreeEyeOnClick,
        onCheck: zTreeEyeOnCheck
    }
};
zTreeObj = $.fn.zTree.init($("#ztreeEye"), setting,null);

//点击树节点
function zTreeEyeOnClick() {

}

//勾选树节点
function zTreeEyeOnCheck() {

}