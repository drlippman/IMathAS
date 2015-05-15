<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use app\components\AppConstant;
use app\components\AppUtility;

$this->title = 'Forums';
$this->params['breadcrumbs'][] = $this->title;
?>
<link rel="stylesheet" type="text/css" href="<?php echo AppUtility::getHomeURL() ?>css/dashboard.css"/>
<!-- DataTables CSS -->
<link rel="stylesheet" type="text/css"
      href="<?php echo AppUtility::getHomeURL() ?>js/DataTables-1.10.6/media/css/jquery.dataTables.css">
<script type="text/javascript" src="<?php echo AppUtility::getHomeURL() ?>js/general.js?ver=012115"></script>
<link rel="stylesheet" href="//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css">
<script type="text/javascript" charset="utf8"
        src="<?php echo AppUtility::getHomeURL() ?>js/DataTables-1.10.6/media/js/jquery.dataTables.js"></script>
<script type="text/javascript" charset="utf8"
        src="<?php echo AppUtility::getHomeURL() ?>js/DataTables-1.10.6/media/js/jquery.dataTables.js"></script>

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


    <?= $form->field($model, 'search')->textInput(['id' => 'search_text']); ?>
    <?= $form->field($model, 'thread')->inline()->radioList(['subject' => 'All thread subjects' , 'post' => 'All Post']) ?>
    <div class="form-group">
        <div class="col-lg-offset-1 col-lg-11">
            <input type="button" id="forum_search" value="Search"/>
        </div>
    </div>


    <input type="hidden" id="courseId" class="courseId" value="<?php echo $cid ?>">

    <br>


    <table id="forum-table displayforum" class="forum-table">
        <thead>
        <tr>
            <th>Forum Name</th>
            <th>Threads</th>
            <th>Posts</th>
            <th>Last Post Date</th>

        </tr>
        </thead>
        <tbody class="forum-table-body">
        </tbody>
    </table>



    <?php ActiveForm::end(); ?>
</div>

    <script>
        $(document).ready(function () {

            var courseId = $('.courseId').val();
            jQuerySubmit('get-forums-ajax', {cid: courseId}, 'forumsSuccess');


            $('#forum_search').click(function () {
                var search = $('#search_text').val();
                var val=$("")
                jQuerySubmit('get-forum-name-ajax', {search: search}, 'getTextSuccess');


            });
        });

        function getTextSuccess(response) {
            console.log(response);

            var result = JSON.parse(response);
            console.log(result);
            if (result.status == 0) {

            }
        }

        function forumsSuccess(response) {
            console.log(response);
            var result = JSON.parse(response);
            if (result.status == 0) {
                var forums = result.forum;

            }
            showForumTable(forums);
        }

        function showForumTable(forums) {

            var html = "";
            $.each(forums, function (index, forum) {
                html += "<tr> <td><a href='#'>" + capitalizeFirstLetter(forum.forumname) + "</a></td>";
                html += "<td>" + forum.threads + "</td>";
                html += "<td>" + forum.posts + "</td>";
                html += "<td>" + forum.lastPostDate + "</td>";
            });
            $(".forum-table-body").append(html);
            $('.forum-table').DataTable();

        }

    </script>
