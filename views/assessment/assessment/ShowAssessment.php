<?php
use yii\helpers\Html;
use app\components\AppUtility;
?>
<script type="text/javascript">var AMTcgiloc = "http://www.imathas.com/cgi-bin/mimetex.cgi";</script>
<?php AppUtility::includeJS('ASCIIMathTeXImg_min.js') ?>
<script type="text/x-mathjax-config">
if (MathJax.Hub.Browser.isChrome || MathJax.Hub.Browser.isSafari) {
MathJax.Hub.Config({"HTML-CSS": {preferredFont: "STIX", imageFont:null}});
} else {
MathJax.Hub.Config({"HTML-CSS": {preferredFont: "STIX", webFont: "STIX-Web", imageFont:null}});
}
</script>
<script type="text/javascript" src="<?php echo AppUtility::getHomeURL() ?>js/mathjax/MathJax.js?config=AM_HTMLorMML"></script>
<script type="text/javascript">noMathRender = false; var usingASCIIMath = true; var AMnoMathML = false; var MathJaxCompatible = true; function rendermathnode(node) { MathJax.Hub.Queue(["Typeset", MathJax.Hub, node]); } </script>

<?php AppUtility::includeJS('confirmsubmit.js') ?>
<?php AppUtility::includeJS('AMhelpers.js') ?>
<?php AppUtility::includeJS('drawing.js') ?>
<style type="text/css">span.MathJax { font-size: 105%;}</style>
<input type="hidden" id="timerlimit" name="time" value="<?php echo abs($assessment->timelimit)?>">

<html>
<!--    Show total time and remaining time-->
<?php
/*Conversion into hour, minute and seconds*/
$hour = (floor(abs($assessment->timelimit)/3600) < 10) ? '0'+floor(abs($assessment->timelimit)/3600) : floor(abs($assessment->timelimit)/3600);
$min = floor((abs($assessment->timelimit)%3600)/60);
?>
<input type="hidden" id="user-rights" value="<?php echo $user['rights'];?>">
<input type="hidden" id="hour" name="hour" value="<?php echo $hour;?>">
<input type="hidden" id="min" name="min" value="<?php echo $min; ?>">
<input type="hidden" id="endDate" name="endDate" value="<?php echo AppUtility::formatDate($assessment->enddate); ?>">
<input type="hidden" id="endDateString" name="endDate" value="<?php echo $assessment->enddate; ?>">
<input type="hidden" id="startDateString" name="endDate" value="<?php echo $assessment->startdate; ?>">
<input type="hidden" id="reviewDateString" name="endDate" value="<?php echo $assessment->reviewdate; ?>">

<input type="hidden" id="courseId" value='<?php echo $courseId ?>'/>
<input type="hidden" id="assessmentsession" value="<?php echo $assessmentSession->starttime;?>">
<input type="hidden" id="timelimit" value="<?php echo $assessment->timelimit;?>">
<input type="hidden" id="now" value="<?php echo $now;?>">
<input type="hidden" id="to" value="<?php echo $isShowExpiredTime;?>">


<?php if(!empty($isQuestions)){echo $response;}else{?>
    <input type="hidden" id="noQuestion" value="1">
<?php }?>
<div id="ehdd" class="ehdd"><span id="ehddtext"></span> <span onclick="showeh(curehdd);" style="cursor:pointer;">[more..]</span></div>
<div id="eh" class="eh"></div>

<script type="text/javascript">
    $(document).ready(function(){
        var timer = $('#timerlimit').val();
        var html = '';
        var hour = $('#hour').val();
        var min = $('#min').val();
        var endDate = $('#endDate').val();
        var endDateString = $('#endDateString').val();
        var startDateString = $('#startDateString').val();
        var reviewDateString = $('#reviewDateString').val();
        var noQuestion = $('#noQuestion').val();
        var userRights = $('#user-rights').val();
        if(noQuestion == 1){
            noQuestionPopup(userRights);
        }
        initEditor();
        if(timer != 0)
        {
            $("#expired").hide();
            html += "<b>Timelimit: "+hour+":"+min+" minutes.</b>" ;
            html += "<span id='timerwrap'><b>";
            html += "<span id='timer'></span> remaining </b></span>"

        }else if(endDateString == 2000000000 && startDateString == 0 && (reviewDateString == 2000000000 || reviewDateString == 0 || reviewDateString != 0)){
            $("#expired").hide();
            $("#timerhide").hide();
        }
        else{
            $("#expired").hide();
            $("#timerhide").hide();
            html = 'Due '+endDate;
        }
        /**
         * Timelimit in hour and minute
         */
        $('#timercontent').append(html);
        /**
         * Count down for timelimit.
         */

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

    function noQuestionPopup(userRights){
        var courseId = $("#courseId").val();
        var msg = '<div><p>This assessment does not have any questions right now</div>';

        $('<div  id="dialog"></div>').appendTo('body').html(msg).dialog
        ({
            modal: true, title: 'Warning', zIndex: 10000, autoOpen: true,
            width: 'auto', resizable: false,
            closeText: "hide",
            buttons:
            {
                "Go Back": function ()
                {
                    if(userRights == 10)
                    {
                        window.location ="../../course/course/index?cid="+courseId;
                    }else{
                        window.location ="../../instructor/instructor/index?cid="+courseId;
                    }

                    $(this).dialog('destroy').remove();

                }
            },
            close: function (event, ui)
            {
                $(this).remove();
            }
        });
    }

$(document).ready(function ()
    {
        var assessmentsession = $("#assessmentsession").val();

        $("#expired").hide();
        var now = $("#now").val();
        var timelimit = $("#timelimit").val();
        var now_int = parseInt(now);
        var assessmentsession_int =parseInt(assessmentsession);
        var timelimit_int = parseInt(timelimit);
        if((assessmentsession_int + timelimit_int) < now_int)
        {
            $("#timerwrap").hide();
            $("#timerhide").hide();
            $('#expired').show();
            var msg = '<div><p>Your time limit has expired </p>'+
                '<p>If you submit any questions, your assessment will be marked overtime, and will have to be reviewed by your instructor.</p></div>';
            $('<div  id="dialog"></div>').appendTo('body').html(msg).dialog
            ({
                modal: true, title: 'Message', zIndex: 10000, autoOpen: true,
                width: 'auto', resizable: false,
                closeText: "hide",
                buttons:
                {
                    "Okay": function ()
                    {
                        $(this).dialog('destroy').remove();

                        return true;
                    }
                }
            });

        }
    });

</script>

