;(function($){
    /**
     * 点击
     * @param options[当前节点,表格实例,是否允许父节点加载表格数据,表格数据请求路径]
     * @author wyang
     */
    $.clicknode = function(options){
        var prevNode = options.treeNode.getPreNode();
        var nextNode = options.treeNode.getNextNode();
        var parentNode = options.treeNode.getParentNode();

        if (parentNode){
            window.parentNodeId = options.treeNode.pId;
        }

        if(prevNode){
            window.prevNodeId = prevNode.id;
            window.prevSortId = prevNode.sort_id;
        }

        if(nextNode){
            window.nextNodeId = nextNode.id;
            window.nextSortId = nextNode.sort_id;
        }

        window.treeNode = options.treeNode;
        window.nodeId = options.treeNode.id;
        window.nodeSortId = options.treeNode.sort_id;

        console.log(options.treeNode);

        var option = {
            tablePath:'/admin/common/datatablesPre?tableName=admin',
            parentShow:true,
            tableItem:tableItem,
            isLoadPath:true,
            isLoadTable:true,
            others:function () {}
        }

        $.extend(option,options);

        //获取路径
        function getPath() {
            if(option.isLoadPath){
                $.ajax({
                    url: "./getParents",
                    type: "post",
                    data: {id:options.treeNode.id},
                    dataType: "json",
                    success: function (res) {
                        if(res.msg === "success"){
                            $("#path").text("当前路径:" + res.path)
                        }
                    }
                });
            }
        }

        if(option.isLoadTable){
            loadData();
            return false;
        }

        //加载表格数据
        function loadData() {
            $('#tableItem_wrapper').show();
            $('.tbcontainer').show();
            option.tableItem.ajax.url(option.tablePath+"&id="+ options.treeNode.id).load();
            getPath();
        }

        //是否允许父节点加载表格数据
        if(options.treeNode.isParent){
            if(option.parentShow){
                loadData();
            }else{
                $('#tableItem_wrapper').hide();
                $('.tbcontainer').hide();
                getPath();
                return false;
            }
        }

        option.others();
    };
    /**
     * 展开/收起
     * @param options
     */
    $.toggle = function (options) {
        var treeObj = $.fn.zTree.getZTreeObj(options.treeId);
        treeObj.expandAll(options.state);
    };
    /**
     * 新增弹层
     * @param options
     */
    $.addNode = function (options) {
        var option = {
            formId:'nodeForm',
            layerId:'3',
            area:['660px','360px'],
            title:'新增节点',
            isSelectNode:true, //是否必须选择节点
            others:function(){}
        };
        $.extend(option,options);
        if(option.isSelectNode){
            if(!window.treeNode){
                layer.msg('请选择节点');
                return;
            }
        }
        layer.open({
            id:option.layerId,
            type:'1',
            area:option.area,
            title:option.title,
            content: $('#'+option.formId),
            success:function () {
                option.others();
            }
        });
    };
    /**
     * 编辑
     * @param options
     */
    $.editNode = function (options) {
        var option = {
            formId:'nodeForm',
            ajaxUrl:'./editNode',
            area:['660px','360px'],
            layerId:'2',
            data:{
                id:window.nodeId
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
                        if(res.code==0){
                            layer.msg(res.msg);
                            return false;
                        }
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
     * 提交新增
     * @param options
     */
    $.submitNode = function (options) {
        var option = {
            treeId:'ztree',
            formId:'nodeForm',
            data:{},
            ajaxUrl:'./editNode',
            others:function () {},
        };

        $.extend(option,options);
        var treeObj = $.fn.zTree.getZTreeObj(option.treeId);
        var nodes = treeObj.getSelectedNodes();

        layui.use('form',function(){
            var form = layui.form;
            //表单提交
            form.on('submit(save)', function(data){
                $.extend(true,data.field,option.data);
                $.ajax({
                    url:option.ajaxUrl,
                    dataType:'JSON',
                    type:'POST',
                    data:option.data,
                    success:function(res){
                        if(res.code!=1){
                            layer.msg(res.msg);
                            return false;
                        }
                        option.others(res);
                        treeObj.addNodes(nodes[0], res.data);
                        $('#'+option.formId)[0].reset();
                        layer.closeAll('page');
                        layer.msg(res.msg);

                    }
                });
                return false;
            });
        });
    }
    /**
     * 删除
     * @param options[树ID,是否删除父节点,ajax请求地址,ajax参数]
     * @author wyang
     */
    $.delnode=function(options){
        var option = {
            treeId:'ztree',
            delParent:false,
            url:'./delNode',
            data:{}
        }

        var treeObj = $.fn.zTree.getZTreeObj(option.treeId);
        var nodes = treeObj.getSelectedNodes();

        //是否选择节点
        if(nodes==''){
            layer.msg('请选择节点');
            return false;
        }

        var id = nodes[0].id;
        var isParent;

        option.data = { id:id };

        $.extend(option,options);

        //是否是父节点
        if (nodes.length > 0) {
            isParent = nodes[0].isParent;
        }

        //禁止删除父节点
        if(!option.delParent){
            if(isParent){
                layer.msg('包含下级，无法删除。');
                return false
            }
        }

        layer.confirm('该操作会将关联用户同步删除，是否确认删除？',{
            icon:3,
            title:'提示'
        },function(index){
            $.ajax({
                url:option.url,
                dataType:'JSON',
                type:'POST',
                data:option.data,
                success:function(res){
                    for (var i=0, l=nodes.length; i < l; i++){
                        treeObj.removeNode(nodes[i]);
                    }
                    layer.msg(res.msg);
                }
            });
            layer.close(index);
        });
    }
})(jQuery);