var project = new PlusProject();
project.setStyle("width:100%;height:727px");
var columns = [];
var WbsColumn = {
    name: "WBS",
    header: "WBS<br/>String",
    field: "WBS",                   //WBS
    width: 50,
    editor: {
        type: "textbox"
    }
};
var UnitColumn = {
    name: "Unit",
    header: "单位<br/>String",
    field: "Unit",                  //Unit
    width: 80,
    editor: {
        type: "textbox"
    }
};
var stringColumn = {
    name: "Quantities",
    header: "工程量<br/>String",
    field: "Quantities",            //Quantities
    width: 90,
    editor: {
        type: "textbox"
    }
};
var RelationModel = {
    name: "Relation",
    header: "是否关联模型<br/>String",
    field: "Relation",            //Relation
    width: 90,
    editor: {
        type: "textbox"
    }
};
var operate = {
    name: "operate",
    header: "操作<br/>String",
    field: "operate",            //operate
    width: 90,
    editor: {
        type: "textbox"
    }
};
columns.push(WbsColumn);
columns.push( new PlusProject.StatusColumn());
columns.push(new PlusProject.NameColumn());
columns.push(UnitColumn);
columns.push(stringColumn);
columns.push(new PlusProject.DurationColumn());
columns.push(new PlusProject.StartColumn());
columns.push(new PlusProject.FinishColumn());
columns.push(RelationModel);
columns.push(operate);
project.setColumns(columns);
project.setShowGanttView(false);
project.render(document.getElementById("project"));
$.ajax({
    async : false,
    url: "/progress/monthlyplan/planMonthly",
    type: "post",
    data: {section_id:set_id,plan_year:year_id,plan_type:1},
    dataType: "json",
    success: function (res) {
        if(res.code == 1){
            var orgs = res.data;
            if(orgs.length == 0){
                $("#seleMonthly").val("");
                $("#seleYear").empty();
                layui.form.render('select');
            }else if(orgs.length > 0){
                $("#seleYear").empty();
                for(var i=0;i<orgs.length;i++){
                    $("#seleMonthly").append($("<option/>").text(orgs[i]));
                }
            }
            layui.form.render();
            //甘特图的获取数据/
            load($("#seleBids").val(),$("#testYear").val(),$("#seleMonthly").val());
        }if(res.code != 1){
            layer.msg(res.msg)
        }
    }
});