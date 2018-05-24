var nodeId; //被点击节点ID
var level;  //节点等级

//左侧的树
function ztree(node_type) {
    var setting = {
        async: {
            enable: true,
            autoParam: ["pid","tid"],
            type: "get",
            url: "./index?node_type="+node_type,
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
    nodeId = treeNode.add_id;
    if(treeNode.level==5){
        alreadyRelationModelTable.ajax.url('/modelmanagement/common/datatablesPre.shtml?tableName=model_quality&id='+nodeId+'&model_type=0').load();
        elval();
        nodeModelNumber(treeNode);
    }
}

//显示隐藏模板事件
function zTreeOnCheck(event, treeId, treeNode) {
    nodeModelNumber(treeNode);
}

//显示隐藏模板函数
function nodeModelNumber(treeNode) {
    var add_id = treeNode.add_id;
    $.ajax({
        url: "./nodeModelNumber",
        type: "post",
        data: {
            add_id:add_id
        },
        dataType: "json",
        success: function (res) {
            window.operateModel(res.data);
        }
    });
}

//绘制radio
$('input[name="nodeRelation"],input[name="nodeRelationTab"]').iCheck({
    radioClass: 'iradio_square-green',
    increaseArea: '0'
});

//模型构件列表
var tableItem = $('#tableItem').DataTable({
    pagingType: "full_numbers",
    processing: true,
    serverSide: true,
    ajax: {
        "url": "/modelmanagement/common/datatablesPre.shtml?tableName=model_quality_search"
    },
    dom: '<".noteverBtn layui-btn layui-btn-sm layui-btn-normal btn-right"><".alreadyBtn layui-btn layui-btn-sm layui-btn-normal btn-right table-btn">rtip',
    columns: [
        {
            name: "id",
            "render": function(data, type, full, meta) {
                var ipt = "<input type='checkbox' name='checkList' idv="+ data +" unit="+ full[1] +" nickname="+ full[2] +" onclick='getSelectId(this)'>";
                return ipt;
            },
        },
        {
            name: "section"
        },
        {
            name: "unit"
        },
        {
            name: "parcel"
        },
        {
            name: "cell"
        },
        {
            name: "pile_number_1"
        },
        {
            name: "pile_val_1"
        },
        {
            name: "pile_number_2"
        },
        {
            name: "pile_val_2"
        },
        {
            name: "pile_number_3"
        },
        {
            name: "pile_val_3"
        },
        {
            name: "pile_number_4"
        },
        {
            name: "pile_val_4"
        },
        {
            name: "el_start"
        },
        {
            name: "el_cease"
        },
        {
            name: "site"
        },
        {
            name: "uid"
        }
    ],
    columnDefs: [
        {
            "searchable": false,
            "orderable": false,
            "targets": [15],
            "render" :  function(data,type,row) {
                var name = row[15];  //单元工程名称
                var uid = row[16];  //单元工程编号
                var html =  "<span id='nodeName' class='node-name' onclick='clickTree(this)' uid="+ uid +">"+ name +"</span>";
                return html;
            }
        },
        {
            "searchable": false,
            "orderable": false,
            "targets": [16],
            "render" :  function(data,type,row) {
                var uid = row[16];  //单元工程编号
                var html =  "<input id='uid' type='hidden' value="+ uid +">" ;
                return html;
            }
        }
    ],
    language: {
        "sProcessing":"数据加载中...",
        "lengthMenu": "_MENU_",
        "zeroRecords": "没有找到记录",
        "info": "第 _PAGE_ 页 ( 总共 _PAGES_ 页 )",
        "infoEmpty": "无记录",
        "search": "搜索",
        "infoFiltered": "(从 _MAX_ 条记录过滤)",
        "paginate": {
            "sFirst": "<<",
            "sPrevious": "<",
            "sNext": ">",
            "sLast": ">>"
        }
    }
});

//已关联模型表
var alreadyRelationModelTable = $('#alreadyRelationModelTable').DataTable({
    pagingType: "full_numbers",
    processing: true,
    serverSide: true,
    ajax: {
        "url": "/modelmanagement/common/datatablesPre.shtml?tableName=model_quality"
    },
    dom: '',
    columns: [
        {
            name: "id",
        },
        {
            name: "section"
        },
        {
            name: "unit"
        },
        {
            name: "parcel"
        },
        {
            name: "cell"
        },
        {
            name: "pile_number_1"
        },
        {
            name: "pile_val_1"
        },
        {
            name: "pile_number_2"
        },
        {
            name: "pile_val_2"
        },
        {
            name: "pile_number_3"
        },
        {
            name: "pile_val_3"
        },
        {
            name: "pile_number_4"
        },
        {
            name: "pile_val_4"
        },
        {
            name: "el_start"
        },
        {
            name: "el_cease"
        },
        {
            name: "site"
        },
        {
            name: "uid"
        }
    ],
    columnDefs: [
        {
            "searchable": false,
            "orderable": false,
            "targets": [15],
            "render" :  function(data,type,row) {
                var name = row[15];  //单元工程名称
                var uid = row[16];  //单元工程编号
                var html =  "<span id='nodeName' class='node-name' onclick='clickTree(this)' uid="+ uid +">"+ name +"</span>";
                return html;
            }
        },
        {
            "searchable": false,
            "orderable": false,
            "targets": [16],
            "render" :  function(data,type,row) {
                var uid = row[16];  //单元工程编号
                var html =  "<input id='uid' type='hidden' value="+ uid +">" ;
                return html;
            }
        }
    ],
    language: {
        "sProcessing":"数据加载中...",
        "lengthMenu": "_MENU_",
        "zeroRecords": "没有找到记录",
        "info": "第 _PAGE_ 页 ( 总共 _PAGES_ 页 )",
        "infoEmpty": "无记录",
        "search": "搜索",
        "infoFiltered": "(从 _MAX_ 条记录过滤)",
        "paginate": {
            "sFirst": "<<",
            "sPrevious": "<",
            "sNext": ">",
            "sLast": ">>"
        }
    }
});

//取消全选的事件绑定
$("thead tr th:first-child").unbind();

//设置按钮文自
$('.alreadyBtn').html('关联');
$('.noteverBtn').html('解除关联');

//起止高程和桩号的值
function elval() {
    $.ajax({
        url: "./elVal",
        type: "post",
        data: {
            add_id:nodeId
        },
        dataType: "json",
        success: function (res) {
            $('#tableItem_wrapper').find('div#modelInfo').remove();
            //起止高程/起止桩号模板
            var Template =  '<div id="modelInfo">' +
                '<p>起止高程：' +
                '<span>'+ res.el_val +'</span>' +
                '</p>' +
                '<p>起止桩号：' +
                '<span>'+ res.pile_number +'</span>' +
                '</p>' +
                '</div>';
            $('#tableItem_wrapper').prepend(Template);
        }
    });
}

//获取选中行ID
var idArr = [];
function getId(that) {
    var isChecked = $(that).prop('checked');
    var id = $(that).attr('idv');
    var checkedLen = $('input[type="checkbox"][name="checkList"]:checked').length;
    var checkboxLen = $('input[type="checkbox"][name="checkList"]').length;
    if(checkedLen===checkboxLen){
        $('#all_checked').prop('checked',true);
    }else{
        $('#all_checked').prop('checked',false);
    }
    if(isChecked){
        idArr.push(id);
        idArr.removalArray();
    }else{
        idArr.remove(id);
        idArr.removalArray();
        $('#all_checked').prop('checked',false);
    }
}

//单选
function getSelectId(that) {
    getId(that);
    console.log(idArr);
}

//checkbox全选
$("#all_checked").on("click", function () {
    var that = $(this);
    if (that.prop("checked") === true) {
        $("input[name='checkList']").prop("checked", that.prop("checked"));
        $('#tableItem tbody tr').addClass('selected');
        $('input[name="checkList"]').each(function(){
            getId(this);
        });
    } else {
        $("input[name='checkList']").prop("checked", false);
        $('#tableItem tbody tr').removeClass('selected');
        $('input[name="checkList"]').each(function(){
            getId(this);
        });
    }
    console.log(idArr);
});

//关联构件
$('.alreadyBtn').click(function(){
    if(!nodeId){
        layer.msg('请选择单元工程');
        return false;
    }
    $.ajax({
        url: "./relevance",
        type: "post",
        data: {
            add_id:nodeId,
            id_arr:idArr
        },
        dataType: "json",
        success: function (res) {
            tableItem.ajax.url('/modelmanagement/common/datatablesPre.shtml?tableName=model_quality_search').load();
            layer.msg(res.msg);
        }
    });
});

//选中的构件 --  解除关联
$('.noteverBtn').click(function(){
    layer.confirm('确定解除该关联模型?', {icon: 3, title:'提示'}, function(index){
        $.ajax({
            url: "./removeRelevance",
            type: "post",
            data: {
                id_arr:idArr
            },
            dataType: "json",
            success: function (res) {
                layer.msg(res.msg);
            }
        });
        layer.close(index);
    });

});

//筛选已关联树节点
$('#already').on('ifChecked', function(event){
    screenNode(1);
});

//筛选未关联树节点
$('#notever').on('ifChecked', function(event){
    screenNode(2);
});

//筛选是否关联模型单元工程节点
function screenNode(node_type) {
    $.ajax({
        url: "./index",
        type: "get",
        data: {
            node_type:node_type
        },
        dataType: "json",
        success: function (res) {
            ztree(node_type);
        }
    });
}

//选中的节点 -- 解除关联
$('#relieveBtn').click(function(){
    if(!nodeId){
        layer.msg('请选择单元工程');
        return false;
    }
    layer.confirm('确定解除该单元工程的关联模型?', {icon: 3, title:'提示'}, function(index){
        $.ajax({
            url: "./removeRelevanceNode",
            type: "post",
            data: {
                add_id:nodeId
            },
            dataType: "json",
            success: function (res) {
                layer.msg(res.msg);
            }
        });
        layer.close(index);
    });
});

//筛选已关联构件
$('#alreadyTab').on('ifChecked', function(event){
    model_quality(1);
});
//筛选未关联构件
$('#noteverTab').on('ifChecked', function(event){
    model_quality(2);
});
//筛选方法
function model_quality(model_type) {
    tableItem.ajax.url('/modelmanagement/common/datatablesPre.shtml?tableName=model_quality&model_type='+model_type).load();
}

//点击已关联节点选中树中对应的节点
function clickTree(that) {
    var uid = $(that).attr('uid');
    var treeObj = $.fn.zTree.getZTreeObj("ztree");
    var nodes = treeObj.getNodesByParam("add_id", uid, null);
    treeObj.selectNode(nodes[0]);
}

//获取选中节点的所有关联模型编号