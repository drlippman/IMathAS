<?php
use app\components\AppUtility;
$this->title = $course->name;
use serhatozles\htmlawed\htmLawed;
require_once("../filter/filter.php");
$this->title = $wiki->name;
$this->params['breadcrumbs'][] = ['label' => $course->name, 'url' => ['/course/course/course?cid='.$course->id]];
$this->params['breadcrumbs'][] = $this->title;
$editByDate=($wikiTotalData[0]['editbydate']);
?>
    <style type="text/css">
        a.grayout {color: #ccc; cursor: default;}  del {color: #f99; text-decoration:none;} ins {color: #6f6; text-decoration:none;} .wikicontent {padding: 10px;}</style>
    <div class="item-detail-header">
        <?php echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title'=>['Home',$course->name], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL().'course/course/course?cid='.$course->id], 'page_title' => $this->title]); ?>
    </div>
    <div class = "title-container">
        <div class="row">
            <div class="pull-left page-heading">
                <div class="vertical-align title-page"><?php echo $this->title ?></div>
            </div>
        </div>
    </div>
<div class="tab-content shadowBox non-nav-tab-item">

        <input type="hidden" class="wiki-id" value="<?php echo $wiki->id;?>">
        <input type="hidden" class="course-id" value="<?php echo $course->id;?>">
<?php
if (isset($isTeacher) && $groupId >0 && !isset($curGroupName)) {
    $grpnote = $groupNote;
} else {
    $grpnote = 'this';
}
if (isset($delAll) && $isTeacher) {
    echo '<p>Are you SURE you want to delete all contents and history for '.$grpnote.' Wiki page?</p>';
    echo "<p><button type=\"button\" onclick=\"window.location.href='show-wiki?courseId=$courseId&wikiId=$id&delall=true$framed'\">Yes, I'm Sure</button> | ";
    echo "<button type=\"button\" class=\"secondarybtn\" onclick=\"window.location.href='viewwiki.php?cid=$cid&id=$id&grp=$groupid$framed'\">Nevermind</button></p>";

} else if($delRev && $isTeacher) {
    echo '<p>Are you SURE you want to delete all revision history for '.$grpnote.' Wiki page?  The current version will be retained.</p>';

    echo "<p><button type=\"button\" onclick=\"window.location.href=".AppUtility::getURLFromHome('wiki', 'wiki/show-wiki?courseId='.$courseId.'&wikiId='.$id.'&delrev=true')."\">Yes, I'm Sure</button> | ";
    echo "<button type=\"button\" class=\"secondarybtn\" onclick=\"window.location.href='viewwiki.php?cid=$cid&id=$id&grp=$groupid$framed'\">Nevermind</button></p>";
} else if ($revert) {
    echo '<p>Are you SURE you want to revert to revision '.$disprev.' of '.$grpnote.' Wiki page?  All changes after that revision will be deleted.</p>';

    echo "<p><button type=\"button\" onclick=\"window.location.href='show-wiki?courseId=$courseId&wikiId=$id&torev=$toRev&revert=true$framed'\">Yes, I'm Sure</button> | ";
    echo "<button type=\"button\" class=\"secondarybtn\" onclick=\"window.location.href='viewwiki.php?cid=$cid&id=$id&grp=$groupid$framed'\">Cancel</button></p>";

} else if ($snapshot) {
    echo "<p class='padding-left-ten'>Current Version Code.  <a href=".AppUtility::getURLFromHome('wiki', 'wiki/show-wiki?courseId=' .$courseId. '&wikiId='.$id).">Back</a></p>";
    echo '<div class="editor" style="font-family:courier; padding: 10px;">';
    echo str_replace('&gt; &lt;',"&gt;<br/>&lt;",filter($text));
    echo '</div>';
} else { //default page display
    if ($isgroup && $isTeacher) {
        echo '<p>Viewing page for group: ';
        AppUtility::writeHtmlSelect('gfilter',$stugroup_ids,$stugroup_names,$groupid,null,null,'onchange="chgfilter()"');
        echo '</p>';
    } else if ($isgroup) {
        echo "<p>Group: $curgroupname</p>";
    }
    if ($isTeacher) {
        echo '<div class="col-md-12 col-sm-12 print-test-header margin-left-zero padding-top-fifteen">';
        if ($isgroup) {
            $grpnote = "For this group's wiki: ";
        }?>

        <button type="button" onclick='clearContent(<?php echo $courseId?>,<?php echo $id?>)'>Clear Page Contents</button>
        <button type="button" onclick='clearHistory(<?php echo $courseId?>,<?php echo $id?>)'>Clear Page History</button>
        <a href="<?php echo AppUtility::getURLFromHome('wiki', 'wiki/show-wiki?courseId=' .$courseId. '&wikiId='.$id.'&grp='.$groupId.'&snapshot=true'.$framed)?>">Current Version Snapshot</a></div>
    <?php }
    ?>

    <br><br class="form"><p><span id="revisioninfo" class="padding-left">Revision <?php echo $numRevisions; ?>
            <?php if ($numRevisions > 0) {
                echo ".  Last edited by $lastEditedBy on $lastEditTime.";
            }
            ?>
    </span>

        <?php
        $addr2 = AppUtility::getURLFromHome('wiki','wiki/show-wiki?revert=ask&courseId='.$courseId.'&wikiId='.$id);
        ?>
        <input type="hidden" id='revert' value="<?php echo $addr2?>">
        <?php
        if ($numRevisions > 1) {
            $last = $numRevisions - 1;
            echo '<span id="prevrev"><input type="button" value="Show Revision History" id="show-revision"/></span>';
            echo '<div class="padding-left"><span id="revcontrol" style="display:none;">'; AppUtility::t('Revision history');
            echo'<a href="#" id="first" onclick="jumpto(1)"> &nbsp;'; AppUtility::t('First'); echo'</a>
            <a id="older" href="#" onclick="seehistory(1); return false;"> &nbsp;'; AppUtility::t('Older'); echo'</a> ';
            echo '<a id="newer" class="grayout" href="#" onclick="seehistory(-1); return false;"> &nbsp;'; AppUtility::t('Newer'); echo'</a>
            <a href="#" class="grayout" id="last" onclick="jumpto(0)"> &nbsp;'; AppUtility::t('Last'); echo'  </a>
            &nbsp; <input type="button" id="showrev" value="Show Changes" onclick="showrevisions()" />';
            if ($isTeacher) { ?>
                <a id="revrevert" style="display:none;" href="#"><?php AppUtility::t('Revert to this revision')?></a>
           <?php }
            echo '</div>';
        }?>
    <div class="editor" style="margin-right: 20px; margin-left: 20px">
        <?php if ($isTeacher || ($editByDate>0 && $editByDate > time())){?>
        <span>
            <a href="<?php echo AppUtility::getURLFromHome('wiki', 'wiki/edit-page?courseId=' .$course->id .'&wikiId=' .$wiki->id ); ?>"
               class="btn btn-primary btn-sm"><?php AppUtility::t('Edit this page');?></a>
        </span>
        <?php } ?>
        <br/><br class="form">

        <?php if(!empty($wikiRevisionData)){

            foreach($wikiRevisionData as $key => $singleWikiRevision) {
                ?>
                <div class="col-md-12 col-sm-12 padding-left-zero padding-bottom"><div contenteditable="false" id='wikicontent' class="form-control text-area-alignment" name='wikicontent' style='width: 100%; height: 400px; overflow: auto'>
                    <?php
                        echo filter($text); ?>
                </div></div>
            <?php }?>
        <?php }?>
    </div>
    </div>
    <script>
    var original = null;
    var wikihistory = null;
    var userinfo = null;
    var curcontent = null;
    var curversion = 0;
    var contentdiv = null;
    var showrev = 0;
    var AHAHrevurl = "'.$addr.'";
    var reverturl = $('#revert').val();
    usingASCIIMath = false;
    /**
     * OnClick of show revision history to display first, last, older and newer buttons.
     */
    $(document).ready(function(){
        $(function() {
            $("#prevrev").click(function() {
                $("#revcontrol").toggle();
            });
        });
    });
    /**
     * To get JSON data on click of show revision history from wikiUtility.
     */
    $('#show-revision').click(function(){
        var courseId = $('.course-id').val();
        var wikiId = $('.wiki-id').val();
        var AHAHrevurl = $.get( "get-revisions?courseId="+courseId+"&wikiId="+wikiId, function( data ) {
            jsonData = $.parseJSON(data);
            original = jsonData.o;
            userinfo = jsonData.u;
            curcontent = original.slice();
            wikihistory = jsonData.h;
            contentdiv = document.getElementById("wikicontent");
            contentdiv.innerHTML = original.join(' ');
            wikirendermath();
            document.getElementById("prevrev").innerHTML="";

        });
    });

    /**
     *
     * To show all history of wiki revision from jsonData.
     */
    function seehistory(n) { //+ older, - newer
        if (n>0 && curversion==wikihistory.length-1) {

            return false;
        } else if (n<0 && curversion==0) {
            return false;
        }
        if (n==1) {
            curversion++;
            curcontent = applydiff(curcontent,curversion);
            username = userinfo[wikihistory[curversion].u];
            time = wikihistory[curversion].t;
        } else {
            curversion += n;

            curcontent = jumptoversion(curversion);
            username = userinfo[wikihistory[curversion].u];
            time = wikihistory[curversion].t;
        }
        if (showrev==1) {
            contentdiv.innerHTML = colorrevisions(curcontent,curversion);
        } else {
            contentdiv.innerHTML = curcontent.join(' ');
        }
        if (curversion==0) {
            document.getElementById("newer").className = "grayout";
            document.getElementById("last").className = "grayout";
            document.getElementById("revrevert").style.display = "none";
        } else {
            document.getElementById("newer").className = "";
            document.getElementById("last").className = "";
            document.getElementById("revrevert").style.display = "";
            document.getElementById("revrevert").href = reverturl+"&torev="+wikihistory[curversion].id+"&disprev="+(wikihistory.length-curversion);
        }
        if (curversion==wikihistory.length-1) {
            document.getElementById("older").className = "grayout";
            document.getElementById("first").className = "grayout";
        } else {
            document.getElementById("older").className = "";
            document.getElementById("first").className = "";
        }
        html = 'Revision '+(wikihistory.length - curversion)+'.  Edited by '+username+' on '+time;
        document.getElementById("revisioninfo").innerHTML = html;
        wikirendermath();
        return false;
    }

    /**
     *   To display first and last revision of wiki.
     */
    function jumpto(n) {  //1: oldest, 0: most recent
        if (n==0) {
            seehistory(-1*curversion);
        } else {
            seehistory(wikihistory.length - curversion-1);
        }
    }
    function applydiff(current, ver) {
        //0: insert, 1: delete, 2 replace.
        //
        var diff = wikihistory[ver].c;
        for (var i=diff.length-1; i>=0; i--) {
            if (diff[i][0]==2) { //replace
                current = current.slice(0,diff[i][1]).concat(diff[i][3]).concat(current.slice(diff[i][1]+diff[i][2]));
                //current.splice(diff[i][1], diff[i][2], diff[i][3]);
            } else if (diff[i][0]==0) {//insert
                current = current.slice(0,diff[i][1]).concat(diff[i][2]).concat(current.slice(diff[i][1]));
                //current.splice(diff[i][1], 0, diff[i][2]);
            } else if (diff[i][0]==1) {//delete
                current.splice(diff[i][1], diff[i][2]);
            }
        }
        return current;
    }

    function jumptoversion(ver) {
        var cur = original.slice();
        if (ver==0) {
            return cur;
        }
        for (var i=1; i<=ver; i++) {
            cur = applydiff(cur,i);
        }
        return cur;
    }


    function showrevisions() {
        showrev = 1 - showrev;
        if (showrev==1) {
            contentdiv.innerHTML = colorrevisions(curcontent,curversion);
            document.getElementById("showrev").value = "Hide Changes";
        } else {
            contentdiv.innerHTML = curcontent.join(' ');
            document.getElementById("showrev").value = "Show Changes";
        }
        wikirendermath();
    }


    function colorrevisions(content,ver) {
        if (ver==wikihistory.length-1) {return content.join(' ');};
        current = content.slice();
        var diff = wikihistory[ver+1].c;
        for (var i=diff.length-1; i>=0; i--) {
            deled = null;  insed = null;
            if (diff[i][0]==2) {
                deled = diff[i][3].join(' ');
                insed = current.splice(diff[i][1], diff[i][2]).join(' ');
            } else if (diff[i][0]==0) {
                deled = diff[i][2].join(' ');
            } else if (diff[i][0]==1) {
                insed = current.splice(diff[i][1], diff[i][2]).join(' ');
            }
            if (insed != null) {
                if (insed) {
                    insed = insed.split('<p>').join('<p><ins>');
                }
                if (insed) {
                    insed = insed.split('</p>').join('</ins></p>');
                }
            }
            if (deled != null) {
                if (deled) {
                    deled = deled.split('<p>').join('<p><del>');
                }
                if (deled) {
                    deled = deled.split('</p>').join('</del></p>');
                }
            }
            
            if (diff[i][0]==2) { //replace
                current.splice(diff[i][1], 0, "<del>"+deled+"</del><p><ins>"+insed+"</ins>");
            } else if (diff[i][0]==0) {//insert
                current.splice(diff[i][1], 0, "<del>"+deled+"</del><p>");
            } else if (diff[i][0]==1) {//delete
                current.splice(diff[i][1], 0, "<ins>"+insed+"</ins><p>");
            }
        }

        return current.join('');
    }
    function rendermathnode(node)
    {
        MathJax.Hub.Queue(["Typeset", MathJax.Hub, node]);
    }
    function wikirendermath() {
        if (usingASCIIMath) {

            rendermathnode(contentdiv);
        }
    }

    function clearContent(courseId,wikiId)
    {
        jQuerySubmit('clear-page-content-ajax', {courseId:courseId, wikiId:wikiId}, 'removeResponseSuccess');
    }

    function removeResponseSuccess(response) {
        response = JSON.parse(response);
        var wikiId = response.data.wikiId;
        var courseId = response.data.courseId;
        if (response.status == 0) {
            var message = '';
            message += 'Are you SURE you want to delete all contents and history for this Wiki page??';
            var html = '<div><p>' + message + '</p></div>';
            $('<div id="dialog"></div>').appendTo('body').html(html).dialog({
                modal: true, title: 'Confirm Page Contents Delete ?', zIndex: 10000, autoOpen: true,
                width: 'auto', resizable: false,
                closeText: "hide",
                buttons: {
                    "Cancel": function () {
                        $(this).dialog('destroy').remove();
                        return false;
                    },
                    "Yes,Delete": function () {
                        window.location = "show-wiki?courseId="+courseId+"&wikiId="+wikiId+"&delall=true";
                    }
                },
                close: function (event, ui) {
                    $(this).remove();
                },
                open: function () {
                    jQuery('.ui-widget-overlay').bind('click', function () {
                        jQuery('#dialog').dialog('close');
                    })
                }
            });
        }
    }

    function clearHistory(courseId,wikiId)
    {
        jQuerySubmit('clear-page-history-ajax', {courseId:courseId, wikiId:wikiId}, 'removeHistoryResponseSuccess');
    }
    function removeHistoryResponseSuccess(response)
    {
        response = JSON.parse(response);
        var wikiId = response.data.wikiId;
        var courseId = response.data.courseId;
        if (response.status == 0) {
            var message = '';
            message += 'Are you SURE you want to delete all revision history for this Wiki page? The current version will be retained.?';
            var html = '<div><p>' + message + '</p></div>';
            $('<div id="dialog"></div>').appendTo('body').html(html).dialog({
                modal: true, title: 'Confirm History Delete ?', zIndex: 10000, autoOpen: true,
                width: 'auto', resizable: false,
                closeText: "hide",
                buttons: {
                    "Cancel": function () {
                        $(this).dialog('destroy').remove();
                        return false;
                    },
                    "Yes,Delete": function () {
                        window.location = "show-wiki?courseId="+courseId+"&wikiId="+wikiId+"&delrev=true";
                    }
                },
                close: function (event, ui) {
                    $(this).remove();
                },
                open: function () {
                    jQuery('.ui-widget-overlay').bind('click', function () {
                        jQuery('#dialog').dialog('close');
                    })
                }
            });
        }
    }
    </script>
<?php } ?>