//初始化layui组件
var initUi = layui.use('form','laydate');
var form = layui.form;
var eTypeId ;//工程类型id
//工程标准及规范树
$.ztree({
    //点击节点
    ajaxUrl:'../division/index',
    zTreeOnClick:function (event, treeId, treeNode){
        tableInfo();
        $.clicknode({
            tableItem:tableItem,
            treeNode:treeNode,
            tablePath:'/quality/common/datatablesPre?tableName=quality_unit',
            isLoadPath:false
        });
    }
});

//工程类型树
var typeTreeNode;
$.ztree({
    treeId:'typeZtree',
    ajaxUrl:'../division/getEnType',
    type:'GET',
    zTreeOnClick:function (event, treeId, treeNode){
        typeTreeNode = treeNode;
    }
});

//是否显示工程类型
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
            if(!typeTreeNode.isParent){
                $('input[name="en_type"]').val(typeTreeNode.name);
                $('input[name="en_type"]').attr('id',typeTreeNode.id);
                layer.close(layer.index);
            }else{
                layer.msg('请选择工作项！');
            }
        },
        cancel: function(index, layero){
            layer.close(layer.index);
        }
    });
});

//编辑节点
$('#editNode').click(function () {
    if(!window.treeNode||window.treeNode.level==0){
        layer.msg('未选择标段');
        return false;
    }
    whetherShow();
    $('input[type="hidden"][name="add_id"]').val('');
    $('input[type="hidden"][name="edit_id"]').val(window.nodeId);
    $.editNode({
        area:['670px','420px'],
        data:{
            edit_id:window.nodeId
        },
        others:function (res) {
            $('input[name="d_name"]').val(res.d_name);
            $('select[name="type"] option:selected').val(res.type);
            if(res.primary==1){
                $('input[name="primary"]').attr('checked',true);
            }else{
                $('input[name="primary"]').attr('checked',false);
            }
            $('input[name="en_type"]').val(res.en_type_name).attr('id',res.en_type);
            $('input[name="d_code"]').val(res.d_code);
            $('textarea[name="remark"]').val(res.remark);
        }
    });
});

//关闭弹层
$.close({
    formId:'nodeForm'
});

//开关
layui.use(['layer', 'form'], function(){
    var form = layui.form;
    form.on('switch(toggle)', function(data){
        if(data.elem.checked==1){
            $('input[name="primary"]').val(1);
        }else{
            $('input[name="primary"]').val(0);
        }
    });
});

//提交节点变更
$('#save').click(function () {
    var add_id = $('input[type="hidden"][name="add_id"]').val();
    var edit_id = $('input[type="hidden"][name="edit_id"]').val();
    var d_code = $('input[name="d_code"]').val();
    var d_name = $('input[name="d_name"]').val();
    var type = $('select[name="type"] option:selected').val();
    var primary = $('input[name="primary"]').val();
    var en_type = $('input[name="en_type"]').attr('id');
    var remark = $('textarea[name="remark"]').val();
    if(window.treeNode.level>0){
        var section_id = window.treeNode.section_id;
    }
    $.submitNode({
        data:{
            d_code:d_code,
            d_name:d_name,
            type:type,
            primary:primary,
            remark:remark,
            section_id:section_id,
            en_type:en_type,
            add_id:add_id,
            edit_id:edit_id
        },
        others:function (res) {
            if(edit_id!=''&&res.code!=-1){
                $('#'+window.treeNode.tId+'_span').html(d_name);
                window.treeNode.en_type = en_type;
            }
        }
    });
});

//table数据
function tableInfo() {
    $.datatable({
        tableId:'tableItem',
        ajax:{
            'url':'/quality/common/datatablesPre?tableName=quality_unit'
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
            },
            {
                name: "en_type"
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
            },
            {
                "searchable": false,
                "orderable": false,
                "targets": [8],
                "visible": false
            },
        ],
    });
}
tableInfo();
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
    $.add({
        formId:'unit',
        area:['800px','700px'],
        success:function () {
            //单元工程流水号编码
            $('input[name="serial_number_before"]').val(window.treeNode.d_code);
            $('input[name="en_type"]').val('');
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

//构建弹层表格
function maBasesTable() {
    $.datatable({
        tableId:'maBasesItem',
        ajax:{
            'url':'/quality/common/datatablesPre?tableName=archive_atlas_cate'
        },
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
            $('input[name="completion_date"]').val(res.completion_time);
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


//全部展开
$('#openNode').click(function(){
    $.toggle({
        treeId:'ztree',
        state:true
    });
});
var selectData ;//选中的数据流
var eTypeId ;//有字段了再注释
var selectRow ;//单元格选中的id


// //组织结构表格
// function tablecon(){
//     $.datatable({
//         tableId:'tableItemControl',
//         ajax:{
//             'url':'/quality/common/datatablesPre?tableName=quality_division_controlpoint_relation&division_id='
//         },
//         columns: [
//             {
//                 name: "code"
//             },
//             {
//                 name: "name"
//             },
//             {
//                 name: "id"
//             }
//         ],
//         columnDefs: [
//             {
//                 "targets":[0]
//             },
//             {
//                 "targets": [1]
//             },
//             {
//                 "searchable": false,
//                 "orderable": false,
//                 "targets": [2],
//                 "render": function (data, type, row) {
//                     var html = "<span style='margin-left: 5px;' onclick='downConFile("+row[2]+")'><i title='下载' class='fa fa-download'></i></span>";
//                     return html;
//                 }
//             }
//         ],
//     });
// }
// tablecon()


//点击行获取Id
$("#tableItem").delegate("tbody tr","click",function (e) {
    if($(e.target).hasClass("dataTables_empty")){
        return;
    }
    $(this).addClass("select-color").siblings().removeClass("select-color");
    selectData = tableItem.row(".select-color").data();//获取选中行数据
    console.log(selectData[7] +" ------选中的行id");
    // console.log(selectData);
    selectRow = selectData[7];
    eTypeId = selectData[8];
    if(eTypeId){
        selfidName(eTypeId);
    }
    if(selectRow != undefined || selectRow != null){

        var tableItemControl = $('#tableItemControl').DataTable({
            pagingType: "full_numbers",
            retrieve: true,
            processing: true,
            serverSide: true,
            ajax: {
                "url": "/quality/common/datatablesPre')}?tableName=quality_division_controlpoint_relation&division_id="
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
        // tableItemControl.ajax.url("/quality/common/datatablesPre?tableName=quality_division_controlpoint_relation&division_id="+selectRow).load();
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
                    "<img class='imgNone' id='img"+i+"' src=\"../../public/static/website/elementimg/right.png\" alt=\"箭头\">" +
                    "<img src=\"/elementimg/work.png\" alt=\"工作\">&nbsp;"+res[i].name+"<span style='display: none;'>"+res[i].id+"</span>" +
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