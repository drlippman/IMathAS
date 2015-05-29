<?php

use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use app\components\AppUtility;
$this->title = 'Manage Tutors';
$this->params['breadcrumbs'][] = ['label' => 'Course', 'url' => ['/instructor/instructor/index?cid='.$_GET['cid']]];
$this->params['breadcrumbs'][] = ['label' => 'Roster', 'url' => ['/roster/roster/student-roster?cid='.$_GET['cid']]];
$this->params['breadcrumbs'][] = $this->title;

?>

<link rel="stylesheet" type="text/css" href="<?php echo AppUtility::getHomeURL() ?>js/DataTables-1.10.6/media/css/jquery.dataTables.css">
<script type="text/javascript" src="<?php echo AppUtility::getHomeURL() ?>js/general.js?ver=012115"></script>
<link rel="stylesheet" href="//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css">
<script type="text/javascript" charset="utf8" src="<?php echo AppUtility::getHomeURL() ?>js/DataTables-1.10.6/media/js/jquery.dataTables.js"></script>
<script type="text/javascript" charset="utf8" src="<?php echo AppUtility::getHomeURL() ?>js/DataTables-1.10.6/media/js/jquery.dataTables.js"></script>
<input type="hidden" class="sectionInfo" value="<?php echo $section[0] ?>">
<input type="hidden" class="courseId" value="<?php echo $courseid ?>">
<?php $ar = $section?>

<h2>Manage Tutors</h2>

<div>

<table class='list'>
    <thead>
        <th>Tutor Name</th>
        <th>Limit to Section</th>
        <th>Remove?  Check <a id="checkAll" class="check-all" href="#">All</a> /
            <a id="checkNone" class="uncheck-all" href="#">None</a></th>
    </thead>
    <tbody>
    <div>
    <?php
    foreach($student as $value)
    {
        echo "<tr><td>{$value['Name']}</td><td><select class='show-section'></select><td><input type='checkbox' name='{$value['id']}' value='{$value['id']}' class='master'></td></tr>";
    }
    ?>
    </div>
    </tbody>

</table>
</div>
<br>
<br>
<p><b>Add new tutors.</b> Provide a list of usernames below, separated by commas, to add as tutors.</p>
<br>
<textarea name="newtutors" id="tutor-text" rows="3" cols="60"></textarea><br><br>
<a class="btn btn-primary" id="update-btn">Update</a>


<script type="text/javascript">
    $(document).ready(function () {
        var cid = $(".sectionInfo").val();
        var ar = <?php echo json_encode($ar) ?>;
        updateInfo();
        for ( var i = 0; i < ar.length; i++ ) {
            $('.show-section').append('<option value="'+i+'">'+ar[i]+'</option>');
        }

        $('#checkNone').click(function() {
            $('.list input[type = "checkbox"]').prop('checked', false);
        });
        $('#checkAll').click(function() {
            $('.list input[type = "checkbox"]').prop('checked', true);
        });
    });
    function updateInfo()
    {
        $("#update-btn").click(function(){

            var cid = $(".courseId").val();
            var data =  {courseid:cid};
            var usernames = $("#tutor-text").val();
            var data =  {courseid:cid,username:usernames};

            jQuerySubmit('mark-update-ajax', data, 'markUpdateSuccess');

        });
    }
    function markUpdateSuccess(response){
        console.log(response);

    }
    </script>