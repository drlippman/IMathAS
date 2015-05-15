<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use app\components\AppConstant;
use app\components\AppUtility;

$this->title = 'Forums';
$this->params['breadcrumbs'][] = $this->title;
?>
    <!--<link rel="stylesheet" href="../../../web/css/forums.css"/>-->
    <div class="site-login">

        <?php $form = ActiveForm::begin([
            'id' => 'login-form',
            'options' => ['class' => 'form-horizontal'],
            'fieldConfig' => [
                'template' => "{label}\n<div class=\"col-lg-3\">{input}</div>\n<div class=\"col-lg-5\">{error}</div>",
                'labelOptions' => ['class' => 'col-lg-1 control-label'],
            ],
        ]); ?>


        <?= $form->field($model, 'search' )->textInput(['id'=>'search_text']);?>
        <?= $form->field($model, 'thread')->radioList([AppConstant::NUMERIC_ONE => 'All thread subjects', AppConstant::NUMERIC_TWO => 'All Post']) ?>
        <div class="form-group">
            <div class="col-lg-offset-1 col-lg-11">
                <input type="button" id="forum_search" value="Search"/>
            </div>
        </div>


        <input type="hidden" id="courseId" class="courseId" value="<?php echo $cid ?>">


       <?php if(!empty($forum)) {?>

            <table class="forum">
            <thead>
            <tr>

                <th id="move">Forum Name</th>
                <th id="move">Threads</th>
                <th id="move">Id</th>
                <th id="move">Posts</th>
                <th id="move">Last Post Date</th>

            </tr>

            </thead>


            <tbody>
            <?php foreach($forum as $key => $forum){?>


                <tr>

                    <td ><b><a id="abc" href="<?php echo ($forum->id);?>"><?php echo ($forum->name);?></a></b></td>
                    <td class="c"><?php echo 0 ?></td>
                    <td id="forums-id"><?php echo ($forum->id);?></td>
                    <td class="c"><?php echo 0 ?></td>
                    <td class="c"><?php echo ''?></td>

                </tr>



            <?php } ?>


            </tbody>

        </table>
       <?php }elseif(($users->rights)== 20){
           echo '<p>There are no active forums at this time.you can add it using course page</p>';
       }else{
       echo '<p>There are no active forums at this time</p>';
       }
       ?>





    </div>

<?php ActiveForm::end(); ?>

<script>
    $(document).ready(function() {

        var courseId = $('.courseId').val();

        jQuerySubmit('get-forums-ajax',{cid: courseId},'forumsSuccess');



        $('#forum_search').click(function() {
            var search = $('#search_text').val();
            var forumId= $('#forums-id').val();
            alert(forumId);
            jQuerySubmit('get-forum-name-ajax',{search: search},'getTextSuccess');


        });
    });

    function getTextSuccess(response)
    {
        console.log(response);

        var result = JSON.parse(response);
        console.log(result);
        if(result.status == 0)
        {

        }
    }

    function forumsSuccess(response)
    {
        console.log(response);

        var result = JSON.parse(response);
        console.log(result);
        if(result.status == 0)
        {

        }
    }
</script>