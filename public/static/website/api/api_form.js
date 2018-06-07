layui.use('layer', function(){
    layer = layui.layer;
});

$('.form').click(function(){
    layer.open({
        title:'表单',
        id:'1',
        type:'1',
        area:['700px','420px'],
        offset: ['75px', '25px'],
        closeBtn: 0,
        shade: 0,
        anim: 5,
        fixed: false,
        resize:false,
        move: false,
        content:$('#formLyout'),
        success:function () {

        },
        yes:function () {

            layer.close(layer.index);
        },
        cancel: function(index, layero){
            layer.close(layer.index);
        }
    });
});