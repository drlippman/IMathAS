$(document).ready(function () {
    initEditor();
});
function toggleGBdetail(v) {
    document.getElementById("gbdetail").style.display = v?"block":"none";
}
function toggleGBdetail1(v) {
    document.getElementById("datediv").style.display = v?"block":"none";
}
//$(function(){
//    var txt = $("input#name-forum");
//    var func = function(e) {
//        if(e.keyCode === 32){
//            txt.val(txt.val().replace(/\s/g, ''));
//        }
//    }
//    txt.keyup(func).blur(func);
//});
