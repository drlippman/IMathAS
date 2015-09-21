$(document).ready(function () {
    initEditor();
    tinyMCE.triggerSave();

    $('.core-options').hide();
    $('.advance-options').hide();
    var img = document.getElementById('img');
    var img1 = document.getElementById('img1');

});
var cnt = 0;
var cnt1 = 0;
var NoneClick = 1;
function chgfb() {
    if (document.getElementById("deffeedback").value=="Practice" || document.getElementById("deffeedback").value=="Homework") {
    document.getElementById("showanspracspan").className = "show";
    document.getElementById("showansspan").className = "hidden";
    document.getElementById("showreattdiffver").className = "hidden";
    } else {
    document.getElementById("showanspracspan").className = "hidden";
    document.getElementById("showansspan").className = "show";
    document.getElementById("showreattdiffver").className = "show";
    }
if (document.getElementById("deffeedback").value=="Practice") {
    document.getElementById("stdcntingb").className = "hidden";
    document.getElementById("praccntingb").className = "formright";
    } else {
    document.getElementById("stdcntingb").className = "formright";
    document.getElementById("praccntingb").className = "hidden";
    }
}
function chgcopyfrom() {
    if (document.getElementById('copyfrom').value == 0) {
       NoneClick = 1;
            if(cnt == 1 || (hidden == 1 && cnt==1)) {
                $('#customoptions').removeClass("hidden");
                document.getElementById('customoptions').className="show";
            }
            if(cnt1 == 1) {
                $('#customoptions1').removeClass("hidden");
                document.getElementById('customoptions1').className="show";
        }
    document.getElementById('copyfromoptions').className="hidden";
    } else {
        NoneClick = 0;
    document.getElementById('customoptions').className="hidden";
    document.getElementById('customoptions1').className="hidden";
    document.getElementById('copyfromoptions').className="show";
        var hidden = 1;
    }
}
function apwshowhide(s) {
    var el = document.getElementById("assmpassword");
    if (el.type == "password") {
    el.type = "text";
    s.innerHTML = "Hide";
    } else {
    el.type = "password";
    s.innerHTML = "Show";
    }
}

function xyz()
{
    var img = document.getElementById('img');
    $('#customoptions').toggle();
    if(NoneClick == 1) {
        $('#customoptions').removeClass("hidden");
    }
    if(cnt == 0)
    {

        $('.clickme').css('background-color','#fafafa');
        $('.core-options').css('background-color','#fafafa');
        img.src= '../../img/assessMinusIcon.png';
        cnt++;
    }else if(cnt > 0)
    {
        if(NoneClick == 1) {
            $('#customoptions').addClass("hidden");
        }
        $('.clickme').css('background-color','#f0f0f0');
        img.src= '../../img/assessAddIcon.png';
        cnt = 0;
    }

}

function xyz1()
{
    var img1 = document.getElementById('img1');
    $('#customoptions1').toggle();
    if(NoneClick == 1) {
        $('#customoptions1').removeClass("hidden");
    }
    if(cnt1 == 0)
    {
        $('.clickmegreen').css('background-color','#fafafa');
        $('.advance-options').css('background-color','#fafafa');
        img1.src= '../../img/assessMinusIcon.png';
        cnt1++;
    }else if(cnt1 > 0)
    {
        if(NoneClick == 1) {
            $('#customoptions1').addClass("hidden");
        }
        $('.clickmegreen').css('background-color','#f0f0f0');
        img1.src= '../../img/assessAddIcon.png';
        cnt1 = 0;
    }
}
