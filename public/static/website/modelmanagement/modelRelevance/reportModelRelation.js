var searchData = '';    //组合查询序列化
var actualId = '';      //当前选中填报ID
layui.use(['laydate', 'form'], function () {
    laydate = layui.laydate;
    form = layui.form;
});
function getSegmentInfo(actual_id) {
    actualId = actual_id;
    $.ajax({
        url: "/progress/actual/preview",
        type: "post",
        data: {
            actual_id:actual_id
        },
        dataType: "json",
        success: function (res) {
            $('#section_id p').text(res.path.section_name);
            $('#actual_date').text(res.path.actual_date);
            $('#user_name').text(res.path.user_name);
            $('#remark').text(res.path.remark);
            $('#attachment_name').attr('uid',res.path.id).find('img').attr('src',res.path.path);
        }
    });
}

//绘制radio
$('input[name="nodeRelationTab"]').iCheck({
    radioClass: 'iradio_square-green',
    increaseArea: '0'
});

//默认选中未关联
$('#noteverTab').iCheck('check');
var isChecked = $('#noteverTab').is(':checked');
if(isChecked){
    tableItemFun(2);
    model_type = 2;
}

//筛选已关联构件
$('#alreadyTab').on('ifClicked', function(event){
    model_quality(1);
    model_type = 1;
});
//筛选未关联构件
$('#noteverTab').on('ifChecked', function(event){
    model_quality(2);
    model_type = 2;
});
//筛选全部构件
$('#allTab').on('ifChecked', function(event){
    model_quality(0);
    model_type = 0;
});
//筛选方法
function model_quality(model_type) {
    tableItem.ajax.url('/modelmanagement/common/datatablesPre.shtml?tableName=model_quality_search'+searchData+'&model_type='+model_type).load();
}

//模型构件列表
function tableItemFun(model_type) {
    tableItem = $('#tableItem').DataTable({
        pagingType: "full_numbers",
        processing: true,
        serverSide: true,
        "scrollX": true,
        "scrollY": "200px",
        "scrollCollapse": "true",
        "paging": "false",
        ajax: {
            "url": "/modelmanagement/common/datatablesPre.shtml?tableName=model_quality_search"+searchData+"&model_type="+model_type
        },
        dom: 'l<"radioWrap"><".alreadyBtn layui-btn layui-btn-normal btn-right table-btn">rtip',
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
                    var html =  "<span class='relation-date'>"+ newName +"</span>";
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
            "sSearchPlaceholder":"请输入关键字",
            "infoFiltered": "(从 _MAX_ 条记录过滤)",
            "paginate": {
                "sFirst": "<<",
                "sPrevious": "上一页",
                "sNext": "下一页",
                "sLast": ">>"
            }
        },
        fnInitComplete: function (oSettings) {
            $('.alreadyBtn').html('关联');
        },
        fnCreatedRow:function (nRow, aData, iDataIndex) {
        }
    });


    //翻页事件
    tableItem.on('draw',function () {
        for(var i = 0;i<idArr.length;i++){
            $('input[type="checkbox"][name="checkList"][idv='+ idArr[i] +']').prop("checked",true);
        }
    });

    //取消全选的事件绑定
    $("#tableItem_wrapper .dataTables_scrollHeadInner thead tr th:first-child").unbind();

    //关联构件
    $('.alreadyBtn').click(function(){
        $.ajax({
            url: "/progress/Actual/relevance",
            type: "post",
            data: {
                actual_id:actualId,
                id_arr:idArr
            },
            dataType: "json",
            success: function (res) {
                idArr.length=0;
                tableItem.ajax.url('/modelmanagement/common/datatablesPre.shtml?tableName=model_quality_search'+searchData+'&model_type='+model_type).load();
                layer.msg(res.msg);
                $('#all_checked').prop("checked",false);
            }
        });
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
    idArr = idArr.removalArray();
    console.log(idArr);
});

//构建查询表格模板
var firstTrTemp = [];
firstTrTemp.push('<tr class="search-tr" id="searchTr">');
firstTrTemp.push('<td></td>');
for(var i = 0;i<15;i++){
    firstTrTemp.push('<td id="searchTd'+i+'">');
    firstTrTemp.push('</td>');
}
firstTrTemp.push('</tr>');
$('#tableItem_wrapper .dataTables_scrollHeadInner table').append(firstTrTemp.join(''));

//获取下拉列表的值
function dropDown(type,eId) {
    $.ajax({
        url: "/modelmanagement/qualitymass/dropDown",
        type: "post",
        data: {
            type:type
        },
        dataType: "json",
        success: function (res) {
            var data = res.data;
            //构建select
            $('#searchTr td#'+eId).empty();
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
function change(that) {
    $('#searchTr select').each(function (i,n) {
        var val = $(n).find('option').val();
        if(val=='请选择'){
            $(n).find('option:first-child').val('');
        }
    });
    var section = $('#searchTd0 select option:selected').val();
    var unit = $('#searchTd1 select option:selected').val();
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
    searchData = '&section='+section+'&unit='+unit+'&parcel='+parcel+'&cell='+cell+'&pile_number_1='+pile_number_1+
        '&pile_val_1='+pile_val_1+'&pile_number_2='+pile_number_2+'&pile_val_2='+pile_val_2+'&pile_number_3='+pile_number_3+
        '&pile_val_3='+pile_val_3+'&pile_number_4='+pile_number_4+'&pile_val_4='+pile_val_4+'&el_start='+el_start+'&el_cease='+el_cease;
    tableItem.ajax.url('/modelmanagement/common/datatablesPre.shtml?tableName=model_quality_search'+searchData+'&model_type='+model_type).load();
}


//查看图片
$('#attachment_name').click(function(){
    var actual_id = $(this).attr('uid');
    $.ajax({
        url: "/progress/actual/preview",
        type: "post",
        data: {
            actual_id:actual_id
        },
        dataType: "json",
        success: function (res) {
            layer.photos({
                photos: {
                    "title": "", //相册标题
                    "id": 1, //相册id
                    "start": 0, //初始显示的图片序号，默认0
                    "data": [   //相册包含的图片，数组格式
                        {
                            "alt": "旁站记录表照片",
                            "pid": 666, //图片id
                            "src": res.path.path, //原图地址
                            "thumb": "" //缩略图地址
                        }
                    ]
                },
                anim: Math.floor(Math.random()*7),
                shade: [0.8, '#333'],
                shadeClose:true,
                closeBtn:1
            });
        }
    });
});
