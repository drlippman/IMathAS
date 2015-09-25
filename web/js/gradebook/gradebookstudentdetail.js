function chgtimefilter() {
    var courseId = $("#course-id").val();
    var tm = document.getElementById("timetoggle").value;
    window.location = "gradebook-testing?stu=&cid="+courseId+"&timefilter=" + tm;
}

 function chglnfilter() {
     var courseId = $("#course-id").val();
     var ln = document.getElementById("lnfilter").value;
     window.location = "gradebook-testing?stu=&cid="+courseId+"&lnfilter=" + ln;
 }

 function chgsecfilter() {
     var sec = document.getElementById("secfiltersel").value;
     var courseId = $("#course-id").val();
     window.location = "gradebook-testing?stu=&cid="+courseId+"&secfilter=" + sec;
 }


