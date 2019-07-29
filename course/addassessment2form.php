<?php

$vueData = array(
	'name' => $line['name'],
	'summary' => $line['summary'],
	'intro' => $line['intro'],
	'avail' => $line['avail'],
	'sdatetype' => ($startdate==0?'0':'sdate'),
	'startdate' => $startdate,
	'sdate' => $sdate,
	'stime' => $stime,
	'edatetype' => ($enddate==2000000000?'2000000000':'edate'),
	'enddate' => $enddate,
	'edate' => $edate,
	'etime' => $etime,
	'datesbylti' => intval($dates_by_lti),
	'allowpractice' => $line['reviewdate']>0,
	'displaymethod' => $line['displaymethod'],
	'subtype' => $line['submitby'],
	'defregens' => $line['defregens'],
	'defregenpenalty' => $defregenpenalty,
	'defregenpenaltyaftern' => $defregenpenalty_aftern,
	'keepscore' => $line['keepscore'],
	'defattempts' => $line['defattempts'],
	'defattemptpenalty' => $defattemptpenalty,
	'defattemptpenaltyaftern' => $defattemptpenalty_aftern,
	'showscores' => $line['showscores'],
	'showans' => $line['showans'],
	'viewingb' => $line['viewingb'],
	'scoresingb' => $line['scoresingb'],
	'ansingb' => $line['ansingb'],
	'gbcategory' => $line['gbcategory'],
	'gbcatOptions' => $gbcats,
	'caltag' => $line['caltag'],
	'shuffle' => ($line['shuffle']&(1+16)),
	'noprint' => $line['noprint'] > 0,
	'sameseed' => ($line['shuffle']&2) > 0,
	'samever' => ($line['shuffle']&4) > 0,
	'istutorial' => $line['istutorial'] > 0,
	'allowlate' => $line['allowlate']%10,
	'latepassafterdue' => $line['allowlate']>10,
	'dolpcutoff' => $line['LPcutoff']>0,
	'lpdate' => $lpdate,
	'lptime' => $lptime,
	'timelimit' => abs($line['timelimit'])>0 ? $timelimit : '',
	'allowovertime' => $line['overtime_grace'] > 0,
	'overtimegrace' => $line['overtime_grace'] > 0 ? round($line['overtime_grace']/60,3) : 5 ,
	'overtimepenalty' => $line['overtime_penalty'],
	'assmpassword' => $line['password'],
	'revealpw' => false,
	'showhints' => ($line['showhints']&1) > 0,
	'showextrefs' => ($line['showhints']&2) > 0,
	'msgtoinstr' => $line['msgtoinstr'] > 0,
	'doposttoforum' => $line['posttoforum'] > 0,
	'posttoforum' => $line['posttoforum']>0 ? $line['posttoforum'] :
				((count($forums)>0) ? $forums[0]['value'] : 0),
	'forumOptions' => $forums,
	'extrefs' => $extrefs,
	'showtips' => ($line['showtips']==0 || $line['showtips']==2) ? $line['showtips'] : 2,
	'cntingb' => $line['cntingb'],
	'minscore' => $line['minscore'],
	'minscoretype' => $minscoretype,
	'usedeffb' => $usedeffb,
	'deffb' => $deffb,
	'allowinstraddtutors' => (!isset($CFG['GEN']['allowinstraddtutors']) || $CFG['GEN']['allowinstraddtutors']==true),
	'tutoredit' => $line['tutoredit'],
	'exceptionpenalty' => $line['exceptionpenalty'],
	'defoutcome' => $line['defoutcome'],
	'outcomeOptions' => $outcomeOptions,
	'isgroup' => $line['isgroup'],
	'groupmax' => $line['groupmax'],
	'canchangegroup' => !($taken && $line['isgroup']>0),
	'groupsetid' => $line['groupsetid'],
	'groupOptions' => $groupOptions,
	'reqscoreshowtype' => $reqscoredisptype,
	'reqscore' => abs($line['reqscore']),
	'reqscorecalctype' => ($line['reqscoretype']&2) > 0 ? 1 : 0,
	'reqscoreaid' => $line['reqscoreaid'],
	'reqscoreOptions' => $otherAssessments,
	'taken' => $taken,
	'showDisplayDialog' => false
);

// skipmathrender class is needed to prevent katex parser from mangling
// Vue template
?>
<div id="app" class="skipmathrender" v-cloak>
	<span class=form>Assessment Name:</span>
	<span class=formright>
		<input type=text size=30 name=name v-model="name" required>
	</span><br class=form />

	Summary:<br/>
	<div class=editor>
		<textarea cols=50 rows=15 id=summary name=summary v-model="summary" style="width: 100%"></textarea>
	</div><br class=form />

	Intro/Instructions:<br/>
	<?php if (isset($introconvertmsg)) {echo $introconvertmsg;} ?>
	<div class=editor>
		<textarea cols=50 rows=20 id=intro name=intro v-model="intro" style="width: 100%"></textarea>
	</div><br class=form />

	<span class=form>Show:</span>
	<span class=formright>
		<label>
			<input type=radio name="avail" value="0" v-model="avail" />
			Hide
		</label><br/>
		<label>
			<input type=radio name="avail" value="1" v-model="avail"/>
			Show by Dates
		</label>
	</span><br class="form"/>

	<div v-show="avail==1 && datesbylti==0">
		<span class=form>Available After:</span>
		<span class=formright>
			<label>
				<input type=radio name="sdatetype" value="0" v-model="sdatetype" />
				Available always until end date
			</label><br/>
			<label>
				<input type=radio name="sdatetype" value="sdate" v-model="sdatetype"/>
				Available after
			</label>
			<input type=text size=10 name="sdate" v-model="sdate">
			<a href="#" onClick="displayDatePicker('sdate', this); return false">
			<img src="../img/cal.gif" alt="Calendar"/></a>
			at <input type=text size=8 name=stime v-model="stime">
		</span><br class="form"/>

		<span class=form>Available Until:</span>
		<span class=formright>
			<label>
				<input type=radio name="edatetype" value="2000000000" v-model="edatetype" />
				Available always after start date
			</label><br/>
			<label>
				<input type=radio name="edatetype" value="edate" v-model="edatetype"/>
				Due
			</label>
			<input type=text size=10 name="edate" v-model="edate">
			<a href="#" onClick="displayDatePicker('edate', this); return false">
			<img src="../img/cal.gif" alt="Calendar"/></a>
			at <input type=text size=8 name=etime v-model="etime">
		</span><br class="form"/>
	</div>
	<div v-show="avail==1 && datesbylti>0">
		<span class=form>Due date</span>
		<span class=formright>
			The course setting is enabled for dates to be set via LTI.<br/>
			<span v-if="datesbylti==1">
				Waiting for the LMS to send a date
			</span>
			<span v-else-if="enddate == 2000000000">
				Default due date set by LMS: No due date (individual student due dates may vary)
			</span>
			<span v-else>
				Default due date set by LMS: {{ edate + ' ' + etime}}
				(individual student due dates may vary)
			</span>
		</span><br class=form />
	</div>

	<div v-if="avail==1 && edatetype=='edate'">
		<span class=form>Practice mode:</span>
		<span class=formright>
			<label>
				<input type=checkbox name="allowpractice" value="true" v-model="allowpractice"/>
				Keep open for un-graded practice after the due date
			</label>
		</span><br class=form />
	</div>

	<fieldset>
		<legend>Assessment Options</legend>

		<div>
			<a href="#" onclick="groupToggleAll(1);return false;">Expand All</a>
	 		<a href="#" onclick="groupToggleAll(0);return false;">Collapse All</a>
		</div>
		<div class="block grouptoggle">
			<img class="mida" src="../img/collapse.gif" />
			Core Options
		</div>
		<div class="blockitems">
			<label class=form for="displaymethod">Display style:</label>
			<span class=formright>
				<select name="displaymethod" id=displaymethod v-model="displaymethod">
					<option value="skip">One question at a time</option>
					<option value="full">All questions at once, or in pages</option>
					<option value="video_cued">Video Cued</option>
					<?php if (isset($CFG['GEN']['livepollserver'])) {
						echo '<option value="livepoll">Live Poll</option>';
					}?>
				</select>
				<a href="#" id="dispdetails" @click.prevent="doShowDisplayDialog">Details</a>
			</span><br class=form />

			<label class="form" for="subtype">Submission type:</label>
			<span class="formright">
				<select name="subtype" id="subtype" v-model="subtype">
					<option value="by_question">Homework-style: new versions of individual questions</option>
					<option value="by_assessment">Quiz-style: retake whole assessment with new versions</option>
				</select>
				<span v-if="taken" class="noticetext">
					<br/>
					Warning: Changing this after students have started will require converting
					their data, and lead to loss of data on earlier attempts.
				</span>
			</span><br class=form />


			<span class=form>Versions:</span>
			<span class=formright>

				<label for="defregens" v-show="subtype == 'by_question'">
					Number of versions for each question:
				</label>
				<label for="defregens" v-show="subtype == 'by_assessment'">
					Number of times assessment can be taken:
				</label>
				<input type=number min=1 max=100 size=3 id="defregens"
					name="defregens" v-model.number="defregens" />
				<span v-show="defregens > 1">
					<br/>
					With a penalty of
					<input type=number min=0 max=100 size=3 id="defregenpenalty"
						name="defregenpenalty" v-model.number="defregenpenalty" />%
					per version
					<span v-show="defregenpenalty>0">
						after
						<input type=number min=1 :max="Math.min(defregens,9)" size=3 id="defregenpenaltyaftern"
							name="defregenpenaltyaftern" v-model.number="defregenpenaltyaftern" />
						full-credit versions
					</span>
					<br/>
					<span v-show="subtype == 'by_assessment'">
						<label for="keepscore">
							Score to keep:
						<label>
						<select id="keepscore" name="keepscore" v-model="keepscore">
							<option value="best">Best</option>
							<option value="last">Last</option>
							<option value="average">Average</option>
						</select>
					</span>
				</span>
			</span><br class=form />


			<span class=form>Tries:</span>
			<span class=formright>
				<label for="defattempts">
					Number of tries on each version of a question:
				</label>
				<input type=number min=1 max=100 size=3 id="defattempts"
					name="defattempts" v-model.number="defattempts" />
				<span v-show="defattempts>1">
					<br/>
					With a penalty of
					<input type=number min=0 max=100 size=3 id="defattemptpenalty"
						name="defattemptpenalty" v-model.number="defattemptpenalty" />%
					per try
					<span v-show="defattemptpenalty>0">
						after
						<input type=number min=1 :max="Math.min(defattempts,9)" size=3 id="defattemptpenaltyaftern"
							name="defattemptpenaltyaftern" v-model.number="defattemptpenaltyaftern" />
						full-credit tries
					</span>
				</span>
			</span><br class=form />

			<label class="form" for="showscores">
				During assessment, show scores:
			</label>
			<span class="formright">
				<select name="showscores" id="showscores" v-model="showscores">
					<option v-for="option in showscoresOptions" :value="option.value" :key="option.value">
						{{ option.text }}
					</option>
				</select>
			</span><br class=form />

			<div v-show="showansOptions.length > 0">
				<label class="form" for="showans">
					During assessment, show answers:
				</label>
				<span class="formright">
					<select name="showans" id="showans" v-model="showans">
						<option v-for="option in showansOptions" :value="option.value">
							{{ option.text }}
						</option>
					</select>
				</span><br class=form />
			</div>

			<label class="form" for="viewingb">
				Students can view their work in the gradebook:
			</label>
			<span class="formright">
				<select name="viewingb" id="viewingb" v-model="viewingb">
					<option v-for="option in viewInGbOptions" :value="option.value" :key="option.value">
						{{ option.text }}
					</option>
				</select>
			</span><br class=form />

			<div v-show="scoresInGbOptions.length > 0">
				<label class="form" for="scoresingb">
					Students can view their scores in the gradebook:
				</label>
				<span class="formright">
					<select name="scoresingb" id="scoresingb" v-model="scoresingb">
						<option v-for="option in scoresInGbOptions" :value="option.value" :key="option.value">
							{{ option.text }}
						</option>
					</select>
				</span><br class=form />
			</div>

			<div v-show="ansInGbOptions.length > 0">
				<label class="form" for="ansingb">
					Students can view correct answers in the gradebook:
				</label>
				<span class="formright">
					<select name="ansingb" id="ansingb" v-model="ansingb">
						<option v-for="option in ansInGbOptions" :value="option.value" :key="option.value">
							{{ option.text }}
						</option>
					</select>
				</span><br class=form />
			</div>

			<label class="form" for="gbcategory">
				Gradebook Category:
			</label>
			<span class="formright">
				<select name="gbcategory" id="gbcategory" v-model="gbcategory">
					<option v-for="option in gbcatOptions" :value="option.value" :key="option.value">
						{{ option.text }}
					</option>
				</select>
			</span><br class=form />

		</div>

		<div class="block grouptoggle">
			<img class="mida" src="../img/expand.gif" />
			Additional Display Options
		</div>
		<div class="blockitems hidden">
			<label class="form" for="caltag">Calendar icon:</label>
			<span class="formright">
				<input name="caltag" id="caltag" v-model="caltag" type=text size=8 />
			</span><br class="form" />

			<label class=form for="shuffle">Shuffle item order:</label>
			<span class=formright>
				<select name="shuffle" id="shuffle" v-model="shuffle">
					<option value="0">No</option>
					<option value="1">All</option>
					<option value="16">All but first</option>
				</select>
			</span><br class=form />

			<span class=form>Options</span>
			<span class=formright>
				<label>
					<input type="checkbox" value="1" name="noprint" v-model="noprint" />
					Make hard to print
				</label>
				<label v-show="subtype != 'by_question' || defregens==1">
					<br/>
					<input type="checkbox" value="2" name="sameseed" v-model="sameseed" />
					All items same random seed
				</label>
				<br/>
				<label>
					<input type="checkbox" value="4" name="samever" v-model="samever" />
					All students same version of questions
				</label>
				<br/>
				<label>
					<input type="checkbox" value="1" name="istutorial" v-model="istutorial" />
					Suppress default score result display
				</label>
			</span><br class=form />
		</div>

		<div class="block grouptoggle">
			<img class="mida" src="../img/expand.gif" />
			Time Limit and Access Control
		</div>
		<div class="blockitems hidden">
			<label for="allowlate" class=form>Allow use of LatePasses?:</label>
			<span class=formright>
				<select name="allowlate" id="allowlate" v-model="allowlate">
					<option value="0">None</option>
					<option value="1">Unlimited</option>
					<option value="2">Up to 1</option>
					<option value="3">Up to 2</option>
					<option value="4">Up to 3</option>
					<option value="5">Up to 4</option>
					<option value="6">Up to 5</option>
					<option value="7">Up to 6</option>
					<option value="8">Up to 7</option>
					<option value="9">Up to 8</option>
				</select>
				<span v-show="allowlate > 0">
					<label>
						<input type="checkbox" name="latepassafterdue" v-model="latepassafterdue">
						Allow LatePasses after due date
					</label>
					<br/>
					<label>
						<input type="checkbox" name="dolpcutoff" value="1" v-model="dolpcutoff" />
						Restrict by date.
					</label>
					<span v-show="dolpcutoff">
						No extensions past
						<input type=text size=10 name="lpdate" v-model="lpdate">
						<a href="#" onClick="displayDatePicker('lpdate', this); return false">
						<img src="../img/cal.gif" alt="Calendar"/></A>
						at <input type=text size=8 name=lptime v-model="lptime">
					</span>
				</span>
			</span><br class=form />

			<label for=timelimit class=form>Time Limit:</label>
			<span class=formright>
				<input type=text size=4 name=timelimit id=timelimit v-model="timelimit">
				minutes (blank or 0 for none)
				<span v-if="timelimit !== '' && timelimit > 0">
					<br/>
					<label>
						<input type="checkbox" name="allowovertime" v-model="allowovertime" />
						Allow student to work past time limit.
					</label>
					<span v-if="allowovertime">
						Grace period of
						<input type="text" size="3" name="overtimegrace" v-model="overtimegrace" />
						minutes with a penalty of
						<input type="text" size="2" name="overtimepenalty" v-model="overtimepenalty" />%
					</span>
				</span>
			</span><br class=form />

			<label class=form>Require Password (blank for none):</label>
			<span class=formright>
				<input :type="revealpw?'text':'password'" name="assmpassword"
					id="assmpassword" v-model="assmpassword" autocomplete="new-password">
				<a v-if="assmpassword != ''" href="#" @click.prevent="revealpw = !revealpw">
					{{ revealpw ? _('Hide') : _('Show') }}
				</a>
			</span><br class=form />

			<label for="reqscoreshowtype" class=form>Show based on another assessment: </label>
			<span class=formright>
				<select id="reqscoreshowtype" name="reqscoreshowtype" v-model="reqscoreshowtype">
					<option value="-1">No prerequisite</option>
					<option value="0">Show only after</option>
					<option value="1">Show greyed until</option>
				</select>
				<span v-show="reqscoreshowtype > -1">
					a score of
	 				<input type=text size=4 name=reqscore v-model="reqscore" />
					<select name="reqscorecalctype" v-model="reqscorecalctype">
						<option value="0">points</option>
						<option value="1">percent</option>
					</select>
					is obtained on
					<select name="reqscoreaid" v-model="reqscoreaid">
						<option v-for="option in reqscoreOptions" :value="option.value" :key="option.value">
							{{ option.text }}
						</option>
					</select>
				</span>
			</span><br class=form />
		</div>

		<div class="block grouptoggle">
			<img class="mida" src="../img/expand.gif" />
			Help and Hints
		</div>
		<div class="blockitems hidden">
			<span class=form>Hints and Videos</span>
			<span class=formright>
				<label>
					<input type="checkbox" name="showhints" value="1" v-model="showhints" />
					Show hints when available?
				</label>
				<br/>
				<label>
					<input type="checkbox" name="showextrefs" value="2" v-model="showextrefs" />
					Show video/text buttons when available?
				</label>
			</span><br class=form />

			<span class=form>"Ask question" links</span>
			<span class=formright>
				<label>
					<input type="checkbox" name="msgtoinstr" v-model="msgtoinstr"/>
					Show "Message instructor about this question" links
				</label>
				<br/>
				<label>
					<input type="checkbox" name="doposttoforum" v-model="doposttoforum" />
					Show "Post this question to forum" links
				</label>
			 	<span v-show="doposttoforum">
					to forum
					<select name="posttoforum" id="posttoforum" v-model="posttoforum">
						<option v-for="option in forumOptions" :value="option.value" :key="option.value">
							{{ option.text }}
						</option>
					</select>
				</span>
			</span><br class=form>

			<span class=form>Assessment resource links</span>
			<span class=formright>
				<span v-for="(extref,index) in extrefs" :key="index">
					<label>
						Label:
						<input name="extreflabels[]" v-model="extref.label" size="10" />
					</label>
					<label>
						Link:
						<input type="url" name="extreflinks[]" v-model="extref.link" size="28" />
					</label>
					<button type="button" @click="extrefs.splice(index,1)">
						Remove
					</button>
					<br/>
				</span>
				<button type="button" @click="addExtref">
					Add Resource
				</button>
			</span><br class=form>

			<label for="showtips" class=form>Show answer entry tips?</label>
			<span class=formright>
				<select name="showtips" id="showtips" v-model="showtips">
					<option value="0">No</option>
					<option value="2">Yes, under answerbox (strongly recommended)</option>
				</select>
			</span><br class=form />

		</div>

		<div class="block grouptoggle">
			<img class="mida" src="../img/expand.gif" />
			Grading and Feedback
		</div>
		<div class="blockitems hidden">
			<label for="cntingb" class=form>Count:</label>
			<span class=formright>
				<select name="cntingb" id="cntingb" v-model="cntingb">
					<option value="1">Count in Gradebook</option>
					<option value="0">Don't count in grade total and hide from students</option>
					<option value="3">Don't count in grade total</option>
					<option value="2">Count as Extra Credit</option>
				</select>
			</span><br class=form />

			<label for="minscore" class=form>Minimum score to receive credit:</label>
			<span class=formright>
				<input type=text size=4 name=minscore id=minscore v-model="minscore">
				<select name="minscoretype" v-model="minscoretype">
					<option value="0">Points</option>
					<option value="1">Percent</option>
				</select>
			</span><br class=form />

			<span class="form">Default Feedback Text:</span>
			<span class="formright">
				<label>
					<input type="checkbox" name="usedeffb" v-model="usedeffb">
					Use default feedback text
				<label>
				<span v-show="usedeffb">
					<br/>
					Text:
					<textarea name="deffb" v-model="deffb" rows="4" cols="60"></textarea>
				</span>
			</span><br class="form" />

			<div v-if="allowinstraddtutors">
				<label for="tutoredit" class="form">Tutor Access:</label>
				<span class="formright">
					<select name="tutoredit" id="tutoredit" v-model="tutoredit">
						<option value="2">No Access</option>
						<option value="0">View Scores</option>
						<option value="1">View and Edit Scores</option>
					</select>
				</span><br class="form" />
			</div>

			<label for="exceptionpenalty" class=form>
				Penalty for questions done while in exception/LatePass:
			</label>
			<span class=formright>
				<input type=text size=4 name="exceptionpenalty" id="exceptionpenalty"
				 	v-model="exceptionpenalty">%
			</span><br class=form />

			<label for="defoutcome" class="form">Default Outcome:</label>
			<span class="formright">
				<select name="defoutcome" id="defoutcome" v-model="defoutcome">
					<option value="0">No default outcome selected</option>
					<option v-for="option in outcomeOptions"
						:key="option.value"
						:value="option.value"
						:disabled="option.isgroup"
					>
						{{ option.text }}
					</option>
				</select>
			</span><br class=form />
		</div>

		<div class="block grouptoggle">
			<img class="mida" src="../img/expand.gif" />
			Group Assessment
		</div>
		<div class="blockitems hidden">

			<label for="isgroup" class=form>Group assessment: </label>
			<span class=formright>
				<select id="isgroup" name="isgroup" v-model="isgroup">
					<option value="0">Not a group assessment</option>
					<option value="2">Students create their own groups</option>
					<option value="3">Instructor created groups</option>
				</select>
			</span><br class="form" />

			<div v-show="isgroup>0">
				<label for="groupmax" class=form>Max group members:</label>
				<span class=formright>
					<input type="number" size=3 min=2 max=999
					 	name="groupmax" id="groupmax" v-model="groupmax"/>
				</span><br class="form" />

				<label for="groupsetid" class="form">Use group set:</label>
				<span class=formright>
					<span v-if="!canchangegroup">
						Cannot change group set after the assessment has started
						<br/>
					</span>
					<select id="groupsetid" name="groupsetid" v-model="groupsetid"
						:disabled="!canchangegroup"
					>
						<option v-for="option in groupOptions"
						:value="option.value"
						:key="option.value"
						>
							{{ option.text }}
						</option>
					</select>
				</span><br class="form" />
			</div>
		</div>

	</fieldset>
	<div v-if="showDisplayDialog" class="fullwrap">
		<div class="dialog-overlay">
			<div class="dialog" role="dialog" aria-modal="true" aria-labelledby="dialoghdr">
	      <div class="pane-header flexrow">
	        <div style="flex-grow: 1" id="dialoghdr">
	          Display Styles
	        </div>
	        <button
	          type = "button"
	          class = "plain slim"
	          aria-label = "Close"
	          @click = "closeDisplayDialog"
						@keydown.tab.prevent
	        >
	          X
	        </button>
	      </div>
	      <div class="pane-body">
					<p><strong>One question at a time</strong>: Students will
						see one question at a time, and can jump between them in any order</p>
					<p><strong>All questions at once, or in pages</strong>: In this style,
						students will typically see all the questions on the screen at once.
						If desired, you can break the questions into pages on the Add/Remove
						Questions page by clicking the +Text button and selecting the New Page
						option.</p>
					<p><strong>Video Cued</strong>: In this style, the questions pop up
						automatically at specified times while watching a YouTube video. On the
						Add/Remove Questions page, after adding the questions to the assessment,
						click Define Video Cues to specify the video and times to display the
						questions.</p>
					<?php if (isset($CFG['GEN']['livepollserver'])) { ?>
					<p><strong>LivePoll</strong>: This is a clicker-style display, requiring
						students to be in the assessment at the same time as the teacher. The
						teacher opens a question for students to answer, and results can be
						viewed live as they are submitted.</p>
					<?php } ?>
	      </div>
			</div>
    </div>
	</div>
</div>
<script type="text/javascript">
var app = new Vue({
	el: '#app',
  data: <?php echo json_encode($vueData); ?>,
	computed: {
		showscoresOptions() {
			var during = {
				'value': 'during',
				'text': _('On each question immediately')
			};
			var at_end = {
				'value': 'at_end',
				'text': _('At the end of the assessment')
			};
			var total = {
				'value': 'total',
				'text': _('Total score only at the end')
			};
			var none = {
				'value': 'none',
				'text': _('No scores at all')
			};

			var out = [];
			if (this.defattempts == 1 && this.subtype != 'by_question') {
				// if we only have 1 try, and not HW mode, show all options
				out = [during, at_end, total, none];
			} else if ((this.subtype == 'by_question' && this.defregens>1) ||
			 	(this.defattempts > 1 && this.subtype != 'by_question')
			) {
				// if we're in HW mode, and allowing multiple versions, must show score immediately
				// likewise if in quiz mode and allow multiple tries
				out = [during];
			} else {
				// otherwise, give option of immediately (typical) or no scores shown
				out = [during, none];
			}
			if (!this.valueInOptions(out, this.showscores)) {
				this.showscores = out[0].value;
			}
			return out;
		},
		showansOptions() {
			//TODO: revisit after_take vs with_score

			var never = {
				'value': 'never',
				'text': _('Never')
			};
			var with_score = {
				'value': 'with_score',
				'text': _('Show with the score')
			};

			var out = [];
			if (this.showscores == 'during' && this.defattempts == 1) {
				// when showing scores immediately and 1 try
				out = [with_score, never];
			} else if (this.showscores == 'during' && this.defattempts > 1) {
				// when showing scores immediately and n tries
				out = [
					{
						'value': 'after_lastattempt',
						'text': _('After the last try on a question')
					},
					{
						'value': 'jump_to_answer',
						'text': _('After the last try or Jump to Answer button')
					},
					never
				];
				for (var i=1; i<Math.min(9,this.defattempts);i++) {
					out.push({
						'value': 'after_'+i,
						'text': i>1 ? _('After %d tries').replace(/%d/, i) :
													_('After 1 try')
					});
				}
			} else if (this.showscores == 'at_end') {
				// for showing scores at end: after_attempt or never
				out = [
					{
						'value': 'after_take',
						'text': _('After the assessment version is submitted')
					},
					never
				];
			}
			if (out.length === 0) {
				this.showans = 'never';
			} else if (!this.valueInOptions(out, this.showans)) {
				this.showans = out[0].value;
			}
			return out;
		},
		viewInGbOptions() {
			/*
			‘immediately’: Immediately - can always view it
			‘after_take’: After an assessment version is done
			‘after_due’: After it’s due
			‘never’: Never
			 */
			var out = [
				{
					'value': 'after_due',
					'text': _('After the due date')
				},
				{
					'value': 'immediately',
					'text': _('Immediately - they can always view it')
				},
				{
					'value': 'never',
					'text': _('Never')
				}
			];
			if (this.subtype == 'by_assessment') {
				out.unshift({
					'value': 'after_take',
					'text': _('After the assessment version is submitted')
				})
			}
			if (!this.valueInOptions(out, this.viewingb)) {
				this.viewingb = out[0].value;
			}
			return out;
		},
		scoresInGbOptions() {
			/*
			‘immediately’: Immediately - can always view it
			‘after_take’: After an assessment version is done
			‘after_due’: After the due date
			‘never’: Never
			 */

			/*
			If showscores = 'during', then scores should show in GB immediately
				Unless Quiz-style, then after-take
			If showscores = 'at_end', then scores should show in GB after_take
			If showscores = 'total', then select 'after_take', 'after_due', or 'never' (?)
				What if we want to only allow viewing total, and NEVER see score details?
				Then GB would need to look at showscores as well as scoresingb
			If showscores = 'never', then select 'after_take', 'after_due', or 'never'
			 */

			var out = [
				{
					'value': 'after_due',
					'text': _('After the due date')
				},
				{
					'value': 'never',
					'text': _('Never')
				}
			];
			if (this.showscores !== 'during' && this.showscores !== 'at_end' &&
					this.subtype == 'by_assessment'
			) {
				out.unshift({
					'value': 'after_take',
					'text': _('After the assessment version is submitted')
				});
			}

			if (this.showscores == 'during' && this.subtype == 'by_question') {
				out = [{
					'value': 'immediately',
					'text': _('Immediately')
				}];
			} else if (this.showscores == 'at_end' ||
					(this.showscores == 'during' && this.subtype == 'by_assessment')
			) {
				out = [{
					'value': 'after_take',
					'text': _('After the assessment version is submitted')
				}];
			}
			if (!this.valueInOptions(out, this.scoresingb)) {
				this.scoresingb = out[0].value;
			}
			return out;

		},
		ansInGbOptions() {
			/*
			‘after_attempt’: After an assessment version is done
			‘after_due’: After it’s due
			‘never’: Never
			 */
			if (this.viewingb == 'never' || this.scoresingb == 'never') {
				this.ansingb = 'never';
 				return [];
 			} else {
 				var out = [
 					{
 						'value': 'after_due',
 						'text': _('After the due date')
 					},
 					{
 						'value': 'never',
 						'text': _('Never')
 					}
 				];
 				if ((this.scoresingb === 'immediately' || this.scoresingb === 'after_take')
				 	&& this.subtype == 'by_assessment'
				) {
 					out.unshift({
 						'value': 'after_take',
 						'text': _('After the assessment version is submitted')
 					});
 				}
				if (!this.valueInOptions(out, this.ansingb)) {
					this.ansingb = out[0].value;
				}
				return out;
 			}
		}
	},
	methods: {
		valueInOptions(optArr, value) {
			var i;
			for (i in optArr) {
				if (optArr[i].value == value) {
					return true;
				}
			}
			return false;
		},
		addExtref() {
			this.extrefs.push({'label':'', 'link':''});
			this.extrefs = this.extrefs.slice();
		},
		doShowDisplayDialog() {
			this.showDisplayDialog = true;
			this.$nextTick(function() {
				$(".dialog .pane-header button").focus();
			});
			var self = this;
			$(document).on('keyup', function(e) {
				if (e.key == 'Escape') {
					self.closeDisplayDialog();
				}
			})
		},
		closeDisplayDialog() {
			this.showDisplayDialog = false;
			$("#dispdetails").focus();
		}
	}
});
</script>
