<?php
use yii\helpers\Html;
use app\components\AppUtility;
?>
<script type="text/javascript">var AMTcgiloc = "http://www.imathas.com/cgi-bin/mimetex.cgi";</script>
<script src="<?php echo AppUtility::getHomeURL() ?>js/ASCIIMathTeXImg_min.js?ver=092314\" type=\"text/javascript\"></script>
<script type="text/x-mathjax-config">
if (MathJax.Hub.Browser.isChrome || MathJax.Hub.Browser.isSafari) {
MathJax.Hub.Config({"HTML-CSS": {preferredFont: "STIX", imageFont:null}});
} else {
MathJax.Hub.Config({"HTML-CSS": {preferredFont: "STIX", webFont: "STIX-Web", imageFont:null}});
}
</script>
<script type="text/javascript" src="<?php echo AppUtility::getHomeURL() ?>js/mathjax/MathJax.js?config=AM_HTMLorMML"></script>
<script type="text/javascript">noMathRender = false; var usingASCIIMath = true; var AMnoMathML = false; var MathJaxCompatible = true; function rendermathnode(node) { MathJax.Hub.Queue(["Typeset", MathJax.Hub, node]); } </script>
<script type="text/javascript" charset="utf8" src="<?php echo AppUtility::getHomeURL() ?>js/confirmsubmit.js"></script>
<script type="text/javascript" charset="utf8" src="<?php echo AppUtility::getHomeURL() ?>js/AMhelpers.js"></script>
<script type="text/javascript" charset="utf8" src="<?php echo AppUtility::getHomeURL() ?>js/drawing.js"></script>
<style type="text/css">span.MathJax { font-size: 105%;}</style>
<input type="hidden" id="timerlimit" name="time" value="<?php echo abs($assessment->timelimit)?>">

<html>
<!--    Show total time and remaining time-->
    <?php
    /*Conversion into hour, minute and seconds*/
    $hour = (floor(abs($assessment->timelimit)/3600) < 10) ? '0'+floor(abs($assessment->timelimit)/3600) : floor(abs($assessment->timelimit)/3600);
    $min = floor((abs($assessment->timelimit)%3600)/60);
    ?>
    <input type="hidden" id="hour" name="hour" value="<?php echo $hour;?>">
    <input type="hidden" id="min" name="min" value="<?php echo $min; ?>">
    <input type="hidden" id="endDate" name="endDate" value="<?php echo AppUtility::formatDate($assessment->enddate); ?>">


<?php echo $response; ?>

<script type="text/javascript">
    $(document).ready(function(){
        var timer = $('#timerlimit').val();
        var html = '';
        var hour = $('#hour').val();
        var min = $('#min').val();
        var endDate = $('#endDate').val();

        if(timer != 0)
        {
            html += "<b>Timelimit: "+hour+":"+min+" minutes.</b>" ;
            html += "<span id='timerwrap'><b>";
            html += "<span id='timer'></span> remaining </b></span>"

        }else {
            html += 'Due '+endDate;
        }

        $('#timercontent').append(html);

        window.onload = CreateTimer("timer",timer);
    });

    function toggleintroshow(n) {
        var link = document.getElementById("introtoggle"+n);
        var content = document.getElementById("intropiece"+n);
        if (link.innerHTML.match("Hide")) {
            link.innerHTML = link.innerHTML.replace("Hide","Show");
            content.style.display = "none";
        } else {
            link.innerHTML = link.innerHTML.replace("Show","Hide");
            content.style.display = "block";
        }
    }
    function togglemainintroshow(el) {
        if ($("#intro").hasClass("hidden")) {
            $(el).html("Hide Intro/Instructions");
            $("#intro").removeClass("hidden").addClass("intro");
        } else {
            $("#intro").addClass("hidden");
            $(el).html("Show Intro/Instructions");
        }
    }

    $('.licensePopup').click(function(e)
    {
        e.preventDefault();
        var questionId= $(".question-id").val();
        var html = '<div><p><Strong>Question License</Strong></p>' +
                   '<p>Question ID '+questionId +' (Universal ID 11435814263779)</p>'  +
                   '<p> This question was written by Lippman, David. This work is licensed under the<a target="Licence" href="http://www.imathas.com/communitylicense.html"> IMathAS Community License (GPL + CC-BY)</a> </p>'
                  +'<p>The code that generated this question can be obtained by instructors by emailing abhishek.prajapati@tudip.com</p></div>';
         $('<div  id="dialog"></div>').appendTo('body').html(html).dialog
         ({
            modal: true, title: 'License', zIndex: 10, autoOpen: true,
            width: 'auto', resizable: false,
            closeText: "hide",
            buttons:
            {
                "Cancel": function ()
                {
                    $(this).dialog('destroy').remove();
                    return false;

                }
            }

        });

    });
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
</script>

