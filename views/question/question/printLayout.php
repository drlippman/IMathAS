<?php
use app\components\AppUtility;
use app\components\AppConstant;
use app\models\QuestionSet;
use app\models\QImages;
use app\components\interpretUtility;

$nologo = true;
$loadgraphfilter = true;

if ($overwriteBody==1) {
    echo $body;
} else {
    ?>
    <div class="tab-content shadowBox">
    <style type="text/css">
        div.a,div.b {
            position: absolute;
            left: 0px;
            border: 1px solid;
            width: <?php echo $pwss ?>in;
            height: <?php echo $phs ?>in;
        }
        div.a {
            border: 3px double #33f;
        }
        div.b {
            border: 3px double #0c0;
        }

        <?php
            if ($isfinal) {
                $heights = explode(',',$params['heights']);
                for ($i=0;$i<count($heights);$i++) {
                    echo "div.trq$i {float: left; width: {$pw}in; height: {$heights[$i]}in; padding: 0px; overflow: hidden;}\n";
                }
                echo "div.hdrm {width: {$pw}in; padding: 0px; overflow: hidden;}\n";
            } else {
                $pt = 0;
                for ($i=0;$i<ceil($numq/3)+1;$i++) {
                    echo "div#pg$i { top: {$pt}in;}\n";
                    $pt+=$ph;
                    if ($params['browser']==1) {$pt -= .4;}
                }
            }
            if (isset($params['hidetxtboxes'])) {
                echo "input.text { display: none; }\n";
            }
        ?>

        div.floatl {
            float: left;
        }
        div.qnum {
            float: left;
            text-align: right;
            padding-right: 5px;
        }
        div#headerleft {
            float: left;
        }
        div#headerright {
            float: right;
            text-align: right;
        }
        div#intro {
            clear: both;
            padding-top: 5px;
            padding-bottom: 5px;
        }
        div.q {
            clear: both;
            padding: 0px;
            margin: 0px;
        }
        div.m {
            float: left;
            width: <?php echo $pws ?>in;
            border-bottom: 1px dashed #aaa;
            padding: 0px;
            overflow: hidden;
        }

        div.cbutn {
            float: left;
            padding-left: 5px;
        }
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
    <style type="text/css" media="print">

        div.a,div.b {
            display: none;
        }
        div.m {
            width: <?php echo $pw ?>in;
            border: 0px;
        }
        div.cbutn {
            display: none;
        }
        .pageb {
            border: 0px;
        }

    </style>

    <?php
    if (!$isfinal) {
        for ($i=0;$i<ceil($numq/3)+1;$i++) { //print page layout divs
            echo "<div id=\"pg$i\" ";
            if ($i%2==0) {
                echo "class=a";
            } else {
                echo "class=b";
            }
            echo ">&nbsp;</div>\n";
        }
    }

    //echo "<div class=maintest>\n";
    echo "<form method=post action=\"print-test?cid=$courseId&aid=$assessmentId\" onSubmit=\"return packheights()\">\n";

    if ($isfinal) {
        $copies = $params['versions'];
    } else {
        $copies = 1;
    }
    for ($j=0; $j<$copies; $j++) {
        $seeds = array();
        if ($line['shuffle']&2) {  //set rand seeds
            $seeds = array_fill(0,count($questions),rand(1,9999));
        } else {
            for ($i = 0; $i<count($questions);$i++) {
                $seeds[] = rand(1,9999);
            }
        }

        $headerleft = '';
        if (isset($params['aname'])) {
            $headerleft .= $line['name'];
        }
        if ($copies>1) {
            $headerleft .= ' - Form ' . ($j+1);
        }
        if ((isset($params['iname']) || isset($params['cname'])) && isset($params['aname'])) {
            $headerleft .= "<br/>";
        }
        if (isset($params['cname'])) {
            $headerleft .= $course->name;
            if (isset($params['iname'])) { $headerleft .= ' - ';}
        }
        if (isset($params['iname'])) {
            $headerleft .= $user['LastName'];
        }
        $headerright = '';
        if (isset($params['sname'])) {
            $headerright .= 'Name ____________________________';
            if (isset($params['otherheader'])) {
                $headerright .= '<br/>';
            }
        }
        if (isset($params['otherheader'])) {
            $headerright .= $params['otherheadertext'] . '____________________________';
        }

        echo "<div class=q>\n";
        if ($isfinal) {
            echo "<div class=hdrm>\n";
        } else {
            echo "<div class=m>\n";
        }
        echo "<div id=headerleft>$headerleft</div><div id=headerright>$headerright</div>\n";
        echo "<div id=intro>{$line['intro']}</div>\n";
        echo "</div>\n";
        if (!$isfinal) {
            echo "<div class=cbutn><a href=\"print-test?cid=$courseId&aid=$assessmentId\">Cancel</a></div>\n";
        }
        echo "</div>\n";


        for ($i=0; $i<$numq; $i++) {
            $sa[$j][$i] = printq($i,$qn[$questions[$i]],$seeds[$i],$points[$questions[$i]],$isfinal);
        }
        if ($isfinal) {
            echo "<p class=pageb>&nbsp;</p>\n";
        }
    }
    if (!$isfinal) {
        ?>

        <script type="text/javascript">
            var heights = new Array();
            for (var i=0; i<<?php echo $numq ?>; i++) {
                heights[i] = 2.5;
                document.getElementById("trq"+i).style.height = "2.5in";
            }
            function incspace(id,sp) {
                if (heights[id]+sp>.5) {
                    heights[id] += sp;
                    document.getElementById("trq"+id).style.height = heights[id]+"in";
                }

            }
            function packheights() {
                document.getElementById("heights").value = heights.join(",");
                return true;
            }
        </script>

        <?php
        echo "<input type=hidden id=heights name=heights value=\"\">\n";
        echo "<input type=hidden name=pw value=\"$pw\">\n";
        echo "<input type=hidden name=ph value=\"$ph\">\n";
        if (isset($params['points'])) {
            echo "<input type=hidden name=points value=1>\n";
        }
        if (isset($params['aname'])) {
            echo "<input type=hidden name=aname value=1>\n";
        }
        if (isset($params['iname'])) {
            echo "<input type=hidden name=iname value=1>\n";
        }
        if (isset($params['cname'])) {
            echo "<input type=hidden name=cname value=1>\n";
        }
        if (isset($params['sname'])) {
            echo "<input type=hidden name=sname value=1>\n";
        }
        if (isset($params['hidetxtboxes'])) {
            echo "<input type=hidden name=hidetxtboxes value=1>\n";
        }
        if (isset($params['otherheader'])) {
            echo "<input type=hidden name=otherheader value=1>\n";
            echo "<input type=hidden name=otherheadertext value=\"{$params['otherheadertext']}\">\n";
        }
        echo "<div class=q><div class=m>&nbsp;</div><div class=cbutn><input type=submit value=\"Continue\"></div></div>\n";
    } else if ($params['keys']>0) { //print answer keys
        for ($j=0; $j<$copies; $j++) {
            echo '<b>Key - Form ' . ($j+1) . "</b>\n";
            echo "<ol>\n";
            for ($i=0; $i<$numq; $i++) {
                echo '<li>';
                if (is_array($sa[$j][$i])) {
                    echo filter(implode(' ~ ',$sa[$j][$i]));
                } else {
                    echo filter($sa[$j][$i]);
                }
                echo "</li>\n";
            }
            echo "</ol>\n";
            if ($params['keys']==2) {
                echo "<p class=pageb>&nbsp;</p>\n";
            }
        }
    }
    if ($isfinal) {
        $licurl = $urlmode.$_SERVER['HTTP_HOST'].$imasroot.'/course/showlicense.php?id='.implode('-',$qn);
        echo '<hr/><p style="font-size:70%">License info at: <a href="'.$licurl.'">'.$licurl.'</a></p>';
        echo "<div class=cbutn><a href=".AppUtility::getURLFromHome('course','course/course?cid='.$courseId).">Return to course page</a></div>\n";
    }
    echo "</form>\n";


}

function printq($qn,$qsetid,$seed,$pts,$isfinal) {
    global $isfinal,$imasroot;
    srand($seed);

    $qdata = QuestionSet::getByQuesSetId($qsetid);

    if ($qdata['hasimg']>0) {
        $query = QImages::getByQuestionSetId($qsetid);
        foreach ($query as $row) {
            ${$row['var']} = "<img src=\"$imasroot/assessment/qimages/{$row['filename']}\" alt=\"{$row['alttext']}\" />";
        }
    }
    eval(interpret('control',$qdata['qtype'],$qdata['control']));
    eval(interpret('qcontrol',$qdata['qtype'],$qdata['qcontrol']));
    $toevalqtxt = interpret('qtext',$qdata['qtype'],$qdata['qtext']);
    $toevalqtxt = str_replace('\\','\\\\',$toevalqtxt);
    $toevalqtxt = str_replace(array('\\\\n','\\\\"','\\\\$','\\\\{'),array('\\n','\\"','\\$','\\{'),$toevalqtxt);
    srand($seed+1);
    eval(interpret('answer',$qdata['qtype'],$qdata['answer']));
    srand($seed+2);
    $la = '';

    if (isset($choices) && !isset($questions)) {
        $questions =& $choices;
    }
    if (isset($variable) && !isset($variables)) {
        $variables =& $variable;
    }
    if ($displayformat=="select") {
        unset($displayformat);
    }

    //pack options
    if (isset($ansprompt)) {$options['ansprompt'] = $ansprompt;}
    if (isset($displayformat)) {$options['displayformat'] = $displayformat;}
    if (isset($answerformat)) {$options['answerformat'] = $answerformat;}
    if (isset($questions)) {$options['questions'] = $questions;}
    if (isset($answers)) {$options['answers'] = $answers;}
    if (isset($answer)) {$options['answer'] = $answer;}
    if (isset($questiontitle)) {$options['questiontitle'] = $questiontitle;}
    if (isset($answertitle)) {$options['answertitle'] = $answertitle;}
    if (isset($answersize)) {$options['answersize'] = $answersize;}
    if (isset($variables)) {$options['variables'] = $variables;}
    if (isset($domain)) {$options['domain'] = $domain;}
    if (isset($answerboxsize)) {$options['answerboxsize'] = $answerboxsize;}
    if (isset($hidepreview)) {$options['hidepreview'] = $hidepreview;}
    if (isset($matchlist)) {$options['matchlist'] = $matchlist;}
    if (isset($noshuffle)) {$options['noshuffle'] = $noshuffle;}
    if (isset($reqdecimals)) {$options['reqdecimals'] = $reqdecimals;}
    if (isset($grid)) {$options['grid'] = $grid;}
    if (isset($background)) {$options['background'] = $background;}

    if ($qdata['qtype']=="multipart") {
        if (!is_array($anstypes)) {
            $anstypes = explode(",",$anstypes);
        }
        $laparts = explode("&",$la);
        foreach ($anstypes as $kidx=>$anstype) {
            list($answerbox[$kidx],$tips[$kidx],$shans[$kidx]) = makeanswerbox($anstype,$kidx,$laparts[$kidx],$options,$qn+1);
        }
    } else {
        list($answerbox,$tips[0],$shans[0]) = makeanswerbox($qdata['qtype'],$qn,$la,$options,0);
    }

    echo "<div class=q>";
    if ($isfinal) {
        echo "<div class=\"trq$qn\">\n";
    } else {
        echo "<div class=m id=\"trq$qn\">\n";
    }
    echo "<div class=qnum>".($qn+1).") ";
    if (isset($params['points'])) {
        echo '<br/>'.$pts.'pts';
    }
    echo "</div>\n";//end qnum div
    echo "<div class=floatl><div>\n";
    //echo $toevalqtext;
    eval("\$evaledqtext = \"$toevalqtxt\";");
    echo printfilter(filter($evaledqtext));
    echo "</div>\n"; //end question div

    if (strpos($toevalqtxt,'$answerbox')===false) {
        if (is_array($answerbox)) {
            foreach($answerbox as $iidx=>$abox) {
                echo printfilter(filter("<div>$abox</div>\n"));
                echo "<div class=spacer>&nbsp;</div>\n";
            }
        } else {  //one question only
            echo printfilter(filter("<div>$answerbox</div>\n"));
        }


    }

    echo "</div>\n"; //end floatl div

    echo "</div>";//end m div
    if (!$isfinal) {
        echo "<div class=cbutn>\n";
        echo "<p><input type=button value=\"+1\" onclick=\"incspace($qn,1)\"><input type=button value=\"+.5\" onclick=\"incspace($qn,.5)\"><input type=button value=\"+.25\" onclick=\"incspace($qn,.25)\"><input type=button value=\"+.1\" onclick=\"incspace($qn,.1)\"><br/>";
        echo "<input type=button value=\"-1\" onclick=\"incspace($qn,-1)\"><input type=button value=\"-.5\" onclick=\"incspace($qn,-.5)\"><input type=button value=\"-.25\" onclick=\"incspace($qn,-.25)\"><input type=button value=\"-.1\" onclick=\"incspace($qn,-.1)\"></p>";
        echo "</div>\n"; //end cbutn div
    }
    echo "&nbsp;";
    echo "</div>\n"; //end q div
    if (!isset($showanswer)) {
        return $shans;
    } else {
        return $showanswer;
    }
}
?>
        </div>