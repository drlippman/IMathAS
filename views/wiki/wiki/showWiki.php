<?php
use app\components\AppUtility;
echo $this->render('_toolbar',['course'=> $course]);
require_once("../filter/filter.php");
?>

<div id="wikiName">
    <h2><?php echo $wiki->name; ?></h2>
   <input type="hidden" class="wiki-id" value="<?php echo $wiki->id;?>">
   <input type="hidden" class="course-id" value="<?php echo $course->id;?>">
</div>

<?php
if(!empty($revisionTotalData))
{
   foreach($revisionTotalData as $key => $revision) {
    $lasteditedby = $revision['FirstName'].',' .$revision['LastName'];
        $time = $revision['time'];
        $lastedittime = AppUtility::tzdate("F j, Y, g:i a", $time);
       $numrevisions = $revision['id'];
   }
}
?>

<p><span id="revisioninfo">Revision <?php echo count($countOfRevision); ?>
       <?php if (count($countOfRevision)>0) {
	echo ".  Last edited by $lasteditedby on $lastedittime.";
}
?>
</span>

<?php
if (count($countOfRevision)>1) {
    $last = count($countOfRevision) - 1;
    echo '<span id="prevrev"><input type="button" value="Show Revision History" id="show-revision"/></span>';
    echo '<span id="revcontrol" style="display:none;"><br/>Revision history:
    <a href="#" id="first" onclick="jumpto(1)">First</a>
    <a id="older" href="#" onclick="seehistory(1); return false;">Older</a> ';
    echo '<a id="newer" class="grayout" href="#" onclick="seehistory(-1); return false;">Newer</a>
    <a href="#" class="grayout" id="last" onclick="jumpto(0)">Last</a>
    <input type="button" id="showrev" value="Show Changes" onclick="showrevisions()" />';
}
?>
<div class="editor">
    <span>
        <a href="<?php echo AppUtility::getURLFromHome('wiki', 'wiki/edit-page?courseId=' .$course->id .'&wikiId=' .$wiki->id ); ?>"
           class="btn btn-primary btn-sm">Edit this page</a></span>
        <?php if(!empty($wikiRevisionData)){
            foreach($wikiRevisionData as $key => $singleWikiRevision) { ?>
    <textarea id='wikicontent' name='wikicontent' style='width: 100% '>
                <?php //$text = $singleWikiRevision->revision;  echo strip_tags($text);
                $in = '<td>Hello Tudip</div>';
                echo strip_tags($out);?>
    </textarea>
    <?php }?>
    <?php }?>
</div>
<script>
    var original = null;
    var wikihistory = null;
    var userinfo = null;
    var curcontent = null;
    var curversion = 0;
    var contentdiv = null;
    var showrev = 0;
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

        $.get( "get-revisions?courseId="+courseId+"&wikiId="+wikiId, function( data ) {
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
    function wikirendermath() {
        if (usingASCIIMath) {
            rendermathnode(contentdiv);
        }
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
        } else {
            document.getElementById("newer").className = "";
            document.getElementById("last").className = "";
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
                if (insed.match(/<p>/)) {
                    insed = insed.split('<p>').join('<p><ins>');
                }
                if (insed.match(/<\/p>/)) {
                    insed = insed.split('</p>').join('</ins></p>');
                }
            }
            if (deled != null) {
                if (deled.match(/<p>/)) {
                    deled = deled.split('<p>').join('<p><del>');
                }
                if (deled.match(/<\/p>/)) {
                    deled = deled.split('</p>').join('</del></p>');
                }
            }
            if (diff[i][0]==2) { //replace
                current.splice(diff[i][1], 0, "<del>"+deled+"</del><ins>"+insed+"</ins>");
            } else if (diff[i][0]==0) {//insert
                current.splice(diff[i][1], 0, "<del>"+deled+"</del>");
            } else if (diff[i][0]==1) {//delete
                current.splice(diff[i][1], 0, "<ins>"+insed+"</ins>");
            }
        }
        return current.join(' ');
    }
</script>
