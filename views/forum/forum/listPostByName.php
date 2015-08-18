<?php
use app\components\AppUtility;
use app\components\AssessmentUtility;
use app\components\CourseItemsUtility;
use app\components\AppConstant;
use yii\widgets\ActiveForm;
$this->title = AppUtility::t('List Post By Name',false );
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="item-detail-header">
    <?php if($userRights == 100 || $userRights == 20) {
        echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false), $course->name], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'instructor/instructor/index?cid=' . $course->id]]);
    } elseif($userRights == 10){
        echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false), $course->name], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'course/course/index?cid=' . $course->id]]);
    }?>
</div>
<div class = "title-container">
    <div class="row">
        <div class="pull-left page-heading">
            <div class="vertical-align title-page"><?php echo AppUtility::t('Forums:',false);?><?php echo $this->title ?></div>
        </div>
    </div>
</div>
<div class="item-detail-content">
    <?php if($userRights == 100 || $userRights == 20) {
        echo $this->render("../../instructor/instructor/_toolbarTeacher", ['course' => $course, 'section' => 'Forums']);
    } elseif($userRights == 10){
        echo $this->render("../../course/course/_toolbarStudent", ['course' => $course, 'section' => 'Forums']);
    }?>
</div>
<div class="tab-content shadowBox ">
    <?php if($status == AppConstant::NUMERIC_ONE){?>
        <div class="midwrapper Align-buttons-post">
            <input type="button" id="expand" onclick="collapseall()" class="btn btn-primary add-new-thread" value="Expand All">
            <input type="button"  onclick="markall(<?php echo $forumId;?>)" class="btn btn-primary add-new-thread" value="Mark All Read">
            <br><br>
        </div>
<?$count =0;?>
    <?php foreach($threadArray as $i => $data)
    {
    $allowDel = ($userRights >= AppConstant::TEACHER_RIGHT || (($data['settings'])&AppConstant::NUMERIC_FOUR)== AppConstant::NUMERIC_FOUR);
    $allowMod = ($userRights >= AppConstant::TEACHER_RIGHT || (($data['settings'])&AppConstant::NUMERIC_TWO)== AppConstant::NUMERIC_TWO);
    $canViewAll = ($userRights >= AppConstant::TEACHER_RIGHT);
    $allowReply = ($canViewAl || (time()<$data['replyby']));
    if($forumId == $data['forumIdData'])
    {$count++;?>
    <div class="listpostbyname">
        <?php
        if($name != $data['name'])
        {?>
        <div class="" id="<?php echo $data['userId']?>">
            <?php $imageUrl = $data['userId'].''.".jpg";?>
            <?php if($data['hasImg'] == 1){ ?>
                <img class="circular-profile-image Align-link-post padding-five" id="img<?php echo $imgCount?>"src="<?php echo AppUtility::getAssetURL() ?>Uploads/<?php echo $imageUrl?>" onclick=changeProfileImage(this,<?php echo $data['userId']?>); />
            <?php }else{?>
                <img class="circular-profile-image Align-link-post padding-five" id="img"src="<?php echo AppUtility::getAssetURL() ?>Uploads/dummy_profile.jpg"/>
            <?php }?>
            <strong><?php echo $data['name']?></strong>
        </div>
        <div class="block Align-block"><span class="right"><a href='<?php echo AppUtility::getURLFromHome('forum', 'forum/post?courseid='. $course->id.'&threadid='.$data['threadId'].'&forumid='.$data['forumIdData']); ?>'>Thread</a>
                <?php if($userRights >= AppConstant::TEACHER_RIGHT || $data['userId']== $currentuserId && $allowMod){?>
                    <a href="<?php echo AppUtility::getURLFromHome('forum','forum/modify-post?forumId='.$data['forumIdData'].'&courseId='.$course->id.'&threadId='.$data['id']); ?>">Modify</a>&nbsp;
                <?php }?>
                <?php if($data['userId']== $currentuserId && $allowDel){?>
                    <a href="#" name="tabs" data-var="<?php echo $data['id']?>" class="mark-remove" >Remove</a>
                <?php }?>
                <?php if($userRights >= AppConstant::NUMERIC_FIVE && $data['postType']!=2 && $allowReply){?>
                    <a href="<?php echo AppUtility::getURLFromHome('forum', 'forum/reply-post?courseid='. $course->id.'&threadId='.$data['threadId'].'&forumid='.$data['forumIdData'].'&id='.$data['id'].'&listbypost=1'); ?>">Reply</a>
                <?php }?>
                    </span><input type="button" value="+" onclick="toggleshow(<?php echo $i ?>)" class="btn-color" id="butn<?php echo $i ?>">
            <?php if($data['parent']!= AppConstant::NUMERIC_ZERO){
                    echo '<span style="color:green;">';
                    echo  $data['subject'];
                }else{
                    echo  $data['subject'];
                }
                ?>
            , Posted: <?php echo $data['postdate']?>
            <?php
            if(strtotime($data['postdate']) >= $data['lastView'] ){?>
                <span  class="New" style="color:red;">New</span>
            <?php }?>
        </div>
        <div id="item<?php echo $i ?>" class="blockitems"><p><?php echo $data['message']?></p></div>
    </div>
    <?php $name=$data['name'];
    }
    else{?>
    <div class="block Align-block"><span class="right"><a href="<?php echo AppUtility::getURLFromHome('forum', 'forum/post?courseid='. $course->id.'&threadid='.$data['threadId'].'&forumid='.$data['forumIdData']); ?>">Thread</a>
            <?php if($userRights >= AppConstant::TEACHER_RIGHT || $data['userId']== $currentuserId && $allowMod){?>
                <a href="<?php echo AppUtility::getURLFromHome('forum','forum/modify-post?forumId='.$data['forumIdData'].'&courseId='.$course->id.'&threadId='.$data['id']); ?>">Modify</a>&nbsp;
            <?php }?>
            <?php if($data['userId']== $currentuserId && $allowDel){?>
                <a href="#" name="tabs" data-var="<?php echo $data['id']?>" class="mark-remove" >Remove</a>
            <?php }?>
            <?php if($userRights >= AppConstant::NUMERIC_FIVE && $data['postType']!=2 && $allowReply){?>
                <a href="<?php echo AppUtility::getURLFromHome('forum', 'forum/reply-post?courseid=' . $course->id.'&threadId='.$data['threadId'].'&forumid='.$data['forumIdData'].'&id='.$data['id'].'&listbypost=1'); ?>">Reply</a>
            <?php }?>
                    </span><input type="button" value="+" onclick="toggleshow(<?php echo $i ?>)"  id="butn<?php echo $i ?>">
        <?php if($data['parent']!= AppConstant::NUMERIC_ZERO){
                echo '<span style="color:green;">';
                echo  $data['subject'];
            }else{
                echo  $data['subject'];
            }
            ?>
            <?php $name=$data['name'];?>
        , Posted: <?php echo $data['postdate']?>
        <?php
        if(strtotime($data['postdate']) > $data['lastView'] || $data['lastView']== null){?>
            <span  class="New" style="color:red;">New</span>
        <?php }?>
    </div>
    <div id="item<?php echo $i ?>" class="blockitems"><p><?php echo $data['message']?></p></div>
</div>
<?php }
}
}?>
<input type="hidden" id="count" value="<?php echo $count;?>">
<?php echo "<p class='Align-link-post'><Bold><strong>Color code:</strong></Bold><br/>Black: New thread</br><span style=\"color:green;\">Green: Reply</span></p>"?>
<?php }else{?>
       <input type="hidden" id="isData" value="0">
    <?php }?>
    <div class="Align-link-post"><a href="<?php echo AppUtility::getURLFromHome('forum','forum/thread?cid='. $course->id.'&forumid='.$forumId);?>">Back to Thread List</a></div>

