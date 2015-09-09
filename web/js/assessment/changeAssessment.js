function chgfb() {
    if (document.getElementById("deffeedback").value == "Practice" || document.getElementById("deffeedback").value == "Homework") {
        document.getElementById("showanspracspan").className = "show";
        document.getElementById("showansspan").className = "hidden";
        document.getElementById("showreattdiffver").className = "hidden";
    } else {
        document.getElementById("showanspracspan").className = "hidden";
        document.getElementById("showansspan").className = "show";
        document.getElementById("showreattdiffver").className = "show";
    }
}
function copyfromtoggle(frm, mark) {
    var tds = frm.getElementsByTagName("tr");
    for (var i = 0; i < tds.length; i++) {
        try {
            if (tds[i].className == 'coptr') {
                if (mark) {
                    tds[i].style.display = "none";
                } else {
                    tds[i].style.display = "";
                }
            }

        } catch (er) {
        }
    }

}
function chkgrp(frm, arr, mark) {
    var els = frm.getElementsByTagName("input");
    for (var i = 0; i < els.length; i++) {
        var el = els[i];
        if (el.type == 'checkbox' && (el.id.indexOf(arr + '.') == 0 || el.id.indexOf(arr + '-') == 0 || el.id == arr)) {
            el.checked = mark;
        }
    }
}

function chkgbcat(cat) {
    chkAllNone('qform', 'checked[]', false);
    var els = document.getElementById("alistul").getElementsByTagName("input");
    var regExp = new RegExp(":" + cat + "$");
    for (var i = 0; i < els.length; i++) {
        var el = els[i];
        if (el.type == 'checkbox' && el.id.match(regExp)) {
            el.checked = true;
        }
    }
}
$(function () {
    $(".chgbox").change(function () {
        $(this).parents("tr").toggleClass("odd");
    });
})
