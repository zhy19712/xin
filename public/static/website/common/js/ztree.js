;(function($){
    $.ztree = function (options) {
        var option = {
            treeId:'ztree',
            zTreeOnClick:function () {},
            zTreeOnDblClick:function () {},
            ajaxUrl:'./index',
            type:'post',
            enable:true,
            dataType:'json'
        };
        $.extend(option,options);
        var setting = {
            async: {
                enable : option.enable,
                type : option.type,
                url : option.ajaxUrl,
                dataType :option.dataType
            },
            data: {
                simpleData: {
                    enable: true,
                    idKey: "id",
                    pIdKey: "pId"
                }
            },
            view:{
                selectedMulti: false
            },
            callback:{
                onClick:option.zTreeOnClick,
                onDblClick:option.zTreeOnDblClick,
            },
            showLine:true,
            showTitle:true,
            showIcon:true
        };

        $.extend(setting,options);

        zTreeObj = $.fn.zTree.init($("#"+ option.treeId), setting, null);
    }
})(jQuery);