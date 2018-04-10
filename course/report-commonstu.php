<?php
//IMathAS: Student sorting report
//(c) 2018 David Lippman

require("../init.php");
require("../includes/report-commonstu-funcs.php");

if (!isset($teacherid)) {
	echo "Not authorized";
	exit;
}

//if saving or running a ruleset, process it
if (isset($_POST['runset'])) {
	if (isset($_POST['rulesets'])) {
		$rulesets = json_decode($_POST['rulesets'], true);
		setCourseRuleSets(Sanitize::courseId($cid), $rulesets);
	} else {
		$rulesets = getCourseRuleSets($cid);
	}
	$result = runRuleSet($rulesets[$_POST['runset']]['rules']);
	header('Content-Type: application/json');
	echo json_encode($result);
	exit;
}

$rulesets = getCourseRuleSets($cid);
$gbcats = getGBcats($cid);
$now = time();
$defsdate = tzdate('n/j/Y', $now);
$defedate = tzdate('n/j/Y', $now+7*24*60*60);

$placeinhead .= '<script src="https://cdn.jsdelivr.net/npm/vue@2.5.13/dist/vue.min.js"></script>';
$placeinhead .= "<script type=\"text/javascript\" src=\"$imasroot/javascript/DatePicker.js\"></script>";
$placeinhead .= '<style type="text/css">
 [v-cloak] { display: none;}
 .rulebtngroup {
 	font-size: 0;
 }
 .rulebtngroup button {
 	font-size: small;
 	line-height: 1em;
 	padding: 2px 4px;
 	margin: 0px;
 	height: auto;
 	border-radius: 0;
 }
 #rulelist li {
 	margin-bottom: 4px;
 }

.flip-list-move {
  transition: transform .5s;
}
#ruleEditor, #ruleDetails {
	margin-left: 15px;
}
</style>';

$pagetitle = _('Activity Report - Sort Students by Activity');
$curBreadcrumb = $breadcrumbbase;
$curBreadcrumb .= "<a href=\"course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> ";
$curBreadcrumb .= "&gt; <a href=\"coursereports.php?cid=$cid\">Course Reports</a> ";


require("../header.php");
echo '<div class="breadcrumb">'. $curBreadcrumb . '&gt; '.$pagetitle.'</div>';
echo '<div class="pagetitle"><h2>'.$pagetitle.'</h2></div>';

?>

<div id="app" v-cloak>
<div id="rulePicker">
	<span v-if="ruleSets.length>0">
		Existing rule sets: <select v-model="selectedRuleSet">
			<option v-for="(option,index) in ruleSets" :value="index">
				{{ option.name }}
			</option>
		</select>
		<button @click="editRuleSet()">Edit Rule Set</button>
		<button @click="runRuleSet()">Run Rule Set</button>
	</span>
	<span v-else>No existing rule sets </span>
	<button @click="newRuleSet()">Add Rule Set</button>
</div>
<div id="ruleEditor" v-if="activeRuleSet==selectedRuleSet || activeRuleSet==ruleSets.length">
	<p>Editing rule set <input v-model="curRuleSetName" size=20></p>
	<div v-if="rulePhrases.length>0">
		Current rules:
		<transition-group name="flip-list" tag="ol" id="rulelist">
			<li v-for="(phrase,index) in rulePhrases" :key="phrase">
				<span class=rulebtngroup>
				<button @click="move(index,-1)" title="Up">&uarr;</button>
				<button @click="move(index,1)" title="Down">&darr;</button>
				<button @click="edit(index)">Edit</button>
				<button @click="remove(index)" title="Delete">X</button>
				</span>
				{{ index==0?"First, ":"Of those remaining, "}} show students
				{{phrase}}
			</li>
		</transition-group>
	</div>
	<div id="ruleDetails">
		<p>{{currentRule.editIndex==-1?'New':'Edit'}} rule type: 
			<select v-model="currentRule.ruleType">
			<option v-for="(option,index) in ruleTypes" :value="index">{{option}}</option>
			</select>
		</p>
		<p v-if="currentRule.ruleType!='none'">Show students who 
			<span v-if="currentRule.ruleType=='score' || currentRule.ruleType=='scores'"> had a score 
				<select v-model="currentRule.abovebelow">
					<option v-for="(option,index) in abovebelowTypes" :value="index">{{option}}</option>
				</select>
				<input v-model="currentRule.scorebreak" size=2>%
				<span v-if="currentRule.ruleType=='score'">average on assignments</span>
				<span v-if="currentRule.ruleType=='scores'">
					on <select v-model="currentRule.numassn">
						<option v-for="(option,index) in numassnTypes" :value="index">{{option}}</option>
					</select> assignment(s)
				</span>
				that are 
				<select v-model="currentRule.tocnt">
					<option v-for="(option, index) in cntTypes" :value="index">{{option}}</option>
				</select>
				in category
				<select v-model="currentRule.gbcat">
					<option value="0">Default</option>
					<option v-for="(option,index) in gbcatTypes" :value="index">{{option}}</option>
				</select>
			</span>
			<span v-if="currentRule.ruleType=='start' || currentRule.ruleType=='comp'">
				<span v-if="currentRule.ruleType=='start'">started</span>
				<span v-if="currentRule.ruleType=='comp'">completed</span>
				<select v-model="currentRule.numassn">
					<option v-for="(option,index) in numassnTypes" :value="index">{{option}}</option>
				</select>
				assignment(s) in category
				<select v-model="currentRule.gbcat">
					<option value="-1">all categories</option>
					<option value="0">Default</option>
					<option v-for="(option,index) in gbcatTypes" :value="index">{{option}}</option>
				</select>
			</span>
			<span v-if="currentRule.ruleType=='late'"> used at least
				<input v-model="currentRule.numLP" size=2> LatePass(es)
				on assignments in category
				<select v-model="currentRule.gbcat">
					<option value="-1">all categories</option>
					<option value="0">Default</option>
					<option v-for="(option,index) in gbcatTypes" :value="index">{{option}}</option>
				</select>
			</span>
			<span v-if="currentRule.ruleType=='close'">
				started 
				<select v-model="currentRule.numassn">
					<option v-for="(option,index) in numassnTypes" :value="index">{{option}}</option>
				</select>
				assignment(s) in category
				<select v-model="currentRule.gbcat">
					<option value="-1">all categories</option>
					<option value="0">Default</option>
					<option v-for="(option,index) in gbcatTypes" :value="index">{{option}}</option>
				</select>
				within <input v-model="currentRule.closeTime" size=2> hours of the due date 
			</span>
			
			<select v-model="currentRule.timeframe">
				<option v-for="(option,index) in timeframeTypes" :value="index">{{option}}</option>
			</select>
			<span v-if="currentRule.timeframe=='since' || currentRule.timeframe=='between'">
				<input type=text size=10 name="sdate" id="sdate" v-model="currentRule.sdate">
				<a href="#" onClick="displayDatePicker('sdate', this); return false">
				<img src="../img/cal.gif" alt="Calendar"/></a>
			</span>
			<span v-if="currentRule.timeframe=='thisweek' || currentRule.timeframe=='week'">
				<select v-model="currentRule.dayofweek">
					<option v-for="(option,index) in dayofweekTypes" :value="index">{{option}}</option>
				</select>
			</span>
			<span v-if="currentRule.timeframe=='inlast'">
				<input v-model="currentRule.inlastDays" size=2> days
			</span>
			<span v-if="currentRule.timeframe=='between'">
				and 
				<input type=text size=10 name="edate" id="edate" v-model="currentRule.edate">
				<a href="#" onClick="displayDatePicker('edate', this, 'sdate', 'start date'); return false">
				<img src="../img/cal.gif" alt="Calendar"/></a>
			</span>
			<span v-if="currentRule.timeframe=='due'">
				<input v-model="currentRule.innextDays" size=2> days
			</span>
			<br/>
			<button @click="editRule()" v-if="currentRule.editIndex!=-1">
				Save changes to rule {{(currentRule.editIndex+1)}}
			</button>
			<button @click="addRule()">{{currentRule.editIndex==-1?'Add Rule':'Add as New Rule'}}</button>
		</p>
	</div>
	<p><button @click="saveRuleSet()">Save and Run Rule Set</button></p>
</div>

<div v-if="resultMessage!=''">{{resultMessage}}</div>
<div v-if="resultRuleSet!=-1">
	<h3>Student Groupings: {{ ruleSets[resultRuleSet].name }}</h3>
	<div v-for="(rule,index) in ruleSets[resultRuleSet].rules">
		<p>Students {{ rulePhrases[index] }}
		<span v-if="results[index].length>0">
		  <br/>
		  <button type=button @click="sendMessage(index)">Send Message to Group</button>
		  <button type=button @click="copyEmails(index)">Copy Emails</button>
		</span>
		</p>
		<ul>
			<li v-for="stu in results[index]">{{ stu[0] }}</li>
		</ul>
	</div>
	<div v-if="results[results.length-1].length>0">
		<p>Remaining students</p>
		<span v-if="results[results.length-1].length>0">
		  <br/>
		  <button type=button @click="sendMessage(results.length-1)">Send Message to Group</button>
		  <button type=button @click="copyEmails(results.length-1)">Copy Emails</button>
		</span>
		<ul>
			<li v-for="stu in results[results.length-1]">{{ stu[0] }}</li>
		</ul>
	</div>
</div>
<div class=small>
<p>&nbsp;</p><p>Notes:</p>
<ul>
	<li>When using a day of week rule like "this week, since" or "last week, ending on",
	    if your rule is "since Monday" or "ending on Monday" and today is a Monday, 
	    the week used will include today.</li>
	<li>"in the last __ days" will be the start of day that many days ago.  So if you
	     run "in the last 2 days" anytime Wednesday, it will count from the start of the
	     day Monday.  If you run "in the last 0 days" it will only include today.
	     Likewise, "due by midnight in 0 days" would mean midnight tonight.</li>
	<li>"Started" is defined as having opened the assignment.</li>
	<li>"Completed" is defined as every question being attempted.</li>
	<li>For score-based rules, "past due" is based on the default due date, and does not
	    take into account LatePasses or exceptions.</li>
</ul>	   
</div>

</div>

<script type="text/javascript">
var app = new Vue({
	el: '#app',
	computed: {
		rulePhrases: function() {
			var phrases = [];
			var thisphrase,curRule;
			for (var i=0;i<this.allRules.length;i++) {
				curRule = this.allRules[i];
				thisphrase = 'who ';
								
				if (curRule.ruleType=='score' || curRule.ruleType=='scores') {
					thisphrase += 'had a score ';
					thisphrase += this.abovebelowTypes[curRule.abovebelow];
					thisphrase += ' '+curRule.scorebreak+'% ';
					if (curRule.ruleType=='score') {
						thisphrase += 'average on';
					} else if (curRule.ruleType=='scores') {
						thisphrase += 'on '+this.numassnTypes[curRule.numassn];
					}
					if (curRule.ruleType=='score' || curRule.ruleType=='scores') {
					thisphrase += ' ' + this.cntTypes[curRule.tocnt];
				}
				} else if (curRule.ruleType=='start' || curRule.ruleType=='close') {
					thisphrase += 'started ';
					thisphrase += this.numassnTypes[curRule.numassn];
				} else if (curRule.ruleType=='comp') {
					thisphrase += 'completed ';
					thisphrase += this.numassnTypes[curRule.numassn];
				} else if (curRule.ruleType=='late') {
					thisphrase += 'used at least ';
					thisphrase += curRule.numLP + ' LatePass(es) on';
				}
				if (curRule.gbcat==0 && curRule.ruleType=='score') {
					thisphrase += ' the overall total';
				} else if (curRule.gbcat==0) {
					thisphrase += ' assignment(s) in all categories';
				} else {
					thisphrase += ' assignment(s) in category ';
					thisphrase += this.gbcatTypes[curRule.gbcat];
				}
				
				if (curRule.ruleType=='close') {
					thisphrase += ' within '+curRule.closeTime+' hours of the due date';
				}
				thisphrase += ' '+this.timeframeTypes[curRule.timeframe];
				if (curRule.timeframe=='since') {
					thisphrase += ' '+curRule.sdate;
				} else if (curRule.timeframe=='inlast') {
					thisphrase += ' '+curRule.inlastDays+' days';
				} else if (curRule.timeframe=='week' || curRule.timeframe=='thisweek') {
					thisphrase += ' '+this.dayofweekTypes[curRule.dayofweek];
				} else if (curRule.timeframe=='between') {
					thisphrase += ' '+curRule.sdate+' and '+curRule.edate;
				} else if (curRule.timeframe=='due') {
					thisphrase += ' '+curRule.innextDays+' days';
				}
				phrases.push(thisphrase);
			}
			return phrases;
		},
		resultsStuList: function() {
			if (this.results.length==0) {return [];}
			var out = []; var stus = [];
			for(var i=0;i<this.results.length;i++) {
				stus = [];
				for (j=0;j<this.results[i].length;j++) {
					stus.push(this.results[i][j][1]);	
				}
				out[i] = stus.join('-');
			}
			return out;
		}
	},
	data: {
		activeRuleSet: -1,
		selectedRuleSet: 0,
		editingRuleSet: -1,
		allRules: [],
		curRuleSetName: '',
		ruleSets: <?php echo json_encode($rulesets, JSON_HEX_QUOT|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS); ?>,
		currentRule: {
			ruleType: 'none',
			abovebelow: 0,
			scorebreak: 75,
			gbcat: 0,
			timeframe: 'inlast',
			inlastDays: 5,
			innextDays: 1,
			closeTime: 24,
			numassn: 1,
			numLP: 1,
			dayofweek: 1,
			tocnt: 0,
			sdate: '<?php echo $defsdate;?>',
			edate: '<?php echo $defedate;?>',
			editIndex: -1
		},
		ruleTypes: {
			'none': _('Select...'),
			'score': _('Score average'),
			'scores': _('Scores'),
			'comp': _('Completion'),
			'start': _('Starting'),
			'late': _('LatePass'),
			'close': _('Last Minute')
		},
		abovebelowTypes: ['above','below'],
		gbcatTypes: <?php echo json_encode($gbcats); ?>,
		timeframeTypes: {
			'since': _('since'),
			'between': _('between'),
			'inlast': _('in the last'),
			'thisweek': _('this week, since'),
			'week': _('last week, ending on'),
			'due': _('due by midnight in')
		},
		numassnTypes: ['no','one','all'],
		cntTypes: ['past due','past due or available','attempted','past due or attempted'],
		dayofweekTypes: ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'],
		results: [],
		showResults: false,
		resultMessage: '',
		resultRuleSet: -1
	},
	methods: {
		move: function(index,delta) {
			var newIndex = index + delta;
			if (newIndex < 0  || newIndex == this.allRules.length) return; //Already at the top or bottom.
			/*
			var indexes = [index, newIndex].sort(); //Sort the indixes
			this.allRules.splice(indexes[0], 2, this.allRules[indexes[1]], this.allRules[indexes[0]]); //Replace from lowest index, two elements, reverting the order
			*/
			var removed = this.allRules.splice(index, 1);
			this.allRules.splice(newIndex,0,removed[0]);
		},
		remove: function(index) {
			if (confirm('Are you sure you want to delete this rule?')) {
				this.allRules.splice(index,1);		
			}
		},
		addRule: function() {
			this.allRules.push(this.clone(this.currentRule));
			this.currentRule.editIndex = -1;
			this.currentRule.ruleType = 'none';
		},
		editRule: function() {
			this.allRules.splice(this.currentRule.editIndex, 1, this.clone(this.currentRule));
			this.currentRule.editIndex = -1;
			this.currentRule.ruleType = 'none';
		},
		edit: function(index) {
			this.currentRule = Object.assign({}, this.allRules[index]);
			this.currentRule.editIndex = index;
		},
		clone: function(obj) {
			return JSON.parse(JSON.stringify(obj)); //crude
		},
		newRuleSet: function() {
			this.activeRuleSet = this.ruleSets.length;
			this.editingRuleSet = -1;
			this.curRuleSetName = 'New Rule Set';
			this.allRules = [];
			this.currentRule.ruleType = 'none';
			this.currentRule.editIndex = -1;
			this.resultRuleSet = -1;
		},
		editRuleSet: function() {
			this.activeRuleSet = this.selectedRuleSet;
			this.editingRuleSet = this.selectedRuleSet;
			this.curRuleSetName = this.ruleSets[this.activeRuleSet].name;
			this.allRules = this.clone(this.ruleSets[this.activeRuleSet].rules);
			this.currentRule.ruleType = 'none';
			this.currentRule.editIndex = -1;
			this.resultRuleSet = -1;
		},
		saveRuleSet: function() {
			this.runRuleSet(true);
		},
		runRuleSet: function(save) {
			var reqdata = {};
			var runRuleSet = this.selectedRuleSet;
			if (save === true) {
				this.resultMessage = 'Saving and loading results...';
				if (this.editingRuleSet == -1) { //new
					this.ruleSets.push({
						name: this.curRuleSetName,
						rules: this.clone(this.allRules)
					});
					this.activeRuleSet = -1;
					this.selectedRuleSet = this.ruleSets.length-1;
					var runRuleSet = this.selectedRuleSet;
				} else { //editing
					this.ruleSets[this.editingRuleSet].name = this.curRuleSetName;
					this.ruleSets[this.editingRuleSet].rules = this.clone(this.allRules);
					this.activeRuleSet = -1;
					this.editingRuleSet = -1;
				}
				reqdata =  {
					rulesets: JSON.stringify(this.ruleSets),
					runset: runRuleSet
				};
			} else {
				this.resultMessage = 'Loading results...';
				reqdata = {
					runset: runRuleSet
				};
			}
			var self = this;
			$.ajax({
				type: "POST",
				url: "report-commonstu.php?cid="+cid,
				data: reqdata,
				dataType: "json"
			}).done(function(results) {
				self.displayRuleSet(results, runRuleSet);
			}).fail(function(error) {
				self.resultMessage = error;
			});
		},
		displayRuleSet: function(results, runRuleSet) {
			this.results = results;
			this.resultRuleSet = runRuleSet;
			this.activeRuleSet = -1;
			this.showResults = true;
			this.resultMessage = '';
			this.allRules = this.clone(this.ruleSets[this.resultRuleSet].rules);
		},
		sendMessage: function(index) {
			GB_show('Send Message', 'masssend.php?embed=true&nolimit=true&cid='+cid+'&to='+this.resultsStuList[index], 760,'auto');
		},
		copyEmails: function(index) {
			GB_show('Emails', 'viewemails.php?cid='+cid+'&ids='+this.resultsStuList[index], 760,'auto');
		}
	}
});
</script>

<?php
require("../footer.php");

