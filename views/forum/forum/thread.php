<?php
use app\components\AppUtility;
use app\components\AssessmentUtility;
use app\components\CourseItemsUtility;
use app\components\AppConstant;
use yii\widgets\ActiveForm;
//require("../filter/filter.php");

$this->title = AppUtility::t($forumData['name'],false );
$this->params['breadcrumbs'][] = $this->title;
$currentTime = AppUtility::parsedatetime(date('m/d/Y'), date('h:i a'));
$now = $currentTime;
?>
<?php if($page){?>
    <input type="hidden" id="page" value="<?php echo $page;?>">
<?php }?>
<div class="item-detail-header">
    <?php if($users['rights'] == 100 || $users['rights'] == 20) {
        echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false), $course->name], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'instructor/instructor/index?cid=' . $course->id]]);
    } elseif($users['rights'] == 10){
        echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false), $course->name], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'course/course/index?cid=' . $course->id]]);
    }?>
</div>

<div class = "title-container">
    <div class="row">
        <div class="pull-left page-heading">
            <div class="vertical-align title-page"><?php echo AppUtility::t('Forums:',false);?><?php echo $this->title ?></div>
        </div>
        <?php if($users['rights']>AppConstant::NUMERIC_FIVE && time()<$forumData['postby'] || $isteacher ){ ?>
        <div class="pull-left header-btn">
            <a href="<?php echo AppUtility::getURLFromHome('forum', 'forum/add-new-thread?forumid=' .$forumid.'&cid='.$course->id); ?>"
               class="btn btn-primary pull-right add-new-thread"><i class="fa fa-plus"></i>&nbsp;Add New Thread</a>
        </div>
        <?php }?>
    </div>
</div>
<div class="item-detail-content">
    <?php if($users['rights'] == 100 || $users['rights'] == 20) {
        echo $this->render("../../instructor/instructor/_toolbarTeacher", ['course' => $course, 'section' => 'Forums']);
    } elseif($users['rights'] == 10){
        echo $this->render("../../course/course/_toolbarStudent", ['course' => $course, 'section' => 'Forums']);
    }?>
</div>

<input type="hidden" id="courseId" class="courseId" value="<?php echo $cid ?>">
<input type="hidden" id="forumid" value="<?php echo $forumid; ?>">
<input type="hidden" id="courseid" value="<?php echo $course->id; ?>">
<input type="hidden" id="user-id" value="<?php echo $users['id'];?>">
<input type="hidden" id="settings" value="<?php echo $forumData['settings'];?>">
<input type="hidden" id="un-read" value="<?php echo $unRead; ?>">

<div class="tab-content shadowBox ">
    <div class="inner-content col-lg-12">
        <div class="view-drop-down  pull-left">
        <span class=""><?php echo AppUtility::t('View Options',false)?></span>
            <select name="seluid" class="form-control-forum select_option" id="">
                <option value="-1" selected="selected"><?php echo AppUtility::t('Select')?></option>
                <option value="0"><?php echo AppUtility::t('List Post by Name')?></option>
                <option value="1"><?php echo AppUtility::t('Limit to Flagged ')?></option>
                <?php if($page < 0){?>
                <option value="3"><?php echo AppUtility::t('Show All')?></option>
                <?php }else{
                if (count($newpost)>0) {?>
                    <option value="2"><?php echo AppUtility::t('Limit to New ')?></option>
                <?php } }?>
            </select>
        </div>

        <div class="mark-as-read-link pull-left col-lg-4 pull-left">
        <?php if (count($newpost)>0) {?>
            <a   href="<?php echo AppUtility::getURLFromHome('forum','forum/thread?page='.$page.'&cid='.$course->id.'&forum='.$forumid.'&markallread=true')?>" id="markRead"><?php echo AppUtility::t('Mark All Read')?></a>
        <?php } ?>
        </div>
        <form method=post action="thread">
        <div class="pull-right view-drop-down">
            <button class="btn btn-primary search-button" type="submit" id="change-button"><i class="fa fa-search"></i>&nbsp;<b><?php echo AppUtility::t('Search')?></b></button>

        </div>
        <div class="checkbox checkbox-thread override-hidden pull-right">
            <label>
                <input type="checkbox" name="allforums" id="searchAll" value=""><?php echo AppUtility::t('All Forum in Courses?')?>

                <span class="cr"><i class="cr-icon fa fa-check"></i></span>
            </label>
        </div>
        <div class="view-drop-down pull-right">
                <span class="">
                 <input type="text" name="search" id="search_text" maxlength="30" placeholder="<?php echo AppUtility::t('Enter Search Terms')?>">

               </span>
        </div>

            <input type=hidden name=page value="<?php echo $page;?>">
            <input type=hidden name=cid value="<?php echo $course->id;?>">
            <input type=hidden name=forum value="<?php echo $forumid;?>">

        </form>


        <?php
        if (isset($params['search']) && trim($params['search'])!='')
        {
            echo "<h2>Forum Search Results</h2>";
            foreach ($searchedPost as $row )
            {
            echo "<div class=block>";
                echo "<b>{$row['subject']}</b>";
                if (isset($params['allforums'])) {
                echo ' (in '.$row['name'].')';
                }
                if ($row['isanon'] == 1) {
                $name = "Anonymous";
                } else {
                $name = "{$row['FirstName']} {$row['LastName']}";
                }
                echo "<br/>Posted by: $name, ";
                echo AppUtility::tzdate("F j, Y, g:i a",$row['postdate']);

                echo "</div>
                <div class=blockitems>";
    //            echo filter($row['imas_forum_posts.message']); ?>
                 <p><a href="<?php echo AppUtility::getURLFromHome('forum','forum/post?cid='.$cid.'&forum='.$row['imas_forums.id'].'&thread='.$row['imas_forum_posts.threadid']);?>">Show full thread</a></p>
                 </div>
            <?php }


            }else
        {
    if ($page > 0) {
        $numpages = ceil($countOfPostId['id'] / $threadsperpage);
        if ($numpages > 1) {
            $prevnext .= "Page: ";
            if ($page < $numpages / 2) {
                $min = max(2, $page - 4);
                $max = min($numpages - 1, $page + 8 + $min - $page);
            } else {
                $max = min($numpages - 1, $page + 4);
                $min = max(2, $page - 8 + $max - $page);
            }
            if ($page == 1) {
                $prevnext .= "<b>1</b> ";
            } else {
                $prevnext .= "<a href=\"thread.php?page=1&cid=$cid&forum=$forumid\">1</a> ";
            }
            if ($min != 2) {
                $prevnext .= " ... ";
            }
            for ($i = $min; $i <= $max; $i++) {
                if ($page == $i) {
                    $prevnext .= "<b>$i</b> ";
                } else {
                    $prevnext .= "<a href=\"thread.php?page=$i&cid=$cid&forum=$forumid\">$i</a> ";
                }
            }
            if ($max != $numpages - 1) {
                $prevnext .= " ... ";
            }
            if ($page == $numpages) {
                $prevnext .= "<b>$numpages</b> ";
            } else {
                $prevnext .= "<a href=\"thread.php?page=$numpages&cid=$cid&forum=$forumid\">$numpages</a> ";
            }
            $prevnext .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";

            if ($page > 1) {
                $prevnext .= "<a href=\"thread.php?page=" . ($page - 1) . "&cid=$cid&forum=$forumid\">Previous</a> ";
            } else {
                $prevnext .= "Previous ";
            }
            if ($page < $numpages) {
                $prevnext .= "| <a href=\"thread.php?page=" . ($page + 1) . "&cid=$cid&forum=$forumid\">Next</a> ";
            } else {
                $prevnext .= "| Next ";
            }

            echo "<div>$prevnext</div>";

        }
    } ?>

            <?php
            if ($isteacher && $groupsetid > 0)
            {
                natsort($groupnames);
                echo '<p>Show posts for group: <select id="ffilter" onChange="chgfilter()"><option value="-1" ';
                if ($curfilter==-1) { echo 'selected="1"';}
                echo '>All groups</option>';
                foreach ($groupnames as $gid=>$gname)
                {
                    echo "<option value=\"$gid\" ";
                    if ($curfilter==$gid) { echo 'selected="1"';}
                    echo ">$gname</option>";
                }
                echo '</select></p>';
            }
            echo '<p>';
            $toshow = array();
            if ($page<0) {
            } else {
                if ($taglist!='') {
                    $p = strpos($taglist,':');

                    $tagselect = 'Filter by '.substr($taglist,0,$p).': ';
                    $tagselect .= '<select id="tagfilter" onChange="chgtagfilter()"><option value="" ';
                    if ($tagfilter=='') {
                        $tagselect .= 'selected="selected"';
                    }
                    $tagselect .= '>All</option>';
                    $tags = explode(',',substr($taglist,$p+1));
                    foreach ($tags as $tag) {
                        $tag =  str_replace('"','&quot;',$tag);
                        $tagselect .= '<option value="'.$tag.'" ';
                        if ($tag==$tagfilter) {$tagselect .= 'selected="selected"';}
                        $tagselect .= '>'.$tag.'</option>';
                    }
                    $tagselect .= '</select>';
                    $toshow[] = $tagselect;
                }
            }
            echo implode(' | ',$toshow);

            echo "</p>";

            ?>

            <div id="data">
                <table style="float: left" id="forum-table displayforum" class="forum-table table table-bordered table-striped table-hover data-table" bPaginate="false">
                    <thead>
                    <th><?php echo AppUtility::t('Topic')?></th>
                    <?php            if ($isteacher && $groupsetid>0 && !$dofilter) { ?>
                        <th><?php echo AppUtility::t('Groups')?></th>
                    <?php } ?>
                    <th><?php echo AppUtility::t('Replies')?></th>
                    <th><?php echo AppUtility::t('Views (Unique)')?></th>
                    <th><?php echo AppUtility::t('Last Post')?></th>
                    <th><?php echo AppUtility::t('Actions')?></th>
                    </thead>
                   <tbody class="forum-table-body">

        <?php
                    foreach ($postIds as $row) {
                    $uniqviews[$row['id']] = $row['count(imas_forum_views.userid)']-1;
                    }

                    if (count($postInformtion) == 0)
                    {
                    echo '<tr><td colspan='.(($isteacher && $grpaid>0 && !$dofilter)?6:5).'>No posts have been made yet.  Click Add New Thread to start a new discussion</td></tr>';
                    }

                    foreach ($postInformtion as $line )
                    {

                    if (isset($postcount[$line['id']])) {
                    $posts = $postcount[$line['id']];
                    $lastpost = AppUtility::tzdate("F j, Y, g:i a",$maxdate[$line['id']]);
                    } else {
                    $posts = 0;
                    $lastpost = '';
                    }
                    echo "<tr id=\"tr{$line['id']}\"";
                              if ($line['posttype']>0) {
                    echo "class=sticky";
                    } else if (isset($flags[$line['id']])) {
                    echo "class=tagged";
                    }
                    echo ">
                    <td>";
                        if ($line['isanon']==1) {
                        $name = "Anonymous";
                        } else {
                        $name = "{$line['LastName']}, {$line['FirstName']}";
                        } ?>
                         <b><a href="<?php echo AppUtility::getURLFromHome('forum','forum/post?courseid='.$cid.'&forumid='.$forumid.'&threadid='.$line['id']);?>"><?php echo $line['subject']?></a></b>: <?php echo $name?>
                    <?php
                        echo "</td>\n";
                    if ($isteacher && $groupsetid>0 && !$dofilter) {
                    echo '<td class=c>'.$groupnames[$line['stugroupid']].'</td>';
                    }
                    echo "<td class=c>$posts</td>";
                    if ($isteacher) {
                    echo '<td class="pointer c" onclick="GB_show(\''._('Thread Views').'\',\'listviews.php?cid='.$cid.'&amp;thread='.$line['id'].'\',500,500);">';
                        } else {
                        echo '<td class="c">';
                    }
                        echo "{$line['tviews']} ({$uniqviews[$line['id']]})</td><td class=c>$lastpost ";
                        if ($lastpost=='' || $maxdate[$line['id']] > $lastview[$line['id']]) {
                        echo "<span style=\"color: red;\">New</span>";
                        } ?>
                         </td>
                        <td>
                    <?php                   echo "<span class=\"right\">\n";
                if ($line['tag']!='') { //category tags
                    echo '<span class="forumcattag">'.$line['tag'].'</span> ';
                }
                if ($isteacher) { ?>
                    <li> <a href="<?php echo AppUtility::getURLFromHome('forum','forum/move-thread?&courseId='.$cid.'&forumId='.$line['forumid'].'&threadId='.$line['id'])?>"><i class='fa fa-scissors'></i>&nbsp;Move</a></li>

                <?php }
                if ($isteacher || ($line['userid']==$userid && $allowmod && time()<$postby)) { ?>
                    <li><a href="<?php echo AppUtility::getURLFromHome('forum','forum/modify-post?&courseId='.$cid.'&forumId='.$line['forumid'].'&threadId='.$line['id'])?>"><i class='fa fa-pencil fa-fw'></i>&nbsp;Modify</a></li>
                <?php }
                if ($isteacher || ($allowdel && $line['userid']==$userid && $posts==0)) { ?>
                    <li><a href='#' name='tabs' data-var='<?php echo $line['id'];?>' class='mark-remove'><i class='fa fa-trash-o'></i>&nbsp;Remove</a></li>
                <?php }
                echo "</span>\n"; ?>
                     </td>
                    </tr>
          <?php          }
        ?>
                    </tbody>
                </table>
            </div>
            <div id="searchpost"></div>
   <?php } ?>
</div>
</div>