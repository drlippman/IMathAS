<?php
use app\components\AppUtility;
$this->title = AppUtility::t('Manage Tutors', false);
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="item-detail-header">
    <?php echo $this->render("../../itemHeader/_indexWithLeftContent",['link_title'=>[AppUtility::t('Home', false),$course->name,AppUtility::t('Roster',false)], 'link_url' => [AppUtility::getHomeURL().'site/index',AppUtility::getHomeURL().'course/course/course?cid='.$course->id, AppUtility::getHomeURL().'roster/roster/student-roster?cid='.$course->id]]); ?>
</div>
<div class = "title-container">
    <div class="row">
        <div class="pull-left page-heading">
            <div class="vertical-align title-page"><?php echo $this->title ?></div>
        </div>
    </div>
</div>
<div class="item-detail-content">
    <?php echo $this->render("../../course/course/_toolbarTeacher", ['course' => $course, 'section' => 'roster']);?>
</div>
<div class="tab-content shadowBox"">
<?php echo $this->render("_toolbarRoster", ['course' => $course]);?>
<div class="col-md-12 col-sm-12 padding-left-two-em padding-right-two-em padding-bottom-two-em">

    <input type="hidden" class="courseId" value="<?php echo $courseId ?>">
    <?php $sectionArray = $section?>
    <p><div id="user-div"></div></p>
    <div>

        <table class='padding-left-zero list display-tutor-table table table-bordered table-striped table-hover data-table' bPaginate = 'false'>
            <thead>
            <th><?php AppUtility::t('Tutor Name')?></th>
            <th><?php AppUtility::t('Limit to Section')?></th>
            <th><?php AppUtility::t('Remove')?>?  <?php AppUtility::t('Check')?> <a id="check-all" class="check-all" href="#"><?php AppUtility::t('All')?></a> /
                <a id="check-none" class="uncheck-all" href="#"><?php AppUtility::t('None')?></a></th>
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
                    echo "</select><td>
                                               <div class='checkbox override-hidden'>
                    <label>
                        <input type='checkbox' name='tutor-check' value='{$value['id']}' class = 'master'>
                        <span class='cr'><i class='cr-icon fa fa-check'></i></span>
                    </label>
                </div>
</td></tr>";
                }
                ?>
            </div>
            </tbody>
        </table>
    </div><br><br>

    <p><b><?php AppUtility::t('Add new tutors.')?></b> <?php AppUtility::t('Provide a list of usernames below, separated by commas, to add as tutors.')?></p>
    <br>
    <textarea class="max-width-hundread" name = "newTutors" id = "tutor-text" rows = "3" cols = "60"></textarea>
    <br><br>
    <a class = "btn btn-primary" id = "update-button"><?php AppUtility::t('Update')?></a>
<span class="padding-left-one-em"><a class="btn btn-primary back-btn-width" href="<?php echo AppUtility::getURLFromHome('roster/roster', 'student-roster?cid='.$course->id)  ?>">
        <?php AppUtility::t('Back')?></a></span>
</div>
</div>