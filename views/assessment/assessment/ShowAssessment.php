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

<input type="hidden" id="courseId" value='<?php echo $courseId ?>'/>
<input type="hidden" id="assessmentsession" value="<?php echo $assessmentSession->starttime;?>">
<input type="hidden" id="timelimit" value="<?php echo $assessment->timelimit;?>">
<input type="hidden" id="now" value="<?php echo $now;?>">

<?php if(!empty($isQuestions)){echo $response;}else{?>
    <script>
            noQuestionPopup();
    </script>
<?php }?>

<div id="ehdd" class="ehdd"><span id="ehddtext"></span> <span onclick="showeh(curehdd);" style="cursor:pointer;">[more..]</span></div>
<div id="eh" class="eh"></div>

<script type="text/javascript">

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
    function noQuestionPopup(){

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
                    window.location ="../../course/course/index?cid="+courseId;
                    $(this).dialog('destroy').remove();

                }
            },
            close: function (event, ui)
            {
                $(this).remove();
            }
        });
    }
    $(document).ready(function (e)
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
            $('<div id="dialog"></div>').appendTo('body').html(msg).dialog
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
//        eqtipBindEvent()
    });
</script>

