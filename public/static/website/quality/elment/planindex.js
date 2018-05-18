
layui.use(['form', 'layedit', 'laydate', 'element', 'layer'], function(){
    var form = layui.form
        ,layer = layui.layer;
});

/*==========开始初始化工程划分树节点=============*/

var selfid, //选中的tree id
    nodeName, //选中的节点名字
    nodePid,  //选中的节点的pid
    zTreeObj, //树对象
    groupid, //父节点的id
    sNodes, //选中节点
    treeNode;//选中节点

var setting = {
    view: {
        showLine: true, //设置 zTree 是否显示节点之间的连线。
        selectedMulti: false //设置是否允许同时选中多个节点。
    },
    async: {
        enable: true,
        autoParam: ["pId"],
        type: "post",
        url: "/quality/division/index",
        dataType: "json"
    },
    data: {
        simpleData: {
            enable: true,
            idkey: "id",
            pIdKey: "pId",
            rootPId: null
        }
    },
    callback: {
        onClick: this.nodeClick
    }
};
zTreeObj = $.fn.zTree.init($("#ztree"), setting, null);

//点击获取路径
function nodeClick(e, treeId, node) {
    // selectData = "";
    sNodes = zTreeObj.getSelectedNodes()[0];//选中节点
    treeNode = zTreeObj.getSelectedNodes()[0];//选中节点
    console.log(sNodes);
    selfid = zTreeObj.getSelectedNodes()[0].id;//当前id
    nodeName = zTreeObj.getSelectedNodes()[0].name;//当前name
    nodePid = zTreeObj.getSelectedNodes()[0].pId;//当前pid
    console.log(selfid + '---id');
    console.log(nodeName + '---name');
    console.log(nodePid + '---pid');
    var path = sNodes.name; //选中节点的名字
    node = sNodes.getParentNode();//获取父节点
    if (node) {
        //判断是否还有父节点
        while (node) {
            path = node.name + "-" + path;
            node = node.getParentNode();
        }
    } else {
        $(".layout-panel-center .panel-title").text(sNodes.name);
    }
    groupid = sNodes.pId ;//父节点的id
    $(".imgList").css("display","none");
    tableItem.ajax.url("/quality/common/datatablesPre?tableName=quality_unit&id="+selfid).load();
    // tableItem.ajax.url("/quality/common/datatablesPre?tableName=quality_division_controlpoint_relation&division_id=").load();
    $(".mybtn").css("display", "none");

    $.ajax({
        url: "../element/getProcedures",
        type: "post",
        data: {
            id:selfid
        },
        dataType: "json",
        success: function (res) {
            console.log(res)
        }
    });
}

//全部展开
$('#openNode').click(function () {
    zTreeObj.expandAll(true);
});

/*==========结束初始化 工程划分树节点 =============*/

var tableItem = $('#tableItem').DataTable({
    pagingType: "full_numbers",
    retrieve: true,
    processing: true,
    serverSide: true,
    "scrollY": "450px",
    ajax: {
        "url": "/quality/common/datatablesPre?tableName=quality_unit&id="
    },
    dom: 'lf<".current-path"<"#add.add layui-btn layui-btn-normal layui-btn-sm">>tipr',
    columns:[
        {
            name: "serial_number"
        },
        {
            name: "site"
        },
        {
            name: "coding"
        },
        {
            name: "hinge"
        },
        {
            name: "pile_number"
        },
        {
            name: "start_date"
        },
        {
            name: "completion_date"
        },
        {
            name: "id"
        }
    ],
    columnDefs:[
        {
            "searchable": false,
            "orderable": false,
            "targets": [7],
            "render" :  function(data,type,row) {
                var html = "<i class='fa fa-pencil' uid="+ data +" title='编辑' onclick='edit(this)'></i>" ;
                html += "<i class='fa fa-trash' uid="+ data +" title='删除' onclick='del(this)'></i>" ;
                return html;
            }
        }
    ],
    language: {
        "lengthMenu": "_MENU_",
        "zeroRecords": "没有找到记录",
        "info": "第 _PAGE_ 页 ( 总共 _PAGES_ 页 )",
        "infoEmpty": "无记录",
        "search": "搜索：",
        "infoFiltered": "(从 _MAX_ 条记录过滤)",
        "paginate": {
            "sFirst": "<<",
            "sPrevious": "<",
            "sNext": ">",
            "sLast": ">>"
        }
    },
    "fnInitComplete": function (oSettings, json) {
        $('#tableItem_length').insertBefore(".mark");
        $('#tableItem_info').insertBefore(".mark");
        $('#tableItem_paginate').insertBefore(".mark");
    }
});

$('#add').html('新增');

$('#add').click(function () {

    if(window.treeNode.level<3||window.treeNode.type<3){
        layer.msg('请选择分项工程');
        return false;
    }
    if(window.treeNode.type==3){
        if(window.treeNode.en_type==''){
            layer.msg('请选择工程类型');
            return false;
        }
    }
    if(!window.treeNode.id){
        layer.msg('请选择工程节点');
        return false;
    }
    //系统编码
    var add_id = window.treeNode.add_id;
    $.ajax({
        url: "../division/getCodeing",
        type: "post",
        data: {
            add_id:add_id
        },
        dataType: "json",
        success: function (res) {
            $('input[name="coding"]').val(res.codeing);
        }
    });
    //新增弹层
    layer.open({
        type:'1',
        area:['800px','700px'],
        title:'新增',
        content:$('#unit'),
        success:function () {
            //单元工程流水号编码
            $('input[name="serial_number_before"]').val(window.treeNode.d_code);
            $('input[name="en_type"]').val('');
        },
        cancel: function(index, layero){
            $('#unit')[0].reset();
        }
    });
});

layui.use('laydate', function(){
    var laydate = layui.laydate;
    laydate.render({
        elem: '#start_date'
    });
    laydate.render({
        elem: '#completion_date'
    });
});

$('.maBasesBtn').click(function () {
    $('.tbcontainer:last-child').remove();
    layer.open({
        title:'添加施工依据',
        id:'100',
        type:'1',
        area:['1024px','650px'],
        content:$('#maBasesLayer'),
        btn:['保存'],
        success:function () {
            maBasesTable();
        },
        yes:function () {
            $('input[name="ma_bases"]').val(idArr);
            layer.close(layer.index);
        },
        cancel: function(index, layero){
            layer.close(layer.index);
        }
    });
});


/*==========开始初始化工程类型节点=============*/

//工程类型树
var selfidType ,
    nodeNameType ,
    nodePidType ,
    zTreeObjType ,
    sNodesType ;
var typeTreeNode;

//初始化数据的方法
    var settingType = {
        view: {
            showLine: true, //设置 zTree 是否显示节点之间的连线。
            selectedMulti: false //设置是否允许同时选中多个节点。
        },
        async: {
            enable: true,
            autoParam: ["pid"],
            type: "post",
            url: "../division/getEnType",
            dataType: "json",
        },
        data: {
            simpleData: {
                enable: true,
                idkey: "id",
                pIdKey: "pid",
                rootPId: null
            }
        },
        callback: {
            onClick: this.nodeClickType
        }
    };
    zTreeObjType = $.fn.zTree.init($("#typeZtree"), settingType, null);

//点击获取路径
function nodeClickType(e, treeId, node) {
    sNodesType = zTreeObjType.getSelectedNodes()[0];//选中节点
    typeTreeNode = sNodesType;
    selfidType = zTreeObjType.getSelectedNodes()[0].id;//当前id
    nodeNameType = zTreeObjType.getSelectedNodes()[0].name;//当前name
    nodePidType = zTreeObjType.getSelectedNodes()[0].pid;//当前pid
    console.log(selfidType + '---id');
    console.log(nodeNameType + '---name');
    console.log(nodePidType + '---pid');
}



/*==========结束初始化工程类型节点 =============*/



// $.ztree({
//     treeId:'typeZtree',
//     ajaxUrl:'../division/getEnType',
//     type:'GET',
//     zTreeOnClick:function (event, treeId, treeNode){
//         typeTreeNode = treeNode;
//     }
// });

// 是否显示工程类型
function whetherShow() {
    if(window.treeNode.type>2){
        $('#enType').show();
    }else{
        $('#enType').hide();
    }
}

//展示工程类型树
$('.typeZtreeBtn').click(function () {
    layer.open({
        title:'工程类型',
        id:'99',
        type:'1',
        area:['650px','400px'],
        content:$('#ztreeLayer'),
        btn:['保存','关闭'],
        yes:function () {
            //Todo 需要加判断
            // if(!typeTreeNode.isParent){
                $('input[name="en_type"]').val(typeTreeNode.name);
                $('input[name="en_type"]').attr('id',typeTreeNode.id);
                layer.close(layer.index);
            // }else{
            //     layer.msg('请选择工作项！');
            // }
        },
        cancel: function(index, layero){
            layer.close(layer.index);
        }
    });
});



//构建弹层表格
function maBasesTable() {
    var maBasesItem = $('#maBasesItem').DataTable({
        pagingType: "full_numbers",
        retrieve: true,
        processing: true,
        serverSide: true,
        ajax: {
            "url": "/quality/common/datatablesPre?tableName=archive_atlas_cate"
        },
        dom:'lftipr',
        columns:[
            {
                name: "id",
                "render": function(data, type, full, meta) {
                    var ipt = "<input type='checkbox' name='checkList' idv='"+data+"' onclick='getSelectId(this)'>";
                    return ipt;
                },
            },
            {
                name: "picture_number"
            },
            {
                name: "picture_name"
            },
            {
                name: "picture_papaer_num"
            },
            {
                name: "a1_picture"
            },
            {
                name: "design_name"
            },
            {
                name: "check_name"
            },
            {
                name: "examination_name"
            },
            {
                name: "completion_time"
            },
            {
                name: "section"
            },
            {
                name: "paper_category"
            },
        ],
        language: {
            "lengthMenu": "_MENU_",
            "zeroRecords": "没有找到记录",
            "info": "第 _PAGE_ 页 ( 总共 _PAGES_ 页 )",
            "infoEmpty": "无记录",
            "search": "搜索：",
            "infoFiltered": "(从 _MAX_ 条记录过滤)",
            "paginate": {
                "sFirst": "<<",
                "sPrevious": "<",
                "sNext": ">",
                "sLast": ">>"
            }
        },
        // "fnInitComplete": function (oSettings, json) {
        //     $('#tableItem_length').insertBefore(".mark");
        //     $('#tableItem_info').insertBefore(".mark");
        //     $('#tableItem_paginate').insertBefore(".mark");
        // }
    });

    //取消全选的事件绑定
    $("thead tr th:first-child").unbind();

    //删除自构建分页位置
    $('#maBasesLayer').show().find('.tbcontainer').remove();

    //翻页事件
    tableItem.on('draw',function () {
        $('input[type="checkbox"][name="checkList"]').prop("checked",false);
        $('#all_checked').prop('checked',false);
        idArr.length=0;
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

//Todo 需要刷新列表
//单元工程段号新增
$('#saveUnit').click(function () {
    var tableItem = $('#tableItem').DataTable();
    var serial_number_before = $('input[name="serial_number_before"]').val();
    var serial_number_val = $('input[name="serial_number"]').val();
    var serial_number = serial_number_before + '-' + serial_number_val;
    var en_type = $('input[name="en_type"]').attr('id');
    var division_id = window.treeNode.add_id;
    $.submit({
        tableItem:tableItem,
        tablePath:'/quality/common/datatablesPre?tableName=quality_unit',
        formId:'unit',
        ajaxUrl:'../division/editUnit',
        data:{
            serial_number:serial_number,
            en_type:en_type,
            division_id:division_id,
            id:window.rowId
        }
    });
});

//单元工程段号编辑
function edit(that) {
    $.edit({
        that:that,
        formId:'unit',
        ajaxUrl:'../division/editUnit',
        area:['800px','700px'],
        others:function (res) {
            $('input[name="coding"]').val(res.coding);
            $('input[name="completion_date"]').val(res.completion_date);
            $('input[name="create_time"]').val(res.create_time);
            $('input[name="el_cease"]').val(res.el_cease);
            $('input[name="el_start"]').val(res.el_start);
            $('input[name="en_type"]').val(res.en_type_name);
            $('input[name="en_type"]').attr('id',res.en_type);
            $('select[name="hinge"]').val(res.hinge);
            $('input[name="ma_bases"]').val(res.ma_bases);
            $('input[name="pile_number"]').val(res.pile_number);
            $('input[name="quantities"]').val(res.quantities);
            $('input[name="serial_number"]').val(res.serial_number);
            $('input[name="serial_number_before"]').val(res.serial_number_before);
            $('input[name="site"]').val(res.site);
            $('input[name="start_date"]').val(res.start_date);
            $('input[name="su_basis"]').val(res.su_basis);
        }
    });
}

//关闭弹层
$.close({
    formId:'unit'
});

//单元工程段号删除
function del(that) {
    var tableItem = $('#tableItem').DataTable();
    $.deleteData({
        tableItem:tableItem,
        that:that,
        ajaxUrl:'../division/delUnit',
        tablePath:'/quality/common/datatablesPre?tableName=quality_unit&edit_id='+ window.nodeId +''
    });
}


var selectData ;//选中的数据流
var eTypeId ;//有字段了再注释
var selectRow ;//单元格选中的id
//点击行获取Id
$("#tableItem").delegate("tbody tr","click",function (e) {
    if($(e.target).hasClass("dataTables_empty")){
        return;
    }
    $(this).addClass("select-color").siblings().removeClass("select-color");
    selectData = tableItem.row(".select-color").data();//获取选中行数据
    console.log(selectData[7] +" ------选中的行id");
    selectRow = selectData[7];
    // if(eTypeId){
        selfidName(16);
    // }
    if(selectRow != undefined || selectRow != null){
        tableItemControl.ajax.url("/quality/common/datatablesPre?tableName=quality_division_controlpoint_relation&division_id="+selectRow).load();
    }else{
        alert("获取不到selectRow id!")
    }
    $(".bitCodes").css("display","block");
    $(".listName").css("display","block");
    $("#tableContent .imgList").css('display','block');
    $("#homeWork").css("color","#2213e9");
    $.ajax({
        type: "post",
        url: "/quality/element/checkout",
        data: {id: selectRow},
        success: function (res) {
            console.log(res);

        }
    })


});





//组织结构表格
var tableItemControl = $('#tableItemControl').DataTable({
    pagingType: "full_numbers",
    retrieve: true,
    processing: true,
    serverSide: true,
    ajax: {
        "url": "/quality/common/datatablesPre?tableName=quality_division_controlpoint_relation&division_id="
    },
    dom:'rpt',
    columns: [
        {
            name: "code"
        },
        {
            name: "name"
        },
        {
            name: "id"
        }
    ],
    columnDefs: [
        {
            "targets":[0]
        },
        {
            "targets": [1]
        },
        {
            "searchable": false,
            "orderable": false,
            "targets": [2],
            "render": function (data, type, row) {
                var html = "<span style='margin-left: 5px;' onclick='downConFile("+row[2]+")'><i title='下载' class='fa fa-download'></i></span>";
                return html;
            }
        }
    ],
    language: {
        "lengthMenu": "_MENU_",
        "zeroRecords": "没有找到记录",
        "info": "第 _PAGE_ 页 ( 总共 _PAGES_ 页 )",
        "infoEmpty": "无记录",
        "search": "搜索：",
        "infoFiltered": "(从 _MAX_ 条记录过滤)",
        "paginate": {
            "sFirst": "<<",
            "sPrevious": "<",
            "sNext": ">",
            "sLast": ">>"
        }
    },
    // "fnInitComplete": function (oSettings, json) {
    //     $('#tableItem_length').insertBefore(".mark");
    //     $('#tableItem_info').insertBefore(".mark");
    //     $('#tableItem_paginate').insertBefore(".mark");
    // }
});

//获取控制点name
function selfidName(id) {
    $.ajax({
        type: "post",
        url: "/quality/element/getProcedures",
        data: {id: id},
        success: function (res) {
                console.log(res);
                var optionStrAfter = '';
                for(var i = 0;i<res.length;i++) {
                    $("#imgListRight").html('');
                    controlPointId = res[i].id;
                    controlPointName = res[i].name;
                    optionStrAfter +=
                        "<a href=\"javascript:;\"  class=\"imgListStyle\" onclick=\"clickConName("+ res[i].id +")\">" +
                            "<img class='imgNone' id='img"+i+"' src=\"__WEBSITE__/elementimg/right.png\" alt=\"箭头\">" +
                            "<img src=\"__WEBSITE__/elementimg/work.png\" alt=\"工作\">&nbsp;"+res[i].name+"<span style='display: none;'>"+res[i].id+"</span>" +
                        "</a>\n";
                };
                $("#imgListRight").append(optionStrAfter);
                if($(".imgNone").attr("id") == 'img0'){
                    $("#img0").css("display","none");
                }
        }
    })
}

/**==========结束初始化 单元工树 =============*/

//点击置灰
$(".imgList").on("click","a",function () {
    $(this).css("color","#2213e9").siblings("a").css("color","#CDCDCD");
    $("#homeWork").css("color","#CDCDCD");
});

//点击作业
$(".imgList").on("click","#homeWork",function () {
    $(".bitCodes").css("display","block");
    $(".mybtn").css("display","none");
    $(".alldel").css("display","none");
    $(this).css("color","#2213e9").parent("span").next("span").children("a").css("color","#CDCDCD");
    tableItem.ajax.url("{:url('/quality/common/datatablesPre')}?tableName=quality_division_controlpoint_relation&division_id="+selfidUnit).load();
});

//点击工序控制点名字
function clickConName(id) {
    conThisId = id;
    $(".bitCodes").css("display","none");
    $(".mybtn").css("display","block");
    $(".alldel").css("display","block");
    $("#tableContent .imgList").css('display','block');
    tableItem.ajax.url("{:url('/quality/common/datatablesPre')}?tableName=quality_division_controlpoint_relation&division_id="+selfidUnit+"&ma_division_id="+conThisId).load();
    console.log(id);
}

//下载封装的方法
function download(id,url) {
    $.ajax({
        url: url,
        type:"post",
        dataType: "json",
        data:{cpr_id:id},
        success: function (res) {
            if(res.code != 1){
                layer.msg(res.msg);
            }else {
                $("#form_container").empty();
                var str = "";
                str += ""
                    + "<iframe name=downloadFrame"+ id +" style='display:none;'></iframe>"
                    + "<form name=download"+id +" action="+ url +" method='get' target=downloadFrame"+ id + ">"
                    + "<span class='file_name' style='color: #000;'>"+str+"</span>"
                    + "<input class='file_url' style='display: none;' name='cpr_id' value="+ id +">"
                    + "<button type='submit' class=btn" + id +"></button>"
                    + "</form>"
                $("#form_container").append(str);
                $("#form_container").find(".btn" + id).click();
            }

        }
    })
}

//点击下载控制点模板
function downConFile(id) {
    download(id,"{:url('quality/element/download')}")
}

