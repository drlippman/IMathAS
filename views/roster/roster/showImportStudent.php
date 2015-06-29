<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use app\components\AppUtility;
use app\components\AppConstant;

//use app\widgets\FileInput;
$this->title = 'Show imported students details';
$this->params['breadcrumbs'][] = ['label' => $course->name, 'url' => ['/instructor/instructor/index?cid='.$course->id]];
$this->params['breadcrumbs'][] = ['label' => 'Roster', 'url' => ['/roster/roster/student-roster?cid='.$course->id]];
$this->params['breadcrumbs'][] = ['label' => 'Import student', 'url' => ['/roster/roster/import-student?cid='.$course->id]];
$this->params['breadcrumbs'][] = $this->title;

?>
<input type="hidden" id="course-id" value="<?php echo $courseId ?>" xmlns="http://www.w3.org/1999/html">
<div class="import-student">
    <fieldset>

<h3><strong>New students Record</strong> </h3>
        <table id="user-table displayUser"  class="display-user-table table table-bordered table-striped table-hover data-table" bPaginate="false">
            <thead>
            <tr>
                <th>Username</th>
                <th>FirstName</th>
                <th>LastName</th>
                <th>Email</th>

                <?php if ($isCodePresent == true) {
                    ?>
                    <th>Code</th>
                <?php
                }
                if ($isSectionPresent == true) {
                    ?>
                    <th>Section</th>
                <?php } ?>
            </tr>
            </thead>
            <tbody class="user-table-body" >
            <?php
            if (isset($uniqueStudents)) {
                foreach ($uniqueStudents as $singleRecord) {

                    ?>
                    <tr>
                        <td><?php echo $singleRecord[0] ?></td>
                        <td><?php echo $singleRecord[1] ?></td>
                        <td><?php echo $singleRecord[2] ?></td>
                        <td><?php echo $singleRecord[3] ?></td>
                        <?php if ($isCodePresent == 1) {
                            ?>
                            <th><?php echo $singleRecord[4] ?></th>
                        <?php
                        }
                        if ($isSectionPresent == 1) {
                            ?>
                            <th><?php echo $singleRecord[5] ?></th>
                        <?php } ?>
                    </tr>

                <?php   $index++; } ?>
            <?php }?>

            </tbody>
        </table>

        <span class="pull-left "><h3><strong>Duplicate students Records</strong></h3> </span><span class="pull-left show-import-student-duplicate-table "><strong>(These students will not be saved)</strong></span>
        <table class="display-user-table table table-bordered table-striped table-hover data-table" bPaginate="false">
            <thead>
            <tr>
                <th>Username</th>
                <th>FirstName</th>
                <th>LastName</th>
                <th>Email</th>

                <?php if ($isCodePresent == true) {
                    ?>
                    <th>Code</th>
                <?php
                }
                if ($isSectionPresent == true) {
                    ?>
                    <th>Section</th>
                <?php } ?>
            </tr>
            </thead>
            <tbody class="user-table-body" >
            <?php
            if (isset($duplicateStudents)) {
                foreach ($duplicateStudents as $singleRecord) {

                    ?>
                    <tr>
                        <td><?php echo $singleRecord[0] ?></td>
                        <td><?php echo $singleRecord[1] ?></td>
                        <td><?php echo $singleRecord[2] ?></td>
                        <td><?php echo $singleRecord[3] ?></td>
                        <?php if ($isCodePresent == 1) {
                            ?>
                            <th><?php echo $singleRecord[4] ?></th>
                        <?php
                        }
                        if ($isSectionPresent == 1) {
                            ?>
                            <th><?php echo $singleRecord[5] ?></th>
                        <?php } ?>
                    </tr>
            <?php } }?>

            </tbody>
        </table>

    </fieldset>
    <div class="form-group">
        <div class="roster-submit">

            <input type="button" onclick="saveStudentData()" value="Submit" class ="btn btn-primary">
            <a class="btn btn-primary back-btn" href="<?php echo AppUtility::getURLFromHome('roster/roster', 'import-student?cid='.$courseId)  ?>">Back</a>
        </div>
    </div>
</div>

<script type="application/javascript">
    $(document).ready(function(){
        createDataTable('display-user-table');
    });
    function saveStudentData() {
        var studentInformation = <?php echo json_encode($studentData ); ?>;
        var existingData = studentInformation['existingUsers'];
        var NewStudentData =  <?php echo json_encode($uniqueStudents ); ?>;
        if(existingData){
        var html = '<div><p>Existing students detail : </p></div><p>';
        html += '* Already existing in system' + '<br>';
        $.each(existingData, function (index, thread) {
            html += thread.userName + '<br>';
        });
        html += '<br>' + '* Already enrolled in course[Skip them]' + '<br>';
        $.each(existingData, function (index, thread) {
            html += thread.userName + '<br>';
        });

        $('<div id="dialog"></div>').appendTo('body').html(html).dialog({
            modal: true, title: 'Message', zIndex: 10000, autoOpen: true,
            width: 'auto', resizable: false,
            closeText: "hide",
            buttons: {
                "confirm": function () {
                    $('#searchText').val(null);
                    $(this).dialog('destroy').remove();
                    console.log(NewStudentData);
                    jQuerySubmit('save-csv-file-ajax', {studentData: NewStudentData}, 'saveCsvFileSuccess');
                    return true;
                },
                "Cancel": function () {
                    $(this).dialog('destroy').remove();
                    return false;
                }
            },
            close: function (event, ui) {
                $(this).remove();
            }
        });
    }else {
            jQuerySubmit('save-csv-file-ajax', {studentData: NewStudentData}, 'saveCsvFileSuccess');
        }

    }
    function saveCsvFileSuccess(response)
    {
        var courseId = $("#course-id").val();

        if(status == 0)
        {
             window.location = "student-roster?cid="+courseId;
        }
    }

</script>
