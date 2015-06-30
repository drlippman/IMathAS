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
//$this->params['breadcrumbs'][] = ['label' => 'Course', 'url' => [Yii::$app->session->get('referrer')]];
$this->params['breadcrumbs'][] = ['label' => 'Forum', 'url' => ['/forum/forum/search-forum?cid='.$course->id]];
$this->params['breadcrumbs'][] = $this->title;
if($status == AppConstant::NUMERIC_ONE){?>
<div><h3>Post by Name- <?php echo $forumName->name?></h3></div>
<br>
<div class="midwrapper">
    <input type="button" id="expand" onclick="collapseall()" class="btn btn-primary" value="Expand All">
<!--    <input type="button" id="collapse" onclick="collapseall()" class="btn btn-primary" value="Collapse All">-->
    <button  onclick="markall()" class="btn btn-primary">Mark All Read</button>
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
                            <a href="<?php echo AppUtility::getURLFromHome('forum', 'forum/reply-post-by-name?cid='. $course->id.'&threadId='.$data['threadId'].'&forumid='.$data['forumIdData'].'&replyto='.$data['id']); ?>">Reply</a>
                    </span><input type="button" value="+" onclick="toggleshow(<?php echo $i ?>)" id="butn<?php echo $i ?>">
                        <b><?php if($data['parent']!= AppConstant::NUMERIC_ZERO){
                            echo '<span style="color:green;">';
                            echo  $data['subject'];
                           }else{
                        echo  $data['subject'];
                            }
                            ?>
                        </b>,Posted: <?php echo $data['postdate']?></div>
                    <div id="item<?php echo $i ?>" class="blockitems"><p><?php echo $data['message']?></p></div>
                    </div>
                    <?php $name=$data['name'];
            }
            else{?>
                    <div class="block"><span class="right"><a href="<?php echo AppUtility::getURLFromHome('forum', 'forum/post?courseid='. $course->id.'&threadid='.$data['threadId'].'&forumid='.$data['forumIdData']); ?>">Thread</a>
                           <?php if($userRights > AppConstant::NUMERIC_TEN){?>
                           <a href="<?php echo AppUtility::getURLFromHome('forum','forum/modify-post?forumId='.$data['forumIdData'].'&courseId='.$course->id.'&threadId='.$data['id']); ?>">Modify</a>&nbsp;<a href="#" name="tabs" data-var="<?php echo $data['id']?>" class="mark-remove" >Remove</a>
                           <?php }?>
                    <a href="<?php echo AppUtility::getURLFromHome('forum', 'forum/reply-post-by-name?cid=' . $course->id.'&threadId='.$data['threadId'].'&forumid='.$data['forumIdData'].'&replyto='.$data['id']); ?>">Reply</a>
                    </span><input type="button" value="+" onclick="toggleshow(<?php echo $i ?>)" id="butn<?php echo $i ?>">
                             <b><?php if($data['parent']!= AppConstant::NUMERIC_ZERO){
                                     echo '<span style="color:green;">';
                                     echo  $data['subject'];
                         }else{
                             echo  $data['subject'];
                         }
                         ?>
                        <?php $name=$data['name'];?>
                     </b>,Posted: <?php echo $data['postdate']?></div>
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
<script>
$(document).ready(function ()
{

   var isData =  $('#isData').val();
    if(isData == 0){
        var msg = 'Does not contains any record';
        CommonPopUp(msg);
    }
        hidebody();
        $('#collapse').hide();
        $('#butn').click(function()
        {
            ExpandOne();
        });

    $("a[name=tabs]").on("click", function () {
        var threadid = $(this).attr("data-var");
        var html = '<div><p>Are you sure? This will remove your thread.</p></div>';
        $('<div id="dialog"></div>').appendTo('body').html(html).dialog({
            modal: true, title: 'Message', zIndex: 10000, autoOpen: true,
            width: 'auto', resizable: false,
            closeText: "hide",
            buttons: {
                "Cancel": function () {
                    $(this).dialog('destroy').remove();
                    return false;
                },
                "confirm": function () {
                    $(this).dialog("close");
                    var threadId = threadid;
                    //jQuerySubmit('mark-as-remove-ajax', {threadId:threadId}, 'markAsRemoveSuccess');
                    return true;
                }
            },
            close: function (event, ui) {
                $(this).remove();
            }
        });
    });
});

    function hidebody()
    {
        var count = $('#count').val();

        for(var i=0; i< count; i++){

            var node = document.getElementById('item'+i);
            node.className = 'hidden';
        }

    }
    function collapseall()
    {
        var count = $('#count').val();
        for(var i=0; i< count; i++)
        {
            var node = document.getElementById('item' + i);
            var buti = document.getElementById('butn' + i);
            node.className = 'blockitems';
            buti.value = '-';
        }
        document.getElementById("expand").value = 'Collapse All';
        document.getElementById("expand").onclick = expandall;
    }


    function expandall()
    {
        var count = $('#count').val();
        for(var i=0; i< count; i++)
        {
            var node = document.getElementById('item' + i);
            var buti = document.getElementById('butn' + i);
            node.className = 'hidden';
            buti.value = '+';
        }
        document.getElementById("expand").value = 'Expand All';
        document.getElementById("expand").onclick = collapseall;
    }


    function toggleshow(inum)
    {
        var node = document.getElementById('item' + inum);
        var buti = document.getElementById('butn' + inum);
        if (node.className == 'blockitems')
        {
            node.className = 'hidden';
            buti.value = '+';
        }
        else
        {
            node.className = 'blockitems';
            buti.value = '-';
        }
    }


     function showall()
     {
         var count = $('#count').val();
         for(var i=0; i< count; i++){

             $('.blockitems').show(i);

         }

     }

    function markall(){

        alert("nbndb");
    }
var  flag =0;
function changeProfileImage(element,id)
{
    if(flag == 0 )
    {
        element.style.width = "200px";
        element.style.height = "175px";
        flag =1;
    }else
    {
        element.style.width = "47px";
        element.style.height = "47px";
        flag=0;
    }

}



</script>