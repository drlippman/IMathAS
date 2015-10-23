<?php
use app\components\AppUtility;

require("../components/filehandler.php");
$pagetitle = "Forums";
//$placeinhead .= "<script type=\"text/javascript\">var AHAHsaveurl = '" . $urlmode . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/savetagged.php?cid=$cid';</script>";


echo "<div class=breadcrumb>$breadcrumbbase <a href=\"../course/course.php?cid=$cid\">$coursename</a> &gt; ";
if ($searchtype != 'none')
{ ?>
    <a href="<?php echo  AppUtility::getURLFromHome('forum','forum/forums?cid='.$cid.'&clearsearch=true')?>">Forum List</a>
<?php }
echo "Forums</div>\n";

//construct tag list selector
$taginfo = array();
foreach ($itemsimporder as $item) {
    if (!isset($itemsassoc[$item])) { continue; }
    $taglist = $forumdata[$itemsassoc[$item]]['taglist'];
    if ($taglist=='') { continue;}
    $p = strpos($taglist,':');
    $catname = substr($taglist,0,$p);
    if (!isset($taginfo[$catname])) {
        $taginfo[$catname] = explode(',',substr($taglist,$p+1));
    } else {
        $newtags = array_diff(explode(',',substr($taglist,$p+1)), $taginfo[$catname]);
        foreach ($newtags as $tag) {
            $taginfo[$catname][] = $tag;
        }
    }
}
if (count($taginfo)==0) {
    $tagfilterselect = '';
} else {
    if (count($taginfo)>1) {
        $tagfilterselect = 'Category: ';
    } else {
        $tagfilterselect = $catname .': ';
    }
    $tagfilterselect .= '<select name="tagfiltersel">';
    $tagfilterselect .= '<option value="">All</option>';
    foreach ($taginfo as $catname=>$tagarr) {
        if (count($taginfo)>1) {
            $tagfilterselect .= '<optgroup label="'.$catname.'">';
        }
        foreach ($tagarr as $tag) {
            $tagfilterselect .= '<option value="'.$tag.'"';
            if ($tag==$searchtag) { $tagfilterselect .= ' selected="selected"';}
            $tagfilterselect .= '>'.$tag.'</option>';
        }
        if (count($taginfo)>1) {
            $tagfilterselect .= '</optgroup>';
        }
    }
    $tagfilterselect .= '</select>';
}
if ($searchtype=='none') {
    echo '<div id="headerforums" class="pagetitle"><h2>Forums</h2></div>';
} else {
    echo '<div id="headerforums" class="pagetitle"><h2>Forum Search Results</h2></div>';
}
?>
<div id="forumsearch">
    <form method="post" action="search-forum?cid=<?php echo $cid;?>">
        <p>
            Search: <input type=text name="search" value="<?php echo $searchstr;?>" />
            <input type="radio" name="searchtype" value="thread" <?php if ($searchtype!='posts') {echo 'checked="checked"';}?>/>All thread subjects
            <input type="radio" name="searchtype" value="posts" <?php if ($searchtype=='posts') {echo 'checked="checked"';}?>/>All posts.
            <?php
            if ($tagfilterselect != '') {
                echo "Limit by $tagfilterselect";
            }
            ?>
            <input name="searchsubmit" type="submit" value="Search"/>
        </p>
    </form>
</div>
<?php


if ($searchtype == 'thread') {
    //doing a search of thread subjects
      {

        echo '<table class=forum><thead>';
        echo '<tr><th>Topic</th><th>Forum</th><th>Replies</th><th>Views</th><th>Last Post Date</th></tr></thead><tbody>';
        foreach ($threaddata as $line) {
            if (isset($postcount[$line['id']])) {
                $posts = $postcount[$line['id']];
                $lastpost = AppUtility::tzdate("F j, Y, g:i a",$maxdate[$line['id']]);
            } else {
                $posts = 0;
                $lastpost = '';
            }
            echo "<tr id=\"tr{$line['id']}\" ";
            if ($line['tagged']==1) {echo 'class="tagged"';}
            echo "><td>";
            echo "<span class=right>\n";
            if ($line['tag']!='') { //category tags
                echo '<span class="forumcattag">'.$line['tag'].'</span> ';
            }

            if ($line['tagged']==1) {
                echo "<img class=\"pointer\" id=\"tag{$line['id']}\" src=\"$imasroot/img/flagfilled.gif\" onClick=\"toggletagged({$line['id']});return false;\" />";
            } else {
                echo "<img class=\"pointer\" id=\"tag{$line['id']}\" src=\"$imasroot/img/flagempty.gif\" onClick=\"toggletagged({$line['id']});return false;\" />";
            }

            if ($isteacher) {
                echo "<a href=\"thread.php?page=$page&cid=$cid&forum={$line['forumid']}&move={$line['id']}\">Move</a> ";
            }
            if ($isteacher || ($line['userid']==$userid && $allowmod && time()<$postby)) {
                echo "<a href=\"thread.php?page=$page&cid=$cid&forum={$line['forumid']}&modify={$line['id']}\">Modify</a> ";
            }
            if ($isteacher || ($allowdel && $line['userid']==$userid && $posts==0)) {
                echo "<a href=\"thread.php?page=$page&cid=$cid&forum={$line['forumid']}&remove={$line['id']}\">Remove</a>";
            }
            echo "</span>\n";
            if ($line['isanon']==1) {
                $name = "Anonymous";
            } else {
                $name = "{$line['LastName']}, {$line['FirstName']}";
            }
            echo "<b><a href=\"posts.php?cid=$cid&forum={$line['forumid']}&thread={$line['id']}&page=-4\">{$line['subject']}</a></b>: $name";
            echo "</td>\n";
            echo "<td class=\"c\"><a href=\"thread.php?cid=$cid&forum={$line['forumid']}\">{$line['name']}</a></td>";
            echo "<td class=c>$posts</td><td class=c>{$line['views']} </td><td class=c>$lastpost ";
            echo "</td></tr>\n";
        }
    }

} else if ($searchtype == 'posts') {
    //doing a search of all posts
    if (!isset($CFG['CPS']['itemicons'])) {
        $itemicons = array('web'=>'web.png', 'doc'=>'doc.png', 'wiki'=>'wiki.png',
            'html'=>'html.png', 'forum'=>'forum.png', 'pdf'=>'pdf.png',
            'ppt'=>'ppt.png', 'zip'=>'zip.png', 'png'=>'image.png', 'xls'=>'xls.png',
            'gif'=>'image.png', 'jpg'=>'image.png', 'bmp'=>'image.png',
            'mp3'=>'sound.png', 'wav'=>'sound.png', 'wma'=>'sound.png',
            'swf'=>'video.png', 'avi'=>'video.png', 'mpg'=>'video.png',
            'nb'=>'mathnb.png', 'mws'=>'maple.png', 'mw'=>'maple.png');
    } else {
        $itemicons = $CFG['CPS']['itemicons'];
    }
    foreach($searchedPost as $line) {
        echo "<div class=block>";
        echo "<b>{$line['subject']}</b>";
        echo ' (in '.$line['name'].')';
        if ($line['isanon']==1) {
            $name = "Anonymous";
        } else {
            $name = "{$line['LastName']}, {$line['FirstName']}";
        }
        echo "<br/>Posted by: $name, ";
        echo AppUtility::tzdate("F j, Y, g:i a",$line['postdate']);

        echo "</div><div class=blockitems>";
        if($line['files']!='') {
            $fl = explode('@@',$line['files']);
            if (count($fl)>2) {
                echo '<p><b>Files:</b> ';//<ul class="nomark">';
            } else {
                echo '<p><b>File:</b> ';
            }
            for ($i=0;$i<count($fl)/2;$i++) {
                //if (count($fl)>2) {echo '<li>';}
                echo '<a href="'.getuserfileurl('ffiles/'.$line['id'].'/'.$fl[2*$i+1]).'" target="_blank">';
                $extension = ltrim(strtolower(strrchr($fl[2*$i+1],".")),'.');
                if (isset($itemicons[$extension])) {
                    echo "<img alt=\"$extension\" src=\"$imasroot/img/{$itemicons[$extension]}\" class=\"mida\"/> ";
                } else {
                    echo "<img alt=\"doc\" src=\"$imasroot/img/doc.png\" class=\"mida\"/> ";
                }
                echo $fl[2*$i].'</a> ';
                //if (count($fl)>2) {echo '</li>';}
            }
            //if (count($fl)>2) {echo '</ul>';}
            echo '</p>';
        }
        echo filter($line['message']); ?>
         <p><a href="<?php echo AppUtility::getURLFromHome('forum','forum/post?courseid='.$cid.'&forumid='.$line['forumid'].'&threadid='.$line['threadid'].'&page=-4'); ?>">Show full thread</a></p>
        <?php echo "</div>\n";
    }

} else {
    if (count($forumdata)==0) {
        if ($isteacher) {
            echo '<p>There are no forums in this class yet.  You can add forums from the course page.</p>';
        } else {
            echo '<p>There are no active forums at this time.</p>';
        }
    } else {
        //default display
        ?>
        <table class=forum>
            <thead>
            <tr><th>Forum Name</th><th>Threads</th><th>Posts</th><th>Last Post Date</th></tr>
            </thead>
            <tbody>
            <?php
             foreach ($itemsimporder as $item) {
                if (!isset($itemsassoc[$item])) { continue; }
                $line = $forumdata[$itemsassoc[$item]];

                if (!$isteacher && !($line['avail']==2 || ($line['avail']==1 && $line['startdate']<$now && $line['enddate']>$now))) {
                    continue;
                }
                echo "<tr><td>";
                if ($isteacher) { ?>
                     <span class="right">
                     <a href="<?php echo AppUtility::getURLFromHome('forum','forum/add-forum?cid='.$cid.'&fromforum=1&id='.$line['id']);?> ">Modify</a>
                     </span>
                <?php } ?>
                 <b><a href="<?php echo AppUtility::getURLFromHome('forum','forum/thread?cid='.$cid.'&forum='.$line['id']);?>"><?php echo $line['name'];?></a></b>
                <?php if ($newcnt[$line['id']]>0) { ?>
                     <a href="<?php echo AppUtility::getURLFromHome('forum','forum/thread?cid='.$cid.'&forum='.$line['id'].'&page=-1');?>" style="color:red">New Posts  (<?php echo $newcnt[$line['id']];?>) </a>
                <?php }
                echo "</td>\n";
                if (isset($threadcount[$line['id']])) {
                    $threads = $threadcount[$line['id']];
                    $posts = $postcount[$line['id']];
                    $lastpost = AppUtility::tzdate("F j, Y, g:i a",$maxdate[$line['id']]);
                } else {
                    $threads = 0;
                    $posts = 0;
                    $lastpost = '';
                }
                echo "<td class=c>$threads</td><td class=c>$posts</td><td class=c>$lastpost</td></tr>\n";
            }
            ?>
            </tbody>
        </table>
    <?php
    }
}

?>




