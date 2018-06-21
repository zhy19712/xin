layui.use(['layer','element','util','laydate','form','table'], function(){
    laydate = layui.laydate;
    form = layui.form;
    table = layui.table;
    element = layui.element;
    util = layui.util;
    laydate.render({
        //构建开始时间
        elem: '#startDate'
    });
    laydate.render({
        //构建结束时间
        elem: '#endDate'
    });
    table.render({
        elem: '#demo' //指定原始表格元素选择器（推荐id选择器）
        ,cols: [[
            {field: 'id', title: '日期'}
            ,{field: 'username', title: '填报人'}
        ]] //设置表头
    });
});

$('.layui-progress-bar').append('<span class="layui-progress-tips"></span><i class="fa fa-circle" id="circle"></i>');
var timer;
var sleepTimer;
active = {
    initSpeed:1000,             //初始速度
    speed:1000,                 //变速度
    speedMultiply:2,            //初始倍速
    currentSpeed:1,             //当前倍速
    scale:0,                    //每一帧的刻度
    dayScale:0,                 //总刻度
    startTime:'2018-03-01',    //开始日期
    endTime:'2018-04-01',      //结束日期
    dayArr:[],                  //日期的总长度(天数)
    index:0,                    //当前日期索引
    prevYear:'',                //上一年份(用来跨年)
    nextYear:'',                //下一年份(用来跨年)
    play: function () {         //播放
        if(Math.round(active.scale)>=99){
            active.stop();
        }
        $('#play').hide();
        $('#pause').show();
        clearInterval(timer);
        active.prevYear = active.startTime.substr(0,4);
        active.nextYear = active.endTime.substr(0,4);
        timer = setInterval('active.date()', active.speed);
        sleepTimer = setInterval('active.sleep()', active.speed);
    },
    pause:function(){           //暂停
        $('#play').show();
        $('#pause').hide();
        clearInterval(timer);
        clearInterval(sleepTimer);
    },
    stop: function () {         //停止
        $('#play').show();
        $('#pause').hide();
        clearInterval(timer);
        clearInterval(sleepTimer);
        active.speed = active.initSpeed;    //重置初始速度
        active.currentSpeed = 1;            //重置当前倍速
        active.scale = 0;                   //重置每一帧的刻度
        active.index = 0;                   //重置当前日期索引
        element.progress('demo', 0);        //重置进度
        $('.layui-progress-text').html(active.startTime);
        $('#speed').text(0);
    },
    forward:function(){         //快进
        if($('#pause').is(':hidden')){  //暂停状态下禁止快进
            return false;
        }
        if(active.speed<=100){
            layer.msg('再快就超速啦^_^');
            return false;
        }
        active.currentSpeed = active.speedMultiply*active.currentSpeed;
        $('#speed').text(active.currentSpeed);
        active.speed -= 200;
        if (active.speed==200) {
            active.speed -= 100;
        }
        clearInterval(timer);
        clearInterval(sleepTimer);
        if (active.speed > 0) {
            timer = setInterval('active.date()', active.speed);
            sleepTimer = setInterval('active.sleep()', active.speed);
        }
        console.log(active.speed);
    },
    backward:function(){        //快退
        if($('#pause').is(':hidden')){
            return false;
        }
        if(active.speed>=1000){
            layer.msg('再慢就不会动啦^_^');
            return false;
        }
        active.currentSpeed = active.currentSpeed/active.speedMultiply;
        $('#speed').text(active.currentSpeed);
        if (active.currentSpeed<2) {
            $('#speed').text(0);
        }
        active.speed += 200;
        if (active.speed==900) {
            active.speed += 100;
        }
        clearInterval(timer);
        clearInterval(sleepTimer);
        if (active.speed > 0) {
            timer = setInterval('active.date()', active.speed);
            sleepTimer = setInterval('active.sleep()', active.speed);
        }
        console.log(active.speed);
    },
    date:function(){        //日期转换为刻度
        console.log(Math.round(active.scale));

        var bd = new Date(active.startTime);
        var be = new Date(active.endTime);
        //console.log(bd);
        //console.log(be);
        var bdTime = bd.getTime(), beTime = be.getTime(),timeDiff = beTime - bdTime;
        active.dayScale = timeDiff/((1000 * 60 * 60 * 24))+1;
        for(var i=0; i<= timeDiff; i+=86400000){
            var ds = new Date(bdTime+i);
            var month = util.digit(ds.getMonth()+1,2);
            var day = util.digit(ds.getDate(),2);
            active.dayArr.push((ds.getFullYear()+'-'+month)+'-'+day);
        }
        active.scale += 100/active.dayScale;
        if(active.scale>100){
            clearInterval(timer);
            clearInterval(sleepTimer);
            $('#play').show();
            $('#pause').hide();
            return false;
        }
        element.progress('demo', active.scale+'%');
    },
    sleep:function(numberMillis){       //睡眠时间
        if(active.index == active.dayArr.length) {
            active.index = 0;
        }

        //跨年+1天
        if (active.prevYear<active.nextYear) {
            $('.layui-progress-tips').html(active.dayArr[active.index]);
        }else{
            $('.layui-progress-tips').html(active.dayArr[active.index+1]);
        }
        active.index++;
    }
}

$('#play').click(function () {
    active.play(100);
});
$('#pause').click(function () {
    active.pause();
});
$('#stop').click(function () {
    active.stop();
});
$('#forward').click(function () {
    active.forward();
});
$('#backward').click(function () {
    active.backward();
});