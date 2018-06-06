layui.use('layer', function(){
    layer = layui.layer;
});

$('#showForm').click(function () {
    layer.open({
        title:'表单',
        id:'1',
        type:'1',
        area:['700px','420px'],
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
