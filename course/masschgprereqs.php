<?php 

// Mass set assessment prereqs
// David Lippman

require_once '../init.php';
require_once "../includes/htmlutil.php";

if (!isset($teacherid)) {
    echo 'You are not authorized to view this page';
    exit;
}

// Handle postbacks
if (isset($_POST['reqscoreshowtype'])) {
    $query = 'UPDATE imas_assessments SET reqscorejson=?,reqscoretype=? 
        WHERE id=? AND courseid=?';
    $stm = $DBH->prepare($query);
 
    foreach ($_POST['reqscoreshowtype'] as $aid=>$reqscoreshowtype) {
        if ($reqscoreshowtype == 'none' || empty($_POST['reqscoreaid'][$aid])) {
            $reqscoretype = 0;
            $reqscorejson = '';
        } else {
            $reqscorearr = [];
            foreach ($_POST['reqscoreaid'][$aid] as $k=>$v) {
                if (!empty($_POST['reqscore'][$aid][$k])) {
                    $reqscorearr[] = [intval($v), intval($_POST['reqscore'][$aid][$k]), intval($_POST['reqscorecalctype'][$aid][$k])];
                }
            }
            if (count($reqscorearr) == 1) {
                $reqscorejson = json_encode($reqscorearr[0]);
            } else if (count($reqscorearr)>1) {
                $reqscorejson = json_encode(['&', $reqscorearr]);
            }
            $reqscoretype = ($reqscoreshowtype == 'grey' ? 1 : 0); // 1 if greyed out, 0 only after
        }
        $stm->execute(array($reqscorejson, $reqscoretype, $aid, $cid));
    }
    header('Location: '.$basesiteurl.'/course/course.php?cid='.$cid);
    exit;
}

// Data load 
$query = 'SELECT id,name,reqscorejson,reqscoretype FROM imas_assessments 
    WHERE courseid=:courseid ORDER BY name';
$stm = $DBH->prepare($query);
$stm->execute(array(':courseid'=>$cid));
$assessments = array();
$vueData = [];
while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
    if ($row['reqscorejson']=='') {
        $vueData['reqscoreshowtype'][$row['id']] = 'none'; // no prereq
    } else if ($row['reqscoretype']&1) {
        $vueData['reqscoreshowtype'][$row['id']] = 'grey'; // show greyed
    } else {
        $vueData['reqscoreshowtype'][$row['id']] = 'after'; // Show only after 
    }
    if ($row['reqscorejson']=='') {
       $vueData['reqscorearr'][$row['id']] = [];
    } else {
        // normalize to an array of [aid,score,type] arrays
        $json = json_decode($row['reqscorejson'], true);
		if (is_array($json[1])) { // has bool format ['&', [array of objects]]
			$vueData['reqscorearr'][$row['id']] = $row['reqscorejson'] = $json[1];
		} else { // single format; make into an array
			$vueData['reqscorearr'][$row['id']] = $row['reqscorejson'] = [$json];
		}
    }
    $vueData['assessments'][] = ['id'=>$row['id'], 'name'=>$row['name']];
    $vueData['newAssessId'][$row['id']] = '';
}

// get course order 
$stm = $DBH->prepare('SELECT itemorder FROM imas_courses WHERE id=?');
$stm->execute(array($cid));
$itemorder = unserialize($stm->fetchColumn(0));
$stm = $DBH->prepare("SELECT id,typeid FROM imas_items WHERE courseid=? AND itemtype='Assessment'");
$stm->execute(array($cid));
$itemmap = $stm->fetchAll(PDO::FETCH_KEY_PAIR);

function flattenitems($items, &$itemmap, &$assessorder) {
    foreach ($items as $item) {
        if (is_array($item)) { // block
            if (!empty($item['items'])) {
                flattenitems($item['items'], $itemmap, $assessorder);
            }
        } else if (isset($itemmap[$item])) { // is an assessment
            $assessorder[] = $itemmap[$item];
        }
    }
}
$assessorder = array();
flattenitems($itemorder, $itemmap, $assessorder);
$vueData['order'] = $assessorder;

$from = Sanitize::simpleString($_GET['from'] ?? '');

// HTML display

$pagetitle = _('Mass Change Prereqs');
//$placeinhead = '<script type="text/javascript" src="'. $staticroot . '/javascript/tablesorter.js"></script>';
if (!empty($CFG['GEN']['uselocaljs'])) {
	$placeinhead = '<script type="text/javascript" src="'.$staticroot.'/javascript/vue3-4-31.min.js"></script>';
} else {
    $placeinhead = '<script src="https://cdnjs.cloudflare.com/ajax/libs/vue/3.4.31/vue.global.prod.min.js" integrity="sha512-Dg9zup8nHc50WBBvFpkEyU0H8QRVZTkiJa/U1a5Pdwf9XdbJj+hZjshorMtLKIg642bh/kb0+EvznGUwq9lQqQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>';
}
require_once '../header.php';

echo '<div class=breadcrumb>';
echo $breadcrumbbase . '<a href="course.php?cid='.$cid.'">'
    . Sanitize::encodeStringForDisplay($coursename) . '</a> &gt; ';
if ($from == 'chgassessments') {
    echo '<a href="chgassessments.php?cid='.$cid.'">';
} else {
    echo '<a href="chgassessments2.php?cid='.$cid.'">';
}
echo _('Mass Change Assessments').'</a> &gt; ';
echo _('Mass Change Prereqs');
echo '</div>';

echo '<h1>' . _('Mass Change Prereqs') . '</h1>';

echo '<p class="small">' . _('Note: To set the same prereq for multiple assessments at once, or to clear prereqs, it may be faster to use the Mass Change Assessments page.') . '</p>';
echo '<form method=post action="masschgprereqs.php?cid='.$cid.'">';
echo '<div id="app" class="skipmathrender" v-cloak>';
echo '<table id="myTable" class="gb"><thead><tr>';
echo '<th>' . _('Assessment') . '</th>';
echo '<th>' . _('Type') . '</th>';
echo '<th>' . _('Prerequisite') . '</th>';
echo '</tr></thead><tbody>';
?>
<tr v-for="(aid,index) in order" :key="index" :class="index%2==0?'even':'odd'">
    <td :id="'n' + aid">{{ assessmentName(aid) }}</td>
    <td><select :id="'reqscoreshowtype'+index" :name="'reqscoreshowtype['+aid+']'" v-model="reqscoreshowtype[aid]">
            <option value="none"><?php echo _('No prerequisite');?></option>
            <option value="after"><?php echo _('Show only after');?></option>
            <option value="grey"><?php echo _('Show greyed until');?></option>
        </select>
    </td>
    <td>
        <div v-show="reqscoreshowtype[aid] != 'none'">
            <ul class="nomark">
                <li v-for="(ritem,rindex) in reqscorearr[aid]" :key="rindex">
                    <input type="hidden" :name="'reqscoreaid['+aid+'][]'" v-model="ritem[0]"/>
                    <label>
                        {{ assessmentName(ritem[0]) }}:
                        <?php echo _('Score of');?>
                        <input size=3 type="number" :name="'reqscore['+aid+'][]'" v-model="ritem[1]" min="0" max="9999"/>
                    </label>
                    <select :name="'reqscorecalctype['+aid+'][]'" v-model="ritem[2]" aria-label="<?php echo _('prerequisite score format');?>">
                        <option value="0"><?php echo _('points');?></option>
                        <option value="1"><?php echo _('percent');?></option>
                    </select>
                    <button class="slim" type="button" @click="reqscorearr[aid].splice(index, 1)"><?php echo _('Remove');?></button>
                </li>
                <li>
                    <select v-model="newAssessId[aid]">
                        <option value=""><?php echo _('Add prerequisite');?>…</option>
                        <option v-for="a in availableAssessmentsForPrereqs[aid]" :key="a.id" :value="a.id">
                            {{ a.name }}
                        </option>
                    </select>
                    <button type="button" 
                        @click="reqscorearr[aid].push([newAssessId[aid], 1, 0]); newAssessId[aid] = ''" 
                        :disabled="!newAssessId[aid]">
                        <?php echo _('Add');?>
                    </button>
                </li>
            </ul>
        </div>
    </td>

</tr>
</tbody></table></div>
<script type="text/javascript">
const { createApp } = Vue;
createApp({
    data: function() { return <?php echo json_encode($vueData, JSON_INVALID_UTF8_IGNORE); ?>;},
    computed: {
        availableAssessmentsForPrereqs() {
            var out = {};
            for (let aid in this.reqscorearr) {
                const used = new Set(this.reqscorearr[aid].map(p => p[0]));
			    out[aid] = this.assessments.filter(a => !used.has(a.id));
            }
			return out;
		}
    },
    methods: {
        assessmentName(id) {
			return this.assessments.find(a => a.id == id)?.name ?? id;
		}
    }
}).mount('#app');;
</script>
<?php

echo '<p><button type=submit>'._('Submit').'</button></p>';

echo '</form>';

require_once '../footer.php';



