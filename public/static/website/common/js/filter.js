;(function($){
    $.viewFilter = function(options){
        $.extend(true,options);
        if($('[view-filter]').size()<=0){
            console.log('你要过滤谁？');
            return false;
        }
        if(options){
            $('[view-filter]').show();
        }else{
            $('[view-filter]').hide();
        }
    }
})(jQuery);
