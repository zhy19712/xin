var nodeId; //被点击节点ID
var level;  //节点等级
var setting = {
    async: {
        enable: true,
        autoParam: ["pid","tid"],
        type: "post",
        url: "./index",
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
        onClick: zTreeOnClick
    },
    showLine:true,
    showTitle:true,
    showIcon:false
};
zTreeObj = $.fn.zTree.init($("#ztree"), setting, null);

//点击节点
function zTreeOnClick(event, treeId, treeNode) {
    console.log(treeNode);
    nodeId = treeNode.add_id;
    alreadyRelationModelTable.ajax.url('/modelmanagement/common/datatablesPre.shtml?tableName=model_quality&id='+nodeId+'&model_type=0').load();
    elval();
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


//已关联节点
function getSelectId() {
    
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

//关联模型
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
            layer.msg(res.msg);
        }
    });
});

//解除关联模型
$('.noteverBtn').click(function(){
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
   })
})