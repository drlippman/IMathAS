if (!window.jQuery) {
    document.write('<script src="jquery.min.js"><\/script>');
}

var imasroot = '/IMathAS'; var cid = 0;

// initSortTable('myTable',Array('S','S','S','S',false,false,false),true);

var usingASCIISvg = true;

function showcourses() {
    var uid=document.getElementById("seluid").value;
    if (uid>0) {window.location='admin.php?showcourses='+uid;}
}
function showgroupusers() {
    var grpid=document.getElementById("selgrpid").value;
    window.location='admin.php?showusers='+grpid;
}
$(function() {
    $(".artl").attr("title","Add or remove additional teachers");
    $(".sl").attr("title","Modify course settings");
    $(".trl").attr("title","Transfer course ownership to someone else");
});

$('#ConfirmDelete').onClick(function() {
    $('#messageBox').toggle();
});

