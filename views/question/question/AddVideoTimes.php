<?php
use yii\helpers\Html;
use app\components\AppUtility;
use yii\bootstrap\ActiveForm;
use app\components\AppConstant;
$this->title = AppUtility::t('Video Navigation',false);
$cname= $course->name;
if($courseId == 'admin')
{
    $cname = 'Admin';
}
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="item-detail-header">
    <?php if($params['cid'] == "admin"){ ?>
        <?php echo $this->render("../../itemHeader/_indexWithLeftContent",['link_title'=>['Home',$cname,'ManageQuestionSet'], 'link_url' => [AppUtility::getHomeURL().'site/index',AppUtility::getHomeURL().'course/course/course?cid='.$courseId.'&aid='.$params['aid'] ,AppUtility::getHomeURL().'question/question/manage-question-set?cid=admin'] ,'page_title' => $this->title]);?>
    <?php } else{ ?>
        <?php echo $this->render("../../itemHeader/_indexWithLeftContent",['link_title'=>['Home',$cname,'Add/Remove Question'], 'link_url' => [AppUtility::getHomeURL().'site/index',AppUtility::getHomeURL().'course/course/course?cid='.$courseIdadmin,AppUtility::getHomeURL().'question/question/add-questions?cid='.$courseId.'&aid='.$aid] ,'page_title' => $this->title]);?>
    <?php }?>
</div>
<div class="title-container">
    <div class="row">
        <div class="vertical-align title-page">
            <?php echo $this->title; ?>
        </div>
    </div>
</div>
<div class="item-detail-content padding-top-two-pt-em">
    <?php echo $this->render("../../course/course/_toolbarTeacher", ['course' => $course, 'section' => '']);?>
</div>
<div class="tab-content shadowBox col-md-12 col-sm-12 add-video-times-shadowbox">
    <div class="padding-left-zero shadow-content modify-data-shadow-box col-sm-12 col-md-12">
        <h2><?php AppUtility::t('Video Navigation and Question Cues');?></h2>
        <div class="col-md-12 col-sm-12 padding-left-zero">
            <div class="col-md-7 col-sm-12 padding-left-zero">
            <p class="padding-top-zero"><?php AppUtility::t('This page allows you to setup your assessment to be cued to a video.  For each
                question, give a title to the video segment that leads up to that question, and select
                the time when that segment ends and the question should show.  You can grab this
                from the playing video, type the time in min:sec form.  Make sure all times are at least
                one second before the end of the video.');?></p>

            <p><?php AppUtility::t('If your video contains a followup segment to a question (such as a solution),
                you can indicate this and specify when the followup ends.  The next segment will
                then start from the end of this followup.');?></p>
            </div>
            <div class="col-md-5 col-sm-12 add-video-video-player">
                <div id="player"></div>
            </div>
        </div>
        <form method="post" style="clear:both;" onsubmit="return validatevidform(this);">
        <div class="col-md-12 col-sm-12 padding-left-zero padding-top-bottom-twenty">
            <span class="col-md-2 col-sm-3 padding-left-zero padding-top-seven"><?php AppUtility::t('YouTube video ID');?></span>
            <span class="col-md-4 col-sm-4 padding-left-zero">
                <input class="form-control-import-question" type="text" name="vidid" id="vidid" value="<?php echo $vidId;?>"/>
            </span>
            <span class="col-md-2 col-sm-2 padding-left-zero">
                <input type="button" value="Load Video" onclick="loadnewvideo()"/>
            </span>
        </div>

            <?php

            for ($i=0;$i<$n;$i++)
            {
                echo '<div class="col-md-12 col-sm-12 insblock margin-bottom-one-pt-five-em" id="insat'.$i.'">';
                echo '<span class="col-md-6 col-sm-6 padding-left-zero padding-top-ten padding-bottom-ten">
                <a class="" href="javascript:void(0)" onclick="addsegat('.$i.'); return false;">Add video segment break</a>
                </span>
                </div>';
                if (isset($qn[$i]))
                {
                    echo '<div class="vidsegblock col-md-12 col-sm-12 padding-left-zero margin-bottom-one-pt-five-em">';
                    echo '<div class="col-md-12 col-sm-12 padding-top-fifteen padding-bottom-five">
                    <span class="col-md-2 col-sm-2 padding-left-zero padding-right-zero padding-top-seven">Segment title</span>
                    <span class="col-md-3 col-sm-4 margin-left-minus-five-per">
                        <input type="text" class="seg-title" name="segtitle'.$i.'" value="'.$title[$i].'"/>
                    </span>';
                    echo '<span class="col-md-1 col-sm-1 padding-top-seven padding-right-zero">Ends at</span>
                    <span class="col-md-1 col-sm-2">
                    <input class="seg-title" type="text" size="4" name="segend'.$i.'" id="segend'.$i.'" value="'.$endTime[$i].'"/>
                    </span>';
                    echo '<span class="col-md-1 col-sm-2">
                    <input type="button" value="grab" onclick="grabcurvidtime('.$i.',0);"/>
                    </span>';
                    echo '<span class="col-md-4 col-sm-6 padding-left-zero addvt-question-text-padding-mobile">Question '.($qn[$i]+1).' '.$qTitle[$qidByNum[$qn[$i]]].'</span>';
                    echo '<input type="hidden" name="qn'.$i.'" value="'.$qn[$i].'"/></div>';

                    echo '<div class="col-md-12 col-sm-12 padding-top-ten padding-bottom-ten">
                    <span class="col-md-4 col-sm-4 padding-left-zero">
                    <span>Has followup?</span>
                     <span><input type="checkbox" name="hasfollowup'.$i.'" value="1" ';
                    if ($hasFollowUp[$i])
                    {
                        echo 'checked="checked" onclick="updatefollowup('.$i.',this);" /></span></span></div>
                        <span class="col-md-12 col-sm-12 padding-top-bottom-ten" id="followupspan'.$i.'">';
                    }
                    else
                    {
                        echo ' onclick="updatefollowup('.$i.',this);" /></span></span></div>
                        <span class="col-md-12 col-sm-12 padding-top-bottom-ten" id="followupspan'.$i.'" style="display:none;">';
                    }
                    echo '<span class="col-md-2 col-sm-2 padding-left-zero padding-right-zero padding-top-seven">Followup title</span>
                    <span class="col-md-3 col-sm-4 margin-left-minus-five-per">
                    <input class="seg-title" type="text" size="20" name="followuptitle'.$i.'" value="'.$followUpTitle[$i].'"/> </span>';
                    echo '<span class="col-md-1 col-sm-1 padding-right-zero padding-top-seven">Ends at</span>
                    <span class="col-md-1 col-sm-2">
                        <input class="seg-title" type="text" size="4" name="followupend'.$i.'" id="followupend'.$i.'" value="'.$followUpEndDTime[$i].'"/>
                    </span>';
                    echo '<span class="col-md-1 col-sm-2"><input type="button" value="grab" onclick="grabcurvidtime('.$i.',1);"/></span>';
                    echo '<span class="col-md-3 col-sm-4 padding-top-seven padding-left-zero addvt-question-text-padding-mobile">Show link in navigation?
                    <input type="checkbox" name="showlink'.$i.'" value="1" ';
                    if ($showLink[$i])
                    {
                        echo 'checked="checked"';
                    }
                    echo '/></span>
                    </span>';
                }
                else
                {
                    echo '<div class="col-md-12 col-sm-12 vidsegblock">';
                    echo '<span>Segment title</span>
                    <input class="seg-title" type="text" size="20" name="segtitle'.$i.'" value="'.$title[$i].'"/> ';
                    echo '<span class="col-md-1 col-sm-1 padding-top-seven padding-right-zero">Ends at</span>
                    <input class="seg-title" type="text" size="4" name="segend'.$i.'" id="segend'.$i.'" value="'.$endTime[$i].'"/> ';
                    echo '<input type="button" value="grab" onclick="grabcurvidtime('.$i.',0);"/>
                    <a href="javascript:void(0)" onclick="return deleteseg(this);">[Delete]</a>
                    </div>';
                }
                echo '</div>';
            }
            echo '<div class="col-md-12 col-sm-12 insblock margin-bottom-one-pt-five-em" id="insat'.$n.'">
            <span class="col-md-6 col-sm-6 padding-left-zero padding-top-ten padding-bottom-ten">';
            echo '<a href="javascript:void(0)" onclick="addsegat('.$n.'); return false;">Add video segment break</a>
            </span></div>';
            echo '<div class="vidsegblock col-md-12 col-sm-12 padding-top-bottom-ten">';
            echo '<span class="col-md-4 col-sm-6 padding-top-seven">Remainder of video segment title (if any)</span>
            <span class="col-md-3 col-sm-3 padding-left-zero margin-left-minus-three">
                <input class="seg-title" type="text" size="20" name="finalseg" value="'.$finalSegTitle.'"/>
            </span>
            </div>';
            echo '<div class="col-md-4 col-sm-4 padding-left-zero padding-top-fifteen"><input type="submit" value="Submit"/></div>';
            echo '</form>';
            ?>
    </div>
</div>
<?php
echo '<script type="text/javascript">var curnumseg = '.$n.';</script>';
?>
<script type="text/javascript">
    var tag = document.createElement('script');
    tag.src = "//www.youtube.com/player_api";
    var firstScriptTag = document.getElementsByTagName('script')[0];
    firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
    var player;
    var vidid = "<?php echo $vidid;?>";
    function validatevidform(el)
    {
        var els = el.getElementsByTagName("input");
        var lastsegtime = 0;
        var hasfollowup = false;
        for (var i=0; i<els.length; i++) {
            if (els[i].name.match(/segtitle/)) {
                if (els[i].value=="")
                {
                    var msg="Please give all segments titles";
                    CommonPopUp(msg);
                    els[i].focus();
                    return false;
                }
            } else if (els[i].name.match(/vidid/)) {
                if (els[i].value=="")
                {
                    var msg="Please provide a video ID";
                    CommonPopUp(msg);
                    els[i].focus();
                    return false;
                }
            } else if (els[i].name.match(/segend/)) {
                if (els[i].value=="")
                {
                    var msg="Please supply end times for all segments";
                    CommonPopUp(msg);
                    els[i].focus();
                    return false;
                }
                if (els[i].value.match(/:/)) {
                    var v = els[i].value.split(':');
                    v = v[0]*60 + v[1]*1;
                } else {
                    var v = els[i].value*1;
                }
                if (v<lastsegtime)
                {
                    var msg="Make sure each segment's end time is later than previous segments";
                    CommonPopUp(msg);
                    els[i].focus();
                    return false;
                }
                lastsegtime = v;
            } else if (els[i].name.match(/hasfollowup/)) {
                hasfollowup = els[i].checked;
            } else if (els[i].name.match(/followuptitle/) && hasfollowup) {
                if (els[i].value=="")
                {
                    var msg="Please give all segments titles";
                    CommonPopUp(msg);
                    els[i].focus();
                    return false;
                }
            } else if (els[i].name.match(/followupend/) && hasfollowup) {
                if (els[i].value=="")
                {
                    var msg="Please supply end times for all segments";
                    CommonPopUp(msg);
                    els[i].focus();
                    return false;
                }
                if (els[i].value.match(/:/)) {
                    var v = els[i].value.split(':');
                    v = v[0]*60 + v[1]*1;
                } else {
                    var v = els[i].value*1;
                }
                if (v<lastsegtime) {
                    var msg="Make sure each segment's end time is later than previous segments";
                    CommonPopUp(msg);
                    els[i].focus();
                    return false;
                }
                lastsegtime = v;
            }

        }
        return true;
    }
    function onYouTubePlayerAPIReady()
    {
        if (vidid!="") {
            loadPlayer();
        }
    }

    function loadPlayer()
    {
        player = new YT.Player('player', {
            height: 270,
            width: 443,
            videoId: vidid,
            playerVars: {'autoplay': 0, 'wmode': 'transparent', 'fs': 0, 'controls':1, 'rel':0, 'modestbranding':1, 'showinfo':0}
        });
    }
    function loadnewvideo()
    {
        if (vidid=="") {
            vidid = document.getElementById("vidid").value;
            loadPlayer();
        } else {
            vidid = document.getElementById("vidid").value;
            player.cueVideoById(vidid);
        }
    }
    function grabcurvidtime(n,type)
    {
        //do youtube video logic here
        if (!player || player.getPlayerState() != 1) { return;}
        var t =  Math.floor(player.getCurrentTime());
        var o;
        if (t < 60) {
            o = t;
        } else {
            o = Math.floor(t/60) + ":" + ((t%60<10)?'0'+(t%60):(t%60));
        }
        if (type==0) {
            document.getElementById("segend"+n).value=o;
        } else {
            document.getElementById("followupend"+n).value=o;
        }
    }
    function updatefollowup(n,el)
    {
        if (el.checked) {
            document.getElementById("followupspan"+n).style.display = "inline";
        } else {
            document.getElementById("followupspan"+n).style.display = "none";
        }
    }
    function addsegat(n)
    {
        var insat = document.getElementById("insat"+n);

        var newins = document.createElement("div");
        newins.className = "col-md-12 col-sm-12 insblock margin-bottom-one-pt-five-em";
        newins.id = "insat"+(curnumseg+1);
        newins.innerHTML = '<span class="col-md-6 col-sm-6 padding-left-zero padding-top-ten padding-bottom-ten">' +
            '<a href="javascript:void(0)" onclick="addsegat('+(curnumseg+1)+'); return false;">Add video segment break</a>' +
            '</span>';
        insat.parentNode.insertBefore(newins, insat);

        var html = '<span class="col-md-2 col-sm-2 padding-top-pt-five-em">Segment title</span>' +
            '<span class="col-md-3 col-sm-3 margin-left-minus-five-per"><input class="form-control" type="text" size="20" name="segtitle'+curnumseg+'" value=""/></span>';
        html += '<span class="col-md-1 col-sm-1 padding-top-seven padding-right-zero">Ends at</span> ' +
            '<span class="col-md-1 col-sm-2"><input class="form-control" type="text" size="4" name="segend'+curnumseg+'" id="segend'+curnumseg+'"  value=""/></span>';
        html += '<span class="col-md-1 col-sm-1 padding-left-zero">' +
            '<input class="form-control" type="button" value="grab" onclick="grabcurvidtime('+curnumseg+',0);"/>' +
            '</span>';
        html += '<div class="col-md-1 col-sm-1 padding-top-pt-five-em"><a href="javascript:void(0)" onclick="return deleteseg(this);">[Delete]</a></div>';
        var newseg = document.createElement("div");
        newseg.className = "col-md-12 col-sm-12 vidsegblock margin-bottom-one-pt-five-em padding-top-bottom-one-pt-five-em";
        newseg.innerHTML = html;
        insat.parentNode.insertBefore(newseg, insat);
        curnumseg++;
    }
    function get_previoussibling(n)
    {
        x=n.previousSibling;
        while (x.nodeType!=1) {
            x=x.previousSibling;
        }
        return x;
    }
    function deleteseg(el)
    {
        var message ='';
        message+='Are you sure you want to remove this video segment?';
        var html = '<div><p>'+message+'</p></div>';
        $('<div id="dialog"></div>').appendTo('body').html(html).dialog({
            modal: true, title: 'Delete video Segment', zIndex: 10000, autoOpen: true,
            width: 'auto', resizable: false,
            closeText: "hide",
            buttons:
            {
                "Nevermind": function ()
                {
                    $(this).dialog('destroy').remove();
                    return false;
                },
                "Yes, Remove": function ()
                {
                    var divtodelete = el.parentNode.parentNode;
                    divtodelete.parentNode.removeChild(get_previoussibling(divtodelete));
                    divtodelete.parentNode.removeChild(divtodelete);
                    $(this).dialog('destroy').remove();
                    return false;
                }
            },
            close: function (event, ui) {
                $(this).remove();
            },
            open: function(){
                jQuery('.ui-widget-overlay').bind('click',function(){
                    jQuery('#dialog').dialog('close');
                })
            }
        });
        return false;
    }
</script>