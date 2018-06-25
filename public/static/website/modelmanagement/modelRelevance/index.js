layui.use(['laydate', 'form'], function () {
    laydate = layui.laydate;
    form = layui.form;
});

/*function getScrollbarWidth() {
    var oP = document.createElement('p'),
        styles = {
            width: '100px',
            height: '100px',
            overflowY: 'scroll'
        }, i, scrollbarWidth;
    for (i in styles) oP.style[i] = styles[i];
    document.body.appendChild(oP);
    scrollbarWidth = oP.offsetWidth - oP.clientWidth;
    oP.remove();
    return scrollbarWidth;
}*/

//构建填报列表
var admin_table = $('#admin_table').DataTable({
    pagingType: "full_numbers",
    processing: true,
    serverSide: true,
    bAutoWidth: false,  //是否自动宽度
    scrollX: true,
    scrollY: "230px",   //表格容器高度
    scrollCollapse: true,
    ajax: {
        url: "/progress/common/datatablesPre?tableName=progress_actual"
    },
    dom: '<"#selectWrap.select-wrap">tlip',
    columns: [
        {
            name: "section_name"
        },
        {
            name: "actual_date"
        },
        {
            name: "user_name"
        },
        {
            name: "remark"
        },
        {
            name: "relevance"
        },
        {
            name: "id"
        }
    ],
    columnDefs: [
        {
            sWidth: "10%",       //根据按钮个数设置适当的宽度，支持px单位【注：该属性需要和bAutoWidth: false搭配使用】
            searchable: false,
            orderable: false,
            targets: [5],
            render: function (data, type, row) {
                var rowId = row[5];
                return '<i href="" title="关联模型" class="layui-btn layui-btn-xs" id="view" onclick="relation(' + rowId + ')">关联模型</i>';
            }
        }
    ],
    fnCreatedRow: function (nRow, aData, iDataIndex) {
        $.fn.dataTable.tables({visible: true, api: true}).columns.adjust();
    },
    fnInitComplete: function (oSettings) {
        $('#add').html('新增');
        constructingQueryConditions();
    },
    language: {
        "lengthMenu": "_MENU_ ",
        "zeroRecords": "没有找到记录",
        "info": "第 _PAGE_ 页 ( 总共 _PAGES_ 页 )",
        "infoEmpty": "无记录",
        "search": "搜索",
        "sSearchPlaceholder": "请输入关键字",
        "infoFiltered": "(从 _MAX_ 条记录过滤)",
        "paginate": {
            "sFirst": "<<",
            "sPrevious": "上一页",
            "sNext": "下一页",
            "sLast": ">>"
        }
    }
});

//构建查询条件
function constructingQueryConditions() {
    var selectEm = '<form class="layui-form">' +
        '<div class="layui-form-item">' +
        '<label class="layui-form-label">标段</label>' +
        '<div class="layui-input-inline">' +
        '<select id="segment" lay-filter="searchSegment"></select>' +
        '</div>' +
        '<label class="layui-form-label">选择日期</label>' +
        '<div class="layui-input-inline">' +
        '<input type="text" class="layui-input date" id="startDate">' +
        '</div>' +
        '</div>' +
        '</form>';
    $('#selectWrap').append(selectEm);
    fillSearchSegment();
    form.render();
}

//填充筛选标段
function fillSearchSegment() {
    $.ajax({
        url: "/progress/actual/index",
        type: "post",
        dataType: "json",
        success: function (res) {
            console.log(res);
            $('#segment').empty();
            if (res.code == 1) {
                for (i in res.sectionArr) {
                    if (res.sectionArr.hasOwnProperty(i)) {
                        $('#segment').append('<option value=' + i + '>' + res.sectionArr[i] + '</option>');
                    }
                }
                form.render();
                selectDate();
                dateScope();
            }
        }
    });
}

//根据选择的标段获取对应的日期区间范围
function dateScope() {
    var section_id = $('dd.layui-this').attr('lay-value');
    $('#startDate').attr('segmentId', section_id);
    form.on('select(searchSegment)', function (data) {
        $('#startDate').attr('segmentId', data.value);
        $.ajax({
            url: "/progress/actual/dateScope",
            type: "post",
            data: {
                section_id: data.value
            },
            dataType: "json",
            success: function (res) {
                $('#startDate').val('');
            }
        });
    });
}

//选择日期
function selectDate() {
    laydate.render({
        //构建起止时间
        elem: '#startDate',
        done: function (value, date, endDate) {
            var section_id = $('#startDate').attr('segmentId');
            admin_table.ajax.url('/progress/common/datatablesPre?tableName=progress_actual&section_id=' + section_id + '&actual_date=' + value).load();
        }
    });
}

//已关联模型表
var alreadyRelationModelTable = $('#alreadyRelationModelTable').DataTable({
    pagingType: "full_numbers",
    processing: true,
    serverSide: true,
    bAutoWidth: false,  //是否自动宽度
    scrollX: true,
    scrollY: "290px",   //表格容器高度
    scrollCollapse: true,
    ajax: {
        url: "/progress/common/datatablesPre.shtml?tableName=model_quality&relevance_type=1"
    },
    dom: 'lrtip',
    columns: [
        {
            name: "id"
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
        }
    ],
    columnDefs: [
        {
            sWidth: '10%',       //根据按钮个数设置适当的宽度，支持px单位【注：该属性需要和bAutoWidth: false搭配使用】
            "searchable": false,
            "orderable": false,
            "targets": [15],
            "render": function (data, type, row) {
                return '<i class="layui-btn layui-btn-xs" onclick="relieve(this)">解除</i>';
            }
        }
    ],
    fnCreatedRow: function (nRow, aData, iDataIndex) {
        $.fn.dataTable.tables({visible: true, api: true}).columns.adjust();
    },
    language: {
        "sProcessing": "数据加载中...",
        "lengthMenu": "_MENU_",
        "zeroRecords": "没有找到记录",
        "info": "第 _PAGE_ 页 ( 总共 _PAGES_ 页 )",
        "infoEmpty": "无记录",
        "search": "搜索",
        "sSearchPlaceholder": "请输入关键字",
        "infoFiltered": "(从 _MAX_ 条记录过滤)",
        "paginate": {
            "sFirst": "<<",
            "sPrevious": "<",
            "sNext": ">",
            "sLast": ">>"
        }
    }
});

//关联模型跳转
function relation(actual_id) {
    layer.open({
        tittlel: '关联模型',
        type: 2,
        area: ['100%', '100%'],
        content: ['./reportModelRelation', 'no'],
        success: function (layero, index) {
            var iframe = window['layui-layer-iframe' + index];
            iframe.getSegmentInfo(actual_id);
        }
    });
}