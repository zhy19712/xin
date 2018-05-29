var nodeId; //被点击节点ID
var level;  //节点等级
var node_type;  //节点类型

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
        view:{
            showLine:true,
            showTitle:true,
            showIcon:false
        }
    };
    zTreeObj = $.fn.zTree.init($("#ztree"), setting, null);
}
ztree(0);

//点击节点
function zTreeOnClick(event, treeId, treeNode) {
    nodeId = treeNode.add_id;
    node_type = treeNode.node_type;
    console.log(treeNode);
    if(treeNode.level==5){
        alreadyRelationModelTable.ajax.url('/modelmanagement/common/datatablesPre.shtml?tableName=model_quality&id='+nodeId+'&model_type=0').load();
        elval();
        var data = nodeModelNumber();
        //透明未关联构件
        window.opacityModel();
        //展示关联构件
        window.operateModel(data);
    }
}

//显示隐藏模板事件
function zTreeOnCheck(event, treeId, treeNode) {
    nodeId = treeNode.add_id;
    node_type = treeNode.node_type;
    var data = nodeModelNumber();
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
        url: "./nodeModelNumber",
        type: "post",
        async:false,
        data: {
            number:nodeId,
            number_type:1
        },
        dataType: "json",
        success: function (res) {
            result = res.data;
        }
    });
    return result;
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
    "scrollX": true,
    "scrollY": "200px",
    "scrollCollapse": "true",
    "paging": "false",
    ajax: {
        "url": "/modelmanagement/common/datatablesPre.shtml?tableName=model_quality_search"
    },
    dom: 'l<".noteverBtn layui-btn layui-btn-warm layui-btn-normal btn-right"><".alreadyBtn layui-btn layui-btn-normal btn-right table-btn">rtip',
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
                var newName = name==null?'无':name;
                var html =  "<span id='nodeName' class='node-name' onclick='clickTree(this)' uid="+ uid +">"+ newName +"</span>";
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
            "sPrevious": "上一页",
            "sNext": "下一页",
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
            var Template =  '<div id="modelInfo" class="model-info">' +
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
    console.log(nodes);
    var tId = nodes[0].tId;
    $('#'+tId+'_span').click();
}

/*//刷新已关联模型
$('#modelTable').tabs({
    onSelect: function(title,index){
        console.log(index);
        if(index==0){
            tableItem.ajax.url('/modelmanagement/common/datatablesPre.shtml?tableName=model_quality_search').load();
        }
        if(index==1){
            alreadyRelationModelTable.ajax.url('/modelmanagement/common/datatablesPre.shtml?tableName=model_quality&id='+nodeId+'&model_type=0').load();
        }
    }
});*/




//构建查询表格模板
var firstTrTemp = [];
firstTrTemp.push('<tr class="search-tr" id="searchTr">');
firstTrTemp.push('<td></td>');
for(var i = 0;i<15;i++){
    firstTrTemp.push('<td id="searchTd'+i+'">');
    firstTrTemp.push('</td>');
}
firstTrTemp.push('</tr>');
$('.dataTables_scrollHeadInner table').append(firstTrTemp.join(''));

//获取下拉列表的值
function dropDown(type,eId) {
    $.ajax({
        url: "./dropDown",
        type: "post",
        data: {
            type:type
        },
        dataType: "json",
        success: function (res) {
            var data = res.data;
            //构建select
            var selectTemp = [];
            selectTemp.push('<select onchange="change(this)">');
            selectTemp.push('<option>请选择</option>');
            for(var j = 0;j<data.length;j++){
                selectTemp.push('<option>');
                selectTemp.push(data[j][type]);
                selectTemp.push('</option>');
            }
            selectTemp.push('</select>');
            $('#searchTr td#'+eId).append(selectTemp.join(''));
        }
    });
}

//构建input
function inputTemp() {
    var ipt = '<input type="text" onchange="change(this)">';
    $('#searchTd5,#searchTd7,#searchTd9,#searchTd11,#searchTd12,#searchTd13').append(ipt);
}
inputTemp();

//给下拉列表赋值
$('#searchTr td').each(function () {
    if($(this).attr('id')=='searchTd0'){
        dropDown('section','searchTd0');
    }
    if($(this).attr('id')=='searchTd1'){
        dropDown('unit','searchTd1');
    }
    if($(this).attr('id')=='searchTd2'){
        dropDown('parcel','searchTd2');
    }
    if($(this).attr('id')=='searchTd3'){
        dropDown('cell','searchTd3');
    }
    if($(this).attr('id')=='searchTd4'){
        dropDown('pile_number_1','searchTd4');
    }
    if($(this).attr('id')=='searchTd6'){
        dropDown('pile_number_2','searchTd6');
    }
    if($(this).attr('id')=='searchTd8'){
        dropDown('pile_number_3','searchTd8');
    }
    if($(this).attr('id')=='searchTd10'){
        dropDown('pile_number_4','searchTd10');
    }
});


//筛选方法
var dataArr = [];
function change(that) {
    $('#searchTr select').each(function (i,n) {
        var val = $(n).find('option').val();
        if(val=='请选择'){
           $(n).find('option:first-child').val('');
        }
    });
    var section = $('#searchTd0 select option:selected').val();
    var unit = $('#searchTd1 select option:selected').val();
    console.log(unit);
    var parcel = $('#searchTd2 select option:selected').val();
    var cell = $('#searchTd3 select option:selected').val();
    var pile_number_1 = $('#searchTd4 select option:selected').val();
    var pile_val_1 = $('#searchTd5 input').val();
    var pile_number_2 = $('#searchTd6 select option:selected').val();
    var pile_val_2 = $('#searchTd7 input').val();
    var pile_number_3 = $('#searchTd8 select option:selected').val();
    var pile_val_3 = $('#searchTd9 input').val();
    var pile_number_4 = $('#searchTd10 select option:selected').val();
    var pile_val_4 = $('#searchTd11 input').val();
    var el_start = $('#searchTd12 input').val();
    var el_cease = $('#searchTd13 input').val();



//模型关联筛选
var data = '&section='+section+'&unit='+unit+'&parcel='+parcel+'&cell='+cell+'&pile_number_1='+pile_number_1+
    '&pile_val_1='+pile_val_1+'&pile_number_2='+pile_number_2+'&pile_val_2='+pile_val_2+'&pile_number_3='+pile_number_3+
    '&pile_val_3='+pile_val_3+'&pile_number_4='+pile_number_4+'&pile_val_4='+pile_val_4+'&el_start='+el_start+'&el_cease='+el_cease+'';
    tableItem.ajax.url('/modelmanagement/common/datatablesPre.shtml?tableName=model_quality_search'+data).load();
/*
    $.ajax({
        url: "/modelmanagement/common/model_quality_search",
        type: "post",
        data: {
            section:section,
            unit:unit,
            parcel:parcel,
            cellcell:cellcell,
            pile_number_1:pile_number_1,
            pile_val_1:pile_val_1,
            pile_number_2:pile_number_2,
            pile_val_2:pile_val_2,
            pile_number_3:pile_number_3,
            pile_val_3:pile_val_3,
            pile_number_4:pile_number_4,
            pile_val_4:pile_val_4,
            el_start:el_start,
            el_cease:el_cease
        },
        dataType: "json",
        success: function (res) {
            $('#searchTr select').each(function (i,n) {
                var val = $(n).val();
                if(val==''){
                    $(n).val('请选择');
                }
            });
        }
    });*/
}

