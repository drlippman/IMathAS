<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use app\components\AppConstant;
use app\components\AppUtility;

$this->title = 'ListPostByName';
//$this->params['breadcrumbs'][] = ['label' => $course->name, 'url' => [AppUtility::getRefererUri(Yii::$app->session->get('referrer'))]];
//$this->params['breadcrumbs'][] = ['label' => 'Forums', 'url' => ['/forum/forum/search-forum?cid='.$course->id]];
$this->params['breadcrumbs'][] = $this->title;
?>
<?php if($status == AppConstant::NUMERIC_ONE){?>
<div><h3>Post by Name- <?php echo $forumname->name?></h3></div>
<br>
<div class="midwrapper">
    <input type="button" id="expand" onclick="expandall()" class="btn btn-primary" value="Expand All">
    <input type="button" id="collapse" onclick="collapseall()" class="btn btn-primary" value="Collapse All">
    <button  onclick="markall()" class="btn btn-primary">Mark All Read</button>
    <br><br>
</div>
<?$count =0;?>
<?php foreach($threadArray as $i => $data)
{
    if($forumid == $data['forumiddata'])
    {$count++;?>
        <div class="listpostbyname">
        <?php
        if($name != $data['name'])
            {?>
                    <div class=""><strong><?php echo $data['name']?></strong></div>
                    <div class="block"><span class="right"><a href='<?php echo AppUtility::getURLFromHome('forum', 'forum/post?courseid='. $courseid.'&threadid='.$data['threadId'].'&forumid='.$data['forumiddata']); ?>'>Thread</a>
                    <a href="<?php echo AppUtility::getURLFromHome('forum', 'forum/reply-post-by-name?cid='. $courseid.'&threadId='.$data['threadId'].'&forumid='.$data['forumiddata'].'&replyto='.$data['id']); ?>">Reply</a>
                    </span><input type="button" value="+" onclick="toggleshow($data['id'])" id="butn">
                        <b><?php if($data['parent']!= AppConstant::NUMERIC_ZERO){
                            echo '<span style="color:green;">';
                            echo  $data['subject'];
                           }else{
                        echo  $data['subject'];
                            }
                            ?>
                        </b>,Posted: <?php echo $data['postdate']?></div>
                    <div id="message2" class="blockitems"><p><?php echo $data['message']?></p></div>
                    </div>
                    <?php $name=$data['name'];
            }
            else{?>
                    <div class="block"><span class="right"><a href="<?php echo AppUtility::getURLFromHome('forum', 'forum/post?courseid='. $courseid.'&threadid='.$data['threadId'].'&forumid='.$data['forumiddata']); ?>">Thread</a>
                    <a href="<?php echo AppUtility::getURLFromHome('forum', 'forum/reply-post-by-name?cid=' . $courseid.'&threadId='.$data['threadId'].'&forumid='.$data['forumiddata'].'&replyto='.$data['id']); ?>">Reply</a>
                    </span><input type="button" value="+" onclick="toggleshow(2)" id="butn2">
                             <b><?php if($data['parent']!= AppConstant::NUMERIC_ZERO){
                                     echo '<span style="color:green;">';
                                     echo  $data['subject'];
                         }else{
                             echo  $data['subject'];
                         }
                         ?>
                        <?php $name=$data['name'];?>
                     </b>,Posted: <?php echo $data['postdate']?></div>
                     <div id="message2" class="blockitems"><p><?php echo $data['message']?></p></div>
                     </div>

          <?php }
    }
    }?>
    <input type="hidden" id="count" value="<?php echo $count;?>">
    <?php echo "<p><Bold><strong>Color code:</strong></Bold><br/>Black: New thread</br><span style=\"color:green;\">Green: Reply</span></p>"?>


<?php }else{?>

    <input type="hidden" id="isData" value="0">

<?php }?>
<div><a href="<?php echo AppUtility::getURLFromHome('forum','forum/thread?cid='. $courseid.'&forumid='.$forumid);?>">Back to Thread List</a></div>
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
});

    function hidebody()
    {
        var count = $('#count').val();

        for(var i=0; i< count; i++){

            $('.blockitems').hide();
        }

    }
    function expandall()
    {
        var count = $('#count').val();
        for(var i=0; i< count; i++)
        {
                $('.blockitems').show();
        }

        $('#collapse').show();
        $('#expand').hide()


    }

    function collapseall()
    {

        var count = $('#count').val();
        for(var i=0; i< count; i++)
        {
            $('.blockitems').hide();
        }

        $('#collapse').hide();
        $('#expand').show()

    }


    function toggleshow(bnum) {
        var node = document.getElementById(bnum);
        var butn = document.getElementById('butn'+bnum);
        if (node.className == 'blockitems') {
            node.className = 'hidden';
            butn.value = '+';
        } else {
            node.className = 'blockitems';
            butn.value = '-';
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




</script>