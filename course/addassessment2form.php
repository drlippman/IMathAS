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
	'datesbylti' => intval($line['date_by_lti']),
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
	'shuffle' => ($line['shuffle']&(1+16+32)),
	'noprint' => $line['noprint'] > 0,
	'sameseed' => ($line['shuffle']&2) > 0,
	'samever' => ($line['shuffle']&4) > 0,
	'istutorial' => $line['istutorial'] > 0,
	'showcat' => $line['showcat'] > 0,
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
	'showwork' => $line['showwork'],
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
	'groupmax' => ($line['groupmax'] > 1) ? $line['groupmax'] : 6,
	'canchangegroup' => !($taken && $line['isgroup']>0),
	'groupsetid' => $line['groupsetid'],
	'groupOptions' => $groupOptions,
	'reqscoreshowtype' => $reqscoredisptype,
	'reqscore' => abs($line['reqscore']),
	'reqscorecalctype' => ($line['reqscoretype']&2) > 0 ? 1 : 0,
	'reqscoreaid' => $line['reqscoreaid'],
	'reqscoreOptions' => $otherAssessments,
	'copyfrom' => 0,
	'taken' => $taken,
	'showDisplayDialog' => false
);

// skipmathrender class is needed to prevent katex parser from mangling
// Vue template
?>
<div id="app" class="skipmathrender" v-cloak>
	<span class=form><?php echo _('Assessment Name');?>:</span>
	<span class=formright>
		<input type=text size=30 name=name v-model="name" required>
	</span><br class=form />

	<?php echo _('Summary');?>:<br/>
	<div class=editor>
		<textarea cols=50 rows=15 id=summary name=summary v-model="summary" style="width: 100%"></textarea>
	</div><br class=form />

	<?php echo _('Intro/Instructions');?>:<br/>
	<?php if (isset($introconvertmsg)) {echo $introconvertmsg;} ?>
	<div class=editor>
		<textarea cols=50 rows=20 id=intro name=intro v-model="intro" style="width: 100%"></textarea>
	</div><br class=form />

	<span class=form><?php echo _('Show');?>:</span>
	<span class=formright>
		<label>
			<input type=radio name="avail" value="0" v-model="avail" />
			<?php echo _('Hide');?>
		</label><br/>
		<label>
			<input type=radio name="avail" value="1" v-model="avail"/>
			<?php echo _('Show by Dates');?>
		</label>
	</span><br class="form"/>

	<div v-show="avail==1 && datesbylti==0">
		<span class=form><?php echo _('Available After');?>:</span>
		<span class=formright>
			<label>
				<input type=radio name="sdatetype" value="0" v-model="sdatetype" />
				<?php echo _('Available always until end date');?>
			</label><br/>
			<label>
				<input type=radio name="sdatetype" value="sdate" v-model="sdatetype"/>
				<?php echo _('Available after');?>
			</label>
			<input type=text size=10 name="sdate" v-model="sdate">
			<a href="#" onClick="displayDatePicker('sdate', this); return false">
			<img src="<?php echo $staticroot;?>/img/cal.gif" alt="Calendar"/></a>
			at <input type=text size=8 name=stime v-model="stime">
		</span><br class="form"/>

		<span class=form><?php echo _('Available Until');?>:</span>
		<span class=formright>
			<label>
				<input type=radio name="edatetype" value="2000000000" v-model="edatetype" />
				<?php echo _('Available always after start date');?>
			</label><br/>
			<label>
				<input type=radio name="edatetype" value="edate" v-model="edatetype"/>
				<?php echo _('Due');?>
			</label>
			<input type=text size=10 name="edate" v-model="edate">
			<a href="#" onClick="displayDatePicker('edate', this, 'sdate', '<?php echo _('Start date');?>'); return false">
			<img src="<?php echo $staticroot;?>/img/cal.gif" alt="Calendar"/></a>
			<?php echo _('at') ?> <input type=text size=8 name=etime v-model="etime">
		</span><br class="form"/>
	</div>
	<div v-show="avail==1 && datesbylti>0">
		<span class=form><?php echo _('Due date');?></span>
		<span class=formright>
			<?php echo _('The course setting is enabled for dates to be set via LTI');?>.<br/>
			<span v-if="datesbylti==1">
				<?php echo _('Waiting for the LMS to send a date');?>
			</span>
			<span v-else-if="enddate == 2000000000">
				<?php echo _('Default due date set by LMS: No due date (individual student due dates may vary)');?>
			</span>
			<span v-else>
				<?php echo _('Default due date set by LMS');?>: {{ edate + ' ' + etime}}
				<?php echo _('(individual student due dates may vary)');?>
			</span>
		</span><br class=form />
	</div>

	<div v-show="avail==1 && edatetype=='edate'">
		<span class=form><?php echo _('Practice mode');?>:</span>
		<span class=formright>
			<label>
				<input type=checkbox name="allowpractice" value="true" v-model="allowpractice"/>
				<?php echo _('Keep open for un-graded practice after the due date');?>
			</label>
		</span><br class=form />
	</div>

	<fieldset>
		<legend><?php echo _('Assessment Options');?></legend>
		<div v-if="reqscoreOptions.length > 0">
			<label class=form for="copyfrom">
				<?php echo _('Copy Options from');?>:
			</label>
			<span class=formright>
				<select name="copyfrom" v-model="copyfrom">
					<option value="0"><?php echo _('None - use settings below');?></option>
					<option v-for="option in reqscoreOptions" :value="option.value" :key="option.value">
						{{ option.text }}
					</option>
				</select>
				<span v-if="taken && copyfrom > 0" class="noticetext">
					<br/>
					<?php echo _('Warning: Changing settings after students have started may require converting their data, and lead to loss of data on earlier attempts. This will occur if converting between Homework-style and Quiz-style.');?>
				</span>
			</span><br class=form />
		</div>
		<div v-show="copyfrom > 0">
			<span class=form>Also copy:</span>
			<span class=formright>
				<input type=checkbox name="copysummary" value=1 /> <?php echo _('Summary');?><br/>
				<input type=checkbox name="copyinstr" value=1 /> <?php echo _('Intro/Instructions');?><br/>
				<input type=checkbox name="copydates" value=1 /> <?php echo _('Dates');?> <br/>
				<input type=checkbox name="copyendmsg" value=1 /> <?php echo _('End of Assessment Messages');?>
			</span><br class=form />
		</div>
		<div v-show="copyfrom == 0">
			<hr v-if="reqscoreOptions.length > 0" />
		<div>
			<a href="#" onclick="groupToggleAll(1);return false;"><?php echo _('Expand All');?></a>
	 		<a href="#" onclick="groupToggleAll(0);return false;"><?php echo _('Collapse All');?></a>
		</div>
		<div class="block grouptoggle">
			<img class="mida" src="<?php echo $staticroot;?>/img/collapse.gif" />
			<?php echo _('Core Options');?>
		</div>
		<div class="blockitems">
			<label class=form for="displaymethod"><?php echo _('Display style');?>:</label>
			<span class=formright>
				<select name="displaymethod" id=displaymethod v-model="displaymethod">
					<option value="skip"><?php echo _('One question at a time');?></option>
					<option value="full"><?php echo _('All questions at once, or in pages');?></option>
					<option value="video_cued"><?php echo _('Video Cued');?></option>
					<?php if (isset($CFG['GEN']['livepollserver'])) {
						echo '<option value="livepoll">',_('Live Poll'),'</option>';
					}?>
				</select>
				<a href="#" id="dispdetails" @click.prevent="doShowDisplayDialog"><?php echo _('Details');?></a>
			</span><br class=form />

			<label class="form" for="subtype"><?php echo _('Submission type');?>:</label>
			<span class="formright">
				<select name="subtype" id="subtype" v-model="subtype">
					<option value="by_question"><?php echo _('Homework-style: new versions of individual questions');?></option>
					<option value="by_assessment"><?php echo _('Quiz-style: retake whole assessment with new versions');?></option>
				</select>
				<span v-if="taken" class="noticetext">
					<br/>
					<?php echo _('Warning: Changing this after students have started will require converting their data, and lead to loss of data on earlier attempts.');?>
				</span>
			</span><br class=form />


			<span class=form><?php echo _('Versions');?>:</span>
			<span class=formright>

				<label for="defregens" v-show="subtype == 'by_question'">
					<?php echo _('Number of versions for each question');?>:
				</label>
				<label for="defregens" v-show="subtype == 'by_assessment'">
					<?php echo _('Number of times assessment can be taken');?>:
				</label>
				<input type=number min=1 max=100 size=3 id="defregens"
					name="defregens" v-model.number="defregens" />
				<span v-if="defregens > 1">
					<br/>
					<?php echo _('With a penalty of');?>
					<input type=number min=0 max=100 size=3 id="defregenpenalty"
						name="defregenpenalty" v-model.number="defregenpenalty" />%
					<?php echo _('per version');?>
					<span v-show="defregenpenalty>0">
						<?php echo _('after');?>
						<input type=number min=1 :max="Math.min(defregens,9)" size=3 id="defregenpenaltyaftern"
							name="defregenpenaltyaftern" v-model.number="defregenpenaltyaftern" />
						<?php echo _('full-credit versions');?>
					</span>
					<br/>
					<span v-if="subtype == 'by_assessment'">
						<label for="keepscore">
							<?php echo _('Score to keep');?>:
						</label>
						<select id="keepscore" name="keepscore" v-model="keepscore">
							<option value="best"><?php echo _('Best');?></option>
							<option value="last"><?php echo _('Last');?></option>
							<option value="average"><?php echo _('Average');?></option>
						</select>
					</span>
				</span>
			</span><br class=form />


			<span class=form><?php echo _('Tries');?>:</span>
			<span class=formright>
				<label for="defattempts">
					<?php echo _('Number of tries on each version of a question');?>:
				</label>
				<input type=number min=1 max=100 size=3 id="defattempts"
					name="defattempts" v-model.number="defattempts" />
				<span v-if="defattempts>1">
					<br/>
					<?php echo _('With a penalty of');?>
					<input type=number min=0 max=100 size=3 id="defattemptpenalty"
						name="defattemptpenalty" v-model.number="defattemptpenalty" />%
					<?php echo _('per try');?>
					<span v-show="defattemptpenalty>0">
						<?php echo _('after');?>
						<input type=number min=1 :max="Math.min(defattempts,9)" size=3 id="defattemptpenaltyaftern"
							name="defattemptpenaltyaftern" v-model.number="defattemptpenaltyaftern" />
						<?php echo _('full-credit tries');?>
					</span>
				</span>
			</span><br class=form />

			<label class="form" for="showscores">
				<?php echo _('During assessment, show scores');?>:
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
					<?php echo _('During assessment, show answers');?>:
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
				<?php echo _('Students can view their work in the gradebook');?>:
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
					<?php echo _('Students can view their scores in the gradebook');?>:
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
					<?php echo _('Students can view correct answers in the gradebook');?>:
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
				<?php echo _('Gradebook Category');?>:
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
			<img class="mida" src="<?php echo $staticroot;?>/img/expand.gif" />
			<?php echo _('Additional Display Options');?>
		</div>
		<div class="blockitems hidden">
			<label class="form" for="caltag"><?php echo _('Calendar icon');?>:</label>
			<span class="formright">
                <label><input name="caltagradio" type="radio" value="usetext" <?php writeHtmlChecked($line['caltag'],"use_name",1); ?>><?php echo _('Use Text');?>:</label>
                 <input aria-label="<?php echo _('Calendar icon text');?>" name="caltag" id="caltag" v-model="caltag" type=text size=8 <?php echo ($line['caltag'] == 'use_name') ? 'style="color:#FFFFFF;opacity:0.6;" readonly' : null ?> /> <br />
				<label><input name="caltagradio" type="radio" value="usename" <?php writeHtmlChecked($line['caltag'],"use_name"); ?>><?php echo _('Use Assessment Name');?></label>
			</span><br class="form" />

			<label class=form for="shuffle"><?php echo _('Shuffle item order');?>:</label>
			<span class=formright>
				<select name="shuffle" id="shuffle" v-model="shuffle">
					<option value="0"><?php echo _('No');?></option>
					<option value="1"><?php echo _('All');?></option>
                    <option value="16"><?php echo _('All but first');?></option>
                    <option value="32"><?php echo _('All but last');?></option>
                    <option value="48"><?php echo _('All but first and last');?></option>
				</select>
			</span><br class=form />

			<label class=form for="showwork"><?php echo _('Provide "Show Work" boxes');?>:</label>
			<span class=formright>
				<select name="showwork" id="showwork" v-model="showwork">
					<option value="0"><?php echo _('No');?></option>
					<option value="1"><?php echo _('During assessment');?></option>
					<option value="2"><?php echo _('After assessment');?></option>
					<option value="3"><?php echo _('During or after assessment');?></option>
				</select>
			</span><br class=form />

			<span class=form><?php echo _('Options');?></span>
			<span class=formright>
				<label>
					<input type="checkbox" value="1" name="noprint" v-model="noprint" />
					<?php echo _('Make hard to print');?>
				</label>
				<label v-show="subtype != 'by_question' || defregens==1">
					<br/>
					<input type="checkbox" value="2" name="sameseed" v-model="sameseed" />
					<?php echo _('All items same random seed');?>
				</label>
				<br/>
				<label>
					<input type="checkbox" value="4" name="samever" v-model="samever" />
					<?php echo _('All students same version of questions');?>
				</label>
				<br/>
				<label>
					<input type="checkbox" value="1" name="istutorial" v-model="istutorial" />
					<?php echo _('Suppress default score result display');?>
				</label>
				<br/>
				<label>
					<input type="checkbox" value="1" name="showcat" v-model="showcat" />
					<?php echo _('Show question categories in Question Details (if defined)');?>
				</label>
			</span><br class=form />
		</div>

		<div class="block grouptoggle">
			<img class="mida" src="<?php echo $staticroot;?>/img/expand.gif" />
			<?php echo _('Time Limit and Access Control');?>
		</div>
		<div class="blockitems hidden">
			<label for="allowlate" class=form><?php echo _('Allow use of LatePasses?');?>:</label>
			<span class=formright>
				<select name="allowlate" id="allowlate" v-model="allowlate">
					<option value="0"><?php echo _('None');?></option>
					<option value="1"><?php echo _('Unlimited');?></option>
					<option value="2"><?php echo _('Up to 1');?></option>
					<option value="3"><?php echo _('Up to 2');?></option>
					<option value="4"><?php echo _('Up to 3');?></option>
					<option value="5"><?php echo _('Up to 4');?></option>
					<option value="6"><?php echo _('Up to 5');?></option>
					<option value="7"><?php echo _('Up to 6');?></option>
					<option value="8"><?php echo _('Up to 7');?></option>
					<option value="9"><?php echo _('Up to 8');?></option>
				</select>
				<span v-show="allowlate > 0">
					<label>
						<input type="checkbox" name="latepassafterdue" v-model="latepassafterdue">
						<?php echo _('Allow LatePasses after due date');?>
					</label>
					<br/>
					<label>
						<input type="checkbox" name="dolpcutoff" value="1" v-model="dolpcutoff" />
						<?php echo _('Restrict by date');?>.
					</label>
					<span v-show="dolpcutoff">
						<?php echo _('No extensions past');?>
						<input type=text size=10 name="lpdate" v-model="lpdate">
						<a href="#" onClick="displayDatePicker('lpdate', this); return false">
						<img src="<?php echo $staticroot;?>/img/cal.gif" alt="Calendar"/></A>
						at <input type=text size=8 name=lptime v-model="lptime">
					</span>
				</span>
			</span><br class=form />

			<label for=timelimit class=form><?php echo _('Time Limit');?>:</label>
			<span class=formright>
				<input type=text size=4 name=timelimit id=timelimit v-model="timelimit">
				<?php echo _('minutes (blank or 0 for none)');?>
				<span v-if="timelimit !== '' && timelimit > 0">
					<br/>
					<label>
						<input type="checkbox" name="allowovertime" v-model="allowovertime" />
						<?php echo _('Allow student to work past time limit');?>.
					</label>
					<span v-if="allowovertime">
						<?php echo _('Grace period of');?>
						<input type="text" size="3" name="overtimegrace" v-model="overtimegrace" />
						<?php echo _('minutes with a penalty of');?>
						<input type="text" size="2" name="overtimepenalty" v-model="overtimepenalty" />%
					</span>
				</span>
			</span><br class=form />

			<label class=form><?php echo _('Require Password (blank for none)');?>:</label>
			<span class=formright>
				<input :type="revealpw?'text':'password'" name="assmpassword"
					id="assmpassword" v-model="assmpassword" autocomplete="new-password">
				<a v-if="assmpassword != ''" href="#" @click.prevent="revealpw = !revealpw">
					{{ revealpw ? '<?php echo _('Hide');?>' : '<?php echo _('Show');?>' }}
				</a>
			</span><br class=form />

			<label for="reqscoreshowtype" class=form><?php echo _('Show based on another assessment');?>: </label>
			<span class=formright>
				<select id="reqscoreshowtype" name="reqscoreshowtype" v-model="reqscoreshowtype">
					<option value="-1"><?php echo _('No prerequisite');?></option>
					<option value="0"><?php echo _('Show only after');?></option>
					<option value="1"><?php echo _('Show greyed until');?></option>
				</select>
				<span v-show="reqscoreshowtype > -1">
					<?php echo _('a score of');?>
	 				<input type=text size=4 name=reqscore v-model="reqscore" />
					<select name="reqscorecalctype" v-model="reqscorecalctype">
						<option value="0"><?php echo _('points');?></option>
						<option value="1"><?php echo _('percent');?></option>
					</select>
					<?php echo _('is obtained on');?>
					<select name="reqscoreaid" v-model="reqscoreaid">
						<option v-for="option in reqscoreOptions" :value="option.value" :key="option.value">
							{{ option.text }}
						</option>
					</select>
				</span>
			</span><br class=form />
		</div>

		<div class="block grouptoggle">
			<img class="mida" src="<?php echo $staticroot;?>/img/expand.gif" />
			<?php echo _('Help and Hints');?>
		</div>
		<div class="blockitems hidden">
			<span class=form><?php echo _('Hints and Videos');?></span>
			<span class=formright>
				<label>
					<input type="checkbox" name="showhints" value="1" v-model="showhints" />
					<?php echo _('Show hints when available?');?>
				</label>
				<br/>
				<label>
					<input type="checkbox" name="showextrefs" value="2" v-model="showextrefs" />
					<?php echo _('Show video/text buttons when available?');?>
				</label>
			</span><br class=form />

			<span class=form><?php echo _('"Ask question" links');?></span>
			<span class=formright>
				<label>
					<input type="checkbox" name="msgtoinstr" v-model="msgtoinstr"/>
					<?php echo _('Show "Message instructor about this question" links');?>
				</label>
				<br/>
				<label>
                    <input type="checkbox" name="doposttoforum" v-model="doposttoforum" 
                        :disabled="forumOptions.length == 0"/>
                    <?php echo _('Show "Post this question to forum" links');?>
                    <span v-if="forumOptions.length == 0" class="small">
                        <?php echo _('(Create a forum first to enable this)'); ?>
                    </span>
				</label>
			 	<span v-show="doposttoforum">
					<?php echo _('to forum');?>
					<select name="posttoforum" id="posttoforum" v-model="posttoforum">
						<option v-for="option in forumOptions" :value="option.value" :key="option.value">
							{{ option.text }}
						</option>
					</select>
				</span>
			</span><br class=form>

			<span class=form><?php echo _('Assessment resource links');?></span>
			<span class=formright>
				<span v-for="(extref,index) in extrefs" :key="index">
					<label>
						<?php echo _('Label');?>:
						<input name="extreflabels[]" v-model="extref.label" size="10" />
					</label>
					<label>
						<?php echo _('Link');?>:
						<input type="url" name="extreflinks[]" v-model="extref.link" size="28" />
					</label>
					<button type="button" @click="extrefs.splice(index,1)">
						<?php echo _('Remove');?>
					</button>
					<br/>
				</span>
				<button type="button" @click="addExtref">
					<?php echo _('Add Resource');?>
				</button>
			</span><br class=form>

			<label for="showtips" class=form><?php echo _('Show answer entry tips?');?></label>
			<span class=formright>
				<select name="showtips" id="showtips" v-model="showtips">
					<option value="0"><?php echo _('No');?></option>
					<option value="2"><?php echo _('Yes, under answerbox (strongly recommended)');?></option>
				</select>
			</span><br class=form />

		</div>

		<div class="block grouptoggle">
			<img class="mida" src="<?php echo $staticroot;?>/img/expand.gif" />
			<?php echo _('Grading and Feedback');?>
		</div>
		<div class="blockitems hidden">
			<label for="cntingb" class=form><?php echo _('Count');?>:</label>
			<span class=formright>
				<select name="cntingb" id="cntingb" v-model="cntingb">
					<option value="1"><?php echo _('Count in Gradebook');?></option>
					<option value="0"><?php echo _('Don\'t count in grade total and hide from students');?></option>
					<option value="3"><?php echo _('Don\'t count in grade total');?></option>
					<option value="2"><?php echo _('Count as Extra Credit');?></option>
				</select>
			</span><br class=form />

			<label for="minscore" class=form><?php echo _('Minimum score to receive credit');?>:</label>
			<span class=formright>
				<input type=text size=4 name=minscore id=minscore v-model="minscore">
				<select name="minscoretype" v-model="minscoretype">
					<option value="0"><?php echo _('Points');?></option>
					<option value="1"><?php echo _('Percent');?></option>
				</select>
			</span><br class=form />

			<span class="form"><?php echo _('Default Feedback Text');?>:</span>
			<span class="formright">
				<label>
					<input type="checkbox" name="usedeffb" v-model="usedeffb">
					<?php echo _('Use default feedback text');?>
				</label>
				<span v-show="usedeffb">
					<br/>
					<?php echo _('Text');?>:
					<textarea name="deffb" v-model="deffb" rows="4" cols="60"></textarea>
				</span>
			</span><br class="form" />

			<div v-if="allowinstraddtutors">
				<label for="tutoredit" class="form"><?php echo _('Tutor Access');?>:</label>
				<span class="formright">
					<select name="tutoredit" id="tutoredit" v-model="tutoredit">
						<option value="2"><?php echo _('No Access');?></option>
						<option value="0"><?php echo _('View Scores');?></option>
						<option value="1"><?php echo _('View and Edit Scores');?></option>
					</select>
				</span><br class="form" />
			</div>

			<label for="exceptionpenalty" class=form>
				<?php echo _('Penalty for questions done while in exception/LatePass');?>:
			</label>
			<span class=formright>
				<input type=text size=4 name="exceptionpenalty" id="exceptionpenalty"
				 	v-model="exceptionpenalty">%
			</span><br class=form />

			<label for="defoutcome" class="form"><?php echo _('Default Outcome');?>:</label>
			<span class="formright">
				<select name="defoutcome" id="defoutcome" v-model="defoutcome">
					<option value="0"><?php echo _('No default outcome selected');?></option>
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
			<img class="mida" src="<?php echo $staticroot;?>/img/expand.gif" />
			<?php echo _('Group Assessment');?>
		</div>
		<div class="blockitems hidden">

			<label for="isgroup" class=form><?php echo _('Group assessment');?>: </label>
			<span class=formright>
				<select id="isgroup" name="isgroup" v-model="isgroup">
					<option value="0"><?php echo _('Not a group assessment');?></option>
					<option value="2"><?php echo _('Students create their own groups');?></option>
					<option value="3"><?php echo _('Instructor created groups');?></option>
				</select>
			</span><br class="form" />

			<div v-show="isgroup>0">
				<label for="groupmax" class=form><?php echo _('Max group members');?>:</label>
				<span class=formright>
					<input type="number" size=3 min=2 max=999
					 	name="groupmax" id="groupmax" v-model="groupmax"/>
				</span><br class="form" />

				<label for="groupsetid" class="form"><?php echo _('Use group set');?>:</label>
				<span class=formright>
					<span v-if="!canchangegroup">
						<?php echo _('Cannot change group set after the assessment has started');?>
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
		</div>
	</fieldset>
	<div v-if="showDisplayDialog" class="fullwrap">
		<div class="dialog-overlay">
			<div class="dialog" role="dialog" aria-modal="true" aria-labelledby="dialoghdr">
	      <div class="pane-header flexrow">
	        <div style="flex-grow: 1" id="dialoghdr">
	          <?php echo _('Display Styles');?>
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
					<p><strong><?php echo _('One question at a time');?></strong>: <?php echo _('Students will see one question at a time, and can jump between them in any order'); ?></p>
					<p><strong><?php echo _('All questions at once, or in pages');?></strong>: <?php echo _('In this style,	students will typically see all the questions on the screen at once. If desired, you can break the questions into pages on the Add/Remove Questions page by clicking the +Text button and selecting the New Page option.'); ?></p>
					<p><strong><?php echo _('Video Cued');?></strong>: <?php echo _('In this style, the questions pop up automatically at specified times while watching a YouTube video. On the Add/Remove Questions page, after adding the questions to the assessment, click Define Video Cues to specify the video and times to display the	questions.'); ?></p>
					<?php if (isset($CFG['GEN']['livepollserver'])) { ?>
					<strong><?php echo _('LivePoll'); ?></strong>: <?php echo _('This is a clicker-style display, requiring students to be in the assessment at the same time as the teacher. The teacher opens a question for students to answer, and results can be viewed live as they are submitted.'); ?>
					</p>
					<?php } ?>
	      </div>
			</div>
    </div>
	</div>
</div>
<script type="text/javascript">
var app = new Vue({
	el: '#app',
  data: <?php echo json_encode($vueData, JSON_INVALID_UTF8_IGNORE); ?>,
	computed: {
		showscoresOptions: function() {
			var during = {
				'value': 'during',
				'text': '<?php echo _('On each question immediately');?>'
			};
			var at_end = {
				'value': 'at_end',
				'text': '<?php echo _('At the end of the assessment');?>'
			};
			var total = {
				'value': 'total',
				'text': '<?php echo _('Total score only at the end');?>'
			};
			var none = {
				'value': 'none',
				'text': '<?php echo _('No scores at all');?>'
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
		showansOptions: function() {
			//TODO: revisit after_take vs with_score

			var never = {
				'value': 'never',
				'text': '<?php echo _('Never');?>'
			};
			var with_score = {
				'value': 'with_score',
				'text': '<?php echo _('After the last try on a question');?>'
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
						'text': '<?php echo _('After the last try on a question');?>'
					},
					{
						'value': 'jump_to_answer',
						'text': '<?php echo _('After the last try or Jump to Answer button');?>'
					},
					never
				];
				for (var i=1; i<Math.min(9,this.defattempts);i++) {
					out.push({
						'value': 'after_'+i,
						'text': i>1 ? '<?php echo _('After %d tries');?>'.replace(/%d/, i) :
													'<?php echo _('After 1 try');?>'
					});
				}
			} else if (this.showscores == 'at_end') {
				// for showing scores at end: after_attempt or never
				out = [
					{
						'value': 'after_take',
						'text': '<?php echo _('After the assessment version is submitted');?>'
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
		viewInGbOptions: function() {
			/*
			‘immediately’: Immediately - can always view it
			‘after_take’: After an assessment version is done
			‘after_due’: After it’s due
			‘never’: Never
			 */
			var out = [
				{
					'value': 'after_due',
					'text': '<?php echo _('After the due date');?>'
				},
				{
					'value': 'immediately',
					'text': '<?php echo _('Immediately - they can always view it');?>'
				},
				{
					'value': 'never',
					'text': '<?php echo _('Never');?>'
				}
			];
			if (this.subtype == 'by_assessment') {
				out.unshift({
					'value': 'after_take',
					'text': '<?php echo _('After the assessment version is submitted');?>'
				})
			}
			if (!this.valueInOptions(out, this.viewingb)) {
				this.viewingb = out[0].value;
			}
			return out;
		},
		scoresInGbOptions: function() {
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
					'text': '<?php echo _('After the due date');?>'
				},
				{
					'value': 'never',
					'text': '<?php echo _('Never');?>'
				}
			];
			if (this.showscores !== 'during' && this.showscores !== 'at_end' &&
					this.subtype == 'by_assessment'
			) {
				out.unshift({
					'value': 'after_take',
					'text': '<?php echo _('After the assessment version is submitted');?>'
				});
			}

			if (this.showscores == 'during' && this.subtype == 'by_question') {
				out = [{
					'value': 'immediately',
					'text': '<?php echo _('Immediately');?>'
				}];
			} else if (this.showscores == 'at_end' ||
					(this.showscores == 'during' && this.subtype == 'by_assessment')
			) {
				out = [{
					'value': 'after_take',
					'text': '<?php echo _('After the assessment version is submitted');?>'
				}];
			}
			if (!this.valueInOptions(out, this.scoresingb)) {
				this.scoresingb = out[0].value;
			}
			return out;

		},
		ansInGbOptions: function() {
			/*
			‘after_attempt’: After an assessment version is done
			‘after_due’: After it’s due
			‘never’: Never
			 */
			if (this.viewingb == 'never') {
				this.ansingb = 'never';
 				return [];
 			} else {
 				var out = [
 					{
 						'value': 'after_due',
 						'text': '<?php echo _('After the due date');?>'
 					},
 					{
 						'value': 'never',
 						'text': '<?php echo _('Never');?>'
 					}
 				];
 				if (this.viewingb === 'after_take' && this.subtype == 'by_assessment') {
 					out.unshift({
 						'value': 'after_take',
 						'text': '<?php echo _('After the assessment version is submitted');?>'
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
		initCalTagRadio: function() {
			// bind to caltagradio controls
            // this is a hacky non-Vue approach, but sufficient
            $('input[type=radio][name=caltagradio]').change(function() {
                if (this.value == 'usename') {
                    $('input[type=text][name=caltag]')
                        .attr('data-prev', function() {return this.value;})
                        .prop('readonly', true)
                        .css({'color':'#FFFFFF', 'opacity':'0.6'})
                        .val('use_name');
                }
                else if (this.value == 'usetext') {
                    $('input[type=text][name=caltag]')
                        .prop('readonly', false)
                        .css({'color':'inherit', 'opacity':'1.0'})
                        .val(function() {
                            return this.getAttribute('data-prev') || '?';
                        });
                }
            });
		},
		valueInOptions: function(optArr, value) {
			var i;
			for (i in optArr) {
				if (optArr[i].value == value) {
					return true;
				}
			}
			return false;
		},
		addExtref: function() {
			this.extrefs.push({'label':'', 'link':''});
			this.extrefs = this.extrefs.slice();
		},
		doShowDisplayDialog: function() {
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
		closeDisplayDialog: function() {
			this.showDisplayDialog = false;
			$("#dispdetails").focus();
		}
	},
    mounted: function() {
    	// call init method
        this.initCalTagRadio();
    },
});
</script>
