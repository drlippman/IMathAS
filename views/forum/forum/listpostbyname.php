<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use app\components\AppUtility;
$this->title = 'Thread';
//$this->params['breadcrumbs'][] = ['label' => $course->name, 'url' => [AppUtility::getRefererUri(Yii::$app->session->get('referrer'))]];
//$this->params['breadcrumbs'][] = ['label' => 'Forums', 'url' => ['/forum/forum/search-forum?cid='.$course->id]];
$this->params['breadcrumbs'][] = $this->title;

?>
<div class="midwrapper">

    <button onclick="expandall()" class="btn btn-primary">Expand All</button>
    <button onclick="markall()" class="btn btn-primary">Mark All</button>
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
                    <div class="block"><span class="right"><a href="#">Thread</a>
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

                    <div class="block"><span class="right"><a href="#">Thread</a>
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

          <?php }

    }
}?>

<input type="hidden" id="count" value="<?php echo $count;?>">
<?php echo "<p>Color code<br/>Black: New thread</br><span style=\"color:green;\">Green: Reply</span></p>";
echo "<p><a href='#'>Back to Thread List</a></p>";?>

<script>


$(document).ready(function ()
{
        hidebody();
        $('#butn2').click(function()
        {
           showall();
        });
});
    function hidebody(){
        var count = $('#count').val();

        for(var i=0; i< count; i++){

            $('.blockitems').hide();
        }

    }
     function showall()
     {

         var count = $('#count').val();
         for(var i=0; i< count; i++){

             var node = document.getElementById('m'+i);
             var butn = document.getElementById(count+i);
             node.className = 'blockitems';
             butn.value = '-';

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
//function toggleshowall() {
//    for (var i=0; i<bcnt; i++) {
//        var node = document.getElementById('m'+i);
//        var butn = document.getElementById('butn'+i);
//        node.className = 'blockitems';
//        butn.value = '-';
//    }
//    document.getElementById("toggleall").value = 'Collapse All';
//    document.getElementById("toggleall").onclick = togglecollapseall;
//}


</script>