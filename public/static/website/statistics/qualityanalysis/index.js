/*****************
 * 流程
 * 先拉取历史记录 然后在发请求拉取默认的月份 返回之后在重载
 * getAllMonth -->createOption()-->getDefault()-->getIndexLeft--time_slot-->getInitLeft(section,data)-->myChartBar.setOption()
 *
 *
 *
 *
 * *********************/
// 基于准备好的dom，初始化echarts实例
var myChartBar = echarts.init(document.getElementById('mainBar'));
// 指定图表的配置项和数据
optionBar = {
  title: {
    text: '', //标题
    subtext:'', //副标题
    x:'center', //居中
    y:'top' //顶部
  },
  color:['#EED5B7','#FFC125', '#1E90FF'], //颜色
  tooltip: {
    trigger: 'axis',
    axisPointer: {
      type: 'shadow'
    }
  },
  legend: {
    data: ['总计', '合格' , '优良'],  //
    x:"right",
    top:30
  },
  grid: {
    left: '3%',
    right: '4%',
    bottom: '3%',
    containLabel: true
  },
  xAxis: {
    type: 'value',
    minInterval:1,
    boundaryGap: [0, 0.1]
  },
  yAxis: {
    type: 'category',
    data: ['标段1','标段2','标段3']
  },

  series: [
  ]
};
// 使用刚指定的配置项和数据显示图表。
myChartBar.setOption(optionBar);
//折线图
var myChartLine = echarts.init(document.getElementById('mainLine'));
optionLine = {
  title:{
    text:'',
    x:'center', //居中
    y:'top' //顶部
  },
  legend: {
    data: [],
    x:"right",
    top:30
  },
  tooltip: {
    trigger: 'axis',
  },


  grid: {
    left: '3%',
    right: '4%',
    bottom: '3%',
    containLabel: true
  },
  xAxis: {
    type: 'category',
    axisLabel:{ //坐标轴刻度标签的相关设置。
      interval:0,// 强制显示所有标签
      rotate:60 //刻度标签旋转的角度，在类目轴的类目标签显示不下的时候可以通过旋转防止标签之间重叠。
    },
    data: ["1月份","2月份","3月份","4月份","5月份","6月份","7月份","8月份","9月份","10月份","11月份","12月份"]
  },
  yAxis: {
    type: 'value',
    min:0,
    max:100,
    splitNumber : 10,
    axisLabel: {
      show: true,
      interval: 'auto',
      formatter: '{value} %'//刻度标签的内容格式器，支持字符串模板和回调函数两种形式。
    },
    show: true
  },
  series: [
  ]
};
myChartLine.setOption(optionLine);

//初始化图表
//重载
function getIndexRight(section,data){
  //标段
  $("#showSelect").html("");
  //截取数据
  var series = [],html = '<option value="yes全部">全部</option>';
  for(var i = 0; i<section.length;i++){
    html += '<option value="'+section[i]+'">'+section[i]+'</option>'
    var arr = data.slice(i*12,(i+1)*12);
      series.push({name:section[i],data:arr,type: 'line',label : {normal:{show: true,formatter:'{c}%'}},showAllSymbol: true});
  }
$("#showSelect").append(html);
  //折线图
  myChartLine.setOption({
    title:{
      text:$("#year").val()+"年度质量验评趋势分布图"
    },
    legend:{
      data: section
    },
    series: series
  });
}
//重载柱状图和表格
function getInitLeft(section,data){
  $('.tableBox').hide();
  $('#tableItem thead tr').html('');
  $('#tableItem tbody tr').html('');

  var excellent = [],excellentNumber = [],qualifiedNumber = [],totalNumber=[]; //优良率,优良数,合格数,合计数
  for(var m = 0; m<section.length;m++){
    excellent[m] = data[m].excellent;//优良率
    excellentNumber[m] = data[m].section_rate_number.excellent_number;//优良数
    qualifiedNumber[m] = data[m].section_rate_number.qualified_number;//合格数
    totalNumber.push(data[m].section_rate_number.total); //合计数
  }
  //柱状图
  myChartBar.setOption({
    title: {
      text: $("#historic option:selected").html(),
      subtext: $("#historic_table_slot").html(),
    },
    yAxis: {
      data: section
    },
    series: [
      {
        name: '总计',
        data: totalNumber,
        type: 'bar'
      },
      {
        name: '合格',
        data: excellentNumber
        ,type: 'bar'
      },
      {
        name: '优良',
        data: qualifiedNumber,
        type: 'bar'
      }
    ]
  });
  //表格
  var sectionHtml="<th>类型</th>",excellentNumberHtml="<th>优良单元工程</th>",qualifiedHtml="<th>合格单元工程</th>",totalHtml="<th>验收总数量</th>",excellentHtml="<th>优良率</th>";
  for (var j = 0;j<section.length;j++){
    sectionHtml += '<th>'+section[j]+'</th>';
    excellentNumberHtml += '<td>'+excellentNumber[j]+'</td>';
    qualifiedHtml += '<td>'+qualifiedNumber[j]+'</td>';
    totalHtml += '<td>'+totalNumber[j]+'</td>';
    excellentHtml += '<td>'+excellent[j]+'%</td>';
  }
  $('#tableItem thead tr').append(sectionHtml);
  $('#tableItem tbody tr:first-child').append(qualifiedHtml);
  $('#tableItem tbody tr:nth-child(2)').append(excellentNumberHtml);
  $('#tableItem tbody tr:nth-child(3)').append(totalHtml);
  $('#tableItem tbody tr:nth-child(4)').append(excellentHtml);
  $('.tableBox').show();
}
//生成历史记录option
function createOption(data) {
  var html  = "";
  for(var i = 0;i<data.length ;i++){
    var timer = data[i].split('-');
    html += '<option value="'+data[i]+'">'+timer[0]+'年'+timer[1]+'月份单元工程质量验收情况统计</option>'
  }
  $("#historic").append(html);

}
//获取历史记录
$.ajax({
  url:"./getAllMonth",
  type:"GET",
  dataType:"JSON",
  success:function (res) {
    if(res.code==1){
      createOption(res.data.reverse());
      monthslot = res.month.reverse(); //时间段
      $("#historic_table").html($("#historic option:selected").html()); //表格标题
      $("#historic_table_slot").html(monthslot[0]);//表格时间段
      getDefault();
    }
  }
});
//切换历史版本
$("#historic").on("change",function () {
  getDefault();
});
//默认显示的柱状图
function getDefault() {
  var index = $("#historic").find("option:selected").index(); //选中的index
  $("#historic_table").html($("#historic").find("option:selected").html()); //表格标题
  $("#historic_table_slot").html(monthslot[index]); //表格时间段
  //发请求拉图表数据
  $.ajax({
    url:"./getIndexLeft",
    type:"POST",
    data:{time_slot:$("#historic_table_slot").html()},
    dataType:"JSON",
    success:function (res) {
      if(res.code==1){
        getInitLeft(res.data.section,res.data.form_result_result);
      }else{
        layer.msg(res.msg);
      }
    }
  });
}

//生成年度option
function createOptionYear(data) {
  var html  = "";
  for(var i = 0;i<data.length ;i++){
    html += '<option value="'+data[i]+'">'+data[i]+'年</option>'
  }
  $("#year").append(html);

}
//获取年度选择
$.ajax({
  url:"./getAllYear",
  type:"GET",
  dataType:"JSON",
  success:function (res) {
    if(res.code==1){
      createOptionYear(res.data.reverse());
      getDefaultLine();
    }
  }
});
//切换年度
$("#year").on("change",function () {
  getDefaultLine();
});
//默认显示的折线图

function getDefaultLine(){
  //发请求拉图表数据
  $.ajax({
    url:"./getIndexRight",
    type:"POST",
    data:{year:$("#year").val()},
    dataType:"JSON",
    success:function (res) {
      if(res.code==1){
        getIndexRight(res.data.section,res.data.form_result_result);
      }else{
        layer.msg(res.msg);
      }
    }
  });
}
//选择要展示的
$("#showSelect").on("change",function () {
  var selectArr = myChartLine.getOption().legend[0].data; //全部可选
  var obj = {};
  var $that = $(this).val();
  var val = false;
  //遍历找出是否显示
  for(var key in selectArr){
    if($that=="yes全部"){
      obj[selectArr[key]] = true;
    }else if(selectArr[key]==$that){
      obj[selectArr[key]] = true
    }else{
      obj[selectArr[key]] = val;
    }
  }
  //筛选
  myChartLine.setOption({
    legend:{
      selected : obj
    }
  });
})
