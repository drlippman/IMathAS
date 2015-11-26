<?php
use app\components\AppUtility;
$this->title = $pageTitle;
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="item-detail-header">
        <?php echo $this->render("../../itemHeader/_indexWithLeftContent",['link_title'=>['Home',$course->name], 'link_url' => [AppUtility::getHomeURL().'site/index',AppUtility::getHomeURL().'course/course/course?cid='.$course->id] ,'page_title' => $this->title]); ?>
</div>
    <div class="title-container">
        <div class="row">
            <div class="col-sm-11">
                <div class=" col-sm-6" style="right: 30px;">
                    <div class="vertical-align title-page"><?php echo $this->title;?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="tab-content shadowBox print-test margin-top-fourty">
<?php
if ($overwriteBody == 1) {
    echo $body;
} if (!isset($_REQUEST['versions'])) {
    echo '<div class="print-test-header"><a class="margin-left-thirty" href="print-test?cid='.$cid.'&amp;aid='.$aid.'">Generate for in-browser printing </a>';
    echo '| <a href="print-layout-bare?cid='.$cid.'&amp;aid='.$aid.'"> Generate for cut-and-paste</a></div>';
    echo "<h2>"._('Generate Word Version')."</h2>";

    echo '<div class="col-md-12 col-sm-12">'; AppUtility::t('This page will help you create a copy of this assessment as a Word 2007+ file that you can then edit for printing.'); echo'</div><br class="form"/><br class="form"/>';

    echo "<form method=\"post\" action=\"print-layout-word?cid=$cid&aid=$aid\" class=\"nolimit\">\n";
    echo '<div class="col-md-3 col-sm-3">'; AppUtility::t('Number of different versions to generate'); echo'</div>
            <div class="col-md-6 col-sm-6"><input type=text class="form-control-1" name=versions value="1" size="3"></div><br class="form"/><br class="form"/>';
    echo '<div class="col-md-3 col-sm-3">'; AppUtility::t('Format?'); echo'</div>
            <div class="col-md-6 col-sm-6"><input type="radio" name="format" value="trad" checked="checked" /><span class="padding-left-five">'; AppUtility::t('Multiple forms of the whole assessment - Form A: 1 2 3, Form B: 1 2 3'); echo'</span><br/><input type="radio" name="format" value="inter"/><span class="padding-left-five">'; AppUtility::t('Multiple forms grouped by question - 1a 1b 2a 2b'); echo'</span></div><br class="form"/><br class="form"/>';
    echo '<div class="col-md-3 col-sm-6">'; AppUtility::t('Generate answer keys?'); echo'</div>
            <div class="col-md-6 col-sm-6"> <input type=radio name=keys value=1 checked=1><span class="padding-left-five">'; AppUtility::t('Yes'); echo'</span><span class="padding-left-five"><input type=radio name=keys value=0></span><span class="padding-left-five">'; AppUtility::t('No'); echo'</span></div><br class="form"/><br class="form"/>';
    echo '<div class="col-md-3 col-sm-3">'; AppUtility::t('Question separator'); echo'</div>
            <div class="col-md-6 col-sm-6"><input type=text class="form-control-1" name="qsep" value="" /></div><br class="form"/><br class="form"/>';
    echo '<div class="col-md-3 col-sm-3">'; AppUtility::t('Version separator');echo'</div>
            <div class="col-md-6 col-sm-6"><input type=text class="form-control" name="vsep" value="+++++++++++++++" /> </div><br class="form"/><br class="form"/>';
    echo '<div class="col-md-3 col-sm-3">'; AppUtility::t('Include question numbers and point values'); echo'</div>
            <div class="col-md-6 col-sm-6"><input type="checkbox" name="showqn" checked="checked" /> </div><br class="form"/><br class="form"/>';
    echo '<div class="col-md-3 col-sm-3">'; AppUtility::t('Hide text entry lines?'); echo'</div>
            <div class="col-md-6 col-sm-6"><input type=checkbox name=hidetxtboxes checked="checked" ></div><br class="form"/><br class="form"/>';

    echo '<div class="col-md-12 col-sm-12">'; AppUtility::t('NOTE: In some versions of Word, variables in equations may appear incorrectly at first.  To fix this, ');
     AppUtility::t('select everything (Control-A), then under the Equation Tools menu, click Linear then Professional.'); echo'</div><br class="form"/><br class="form"/>';

    echo '<div class="submit"><input type="submit" value="Download"/></div></form>';

} else {
    $curdir = rtrim(dirname(__FILE__), '/\\');

    echo '<div class="print-test-header"><a class="margin-left-thirty" href="print-test?cid='.$cid.'&amp;aid='.$aid.'">Generate for in-browser printing</a> | <a href="print-layout-bare?cid='.$cid.'&amp;aid='.$aid.'">Generate for cut-and-paste</a></div>';

    echo "<h2>"._('Generate Word Version')."</h2>";
    echo '<div class="col-md-12 col-sm-12">'._('Assessment is prepared, and ready for conversion').'.</div>';
    echo '<div class="col-md-12 col-sm-12">'; AppUtility::t('NOTE: In some versions of Word, variables in equations may appear incorrectly at first.  To fix this, ');
     AppUtility::t('select everything (Control-A), then under the Equation Tools menu, click Linear then Professional.'); echo'</div>';
    echo '<form id="theform" method="post" action="http://'.$CFG['GEN']['pandocserver'].'/html2docx.php">';
    echo '<input type="submit" value="'._("Convert to Word").'"/> ';
    echo '<a href="print-layout-word?cid='.$cid.'&amp;aid='.$aid.'">'._('Change print settings').'</a>';
    echo '<textarea name="html" style="visibility:hidden">'.htmlentities($out).'</textarea>';
    echo '</form>';
}