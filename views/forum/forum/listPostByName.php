<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use app\components\AppConstant;
use app\components\AppUtility;
$this->title = 'ListPostByName';
if ($userRights > AppConstant::STUDENT_RIGHT){

    $this->params['breadcrumbs'][] = ['label' => $course->name, 'url' => ['/instructor/instructor/index?cid=' . $course->id]];
}
else{
    $this->params['breadcrumbs'][] = ['label' => $course->name, 'url' => ['/course/course/index?cid=' . $course->id]];
}
$this->params['breadcrumbs'][] = ['label' => 'Forum', 'url' => ['/forum/forum/search-forum?cid='.$course->id]];
$this->params['breadcrumbs'][] = ['label' => 'Thread', 'url' => ['/forum/forum/thread?cid='.$course->id.'&forumid='.$forumId]];
$this->params['breadcrumbs'][] = $this->title;
if($status == AppConstant::NUMERIC_ONE){?>
    <div><h3>Post by Name- <?php echo $forumName->name?></h3></div>
    <br>
    <div class="midwrapper">
        <input type="button" id="expand" onclick="collapseall()" class="btn btn-primary" value="Expand All">
        <button  onclick="markall(<?php echo $forumId;?>)" class="btn btn-primary">Mark All Read</button>
        <br><br>
    </div>
    <?$count =0;?>
    <?php foreach($threadArray as $i => $data)
    {
        if($forumId == $data['forumIdData'])
        {$count++;?>
            <div class="listpostbyname">
            <?php
            if($name != $data['name'])
            {?>
                <div class="" id="<?php echo $data['userId']?>">
                    <?php $imageUrl = $data['userId'].''.".jpg";?>
                    <?php if($data['hasImg'] == 1){ ?>
                        <img class="circular-profile-image" id="img<?php echo $imgCount?>"src="<?php echo AppUtility::getAssetURL() ?>Uploads/<?php echo $imageUrl?>" onclick=changeProfileImage(this,<?php echo $data['userId']?>); />
                    <?php }else{?>
                        <img class="circular-profile-image" id="img"src="<?php echo AppUtility::getAssetURL() ?>Uploads/dummy_profile.jpg"/>
                    <?php }?>
                    <strong><?php echo $data['name']?></strong>
                </div>
                <div class="block"><span class="right"><a href='<?php echo AppUtility::getURLFromHome('forum', 'forum/post?courseid='. $course->id.'&threadid='.$data['threadId'].'&forumid='.$data['forumIdData']); ?>'>Thread</a>
                        <?php if($userRights > AppConstant::NUMERIC_TEN){?>
                            <a href="<?php echo AppUtility::getURLFromHome('forum','forum/modify-post?forumId='.$data['forumIdData'].'&courseId='.$course->id.'&threadId='.$data['id']); ?>">Modify</a>&nbsp;<a href="#" name="tabs" data-var="<?php echo $data['id']?>" class="mark-remove" >Remove</a>
                        <?php }?>
                        <a href="<?php echo AppUtility::getURLFromHome('forum', 'forum/reply-post?courseid='. $course->id.'&threadId='.$data['threadId'].'&forumid='.$data['forumIdData'].'&id='.$data['id'].'&listbypost=1'); ?>">Reply</a>
                    </span><input type="button" value="+" onclick="toggleshow(<?php echo $i ?>)" id="butn<?php echo $i ?>">
                    <b><?php if($data['parent']!= AppConstant::NUMERIC_ZERO){
                            echo '<span style="color:green;">';
                            echo  $data['subject'];
                        }else{
                            echo  $data['subject'];
                        }
                        ?>
                    </b>,Posted: <?php echo $data['postdate']?>
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
                <div class="block"><span class="right"><a href="<?php echo AppUtility::getURLFromHome('forum', 'forum/post?courseid='. $course->id.'&threadid='.$data['threadId'].'&forumid='.$data['forumIdData']); ?>">Thread</a>
                        <?php if($userRights > AppConstant::NUMERIC_TEN){?>
                            <a href="<?php echo AppUtility::getURLFromHome('forum','forum/modify-post?forumId='.$data['forumIdData'].'&courseId='.$course->id.'&threadId='.$data['id']); ?>">Modify</a>&nbsp;<a href="#" name="tabs" data-var="<?php echo $data['id']?>" class="mark-remove" >Remove</a>
                        <?php }?>
                        <a href="<?php echo AppUtility::getURLFromHome('forum', 'forum/reply-post?courseid=' . $course->id.'&threadId='.$data['threadId'].'&forumid='.$data['forumIdData'].'&id='.$data['id'].'&listbypost=1'); ?>">Reply</a>
                    </span><input type="button" value="+" onclick="toggleshow(<?php echo $i ?>)" id="butn<?php echo $i ?>">
                    <b><?php if($data['parent']!= AppConstant::NUMERIC_ZERO){
                            echo '<span style="color:green;">';
                            echo  $data['subject'];
                        }else{
                            echo  $data['subject'];
                        }
                        ?>
                        <?php $name=$data['name'];?>
                    </b>,Posted: <?php echo $data['postdate']?>
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
    <?php echo "<p><Bold><strong>Color code:</strong></Bold><br/>Black: New thread</br><span style=\"color:green;\">Green: Reply</span></p>"?>


<?php }else{?>

    <input type="hidden" id="isData" value="0">

<?php }?>
<div><a href="<?php echo AppUtility::getURLFromHome('forum','forum/thread?cid='. $course->id.'&forumid='.$forumId);?>">Back to Thread List</a></div>