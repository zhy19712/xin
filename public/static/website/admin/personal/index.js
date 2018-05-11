
var message;
layui.config({
    base: '/static/webSite/common/js/',
    version: '1.0.1'
}).use(['app', 'message'], function() {
    var app = layui.app,
        $ = layui.jquery,
        layer = layui.layer;
    //将message设置为全局以便子页面调用
    message = layui.message;
    //主入口
    app.set({
        type: 'iframe'
    }).init();
});

layui.use('upload', function(){
    var upload = layui.upload;
    //执行实例
    var uploadInst = upload.render({
        elem: '#thumb' //绑定元素
        ,url: "/admin/common/upload" //上传接口
        ,done: function(res){
            //上传完毕回调
            if(res.code == 2) {
                $('#demo1').attr('src',res.src);
                $('#upload-thumb').append('<input type="hidden" name="thumb" value="'+ res.id +'">');
            } else {
                layer.msg(res.msg);
            }
        }
        ,error: function(){
            //请求异常回调
            //演示失败状态，并实现重传
            var demoText = $('#demoText');
            demoText.html('<span style="color: #FF5722;">上传失败</span> <a class="layui-btn layui-btn-mini demo-reload">重试</a>');
            demoText.find('.demo-reload').on('click', function(){
                uploadInst.upload();
            });
        }
    });

    var uploadAuto = upload.render({
        elem: '#upload' //绑定元素
        ,url: "/admin/common/upload" //上传接口
        ,done: function(res){
            //上传完毕回调
            if(res.code == 2) {
                $('#uploadImg').attr('src',res.src);
                $('#uploadInline').append('<input type="hidden" name="signature" value="'+ res.id +'">');
            } else {
                layer.msg(res.msg);
            }
        }
        ,error: function(){
            //请求异常回调
            //演示失败状态，并实现重传
            var demoText = $('#uploadText');
            demoText.html('<span style="color: #FF5722;">上传失败</span> <a class="layui-btn layui-btn-mini demo-reload">重试</a>');
            demoText.find('.demo-reload').on('click', function(){
                uploadAuto.upload();
            });
        }
    });
});


layui.use(['layer', 'form'], function() {
    var layer = layui.layer,
        $ = layui.jquery,
        form = layui.form;
    $(window).on('load', function() {
        form.on('submit(admin)', function(data) {
            $.ajax({
                url:"/admin/admin/personal",
                data:$('#admin').serialize(),
                type:'post',
                async: false,
                success:function(res) {
                    if(res.code == 1) {
                        layer.alert(res.msg, function(index){
                            location.href = res.url;
                        })
                    } else {
                        layer.msg(res.msg);
                    }
                }
            })
            return false;
        });
    });
});
