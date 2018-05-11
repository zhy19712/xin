//表单验证
layui.use('form',function () {
    var form = layui.form;
    form.verify({
        username: function(value, item){ //value：表单的值、item：表单的DOM对象
            if(!new RegExp("^[a-zA-Z_\u4e00-\u9fa5\\s·]+$").test(value)){
                return '姓名只能为中文或英文字母';
            }
            if(/(^\_)|(\__)|(\_+$)/.test(value)){
                return '用户名首尾不能出现下划线\'_\'';
            }
        }
        ,pass: [
            /^[\S]{6,12}$/
            ,'密码必须6到12位，且不能出现空格'
        ],
        loginName:function (value, item) {
            if(!new RegExp("^[a-zA-Z0-9_\s·]+$").test(value)){
                return '登录名英文字母、数字或下划线';
            }
        },
        email:function (value, item) {
            if($(item).val()==''){
                return false;
            }
            if(!/^[a-zA-Z0-9_.-]+@[a-zA-Z0-9-]+(\.[a-zA-Z0-9-]+)*\.[a-zA-Z0-9]{2,3}$/.test(value)){
                return '请输入正确的邮箱';
            }
        },
        phone:function (value, item) {
            if($(item).val()==''){
                return false;
            }
            if(!/^1\d{10}$/.test(value)){
                return '请输入正确的手机号';
            }
        },
        chinese:function (value, item) {
            if($(item).val()==''){
                return false;
            }
            if(!/[\u4e00-\u9fa5]/.test(value)){
                return '只能输入中文字符';
            }
        },
        english:function (value, item) {
            if($(item).val()==''){
                return false;
            }
            if(!/[\a-zA-Z]/.test(value)){
                return '只能输入英文字母';
            }
        },
        number:function (value, item) {
            if($(item).val()==''){
                return false;
            }
            if(!/^[0-9]*$/.test(value)){
                return '只能输入数字';
            }
        },
        tel:function (value, item) {
            if($(item).val()==''){
                return false;
            }
            if(!/^(\(\d{3,4}\)|\d{3,4}-)?\d{7,8}$/.test(value)){
                return '请输入正确的电话号码';
            }
        }
    });
});