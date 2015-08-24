<?php
use app\components\AppUtility;
use app\components\AssessmentUtility;
$this->title = AppUtility::t("Blocks to change", false);
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="item-detail-header">
    <?php echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false), $course->name], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'instructor/instructor/index?cid='.$course->id]]); ?>
</div>

<div class = "title-container">
    <div class="row">
        <div class="pull-left page-heading">
            <div class="vertical-align title-page"><?php echo $this->title ?></div>
        </div>
    </div>
</div>

<div class="item-detail-content">
    <?php echo $this->render("../../instructor/instructor/_toolbarTeacher", ['course' => $course]); ?>
</div>
<div class="tab-content shadowBox">
<form id="qform" method="post" action="change-block?cid=<?php echo $course->id ?>">
    <div style="padding-top: 10px">
        <div class="col-lg-1"><?php AppUtility::t('Check')?></div>
        <div class="col-lg-4">
            <label class="col-lg-2 non-bold"><a href="#" onclick="return chkAllNone('qform','checked[]',true)"><?php echo _('All');?></a></label>
            <label class="col-lg-2 non-bold"><a href="#" onclick="return chkAllNone('qform','checked[]',false)"><?php echo _('None');?></a></label>
        </div>
    </div>
    <div class="clear-both"></div>

<ul class="nomark padding-left">
    <?php
    foreach ($existblocks as $pos=>$name) {
        echo '<li><input type="checkbox" name="checked[]" value="'.$existblockids[$pos].'"/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
        $n = substr_count($pos,"-")-1;
        for ($i=0;$i<$n;$i++) {
            echo '&nbsp;&nbsp;&nbsp;';
        }
        echo $name.'</li>';
    }
    ?>
</ul>
<table class="gb" id="opttable">
    <thead>
    <tr>
        <th><?php AppUtility::t('Change?')?></th>
        <th><?php AppUtility::t('Option')?></th>
        <th><?php AppUtility::t('Setting')?></th>
    </tr>
    </thead>
    <tbody>
    <tr>
        <td class="padding-left"><input type="checkbox" name="chgavail" class="chgbox"/></td>
        <td class="r col-lg-4"><?php AppUtility::t('Show')?></td>
        <td class="col-lg-6">
            <input type=radio name="avail" value="0"/><label class="padding-left non-bold"><?php AppUtility::t('Hide')?></label><br/>
            <input type=radio name="avail" value="1"/><label class="padding-left non-bold"><?php AppUtility::t('Show by Dates')?></label><br/>
            <input type=radio name="avail" value="2" checked="checked"/><label class="padding-left non-bold"><?php AppUtility::t('Show Always')?></label>
        </td>
    </tr>
    <tr>
        <td class="padding-left"><input type="checkbox" name="chgavailbeh" class="chgbox"/></td>
        <td class="r"><?php AppUtility::t('When available')?><br/><br/><br/>
            <?php AppUtility::t('When not available')?></td>
        <td class="col-lg-6">
            <select name="availbeh" class="form-control">
                <option value="O" selected="selected"><?php AppUtility::t('Show Expanded')?></option>
                <option value="C"><?php AppUtility::t('Show Collapsed')?></option>
                <option value="F"><?php AppUtility::t('Show as Folder')?></option>
                <option value="T"><?php AppUtility::t('Show as TreeReader')?></option>
            </select><br/>
            <select name="showhide" class="form-control">
                <option value="H" selected="selected"><?php AppUtility::t('Hide from Students')?></option>
                <option value="S"><?php AppUtility::t('Show Collapsed/as folder')?></option>
            </select>
        </td>
    </tr>
    <tr>
        <td class="padding-left"><input type="checkbox" name="chggrouplimit" class="chgbox"/></td>
        <td class="r"><?php AppUtility::t('Restrict access to students in section')?></td>
        <td class="col-lg-6">
            <?php AssessmentUtility::writeHtmlSelect('grouplimit',$page_sectionlistval,$page_sectionlistlabel,0); ?>
        </td>
    </tr>
    </tbody>
</table>
<div class=submit><input type=submit value="<?php echo _('Apply Changes')?>"></div><br/>
</form>
</div>

<script>
    $(function() {
        $('.chgbox').change(function() {
            $(this).parents('tr').toggleClass('odd');
        });
    })
</script>