<?php

$vueData = array(
	'allassess' => $page_assessSelect,
	'gbcatOptions' => $gbcats,
	'forumOptions' => $forums,
	'allowinstraddtutors' => (!isset($CFG['GEN']['allowinstraddtutors']) || $CFG['GEN']['allowinstraddtutors']==true),
	'outcomeOptions' => $outcomeOptions,
	'subtype' => 'DNC',
	'defregens' => '',
	'defregenpenalty' => '',
	'defregenpenaltyaftern' => '',
	'keepscore' => 'DNC',
	'defattempts' => '',
	'defattemptpenalty' => '',
	'defattemptpenaltyaftern' => '',
	'showscores' => 'DNC',
	'showans' => 'DNC',
	'viewingb' => 'DNC',
	'scoresingb' => 'DNC',
	'ansingb' => 'DNC',
	'coresub' => 0,
	'summary' => 'DNC',
	'intro' => 'DNC',
	'dates' => 'DNC',
	'avail' => 'DNC',
	'review' => 'DNC',
	'copyendmsg' => 'DNC',
	'chgendmsg' => false,
	'removeperq' => false,
    'copyopts' => 'DNC',
    'copyreqscore' => false,
	'displaymethod' => 'DNC',
	'defpoints' => '',
	'gbcategory' => 'DNC',
	'caltag' => '',
	'shuffle' => 'DNC',
	'istutorial' => 'DNC',
	'showcat' => 'DNC',
	'samever' => 'DNC',
	'noprint' => 'DNC',
	'showwork' => 'DNC',
	'allowlate' => 'DNC',
	'timelimit' => '',
	'allowovertime' => false,
	'overtimegrace' => 5,
	'overtimepenalty' => 0,
	'dochgpassword' => false,
	'assmpassword' => '',
	'reqscoretype' => 'DNC',
	'reqscoreaid' => 'DNC',
	'reqscore' => 1,
	'reqscorecalctype' => 0,
	'chgreqscore' => false,
	'showhints' => 'DNC',
	'msgtoinstr' => 'DNC',
	'posttoforum' => 'DNC',
	'dochgextref' => false,
	'extrefs' => array(),
	'showtips' => 'DNC',
	'cntingb' => 'DNC',
	'minscore' => '',
	'usedeffb' => 'DNC',
	'tutoredit' => 'DNC',
	'exceptionpenalty' => '',
	'defoutcome' => 'DNC',
	'revealpw' => false
);

// skipmathrender class is needed to prevent katex parser from mangling
// Vue template
?>
<div id="app" class="skipmathrender" v-cloak>
	<fieldset>
		<legend><?php echo _('General Options'); ?></legend>
		<div :class="{highlight:summary != 'DNC'}">
			<span class=form><?php echo _('Summary'); ?>:</span>
			<span class=formright>
				Copy from: <select name="summary" v-model="summary">
					<option value="DNC"><?php echo _('Do not copy'); ?></option>
					<option v-for="assess in allassess" :key="assess.val" :value="assess.val">
						{{ assess.label }}
					</option>
				</select>
			</span><br class=form />
		</div>
		<div :class="{highlight:intro != 'DNC'}">
			<span class=form><?php echo _('Instructions'); ?>:</span>
			<span class=formright>
				Copy from: <select name="intro" v-model="intro">
					<option value="DNC"><?php echo _('Do not copy'); ?></option>
					<option v-for="assess in allassess" :key="assess.val" :value="assess.val">
						{{ assess.label }}
					</option>
				</select>
			</span><br class=form />
		</div>
		<div :class="{highlight:dates != 'DNC'}">
			<span class=form><?php echo _('Dates and Times'); ?>:</span>
			<span class=formright>
				Copy from: <select name="dates" v-model="dates">
					<option value="DNC"><?php echo _('Do not copy'); ?></option>
					<option v-for="assess in allassess" :key="assess.val" :value="assess.val">
						{{ assess.label }}
					</option>
				</select>
			</span><br class=form />
		</div>
		<div :class="{highlight:avail != 'DNC'}">
			<span class=form>Show:</span>
			<span class=formright>
				<select name="avail" v-model="avail">
					<option value="DNC"><?php echo _('Do not change'); ?></option>
					<option value="0"><?php echo _('Hide'); ?></option>
					<option value="1"><?php echo _('Show By Dates'); ?></option>
				</select>
			</span><br class=form />
		</div>
		<div :class="{highlight:review != 'DNC'}">
			<span class=form><?php echo _('Practice mode'); ?>:</span>
			<span class=formright>
				<select name="review" v-model="review">
					<option value="DNC"><?php echo _('Do not change'); ?></option>
					<option value="0"><?php echo _('Do not keep open for un-graded practice after the due date'); ?></option>
					<option value="1"><?php echo _('Keep open for un-graded practice after the due date'); ?></option>
				</select>
			</span><br class=form />
		</div>
		<div :class="{highlight:copyendmsg != 'DNC'}">
			<span class=form><?php echo _('End of Assessment Messages'); ?>:</span>
			<span class=formright>
				Copy from: <select name="copyendmsg" v-model="copyendmsg">
					<option value="DNC"><?php echo _('Do not copy'); ?></option>
					<option v-for="assess in allassess" :key="assess.val" :value="assess.val">
						{{ assess.label }}
					</option>
				</select>
			</span><br class=form />
		</div>
		<div :class="{highlight:chgendmsg}">
			<span class=form><?php echo _('Define new end of assessment messages'); ?>:</span>
			<span class=formright>
				<input type="checkbox" name="chgendmsg" v-model="chgendmsg"/>
				<?php echo _('You will be taken to a page to change these after you hit submit'); ?>
			</span><br class=form />
		</div>
		<div :class="{highlight:removeperq}">
			<span class=form><?php echo _('Remove per-question settings'); ?></span>
			<span class=formright>
				<label>
					<input type="checkbox" name="removeperq" v-model="removeperq"/>
					<?php echo _('Remove per-question settings (points, attempts, etc.) for all questions in these assessments'); ?>
				</label>
			</span><br class=form />
		</div>
	</fieldset>

	<fieldset>
		<legend>Assessment Options</legend>
		<input type="hidden" id="oktocopy"
			:value = "(copyopts != 'DNC' || coreSub != 1) ? 1 : 0" />
		<div :class="{highlight:copyopts != 'DNC'}">
			<span class=form><?php echo _('Copy assessment options'); ?>:</span>
			<span class=formright>
				<?php echo _('Copy from'); ?>: <select name="copyopts" v-model="copyopts">
					<option value="DNC"><?php echo _('Do not copy'); ?></option>
					<option v-for="assess in allassess" :key="assess.val" :value="assess.val">
						{{ assess.label }}
					</option>
				</select>
			</span><br class=form />
		</div>
        <div v-show="copyopts !== 'DNC'" :class="{highlight:copyreqscore != false}">
            <span class="form"></span>
            <span class=formright>
                <label>
                <input type="checkbox" name="copyreqscore" v-model="copyreqscore" />
                <?php echo _('Also copy "show based on another assessment" setting');?>
                </label>
            </span><br class=form />
        </div>
		<div v-show="copyopts === 'DNC'" style="border-top: 3px double #ccc;">
		<div style="padding-top:4px;">
			<a href="#" onclick="groupToggleAll(1);return false;"><?php echo _('Expand All'); ?></a>
	 		<a href="#" onclick="groupToggleAll(0);return false;"><?php echo _('Collapse All'); ?></a>
		</div>
		<div class="block grouptoggle">
			<img class="mida" src="<?php echo $staticroot;?>/img/collapse.gif" />
			<?php echo _('Core Options'); ?>
		</div>
		<div class="blockitems">
			<div :class="{highlight:displaymethod !== 'DNC'}">
				<label class=form for="displaymethod"><?php echo _('Display style'); ?>:</label>
				<span class=formright>
					<select name="displaymethod" id="displaymethod" v-model="displaymethod">
						<option value="DNC"><?php echo _('Do not change'); ?></option>
						<option value="skip"><?php echo _('One question at a time'); ?></option>
						<option value="full"><?php echo _('All questions at once, or in pages'); ?></option>
						<option value="video_cued"><?php echo _('Video Cued'); ?></option>
						<?php if (isset($CFG['GEN']['livepollserver'])) {
							echo '<option value="livepoll">Live Poll</option>';
						}?>
					</select>
				</span><br class=form />
			</div>
			<div :class="{highlight:defpoints !== ''}">
				<label class=form for="defpoints"><?php echo _('Default points per problem'); ?>:</label>
				<span class=formright>
					<input type=number min=1 max=100 size=3 id="defpoints"
						name="defpoints" v-model.number="defpoints" />
				</span><br class=form />
			</div>
			<div :class="{warn:coreSub == 1, highlight:coreSub == 2}">

				<p class="noticetext" v-if="coreSub == 1">
					<?php echo _('To ensure consistency, if you change one of these settings you need	to change them all.'); ?>
				</p>
				<label class="form" for="subtype"><?php echo _('Submission type'); ?>:</label>
				<span class="formright">
					<select name="subtype" v-model="subtype" :required="changingCore">
						<option value="DNC"><?php echo _('Do not change'); ?></option>
						<option value="by_question"><?php echo _('Homework-style: new versions of individual questions'); ?></option>
						<option value="by_assessment"><?php echo _('Quiz-style: retake whole assessment with new versions'); ?></option>
					</select>
					<span class="noticetext small">
						<br/>
						<?php echo _('Warning: Changing this after students have started will require converting their data, and lead to loss of data on earlier attempts.'); ?>
					</span>
				</span><br class=form />

				<span class=form><?php echo _('Versions'); ?>:</span>
				<span class=formright>

					<label for="defregens" v-show="subtype == 'by_question'">
						<?php echo _('Number of versions for each question'); ?>:
					</label>
					<label for="defregens" v-show="subtype == 'by_assessment'">
						<?php echo _('Number of times assessment can be taken'); ?>:
					</label>
					<input type=number min=1 max=100 size=3 id="defregens"
						name="defregens" v-model.number="defregens"
						:required="changingCore" />
					<span v-if="defregens > 1">
						<br/>
						<?php echo sprintf(_('With a penalty of %1$s %% per version %2$s after %3$s full credit versions'), '<input type=number min=0 max=100 size=3 id="defregenpenalty"	name="defregenpenalty" v-model.number="defregenpenalty" :required="changingCore" />',
						'<span v-if="defregenpenalty>0">','<input type=number min=1 :max="Math.min(defregens,9)" size=3 id="defregenpenaltyaftern"
								name="defregenpenaltyaftern" v-model.number="defregenpenaltyaftern"
								:required="changingCore" />'); ?>
						</span>
						<br/>
						<span v-if="subtype == 'by_assessment'">
							<label for="keepscore">
								<?php echo _('Score to keep'); ?>:
							</label>
							<select id="keepscore" name="keepscore" v-model="keepscore"
								 :required="changingCore">
								<option value="DNC"><?php echo _('Do not change'); ?></option>
								<option value="best"><?php echo _('Best'); ?></option>
								<option value="last"><?php echo _('Last'); ?></option>
								<option value="average"><?php echo _('Average'); ?></option>
							</select>
						</span>
					</span>
				</span><br class=form />


				<span class=form><?php echo _('Tries'); ?>:</span>
				<span class=formright>
					<label for="defattempts">
						<?php echo _('Number of tries on each version of a question'); ?>:
					</label>
					<input type=number min=1 max=100 size=3 id="defattempts"
						name="defattempts" v-model.number="defattempts"
						 :required="changingCore" />
					<span v-if="defattempts>1">
						<br/>
						<?php echo sprintf(_('With a penalty of %1$s %% per try %2$s after %3$s full-credit tries'),'<input type=number min=0 max=100 size=3 id="defattemptpenalty"	name="defattemptpenalty" v-model.number="defattemptpenalty"	:required="changingCore" />','<span v-if="defattemptpenalty>0">','<input type=number min=1 :max="Math.min(defattempts,9)" size=3 id="defattemptpenaltyaftern" name="defattemptpenaltyaftern" v-model.number="defattemptpenaltyaftern"	:required="changingCore" />'); ?>
						</span>
					</span>
				</span><br class=form />

				<label class="form" for="showscores">
					<?php echo _('During assessment, show scores'); ?>:
				</label>
				<span class="formright">
					<select name="showscores" id="showscores" v-model="showscores" :required="changingCore" >
						<option v-for="option in showscoresOptions" :value="option.value" :key="option.value">
							{{ option.text }}
						</option>
					</select>
				</span><br class=form />

				<div v-if="showansOptions.length > 0">
					<label class="form" for="showans">
						<?php echo _('During assessment, show answers'); ?>:
					</label>
					<span class="formright">
						<select name="showans" id="showans" v-model="showans" :required="changingCore">
							<option v-for="option in showansOptions" :value="option.value">
								{{ option.text }}
							</option>
						</select>
					</span><br class=form />
				</div>

				<label class="form" for="viewingb">
					<?php echo _('Students can view their work in the gradebook'); ?>:
				</label>
				<span class="formright">
					<select name="viewingb" id="viewingb" v-model="viewingb" :required="changingCore">
						<option v-for="option in viewInGbOptions" :value="option.value" :key="option.value">
							{{ option.text }}
						</option>
					</select>
				</span><br class=form />

				<div v-if="scoresInGbOptions.length > 0">
					<label class="form" for="scoresingb">
						<?php echo _('Students can view their scores in the gradebook'); ?>:
					</label>
					<span class="formright">
						<select name="scoresingb" id="scoresingb" v-model="scoresingb"
							:required="changingCore">
							<option v-for="option in scoresInGbOptions" :value="option.value" :key="option.value">
								{{ option.text }}
							</option>
						</select>
					</span><br class=form />
				</div>

				<div v-if="ansInGbOptions.length > 0">
					<label class="form" for="ansingb">
						<?php echo _('Students can view correct answers in the gradebook'); ?>:
					</label>
					<span class="formright">
						<select name="ansingb" id="ansingb" v-model="ansingb"	:required="changingCore">
							<option v-for="option in ansInGbOptions" :value="option.value" :key="option.value">
								{{ option.text }}
							</option>
						</select>
					</span><br class=form />
				</div>
			</div> <!-- core grouping -->

			<div :class="{highlight:gbcategory != 'DNC'}">
				<label class="form" for="gbcategory">
					<?php echo _('Gradebook Category'); ?>:
				</label>
				<span class="formright">
					<select name="gbcategory" id="gbcategory" v-model="gbcategory">
						<option value="DNC"><?php echo _('Do not change'); ?></option>
						<option v-for="(cat,id) in gbcatOptions" :value="id" :key="id">
							{{ cat }}
						</option>
					</select>
				</span><br class=form />
			</div>

		</div>

		<div class="block grouptoggle">
			<img class="mida" src="<?php echo $staticroot;?>/img/expand.gif" />
			<?php echo _('Additional Display Options'); ?>
		</div>
		<div class="blockitems hidden">
			<div :class="{highlight:caltag != ''}">
				<label class="form" for="caltag"><?php echo _('Calendar icon'); ?>:</label>
				<span class="formright">
                    <label><input name="caltagradio" type="radio" value="usetext" checked><?php echo _('Use Text');?>:</label>
                        <input aria-label="<?php echo _('Calendar icon text');?>" name="caltag" id="caltag" v-model="caltag" type=text size=8 /> <br />
					<label><input name="caltagradio" type="radio" value="usename"><?php echo _('Use Assessment Name');?></label>
				</span><br class=form />
			</div>

			<div :class="{highlight:shuffle != 'DNC'}">
				<label class=form for="shuffle"><?php echo _('Shuffle item order'); ?>:</label>
				<span class=formright>
					<select name="shuffle" id="shuffle" v-model="shuffle">
						<option value="DNC"><?php echo _('Do not change'); ?></option>
						<option value="0"><?php echo _('No'); ?></option>
						<option value="1"><?php echo _('All'); ?></option>
                        <option value="16"><?php echo _('All but first'); ?></option>
                        <option value="32"><?php echo _('All but last');?></option>
                        <option value="48"><?php echo _('All but first and last');?></option>
					</select>
				</span><br class=form />
			</div>

			<div :class="{highlight:showwork != 'DNC'}">
				<label class=form for="showwork"><?php echo _('Provide "Show Work" boxes');?>:</label>
				<span class=formright>
					<select name="showwork" id="showwork" v-model="showwork">
						<option value="DNC"><?php echo _('Do not change'); ?></option>
						<option value="0"><?php echo _('No');?></option>
						<option value="1"><?php echo _('During assessment');?></option>
						<option value="2"><?php echo _('After assessment');?></option>
						<option value="3"><?php echo _('During or after assessment');?></option>
					</select>
				</span><br class=form />
			</div>

			<div :class="{highlight:noprint != 'DNC'}">
				<label class=form for="noprint"><?php echo _('Make hard to print'); ?></label>
				<span class=formright>
					<select name="noprint" id="noprint" v-model="noprint">
						<option value="DNC"><?php echo _('Do not change'); ?></option>
						<option value="0"><?php echo _('No'); ?></option>
						<option value="1"><?php echo _('Yes'); ?></option>
					</select>
				</span><br class=form />
			</div>
			<div :class="{highlight:samever != 'DNC'}">
				<label class=form for="samever"><?php echo _('All students same version of questions'); ?></label>
				<span class=formright>
					<select name="samever" id="samever" v-model="samever">
						<option value="DNC"><?php echo _('Do not change'); ?></option>
						<option value="0"><?php echo _('No'); ?></option>
						<option value="1"><?php echo _('Yes'); ?></option>
					</select>
				</span><br class=form />
			</div>
			<div :class="{highlight:istutorial != 'DNC'}">
				<label class=form for="istutorial"><?php echo _('Suppress default score result display'); ?></label>
				<span class=formright>
					<select name="istutorial" id="istutorial" v-model="istutorial">
						<option value="DNC"><?php echo _('Do not change'); ?></option>
						<option value="0"><?php echo _('No'); ?></option>
						<option value="1"><?php echo _('Yes'); ?></option>
					</select>
				</span><br class=form />
			</div>
			<div :class="{highlight:showcat != 'DNC'}">
				<label class=form for="showcat"><?php echo _('Show question categories in Question Details (if defined)'); ?></label>
				<span class=formright>
					<select name="showcat" id="showcat" v-model="showcat">
						<option value="DNC"><?php echo _('Do not change'); ?></option>
						<option value="0"><?php echo _('No'); ?></option>
						<option value="1"><?php echo _('Yes'); ?></option>
					</select>
				</span><br class=form />
			</div>
		</div>

		<div class="block grouptoggle">
			<img class="mida" src="<?php echo $staticroot;?>/img/expand.gif" />
			<?php echo _('Time Limit and Access Control'); ?>
		</div>
		<div class="blockitems hidden">
			<div :class="{highlight:allowlate != 'DNC'}">
				<label for="allowlate" class=form><?php echo _('Allow use of LatePasses?'); ?>:</label>
				<span class=formright>
					<select name="allowlate" id="allowlate" v-model="allowlate">
						<option value="DNC"><?php echo _('Do not change'); ?></option>
						<option value="0"><?php echo _('None'); ?></option>
						<option value="1"><?php echo _('Unlimited'); ?></option>
						<option value="2"><?php echo _('Up to 1'); ?></option>
						<option value="3"><?php echo _('Up to 2'); ?></option>
						<option value="4"><?php echo _('Up to 3'); ?></option>
						<option value="5"><?php echo _('Up to 4'); ?></option>
						<option value="6"><?php echo _('Up to 5'); ?></option>
						<option value="7"><?php echo _('Up to 6'); ?></option>
						<option value="8"><?php echo _('Up to 7'); ?></option>
						<option value="9"><?php echo _('Up to 8'); ?></option>
					</select>
					<span v-show="allowlate > 0">
						<label>
							<input type="checkbox" name="latepassafterdue">
							<?php echo _('Allow LatePasses after due date'); ?>
						</label>
					</span>
				</span><br class=form />
			</div>

			<div :class="{highlight:timelimit !== ''}">
				<label for=timelimit class=form><?php echo _('Time Limit'); ?>:</label>
				<span class=formright>
					<input type=text size=4 name=timelimit id=timelimit v-model="timelimit">
					<?php echo _('minutes (0 for none)'); ?>
					<span v-if="timelimit !== '' && timelimit > 0">
						<br/>
						<label>
							<input type="checkbox" name="allowovertime" v-model="allowovertime" />
							<?php echo _('Allow student to work past time limit'); ?>
						</label>
						<span v-if="allowovertime">
							<?php echo sprintf(_('Grace period of %s minutes with a penalty of %s %%'),'<input type="text" size="3" name="overtimegrace" v-model="overtimegrace" />','<input type="text" size="2" name="overtimepenalty" v-model="overtimepenalty" />'); ?>
						</span>
					</span>
				</span><br class=form />
			</div>

			<div :class="{highlight:dochgpassword}">
				<span class=form><?php echo _('Require Password'); ?>:</span>
				<span class=formright>
					<label>
						<input type="checkbox" name="dochgpassword" v-model="dochgpassword"/>
						<?php echo _('Change password'); ?>
					</label>
					<span v-show="dochgpassword">
						<br/>
						<label for="assmpassword"><?php _('Password (blank for none)'); ?>:</label>
						<input :type="revealpw?'text':'password'" name="assmpassword"
							id="assmpassword" v-model="assmpassword" autocomplete="new-password">
						<a v-if="assmpassword != ''" href="#" @click.prevent="revealpw = !revealpw">
							{{ revealpw ? <?php echo _('Hide'); ?>: <?php echo _('Show'); ?>}}
						</a>
					</span>
				</span><br class=form />
			</div>

			<div :class="{highlight:reqscoreaid !== 'DNC'}">
				<label for="reqscoreaid" class=form><?php echo _('Show based on another assessment'); ?>:</label>
				<span class=formright>
					<select id="reqscoreaid" name="reqscoreaid" v-model="reqscoreaid">
						<option value="DNC"><?php echo _('Do not change'); ?></option>
						<option value="0"><?php echo _('No prerequisite'); ?></option>
						<option v-for="assess in allassess" :key="assess.val" :value="assess.val">
							{{ assess.label }}
						</option>
					</select>
					<span id="reqscorewrap" v-if="reqscoreaid !== 'DNC' && reqscoreaid > 0">
						<?php echo _('with a score of'); ?>
						<input type=text size=4 name="reqscore" v-model="reqscore" />
						<select name="reqscorecalctype" v-model="reqscorecalctype">
							<option value="0"><?php echo _('Points'); ?></option>
							<option value="1"><?php echo _('Percent'); ?></option>
						</select>
					</span>
				</span><br class=form />
			</div>
			<div :class="{highlight:reqscoretype !== 'DNC'}">
				<label for="reqscoreshowtype" class=form><?php echo _('Show based on another assessment display'); ?>: </label>
				<span class=formright>
					<select id="reqscoreshowtype" name="reqscoreshowtype" v-model="reqscoretype">
						<option value="DNC"><?php echo _('Do not change'); ?></option>
						<option value="0"><?php echo _('Hide until requirement is met'); ?></option>
						<option value="1"><?php echo _('Show greyed until requirement is met'); ?></option>
					</select>
				</span><br class=form />
			</div>

		</div>

		<div class="block grouptoggle">
			<img class="mida" src="<?php echo $staticroot;?>/img/expand.gif" />
			<?php echo _('Help and Hints'); ?>
		</div>
		<div class="blockitems hidden">
			<div :class="{highlight:showhints !== 'DNC'}">
				<label for="showhints" class=form><?php echo _('Hints and Videos'); ?></label>
				<span class=formright>
					<select name="showhints" id="showhints" v-model="showhints">
						<option value="DNC"><?php echo _('Do not change'); ?></option>
			      <option value="0"><?php echo _('No'); ?></option>
			      <option value="1"><?php echo _('Hints'); ?></option>
			      <option value="2"><?php echo _('Video/text buttons'); ?></option>
			      <option value="3"><?php echo _('Hints and Video/text buttons'); ?></option>
					</select>
				</span><br class=form />
			</div>

			<div :class="{highlight:msgtoinstr !== 'DNC'}">
				<label class="form" for="msgtoinstr"><?php echo _('Show "Message instructor about this question" links'); ?></label>
				<span class=formright>
					<select name="msgtoinstr" id="msgtoinstr" v-model="msgtoinstr">
						<option value="DNC"><?php echo _('Do not change'); ?></option>
						<option value="0"><?php echo _('No'); ?></option>
						<option value="1"><?php echo _('Yes'); ?></option>
					</select>
				</span><br class=form />
			</div>
			<div :class="{highlight:posttoforum !== 'DNC'}">
				<label class="form" for="posttoforum"><?php echo _('Show "Post this question to forum" links'); ?></label>
				<span class=formright>
					<select name="posttoforum" id="posttoforum" v-model="posttoforum">
						<option v-for="option in forumOptions" :value="option.value" :key="option.value">
							{{ option.text }}
						</option>
					</select>
				</span><br class=form />
			</div>

			<div :class="{highlight:dochgextref}">
				<span class=form><?php echo _('Assessment resource links'); ?></span>
				<span class=formright>
					<label>
						<input type="checkbox" name="dochgextref" v-model="dochgextref" />
						<?php echo _('Replace existing assessment resources'); ?>
					</label>
					<span v-show="dochgextref">
						<br/>
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
					</span>
				</span><br class=form />
			</div>

			<div :class="{highlight:showtips !== 'DNC'}">
				<label for="showtips" class=form><?php echo _('Show answer entry tips?'); ?></label>
				<span class=formright>
					<select name="showtips" id="showtips" v-model="showtips">
						<option value="DNC"><?php echo _('Do not change'); ?></option>
						<option value="0"><?php echo _('No'); ?></option>
						<option value="2"><?php echo _('Yes, under answerbox (strongly recommended)'); ?></option>
					</select>
				</span><br class=form />
			</div>

		</div>

		<div class="block grouptoggle">
			<img class="mida" src="<?php echo $staticroot;?>/img/expand.gif" />
			<?php echo _('Grading and Feedback'); ?>
		</div>
		<div class="blockitems hidden">
			<div :class="{highlight:cntingb !== 'DNC'}">
				<label for="cntingb" class=form><?php echo _('Count'); ?>:</label>
				<span class=formright>
					<select name="cntingb" id="cntingb" v-model="cntingb">
						<option value="DNC"><?php echo _('Do not change'); ?></option>
						<option value="1"><?php echo _('Count in Gradebook'); ?></option>
						<option value="0"><?php echo _("Don't count in grade total and hide from students"); ?></option>
						<option value="3"><?php echo _("Don't count in grade total"); ?></option>
						<option value="2"><?php echo _('Count as Extra Credit'); ?></option>
					</select>
				</span><br class=form />
			</div>

			<div :class="{highlight:minscore !== ''}">
				<label for="minscore" class=form><?php echo _('Minimum score to receive credit'); ?>:</label>
				<span class=formright>
					<input type=text size=4 name=minscore id=minscore v-model="minscore">
					<select name="minscoretype">
						<option value="0" selected><?php echo _('Points'); ?></option>
						<option value="1"><?php echo _('Percent'); ?></option>
					</select>
				</span><br class=form />
			</div>

			<div :class="{highlight:usedeffb !== 'DNC'}">
				<span class="form"><?php echo _('Default Feedback Text'); ?>:</span>
				<span class="formright">
					<select name="usedeffb" v-model="usedeffb">
						<option value="DNC"><?php echo _('Do not change'); ?></option>
						<option value="0"><?php echo _('Do not use default feedback text'); ?></option>
						<option value="1"><?php echo _('Use default feedback text'); ?></option>
					</select>
					<span v-show="usedeffb==1">
						<br/>
						<?php echo _('Text'); ?>:
						<textarea name="deffb" rows="4" cols="60"></textarea>
					</span>
				</span><br class=form />
			</div>

			<div v-if="allowinstraddtutors" :class="{highlight:tutoredit !== 'DNC'}">
				<label for="tutoredit" class="form"><?php echo _('Tutor Access'); ?>:</label>
				<span class="formright">
					<select name="tutoredit" id="tutoredit" v-model="tutoredit">
						<option value="DNC"><?php echo _('Do not change'); ?></option>
						<option value="2"><?php echo _('No Access'); ?></option>
						<option value="0"><?php echo _('View Scores'); ?></option>
						<option value="1"><?php echo _('View and Edit Scores'); ?></option>
					</select>
				</span><br class=form />
			</div>

			<div :class="{highlight:exceptionpenalty !== ''}">
				<label for="exceptionpenalty" class=form>
					<?php echo _('Penalty for questions done while in exception/LatePass'); ?>:
				</label>
				<span class=formright>
					<input type=text size=4 name="exceptionpenalty" id="exceptionpenalty"
					 	v-model="exceptionpenalty">%
				</span><br class=form />
			</div>

			<div :class="{highlight:defoutcome !== 'DNC'}" v-if="outcomeOptions.length > 0">
				<label for="defoutcome" class="form"><?php echo _('Default Outcome'); ?>:</label>
				<span class="formright">
					<select name="defoutcome" id="defoutcome" v-model="defoutcome">
						<option value="DNC"><?php echo _('Do not change'); ?></option>
						<option value="0"><?php echo _('No default outcome selected'); ?></option>
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
		</div>
		</div>

	</fieldset>
</div>
<script type="text/javascript">

var app = new Vue({
	el: '#app',
  data: <?php echo json_encode($vueData, JSON_INVALID_UTF8_IGNORE); ?>,
	computed: {
		coreSet: function() {
			let tot = (this.subtype === 'DNC' ? 0 : 1) +
				(this.defregens === '' ? 0 : 1) +
				(this.defregens > 1 && this.defregenpenalty === '' ? 0 : 1) +
				(this.defregens > 1 && this.defregenpenalty > 0 && this.defregenpenaltyaftern === '' ? 0 : 1) +
				(this.subtype === 'by_assessment' && this.defregens > 1 && this.keepscore === 'DNC' ? 0 : 1) +
				(this.defattempts === '' ? 0 : 1) +
				(this.defattempts > 1 && this.defattemptpenalty === '' ? 0 : 1) +
				(this.defattempts > 1 && this.defattemptpenalty > 0 && this.defattemptpenaltyaftern === '' ? 0 : 1) +
				(this.showscores === 'DNC' ? 0 : 1) +
				(this.showansOptions.length > 0 && this.showans === 'DNC' ? 0 : 1) +
				(this.viewingb === 'DNC' ? 0 : 1) +
				(this.scoresingb === 'DNC' ? 0 : 1) +
				(this.ansInGbOptions.length > 0 && this.ansingb === 'DNC' ? 0 : 1);
			return tot;
		},
		changingCore: function () {
			let tot = (this.subtype !== 'DNC') ||
				(this.defregens !== '') ||
				(this.defattempts !== '') ||
				(this.showscores !== 'DNC') ||
				(this.showansOptions.length > 0 && this.showans !== 'DNC') ||
				(this.viewingb !== 'DNC') ||
				(this.scoresingb !== 'DNC') ||
				(this.ansingb !== 'DNC');
			return tot;
		},
		coreSub: function () {
			if (this.coreSet == 13) { //all set
				return 2;
			} else if (this.changingCore) {
				return 1;
			} else {
				return 0;
			}
		},
		showscoresOptions: function() {
			var nochange = {
				'value': 'DNC',
				'text': '<?php echo _('Do not change'); ?>'
			};
			var during = {
				'value': 'during',
				'text': '<?php echo _('On each question immediately'); ?>'
			};
			var at_end = {
				'value': 'at_end',
				'text': '<?php echo _('At the end of the assessment'); ?>'
			};
			var total = {
				'value': 'total',
				'text': '<?php echo _('Total score only at the end'); ?>'
			};
			var none = {
				'value': 'none',
				'text': '<?php echo _('No scores at all'); ?>'
			};

			var out = [];
			if (this.defattempts == 1 && this.subtype != 'by_question') {
				// if we only have 1 try, and not HW mode, show all options
				out = [nochange,during, at_end, total, none];
			} else if ((this.subtype == 'by_question' && this.defregens>1) ||
			 	(this.defattempts > 1 && this.subtype != 'by_question')
			) {
				// if we're in HW mode, and allowing multiple versions, must show score immediately
				// likewise if in quiz mode and allow multiple tries
				out = [nochange,during];
			} else {
				// otherwise, give option of immediately (typical) or no scores shown
				out = [nochange,during, none];
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
				'text': '<?php echo _('Never'); ?>'
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
						'text': '<?php echo _('After the last try on a question'); ?>'
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
													'<?php echo _('After 1 try'); ?>'
					});
				}
			} else if (this.showscores == 'at_end') {
				// for showing scores at end: after_attempt or never
				out = [
					{
						'value': 'after_take',
						'text': '<?php echo _('After the assessment version is submitted'); ?>'
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
					'text': '<?php echo _('After the due date'); ?>'
				},
				{
					'value': 'immediately',
					'text': '<?php echo _('Immediately - they can always view it'); ?>'
				},
				{
					'value': 'never',
					'text': '<?php echo _('Never'); ?>'
				}
			];
			if (this.subtype == 'by_assessment') {
				out.unshift({
					'value': 'after_take',
					'text': '<?php echo _('After the assessment version is submitted'); ?>'
				})
			}
			out.unshift({
				'value': 'DNC',
				'text': '<?php echo _('Do not change'); ?>'
			});
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
			If showscores = 'at_end', then scores should show in GB after_take
			If showscores = 'total', then select 'after_take', 'after_due', or 'never' (?)
				What if we want to only allow viewing total, and NEVER see score details?
				Then GB would need to look at showscores as well as scoresingb
			If showscores = 'never', then select 'after_take', 'after_due', or 'never'
			 */

			var out = [
				{
					'value': 'after_due',
					'text': '<?php echo _('After the due date'); ?>'
				},
				{
					'value': 'never',
					'text': '<?php echo _('Never'); ?>'
				}
			];
			if (this.showscores !== 'during' && this.showscores !== 'at_end' &&
					this.subtype == 'by_assessment'
			) {
				out.unshift({
					'value': 'after_take',
					'text': '<?php echo _('After the assessment version is submitted'); ?>'
				});
			}

			if (this.showscores == 'during' && this.subtype == 'by_question') {
				out = [{
					'value': 'immediately',
					'text': '<?php echo _('Immediately'); ?>'
				}];
			} else if (this.showscores == 'at_end' ||
					(this.showscores == 'during' && this.subtype == 'by_assessment')
			) {
				out = [{
					'value': 'after_take',
					'text': '<?php echo _('After the assessment version is submitted'); ?>'
				}];
			}
			out.unshift({
				'value': 'DNC',
				'text': '<?php echo _('Do not change'); ?>'
			});
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
 						'text': '<?php echo _('After the due date'); ?>'
 					},
 					{
 						'value': 'never',
 						'text': '<?php echo _('Never'); ?>'
 					}
                ];
                if (this.viewingb === 'after_take' && this.subtype == 'by_assessment') {
 					out.unshift({
 						'value': 'after_take',
 						'text': '<?php echo _('After the assessment version is submitted'); ?>'
 					});
 				}
				out.unshift({
					'value': 'DNC',
					'text': '<?php echo _('Do not change'); ?>'
				});
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
			$('input[type=radio][name=caltagradio]').change(function() {
				if (this.value == 'usename') {
					$('input[type=text][name=caltag]').prop('readonly', true).css({'color':'#FFFFFF', 'opacity':'0.6'}).val('use_name');
					$('input[type=text][name=caltag]').closest('div').addClass('highlight');
				}
				else if (this.value == 'usetext') {
					$('input[type=text][name=caltag]').prop('readonly', false).css({'color':'inherit', 'opacity':'1.0'}).val('');
					$('input[type=text][name=caltag]').closest('div').removeClass('highlight');
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
		}
	},
    mounted: function() {
    	// call init method
        this.initCalTagRadio();
	}
});
</script>
