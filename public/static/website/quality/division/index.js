//初始化layui组件
var initUi = layui.use('form','laydate');
var form = layui.form;
//ctrl
isCtrlDown = false;
$(document).keydown(function (event) {
    var KeyCode = (navigator.appname=="Netscape")?event.which:window.event.keyCode;
    if(KeyCode==17){
        isCtrlDown = true;
    }
});
$(document).keyup(function (event) {
    var KeyCode = (navigator.appname=="Netscape")?event.which:window.event.keyCode;
    if(KeyCode==17){
        isCtrlDown = false;
    }
});
//工程标准及规范树
$.ztree({
    //点击节点
    zTreeOnClick:function (event, treeId, treeNode){
        tableInfo();
        $.clicknode({
            tableItem:tableItem,
            treeNode:treeNode,
            tablePath:'/quality/common/datatablesPre?tableName=quality_unit',
            isLoadPath:false
        });
        getModel(treeNode);
    }
});

//全部展开
$('#openNode').click(function(){
    $.toggle({
        treeId:'ztree',
        state:true
    });
});

//收起所有
$('#closeNode').click(function(){
    $.toggle({
        treeId:'ztree',
        state:false
    });
});

//删除节点
$('#delNode').click(function () {
    $.delnode();
});

//新增节点弹层
$('#addNode').click(function (){
    if(!window.treeNode||window.treeNode.level==0){
        layer.msg('未选择标段');
        return false;
    }
    if(window.treeNode.level>3){
        layer.msg('请在标段上新建');
        return false;
    }
    whetherShow();
    $('input[type="hidden"][name="edit_id"]').val('');
    $('input[type="hidden"][name="add_id"]').val(window.nodeId);
    $.addNode({
        area:['670px','420px'],
        others:function () {
            //构建select
            //type = 1 单位工程 / type = 2 [子单位工程|分部工程] / type = 3 [子分部工程|分项工程]
            var options = [];
            var unitArr = [{type:1,name:"单位工程"}];
            var branchArr = [{type:2,name:"子单位工程"},{type:3,name:"分部工程"}];
            var itemArr = [{type:4,name:"子分部工程"},{type:5,name:"分项工程"},{type:6,name:"单元工程"}];
            if(window.treeNode.level==1){
                options.push('<option value='+ unitArr[0].type +'>'+ unitArr[0].name +'</option>');
            }
            if(window.treeNode.level==2){
                for(var i = 0;i<branchArr.length;i++){
                    options.push('<option value='+ branchArr[i].type +'>'+ branchArr[i].name +'</option>');
                }
            }
            if(window.treeNode.level==3){
                for(var i = 0;i<itemArr.length;i++){
                    options.push('<option value='+ itemArr[i].type +'>'+ itemArr[i].name +'</option>');
                }
            }
            $('select[name="type"]').empty();
            $('select[name="type"]').append(options);

            if(window.treeNode.level>2){
                $('.autograph').show();
            }
            initUi.form.render('select');
        }
    });
});

//工程类型树
var typeTreeNode;
$.ztree({
    treeId:'typeZtree',
    ajaxUrl:'./getEnType',
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
                    html += "<i class='fa fa-qrcode' uid="+ data +" title='二维码' onclick='qrcode(this)'></i>" ;
                    html += "<i class='fa fa-chain' uid="+ data +" title='关联视图' onclick='relation(this)'></i>" ;
                    return html;
                }
            }
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
        url: "./getCodeing",
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
                name: "completion_date"
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
        ajaxUrl:'./editUnit',
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
        ajaxUrl:'./editUnit',
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
        ajaxUrl:'./delUnit',
        tablePath:'/quality/common/datatablesPre?tableName=quality_unit&edit_id='+ window.nodeId +''
    });
}

//生成二维码
function qrcode(that) {
    var id = $(that).attr('uid');
    $.ajax({
        url: "./qrCode",
        type: "post",
        data: {
            id:id
        },
        dataType: "json",
        success: function (res) {
            $('#qrCode').html('<img src="./qrCode/'+id+'">');
            var number = $("#easyuiLayout").layout("panel", "east")[0].clientWidth;
            if(number<=0){
                $('#easyuiLayout').layout('expand','east');
            }
        }
    })
}

//导入弹层
$('#importExcel').click(function () {
    if(!window.treeNode){
        layer.msg('未选择标段');
        return false;
    }
    if(window.treeNode.level!==1){
        layer.msg('只能从标段导入');
        return false;
    }
   var index = layer.open({
        id:'100',
        type:'1',
        area:['500px','170px'],
        title:'数据导入',
        content:$('#importExcelLayer'),
        success:function(){
            $.upload({
                btnId:'#importExcelBtn',
                server: "/quality/Division/importExcel",
                btnText:'选择文件',
                formData:{
                    add_id:''
                },
                accept: {
                    title: 'excel',
                    extensions: 'xls,xlsx',
                    mimeTypes: '.xls,.xlsx'
                },
                uploadSuccess:function (res) {
                    layer.msg(res.msg);
                    if(res.code!=0){
                        layer.close(index);
                    }
                    var zTree = $.fn.zTree.getZTreeObj("ztree");
                    var nodes = zTree.getSelectedNodes();
                    var type = "refresh";
                    var silent = false;
                    zTree.reAsyncChildNodes(null,type, silent,function () {
                        var newNode = zTree.getNodesByParam('add_id',window.treeNode.add_id,null);
                        zTree.expandNode(newNode[0], true, false , false );
                    });

                },
                uploadStart:function(uploader){
                    uploader.options.formData.add_id = window.treeNode.add_id;
                }
            });
            $('#importExcelBtn .webuploader-pick').prepend('<i class="fa fa-plus"></i>');
        },
        cancel: function(index, layero){
            $('#importExcelBtn .webuploader-pick i').remove();
            uploader.destroy();
        }
    });
});

//模板下载
$('#exceldownloadBtn').click(function () {
    $.download({
        url:'./excelDownload'
    })
});

//关联视图新开页
function relation(that) {
    var uid = $(that).attr('uid');
    document.cookie="unitEnginNoId="+uid;
    window.open('./openModelPicture');
}