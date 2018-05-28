$(function () {
    $.ajax({
        url:"/modelmanagement/configure/index",
        type:"get",
        success:function (res) {
            console.log(res);
            if (res.code == 1){
                var panorama = res.data.panorama;
                var quality = res.data.quality;
                $("#pellucidity_home").val(panorama.pellucidity);
                $("#transparent_effect_home").val(panorama.transparent_effect);
                $("#pigment_home").val(splitPrefix(panorama.pigment));
                $("#pellucidity_quality").val(quality.pellucidity);
                $("#pigment_quality").val(splitPrefix(quality.pigment));
                $("#choiceness_pigment").val(splitPrefix(quality.choiceness_pigment));
                $("#qualified_pigment").val(splitPrefix(quality.qualified_pigment));
                $("#un_evaluation_pigment").val(splitPrefix(splitPrefix(quality.un_evaluation_pigment)));
                //初始化调色盘
                $('#pigment_home_color,#pigment_quality_color,#choiceness_pigment_color,#qualified_pigment_color,#un_evaluation_pigment_color').colorpicker({

                });
            }
        }
    })

    //3D效果配置
    layui.use('form', function(){
        var form = layui.form;
        //首页
        form.on('submit(homeApply)', function(data){

            data.field.pigment = joinPrefix(data.field.pigment);
            data.field.model_type = '1';
            formSubmit(data);
            return false;
        });
        //质量
        form.on('submit(qualityApply)', function(data){
            data.field.pigment = joinPrefix(data.field.pigment);
            data.field.choiceness_pigment = joinPrefix(data.field.choiceness_pigment);
            data.field.qualified_pigment = joinPrefix(data.field.qualified_pigment);
            data.field.un_evaluation_pigment = joinPrefix(data.field.un_evaluation_pigment);
            data.field.model_type = '2';
            formSubmit(data);
            return false;
        });
    });
    function formSubmit(data) {
        $.ajax({
            url: "/modelmanagement/configure/edit",
            type: "post",
            data:data.field,
            dataType: "json",
            success: function (res) {
                layer.msg(res.msg);
            }
        });
    }
    //颜色格式
    function splitPrefix(val) {
        val = "#"+ val.substr(val.length-6);
        return val;

    }
    function joinPrefix(val) {
        val = "0x64"+ val.substr(val.length-6);
        return val;
    }
    // 透明度输入控制
    function opcityControl(that) {
        var val = $(that).val();
        var arr = ['0','0.1','0.2','0.3','0.4','0.5','0.6','0.7','0.8','0.9','1'];
        if (arr.indexOf(val) == '-1'){
            $(that).val('');
        }else{
            $(that).val(val);
        }
    }
})