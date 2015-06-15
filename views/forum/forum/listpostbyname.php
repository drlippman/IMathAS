<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use app\components\AppUtility;
$this->title = 'Thread';
//$this->params['breadcrumbs'][] = ['label' => $course->name, 'url' => [AppUtility::getRefererUri(Yii::$app->session->get('referrer'))]];
//$this->params['breadcrumbs'][] = ['label' => 'Forums', 'url' => ['/forum/forum/search-forum?cid='.$course->id]];
$this->params['breadcrumbs'][] = $this->title;
?>
<div><h3>Post by Name- <?php echo $forumname->name?></h3></div>
<br>
<div class="midwrapper">
    <button id="expand" onclick="expandall()" class="btn btn-primary">Expand All</button>
    <button  onclick="markall()" class="btn btn-primary">Mark All</button>
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
                    <a href="#">Reply</a>
                    </span><input type="button" value="+" onclick="toggleshow(2)" id="butn2">
                        <b><?php if($data['parent']!= 0){
                            echo '<span style="color:green;">';
                            echo  $data['subject'];
                           }else{
                        echo  $data['subject'];
                            }
                            ?>
                        </b>,Posted: <?php echo $data['postdate']?></div>
                    <div id="m2" class="blockitems"><p><?php echo $data['message']?></p></div>
                    </div>
                    <?php $name=$data['name'];
            }
            else{?>
                    <div class="block"><span class="right"><a href="<?php echo AppUtility::getURLFromHome('forum', 'forum/post?courseid='. $courseid.'&threadid='.$data['threadId'].'&forumid='.$data['forumiddata']); ?>">Thread</a>
                    <a href="#">Reply</a>
                    </span><input type="button" value="+" onclick="toggleshow(2)" id="butn2">
                             <b><?php if($data['parent']!= 0){
                                     echo '<span style="color:green;">';
                                     echo  $data['subject'];
                         }else{
                             echo  $data['subject'];
                         }
                         ?>
                        <?php $name=$data['name'];?>
                     </b>,Posted: <?php echo $data['postdate']?></div>
                     <div id="m2" class="blockitems"><p><?php echo $data['message']?></p></div>
                     </div>

          <?php }
    }
}?>
<input type="hidden" id="count" value="<?php echo $count;?>">
<?php echo "<p>Color code<br/>Black: New thread</br><span style=\"color:green;\">Green: Reply</span></p>"?>
<a href="<?php AppUtility::getHomeURL('forum','forum/thread?cid='. $courseid.'&forumid='.$forumid);?>">Back to Thread List</a>

<script>
$(document).ready(function ()
{
        hidebody();
        $('#butn2').click(function()
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
        for(var i=0; i< count; i++){

            $('.blockitems').show();
        }

    }


    function toggleshow(bnum) {
        var node = document.getElementById('m'+bnum);
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

//             var node = document.getElementById('btn2'+i);
//             var butn = document.getElementById(count+i);
//             node.className = 'blockitems';
//             butn.value = '-';
             $('.blockitems').show(i);

         }

     }





</script>