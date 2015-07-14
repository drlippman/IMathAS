var Timer;
var TotalSeconds;

function CreateTimer(TimerID, Time) {
    Timer = document.getElementById(TimerID);
    TotalSeconds = Time;

    UpdateTimer()
    window.setTimeout("Tick()", 1000);
}

function Tick() {
    TotalSeconds -= 1;
    UpdateTimer()
    window.setTimeout("Tick()", 1000);
}

function UpdateTimer() {
    var hour = (Math.floor(TotalSeconds/3600) < 10) ? '0'+Math.floor(TotalSeconds/3600) : Math.floor(TotalSeconds/3600);
    var min = Math.floor((TotalSeconds%3600)/60);
    var sec = ((TotalSeconds%3600)%60 < 10) ? '0'+(TotalSeconds%3600)%60 :(TotalSeconds%3600)%60 ;

    Timer.innerHTML = hour +':'+ Math.floor((TotalSeconds%3600)/60) +':' + sec;
}

function toggletimer() {
    $('#expired').hide();
    if ($("#timerhide").text()=="[x]") {
        $("#timercontent").hide();
        $("#timerhide").text(' [Show Timer]');
        $("#timerhide").attr("title","Show Timer");
    } else {
        $("#timercontent").show();
        $("#timerhide").text("[x]");
        $("#timerhide").attr("title","Hide");
    }
}

