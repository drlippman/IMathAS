<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use app\components\AppUtility;
use app\components\AppConstant;

//use app\widgets\FileInput;
$this->title = 'Import Students';
//$this->params['breadcrumbs'][] = ['label' => 'Course', 'url' => ['/instructor/instructor/index?cid='.$_GET['cid']]];
//$this->params['breadcrumbs'][] = ['label' => 'Roster', 'url' => ['/roster/roster/student-roster?cid='.$_GET['cid']]];
$this->params['breadcrumbs'][] = $this->title;

?>
<input type="hidden" id="course-id" value="<?php echo $courseId ?>" >
<div class="import-student">
    <fieldset>


        <table id="user-table displayUser"  class="display-user-table">
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
            if (isset($studentData['newUsers'])) {
                $newStudentData = $studentData['newUsers'];
                foreach ($newStudentData as $singleRecord) {
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
            <?php } ?>
            <?php
            if (isset($studentData['existingUsers'])) {
                $existStudentData = $studentData['existingUsers'];
                foreach ($existStudentData as $singleRecord) {
                    ?>
                    <tr>
                        <?php
                        foreach ($singleRecord as $key => $data) {
                            ?>
                            <td><?php echo $singleRecord[$key] ?></td>
                        <?php } ?>
                    </tr>
                <?php } ?>
            <?php } ?>
            </tbody>
        </table>

    </fieldset>
    <div class="form-group">
        <div class="roster-submit">

            <input type="button" onclick="saveStudentData()" value="Submit" class ="btn btn-primary">
        </div>
    </div>
</div>

<script>
    function saveStudentData() {
        var studentInformation= <?php echo json_encode($studentData ); ?>;
        var existingData = studentInformation['existingUsers'];
        var NewStudentData = studentInformation['newUsers'];



        var html = '<div><p>Existing students detail : </p></div><p>';
        html +='* Already existing in system'+  '<br>';
        $.each(existingData, function (index, thread) {
            html += thread.userName+'<br>';
        });
        html += '<br>'+'* Already enrolled in course[Skip them]'+  '<br>';
        $.each(existingData, function (index, thread) {
            html +=  thread.userName+'<br>';
        });

        $('<div id="dialog"></div>').appendTo('body').html(html).dialog({
            modal: true, title: 'Message', zIndex: 10000, autoOpen: true,
            width: 'auto', resizable: false,
            closeText: "hide",
            buttons: {
                "confirm": function () {
                    $('#searchText').val(null);
                    $(this).dialog('destroy').remove();
                    jQuerySubmit('save-csv-file-ajax', {studentData:NewStudentData}, 'saveCsvFileSuccess');
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

    }
    function saveCsvFileSuccess(response)
    {
//        var startDate = $("#datepicker-id input").val();
        var courseId = $("#course-id").val();

        if(status == 0)
        {
             window.location = "student-roster?cid="+courseId;
        }
    }

</script>
