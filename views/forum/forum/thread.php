<?php
use app\components\AppUtility;
use app\components\AssessmentUtility;
use app\components\CourseItemsUtility;
use app\components\AppConstant;
use yii\widgets\ActiveForm;
use yii\helpers\Html;
use yii\helpers\HtmlPurifier;


$this->title = AppUtility::t($forumData['name'],false );
$this->params['breadcrumbs'][] = $this->title;
$currentTime = AppUtility::parsedatetime(date('m/d/Y'), date('h:i a'));
$now = $currentTime;
?>
<?php if($page){?>
    <input type="hidden" id="page" value="<?php echo $page;?>">
<?php }?>
<div class="item-detail-header">

   <!-- encode course->name -->
    <?php if($params['search'] != 'none') {
        echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false), Html::encode($course->name),'Forum List'], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'course/course/course?cid=' . $course->id,AppUtility::getHomeURL() . 'forum/forum/thread?cid=' . $course->id.'&forumid='.$forumid.'&clearsearch=true']]);
    } else
    {
        echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false), Html::encode($course->name)], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'course/course/course?cid=' . $course->id]]);
    }?>

</div>


<div class = "title-container padding-bottom-two-em">
    <div class="row">
        <div class="pull-left page-heading">
          <!-- encode title -->
            <div class="vertical-align title-page"><?php echo AppUtility::t('Forums:',false);?><?php echo HTML::encode($this->title) ?></div>
        </div>
        <?php if(($users['rights']>AppConstant::NUMERIC_FIVE && time()<$forumData['postby']) || $isteacher ){ ?>
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
<input type="hidden" id="fromforum" value="1">
<input type="hidden" id="courseid" value="<?php echo $course->id; ?>">
<input type="hidden" id="user-id" value="<?php echo $users['id'];?>">
<input type="hidden" id="settings" value="<?php echo $forumData['settings'];?>">
<input type="hidden" id="un-read" value="<?php echo $unRead; ?>">
<input type="hidden" name="from" value="<?php echo $unRead; ?>">
<div class="tab-content shadowBox ">
    <div class="inner-content col-md-12 col-sm-12 padding-left-right-thirty padding-bottom-eight">
        <form id="myForm">
            <div class="padding-left-zero col-md-5 col-sm-8">
                <div class="view-drop-down col-md-8  col-sm-8 padding-left-right-zero">
                    <span class="floatleft padding-right-ten padding-top-five"><?php echo AppUtility::t('View Options',false)?></span>
                    <select name="seluid" class="form-control-forum form-control select_option width-fifty-five-per" id="">
                        <option value="-1" selected="selected"><?php echo AppUtility::t('Select')?></option>
                        <option value="0"><?php echo AppUtility::t('List Post by Name')?></option>
                        <option value="1"><?php echo AppUtility::t('Limit to Flagged')?></option>
                        <?php if($page < 0)
                        {?>
                            <option value="3"><?php echo AppUtility::t('Show All')?></option>
                        <?php }else{
                            if (count($newpost)>0) {?>
                                <option value="2"><?php echo AppUtility::t('Limit to New ')?></option>
                            <?php } }?>
                    </select>
                </div>
                <?php if (count($newpost)>0) {?>
                    <div class="col-md-4 col-sm-4 padding-top-eighteen padding-left-right-zero">
                        <a href="<?php echo AppUtility::getURLFromHome('forum','forum/thread?page='.$page.'&cid='.$cid.'&forum='.$forumid.'&markallread=true')?>">
                            <?php echo AppUtility::t('Mark All Read')?>
                        </a>
                    </div>
                <?php } ?>
            </div>
            <div class="col-md-6 col-sm-12 padding-top-ten padding-left-right-zero floatright">
                        <span class="col-md-4 col-sm-4 padding-left-right-zero">
                             <input type="text" class="form-control" name="search" id="search_text" maxlength="30" placeholder="<?php echo AppUtility::t('Enter Search Terms')?>">
                        </span>
                        <span class="checkbox checkbox-thread override-hidden col-md-5 col-sm-5 padding-left-right-zero margin-left-right-zero">
                            <label class="margin-top-zero floatright padding-left-zero">
                                <input type="checkbox" name="allforums" id="searchAll" value=""><?php echo AppUtility::t('All Forums in Courses?')?>

                                <span class="cr"><i class="cr-icon fa fa-check"></i></span>
                            </label>
                        </span>
                        <span class="col-md-3 col-sm-2 padding-left-right-zero">
                            <a class="btn btn-primary search-button floatright" id="change-button"><i class="fa fa-search"></i>&nbsp;<b><?php echo AppUtility::t('Search')?></b></a>
                        </span>
            </div>

            <input type=hidden id="page" name=page value="<?php echo $page;?>">
            <input type=hidden id="cid" name=cid value="<?php echo $course->id;?>">
            <input type=hidden id="forum" name=forum value="<?php echo $forumid;?>">
        </form>
    </div>

    <?php
    if (isset($params['search']) && trim($params['search'])!='')
    {
    echo "<h2 class='col-sm-12 padding-bottom-ten padding-left-twenty-eight'>Forum Search Results</h2>";
    echo '<div class="col-sm-12 padding-left-right-thirty padding-bottom-ten">';
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
    echo "<br/>Posted by: " . Html::encode($name) . ", ";
    echo AppUtility::tzdate("F j, Y, g:i a",$row['postdate']);

    echo "</div>
    <div class=blockitems>";
    echo HtmlPurifier::process(filter($row['message'])); ?>
    <p><a href="<?php echo AppUtility::getURLFromHome('forum','forum/post?courseid='.$cid.'&forumid='.$row['forumid'].'&threadid='.$row['threadid']);?>">Show full thread</a></p>
</div>

<?php }
echo '</div>';

}else
{

    if ($page > 0) {
        $numpages = ceil($countOfPostId[COUNT('id')] / $threadsperpage);
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
                $prevnext .= "<a href=\"thread?page=1&cid=$cid&forum=$forumid\">1</a> ";
            }
            if ($min != 2) {
                $prevnext .= " ... ";
            }
            for ($i = $min; $i <= $max; $i++) {
                if ($page == $i) {
                    $prevnext .= "<b>$i</b> ";
                } else {
                    $prevnext .= "<a href=\"thread?page=$i&cid=$cid&forum=$forumid\">$i</a> ";
                }
            }
            if ($max != $numpages - 1) {
                $prevnext .= " ... ";
            }
            if ($page == $numpages) {
                $prevnext .= "<b>$numpages</b> ";
            } else {
                $prevnext .= "<a href=\"thread?page=$numpages&cid=$cid&forum=$forumid\">$numpages</a> ";
            }
            $prevnext .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";

            if ($page > 1) {
                $prevnext .= "<a href=\"thread?page=" . ($page - 1) . "&cid=$cid&forum=$forumid\">Previous</a> ";
            } else {
                $prevnext .= "Previous ";
            }
            if ($page < $numpages) {
                $prevnext .= "| <a href=\"thread?page=" . ($page + 1) . "&cid=$cid&forum=$forumid\">Next</a> ";
            } else {
                $prevnext .= "| Next ";
            }

            echo "<div>$prevnext</div>";

        }
    } ?>

    <?php
    $toshow = array();
    if ($page<0)
    {
    }else { ?>
        <div class="col-sm-12 col-md-12 padding-top-twenty">
            <?php if ($isteacher && $groupsetid > 0)
            {
                natsort($groupnames);
                echo '<div class="col-md-4 col-sm-6"><span class="padding-right-ten padding-top-five floatleft">Filter By Group </span><select class="form-control width-fifty-five-per" id="ffilter" onChange="chgfilter()"><option value="-1" ';
                if ($curfilter==-1) { echo 'selected="1"';}
                echo '>All groups</option>';
                foreach ($groupnames as $gid=>$gname)
                {
                    echo "<option value=\"$gid\" ";
                    if ($curfilter==$gid) { echo 'selected="1"';}
                    echo ">$gname</option>";
                }
                echo '</select>';

            } ?>
            <?php if ($taglist != '') {

                $p = strpos($taglist,':');

                $tagselect = '<span class="col-md-4 col-sm-4"><span>Filter by </span>'.substr($taglist,0,$p);
                $tagselect .= '<select class="form-control width-fifty-per display-inline-block margin-left-ten" id="tagfilter" onChange="chgtagfilter()"><option value="" ';

                if ($tagfilter == '') {
                    $tagselect .= 'selected="selected"';
                }
                $tagselect .= '>All</option>';
                $tags = explode(',',substr($taglist,$p));
                foreach ($tags as $tag) {

                    $tag =  str_replace('"','&quot;',$tag);
                    $tagselect .= '<option value="'.$tag.'" ';
                    if ($tag == $tagfilter) {$tagselect .= 'selected="selected"';}
                    $tagselect .= '>'.$tag.'</option>';
                }
                $tagselect .= '</select></span>';
                $toshow[] = $tagselect;

            }
            ?>
            </div>

    <?php }
    echo implode(' | ',$toshow);
    if (count($postInformtion) == 0)
    {
        echo '<div class="col-sm-12 padding-left-thirty padding-top-thirty">';
        echo ' <h4>No posts have been made yet.  Click Add New Thread to start a new discussion </h4>';
        echo '</div>';
    }else
    {            ?>

        <div id="data" class="col-sm-12 padding-left-right-thirty padding-top-twenty padding-bottom-ten">
           <div >
                <table style="float: left" id="forum-table displayforum" class="forum-table table table-bordered table-striped table-hover data-table" bPaginate="false">
                    <thead>
                    <th class="width-fifteen-per text-align-center"><?php echo AppUtility::t('Topic')?></th>
                    <?php            if ($isteacher && $groupsetid>0 && !$dofilter) { ?>
                        <th class="width-twenty-per text-align-center"><?php echo AppUtility::t('Groups')?></th>
                    <?php } ?>
                    <th class="width-five-per text-align-center"><?php echo AppUtility::t('Replies')?></th>
                    <th class="width-five-per text-align-center"><?php echo AppUtility::t('Views (Unique)')?></th>
                    <th class="width-twenty-per text-align-center"><?php echo AppUtility::t('Last Post')?></th>
                    <?php if($users['rights'] >= AppConstant::STUDENT_RIGHT){?>
                    <th class="width-fifteen-per text-align-center"><?php echo AppUtility::t('Actions')?></th>
                    <?php } else{?>
                        <th class="width-fifteen-per text-align-center"></th>
                    <?php }?>
                    </thead>
                    <tbody class="forum-table-body">

                    <?php

                    foreach ($postIds as $row) {
                        $uniqviews[$row['id']] = $row['count(imas_forum_views.userid)']-1;
                    }

                    foreach ($postInformtion as $line )
                    {

                        if (isset($postcount[$line['id']]))
                        {
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
                    <td class='width-fifteen-per'>";
                        if ($line['isanon']==1) {
                            $name = "Anonymous";
                        } else {
                            $name = "{$line['LastName']}, {$line['FirstName']} ";
                        } ?>
                        <b><a href="<?php echo AppUtility::getURLFromHome('forum','forum/post?courseid='.$cid.'&forumid='.$forumid.'&threadid='.$line['id']);?>"><?php echo  Html::encode(trim($line['subject']))?></a></b>:
                        <div><?php echo Html::encode($name);?></div>
                        <?php
                        echo "</td>\n";
                        if ($isteacher && $groupsetid>0 && !$dofilter) {
                            echo '<td class="width-twenty-per c">'.$groupnames[$line['stugroupid']].'</td>';
                        }
                        echo "<td class='width-five-per c'>$posts</td>";
                        if ($isteacher) { ?>
                            <td class="pointer c width-five-per" onclick="GB_show( 'Thread Views' ,'<?php echo AppUtility::getURLFromHome('forum','forum/list-views?cid='.$cid.'&thread='.$line['id']);?>',500,500);">
                        <?php } else {
                            echo '<td class="c width-twenty-per">';
                        }
                        echo "{$line['tviews']} ({$uniqviews[$line['id']]})</td>
                        <td class='c width-twenty-per'>$lastpost ";
                        if ($lastpost=='' || $maxdate[$line['id']] > $lastview[$line['id']]) {
                            echo "<div style=\"color: red;\">New</div>";
                        } ?>
                        </td>
                        <td class="width-fifteen-per padding-left-five-per">
                            <div>
                                <?php                   echo "<span class=\" text-align-center\">\n";
                                if ($line['tag']!='') { //category tags
                                    echo '<span class="forumcattag text-align-center">'.$line['tag'].'</span> ';
                                }else{
                                    echo '<span class="forumcattag text-align-center">     </span> ';
                                }
                                echo "</span>\n"; ?>
                            </div>

                            <div class="btn-group">
                                <?php
                                if ($line['posttype']==0) {

                                    if (isset($flags[$line['id']])) {
                                        if($users['rights'] >= AppConstant::STUDENT_RIGHT){?>
                                        <a class='btn btn-primary flag-btn' id="tag{<?php echo $line['id'] ?>}"  onClick="changeImage(this,'true',<?php echo $line['id'] ?>)" > <i class='fa fa-flag'></i> Unflag</a>
                                            <?php }?>
                                    <?php
                                    } else {
                                        if($users['rights'] >= AppConstant::STUDENT_RIGHT){?>

                                        <a class='btn btn-primary flag-btn' id="tag{<?php echo $line['id'] ?>}" onClick="changeImage(this,'true',<?php echo $line['id'] ?>)"> <i class='fa fa-flag-o'></i> Flag</a>
                                            <?php }?>
                        <?php
                      }
                                }else{
                                    if($users['rights'] >= AppConstant::STUDENT_RIGHT){
                                    echo '<a class="btn btn-primary flag-btn disable-btn-not-allowed"> No Flag</a>';
                                    }
                                    } ?>
                                <?php

                                if(($isteacher || ($line['userid']==$users['id'] && $allowmod && time()<$postby)) || ($isteacher || ($allowdel && $line['userid']==$users['id'] && $posts==0))) {?>
                                <a class="btn btn-primary dropdown-toggle" id="drop-down-id" data-toggle="dropdown" href="#">
                                    <span class="fa fa-caret-down "></span>
                                </a>
                                <ul class="dropdown-menu thread-dropdown">

                                    <?php if($isteacher) { ?>
                                        <li> <a href="<?php echo AppUtility::getURLFromHome('forum','forum/move-thread?courseid='.$cid.'&forumid='.$line['forumid'].'&threadid='.$line['id'])?>"><i class='fa fa-scissors'></i>&nbsp;&nbsp;Move</a></li>

                                    <?php }
                                    if ($isteacher || ($line['userid']==$users['id'] && $allowmod && time()<$postby)) { ?>
                                        <li><a href="<?php echo AppUtility::getURLFromHome('forum','forum/modify-post?courseId='.$cid.'&forumId='.$line['forumid'].'&threadId='.$line['id'])?>"><i class='fa fa-pencil fa-fw padding-right-five'></i>&nbsp;Modify</a></li>
                                    <?php }
                                    if ($isteacher || ($allowdel && $line['userid']==$users['id'] && $posts==0)) { ?>
                                        <li><a href='#' name='tabs' data-var='<?php echo $line['id'];?>' class='mark-remove'><i class='fa fa-trash-o'></i>&nbsp;&nbsp;&nbsp;Remove</a></li>
                                    <?php }
                                    ?>
                                </ul>
                        <?php }?>
                            </div>
                        </td>
                        </tr>
                    <?php  }
                    ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div id="searchpost"></div>
    <?php }
    }
?>
</div>
