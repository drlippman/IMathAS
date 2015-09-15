<?php

use app\components\AppUtility;
use app\components\AppConstant;
$this->title = AppUtility::t('Print Test', false);
$this->params['breadcrumbs'][] = $this->title;
?>
<!--Get current time-->
<input type="hidden" class="" value="<?php echo $courseId?>">
<div class="item-detail-header">
    <?php echo $this->render("../../itemHeader/_indexWithLeftContent",['link_title'=>['Home',$course->name], 'link_url' => [AppUtility::getHomeURL().'site/index',AppUtility::getHomeURL().'instructor/instructor/index?cid='.$course->id] ,'page_title' => $this->title]); ?>
</div>
<!--Course name-->
<div class="title-container">
    <div class="row">
        <div class="col-sm-11">
            <div class=" col-sm-6" style="right: 30px;">
                <div class="vertical-align title-page">Print Test</div>
            </div>
            <div class="col-sm-6"">
                    <div class="col-sm-2 pull-right">
<!--                                    <a style="background-color: #008E71;border-color: #008E71;" title="Exit back to course page" href="/openmath/web/instructor/instructor/index?cid=2" class="btn btn-primary  page-settings"><img class="small-icon" src="/openmath/web/img/done.png">&nbsp;Done</a>-->
                    </div>
            </div>
        </div>
    </div>
</div>
<div class="tab-content shadowBox print-test margin-top-fourty">
    <?php
if (isset($params['versions'])) {
//    $placeinhead = "<link rel=\"stylesheet\" type=\"text/css\" href=\"$imasroot/assessment/print.css?v=100213\"/>\n";
}

$nologo = true;

if ($overwriteBody==1) {
    echo $body;
} if (!isset($params['versions'])) {

    echo '<div class="cpmid"><a href="print-test?cid='.$courseId.'&aid='.$assessmentId.'">Generate for in-browser printing</a>';
    if (isset($CFG['GEN']['pandocserver'])) {
        echo ' | <a href="print-layout-word?cid='.$courseId.'&amp;aid='.$assessmentId.'">Generate for Word</a>';
    }
    echo '</div>';

    echo "<h2>Copy-and-Paste Print Version</h2>";

    echo '<p>This page will help you create a copy of this assessment that you should be able to cut and ';
    echo 'paste into Word or another word processor and adjust layout for printing</p>';

    echo "<form method=post action=\"print-layout-bare?cid=$courseId&aid=$assessmentId\">\n";
    echo '<span class="form">Number of different versions to generate:</span><span class="formright"><input type=text name=versions value="1" size="3"></span><br class="form"/>';
    echo '<span class="form">Format?</span><span class="formright"><input type="radio" name="format" value="trad" checked="checked" /> Form A: 1 2 3, Form B: 1 2 3<br/><input type="radio" name="format" value="inter"/> 1a 1b 2a 2b</span><br class="form"/>';
    echo '<span class="form">Generate answer keys?</span><span class="formright"> <input type=radio name=keys value=1 checked=1>Yes <input type=radio name=keys value=0>No</span><br class="form"/>';
    echo '<span class="form">Question separator:</span><span class="formright"><input type=text name="qsep" value="" /></span><br class="form"/>';
    echo '<span class="form">Version separator:</span><span class="formright"><input type=text name="vsep" value="+++++++++++++++" /> </span><br class="form"/>';
    echo '<span class="form">Math display:</span><span class="formright"><input type="radio" name="mathdisp" value="img" checked="checked" /> Images <input type="radio" name="mathdisp" value="text"/> Text <input type="radio" name="mathdisp" value="tex"/> TeX <input type="radio" name="mathdisp" value="textandimg"/> Images, then again in text</span><br class="form"/>';
    echo '<span class="form">Include question numbers and point values:</span><span class="formright"><input type="checkbox" name="showqn" checked="checked" /> </span><br class="form"/>';
    echo '<span class="form">Hide text entry lines?</span><span class="formright"><input type=checkbox name=hidetxtboxes ></span><br class="form"/>';

    echo '<div class="submit"><input type=submit value="Continue"></div></form>';

} else {





    ?>
    <style type="text/css">
        body {
            padding: 0px;
            margin: 0px;
        }
        form {
            padding: 0px;
            margin: 0px;
        }
        div.maintest {
            position: absolute;
            top: 0px;
            left: 0px;
        }
        .pageb {
            clear: both;
            padding: 0px;
            margin: 0px;
            page-break-after: always;
            border-bottom: 1px dashed #aaa;
        }
        div.mainbody {
            margin: 0px;
            padding: 0px;
        }
    </style>

    <?php




    //add interlace output
    //add prettyprint along with text-based output option


    for ($pt=0;$pt<$printtwice;$pt++) {
        if ($pt==1) {
            $sessionData['mathdisp'] = 0;
            echo $params['vsep'].'<br/>';;

        }

        if ($params['format']=='trad') {
            for ($j=0; $j<$copies; $j++) {
                if ($j>0) { echo $params['vsep'].'<br/>';}

                $headerleft = '';
                $headerleft .= $line['name'];
                if ($copies>1) {
                    $headerleft .= ' - Form ' . ($j+1);
                }
                if ((isset($params['iname']) || isset($params['cname'])) && isset($params['aname'])) {
                    $headerleft .= "<br/>";
                }
                $headerright = '';
                echo "<div class=q>\n";
                echo "<div class=hdrm>\n";

                echo "<div id=headerleft>$headerleft</div><div id=headerright>$headerright</div>\n";
                echo "<div id=intro>{$line['intro']}</div>\n";
                echo "</div>\n";
                echo "</div>\n";


                for ($i=0; $i<$numq; $i++) {
                    if ($i>0) { echo $params['qsep'];}
                    $sa[$j][$i] = AppUtility::printq($i,$qn[$questions[$i]],$seeds[$j][$i],$points[$questions[$i]],isset($params['showqn']));
                }

            }

            if ($params['keys']>0) { //print answer keys
                for ($j=0; $j<$copies; $j++) {
                    echo $params['vsep'].'<br/>';
                    echo '<b>Key - Form ' . ($j+1) . "</b>\n";
                    echo "<ol>\n";
                    for ($i=0; $i<$numq; $i++) {
                        echo '<li>';
                        if (is_array($sa[$j][$i])) {
                            echo printfilter(filter(implode(' ~ ',$sa[$j][$i])));
                        } else {
                            echo printfilter(filter($sa[$j][$i]));
                        }
                        echo "</li>\n";
                    }
                    echo "</ol>\n";
                    //if ($params['keys']==2) {
                    //	echo "<p class=pageb>&nbsp;</p>\n";
                    //}
                }
            }
        } else if ($params['format']=='inter') {

            $headerleft = '';
            $headerleft .= $line['name'];
            if ((isset($params['iname']) || isset($params['cname'])) && isset($params['aname'])) {
                $headerleft .= "<br/>";
            }
            $headerright = '';
            echo "<div class=q>\n";
            echo "<div class=hdrm>\n";

            echo "<div id=headerleft>$headerleft</div><div id=headerright>$headerright</div>\n";
            echo "<div id=intro>{$line['intro']}</div>\n";
            echo "</div>\n";
            echo "</div>\n";
            for ($i=0; $i<$numq; $i++) {
                if ($i>0) { echo $params['qsep'];}
                for ($j=0; $j<$copies;$j++) {
                    if ($j>0) { echo $params['qsep'];}
                    $sa[] = AppUtility::printq($i,$qn[$questions[$i]],$seeds[$j][$i],$points[$questions[$i]],isset($params['showqn']));
                }
            }
            if ($params['keys']>0) { //print answer keys
                echo $params['vsep'].'<br/>';
                echo "<b>Key</b>\n";
                echo "<ol>\n";
                for ($i=0; $i<count($sa); $i++) {
                    echo '<li>';
                    if (is_array($sa[$i])) {
                        echo printfilter(filter(implode(' ~ ',$sa[$i])));
                    } else {
                        echo printfilter(filter($sa[$i]));
                    }
                    echo "</li>\n";
                }
                echo "</ol>\n";
            }
        }
    }
    $licurl = AppUtility::getURLFromHome('question','question/show-license?id='.implode('-',$qn));
    echo '<hr/><p style="font-size:70%">License info at: <a href="'.$licurl.'">'.$licurl.'</a></p>';

    echo "<div class=cbutn><a href=".AppUtility::getURLFromHome('instructor','instructor/index?cid='.$courseId).">Return to course page</a></div>\n";



}

?>
</div>