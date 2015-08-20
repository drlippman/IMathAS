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
Check: <a href="#" onclick="return chkAllNone('qform','checked[]',true)"><?php echo _('All');?></a>
<a href="#" onclick="return chkAllNone('qform','checked[]',false)"><?php echo _('None');?></a>
<ul class="nomark">
    <?php
    foreach ($existblocks as $pos=>$name) {
        echo '<li><input type="checkbox" name="checked[]" value="'.$existblockids[$pos].'"/>';
        $n = substr_count($pos,"-")-1;
        for ($i=0;$i<$n;$i++) {
            echo '&nbsp;&nbsp;';
        }
        echo $name.'</li>';
    }
    ?>
</ul>
<table class="gb" id="opttable">
    <thead>
    <tr><th>Change?</th><th>Option</th><th>Setting</th></tr>
    </thead>
    <tbody>
    <tr>
        <td><input type="checkbox" name="chgavail" class="chgbox"/></td>
        <td class="r">Show:</td>
        <td>
            <input type=radio name="avail" value="0"/>Hide<br/>
            <input type=radio name="avail" value="1"/>Show by Dates<br/>
            <input type=radio name="avail" value="2" checked="checked"/>Show Always
        </td>
    </tr>
    <tr>
        <td><input type="checkbox" name="chgavailbeh" class="chgbox"/></td>
        <td class="r">When available:<br/>
            When not available:</td>
        <td>
            <select name="availbeh">
                <option value="O" selected="selected">Show Expanded</option>
                <option value="C">Show Collapsed</option>
                <option value="F">Show as Folder</option>
                <option value="T">Show as TreeReader</option>
            </select><br/>
            <select name="showhide">
                <option value="H" selected="selected">Hide from Students</option>
                <option value="S">Show Collapsed/as folder</option>
            </select>
        </td>
    </tr>
    </tbody>
</table>
<div class=submit><input type=submit value="<?php echo _('Apply Changes')?>"></div>
</form>
</div>

<script>
    $(function() {
        $('.chgbox').change(function() {
            $(this).parents('tr').toggleClass('odd');
        });
    })
</script>