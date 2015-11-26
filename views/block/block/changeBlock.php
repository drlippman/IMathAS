<?php
use app\components\AppUtility;
use app\components\AssessmentUtility;
$this->title = AppUtility::t("Blocks to change", false);
?>
<div class="item-detail-header">
    <?php echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false), $course->name], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'course/course/course?cid='.$course->id]]); ?>
</div>
<form id="qform" method="post" action="change-block?cid=<?php echo $course->id ?>">
<div class = "title-container">
    <div class="row">
        <div class="pull-left page-heading">
            <div class="vertical-align title-page"><?php echo $this->title ?></div>
        </div>
    </div>
</div>

 <div class="tab-content shadowBox">

    <div class="padding-top-one-em col-sm-12 col-md-12">
        <span class=" "><?php AppUtility::t('Check:')?></span>&nbsp;
            <label class=" non-bold"><a href="#" onclick="return chkAllNone('qform','checked[]',true)"><?php echo _('All');?></a></label>
            <label class=" non-bold"><a href="#" onclick="return chkAllNone('qform','checked[]',false)"><?php echo _('None');?></a></label>
    </div>
    <div class="clear-both"></div>

<ul class="list-style-type padding-left-two-em">
    <?php
    foreach ($existblocks as $pos=>$name)
    {
        echo '<li><input type="checkbox" name="checked[]" value="'.$existblockids[$pos].'"/>';
        $n = substr_count($pos,"-")-1;
        for ($i=0;$i<$n;$i++) {
            echo '&nbsp;&nbsp;&nbsp;';
        } ?>
        <span class="padding-left-one-em"><?php echo $name?></span>
        <?php echo  '</li>';
    }
    ?>
</ul>
    <div class="col-sm-12 col-md-12">
        <p><?php AppUtility::t('With selected, make changes below');?>
        <h3><?php AppUtility::t('Block Options')?></h3>

        <table class="table table-bordered table-striped table-hover data-table " id="opttable">
    <thead>
    <tr>
        <th class="col-sm-1 col-md-1 padding-left-zero"><?php AppUtility::t('Change?')?></th>
        <th class="col-sm-4 col-md-3"><?php AppUtility::t('Option')?></th>
        <th class="col-sm-7 col-md-7"><?php AppUtility::t('Setting')?></th>
    </tr>
    </thead>
    <tbody>
    <tr>
        <td class="padding-left col-sm-1 col-md-1"><input type="checkbox" name="chgavail" class="chgbox"/></td>
        <td class="col-sm-4 col-md-3 text-align-center"><?php AppUtility::t('Show')?></td>
        <td class=" col-sm-7 col-md-7">
            <input type=radio name="avail" value="0"/><label class="padding-left non-bold"><?php AppUtility::t('Hide')?></label><br/>
            <input type=radio name="avail" value="1"/><label class="padding-left non-bold"><?php AppUtility::t('Show by Dates')?></label><br/>
            <input type=radio name="avail" value="2" checked="checked"/><label class="padding-left non-bold"><?php AppUtility::t('Show Always')?></label>
        </td>
    </tr>
    <tr>
        <td class="padding-left col-sm-1 col-md-1"><input type="checkbox" name="chgavailbeh" class="chgbox"/></td>
        <td class="col-sm-4 col-md-3 text-align-center"><?php AppUtility::t('When available')?><br/><br/><br/>
            <?php AppUtility::t('When not available')?></td>
        <td class="col-sm-7 col-md-7">
            <div class="col-md-5 col-sm-9 padding-left-zero">
            <select name="availbeh" class="form-control  ">
                <option value="O" selected="selected"><?php AppUtility::t('Show Expanded')?></option>
                <option value="C"><?php AppUtility::t('Show Collapsed')?></option>
                <option value="F"><?php AppUtility::t('Show as Folder')?></option>
                <option value="T"><?php AppUtility::t('Show as TreeReader')?></option>
            </select><br/>

            <select name="showhide" class="form-control  ">
                <option value="H" selected="selected"><?php AppUtility::t('Hide from Students')?></option>
                <option value="S"><?php AppUtility::t('Show Collapsed/as folder')?></option>
            </select>
                </div>
        </td>
    </tr>
    <tr>
        <td class="padding-left col-sm-1 col-md-1"><input type="checkbox" name="chggrouplimit" class="chgbox"/></td>
        <td class="col-sm-5 col-md-3 text-align-center"><?php AppUtility::t('Restrict access to students in section')?></td>
        <td class="col-sm-6 col-md-7">
            <div class="col-md-5 col-sm-9 padding-left-zero">
            <?php AssessmentUtility::writeHtmlSelect('grouplimit',$page_sectionlistval,$page_sectionlistlabel,0); ?>
            </div>
        </td>
    </tr>
    </tbody>
</table>
        </div>
    <div class="header-btn col-sm-6 padding-top-ten padding-bottom-thirty">
        <button class="btn btn-primary page-settings" type="submit" value="Submit">
            <i class="fa fa-share header-right-btn"></i><?php echo _('Apply Changes'); ?></button>
    </div>
</form>
</div>

<script>
    $(function() {
        $('.chgbox').change(function() {
            $(this).parents('tr').toggleClass('odd');
        });
    })
</script>