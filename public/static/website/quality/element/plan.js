//初始化layui组件
var initUi = layui.use('form','laydate');
var form = layui.form;
var eTypeId ;//工程类型id
var procedure ;//工序id
var division_id;//工程树点击节点id
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
        division_id = treeNode.id;
        $(".imgList").css("display","none");
        tpyeTable();
        tableItemControl.ajax.url("/quality/common/datatablesPre?tableName=norm_materialtrackingdivision&checked=0&en_type=&unit_id=&division_id=").load();
        $("#all_checked_plan").attr("checked",false);
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
                $('#ztreeLayer').hide();
            }else{
                layer.msg('请选择工作项！');
            }
        },
        cancel: function(index, layero){
            layer.close(layer.index);
            $('#ztreeLayer').hide();
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

var tableItem;
//table数据
function tableInfo() {
    tableItem = $('#tableItem').DataTable({
        pagingType: "full_numbers",
        processing: true,
        serverSide: true,
        retrieve: true,
        iDisplayLength:1000,
        "scrollY": "200px",
        "scrollCollapse": "true",
        "paging": "false",
        ajax:{
            'url':'/quality/common/datatablesPre?tableName=quality_unit'
        },
        dom: 'f<".current-path"<"#add.add layui-btn layui-btn-normal layui-btn-sm">>tr',
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
                name: "el_start"
            },
            {
                name: "el_cease"
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
                "targets": [3],
                "render" :  function(data,type,row) {
                    if(data == 0){
                        return '否'
                    }
                    return '是'
                }
            },
            {
                "searchable": false,
                "orderable": false,
                "width":"110px",
                "targets": [9],
                "render" :  function(data,type,row) {
                    var html = "<i class='fa fa-pencil' uid="+ data +" title='编辑' onclick='edit(this)'></i>" ;
                    html += "<i class='fa fa-trash' uid="+ data +" title='删除' onclick='del(this)'></i>" ;
                    return html;
                }
            },
            {
                "searchable": false,
                "orderable": false,
                "targets": [10],
                "visible": false
            },
            {
                "targets": [2],
                "visible": false,
                "searchable": false,
                "orderable": false
            }
        ],
        language: {
            "zeroRecords": "没有找到记录",
            "lengthMenu": "_MENU_ ",
            "info": "第 _PAGE_ 页 ( 总共 _PAGES_ 页 )",
            "infoEmpty": "无记录",
            "search": "搜索",
            "sSearchPlaceholder": "请输入关键字",
            "infoFiltered": "(从 _MAX_ 条记录过滤)",
        },
        "fnInitComplete": function (oSettings, json) {
            //表头固定的滚动条
            $('#unitWork .dataTables_scroll').on('scroll',function(){
                $("#unitWork .dataTables_scrollHead").css("top",$(this).scrollTop())
            });
        },
    });

    // $.datatable({
    //     tableId:'tableItem',
    //     iDisplayLengths:1000,
    //     scrollYs: true,
    //     scrollCollapses: true,
    //     pagings: false,
    //     ajax:{
    //         'url':'/quality/common/datatablesPre?tableName=quality_unit'
    //     },
    //     dom: 'f<".current-path"<"#add.add layui-btn layui-btn-normal layui-btn-sm">>tr',
    //     columns:[
    //         {
    //             name: "serial_number"
    //         },
    //         {
    //             name: "site"
    //         },
    //         {
    //             name: "coding"
    //         },
    //         {
    //             name: "hinge"
    //         },
    //         {
    //             name: "pile_number"
    //         },
    //         {
    //             name: "el_start"
    //         },
    //         {
    //             name: "el_cease"
    //         },
    //         {
    //             name: "start_date"
    //         },
    //         {
    //             name: "completion_date"
    //         },
    //         {
    //             name: "id"
    //         },
    //         {
    //             name: "en_type"
    //         }
    //     ],
    //     columnDefs:[
    //         {
    //             "searchable": false,
    //             "orderable": false,
    //             "targets": [3],
    //             "render" :  function(data,type,row) {
    //                 if(data == 0){
    //                     return '否'
    //                 }
    //                 return '是'
    //             }
    //         },
    //         {
    //             "searchable": false,
    //             "orderable": false,
    //             "targets": [9],
    //             "render" :  function(data,type,row) {
    //                 var html = "<i class='fa fa-pencil' uid="+ data +" title='编辑' onclick='edit(this)'></i>" ;
    //                 html += "<i class='fa fa-trash' uid="+ data +" title='删除' onclick='del(this)'></i>" ;
    //                 return html;
    //             }
    //         },
    //         {
    //             "searchable": false,
    //             "orderable": false,
    //             "targets": [10],
    //             "visible": false
    //         },
    //         {
    //             "targets": [2],
    //             "visible": false,
    //             "searchable": false,
    //             "orderable": false
    //         }
    //     ],
    // });
    // $('.tbcontainer:last-child').remove();
    // $('.dataTables_scrollBody #tableItem').next(".tbcontainer").nextAll().remove();
}
tableInfo();
// setTimeout(function () {
//     $("#tableItem_info").remove();
//     $("#tableItem_paginate").remove();
// },1000)

//声明选中行的name
var idArrName = [];

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
    $('#maBasesItem_wrapper .tbcontainer:last-child').remove();
    var index = layer.open({
        title:'添加施工依据',
        id:'100',
        type:'1',
        area:['1024px','650px'],
        content:$('#maBasesLayer'),
        btn:['保存'],
        success:function () {
            maBasesTable();
            $.fn.dataTable.tables( {visible: true, api: true} ).columns.adjust();
        },
        yes:function () {
            // $('input[name="ma_bases_name"]').val(idArrName);
            console.log(idArr.dedupe()+" 11");
            $('input[name="ma_bases"]').val(idArr.dedupe());
            getMaBasesName(idArr.dedupe());
            layer.close(layer.index);
            $('#maBasesLayer').css("display","none")
        },
        cancel: function(index, layero){
            layer.close(layer.index);
            $('#maBasesLayer').hide();
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

//获取名字
function getMaBasesName(baseId){
    $.ajax({
        type: "post",
        url: "/quality/division/getMabases",
        data: {
            atlas_id:baseId,
        },
        success: function (res) {
            console.log(res)
            if(res.code == 1){
                $('input[name="ma_bases_name"]').val(res.data);
            }else{
                layer.msg(res.msg)
            }
        }
    })
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
        idArrName.push(mapNum+' '+mapName);
        idArr.removalArray();
        idArrName.removalArray();
        // console.log(idArrName)
    }else{
        idArr.remove(id);
        idArrName.remove(mapNum+' '+mapName);
        idArr.removalArray();
        idArrName.removalArray();
        // console.log(idArrName)

        $('#all_checked').prop('checked',false);
    }
}

//去重
Array.prototype.dedupe = function(){
    var res = [];
    var json = {};
    for(var i = 0; i < this.length; i++){
        if(!json[this[i]]){
            res.push(this[i]);
            json[this[i]] = 1;
        }
    }
    return res;
}

Array.prototype.removalArray = function(){
    var newArr = [];
    for (var i = 0; i < this.length; i++) {
        if(newArr.indexOf(this[i]) == -1){  //indexOf 不兼容IE8及以下
            newArr.push(this[i]);
        }
    }
    return newArr;
}

//单选
function getSelectId(that) {
    getId(that);
    console.log(idArr);
}

var mapName;//图名
var mapNum;//图号
//获取施工依据的名字
$("#maBasesItem").delegate("tbody tr","click",function (e) {
    if($(e.target).hasClass("dataTables_empty")){
        return;
    }
    var tableItem = $('#maBasesItem').DataTable();
    $(this).addClass("selectmaBases").siblings().removeClass("selectmaBases");
    selectData = tableItem.row(".selectmaBases").data();//获取选中行数据
    // console.log(selectData[1] +" ------图名");
    // console.log(selectData[2] +" ------图号");
    // console.log(selectData);
    mapName =selectData[1];
    mapNum = selectData[2];
});

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
    $('.tbcontainer:last-child').remove();
    var tableItem = $('#tableItem').DataTable();
    var serial_number_before = $('input[name="serial_number_before"]').val();
    var serial_number_val = $('input[name="serial_number"]').val();
    var serial_number = serial_number_before + '-' + serial_number_val;
    var en_type = $('input[name="en_type"]').attr('id');
    var division_id = window.treeNode.add_id;
    var ma_bases_name = $('input[name="ma_bases_name"]').val();
    var su_basis = $('input[name="su_basis"]').val();
    console.log(ma_bases_name+','+su_basis);
    if (ma_bases_name==''&&su_basis==''){
        layer.msg('施工依据和补充依据二者必须填一个', {time: 3000});
        return false;
    }else {
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
            },
            others:function () {
                $('#unit').css("display","none");
            }
        });
    }
    $('.dataTables_scrollBody #tableItem').next(".tbcontainer").nextAll().remove();

    // $(".dataTables_scrollBody .dataTables_paginate").css("float","none");
    // $(".dataTables_wrapper .dataTables_info").css("float","right");
    // $(".dataTables_wrapper .dataTables_length").css("float","none");
    // $(".dataTables_wrapper .dataTables_scrollBody").css("overflow","initial");
    // $(".dataTables_wrapper .tbcontainer").css("line-height","0px");
    // $(".dataTables_wrapper .tbcontainer").css("position","initial");
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
            $('input[name="ma_bases_name"]').val(res.ma_bases_name);
            $('input[name="pile_number"]').val(res.pile_number);
            $('input[name="quantities"]').val(res.quantities);
            $('input[name="serial_number"]').val(res.serial_number);
            $('input[name="serial_number_before"]').val(res.serial_number_before);
            $('input[name="site"]').val(res.site);
            $('input[name="start_date"]').val(res.start_date);
            $('input[name="su_basis"]').val(res.su_basis);
            $('.dataTables_scrollBody #tableItem').next(".tbcontainer").nextAll().remove();
            var baseId =[];
            var dataId =[];
            var id = $('input[name="ma_bases"]').val();
            baseId.push(id);
            var dataId = baseId[0].split(',');
            console.log(dataId);
            getMaBasesName(dataId)
        }
    });

}

//关闭弹层
$.close({
    formId:'unit',
    others:function(){
        $('#unit').css("display","none");
        layer.closeAll('page');
    }
});
$('.close').click(function () {
    $('#unit')[0].reset();
    $('#unit').css("display","none");
    layer.closeAll('page');
});


//单元工程段号删除
function del(that) {
    var tableItem = $('#tableItem').DataTable();
    $.deleteData({
        tableItem:tableItem,
        that:that,
        ajaxUrl:'../division/delUnit',
        tablePath:'/quality/common/datatablesPre?tableName=quality_unit&id='+ window.nodeId +'',
        others:function () {
            window.rowId = '';
            $(".imgList").css("display","none");
            tpyeTable();
            tableItemControl.ajax.url("/quality/common/datatablesPre?tableName=norm_materialtrackingdivision&checked=0&en_type=&unit_id=&division_id=").load();
            setTimeout(function () {
                $("#all_checked_plan").prop("checked",false);
            },700)
        }
    });
}

//全部展开
$('#openNode').click(function(){
    $.toggle({
        treeId:'ztree',
        state:true
    });
});

/**==========结束初始化 单元工程段号 =============*/

// 控制点的table 表
function tpyeTable() {
    tableItemControl = $('#tableItemControl').DataTable({
        pagingType: "full_numbers",
        processing: true,
        serverSide: true,
        retrieve: true,
        iDisplayLength:1000,
        "scrollY": "200px",
        "scrollCollapse": "true",
        "paging": "false",
        ajax: {
            "url": "/quality/common/datatablesPre?tableName=norm_materialtrackingdivision&checked=0&en_type=&unit_id=&division_id="
        },
        dom: 'tr',
        columns: [
            {
                name: "checked"
            },
            {
                name: "code"
            },
            {
                name: "name"
            },
            {
                name: "id"
            },
            {
                name: "division_id"
            }
        ],
        columnDefs: [
            {
                "targets":[0],
                "searchable": false,
                "orderable": false,
                "render" :  function(data,type,row) {
                    if(data == 0){
                        var html = "<input type='checkbox' name='checkList_plan' class='checkList' checked id='"+row[3]+"' onclick='getSelectIdPlanCheck("+row[3]+",this)'>";
                    }else{
                        var html = "<input type='checkbox' name='checkList_plan' class='checkList' id='"+row[3]+"'  onclick='getSelectIdPlanCheck("+row[3]+",this)'>";
                    }
                    return html;
                }
            },
            {
                "targets": [1]
            },
            {
                "targets": [2]
            },
            {
                "searchable": false,
                "orderable": false,
                "targets": [3],
                "render": function (data, type, row) {
                    var html = "<span style='margin-left: 5px;' onclick='downConFile(" + row[3] + ")'><i title='下载' class='fa fa-download'></i></span>";
                    return html;
                }
            },
            {
                "searchable": false,
                "orderable": false,
                "targets": [4],
                "visible": false
            },
        ],
        language: {
            "zeroRecords": "没有找到记录",
        },
        "fnInitComplete": function (oSettings, json) {
            //表头固定的滚动条
            $('#tableContent .dataTables_scroll').on('scroll',function(){
                $("#tableContent .dataTables_scrollHead").css("top",$(this).scrollTop())
            });
        },
    });
}

//获取选中行ID
var idArrPlan = [];
function getIdPlan(that) {
    var isChecked = $(that).prop('checked');
    var id = $(that).attr('idv');
    var checkedLen = $('input[type="checkbox"][name="checkList_plan"]:checked').length;
    var checkboxLen = $('input[type="checkbox"][name="checkList_plan"]').length;
    if(checkedLen===checkboxLen){
        $('#all_checked_plan').prop('checked',true);
    }else{
        $('#all_checked_plan').prop('checked',false);
    }
    if(isChecked){
        idArrPlan.push(id);
        idArrPlan.removalArray();
    }else{
        idArrPlan.remove(id);
        idArrPlan.removalArray();
        $('#all_checked_plan').prop('checked',false);
    }
}

//单选
function getSelectIdPlan(that) {
    getId(that);
    console.log(idArrPlan);
}

var procedureId; //工序id

//全选 全不选
$("#all_checked_plan").on('click',function () {
    var checked;
    if($(this).is(':checked')){
        $(".checkList").prop("checked",true);
        checked = 0;
    }else{
        $(".checkList").prop("checked",false);
        checked = 1;
    }
    console.log(procedureId)
    $.ajax({
        url: '/quality/element/checkout',
        data: {
            checkall:checked,
            id:'',
            unit_id:selectRow,
            procedureid:(procedureId == undefined || procedureId == "") ? "" : procedureId,
            checked: checked
        },
        type: "POST",
        dataType: "JSON",
        success: function (res) {
            if(procedureId != ''){
                tableItemControl.ajax.url("/quality/common/datatablesPre?tableName=norm_materialtrackingdivision&checked=0&en_type="+eTypeId+"&unit_id="+selectRow+"&division_id="+division_id+"&nm_id="+procedureId).load();
            }
            if(procedureId == undefined || procedureId == ""){
                tpyeTable();
                tableItemControl.ajax.url("/quality/common/datatablesPre?tableName=norm_materialtrackingdivision&checked=0&en_type="+eTypeId+"&unit_id="+selectRow+"&division_id="+division_id).load();
            }
        }
    })
});

var selectData ;//选中的数据流
var eTypeId ;//有字段了再注释
var selectRow ;//单元格选中的id

var tableItemControl;
//点击行获取Id
$("#tableItem").delegate("tbody tr","click",function (e) {
    if($(e.target).hasClass("dataTables_empty")){
        return;
    }
    var tableItem = $('#tableItem').DataTable();
    $(this).addClass("select-color").siblings().removeClass("select-color");
    selectData = tableItem.row(".select-color").data();//获取选中行数据
    console.log(selectData[9] +" ------选中的行id");
    console.log(selectData[10] +" ------选中的行en_typeId");
    console.log(selectData);
    selectRow =selectData[9];
    eTypeId = selectData[10];
    if(eTypeId){
        selfidName(eTypeId);
    }
    //向后台插数据
    insetData(eTypeId);
    if(selectRow != undefined || selectRow != null){
        setTimeout(function () {
            tpyeTable();
            tableItemControl.ajax.url("/quality/common/datatablesPre?tableName=norm_materialtrackingdivision&checked=0&en_type="+eTypeId+"&unit_id="+selectRow+"&division_id="+division_id).load();
            ischeckedBox();
        },900)

    }else{
        alert("获取不到selectRow id!")
    }
    $(".bitCodes").css("display","block");
    $(".listName").css("display","block");
    $("#tableContent .imgList").css('display','block');
    $("#homeWork").css("color","#00c0ef");
    // ischeckedBox()
});

//获取控制点name
function selfidName(id) {
    $.ajax({
        type: "post",
        url: "/quality/element/getProcedures",
        data: {id: id},
        success: function (res) {
            // console.log(res);
            var optionStrAfter = '';
            for(var i = 0;i<res.length;i++) {
                $("#imgListRight").html('');
                controlPointId = res[i].id;
                controlPointName = res[i].name;
                optionStrAfter +=
                    '<a href="javascript:;"  class="imgListStyle" onclick="clickConName('+ res[i].id +')">' +
                        '<img class="imgNone" id="img'+i+'" src="/static/website/elementimg/next.png" alt="箭头">' +
                        '<img src="/static/website/elementimg/procedure.png" alt="工作">' +
                        '<span style="vertical-align: middle">&nbsp; '+res[i].name+'</span>' +
                        '<span style="display: none;">'+res[i].id+'</span>' +
                    '</a>';
            };
            $("#imgListRight").append(optionStrAfter);
            // if($(".imgNone").attr("id") == 'img0'){
            //     $("#img0").css("display","none");
            // }
            $("#tableItemControl_wrapper").css("height","calc(100% - "+$(".imgList").outerHeight()+"px - 39px)");
        }
    })
}

//向后台插入数据
function insetData(eTypeId) {
    $.ajax({
        type: "post",
        url: "/quality/element/insertalldata",
        data: {en_type: eTypeId,division_id:division_id,unit_id:selectRow},
        success: function (res) {
            //什么都不返回说明是正确的
            // console.log(res);
        }
    })
}

//点击置灰
$(".imgList").on("click","a",function () {
    $(this).css("color","#00c0ef").siblings("a").css("color","#333333");
    $("#homeWork").css("color","#333333");
});

//点击作业
$(".imgList").on("click","#homeWork",function () {
    procedureId = '';
    $(".bitCodes").css("display","block");
    $(".mybtn").css("display","none");
    $(".alldel").css("display","none");
    $(this).css("color","#00c0ef").parent("span").next("span").children("a").css("color","#333333");
    // tableItemControl.ajax.url("/quality/common/datatablesPre?tableName=quality_division_controlpoint_relation&division_id="+selectRow).load();
    tableItemControl.ajax.url("/quality/common/datatablesPre?tableName=norm_materialtrackingdivision&checked=0&en_type="+eTypeId+"&unit_id="+selectRow+"&division_id="+division_id).load();
    ischeckedBox();
});

//点击工序控制点名字
function clickConName(id) {
    procedureId = id;
    $(".bitCodes").css("display","none");
    $(".mybtn").css("display","block");
    $(".alldel").css("display","block");
    $("#tableContent .imgList").css('display','block');
    tableItemControl.ajax.url("/quality/common/datatablesPre?tableName=norm_materialtrackingdivision&checked=0&en_type="+eTypeId+"&unit_id="+selectRow+"&division_id="+division_id+"&nm_id="+procedureId).load();
    console.log(id);
    ischeckedBox();
}
//判断是否全选
function ischeckedBox() {
    setTimeout(function () {
        var lock = 1;
        $(".checkList").each(function (i,item) {
            if(!$(item).is(":checked")){
                lock = 0;
                return;
            }
        });
        if( lock == 0){
            $('#all_checked_plan').prop("checked",false);
        }else{
            $('#all_checked_plan').prop("checked",true);
        }
    },1000)
}

//单选的选中或取消 checkBox
function getSelectIdPlanCheck(rowId,that){
    var checked;     //0 为选中  ，1为未选中
    if($(that).is(':checked') == false){
        checked = 1;
    }else if($(that).is(':checked') == true){
        checked = 0;
    }
    $.ajax({
        type: "post",
        url: "/quality/element/checkout",
        data: {
            division_id: division_id,
            id:rowId,
            checked:checked,
            unit_id:selectRow
        },
        success: function (res) {
            //什么都不返回说明是正确的
            console.log(res);
            if(res.msg == "success"){
                if(checked == 1 ){
                    $('#all_checked_plan').prop("checked",false);
                }else{
                    var lock = 1;
                    $(".checkList").each(function (i,item) {
                        if(!$(item).is(":checked")){
                            lock = 0;
                            return;
                        }
                    });
                    if( lock == 0){
                        $('#all_checked_plan').prop("checked",false);
                    }else{
                        $('#all_checked_plan').prop("checked",true);
                    }
                }
                if(procedureId != ''){
                    tableItemControl.ajax.url("/quality/common/datatablesPre?tableName=norm_materialtrackingdivision&checked=0&en_type="+eTypeId+"&unit_id="+selectRow+"&division_id="+division_id+"&nm_id="+procedureId).load();
                }
                if(procedureId == undefined || procedureId == ""){
                    tpyeTable();
                    tableItemControl.ajax.url("/quality/common/datatablesPre?tableName=norm_materialtrackingdivision&checked=0&en_type="+eTypeId+"&unit_id="+selectRow+"&division_id="+division_id).load();
                }
            }else{
                tpyeTable();
                tableItemControl.ajax.url("/quality/common/datatablesPre?tableName=norm_materialtrackingdivision&checked=0&en_type="+eTypeId+"&unit_id="+selectRow+"&division_id="+division_id).load();
            }
        }
    })
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
    download(id,"/quality/element/download")
}