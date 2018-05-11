;(function($){
    /**
     * 新增弹层
     * @param options
     * @author wyang
     */
    $.add = function(options){
        var option = {
            formId:'formLayer',
            layerId:'1',
            area:['660px','700px'],
            success:function () {}
        };
        $.extend(option,options);
        $('#'+option.formId)[0].reset();
        var nodeId = window.nodeId;
        if(!nodeId){
            layer.msg('请选择节点');
            return false;
        }
        layer.open({
            id:option.layerId,
            type:'1',
            area:option.area,
            title:'新增',
            content:$('#'+ option.formId),
            success:option.success,
            cancel: function(index, layero){
                $('#'+option.formId)[0].reset();
            }
        });
    };
    /**
     * 关闭弹层
     * @param btn/formId/others
     * @author wyang
     */
    $.close = function(options){
        var option = {
            btn:'close',
            formId:'formLayer',
            others:function(){}
        };
        $.extend(option,options);
        $('.'+option.btn).click(function () {
            $('#'+option.formId)[0].reset();
            option.others();    //关闭的时候需要处理的其他事务
            layer.closeAll('page');
        });
    };
    /**
     *  表单提交
     * @param formId/tablePath/data/ajaxUrl/others
     * @author wyang
     */
    $.submit= function (options) {
        var option = {
            formId:'formLayer',
            ajaxUrl:'./publish',
            data:{},
            tableItem:window.tableItem,
            others:function(){}

        };
        $.extend(option,options);
        layui.use('form',function(){
            var form = layui.form;
            //表单提交
            form.on('submit(save)', function(data){
                option.data.nodeId = window.nodeId;
                $.extend(true,data.field,option.data);
                $.ajax({
                    url:option.ajaxUrl,
                    dataType:'JSON',
                    type:'POST',
                    data:data.field,
                    success:function(res){
                        var filter = $(data.elem).attr('lay-filter');
                        if(filter=='save'){
                            layer.closeAll('page');
                        }
                        $('#'+option.formId)[0].reset();
                        window.rowId = '';
                        option.others();    //表单提交成功后需要处理的事务
                        layer.msg(res.msg);
                        option.tableItem.ajax.url(options.tablePath+"&id="+ nodeId).load();
                    }
                });
                return false;
            });
        });
    };
    /**
     * 编辑
     * @param options
     * @author wyang
     */
    $.edit = function(options){
        window.rowId = $(options.that).attr('uid');
        var option = {
            formId:'formLayer',
            ajaxUrl:'./publish',
            area:['660px','700px'],
            layerId:'2',
            data:{
                id:window.rowId
            },
            others:function(){}
        };
        $.extend(option,options);
        layer.open({
            id:option.layerId,
            type:'1',
            area:option.area,
            title:'编辑',
            content:$('#'+option.formId),
            success:function(){
                $.ajax({
                    url:option.ajaxUrl,
                    dataType:'JSON',
                    type:'GET',
                    data:option.data,
                    success:function(res){
                        option.others(res);
                        initUi.form.render();
                    }
                });
            },
            cancel: function(index, layero){
                $('#'+option.formId)[0].reset();
            }
        });
    };
    /**
     * 删除
     * @param options
     * @author wyang
     */
    $.deleteData = function (options) {
        window.rowId = $(options.that).attr('uid');
        var option = {
            tableItem:window.tableItem,
            ajaxUrl:'./standardDel',
            data:{
                id:window.rowId
            },
            tablePath:'/standard/common/datatablesPre?tableName=norm_file&pid='+ window.nodeId +'',
            others:function(){}
        };
        $.extend(option,options);
        layer.confirm('确认删除此条记录吗?', {icon: 3, title:'提示'}, function(index){
            $.ajax({
                url: option.ajaxUrl,
                data: option.data,
                type: "get",
                dataType: "json",
                success: function (res) {
                    if(res.code == 1){
                        layer.msg(res.msg,{icon:1,time:1500,shade: 0.1});
                        option.tableItem.ajax.url(option.tablePath).load();
                    }else{
                        layer.msg(res.msg,{time:1500,shade: 0.1});
                    }
                }
            })
            layer.close(index);
        })
    };
})(jQuery);