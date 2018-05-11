;(function($){
    $.download = function(options){
        var file_id = $(options.that).attr('uid');
        if(!file_id){
            file_id = 'download';
        }
        var option = {
            url:'',
            data:{
                file_id:file_id
            }
        }
        $.extend(option,options);
        $.ajax({
            url: option.url,
            type: "post",
            dataType: "json",
            data:option.data,
            success: function (res) {
                if(res.code != 1){
                    layer.msg(res.msg);
                }else {
                    $("#form_container").empty();
                    var str = "";
                    str += ""
                        + "<iframe name=downloadFrame"+ file_id +" style='display:none;'></iframe>"
                        + "<form id=download"+ file_id +" action="+ option.url +" method='get' target=downloadFrame"+ file_id +">"
                        + "<span class='file_name' style='color: #000;'>"+str+"</span>"
                        + "<input class='file_url' style='display: none;' name='file_id' value="+ file_id +">"
                        + "<button type='submit' class=btn" + file_id +"></button>"
                        + "</form>"
                    $("#form_container").append(str);
                    $("#form_container").find(".btn" + file_id).click();
                }
            }
        });
    }
})(jQuery);