<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use app\components\AppUtility;
use app\components\AppConstant;

$this->title = 'Show imported students details';
?>
<div class="item-detail-header" xmlns="http://www.w3.org/1999/html">
    <?php echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => ['Home', $course->name,'Roster','Import student'], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'course/course/course?cid=' . $course->id,AppUtility::getHomeURL() . 'roster/roster/student-roster?cid=' . $course->id,AppUtility::getHomeURL() . 'roster/roster/import-student?cid=' . $course->id], 'page_title' => $this->title]); ?>
</div>
<div class="title-container">
    <div class="row">
        <div class="pull-left ">
            <div class="vertical-align title-page"><?php echo $this->title ?></div>
        </div>
    </div>
</div>
<input type="hidden" id="course-id" value="<?php echo $course->id?>" xmlns="http://www.w3.org/1999/html">
<div class="tab-content shadowBox non-nav-tab-item   col-sm-12 col-md-12">
    <div class="import-student col-sm-12 col-md-12">
    <h3><strong>New students Record</strong> </h3>
            <table id="user-table displayUser"  class="display-user-table table table-bordered table-striped table-hover data-table" bPaginate="false">
                <thead>
                <tr>
                    <th >Username</th>
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
                    foreach ($uniqueStudents as $singleRecord)
                    {
                        ?>
                        <tr>
                            <td class="word-break-break-all"><?php echo $singleRecord[0] ?></td>
                            <td class="word-break-break-all"><?php echo $singleRecord[1] ?></td>
                            <td class="word-break-break-all"><?php echo $singleRecord[2] ?></td>
                            <td class="word-break-break-all"><?php echo $singleRecord[3] ?></td>
                            <?php if ($isCodePresent == 1) {
                                ?>
                                <th class="word-break-break-all"><?php echo $singleRecord[4] ?></th>
                            <?php
                            }
                            if ($isSectionPresent == 1) {
                                ?>
                                <th class="word-break-break-all"><?php echo $singleRecord[5] ?></th>
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
                if (isset($existingStudent)) {
                    foreach ($existingStudent as $singleRecord) {
                        ?>
                        <tr>
                            <td class="word-break-break-all"><?php echo $singleRecord['userName'] ?></td>
                            <td class="word-break-break-all"><?php echo $singleRecord['firstName'] ?></td>
                            <td class="word-break-break-all"><?php echo $singleRecord['lastName'] ?></td>
                            <td class="word-break-break-all"><?php echo $singleRecord['email'] ?></td>
                            <?php if ($isCodePresent == 1) {
                                ?>
                                <th class="word-break-break-all"><?php echo $singleRecord[4] ?></th>
                            <?php
                            }
                            if ($isSectionPresent == 1) {
                                ?>
                                <th class="word-break-break-all"><?php echo $singleRecord[5] ?></th>
                            <?php } ?>
                        </tr>
                <?php } }?>

                </tbody>
            </table>
        <div class="form-group col-sm-12 col-md-12 padding-bottom-one-em padding-top-one-em">
            <div class="roster-submit col-sm-12 col-md-12 padding-left-zero">
                <span class="padding-right-one-em"><input type="button" onclick="saveStudentData()" value="Accept and Enroll" class ="btn btn-primary"></span>
                <a class="btn btn-primary back-btn" href="<?php echo AppUtility::getURLFromHome('roster/roster', 'import-student?cid='.$courseId)  ?>"><i class="fa fa-share header-right-btn"></i>Back</a>
            </div>
        </div>
      </div>
</div>
<script type="application/javascript">
    $(document).ready(function(){
        createDataTable('display-user-table');
    });
    function saveStudentData() {
        var existingData = <?php echo json_encode($existingStudent ); ?>;
        var NewStudentData =  <?php echo json_encode($uniqueStudents ); ?>;
        var courseId = $("#course-id").val();
        if(existingData)
        {
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
                    jQuerySubmit('save-csv-file-ajax', {studentData: NewStudentData,courseId:courseId}, 'saveCsvFileSuccess');
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
            jQuerySubmit('save-csv-file-ajax', {studentData: NewStudentData,courseId:courseId}, 'saveCsvFileSuccess');
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
