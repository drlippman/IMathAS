<?php
use app\components\AppConstant;
use app\components\AppUtility;
$this->title = 'Content Stats';
?>
<div class="item-detail-header">
    <?php echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => ['Home', $course->name], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'instructor/instructor/index?cid=' . $course->id], 'page_title' => $this->title]); ?>
</div>
<div class="title-container">
    <div class="row">
        <div class="pull-left page-heading">
            <div class="vertical-align title-page"><?php echo $this->title ?></div>
        </div>
    </div>
</div>
<div class="tab-content shadowBox non-nav-tab-item add-link-padding">
<?php
if ($overWriteBody) {
    echo $body;
}  else {
    echo '<div id="headermoddataset" class="pagetitle">';
    echo "<div class='col-lg-12'><h2>Stats: $itemName</h2></div><BR class=form>\n";
    echo '</div>';

    $idents = array_keys($descrips);

    if (count($idents) == AppConstant::NUMERIC_ZERO) {
        echo '<p>No views on this item yet</p>';
    }

    foreach ($idents as $ident) {
        echo '<div class="col-lg-10"><h4>'.$descrips[$ident].'</h4></div>';
        echo '<table class="gb col-lg-10"><thead>';
        echo '<tr><th colspan="2">Viewed</th><th>Not Viewed</th></tr>';
        echo '<tr><th>Name</th><th style="padding-right:1em">Views</th>';
        echo '<th>Name</th></tr></thead><tbody>';

        $didview = array();
        $notview = array();

        foreach ($stus as $stu=>$name) {

            if (isset($data[$ident][$stu])) {
                $didview[] = array($name,$data[$ident][$stu]);
            } else {
                $notview[] = $name;
            }
        }
        $n = max(count($didview),count($notview));
        for ($i=0;$i<$n;$i++) {
            echo '<tr>';
            if (!isset($didview[$i])) {
                echo '<td></td><td style="border-right:1px solid"></td>';
            } else {
                echo '<td>'.$didview[$i][0].'</td>';
                echo '<td style="border-right:1px solid">'.$didview[$i][1].'</td>';
            }
            if (!isset($notview[$i])) {
                echo '<td></td>';
            } else {
                echo '<td>'.$notview[$i].'</td>';
            }
            echo '</tr>';
        }
        echo '</tbody></table>';
    }
}?>
    </div>