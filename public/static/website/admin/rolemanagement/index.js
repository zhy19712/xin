
var selfid,zTreeObj,groupid,sNodes,selectData="";
var userList=[];
//时间
Date.prototype.Format = function (fmt) { // author: meizz
    var o = {
        "M+": this.getMonth() + 1, // 月份
        "d+": this.getDate(), // 日
    };
    if (/(y+)/.test(fmt))
        fmt = fmt.replace(RegExp.$1, (this.getFullYear() + "").substr(4 - RegExp.$1.length));
    for (var k in o)
        if (new RegExp("(" + k + ")").test(fmt)) fmt = fmt.replace(RegExp.$1, (RegExp.$1.length == 1) ? (o[k]) : (("00" + o[k]).substr(("" + o[k]).length)));
    return fmt;
};

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
//组织结构树
var setting = {
    view: {
        showLine: true, //设置 zTree 是否显示节点之间的连线。
        selectedMulti: false, //设置是否允许同时选中多个节点。
        // dblClickExpand: true //双击节点时，是否自动展开父节点的标识。
    },
    async: {
        enable : true,
        autoParam: ["pid"],
        type : "post",
        url : "./roletree",
        dataType :"json",
        dataFilter: ajaxDataFilter
    },
    data:{
        simpleData : {
            enable:true,
            idkey: "id",
            pIdKey: "pid",
            rootPId:0
        }
    },
    callback: {
        onClick: this.onClick
    }
};

zTreeObj = $.fn.zTree.init($("#ztree"), setting, null);


//新增
var dom = ['    <form  id="roleform" action="#" onsubmit="return false" class="layui-form" style="padding-top: 20px;">',
    '        <input type="hidden" name="id" id="addId" style="display: none;">',
    '        <input type="hidden" name="pid" id="pid" style="display: none;">',
    '        <div class="layui-form-item">',
    '            <div class="layui-inline">',
    '                <label class="layui-form-label">编号</label>',
    '                <div class="layui-input-inline">',
    '                    <input type="text" name="number_id" id="number_id" lay-verify="required" placeholder="编号" autocomplete="off" class="layui-input">',
    '                </div>',
    '            </div>',
    '            <div class="layui-inline">',
    '                <label class="layui-form-label">角色名称</label>',
    '                <div class="layui-input-inline">',
    '                    <input type="text" name="role_name" id="role_name" lay-verify="required" lay-verify="required" placeholder="角色名称" autocomplete="off" class="layui-input">',
    '                </div>',
    '            </div>',
    '        </div>',
    '        <hr class="layui-bg-gray">',
    '        <div class="layui-form-item">',
    '            <div class="layui-inline">',
    '                <label class="layui-form-label">创建人</label>',
    '                <div class="layui-input-inline">',
    '                    <input type="text" name="create_owner" id="create_owner" placeholder="创建人" readonly autocomplete="off" class="layui-input">',
    '                </div>',
    '            </div>',
    '            <div class="layui-inline">',
    '                <label class="layui-form-label">创建时间</label>',
    '                <div class="layui-input-inline">',
    '                    <input type="text" name="date" id="date" placeholder="创建时间" readonly autocomplete="off" class="layui-input">',
    '                </div>',
    '            </div>',
    '        </div>',
    '        <hr class="layui-bg-gray">',
    '        <div class="layui-form-item">',
    '                <label class="layui-form-label">角色描述</label>',
    '                <div class="layui-input-block">',
    '                       <textarea name="desc" id="desc"  placeholder="角色描述" placeholder="请输入" class="layui-textarea"></textarea>',
    '    </div>',
    '        </div>',
    '        <hr class="layui-bg-gray">',
    '        <div class="layui-form-item">',
    '            <div class="col-xs-12" style="text-align: center;">',
    '                <button class="layui-btn" lay-submit="" lay-filter="demo1"><i class="fa fa-save"></i> 保存</button>&nbsp;&nbsp;&nbsp;',
    '                <button type="reset" class="layui-btn layui-btn-danger layui-layer-close layui-layer-close1"><i class="fa fa-close"></i> 返回</button>',
    '            </div>',
    '        </div>',
    '    </form>',
].join("");


//tab 切换
layui.use(['element',"layer",'form'], function(){
    var $ = layui.jquery
        ,element = layui.element; //Tab的切换功能，切换事件监听等，需要依赖element模块

    var form = layui.form
        ,layer = layui.layer
        ,layedit = layui.layedit
        ,laydate = layui.laydate;

    //监听提交
    form.on('submit(demo1)', function(data){
            $.ajax({
                type: "post",
                url:"./editCate",
                data:data.field,
                success: function (res) {
                    if(res.code == 1) {
                        var url = "/admin/common/datatablespre/tableName/admin_cate/id/"+selfid+".shtml";
                        tableItem.ajax.url(url).load();
                        parent.layer.msg('保存成功！');
                        layer.closeAll();
                    }else{
                      layer.msg(res.msg);
                    }
                },
                error: function (data) {
                    debugger;
                }
            });
        return false;
    });
});

//点击获取路径
function onClick(e, treeId, node) {
    selectData = "";
    $(".layout-panel-center .panel-title").text("");
    sNodes = zTreeObj.getSelectedNodes();//选中节点
    selfid = zTreeObj.getSelectedNodes()[0].id
    var path = sNodes[0].name; //选中节点的名字
    node = sNodes[0].getParentNode();//获取父节点
    //判断是否还有父节点
    if (node) {
        //判断是否还有父节点
        while (node){
            path = node.name + "-" + path;
            node = node.getParentNode();
        }
    }else{
        $(".layout-panel-center .panel-title").text(sNodes[0].name);
    }
    groupid = sNodes[0].pId //父节点的id
        var url = "/admin/common/datatablespre/tableName/admin_cate/id/"+selfid+".shtml";
        tableItem.ajax.url(url).load();
    $(".layout-panel-center .panel-title").text("当前路径:"+path)
}

//点击添加节点
function addNodetree() {
    var pid  = selfid?selfid:0;

    layer.prompt({
        title: '请输入节点名称',
    },function(value, index, elem){
        $.ajax({
            url:'./editCatetype',
            type:"post",
            data:{pid:pid,name:value},
            success: function (res) {
                if(res.code===1){
                    if(sNodes){
                        zTreeObj.addNodes(sNodes[0],res.data);
                    }else{
                        zTreeObj.addNodes(null,res.data);
                    }

                }else{
                    layer.msg(res.msg);
                }
            }
        });
        layer.close(index);
    });

};

//编辑节点
function editNodetree() {
    if(!selfid){
        layer.msg("请选择节点",{time:1500,shade: 0.1});
        return;
    }
    console.log(sNodes[0])
    layer.prompt({
        title: '编辑',
        value:sNodes[0].name
    },function(value, index, elem){
        $.ajax({
            url:'./editCatetype',
            type:"post",
            data:{id:selfid,name:value},
            success: function (res) {
                if(res.code===1){
                    sNodes[0].name = value;
                    zTreeObj.updateNode(sNodes[0]);//更新节点名称
                    layer.msg("编辑成功")
                 }else{
                  layer.msg(res.msg);
                }
            }
        });
        layer.close(index);
    });
};

//删除节点
function delNodetree() {
    if(!selfid){
        layer.msg("请选择节点");
        return;
    }
    if(!sNodes[0].children){
        layer.confirm("该操作会将关联数据同步删除，是否确认删除？",function () {
            $.ajax({
                url:'./delCatetype',
                type:"post",
                data:{id:selfid},
                success: function (res) {
                    if(res.code===1){
                        layer.msg("删除节点成功",{time:1500,shade: 0.1});
                        var url = "/admin/common/datatablespre/tableName/admin_cate/id/"+selfid+".shtml";
                        tableItem.ajax.url(url).load();
                        $("#catepublish").parent("div").css("display",'none');
                        $(".userContainer").html('');
                        $(".path").html("");
                        zTreeObj.removeNode(sNodes[0]);
                        selfid = "";
                    }else{
                      layer.msg(res.msg);
                    }
                }
            });
        });
    }else{
        layer.msg("包含下级，无法删除",{time:1500,shade: 0.1});
    }

};
//

//全部展开
$('#openNode').click(function(){
    zTreeObj.expandAll(true);
});

//收起所有
$('#closeNode').click(function(){
    zTreeObj.expandAll(false);
});

//表格编辑
function conEdit(id){
    $.ajax({
        url: "./getOne",
        type: "post",
        data: {id:id},
        dataType: "json",
        success: function (res) {
            if(res.code==0){
              layer.msg(res.msg);
              return;
            }
            var nowtime = new Date().Format("yyyy-MM-dd");
            layer.open({
                type: 1,
                title: '角色管理—新增',
                area: ['690px', '440px'],
                content:dom
            });
            $("#addId").val(res.data.id);
            $("#number_id").val(res.data.number_id);
            $("#role_name").val(res.data.role_name);
            $("#desc").val(res.data.desc);
            $('#create_owner').val(res.data.create_owner);
            $('#date').val(res.data.date);
        }
    })
}
//表格删除
function conDel(id){
    layer.confirm('该操作会将关联数据同步删除，是否确认删除？', function(index){
        console.log(id);
        $.ajax({
            url: "./delCate",
            type: "post",
            data: {id:id},
            dataType: "json",
            success: function (res) {
                console.log(res);
                if(res.code == 1){
                    layer.msg("删除成功！");
                    var url = "/admin/common/datatablespre/tableName/admin_cate/id/"+selfid+".shtml";
                    tableItem.ajax.url(url).load();
                }else{
                  layer.msg(res.msg);
                }
            }
        });
        layer.close(index);
    });
}

//获取点击行
$("#tableItem").delegate("tbody tr","click",function (e) {
    if($(e.target).hasClass("dataTables_empty")){
       return;
    }
    $(".path").html("");
    $(this).addClass("select-color").siblings().removeClass("select-color");
    selectData = tableItem.row(".select-color").data();//获取选中行数据
    $(".path").html($(".layout-panel-center .panel-title").html().split("-").pop()+"-"+selectData[1]);
    $("#catepublish").parent("div").show();
    $("#catepublish").attr('src','/admin/rolemanagement/catepublish.shtml?roleId='+selectData[5]+'&path='+$(".path").html());
    getAdminname(selectData[5]);
});
//拉取角色用户
function getAdminname(id) {
    $.ajax({
        type:"post",
        url:"./getAdminname",
        data:{id:id,name:''},
        success: function (res) {
            userList = res;
            showUser();
        },
        error: function (data) {
            debugger;
        }
    })
};
//删除的点击事件
$(".userContainer").delegate("p a","click",function () {
    var admin_id = $(this).attr('id').slice(1);
    var that = $(this);
    $.ajax({
        type:"post",
        url:"./delAdminname",
        data:{id:selectData[5],admin_id:admin_id},
        success: function (res) {
            if(res.code===1){
                layer.msg("删除成功");
                console.log(that.parent("p"))
                that.parent("p").remove();
            }else{
                layer.msg(res.msg);
            }
        },
        error: function (data) {
            debugger;
        }
    })
})
//展示名字
function showUser() {
    var str = ""
    for(var i=0 ; i<userList.length;i++){
        str += '<p id="p'+userList[i].id+'">'+userList[i].name+'&nbsp;<a id="a'+userList[i].id+'"><i class="fa fa-times"></i></a></p>';
    }
    $(".userContainer").html(str);
}
//筛选
$('#usernameSearch').bind('input propertychange', function() {
    var userList2 = [];
    var that = $(this);
    if(that.val().trim()===""){
        showUser();
        return;
    }
    setTimeout(function () {
        for(var i=0 ; i<userList.length;i++){
            //有就push进去
            if(userList[i].name.indexOf(that.val().trim())!==-1){
                userList2.push(userList[i]);
            }
        }
        $(".userContainer").html("");
        var str = ""
        for(var j=0 ; j<userList2.length;j++){
            str += '<p id="p'+userList2[j].id+'">'+userList2[j].name+'&nbsp;<a id="a'+userList2[j].id+'"><i class="fa fa-times"></i></a></p>';
        }
        $(".userContainer").html(str);
    },20)
});
//点击添加角色用户
$(".ibox-tools i").click(function () {
    if(selectData===""){
        layer.msg("请先选择角色");
        return;
    }
    var path = $(".path").html();//获取角色类型+角色名称的路径信息
    window.open("./addpeople?path=" + path + "&roleId=" + selectData[5], "授权", "height=560, width=1000, top=200,left=400, toolbar=no, menubar=no, scrollbars=no, resizable=no,location=no,status=no");

});
//
function cateMsg(str) {
    layer.msg(str)
}
