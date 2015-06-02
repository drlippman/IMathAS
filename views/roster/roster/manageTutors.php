<?php

use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use app\components\AppUtility;
$this->title = 'Manage Tutors';
$this->params['breadcrumbs'][] = ['label' => $course->name, 'url' => ['/instructor/instructor/index?cid=' . $_GET['cid']]];
$this->params['breadcrumbs'][] = ['label' => 'Roster', 'url' => ['/roster/roster/student-roster?cid='.$_GET['cid']]];
$this->params['breadcrumbs'][] = $this->title;

?>
<link rel="stylesheet" href="//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css">
<input type="hidden" class="courseId" value="<?php echo $courseid ?>">
<?php $sectionArray = $section?>

<div id="user-div"></div>
<h2>Manage Tutors</h2>

<div>

<table class='list display-tutor-table'>
    <thead>
        <th>Tutor Name</th>
        <th>Limit to Section</th>
        <th>Remove?  Check <a id="checkAll" class="check-all" href="#">All</a> /
            <a id="checkNone" class="uncheck-all" href="#">None</a></th>
    </thead>
    <tbody class="tutor-table-body">
        <div>
            <?php
                foreach($tutors as $value)
                {
                  echo "<tr><td>{$value['Name']}</td><td><select class = 'show-section' id='{$value['id']}' name = 'select-section'><option value = ''>All</option>" ?>
                    <?php
                        foreach($section as $key => $option)
                            {
                                if($option !== null || $option != "")
                                {
                                    if($option != $value['section'])
                                        {
                                            echo"<option value = '{$option}'>$option</option>";
                                        }
                                    else
                                    {
                                        if($value['section'] != null)
                                            {
                                                echo"<option value = '{$option}' selected='selected'>$option</option>";
                                            }
                                    }
                                }
                            }
                            echo "</select><td><input type = 'checkbox' name = 'tutor-check' value = '{$value['id']}' class = 'master'></td></tr>";
                }
            ?>
        </div>
    </tbody>
</table>
</div><br><br>

<p><b>Add new tutors.</b> Provide a list of usernames below, separated by commas, to add as tutors.</p>
<br>
    <textarea name = "newtutors" id = "tutor-text" rows = "3" cols = "60"></textarea>
<br><br>
<a class = "btn btn-primary" id = "update-btn">Update</a>


<script type = "text/javascript">
    $(document).ready(function () {
        var sessionCount = 0;
        var sectionarray = $(".section-array").val();
        $('.display-tutor-table').DataTable();
        $sessionatr = $.session.get("userNotFound");

        if($sessionatr)
        {
           if(sessionCount == 0)
           {
               $("#user-div").append("<b class = 'btn-danger'>Following Usernames Were Not Found :</b>&nbsp;");
               sessionCount++;
           }
            $.each($sessionatr, function( index, value )
            {
                $("#user-div").append(''+value+'');
            });
            $.session.clear();
            $sessionatr.clean();
        }
        $.session.clear();

        markCheck();
        updateInfo();
    });
</script>
