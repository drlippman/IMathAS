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
                $('#core-options').removeClass("hidden");
                $('#core-options').addClass('show');
            }
            if(cnt1 == 1) {
                $('#advance-options').removeClass("hidden");
                $('#advance-options').addClass("show");
        }
    $('#copyfromoptions').addClass("hidden");
    document.getElementById('copyfromoptions').className="hidden";
    } else {
        NoneClick = 0;
        $('#core-options').addClass('hidden');
        $('#advance-options').addClass('hidden');
        $('#copyfromoptions').removeClass('hidden');
        $('#copyfromoptions').addClass('show');
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

function coreOptionToggle()
{
    var img = document.getElementById('img');
    $('#core-options').toggle();
    if(NoneClick == 1) {
        $('#core-options').removeClass("hidden");
    }
    if(cnt == 0)
    {

        $('.clickme').css('background-color','#fafafa');
        $('#core-options').css('background-color','#fafafa');
        $('.core-options').css('background-color','#fafafa');
        img.src= '../../img/assessMinusIcon.png';
        cnt++;
    }else if(cnt > 0)
    {
        if(NoneClick == 1) {
            $('#core-options').addClass("hidden");
        }
        $('.clickme').css('background-color','#f0f0f0');
        $('#core-options').css('background-color','#f0f0f0');
        img.src= '../../img/assessAddIcon.png';
        cnt = 0;
    }

}

function advanceOptionToggle()
{
    var img1 = document.getElementById('img1');
    $('#advance-options').toggle();
    if(NoneClick == 1) {
        $('#advance-options').removeClass("hidden");
    }
    if(cnt1 == 0)
    {
        $('.clickmegreen').css('background-color','#fafafa');
        $('#advance-options').css('background-color','#fafafa');
        $('.advance-options').css('background-color','#fafafa');
        img1.src= '../../img/assessMinusIcon.png';
        cnt1++;
    }else if(cnt1 > 0)
    {
        if(NoneClick == 1) {
            $('#advance-options').addClass("hidden");
        }
        $('.clickmegreen').css('background-color','#f0f0f0');
        $('#advance-options').css('background-color','#f0f0f0');
        img1.src= '../../img/assessAddIcon.png';
        cnt1 = 0;
    }
}
