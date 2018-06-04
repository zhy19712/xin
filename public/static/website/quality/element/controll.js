
var tableItem,      //控制点的table
    implementation, //扫描件回传table
    imageData,      //附件资料的table
    onlineFill,     //在线填报的table
    type = 1,       //区分附件资料与扫描件回传的表格
    autoHeight,     //自动获取页面高度
    resources;      //向提交页面之前放置值

tableItem = $('#tableItem').DataTable({
    retrieve: true,
    processing: true,
    serverSide: true,
    iDisplayLength:1000,
    "scrollY": "200px",
    "scrollCollapse": "true",
    "paging": "false",
    ajax: {
        // "url": "/quality/common/datatablesPre?tableName=quality_division_controlpoint_relation&division_id=" //老的
        "url": "/quality/common/datatablesPre?tableName=norm_materialtrackingdivision&checked_gk=0&en_type=&unit_id=&division_id="
    },
    dom: 'rt',
    columns: [
        {
            name: "code"
        },
        {
            name: "name"
        },
        {
            name: "status"
        },
        {
            //当前这条数据的id
            name: "id"
        },
        {
            //控制点id
            name: "control_id"
        },
        // {
        //     name: "remark"
        // }
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
                if(data == 0){
                    return '<span style="color:red">未执行</span>'
                }
                return '已执行'
            }
        },
        {
            "searchable": false,
            "orderable": false,
            "targets": [3],
            "render": function (data, type, row) {
                var html = "<a class='faStyle' title='下载' onclick='downConFile("+row[3]+")'><i class='fa fa-download'></i></a>";
                // html += "<span style='margin-left: 5px;' onclick='printFile("+row[3]+")'><i title='打印' class='fa fa-print'></i></span>";
                // html += "<span style='margin-left: 5px;' onclick='printConFile("+row[3]+")'><i title='打印' class='fa fa-print'></i></span>";
                return html;
            }
        },
        {
            "targets": [4],
            "searchable": false,
            "visible": false
        },
    ],
    language: {
        "zeroRecords": "没有找到记录"
    }
});

//查询提交
var layer = layui.layer;
layui.use(['form', 'layedit', 'laydate', 'element', 'layer'], function(){
    var form = layui.form
        ,layer = layui.layer
        ,laydate = layui.laydate;
});


/*==========开始初始化工程划分树节点=============*/
var nodeId,     //选中的节点id
    nodeName,   //选中的节点名字
    nodePid,    //选中的节点的pid
    zTreeObj,   //选中的节点的根对象
    groupId,    //选中的节点的父节点的id
    sNodes;     //选中的节点的对象数据

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
    $("#removeCon").remove();//删出静态页面的没有找到记录
    selectData = "";
    sNodes = zTreeObj.getSelectedNodes()[0];//选中节点
    nodeId = zTreeObj.getSelectedNodes()[0].id;//当前id
    nodeName = zTreeObj.getSelectedNodes()[0].name;//当前name
    nodePid = zTreeObj.getSelectedNodes()[0].pId;//当前pid
    console.log(sNodes);
    console.log(nodeId + '---id');
    // console.log(nodeName + '---name');
    // console.log(nodePid + '---pid');
    var path = sNodes.name; //选中节点的名字
    node = sNodes.getParentNode();//获取父节点
    groupId = sNodes.pId ;//父节点的id
    if (node) {
        //判断是否还有父节点
        while (node) {
            path = node.name + "-" + path;
            node = node.getParentNode();
        }
    } else {
        $(".layout-panel-center .panel-title").text(sNodes.name);
    }

    $(".imgList").css("display","none");
    // tableItem.ajax.url("/quality/common/datatablesPre?tableName=quality_division_controlpoint_relation&division_id=").load();
    tableItem.ajax.url("/quality/common/datatablesPre?tableName=norm_materialtrackingdivision&checked_gk=0&en_type=&unit_id=&division_id=").load();
    implementation.ajax.url("/quality/common/datatablesPre?tableName=quality_upload&type=1&cpr_id=").load();
    imageData.ajax.url("/quality/common/datatablesPre?tableName=quality_upload&type=4&cpr_id=").load();
    console.log(controlRowId)
    console.log(procedureId)

    controlRowId ='';
    procedureId ='';
    if(controlRowId == '' || procedureId == '') {
        $(".mybtnAdd").css("display", "none");
        // onlineFill = $("#onlineFill").dataTable().fnDestroy(true);
        $('#onlineFillParent').html('<table id="onlineFill" class="table table-striped table-bordered" cellspacing="0" width="100%">' +
            '<thead>' +
                '<tr style="text-align: center;border-bottom:2px solid #111;border-top: 1px solid #cecece;">' +
                    '<th style="padding:10px 18px;font-weight: bold;">填报人</th>' +
                    '<th style="padding:10px 18px;font-weight: bold;">填报日期</th>' +
                    '<th style="padding:10px 18px;font-weight: bold;">当前审批人</th>' +
                    '<th style="padding:10px 18px;font-weight: bold;">审批状态</th>' +
                    '<th style="padding:10px 18px;font-weight: bold;">操作</th>' +
                    '<th style="display: none;">当前审批人Id</th>' +
                '</tr>' +
                '<tr class="delElement" style="text-align: center">' +
                    '<td colspan="5" style="padding:10px 18px;">没有找到记录</td>' +
                '</tr>' +
            '</thead>' +
        '</table>');
    }
    $(".mybtn").css("display", "none");
    $(".bitCodes").css("display","none");
    $(".mybtnAdd").css("display","none");

    initData(nodeId);//调用单元工
}

//全部展开
$('#openNode').click(function () {
    zTreeObj.expandAll(true);
});

//收起所有
$('#closeNode').click(function () {
    zTreeObj.expandAll(false);
});

/*==========结束初始化 工程划分树节点 =============*/



/**==========开始初始化 单元工树 =================*/
var nodeUnitId ,        //单元工程段号 id
    nodeNameUnit ,      //单元工程名字
    nodePidUnit ,       //单元工程名字 pid
    zTreeObjUnit ,      //单元工程树对象
    groupIdUnit ,       //单元工程蒂点击节点的父节点id
    sNodesUnit ,        //选中节点对象的数据
    eTypeId ,           //工程划分里面的工程类型里面的 单元id (这个id 下面包含着所以的工序id以及下面的控制点id)
    procedureId = '',   //点击工序id
    selectData ,        //点击控制点的行，获取选中该行数据
    controlRowId = '';  //点击控制点表的行id  关联表ID
var controlId;          //控制点id

var result = [];
//名字拼接过滤方法
function ajaxDataFilter(treeId, parentNode, responseData) {
    if (responseData) {
        for(var i =0; i < responseData.length; i++) {
            responseData[i].name = responseData[i].el_start + responseData[i].el_cease + responseData[i].pile_number + responseData[i].site;
        }
    }
    return responseData;
};

//初始化数据的方法
function initData(nodeId){
    var settingUnit = {
        view: {
            showLine: true, //设置 zTree 是否显示节点之间的连线。
            selectedMulti: false //设置是否允许同时选中多个节点。
        },
        async: {
            enable: true,
            autoParam: ["pid"],
            type: "post",
            url: "/quality/element/getDivisionUnitTree?id="+nodeId,
            dataType: "json",
            dataFilter: ajaxDataFilter
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
            onClick: this.nodeClickUnit
        }
    };
    zTreeObjUnit = $.fn.zTree.init($("#ztreeUnit"), settingUnit, null);
}

//控制控制点的显示的开关
var flag = true;
//点击获取路径
function nodeClickUnit(e, treeId, node) {
    selectData = "";
    sNodesUnit = zTreeObjUnit.getSelectedNodes()[0];//选中节点
    nodeUnitId = sNodesUnit.id;//当前id
    nodeNameUnit = sNodesUnit.name;//当前name
    nodePidUnit = sNodesUnit.pid;//当前pid
    eTypeId = sNodesUnit.en_type;//当前en_type
    console.log(sNodesUnit);
    console.log(nodeUnitId + '---nodeUnitId');
    // console.log(nodeNameUnit + '---name');
    // console.log(nodePidUnit + '---pid');
    console.log(eTypeId+"---eTypeId")
    if(eTypeId){
        selfidName(eTypeId);
    }
    if(nodeUnitId != undefined || nodeUnitId != null){
        tableItem.ajax.url("/quality/common/datatablesPre?tableName=norm_materialtrackingdivision&checked_gk=0&en_type="+eTypeId+"&unit_id="+nodeUnitId+"&division_id="+nodeId).load();
    }
    $("#tableContent .imgList").css('display','block');
    $("#homeWork").css("color","#00c0ef");
    checkforming(nodeUnitId); //判断是否手填
    resultInfo(nodeUnitId); //点击获取验评
    $(".mybtn").css("display", "none");
    $(".bitCodes").css("display","none");
    $(".mybtnAdd").css("display","none");
    controlRowId ='';
    procedureId ='';
    flag = true;
    // console.log(flag)
}

//点击单元工创建工序name
function selfidName(id) {
    $.ajax({
        type: "post",
        url: "/quality/element/getProcedures",
        data: {id: id},
        success: function (res) {
            // if(res.code == 1){
            // console.log(res);
            var optionStrAfter = '';
            for(var i = 0;i<res.length;i++) {
                $("#imgListRight").html('');
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

            $("#tableItem_wrapper").css("height","calc(100% - "+$(".imgList").outerHeight()+"px - 64px)");

            // }else if(res.code==0){
            //     layer.msg(res.msg);
            // }
        }
    })
}
/**==========结束初始化 单元工树 =============*/

//点击置灰
$(".imgList").on("click","a",function () {
    $(this).css("color","#00c0ef").siblings("a").css("color","#333333");
    $("#homeWork").css("color","#333333");
});


//点击作业
$(".imgList").on("click","#homeWork",function () {
    $(".bitCodes").css("display","none");
    $(".mybtn").css("display","none");
    $(".mybtnAdd").css("display","none");
    $(this).css("color","#00c0ef").parent("span").next("span").children("a").css("color","#333333");
    tableItem.ajax.url("/quality/common/datatablesPre?tableName=norm_materialtrackingdivision&checked_gk=0&en_type="+eTypeId+"&unit_id="+nodeUnitId+"&division_id="+nodeId).load();
    implementation.ajax.url("/quality/common/datatablesPre?tableName=quality_upload&type=1&cpr_id=").load();
    imageData.ajax.url("/quality/common/datatablesPre?tableName=quality_upload&type=4&cpr_id=").load();
    flag = true;
});

//点击工序名字刷新列表
function clickConName(id) {
    flag = false;
    controlRowId ='';
    procedureId ='';
    procedureId = id;
    if(nodeUnitId != undefined || nodeUnitId != null && procedureId != undefined || procedureId != null){
        tableItem.ajax.url("/quality/common/datatablesPre?tableName=norm_materialtrackingdivision&checked_gk=0&en_type="+eTypeId+"&unit_id="+nodeUnitId+"&division_id="+nodeId+"&nm_id="+procedureId).load();
    }
    $("#tableContent .imgList").css('display','block');
    console.log(id + " 控制点工序 procedureId");
    if(controlRowId =='' || procedureId == '') {
        $(".bitCodes").css("display","none");
        $(".mybtn").css("display","none");
        $(".mybtnAdd").css("display","none");
        implementation.ajax.url("/quality/common/datatablesPre?tableName=quality_upload&type=1&cpr_id=").load();
        imageData.ajax.url("/quality/common/datatablesPre?tableName=quality_upload&type=4&cpr_id=").load();
        // onlineFill = $("#onlineFill").dataTable().fnDestroy(true);// 报错可能是这个原因
        $('#onlineFillParent').html('<table id="onlineFill" class="table table-striped table-bordered" cellspacing="0" width="100%">' +
            '<thead>' +
                '<tr style="text-align: center;border-bottom:2px solid #111;border-top: 1px solid #cecece;">' +
                    '<th style="padding:10px 18px;font-weight: bold;">填报人</th>' +
                    '<th style="padding:10px 18px;font-weight: bold;">填报日期</th>' +
                    '<th style="padding:10px 18px;font-weight: bold;">当前审批人</th>' +
                    '<th style="padding:10px 18px;font-weight: bold;">审批状态</th>' +
                    '<th style="padding:10px 18px;font-weight: bold;">操作</th>' +
                    '<th style="display: none;">当前审批人Id</th>' +
                '</tr>' +
                '<tr class="delElement" style="text-align: center">' +
                    '<td colspan="5" style="padding:10px 18px;">没有找到记录</td>' +
                '</tr>' +
            '</thead>' +
        '</table>');
    }
};

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
    download(id,"/quality/element/download");
}

//下载list封装的方法
function downloadList(id,url) {
    $.ajax({
        url: url,
        type:"post",
        dataType: "json",
        data:{id:id},
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
                    + "<input class='file_url' style='display: none;' name='id' value="+ id +">"
                    + "<button type='submit' class=btn" + id +"></button>"
                    + "</form>"
                $("#form_container").append(str);
                $("#form_container").find(".btn" + id).click();
            }

        }
    })
}

//点击下面的列表的下载
function downConFileImp(id) {
    downloadList(id,"/quality/Unitqualitymanage/relationDownload");
}

//点击下面的列表的下载
function downConFileData(id) {
    downloadList(id,"/quality/Unitqualitymanage/relationDownload");
}

//预览
function showPdf(id,url) {
    $.ajax({
        url: url,
        type: "post",
        data: {id:id},
        success: function (res) {
            console.log(res);
            if(res.code === 1){
                var path = res.path;
                var houzhui = res.path.split(".");
                if(houzhui[houzhui.length-1]=="pdf"){
                    window.open("/static/public/web/viewer.html?file=../../../" + path,"_blank");
                }else if(res.path.split(".")[1]==="png"||res.path.split(".")[1]==="jpg"||res.path.split(".")[1]==="jpeg"){
                    layer.photos({
                        photos: {
                            "title": "", //相册标题
                            "id": id, //相册id
                            "start": 0, //初始显示的图片序号，默认0
                            "data": [   //相册包含的图片，数组格式
                                {
                                    "alt": "图片名",
                                    "pid": id, //图片id
                                    "src": "../../../"+res.path, //原图地址
                                    "thumb": "" //缩略图地址
                                }
                            ]
                        }
                        ,anim: Math.floor(Math.random()*7) //0-6的选择，指定弹出图片动画类型，默认随机（请注意，3.0之前的版本用shift参数）
                        ,success:function () {
                            $(".layui-layer-shade").empty();
                        }
                    });
                }else{
                    layer.msg("不支持的文件格式");
                }

            }else {
                layer.msg(res.msg);
            }
        }
    })
}

//点击打印模板
function printConFile(id) {
    showPdf(id,"/quality/Unitqualitymanage/relationPreview");
}

//删除封装的方法
function delData(id,url) {
    layer.confirm('是否删除该数据?', function(index){
        $.ajax({
            type: "post",
            url: url,
            data: {id:id},
            success: function (res) {
                console.log(res);
                if(res.code ==1){
                    layer.msg("删除成功！");
                    checkforming(nodeUnitId);
                    resultInfo(nodeUnitId)
                    // tableItem.ajax.url("/quality/common/datatablesPre?tableName=quality_division_controlpoint_relation&division_id="+nodeUnitId+"&nm_id="+procedureId).load();
                    // tableItem.ajax.url("/quality/common/datatablesPre?tableName=norm_materialtrackingdivision&checked_gk=0&en_type="+eTypeId+"&unit_id="+nodeUnitId+"&division_id="+nodeId+"&nm_id="+procedureId).load();
                    if(procedureId !=''){
                        tableItem.ajax.url("/quality/common/datatablesPre?tableName=norm_materialtrackingdivision&checked_gk=0&en_type="+eTypeId+"&unit_id="+nodeUnitId+"&division_id="+nodeId+"&nm_id="+procedureId).load();
                    }else if(procedureId == ''){
                        tableItem.ajax.url("/quality/common/datatablesPre?tableName=norm_materialtrackingdivision&checked_gk=0&en_type="+eTypeId+"&unit_id="+nodeUnitId+"&division_id="+nodeId).load();
                    }
                    implementation.ajax.url("/quality/common/datatablesPre?tableName=quality_upload&type=1&cpr_id="+controlRowId).load();
                    imageData.ajax.url("/quality/common/datatablesPre?tableName=quality_upload&type=4&cpr_id="+controlRowId).load();
                }else{
                    layer.msg(res.msg)
                }
            }
        })
        layer.close(index);
    });
}

//扫描件回传、附件资料的删除
function delFile(id) {
    console.log(id +"当前行Id");
    delData(id,"/quality/Unitqualitymanage/relationDel");
}

//点击行获取Id
$("#tableItem").delegate("tbody tr","click",function (e) {
    if($(e.target).hasClass("dataTables_empty")){
        return;
    }
    $(this).addClass("select-color").siblings().removeClass("select-color");
    selectData = tableItem.row(".select-color").data();//获取选中行数据
    console.log(selectData[3] +" ------控制点 controlRowId");
    console.log(selectData);
    controlRowId = selectData[3];
    // resources = selectData[4];
    controlId = selectData[4];

    if(controlRowId != undefined && controlRowId != null){
        $(".delElement").remove();
        implementation.ajax.url("/quality/common/datatablesPre?tableName=quality_upload&type=1&cpr_id="+controlRowId).load();
        imageData.ajax.url("/quality/common/datatablesPre?tableName=quality_upload&type=4&cpr_id="+controlRowId).load();
        funOnLine(nodeUnitId,procedureId,controlRowId)
        onlineFill.ajax.url("/quality/common/datatablesPre?tableName=quality_form_info&DivisionId="+nodeUnitId+"&ProcedureId="+procedureId+"&cpr_id="+controlRowId).load();
    }
    $(".bitCodes").css("display","none");
    $(".mybtn").css("display","none");
    $(".mybtnAdd").css("display","none");
    // console.log(flag)
    if(flag != true){
        if($(".tabs-selected a span:first-child").html() === "扫描件回传"){
            $(".mybtn").css("display","block");
        }else if($(".tabs-selected a span:first-child").html() === "附件资料"){
            $(".bitCodes").css("display","block");
            $(".mybtn").css("display","none");
        }else if($(".tabs-selected a span:first-child").html() === "在线填报"){
            $(".mybtnAdd").css("display", "block");
            $(".mybtn").css("display", "none");
        }
    }else if(flag == true){
        $(".mybtn").css("display", "none");
        $(".bitCodes").css("display","none");
        $(".mybtnAdd").css("display","none");
    }

    //向提交页面之前放置值
    $("#resVal").val(resources);

});

//线上的验评结果
function resultInfo(nodeUnitId) {
    $.ajax({
        url:"/quality/element/getEvaluation",
        data:{
            unit_id:nodeUnitId
        },
        type:"POST",
        dataType:"JSON",
        success:function (res) {
            console.log(res);
            if(res.msg == 'success'){
                $(".result form select").val(res.evaluateResult);
                $(".result form #date").val(res.evaluateDate);
                layui.form.render('select');
                if(res.evaluateDate == 0){
                    $("#date").val('');
                }
            }
        }
    })

}

//在二次点击在线填报时触发
var selectAddShow;
//checkform判断是否手填验评
function checkforming(nodeUnitId) {
    $.ajax({
        url: "/quality/element/checkform",
        type: "post",
        data: {
            // division_id:nodeUnit_id,
            // cpr_id:controlRowId,
            // cp_id:controlId,
            unit_id:nodeUnitId,
        },
        success: function (res) {
            // console.log(res);
            if(res.msg == "fail"){//线上结果
                // onlineFill = $("#onlineFill").dataTable().fnDestroy(true);
                // $('#onlineFillParent').html('<table id="onlineFill" class="table table-striped table-bordered" cellspacing="0" width="100%">' +
                //     '<thead>' +
                //     '<tr style="text-align: center">' +
                //     '<th>填报人</th>' +
                //     '<th>填报日期</th>' +
                //     '<th>当前审批人</th>' +
                //     '<th>审批状态</th>' +
                //     '<th>操作</th>' +
                //     '<th style="display: none;">当前审批人Id</th>' +
                //     '</tr>' +
                //     '</thead>' +
                //     '</table>');
                // funOnLine(nodeUnitId,procedureId,controlRowId);
                // onlineFill.ajax.url("/quality/common/datatablesPre?tableName=quality_form_info&DivisionId="+nodeUnitId+"&ProcedureId="+procedureId+"&cpr_id="+controlRowId).load();
                selectAddShow = false;
                $("option").attr('disabled',true);
                layui.use(['form'], function(){
                    var form = layui.form;
                    $(".layui-input[readonly]").attr('style', 'background: #e0e0e0');
                    $("#date").attr({"disabled":true});
                    form.render("select");
                });
            }else if(res.msg == "success"){ //手动填写
                selectAddShow = true;
                $("option").removeAttr('disabled');
                layui.use(['form'], function(){
                    var form = layui.form;
                    form.render("select");
                });
                $("#date").attr({"disabled":false});
                setTimeout(function () {
                    $(".layui-input[readonly]").attr('style', 'background: #ffffff !important');
                    $(".result input[readonly]").addClass('disabledColor');
                },900)

                // $(".mybtnAdd").css("display","none");
                // $('#onlineFillParent').html('<p style="text-align: center;width: 100%;margin-top: 20px;">在线填报没有该模板！请移步到扫描件回传上传相关资料！</p>');
            }
        }
    });
}

//线下的验评结果手动填写
layui.use(['form', 'layedit', 'laydate', 'element', 'layer'], function(){
    var form = layui.form
        ,layer = layui.layer
        ,laydate = layui.laydate;
    //日期
    laydate.render({
        elem: '#date' //指定元素
        ,done:function (value, date, endDate) {
            //得到日期生成的值 得到日期时间对象 得结束的日期时间对象，开启范围选择（range: true）才会返回。对象成员同上。
            resultChange();
        }
    });

    form.on('select(type)',function (data) {
        resultChange();
    });
});

//修改验评结果result
function resultChange() {
    $.ajax({
        url:'/quality/element/Evaluate',
        data:{
            Unit_id:nodeUnitId,
            EvaluateResult:$(".result form select").val(),
            EvaluateDate:($(".result form #date").val())?($(".result form #date").val()):arguments[0]
        },
        type:'POST',
        dataType:"JSON",
        success:function (res) {
            if(res.code == 1){
                console.log("成功");
            }else{
                layer.msg(res.msg)
            }
        }
    })
}

//easyui点击显示选择
$('#unitTab').tabs({
    border:false,
    onSelect:function(title,index){
        if(title === "扫描件回传"){
            if(flag == true){
                $(".mybtn").css("display","none");
            }
            if(controlRowId && procedureId){
                $(".mybtn").css("display","block");
            }
            if(controlRowId ==''|| procedureId == '' || flag == true) {
                $(".mybtn").css("display","none");
            }
        }else if(title != "扫描件回传"){
            $(".mybtn").css("display","none");
        }

        if(title === "附件资料"){
            if(flag == true){
                $(".bitCodes").css("display","none");
            }
            if(controlRowId && procedureId){
                $(".bitCodes").css("display","block");
                $(".mybtn").css("display","none");
            }
            if(controlRowId ==''|| procedureId == '' || flag == true) {
                $(".bitCodes").css("display","none");
            }
        }else if(title != "附件资料"){
            $(".bitCodes").css("display","none");
        }

        if(title === "在线填报"){
            if(flag == true){
                $(".mybtnAdd").css("display","none");
            }
            if(controlRowId && procedureId){
                $(".mybtnAdd").css("display", "block");
                $(".mybtn").css("display", "none");
            }else if(controlRowId == undefined || controlRowId == null){
                layer.msg("请选择控制点!")
            }
            if(controlRowId == '' || procedureId == '' || flag == true){
                $(".mybtnAdd").css("display","none");
            }
            // if(selectAddShow == true){
            //     $(".mybtnAdd").css("display","none");
            // }
        }else if(title != "在线填报"){
            $(".mybtnAdd").css("display","none");
        }
    }
});

//获取高度自适应
function outerHeight() {
    autoHeight = parseInt($("#unitWorkRight").height() - $("#tableContent").height() - 49);
    $("#implementation_wrapper").css("height",autoHeight+"px");
}

/*回传件上传*/
   var uploader;

   uploader = WebUploader.create({
       auto: true,
       swf:  '/static/public/webupload/Uploader.swf',
       server: "/admin/common/upload?module=quality&use=element",
       pick: {
           multiple: false,
           id: "#file_upload_standards",
           innerHTML: "<i class='fa fa-upload'></i>&nbsp;上传"
       },
       resize: false,
       duplicate :true, //是否可以重复上传
   });
   uploader.on( 'fileQueued', function( file ) {
       // $list.val('等待上传...');
   });
   uploader.on( 'uploadSuccess', function( file,res ) {
       // uploader.destroy();
       var  uploadFileId = res.id;
       var  uploadFileName = file.name;
       $.ajax({
            url:"/quality/element/copycheck",
            data:{cpr_id:controlRowId},
            type:'post',
            success:function(res) {
                if(res.msg == "success"){
                    $.ajax({
                        url:"/quality/element/addExecution",
                        data:{
                            att_id:uploadFileId,
                            filename: uploadFileName,
                            cpr_id:controlRowId,
                            type:1
                        },
                        type:'post',
                        success:function(res) {
                            if(res.code == 1) {
                                checkforming(nodeUnitId);
                                layer.msg(uploadFileName+'上传成功');
                                implementation.ajax.url("/quality/common/datatablesPre?tableName=quality_upload&type=1&cpr_id="+controlRowId).load();
                                imageData.ajax.url("/quality/common/datatablesPre?tableName=quality_upload&type=4&cpr_id="+controlRowId).load();

                                if(procedureId !=''){
                                    tableItem.ajax.url("/quality/common/datatablesPre?tableName=norm_materialtrackingdivision&checked_gk=0&en_type="+eTypeId+"&unit_id="+nodeUnitId+"&division_id="+nodeId+"&nm_id="+procedureId).load();
                                }else if(procedureId == ''){
                                    tableItem.ajax.url("/quality/common/datatablesPre?tableName=norm_materialtrackingdivision&checked_gk=0&en_type="+eTypeId+"&unit_id="+nodeUnitId+"&division_id="+nodeId).load();
                                }
                            } else {
                                layer.msg(res.msg);
                            }
                        }
                    })
                }else if(res.msg == "fail"){
                    layer.msg(res.remark);
                }
            }
        });
   });
   uploader.on( 'uploadError', function( file ) {
       layer.msg('上传出错！请稍后上传!');
   });

/*============回传件上传开始==============*/
//回传件上传结构表格
implementation = $('#implementation').DataTable({
    retrieve: true,
    processing: true,
    serverSide: true,
    iDisplayLength:1000,
    "scrollY": "true",
    "scrollCollapse": "true",
    "paging": "false",
    ajax: {
        "url": "/quality/common/datatablesPre?tableName=quality_upload&type=1&cpr_id="
    },
    dom:'rt',
    columns: [
        {
            name: "data_name"
        },
        {
            name: "nickname"
        },
        {
            name: "name"
        },
        {
            name: "create_time"
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
            "targets": [2]
        },
        {
            "targets": [3],
            "render": function (data, type, row) {
                if (data == null || data == undefined || data == '') return '';
                var time = new Date(data*1000);
                var y = time.getFullYear();
                var m = time.getMonth() + 1;
                m = m < 10 ? '0' + m : m;
                var d = time.getDate();
                d = d < 10 ? ('0' + d) : d;
                return y + '-' + m + '-' + d;
            }
        },
        {
            "searchable": false,
            "orderable": false,
            "targets": [4],
            "render": function (data, type, row) {
                var a = data;
                var html = "<span style='margin-left: 5px;' onclick='printConFile("+row[4]+")'><i title='预览' class='fa fa-search'></i></span>";
                html += "<span style='margin-left: 5px;' onclick='downConFileImp("+row[4]+")'><i title='下载' class='fa fa-download'></i></span>";
                html += "<span style='margin-left: 5px;' onclick='delFile("+row[4]+")'><i title='删除' class='fa fa-trash'></i></span>";
                return html;
            }
        }
    ],
    language: {
        "zeroRecords": "没有找到记录",
    },
    "fnDrawCallback":function(obj){
        // outerHeight();
    }
});

//附件资料上传点击
layui.use(['element', "layer", 'form', 'upload'], function () {
    var $ = layui.jquery
        , element = layui.element
        , upload = layui.upload;

    var form = layui.form
        , layer = layui.layer
        , layedit = layui.layedit
        , laydate = layui.laydate;
    upload.render({
        elem: '#test4',
        url: "/admin/common/upload?module=quality&use=element",
        accept:"file",
        // size:8192,
        before: function(obj){
            obj.preview(function(index, file, result){
                uploadName = file.name;
                console.log(file.name);//获取文件名，result就是base64
            })
        },
        done: function(res){
            if($(".tabs-selected a span:first-child").html() === "附件资料"){
                type=2;
            }
            if($(".tabs-selected a span:first-child").html() === "扫描件回传"){
                type=1;
            }
            if(res.code == 2){
                uploadId = res.id;
                $.ajax({
                    url:"/quality/element/addExecution",
                    data:{
                        att_id:uploadId,
                        filename: uploadName, //名称
                        cpr_id:controlRowId,
                        type:4
                    },
                    type:'post',
                    success:function(res) {
                        console.log(res)
                        if(res.code == 1) {
                            implementation.ajax.url("/quality/common/datatablesPre?tableName=quality_upload&type=1&cpr_id="+controlRowId).load();
                            imageData.ajax.url("/quality/common/datatablesPre?tableName=quality_upload&type=4&cpr_id="+controlRowId).load();
                            if(procedureId !=''){
                                tableItem.ajax.url("/quality/common/datatablesPre?tableName=norm_materialtrackingdivision&checked_gk=0&en_type="+eTypeId+"&unit_id="+nodeUnitId+"&division_id="+nodeId+"&nm_id="+procedureId).load();
                            }else if(procedureId == ''){
                                tableItem.ajax.url("/quality/common/datatablesPre?tableName=norm_materialtrackingdivision&checked_gk=0&en_type="+eTypeId+"&unit_id="+nodeUnitId+"&division_id="+nodeId).load();
                            }
                            layer.msg("上传成功！",{time:1500})
                        } else {
                            layer.msg(res.msg);
                        }
                    }
                })
            }else{
                layer.msg("上传失败！",{time:1500})
            }
        },
    });

});

/*============回传件上传结束==============*/


/*============图像资料开始==============*/
//图像资料结构表格
imageData = $('#imageData').DataTable({
    retrieve: true,
    processing: true,
    serverSide: true,
    iDisplayLength:1000,
    "scrollY": "200px",
    "scrollCollapse": "true",
    "paging": "false",
    ajax: {
        "url": "/quality/common/datatablesPre?tableName=quality_upload&type=4&cpr_id="
    },
    dom:'rt',
    columns: [
        {
            name: "data_name"
        },
        {
            name: "nickname"
        },
        {
            name: "name"
        },
        {
            name: "create_time"
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
            "targets": [2]
        },
        {
            "targets": [3],
            "render": function (data, type, row) {
                if (data == null || data == undefined || data == '') return '';
                var time = new Date(data*1000);
                var y = time.getFullYear();
                var m = time.getMonth() + 1;
                m = m < 10 ? '0' + m : m;
                var d = time.getDate();
                d = d < 10 ? ('0' + d) : d;
                return y + '-' + m + '-' + d;
            }
        },
        {
            "searchable": false,
            "orderable": false,
            "targets": [4],
            "render": function (data, type, row) {
                var html = "<span style='margin-left: 5px;' onclick='printConFile("+row[4]+")'><i title='预览' class='fa fa-search'></i></span>";
                html += "<span style='margin-left: 5px;' onclick='downConFileData("+row[4]+")'><i title='下载' class='fa fa-download'></i></span>";
                html += "<span style='margin-left: 5px;' onclick='delFile("+row[4]+")'><i title='删除' class='fa fa-trash'></i></span>";
                return html;
            }
        }
    ],
    language: {
        "zeroRecords": "没有找到记录"
    }
});
/*============图像资料结束==============*/



/*============在线填报开始==============*/

var isView = false;
var reList = true ;

//在线填报结构表格
function funOnLine(nodeUnitId,procedureId,controlRowId){
    onlineFill = $('#onlineFill').DataTable({
        retrieve: true,
        processing: true,
        serverSide: true,
        iDisplayLength:1000,
        "scrollY": "200px",
        "scrollCollapse": "true",
        "paging": "false",
        ajax: {
            "url": "/quality/common/datatablesPre?tableName=quality_form_info&DivisionId="+nodeUnitId+"&ProcedureId="+procedureId+"&cpr_id="+controlRowId
        },
        dom: 'tr',
        columns: [
            {
                name: "nickname"
            },
            {
                name: "create_time"
            },
            {
                name: "currentname"
            },
            {
                name: "approvestatus"
            },
            {
                name: "id"
            },
            {
                name: "CurrentApproverId"
            },
            {
                name: "CurrentStep"
            },
            {
                name: "user_id"
            }
        ],
        columnDefs: [
            {
                "targets":[0],
                "searchable": false,
                "orderable": false
            },
            {
                "targets": [1],
                "searchable": false,
                "orderable": false,
                "render": function (data, type, row) {
                    if (data == null || data == undefined || data == '') return '';
                    var time = new Date(data*1000);
                    var y = time.getFullYear();
                    var M = time.getMonth() + 1;
                    M = M < 10 ? '0' + M : M;
                    var d = time.getDate();
                    d = d < 10 ? ('0' + d) : d;
                    var h = time.getHours();
                    h = h < 10 ? '0' + h : h;
                    var m = time.getMinutes();
                    m = m < 10 ? '0' + m : m;
                    var s = time.getSeconds();
                    s = s < 10 ? '0' + s : s;
                    return y + '-' + M + '-' + d +' '+ h + ':' + m + ':' + s;
                }
            },
            {
                "targets": [2],
                "searchable": false,
                "orderable": false
            },
            {
                "targets": [3],
                "searchable": false,
                "orderable": false,
                "render": function (data, type, row) {
                    if (data === 0) {
                        return "待提交"
                    }
                    if (data === 1) {
                        return "审批中"
                    }
                    if (data === 2) {
                        return "已完成"
                    }
                    if (data === -1) {
                        return "被退回"
                    }
                    if (data === -2) {
                        return "已作废"
                    }
                }
            },
            {
                "searchable": false,
                "orderable": false,
                "targets": [4],
                "render": function (data, type, row) {
                    // console.log(row);
                    // console.log(row[5]+"当前审批人Id");
                    // console.log($("#userId").val() + "当前登录人Id");
                    // console.log($("#userId").val())
                    var userName = $(top.document).find("#current_user").text().trim();
                    var html = "";
                    html += "<a class='faStyle' title='查看' onclick='seeOnLine("+row[4]+")'><i class='fa fa fa-search'></i></a>";
                    if (row[3] === 0 && userName == row[0]) {
                        html += "<a class='faStyle' title='编辑' onclick='editOnLine("+row[4]+","+row[6]+")'><i class='fa fa-pencil'></i></a>";
                        html += "<a class='faStyle' title='删除' onclick='delOnLine("+row[4]+")'><i class='fa fa fa-trash'></i></a>";
                        // html += "<a class='faStyle' title='提交' onclick='submitOnLine("+row[4]+")'><i class='fa fa fa-check-square-o'></i></a>";
                    }
                    else if (row[3] === 1 && row[5] == $("#userId").val()) {
                        var str = JSON.stringify(row[2]);
                        html += "<a class='faStyle' title='编辑' onclick='editOnLine ("+row[4]+","+row[6]+")'><i class='fa fa fa-pencil'></i></a>";
                        // html += "<a class='faStyle' title='审批' onclick='approve("+row[4]+","+str+","+row[6]+")'><i class='fa fa fa-pencil-square-o'></i></a>";
                        html += "<a class='faStyle' title='审批历史' onclick='historyOnLine("+row[4]+","+row[6]+")'><i class='fa fa fa-file-text'></i></a>";
                    }
                    else if (row[3] === 2) {
                        html += "<a class='faStyle' title='审批历史' onclick='historyOnLine("+row[4]+","+row[6]+")'><i class='fa fa fa-file-text'></i></a>";
                        // html += "<a class='faStyle' title='下载' onclick='downOnLine("+row[4]+")' class='eleHide'><i class='fa fa fa-download'></i></a>";
                        html += "<a class='faStyle' title='作废' onclick='toVoidOnLine("+row[4]+")' class='eleHide'><i class='fa fa fa-minus'></i></a>";
                    }
                    else if (row[3] === -1 && row[5] == $("#userId").val()) {
                        // html += "<a class='faStyle' title='提交' onclick='submitOnLine("+row[4]+")'><i class='fa fa fa-check-square-o'></i></a>";
                        html += "<a class='faStyle' title='编辑' onclick='editOnLine("+row[4]+","+row[6]+")'><i class='fa fa fa-pencil'></i></a>";
                        html += "<a class='faStyle' title='删除' onclick='delOnLine("+row[4]+")'><i class='fa fa fa-trash'></i></a>";
                        html += "<a class='faStyle' title='审批历史' onclick='historyOnLine("+row[4]+","+row[6]+")'><i class='fa fa fa-file-text'></i></a>";
                    }
                    else html += "<a class='faStyle' title='审批历史' onclick='historyOnLine("+row[4]+","+row[6]+")'><i class='fa fa fa-file-text'></i></a>";
                    return html;

                }
            },
            {
                "targets": [5],
                "visible": false,
                "searchable": false,
                "orderable": false
            },
            {
                "targets": [6],
                "visible": false,
                "searchable": false,
                "orderable": false
            },
            {
                "targets": [7],
                "visible": false,
                "searchable": false,
                "orderable": false
            }
        ],
        language: {
            "zeroRecords": "没有找到记录"
        }
    });
}

//在线填报-新增
$("#unitWorkRightBottom").on("click",".mybtnAdd",function () {
    console.log(controlRowId);
    layer.open({
        type: 2,
        title: '在线填报',
        shadeClose: true,
        area: ['980px', '90%'],
        content: '/quality/Qualityform/edit?cpr_id='+ controlRowId + '&unit_id='+ nodeUnitId +'&currentStep=0&isView=false',
        end:function () {
            onlineFill.ajax.url("/quality/common/datatablesPre?tableName=quality_form_info&DivisionId="+nodeUnitId+"&ProcedureId="+procedureId+"&cpr_id="+controlRowId).load();
            tableItem.ajax.url("/quality/common/datatablesPre?tableName=norm_materialtrackingdivision&checked_gk=0&en_type="+eTypeId+"&unit_id="+nodeUnitId+"&division_id="+nodeId+"&nm_id="+procedureId).load();        }
    });
});

//在线填报-查看
function seeOnLine(id) {
    console.log(controlRowId);
    layer.open({
        type: 2,
        title: '在线填报',
        shadeClose: true,
        area: ['980px', '90%'],
        content: '/quality/Qualityform/edit?cpr_id='+ controlRowId + '&id='+ id +'&currentStep=null&isView=True'
    });
}

//在线填报-编辑
function editOnLine(id,step) {
    console.log(controlRowId);
    isView = true;
    layer.open({
        type: 2,
        title: '在线填报',
        shadeClose: true,
        area: ['980px', '90%'],
        content: '/quality/Qualityform/edit?cpr_id='+ controlRowId + '&id='+ id +'&currentStep=' + step,
        end:function () {
            onlineFill.ajax.url("/quality/common/datatablesPre?tableName=quality_form_info&DivisionId="+nodeUnitId+"&ProcedureId="+procedureId+"&cpr_id="+controlRowId).load();
        }
    });
}

//在线填报-删除
function delOnLine(id) {
    layer.confirm("你将删除该数据，是否确认删除？", function () {
        $.ajax({
            url: "/quality/Qualityform/delForm",
            type: "post",
            data: {id: id},
            success: function (res) {
                if (res.code === 1) {
                    layer.msg("删除数据成功！", {time: 1500, shade: 0.1});
                    onlineFill.ajax.url("/quality/common/datatablesPre?tableName=quality_form_info&DivisionId="+nodeUnitId+"&ProcedureId="+procedureId+"&cpr_id="+controlRowId).load();
                }else{
                    layer.msg(res.msg)
                }
            }
        });
    });
    console.log(id)
}

//在线填报-提交审批
function submitOnLine(id) {
    $.ajax({
        url: "/approve/Approve/CheckBeforeSubmitOrApprove",
        type: "post",
        data: {
            dataId:id,
            dataType:"app\\quality\\model\\QualityFormInfoModel",
            currentStep:0
        },
        success: function (res) {
            console.log(res);
            if(res == ""){
                layer.open({
                    type: 2,
                    title: '提交审批',
                    shadeClose: true,
                    area: ['980px', '90%'],
                    content: '/approve/approve/submit?dataId='+ id + '&dataType=app\\quality\\model\\QualityFormInfoModel',
                    end:function () {
                        funOnLine(nodeUnitId,procedureId,controlRowId);
                        onlineFill.ajax.url("/quality/common/datatablesPre?tableName=quality_form_info&DivisionId="+nodeUnitId+"&ProcedureId="+procedureId+"&cpr_id="+controlRowId).load();
                    }
                });
            }else if(res != ''){
                layer.alert(res);
            }
        },
        error:function () {
            alert("获取数据完整性检测异常")
        }
    });
    console.log(id)
}

//在线填报-流程审批
function approve(id,app,step) {
    console.log(app);
    $.ajax({
        url: "/approve/Approve/CheckBeforeSubmitOrApprove",
        type: "post",
        data: {
            dataId:id,
            dataType:"app\\quality\\model\\QualityFormInfoModel",
            currentStep:step
        },
        success: function (res) {
            console.log(res);
            if(res == ""){
                layer.open({
                    type: 2,
                    title: '流程处理',
                    shadeClose: true,
                    area: ['980px', '90%'],
                    content: '/approve/approve/Approve?dataId='+ id + '&dataType=app\\quality\\model\\QualityFormInfoModel',
                    success: function(layero, index){
                        var body = layer.getChildFrame('body', index);
                        body.find("#conCode").val(app);
                        body.find("#dataId").val(id);
                        body.find("#dataType").val('app\\quality\\model\\QualityFormInfoModel');
                    },
                    end:function () {
                        funOnLine(nodeUnitId,procedureId,controlRowId);
                        onlineFill.ajax.url("/quality/common/datatablesPre?tableName=quality_form_info&DivisionId="+nodeUnitId+"&ProcedureId="+procedureId+"&cpr_id="+controlRowId).load();
                    }
                });
            }else if(res != ''){
                layer.alert(res);
            }
        },
        error:function () {
            alert("获取数据完整性检测异常")
        }
    });
    console.log(id);
}

//在线填报-审批历史
function historyOnLine(id,curStep) {
    console.log(curStep);
    //向提交页面之前放置值
    $("#curStepVal").val(curStep);
    console.log($("#curStepVal").val());
    console.log(controlRowId);
    layer.open({
        type: 2,
        title: '审批历史',
        shadeClose: true,
        area: ['980px', '90%'],
        content: '/approve/approve/ApproveHistory?dataId='+ id + '&dataType=app\\quality\\model\\QualityFormInfoModel',
        // end:function () {
        //     tableItem.ajax.url("/quality/common/datatablesPre?tableName=quality_division_controlpoint_relation&division_id="+nodeUnitId+"&nm_id="+procedureId).load();
        //     tableItem.ajax.url("/quality/element/datatablesPre?tableName=quality_division_controlpoint_relation&division_id="+nodeId+"&unit_id="+nodeUnitId+"&nm_id="+procedureId).load();
        // }
    });
}

//在线填报-点击退回
function returnOnLine(id,curStep) {
    console.log(curStep);
    console.log(controlRowId);
    layer.msg("退回");
}

//在线填报-点击作废
function toVoidOnLine(id) {
    layer.confirm('是否作废该数据? 如果作废,会生成一个新的待提交表单', function(index){
        $.ajax({
            url: "/quality/qualityform/cancel",
            type: "post",
            data: {id:id},
            success: function (res) {
                console.log(res);
                if(res.msg == "success"){
                    layer.msg("该数据已作废了！")
                    $(".eleHide").css("display","none");
                    onlineFill.ajax.url("/quality/common/datatablesPre?tableName=quality_form_info&DivisionId="+nodeUnitId+"&ProcedureId="+procedureId+"&cpr_id="+controlRowId).load();
                }
            },
            error:function () {
                alert("作废操作异常")
            }
        });
        layer.close(index);
    });

}

//下载封装的方法
function downloadFrom(id,url) {
    $.ajax({
        url: url,
        type:"post",
        dataType: "json",
        data:{formId:id},
        success: function (res) {
            if(res.code != 1){
                layer.msg(res.msg);
            }else {
                $("#form_container_from").empty();
                var str = "";
                str += ""
                    + "<iframe name=downloadFrame"+ id +" style='display:none;'></iframe>"
                    + "<form name=download"+id +" action="+ url +" method='get' target=downloadFrame"+ id + ">"
                    + "<span class='file_name' style='color: #000;'>"+str+"</span>"
                    + "<input class='file_url' style='display: none;' name='formId' value="+ id +">"
                    + "<button type='submit' class=btn" + id +"></button>"
                    + "</form>"
                $("#form_container_from").append(str);
                $("#form_container_from").find(".btn" + id).click();
            }
        }
    })
}

//在线填报-下载
function downOnLine(id) {
    downloadFrom(id,"/quality/element/formDownload")
}

/*============在线填报结束==============*/
