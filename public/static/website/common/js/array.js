//删除数组中的指定元素
Array.prototype.remove = function(val){
    for (var i = 0; i < this.length; i++) {
        if(this[i] === val){
            this.splice(i,1);
            break;
        }
    }
    return this;
}

//数组去重
Array.prototype.removalArray = function(){
    var newArr = [];
    for (var i = 0; i < this.length; i++) {
        if(newArr.indexOf(this[i]) == -1){  //indexOf 不兼容IE8及以下
            newArr.push(this[i]);
        }
    }
    return newArr;
}