# Time units
nth = 
    { $n ->
        [1] first
        [2] second
        [3] third
        [4] fourth
        *[other] {$n}th
    }

seconds = 
    { $n ->
        [one] 1 second
        *[other] {$n} seconds
    }

minutes = 
    { $n ->
        [one] 1 minute
        *[other] {$n} minutes
    }

hours = 
    { $n ->
        [one] 1 hour
        *[other] {$n} hours
    }

longdate = DATETIME($date, dateStyle: "long")

# Basic UI
close = Close
loading = Loading...
intro = Intro / Instructions
next = Next
previous = Previous
question_n = Question {$n}
extracredit = Extra Credit
jumptocontent = Skip Navigation

# Launch section
launch-continue_assess = Resume
launch-retake_assess = Retake
launch-start_assess = Start
launch-timewarning = This assessment has a time limit. Once you start the assessment, the timer will not pause for any reason. Are you sure you are ready to start?
launch-resetmsg = Teachers: you can reset your attempts on this assessment if desired.
launch-doreset = Reset
launch-view_as_stu = Acting as student: {$name}
launch-scorelist = Score List
launch-itemanalysis = Item Analysis
launch-gblinks = Gradebook Links

# Closed section
closed-hidden = This assessment is not currently available.
closed-notyet = This assessment is not yet available. It will be available {$sd} until {$ed}.
closed-pastdue = This assessment was due {$ed}.
closed-pasttime = The timelimit has expired on this assessment.
closed-needprereq = You have not yet met the prerequisite requirements to work on this assessment.
closed-prereqreq = A score of {$score} on {$name} is required.
closed-no_attempts = You have used all your attempts at this assessment.
closed-latepassn = 
    { $n ->
        [one] You have one LatePass available.
        *[other] You have {$n} LatePasses available.
    }
closed-latepass_needed = 
    { $n ->
        [one] You can redeem one LatePass to reopen this assessment until {$date}
        *[other] You can redeem {$n} LatePasses to reopen this assessment until {$date}
    }
closed-practice_no_latepass = This assessment is now open for un-graded practice.
closed-practice_w_latepass = You can also open the assessment for un-graded practice.
closed-will_block_latepass = If you do so, you will not be able to later use a LatePass.
closed-confirm = Are you SURE you want to do this? If you do, you will not be able to later use a LatePass.
closed-can_view_scored = You can review your scored assessment.
closed-view_scored = View scored assessment
closed-use_latepass = 
    { $n ->
        [one] Redeem LatePass
        *[other] Redeem {$n} LatePasses
    }
closed-do_practice = Practice
closed-unsubmitted_pastdue = You have an unsubmitted assessment attempt.
closed-unsubmitted_overtime = You have an unsubmitted assessment attempt whose time limit has expired.
closed-submit_now = Submit it now
closed-exit = Exit
closed-teacher_preview = This assessment is closed to students, but you can preview it.
closed-teacher_preview_button = Student Preview
closed-teacher_previewall_button = Teacher Preview

# Due dialog
duedialog-due = Due Date Reached
duedialog-nowdue = This assessment is due now.
duedialog-byq_unsubmitted = You have work that has not yet been submitted for grading.
duedialog-bya_unsubmitted = This assessment attempt has not been submitted for grading.
duedialog-submitnow = Submit for grading

# Set list
setlist-practice = This assessment is in un-graded practice mode.
setlist-points_possible = {$pts} points possible.
setlist-due_at = Due {$date}.
setlist-originally_due = Originally due {$date}.
setlist-latepass_used = 
    { $n ->
        [one] You used one LatePass.
        *[other] You used {$n} LatePasses.
    }
setlist-extension = You were granted an extension.
setlist-penalty = A penalty of {$p}% will be applied.
setlist-penalty_after = A penalty of {$p}% will be applied after {$date}.
setlist-earlybonus = A bonus of {$p}% will be applied until {$date}.
setlist-take = 
    { $n ->
        [one] You can take this assessment one time.
        *[other] You can take this assessment {$n} times.
    }
setlist-take_more = 
    { $n ->
        [one] You can take this assessment one more time.
        *[other] You can take this assessment {$n} more times.
    }
setlist-attempt_inprogress = You have an assessment attempt in progress.
setlist-cur_attempt_n_of = You are working on attempt {$n} of {$nmax}.
setlist-keep_highest_q = Best attempt on each question recorded as grade.
setlist-keep_highest = Highest scored attempt recorded as grade.
setlist-keep_average = Average score recorded as grade.
setlist-keep_last = Last attempt recorded as grade.
setlist-retake_penalty = A penalty of {$p}% will be applied on the next retake.
setlist-time_expires = Your current assessment time limit expires at {$date}.
setlist-time_expires_wgrace = Your current assessment time limit expires at {$date}, and the grace period ends {$grace}.
setlist-time_expired = Your current assessment time limit expired at {$date}.
setlist-time_grace_expires = Your current assessment time limit expired at {$date}. The grace period ends {$grace}.
setlist-timelimit = Time limit: {$time}.
setlist-timelimit_wgrace = Time limit: {$time}, with a grace period of {$grace}.
setlist-timelimit_wgrace_penalty = Time limit: {$time}, with a grace period of {$grace} subject to a {$penalty}% penalty.
setlist-timelimit_extend = Extended from the original {$time}.
setlist-timelimit_restricted = Because of the upcoming due date, your time limit will expire at the due date, {$due}.
setlist-timelimit_wgrace_restricted = Time limit: {$time}, with a grace period which expires at the due date, {$due}.
setlist-timelimit_wgrace_restricted_penalty = Time limit: {$time}, with a grace period subject to a {$penalty}% penalty which expires at the due date, {$due}.
setlist-timelimit_ext = You have been given a {$n} minute extension on the time limit
setlist-timelimit_ext_used = You have used a {$n} minute timelimit extension
setlist-excused = You have been excused from this assignment. It will not be counted in your grade.
setlist-latepass_needed = 
    { $n ->
        [one] You can redeem one LatePass to extend the due date to {$date}
        *[other] You can redeem {$n} LatePasses to extend the due date to {$date}
    }

# Group
group-isgroup = This is a group assessment.
group-teacher_auto = This assessment allows students to select their own group members, up to {$n}.
group-teacher_preset = This assessment uses groups that the instructor must create in advance.
group-needpreset = You are not yet a member of a group. Contact your instructor to be added to a group.
group-members = Group Members
group-max = max {$n}
group-remove = Remove
group-add = Add to group:
group-select = Select...
group-addbutton = Add

# Password
password-requires = This assessment requires a password.
password-label = Password:

# Question info
qinfo-tryn = Try {$n} of {$nmax}
qinfo-regenn = Version {$n} of {$nmax}
qinfo-tries_remaining = 
    { $n ->
        [one] 1 try on this question remaining
        *[other] {$n} tries on this question remaining
    }
qinfo-tries_remaining_range = {$min} to {$max} tries remaining depending on the part - see Details
qinfo-regens_remaining = 
    { $n ->
        [one] You can Get a Similar Question 1 more time
        *[other] You can Get a Similar Question {$n} more times
    }

# Question
question-submit = Submit Question
question-checkans = Check Answer
question-saveans = Save Answer
question-next = Next Question
question-submit_seqnext = Submit Part
question-checkans_seqnext = Check Part Answer
question-saveans_seqnext = Next Part
question-submit_submitall = Submit All Parts
question-checkans_submitall = Check All Parts
question-saveans_submitall = Save All Parts
question-withdrawn = This question was withdrawn by the instructor. You do not need to complete this question.
question-jump_to_answer = Jump to Answer
question-jump_warn = This will use up your remaining tries at this version of the question.
question-showwork = Show work here by typing it or attaching a file or picture
question-showwork_n = Work for question {$n}
question-uploadwork = Show work here by attaching a file or picture
question-uploading = Uploading...
question-intronext = To begin, navigate to a question using the selector or > Next button above.
question-firstq = First Question

# Header
header-score = Score: {$pts}/{$poss}
header-practicescore = Practice score: {$pts}/{$poss}
header-possible = 
    { $n ->
        [one] 1 point possible
        *[other] {$n} points possible
    }
header-answered = Answered: {$n}/{$tot}
header-assess_submit = Submit and End
header-done = Done
header-resources_header = Resources
header-pts = 
    { $n ->
        [one] 1 pt
        *[other] {$n} pts
    }
header-details = Details
header-warn_unattempted = There appears to be unattempted questions. Are you sure you want to submit now?
header-withdrawn = Question withdrawn
header-use_mq = Use equation editor
header-enable_mq = Enable equation editor
header-disable_mq = Disable equation editor
header-work_save = Save progress
header-work_saved = Progress saved
header-work_save_avail = Save progress button available
header-work_saving = Saving...
header-confirm_assess_submit = After submitting, you will not be able to change your answers on this version of the assessment. Are you ready to submit?
header-confirm_assess_unattempted_submit = There appears to be unattempted questions. After submitting, you will not be able to change your answers on this version of the assessment. Are you ready to submit?
header-preview_all = Instructor Preview of All Questions

# Resource
resource-sidebar = Open in a sidebar
resource-newtab = Open in a new tab

# Text
text-hide = Hide text
text-show = Show question text

# Errors
error-error = Error
error-invalid_password = The password you entered was invalid
error-invalid_aid = Invalid assessment ID
error-no_access = You must be a student, teacher, or tutor to access this assessment
error-teacher_only = You must be a teacher to access this feature
error-missing_param = Missing required parameter on API call
error-not_avail = The assessment is not currently available
error-not_ready = The assessment is not ready for this action
error-not_practice = The assessment is no longer in practice mode. Go back and open the assessment again.
error-timelimit_expired = Timelimit has expired
error-timesup_submitting = Timelimit has expired. Submitting now.
error-workafter_expired = Time for adding work after has expired
error-workafter_submitting = Time for adding work after has expired. Saving now.
error-out_of_regens = No more Get a Similar Question remain
error-need_group = Cannot start the assessment until you have been added to a group
error-out_of_attempts = You have used all your assessment attempts
error-already_submitted = Submission rejected. You have submitted this assessment elsewhere since it was displayed here, so the question(s) you were trying to submit may be out of date.
error-no_active_attempt = You do not have an active attempt
error-no_session = Your login session expired. To continue you will need to log in again.
error-lti_no_session = Your session expired. Please go back to your LMS and open the assignment again.
error-fast_regen = Hey, how about slowing down and trying the problem before hitting Get a Similar Question? Wait 5 seconds before trying again.
error-nochange = Your answers have not changed since your last submission.
error-noserver = The site is not responding
error-parseerror = Server sent an invalid response
error-livepoll_wrongquestion = Submitted question is not the current question.
error-livepoll_notopen = This question is not open for submissions.
error-need_relaunch = Necessary information is missing. Please go back to your LMS and open the assignment again.
error-ytnotready = YouTube is not ready yet. Please be patient for a few seconds.
error-file_upload_error = Error uploading file
error-file_toolarge = Error uploading file - too large. Must be under 15MB
error-file_invalidtype = Error uploading file - invalid file type

# Confirm
confirm-ok = OK
confirm-cancel = Cancel

# Score result
scoreresult-correct = Correct
scoreresult-incorrect = Incorrect
scoreresult-partial = Partially correct
scoreresult-retry = Retry this question
scoreresult-next = Next question
scoreresult-retryq = You can retry this question below
scoreresult-trysimilar = Get a similar question
scoreresult-scorepts = 
    { $poss ->
        [one] {$pts} of {$poss} pt
        *[other] {$pts} of {$poss} pts
    }
scoreresult-scorelast = Score on last try:
scoreresult-submitted = Question submitted.
scoreresult-see_details = See Details for more.
scoreresult-manual_grade = This question contains parts that must be graded by your instructor. They will show a score of 0 until they are graded.
scoreresult-jumptoincorrect = Jump to first changable incorrect part
scoreresult-jumptolast = Jump to last submitted part
scoreresult-allpartscorrect = All submitted parts correct.
scoreresult-onepartincorrect = At least one scored part is incorrect.

# Summary
summary-no_total = Your assessment has been submitted.
summary-viewwork_work = You can view your work in the gradebook.
summary-viewwork_work_after = You can view your work in the gradebook after the due date.
summary-viewwork_immediately = You can view your work and scores in the gradebook.
summary-viewwork_after_due = You can view your work and scores in the gradebook after the due date.
summary-viewwork_work_scores_after = You can view your work in the gradebook, and scores after the due date.
summary-viewwork_work_after_lp = You can view your work in the gradebook after the LatePass period.
summary-viewwork_after_lp = You can view your work and scores in the gradebook after the LatePass period.
summary-viewwork_never = 
summary-score = Score
summary-recordedscore = Recorded Score
summary-use_override = Instructor grade override is recorded
summary-scorepts = 
    { $poss ->
        [one] {$pts} of {$poss} pt
        *[other] {$pts} of {$poss} pts
    }
summary-retake_penalty = {$n}% retake penalty applied
summary-late_penalty = {$n}% late work penalty applied
summary-scorelist = Score List
summary-reshowquestions = Review Questions
summary-new_excused = Based on your results in this assessment, the following assessments have been excused:

# Score list
scorelist-question = Question
scorelist-score = Score
scorelist-pts = 
    { $poss ->
        [one] {$pts} of {$poss} pt
        *[other] {$pts} of {$poss} pts
    }
scorelist-unattempted = Unattempted

# Category list
catlist-category = Category
catlist-score = Score
catlist-pts = 
    { $poss ->
        [one] {$pts} of {$poss} pt
        *[other] {$pts} of {$poss} pts
    }

# Previous attempts
prev-previous_attempts = Previous Attempts
prev-scored_attempts = Scored Attempts
prev-all_attempts = All Attempts
prev-date = Date
prev-score = Score
prev-viewingb = Review work in gradebook

# Penalties
penalties-applied = Penalties/bonus applied
penalties-retry = Retry penalty
penalties-regen = Assessment retake penalty
penalties-trysimilar = Try Similar Question penalty
penalties-late = Late work penalty
penalties-early = Early work bonus
penalties-overtime = Overtime penalty

# Question details
qdetails-question_details = Question Details
qdetails-part = Part
qdetails-lasttry = Results on last try:
qdetails-score = Score
qdetails-try = Tries Remaining
qdetails-penalties = Penalties
qdetails-category = Category
qdetails-gbscore = Recorded score
qdetails-bestpractice = Best practice score
qdetails-lastscore = Score on last try
qdetails-license = License
qdetails-extracredit = This question is extra credit

# Timer
timer-hrs = 
    { $n ->
        [one] hr
        *[other] hrs
    }
timer-min = 
    { $n ->
        [one] min
        *[other] mins
    }
timer-overtime = Overtime
timer-show = Show timelimit countdown

# Help
helps-help = Question Help
helps-message_instructor = Message instructor
helps-post_to_forum = Post to forum
helps-video = Video
helps-read = Read
helps-written_example = Written Example

# Unload warnings
unload-alert = Alert
unload-unsubmitted_questions = You have entered answers that have not yet been submitted. Are you sure you want to leave?
unload-unsubmitted_assessment = You have not submitted your assessment for grading yet. Don't forget to come back and do that.
unload-unsubmitted_done_assessment = You have attempted every question, but have not submitted your assessment yet. Don't forget to come back and do that.
unload-unsubmitted_work = You have unsubmitted work. If you leave now your work will be lost. Are you sure you want to leave?

# Pages
pages-next = Next Page

# Print
print-print_version = Print Ready Version
print-print = Print
print-hide_text = Hide Intro and Between-Question Text
print-show_text = Show Intro and Between-Question Text
print-hide_qs = Hide Questions
print-show_qs = Show Questions

# Video cued
videocued-start = Play video
videocued-continue = Continue video to {$title}
videocued-skipto = Jump video to {$title}

# Live poll
livepoll-settings = LivePoll Settings
livepoll-show_question_default = Show question on this screen when first selected
livepoll-show_results_live_default = Show results as they come in on this screen
livepoll-show_results_after = Show results on this screen after question is closed
livepoll-show_answers_after = Show correct answers automatically after question is closed
livepoll-use_timer = Use question timer
livepoll-seconds = seconds
livepoll-show_question = Show question on this screen
livepoll-show_results = Show results
livepoll-show_answers = Show correct answers
livepoll-stucnt = 
    { $n ->
        [0] No students
        [one] 1 student
        *[other] {$n} students
    }
livepoll-open_input = Open student input
livepoll-close_input = Close student input
livepoll-new_version = Generate a similar question
livepoll-waiting = Waiting for the instructor to start a question
livepoll-numresults = 
    { $n ->
        [one] 1 result received
        *[other] {$n} results received
    }
livepoll-answer = Answer
livepoll-frequency = Frequency

# LTI
lti-more = More options
lti-userprefs = User Preferences
lti-msgs = 
    { $n ->
        [0] Messages
        [one] Messages (1 new)
        *[other] Messages ({$n} new)
    }
lti-forum = 
    { $n ->
        [0] Forum
        [one] Forum (1 new)
        *[other] Forum ({$n} new)
    }
lti-use_latepass = Redeem LatePass

# Icons
icons-retake = Reattempts
icons-calendar = Date
icons-retry = Retries
icons-alert = Alert
icons-info = Info
icons-timer = Timer
icons-lock = Lock
icons-square-check = Check
icons-group = Group
icons-incorrect = Incorrect
icons-correct = Correct
icons-partial = Partially correct
icons-dot = Dot
icons-attempted = Attempted
icons-partattempted = Partially attempted
icons-unattempted = Unattempted
icons-print = Print
icons-left = Previous
icons-right = Next
icons-downarrow = Expand
icons-file = File
icons-close = Close
icons-message = Message
icons-forum = Forum
icons-video = Video
icons-eqned = Equation editor
icons-eqnedoff = Equation editor disabled
icons-more = More
icons-clipboard = Clipboard
icons-rubric = Rubric
icons-none = 

# Gradebook
gradebook-detail_title = Review Assessment Attempts
gradebook-started = Started
gradebook-lastchange = Last Changed
gradebook-time_onscreen = 
    { $all ->
        [true] Total time questions were on-screen (all attempts)
        *[other] Total time questions were on-screen
    }
gradebook-time_on_version = Time spent on this version
gradebook-due = Due Date
gradebook-originally_due = Originally Due
gradebook-make_exception = Make Exception
gradebook-edit_exception = Edit Exception
gradebook-attempt_n = Attempt {$n}
gradebook-version_n = Version {$n}
gradebook-scored_attempt = Scored attempt
gradebook-practice_attempt = Practice attempt
gradebook-submitted = Submitted
gradebook-scored = scored
gradebook-score = Score
gradebook-not_started = Not started
gradebook-not_submitted = Not submitted
gradebook-best_on_question = Grade is calculated on the best version of each question
gradebook-keep_best = Grade is calculated on the best assessment attempt
gradebook-keep_avg = Grade is calculated on the average of all assessment attempts
gradebook-keep_last = Grade is calculated on the last assessment attempt
gradebook-full_credit_parts = Full credit all parts
gradebook-full_manual_parts = Full credit all manually-graded parts
gradebook-full_credit = Full credit
gradebook-add_feedback = Add feedback
gradebook-feedback = Feedback
gradebook-feedback_for = Feedback for {$name}
gradebook-general_feedback = General feedback
gradebook-use_in_msg = Use in Message
gradebook-msg_student = Message Student
gradebook-clear_hdr = Delete confirmation
gradebook-clear_all = Delete all attempts
gradebook-clear_attempt = Delete this attempt
gradebook-clear_qwork = Delete work on question
gradebook-question_id = Question ID
gradebook-seed = Seed
gradebook-msg_owner = Message owner to report problems
gradebook-had_help = Had help available
gradebook-save = Save Changes
gradebook-savenext = Save and Next Student
gradebook-return = Return to Gradebook
gradebook-gb_score = Score in Gradebook
gradebook-override = Override score
gradebook-overridden = Overridden by teacher
gradebook-view_as_stu = View as student
gradebook-print = Print version
gradebook-filters = Filters and Options
gradebook-hide = Hide
gradebook-hide_perfect = Score = 100% (before penalties)
gradebook-hide_100 = Score ≥ 100% (after penalties)
gradebook-hide_nonzero = 0 < score < 100% (before penalties)
gradebook-hide_zero = Score = 0
gradebook-hide_fb = Questions with Feedback
gradebook-hide_nowork = Questions without Work
gradebook-hide_unans = Unanswered questions
gradebook-quick_grade = Quick grade
gradebook-saving = Saving...
gradebook-saved = Saved
gradebook-save_fail = Error saving
gradebook-clear_completely_msg = Delete all student attempts, as if the student never started the assessment. If the student takes the assessment, they will get new versions of all questions.
gradebook-clear_all_work_msg = Delete all student work, but retain the most recent question versions.
gradebook-clear_attempt_regen_msg = Delete this assessment attempt completely. If the student retakes the assessment, they will get new versions of all questions.
gradebook-clear_attempt_msg = Delete work on this attempt. Student will be able to redo this attempt with the same question versions.
gradebook-clear_qver_regen_msg = Delete this question version entirely.
gradebook-clear_qver_regen_msg2 = Delete student work on this question version, and generate a new version of the question.
gradebook-clear_qver_msg = Delete student work on this question version, but keep the version.
gradebook-clear_warning = WARNING: This action will delete student data and CAN NOT be undone.
gradebook-unsaved_warn = Warning: You have unsaved score or feedback changes. If you change versions now, your changes will be discarded.
gradebook-unsubmitted = This assessment attempt has not been submitted for grading.
gradebook-show_tries = Show all tries
gradebook-show_penalties = Show applied penalties
gradebook-show_autosaves = Show autosaves
gradebook-all_tries = All Tries
gradebook-part_n = Part {$n}
gradebook-try_n = Try {$n}
gradebook-autosaves = Autosaves
gradebook-autosave_info = Autosaves have been entered by the student but not submitted for grading, so are not included in the scoring.
gradebook-autosave_byassess = Autosaves will be scored when the assessment attempt is submitted for scoring.
gradebook-view_edit = View/Edit Question
gradebook-show_all_ans = Show All Answers
gradebook-show_all_work = Show All Work
gradebook-no_versions = No assessment attempts available to view yet
gradebook-minutes = minutes
gradebook-avail_never = Grade is currently hidden by the teacher.
gradebook-avail_manual = Grade is currently hidden by the teacher.
gradebook-avail_after_take = Will show after you submit an assessment attempt.
gradebook-avail_after_due = Will show after the due date.
gradebook-avail_after_lp = Will show after the LatePass period.
gradebook-latepass_blocked_practice = Use of a LatePass is currently blocked because the student viewed the assessment in practice mode.
gradebook-latepass_blocked_gb = Use of a LatePass is currently blocked because the student viewed the assessment answers in the gradebook.
gradebook-latepass_blocked_lpcutoff = Use of a LatePass is currently blocked because it is past the LatePass cutoff date.
gradebook-latepass_blocked_courseend = Use of a LatePass is currently blocked because it is past the course end date.
gradebook-latepass_blocked_pastdue = Use of a LatePass is currently blocked because it is past the due date, and LatePasses are set to only allow use before.
gradebook-latepass_blocked_toolate = Use of a LatePass is currently blocked because it is too far past the due date to be reopened using the allowed number of LatePasses.
gradebook-latepass_blocked_toofew = Use of a LatePass is currently blocked because the student does not have enough LatePasses.
gradebook-clear_latepass_block = Clear Block
gradebook-showwork = View Work
gradebook-hidework = Hide Work
gradebook-show_excused = Show Auto-Excused
gradebook-hide_excused = Hide Auto-Excused
gradebook-excused_list = Based on the results of this assessment, the following assessments have been excused:
gradebook-show_endmsg = Show End Message
gradebook-hide_endmsg = Hide End Message
gradebook-has_timeext = Has a {$n} minute time limit extension available
gradebook-used_timeext = Used a {$n} minute time limit extension
gradebook-attemptext = Exception given for {$n} additional versions
gradebook-preview_files = Preview All Files
gradebook-introtexts = Intro and between-question text
gradebook-floating_scoreboxes = Floating Scoreboxes
gradebook-sidebyside = Side-by-Side
gradebook-no_edit = You are in the gradebook. You cannot edit answers or submit questions from here.
gradebook-activitylog = Activity Log
gradebook-nextq = Next Question
gradebook-prevq = Previous Question
gradebook-oneatatime = One at a time
gradebook-a11yalt = Accessible alternative
gradebook-set_as_last = Move to Last
gradebook-setaslast_warn = This will move this attempt to be the last attempt, making it the scored attempt
gradebook-manualstatus0 = Grade has not been released to the student
gradebook-manualbutton0 = Release grade
gradebook-manualstatus1 = Grade has been released to the student
gradebook-manualbutton1 = Un-Release grade
gradebook-release_on_save = Release grade to student on Save

# Work
work-add = Add Work
work-hide = Hide work entry
work-noquestions = No questions require work
work-save = Save Work
work-duein = Work must be submitted by {$date}
work-save_continue = Save Work and Continue
work-add_prev = You can still show or attach work for the previous attempt
work-remove = Are you sure you want to remove this file?

# Regions
regions-questions = Questions and text
regions-q_and_vid = Video and questions
regions-pagenav = Page navigation
regions-qnav = Question navigation
regions-qvidnav = Video and question navigation
regions-aheader = Assessment info

# Links
links-settings = Settings
links-questions = Questions

# LatePass reasons
latepass-reason0 = LatePasses are not enabled.
latepass-reason2 = LatePasses cannot be used because it is past the LatePass cutoff time.
latepass-reason3 = LatePasses cannot be used because it is past the course end date.
latepass-reason4 = LatePasses cannot be used because it is past assessment end date, and LatePasses must be used before that.
latepass-reason5 = LatePasses cannot be used because it is too far past the due date for the allowed number to LatePasses to reopen it.
latepass-reason6 = LatePasses cannot be used because you do not have enough LatePasses to reopen this assesssment.
latepass-reason7 = LatePasses cannot be used because you have opened this assessment in Practice Mode, and that blocks use of LatePasses.
latepass-reason8 = LatePasses cannot be used because you have reviewed this assessment in the Gradebook, and that blocks use of LatePasses.
latepass-reason9 = LatePasses cannot be used because you are out of attempts.