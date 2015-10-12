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

function showhidefb(el,n) {
    el.style.display="none";
    document.getElementById("feedbackholder"+n).style.display = "inline";
    return false;
}
function showhideallfb(s) {
    s.style.display="none";
    var els = document.getElementsByTagName("a");
    for (var i=0;i<els.length;i++) {
        if (els[i].className.match("feedbacksh")) {
            els[i].style.display="none";
        }
    }
    var els = document.getElementsByTagName("span");
    for (var i=0;i<els.length;i++) {
        if (els[i].id.match("feedbackholder")) {
            els[i].style.display="inline";
        }
    }
}

function chgfilter() {
    var cat = document.getElementById("filtersel").value;
    var studentId = $("#student-id").val();
    var courseId = $("#course-id").val();
    window.location = "grade-book-student-detail?cid="+courseId+"&studentId="+studentId+"&catfilter=" + cat;
}

function chgtoggle() {

    var totonleft = $("#totonleft").val();
    var avgontop = $("#avgontop").val();
    var studentId = $("#student-id").val();
    var courseId = $("#course-id").val();
    var includelastchange = $("#includelastchange").val();
    var lastlogin = $("#lastlogin").val();
    var includeduedate = $("#includeduedate").val();
    var altgbmode = 10000 * document.getElementById("toggle4").value + 1000 * parseInt(totonleft) + parseInt(avgontop) + 100 * (document.getElementById("toggle1").value * 1 + document.getElementById("toggle5").value * 1) + 10 * document.getElementById("toggle2").value + 1 * document.getElementById("toggle3").value;
    if (includelastchange) {
        altgbmode += 40;
    }
    if (lastlogin) {
        altgbmode += 4000;
    }
    if (includeduedate) {
        altgbmode += 400;
    }
    window.location = "grade-book-student-detail?cid="+courseId+"&studentId="+studentId+"&gbmode="+altgbmode;
}
function chgstu(el)
{
//         $('#updatingicon').show();
    var courseId = $("#course-id").val();
    window.location = "grade-book-student-detail?cid="+courseId+"&studentId="+el.value;
}
function makeofflineeditable(el) {
    var anchors = document.getElementsByTagName("a");
    for (var i=0;i<anchors.length;i++) {
        if (bits=anchors[i].href.match(/add-grades.*gbitem=(\d+)/)) {
            if (anchors[i].innerHTML.match("-")) {
                type = "newscore";
            } else {
                type = "score";
            }
            anchors[i].style.display = "none";
            var newinp = document.createElement("input");
            newinp.size = 4;
            if (type=="newscore") {
                newinp.name = "newscore["+bits[1]+"]";

            } else {
                newinp.name = "score["+bits[1]+"]";
                newinp.value = anchors[i].innerHTML;

            }
            anchors[i].parentNode.appendChild(newinp);
            var newtxta = document.createElement("textarea");
            newtxta.name = "feedback["+bits[1]+"]";
            newtxta.cols = 50;
            var feedbtd = anchors[i].parentNode.nextSibling.nextSibling.nextSibling;
            newtxta.value = feedbtd.innerHTML;
            feedbtd.innerHTML = "";
            feedbtd.appendChild(newtxta);
        }
    }
    document.getElementById("savechgbtn").style.display = "";
    el.onclick = null;
}



