<?php
use app\components\AppUtility;

require("../components/filehandler.php");
if ($searchtype=='none')
{
    $pagetitle = "Forums";
} else {
    $pagetitle = "Forum Search Results";
}

//construct tag list selector
$taginfo = array();
foreach ($itemsimporder as $item) {
    if (!isset($itemsassoc[$item])) { continue; }
    $taglist = $forumdata[$itemsassoc[$item]]['taglist'];
    if ($taglist=='') { continue;}
    $p = strpos($taglist,':');
    $catname = substr($taglist,0,$p);
    if (!isset($taginfo[$catname])) {
        $taginfo[$catname] = explode(',',substr($taglist,$p));
    } else {
        $newtags = array_diff(explode(',',substr($taglist,$p)), $taginfo[$catname]);
        foreach ($newtags as $tag) {
            $taginfo[$catname][] = $tag;
        }
    }
}

if (count($taginfo)==0) {
    $tagfilterselect = '';
} else {
    if (count($taginfo)>1) {
        $tagfilterselect = 'Category ';
    } else {
        $tagfilterselect = $catname .'';
    }
    $tagfilterselect .= '<select class="display-inline-block width-fifty-per form-control margin-left-five"  name="tagfiltersel">';
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
?>

<div class="item-detail-header">
    <?php if($searchtype != 'none') {
        echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false), $course->name,'Forum List'], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'course/course/course?cid=' . $course->id,AppUtility::getHomeURL() . 'forum/forum/search-forum?cid=' . $course->id.'&clearsearch=true']]);
    } else
    {
        echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false), $course->name], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'course/course/course?cid=' . $course->id]]);
    }?>
</div>
<div class = "title-container">
    <div class="row">
        <div class="pull-left page-heading">
            <div class="vertical-align title-page"><?php echo $pagetitle ?></div>
        </div>
    </div>
</div>
<div class="item-detail-content">
    <?php if($users->rights == 100 || $users->rights == 20) {
        echo $this->render("../../course/course/_toolbarTeacher", ['course' => $course, 'section' => 'Forums']);
    } elseif($users->rights == 10){
        echo $this->render("../../course/course/_toolbarStudent", ['course' => $course, 'section' => 'Forums']);
    }?>
</div>
<input type="hidden" id="courseid" value="<?php echo $course->id; ?>">
<input type="hidden" id="user-id" value="<?php echo $users['id'];?>">
<div class="tab-content shadowbox">
    <div class="col-md-12 col-sm-12 padding-left-right-zero padding-bottom-thirty">
        <div id="forumsearch">
            <form method="post" action="search-forum?cid=<?php echo $cid;?>">
                <div class="inner-content col-md-12 col-sm-12 padding-left-thirty padding-top-bottom-ten">
                    <div class="col-md-3 col-sm-6 padding-left-zero">
                        <?php
                        if ($tagfilterselect != '') {
                            echo "Limit by $tagfilterselect";
                        }
                        ?>
                    </div>
                    <div class="col-md-4 col-sm-6 padding-left-zero">
                        <span>Search</span>
                        <input class="width-seventy-per form-control display-inline-block margin-left-five" type=text name="search" value="<?php echo $searchstr;?>"/>
                    </div>
                    <div class="col-md-5 col-sm-12 padding-left-zero">
            <span class="col-md-6 col-sm-3 padding-left-zero padding-top-seven">
                <input type="radio" name="searchtype" value="thread"
                    <?php if ($searchtype!='posts') {echo 'checked="checked"';}?>/>
                <span class="padding-left-five">All thread subjects</span>
            </span>
            <span class="col-md-4 col-sm-2 padding-top-seven padding-left-five">
                <input type="radio" name="searchtype" value="posts" <?php if ($searchtype=='posts') {echo 'checked="checked"';}?>/>
                <span class="padding-left-five">All posts</span>
            </span>
                        <span class="col-md-2 col-sm-1 padding-left-ten"><input name="searchsubmit" type="submit" value="Search"/></span>
                    </div>
                </div>
            </form>
        </div>
 <?php

 $isblank = 0;
 if(count($threadids)==0 && $searchtype == 'thread')
 {
     $isblank = 1;
 }else if(count($searchedPost) == 0 && $searchtype == 'posts'){
     $isblank = 1;
 }?>
        <?php if($isblank)
        {
            echo '<div class="col-md-12 col-sm-12 title-option">';
            echo '<h4>No result found.</h4>';
            echo '</div>';
        } else if ($searchtype == 'thread') {
            ?>
            <!--    doing a search of thread subjects-->
            <div class="col-md-12 col-sm-12 padding-top-twenty padding-left-right-thirty">
                <?php echo '<table class="search-forum table table-bordered table-striped table-hover data-table"><thead>';
                echo '<tr><th class="width-twelve-five-per">Topic</th><th class="width-fourty-five-per">Forum</th><th>Replies</th><th>Views</th><th class="width-twelve-per">Last Post Date</th></tr></thead><tbody>';
                foreach ($threaddata as $line) {
                    if (isset($postcount[$line['id']])) {
                        $posts = $postcount[$line['id']];
                        $lastpost = AppUtility::tzdate("F j, Y, g:i a", $maxdate[$line['id']]);
                    } else {
                        $posts = 0;
                        $lastpost = '';
                    }
                    echo "<tr id=\"tr{$line['id']}\" ";
                    if ($line['tagged'] == 1) {
                        echo 'class="tagged"';
                    }
                    echo "><td>"; ?>

                    <?php echo "<span class=\" text-align-center\">\n";
                    if ($line['tag'] != '')
                    { //category tags
                        echo '<span class="forumcattag text-align-center">' . $line['tag'] . '</span> ';
                    } else {
                        echo '<span class="forumcattag text-align-center">     </span> ';
                    }
                    echo "</span>\n"; ?>


                    <div class="btn-group floatright">
                    <?php
                    if ($line['posttype']==0) {
                    if ($line['tagged'] == 1) { ?>

                        <a class='btn btn-primary flag-btn' id="tag{<?php echo $line['id'] ?>}"
                           onClick="changeImage(this,'true',<?php echo $line['id'] ?>)"> <i class='fa fa-flag'></i>
                            Unflag</a>
                    <?php

                    } else { ?>

                        <a class='btn btn-primary flag-btn' id="tag{<?php echo $line['id'] ?>}" onClick="changeImage(this,'true',<?php echo $line['id'] ?>)" )'> <i class='fa fa-flag-o'></i> Flag</a>
                        <?php

                    }
                }else{
                        echo '<a class="btn btn-primary flag-btn disable-btn-not-allowed"> No Flag</a>';
                    } ?>
                        <a class="btn btn-primary dropdown-toggle" id="drop-down-id" data-toggle="dropdown" href="#">
                            <span class="fa fa-caret-down "></span>
                        </a>
                        <ul class="dropdown-menu thread-dropdown">

                            <?php if ($isteacher) { ?>
                                <li> <a href="<?php echo AppUtility::getURLFromHome('forum','forum/move-thread?&courseId='.$cid.'&forumId='.$line['forumid'].'&threadId='.$line['id'])?>"><i class='fa fa-scissors'></i>&nbsp;&nbsp;Move</a></li>

                            <?php }
                            if ($isteacher || ($line['userid']==$userid && $allowmod && time()<$postby)) { ?>
                                <li><a href="<?php echo AppUtility::getURLFromHome('forum','forum/modify-post?&courseId='.$cid.'&forumId='.$line['forumid'].'&threadId='.$line['id'])?>"><i class='fa fa-pencil fa-fw padding-right-five'></i>&nbsp;Modify</a></li>
                            <?php }
                            if ($isteacher || ($allowdel && $line['userid']==$userid && $posts==0)) { ?>
                                <li><a href='#' name='tabs' data-var='<?php echo $line['id'];?>' class='mark-remove'><i class='fa fa-trash-o'></i>&nbsp;&nbsp;&nbsp;Remove</a></li>
                            <?php }
                            ?>
                        </ul>
                    </div>


                    <div>
                    <?php
                    if ($line['isanon']==1) {
                        $name = "Anonymous";
                    } else {
                        $name = "{$line['LastName']}, {$line['FirstName']}";
                    } ?>
                     <b><a class="width-hundread-per word-break-all-width" href="<?php echo AppUtility::getURLFromHome('forum','forum/post?&page=-4&courseid='.$cid.'&forumid='.$line['forumid'].'&threadid='.$line['id']);?> "> <?php echo $line['subject'] ?></a></b>&nbsp;<?php echo $name?>


                     </div>
                     </td>

                     <td class="c width-hundread-per word-break-all-width"><a href="<?php echo AppUtility::getURLFromHome('forum','forum/thread?cid='.$cid.'&forumid='.$line['forumid']);?>"><?php echo $line['name']?></a></td>
                    <?php echo "<td class='C '>$posts</td><td class=c>{$line['views']} </td><td class=c>$lastpost ";
                    echo "</td></tr>\n";
                } ?>
                </tbody>
                </table>
            </div>
        <?php } else if ($searchtype == 'posts') {
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
                echo "<div class='col-md-12 col-sm-12 padding-left-right-thirty padding-top-twenty'>
        <div class='block'>";
                echo "<b>{$line['subject']}</b>";
                echo ' (in '.$line['name'].')';
                if ($line['isanon']==1) {
                    $name = "Anonymous";
                } else {
                    $name = "{$line['LastName']}, {$line['FirstName']}";
                }
                echo "<br/>Posted by: $name, ";
                echo AppUtility::tzdate("F j, Y, g:i a",$line['postdate']);

                echo "</div>
        <div class='blockitems margin-bottom-zero'>";
                if($line['files']!='') {
                    $fl = explode('@@',$line['files']);
                    if (count($fl)>2) {
                        echo '<p><b>Files:</b> ';//<ul class="nomark">';
                    } else {
                        echo '<p><b>File:</b> ';
                    }
                    for ($i=0;$i<count($fl)/2;$i++) {
                        //if (count($fl)>2) {echo '<li>';}
                        echo '<a href="'.\app\components\filehandler::getuserfileurl('files/'.$line['id'].'/'.$fl[2*$i+1]).'" target="_blank">';
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
                <?php echo "</div></div>\n";
            }

        }else {

            if (count($forumdata)==0) {
                echo '<div class="col-sm-12 padding-left-thirty padding-top-thirty">';

                if ($isteacher)
                {
                    echo '<h4 class="">There are no forums in this class yet.  You can add forums from the course page.</h4>';
                } else {
                    echo '<h4>There are no active forums at this time.</h4>';
                }
                echo '</div>';
            } else {
                //default display
                ?>
<!--        --><?php
//        foreach ($itemsimporder as $item) {
//            if (!isset($itemsassoc[$item])) {
//                continue;
//            }
//            $line = $forumdata[$itemsassoc[$item]];
//        }
                ?>
        <div class="col-md-12 col-sm-12 padding-top-twenty padding-left-right-thirty myScrollTable">
                    <table class="search-forum table table-bordered table-striped table-hover data-table">
                        <thead>
                        <tr><th class="width-sixty-per">Forum Name</th><th class="width-three-per">Threads</th><th class="width-three-per">Posts</th><th class="width-twenty-four-per">Last Post Date</th></tr>
                        </thead>
                        <tbody>
                        <?php
                        foreach ($itemsimporder as $item)
                        {
                            if (!isset($itemsassoc[$item])) { continue; }
                            $line = $forumdata[$itemsassoc[$item]];
//                            if (!$isteacher && !($line['avail']==2 || ($line['avail']==1 && $line['startdate']<$now && $line['enddate']>$now)))
//                            {
//                                continue;
//                            }
                            echo "<tr>
                            <td class=''>";?>


                         <?php   if ($isteacher) { ?>

                                <span class="floatright">
                     <a href="<?php echo AppUtility::getURLFromHome('forum','forum/add-forum?cid='.$cid.'&fromforum=1&id='.$line['id']);?> ">&nbsp;Modify</a>
                     </span>

                            <?php } ?>
                            <b><a class="word-break-break-all width-hundread-per" href="<?php echo AppUtility::getURLFromHome('forum','forum/thread?cid='.$cid.'&forumid='.$line['id']);?>"><?php echo $line['name'];?></a></b>
<!--                            --><?php //if ($newcnt[$line['id']]>0)
//                                { ?>
<!--                                <a href="--><?php //echo AppUtility::getURLFromHome('forum','forum/thread?cid='.$cid.'&forumid='.$line['id'].'&page=-1');?><!--" style="color:red">New Posts  (--><?php //echo $newcnt[$line['id']];?><!--) </a>-->
<!--                            --><?php //}
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
                </div>
            <?php
            }
        }
        ?>
    </div>
</div>




