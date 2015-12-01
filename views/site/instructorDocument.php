<?php
use yii\helpers\Html;
use app\components\AppUtility;

$this->title = AppUtility::t('Documentation',false);
?>

<div class="item-detail-header">
    <?php echo $this->render("../itemHeader/_indexWithLeftContent",['link_title'=>['Home'], 'link_url' => [AppUtility::getHomeURL()], 'page_title' => $this->title]); ?>
</div>

<div class = "title-container">
    <div class="row">
        <div class="pull-left page-heading">
            <div class="vertical-align title-page"><?php echo $this->title ?></div>
        </div>
    </div>
</div>
<div class="tab-content shadowBox padding-one-em scroll-x" >
<h2><?php AppUtility::t('Guides')?></h2>
<h4><?php AppUtility::t('Use Guides')?></h4>
<ul>
    <li><a href="<?php echo AppUtility::getHomeURL()?>docs/GettingStarted.pdf" target="_blank"><?php AppUtility::t('Getting Started in WAMAP
    version is specific to the WAMAP installation of IMathAS, but applies to any installation.')?></a> <?php AppUtility::t('(PDF file).  A walkthrough of creating a course in WAMAP, with screenshots.  This
        ')?></li>
    <li><a href="#getting-started"><?php AppUtility::t('Getting Started in IMathAS')?></a>.  <?php AppUtility::t('A walkthrough of creating
        your first IMathAS course.')?></li>
    <li><a href="#common-task"><?php AppUtility::t('Common Tasks in IMathAS');?></a>.  <?php AppUtility::t('A walkthrough of common instructor activities.')?></li>
    <li><a href="#manage-question-library"><?php AppUtility::t('Managing Libraries and Questions in IMathAS')?></a>.  A guide to the library and question
        set management tools</li>
    <li><a href="#administrator"><?php AppUtility::t('Administration');?></a>.  <?php AppUtility::t('A guide to administering IMathAS for Full and Group Admins')?>.</li>
    <li><a href="#diagnostics"><?php AppUtility::t('Diagnostics')?></a>. <?php AppUtility::t(' A guide to setting up diagnostic assessment login pages')?>.</li>

</ul>


<h4><?php AppUtility::t('Video Guides')?></h4>
<ul>
    <li><a href="#getting-started"><?php AppUtility::t('Getting Started in IMathAS')?></a> <?php AppUtility::t('video guide')?>.</li>
    <li><a href="#"><?php AppUtility::t('Training Videos')?></a>.  <?php AppUtility::t('A complete training course set of video guides')?>.</li>
    <li><a href="#"><?php AppUtility::t('Examples of Course Layouts')?></a>.  <?php AppUtility::t('Some ideas for how to lay out a course page')?></li>
    <li><a href="#">Using Course Templates</a>.  <?php AppUtility::t('How to copy pre-created course templates to use course assignments created by your colleagues')?>
        .</li>
    <li><a href="#"><?php AppUtility::t('Video versions of several other guides')?></a><?php AppUtility::t('are also available')?>.</p></li>
</ul>


<h4><?php AppUtility::t('Question Writing Guides')?></h4>
<ul>
    <li><a href="#intro-question-writing"><?php AppUtility::t('Intro to Question Writing in IMathAS')?></a>.  <?php AppUtility::t('A step-by-step guide for writing
        your first IMathAS question')?>.</li>
    <li><a href="#more-question-examples"><?php AppUtility::t('More Question Examples')?></a>.  <?php AppUtility::t('Examples of several questions, with explanation')?>.</li>
    <li><a href="#question-oddities"><?php AppUtility::t('Question Oddities')?></a>.  <?php AppUtility::t('Common pitfalls and oddities in the IMathAS question language')?>.</li>
    <li><a href="<?php echo AppUtility::getHomeURL()?>docs/langquickref.doc" target="_blank"><?php AppUtility::t('Language Quick Reference') ?></a>.  <?php AppUtility::t('Short document with quick function reference')?>.</li>
</ul>


<h2><?php AppUtility::t('Documentation')?></h2>
<ul>
    <li><a href="<?php echo AppUtility::getHomeURL()?>docs/help.html" target="_blank"><?php AppUtility::t('Help file')?></a>.  <?php AppUtility::t("Detailed documentation of all of IMathAS's features and
question language")?></li>
    <li><a href="<?php echo AppUtility::getHomeURL()?>docs/AccessingOnlineHomeworkinWAMAP.doc"><?php AppUtility::t('Getting enrolled for students')?></a>.<?php AppUtility::t('Not so much documentation, but
        a word document containing instructions for students to sign up and enroll in your course.  Suitable for including
        in your syllabus or handing out to students')?></li>
    <li><a href="#asciimath-syntax"><?php AppUtility::t('ASCIIMath syntax reference')?></a>.  <?php AppUtility::t('A reference sheet for ASCIIMath symbol entry')?>.</li>
    <li><a href="#installation"><?php AppUtility::t('Installation')?></a>.<?php AppUtility::t("Step-by-step installation instructions, part of the system's
        readme file.  This is only needed for server installation - teachers should not need this")?></li>
</ul>

<p><?php AppUtility::t('Many of these guides were written with development grant support from the WA State Distance Learning Council')?></p>

<!--Getting started with IMathAS-->
<div id="getting-started">

    <h2><?php AppUtility::t('Purpose of this document')?></h2>
    <p><?php AppUtility::t('This document will guide you through the process of creating your first IMathAS
        course and setting up a few items and assessments.  This is not a comprehensive
        guide; please refer to the')?> <a href="<?php echo AppUtility::getHomeURL()?>docs/help.html" target="_blank"><?php AppUtility::t('help file')?></a> <?php AppUtility::t('for more
        detailed information on any process')?>.</p>

    <p><?php AppUtility::t('This document presumes that you have Limited Course Creator rights.  If you have
        Teacher rights, you will not be able to add a new course to the system yourself;  contact
        your IMathAS admin to setup the course, then start this document at step 2')?>. </p>

    <h2>1. <?php AppUtility::t('Adding a New Course')?></h2>
    <ol>
        <li><?php AppUtility::t('After logging in to IMathAS, click on "Go to Admin Page" in the grey control panel box')?>.</li>
        <li><?php AppUtility::t('Click the "Add New Course" button')?>.</li>
        <li><?php AppUtility::t('Enter your course name and an enrollment key.  The course name is the name that will show
            up in your "Classes You\'re Teaching" list and in the student\'s "Classes You\'re Taking" list.  The
            enrollment key is the password that students must enter to enroll themselves in your course')?>.</li>
        <li><?php AppUtility::t('Click "Submit"')?>.</li>
        <li><?php AppUtility::t('Back at the Admin page, note the Course ID of the course you just created.  Your students
            will need both your Course ID and enrollment key to sign up for your course')?>.</li>
        <li><?php AppUtility::t('Click on the Course Name to enter your course.  In the future, the course will show up on your
            Home Page under "Classes You\'re Teaching"')?>.
    </ol>

    <p><em><?php AppUtility::t('Note')?></em>: <?php AppUtility::t('If you have Admin or Course Creator rights, you will not automatically be
        registered as instructor for the course.  To do so, click the "Add/Remove Teachers" link, then
        click the "Add as Teacher" link next to your name')?>.</p>

    <h2>2.  <?php AppUtility::t('The Course Page')?></h2>
    <p><?php AppUtility::t('When you first access your course, the only thing you will see is two grey control panel
    boxes.  To familiarize yourself with the control options, here are some brief descriptions')?>:</p>

    <ul>
        <li><strong><?php AppUtility::t('Add an item')?></strong>:  <?php AppUtility::t('Used to add items to your course')?></li>
        <li><strong><?php AppUtility::t('List Students')?></strong>:<?php AppUtility::t('List the students in your course.  This page also provides options for
        importing students into your course and for setting testing due date exceptions')?>.</li>
        <li><strong><?php AppUtility::t('Show Gradebook');?></strong>: <?php AppUtility::t('Your course gradebook.  From here you can review individual students\'
        tests, adjust grades, accept overtime tests, and export the gradebook to a file');?>.</li>
        <li><strong><?php AppUtility::t('Manage Question Set')?></strong>:  <?php AppUtility::t('Search, modify, and add questions to the system question database')?>.</li>
        <li><strong>Manage Libraries</strong>:<?php AppUtility::t('Add to or modify the question library structure')?>.</li>
        <li><strong><?php AppUtility::t('Copy Course Items')?></strong>:  <?php AppUtility::t('Copy items in your course, or copy items from other instructors\' courses')?></li>
        <li><strong><?php AppUtility::t('Export/Import Course Items')?></strong>:  <?php AppUtility::t('For sharing course items with other IMathAS installations')?>.</li>
        <li><strong><?php AppUtility::t('Shift all Course Dates')?></strong>:  <?php AppUtility::t('Change all course item dates at one time')?>.</li>
        <li><strong><?php AppUtility::t('Mass Change Assessments')?></strong>:  <?php AppUtility::t('Change several assessments\' settings at once')?>.</li>

    </ul>
    <p><?php AppUtility::t('Once you add items to the course page, each item will have an icon attached to it.  The icons change
    color based on the due date;  grey when unavailable, and green for 2 weeks or more until due date, changing to yellow
    then red as the due date approaches')?></p>

    <h2>3. <?php AppUtility::t('Add an Inline Text Item')?></h2>
    <p><?php AppUtility::t('Inline Text items display announcements or other text on the course page')?>.</p>
    <ol>
        <li><?php AppUtility::t('Select "Inline Text" from the "Add An Item" pulldown')?></li>
        <li>Enter a title for the text item, e.g. "Welcome to the Class!"</li>
        <li>Enter the item text.  The editor will allow you to format your text item.  The editor
        has been extended with support for math and graphs.
        <ul>
            <li>To add math, click the `(sum +)` button.  This will enter a red box with backticks into the editor.  Type
                in your math using calculator-style notation, for example x^2/5.  Click outside the red box, and the math
                will render</li>
            <li>To add a graph, click on the graph button.  Depending on the browser you're using, you'll either
                see an empty graph.  In Internet Explorer with AdobeSVG installed, double-click on it.  In other browsers,
                click on the graph, then hit the graph button again.  A popup window will come up allowing
                you to enter equations to graph</li>
        </ul></li>
        <li>Enter an "Available After" date and time.  Click the calendar icon to pop up a calendar.</li>
        <li>Enter an "Available Until" date and time.  Click the calendar icon to pop up a calendar.</li>
        <li>Click Submit</li>
        <li>You will be returned to the course page, and should see your text item displayed.  You can use the
        "Modify" link to alter the item.</li>
    </ol>

    <h2>4. Add a Block</h2>
        <p>Blocks are used to group similar items (by chapter or week, for example).  This allows you to hide
        from students a set of items by date.  Teachers will see non-current blocks collapsed, keeping the course
        page cleaner.</p>
    <ol>
        <li>Select "Block" from the "Add an Item" pulldown</li>
        <li>Enter a title for the block, e.g. "Chapter 1"</li>
        <li>Enter an "Available After" date and time.  Click the calendar icon to pop up a calendar.</li>
        <li>Enter an "Available Until" date and time.  Click the calendar icon to pop up a calendar.</li>
        <li>The "When not available" option allows you to decide whether to hide the block from students outside
        of the available dates, or whether to show the block condensed.  In a Week structure, you will probably
        want to hide the blocks from students.  If you're using IMathAS for practice tests, you may want to show the
        blocks collapsed so students can access old practice tests.</li>
        <li>Click Submit</li>
        <li>You will be returned to the course page.  If you the block shows an "Expand" button, click it to expand the
        block</li>
    </ol>

    <h2>5. Add a Linked Text Item</h2>
    <p>Linked Text items create a link on the course page to a longer text item, an uploaded file, or to an external website.</p>

    <ol>
        <li>Select "Add Linked Text" from the "Add an Item" pulldown inside of the block you created.  This will place the item inside
        the block.</li>
        <li>Enter a title for the item, e.g. "Syllabus"</li>
        <li>Enter a summary, if desired.  The summary will display on the course page</li>
        <li>Decide on what you want the item to be:
        <ul>
            <li>Typed text:  Enter the text in the text box provided</li>
            <li>Web link:  Enter the link in the text box, starting with "http://".  Example: "http://www.imathas.com"</li>
            <li>File:  Skip past the text box, and click the "Browse" button to select a file to upload</li>
        </ul></li>
        <li>Enter an "Available After" date and time.  Click the calendar icon to pop up a calendar.</li>
        <li>Enter an "Available Until" date and time.  Click the calendar icon to pop up a calendar.</li>
        <li>Click Submit</li>
        <li>You will be returned to the course page.  The title and summary for the item will be displayed.  Click on the
        title to view the item</li>
    </ol>

    <h2>6. Create an Assessment</h2>
    <p>Assessments are computer-gradable tests or homework sets.</p>
    <ol>
        <li>Select "Add Assessment" from the "Add an Item" pulldown inside of the block you created.</li>
        <li>Enter an Assessment Name.  Example: "Sample test"</li>
        <li>Enter a Summary.  This will display on the course page.  Example: "A sample test"</li>
        <li>Enter an Intro/Instructions.  This will display at the beginning of the assessment.  Example: "Answer all the questions"</li>
        <li>Enter an "Available After" date and time.  Click the calendar icon to pop up a calendar.</li>
        <li>Enter an "Available Until" date and time.  Click the calendar icon to pop up a calendar.</li>
        <li>Select Assessment Options
        <ul>
            <li>Time limit:  The number of minutes to allow for the test.  Enter 0 for no time limit</li>
            <li>Display method:  Select whether to display the full test at once, one question at a time, or allow students to
                skip around between questions.</li>
            <li>Default Points per Problem:  How many points each problem should be worth.  This can be overridden for individual
                questions</li>
            <li>Default Attempts per Problem:  How many tries a student should be allowed at each problem.  This can be overridden for
                individual questions</li>
            <li>Default Penalty per Attempt:  What percent penalty should be applied for missed attempts.  For example, if this is set at
                10%, then if a student misses one attempt then completes the problem successfully, they will receive 90% of possible
                points.  This can be overridden for individual questions.</li>
            <li>Feedback method:  How much detail should be provided to students.  This option also can set the test type.  The choices are:
                <ul>
                    <li>Final Score:  Only show the cumulative score at the end of the test</li>
                    <li>Score on each question at end:  Show the score on each question at the end of the test</li>
                    <li>Score on each as its submitted:  Show the score for a question once it is submitted.  This option only applies
                        to the "one question at a time" and "skip around" display methods.</li>
                    <li>Practice test:  Show the score on each question as it's submitted, allow a student to regenerate the test.  Scores
                        are not saved.</li>
                    <li>Homework:  Show the score on each question as it's submitted, allow a student to try a similar problem if they
                        miss one to regain full credit.</li>
                </ul>
            <li>Show answers:  Select when to make the answers available to the students.  In testing mode, you can select to make answers
                available immediately (through the gradebook), or only after the test due date.  For practice tests and homework, you can
                select to show answers after a number of attempts.</li>
            <li>Shuffle item order:  Randomize the question order for each student.  Otherwise the test will present questions in the
                order you specify</li>
            <li>All items same random seed:  Rarely needed.  Only needed if you need all questions to generate the same data.</li>
        </ul>
        An example set of options for homework might be:
        <ul>
            <li>Display method: Skip around</li>
            <li>Attempts per Problem: 3</li>
            <li>Penalty per Attempt: 40%</li>
            <li>Feedback method: Homework</li>
            <li>Show answers: After 2 attempts</li>
        </ul>
        Since the answer is shown after 2 attempts, these settings would allow a student to earn 20% of possible points
        just for trying the problem.
    </li>
    <li>Click Submit.  You will be taken to the Add Questions page</li>
    <li>Click the "Select Libraries" button. </li>
    <li>In the libraries pop-up, select the "Examples" Library and click "Use Libraries"</li>
    <li>Click "Search" to list all the items in the library.</li>
    <li>Click "Check/Uncheck All" to select all the problems.  Alternatively, you can use the "Preview" button to preview
        individual questions, and select the questions you wish to use.</li>
    <li>Click the "Add Selected (using defaults)" button to add the questions to the assessment. </li>
    <li>Click "Done"</li>
    <li>You will be returned to the course page</li>
    </ol>
    <p><em>Note:</em>  While an assessment is available, you will be unable to add or remove questions or change some
    test options.  This is to prevent messing up the scores of students who have already completed the test.  To modify the test
    after its available, you need to change the available dates.</p>

    <h2>7.  Set up a Discussion Forum</h2>
    <p>Discussion forums allow students to post questions or comments.  The discussion forum system in IMathAS
    is a simple threaded discussion setup, designed primarily to allow question-and-answer postings.</p>
    <ol>
        <li>Select "Add Forum" from the "Add an Item" pulldown</li>
        <li>Enter a Forum Name.  Example:  "Homework Questions"</li>
        <li>Enter a Description.  This will display on the course page.  Example: "Ask homework questions here"</li>
        <li>Enter an "Available After" date and time.  Click the calendar icon to pop up a calendar.</li>
        <li>Enter an "Available Until" date and time.  Click the calendar icon to pop up a calendar.</li>
        <li>Click Submit</li>
        <li>You will be returned to the course page.  Click on the Forum Name</li>
        <li>Click "Add New Thread"</li>
        <li>Enter a Subject for the posting.  Example:  "Welcome"</li>
        <li>Enter a message</li>
        <li>Select a post type.  These options are only available to teachers.
        <ul>
            <li>Regular post</li>
            <li>Displayed at top of list.  Good for announcements or instructions, this moves
                the post to the top of the thread list</li>
            <li>Displayed at top and locked.  Places the post at the top of the thread list, and
                does not allow replies to the post</li>
        </ul>
        </li>
        <li>Click Submit</li>
        <li>Click on your post Subject to view the message.</li>
        <li>Use the breadcrumbs (location listing at the top of the page) to return to the course page</li>
    </ol>

    <h2>That's it!</h2>
    <P>That's the basics of creating items in IMathAS.  There is a lot more to IMathAS, such
    as managing the question set or libraries, or writing new questions.</p>
</div>

<div id="common-task">
<h2>Purpose of this document</h2>
<p>This document will guide you through many common tasks an instructor would
    perform in IMathAS.  This is not a comprehensive
    guide; please refer to the <a href="<?php echo AppUtility::getHomeURL()?>docs/help.html" target="_blank">help file</a> for more
    detailed information on any process.</p>


<h2>Contents</h2>
<ul>
    <li><a href="#rearranging">Rearranging the Course Page</a></li>
    <li><a href="#lostpasswords">Resetting Lost Passwords</a></li>
    <li><a href="#exceptions">Making a Due Date Exception</a></li>
    <li><a href="#cleanup">End of Quarter Cleanup</a></li>
    <li><a href="#enrolling">Enrolling Students</a></li>
    <li><a href="#gradebook">Overriding Grades and other Gradebook issues</a></li>
    <li><a href="#copyitems">Copy Course Items</a></li>
    <li><a href="#assessments">Setting up Assessments</a></li>
</ul>



<a name="rearranging"></a>
<h2>Rearranging the Course Page</h2>
<p>If the items on the Course Page are not appearing in the order you'd like, you can
    use the pulldown select box above each item to change its order on the page</p>


<p>If the Course Page is getting cluttered, Blocks allow you to group items together
    by type, week, chapter, or any other grouping.  Create a new block by selecting &quot;Add a Block&quot; from
    the &quot;Add an Item&quot; pulldown in the grey control panel box.  Then use the order select box on course
    items to select &quot;Into #&quot;, where # is the current order number of the block.  For example, if your
    block is currently in position 3, you'd select &quot;Into 3&quot; on a course item to move it into that block.
    To move an item out of the block, choose &quot;Out of Block&quot; from the item's order select box.</p>


<a name="lostpasswords"></a>
<h2>Resetting Lost Passwords</h2>
If a student loses their password, select &quot;List Students&quot; from the grey control box on the course page.
Next to their name, you will find a &quot;Reset password to 'password'&quot; link.  Click that link to reset the
student's password to the default password of &quot;password&quot;.


<a name="exceptions"></a>
<h2>Making a Due Date Exception</h2>
<p>If a student has a valid reason for needing to take an assessment early or late, but you do not want
    to make it available for the whole class, you can make a due date exception for that one student.</p>


<p>Select &quot;List Students&quot; from the grey control box on the course page.  Next to the student's name, click
    on the &quot;Make Exception&quot; link.  Select the assessment you want to make an exception on.  Enter new
    start and end dates that will apply to just this student and click &quot;Submit&quot;</p>


<a name="cleanup"></a>
<h2>End of Quarter Cleanup</h2>
<p>When the end of the term rolls around, you may want to clean out your classroom
    in preparation for the next term.  Start out by saving a copy of your gradebook, if you need it.  Click on
    &quot;Show Gradebook&quot; from the grey control box on the course page.  Click &quot;Export Gradebook&quot; to export a CSV (comma
    separated values) spreadsheet copy of the gradebook.  This file can be opened in Excel or other spreadsheet programs.</p>
<p>To clear out enrolled students, click &quot;List Students&quot; from the grey control box on the course page.  Click the
    &quot;Unenroll All Students&quot; button, and confirm your choice.  This will unenroll all students from the course, clear out the
    gradebook, and remove all regular posts from the discussion forums.  Students will still be registered on IMathAS, just not
    enrolled in your course</p>


<p>You can quickly set up the course dates for the new term if your basic schedule will similar.  Click on the &quot;Shift all
    Course Dates&quot; link from the bottom grey control box on the course page.  You will specify the date shift for all items by
    changing the dates for one assessment.  Choose the assessment to base the date shift on, then select whether you want to give
    a new Available After date, or a new Available Until date.  Provide a new date, and click submit.  ALL course items will have their
    dates shifted the same number of days forward</p>


<a name="enrolling"></a>
<h2>Enrolling Students</h2>
<p>IMathAS allows flexible enrollment options.  It is most common to allow students to self-enroll in your course by
    providing them with the course ID and enrollment key.  These can be viewed on the Admin page (link from the Home Page) if
    you forget them, or obtained from your IMathAS administrator if you do not have access to the Admin page.</p>


<p>Alternatively, you can enroll students in your course.  If a student is already registered on the IMathAS system, you can
    enroll them by clicking "List Students" from the grey control box on the course page, then selecting "Enroll student with known
    username".  Then provide the student's username, and they will be enrolled in your course.  This is usually not an efficient way
    to enroll students in the course; it's mainly provided as a way to enroll guest users in the course</p>


<h4>Importing Students</h4>
<p>If you wish to both register and enroll a larger group of students, it is best to use the "Import Students from File" option on the
    "List Students" page.  To use this, you will need to have at least your student's names in a comma-separated-values (CSV) spreadsheet file.
    These can be exported from Excel or other spreadsheet programs.  Alternatively, if you just have a text file with one student's name on each
    line, that should work to.</p>
<ul>
    <li>On the "Import Students" form, start by selecting the file from your computer containing the student info</li>
    <li>Indicate whether the file contains a header row (a first row containing names, not data) </li>
    <li>Indicate which column the First Name is in</li>
    <li>Indicate whether the whole column entry or just part of it contains the first name</li>
    <li>Indicate which column the Last Name is in</li>
    <li>Indicate whether the whole column entry or just part of it contains the last name</li>
    <li>Indicate which column contains an email address, or 0 if there is no email column</li>
    <li>Indicate whether a column contains a desired username (such as student ID number), or whether to form a username
        from the first and last name</li>
    <li>Click Submit to both register the students on IMathAS and enroll them in your course</li>
</ul>
<p>As an example, at my school, we have an on-line "Instructor Briefcase" from which we can access class rosters.  I highlight all the
    data, copy it, and paste it into Excel, then export it as a CSV file.  A line of this file looks like:<br/>
<pre>2, 555-55-5555, Doe Jane R, , UWSS, 253-555-555, janedoe@hotmail.com</pre></p>


<p>To use this file, I specify that the First Name is the second word in the entry in the 3rd column, the Last Name is the first word
    in the entry in the 3rd column, the email address is in column 7, and the desired username is in column 2</p>


<a name="gradebook"></a>
<h2>Overriding Grades and other Gradebook issues</h2>
<p>In the Gradebook, you can click on a student's grade to view the version of the test they worked on and the answers
    they entered.</p>


<p>If you think they deserve more credit than
    was awarded, replace the given grade with the grade you want.  Be sure to click the &quot;Record Changed Grades&quot; button at the
    bottom of the page when you're done.</p>


<p>If a student went over the time limit on an assessment, an (OT) will show up next to their grade.
    An overtime grade is not computed in the student's overall grade.  To allow an overtime assessment to count
    towards their grade, click on their grade to view their test, and click the &quot;Clear overtime and accept grade&quot; link.</p>


<p>If a student runs into a problem and you wish to allow them to retake an assessment, click on their grade in the Gradebook
    to view their test.  Click the &quot;Clear Attempt&quot; link to clear their attempt.  Note that this clears all scores and attempts
    records for that student's assessment, including correct answers.</p>


<a name="copyitems"></a>
<h2>Copying Course Items</h2>
<p>Course items, including Assessments and full course layouts, can be shared between IMathAS users, both on the same
    installation and between servers.</p>
<p>To share items with a user at another server, using Import/Export Course Items.  For users on the same server,
    use Copy Course Items</p>


<p>To share your course, click &quot;Copy Course Items&quot; in the grey control box on the Course Page.
    Select the course you wish to copy from.  If the course is not one of your courses, you will need to supply
    the course enrollment key to show you have permission to copy from that course.  Once you have selected the course,
    click the &quot;Select Course Items&quot; button</p>


<p>Use the checkboxes to select the items you wish to copy.  If you copy blocks along with items, the items
    in the block will remain in the block after copying.  If you don't copy the block, the items will be
    placed on the main course page.</p>


<p>If you wish, you can append a phrase to the title of the copied items.  For example, if you're copying
    a quiz to create a review, you might append &quot; - Review&quot;.</p>


<p>Finally, specify if you want the copied items to be placed onto the main course page, or into an existing
    block in your course.  Note that if you place items into a block, only regular items will be copied; blocks
    can not be copied into blocks.</p>


<p>When done, click &quot;Copy Items&quot; to copy the items.</p>


<a name="assessments"></a>
<h2>Setting up Assessments</h2>
<p>The <a href="<?php echo AppUtility::getHomeURL()?>docs/gettingstarted.html">Getting Started</a> guide explained the assessment options.  Here are some other
    aspects of setting up assessments</p>
<h4>Arranging and Grouping Questions</h4>
<p>Once you have added questions to an assessment, you can use the pulldown select boxes next to each question to
    rearrange the question order.  This is, of course, not necessary if you select the Shuffle assessment option, which randomizes
    question order.<p>


<p>In addition to rearranging questions, you can group questions into a "mini-pool".  At the beginning of the Questions in Assessment
    section, change the "Use select boxes to" option to "Group Questions".  Then if you, for example, go to the third question in the
    assessment, and choose "1" for its select box, the third question is grouped with the first.  When the assessment is generated
    for each student, one question in the group will randomly be selected.  You can select as many questions as you'd like in each group,
    and can remove items from the group by selecting the "Ungroup" link next to the question you wish to pull out of the group.</p>


<h4>Changing Settings for Individual Questions</h4>
<p>Some questions (for example, multiple-choice questions) you may not wish to allow unlimited attempts on, while you may
    wish to allow unlimited attempts on free-answer questions.</p>
<p>When you add an individual item (by clicking the "Add" link rather than the "Add Selected (using defaults)" button), you will
    be prompted to enter the question's settings.  You can also change settings for already added questions by clicking the "Change" link
    in the Settings column.  You can specify the point value of the problem, allowed attempts, and penalty per attempt for individual
    questions.  Leave the entries blank or enter 9999 to use the assessment default values</p>


<h4>Categorizing Questions</h4>
<p>After adding questions to an assessment, click the "Categorize Questions" button to categorize the questions
    in the assessment.  Categorization allows you and students to see a score breakdown by question category.  The breakdown
    shows to students at the end of an assessment, and is also displayed in the gradebook when you click on an assessment to
    view detail.</p>


<p>In the question categorization page, you will see each question listed, followed by a category pull-down.  By default, the
    list contains the names of all libraries containing the question.  If you'd like to define a category not in the lists, type in
    the new category name in the box provided and click the "Add Category" button.  The new category name will now appear
    in the pull-down lists next to each question, and can be selected to assign the question to that category.  After assigning all questions
    to a cateogry, click the "Record" button.</p>


<h4>Print Versions</h4>
<p>After adding questions to an assessment, you can print one or multiple versions of the asessment
    by clicking the "Create Print Version" button.</p>


<p>On the first page, you will be asked what you would like to include in the test header.
    You will also be asked to enter your print margins setup. These can be found by choosing "Page Setup"
    from the "File" menu in your browser. In the Page Setup you may also wish to remove the default
    header and footer materials included in printouts by your browser. </p>


<p>On the next page, you will see alternating blue and green rectangles indicating the size of
    pages. Use the resizing buttons next to each question to increase or decrease the space after
    each question until the questions fall nicely onto the pages. You can use Print Preview in your
    browser to verify that the print layout looks correct. After you have completed the print layout,
    you will be given the chance to specify additional print options. Longer questions, such as those
    with graphs, may appear cut off in the print layout page. Be sure to resize those questions to
    show the entire question.</p>


<p>On the next page, select how many versions of the test you would like to generate, and
    whether you'd like to generate answer keys. After hitting continue, you print version of the
    test will be displayed. Choose Print in your browser to print your tests.</p>
</div>

<!--Managing libraries and questions.-->
<div id="manage-question-library">

    <h2>Purpose of this document</h2>
    <p>This document will guide you through managing questions and libraries in IMathAS.
        These tasks are commonly done when you are writing new questions or recategorizing
        existing questions.  This is not a comprehensive
        guide; please refer to the <a href="<?php echo AppUtility::getHomeURL()?>docs/help.html" target="_blank">help file</a> for more
        detailed information.</p>

    <h2>Managing Libraries</h2>
    <p>The question libraries categorize questions on the IMathAS system.  Having a
        clear library structure makes it easier for instructors to find the questions they
        need when writing assessments</p>

    <p>On busy systems with many instructors who write questions, it is advisable for each
        instructor to create a Private "scratch" library in which they can create and test questions before
        making them available by assignment into public libraries</p>

    <p>Additionally, an instructor might find it useful to create a library for each of their classes, and
        assign to those libraries the questions they feel might be useful in assessments</p>

    <h4>Accessing Library Management</h4>
    <p>Click on the "Manage Libraries" link in the grey control box on the course page.  The
        entire library structure will be displayed</p>

    <h4>Adding Libraries</h4>
    <p>You can add a library by clicking the "Add New Library" button.  Enter a name for the library.  Then
        select the library Rights.  There are six levels of library rights:</p>
    <ul>
        <li>Private: Only the library owner can access the library.  It is not displayed on other instructor's
            library lists.</li>
        <li>Closed to group, private to others: Anyone from the owner's group can view and access the library, but only the owner can
            add new questions.  The library is hidden from instructors outside the owner's group.</li>
        <li>Open to group, private to others:  Anyone from the owner's group can view, access, or add new questions to the library.
            The library is hidden from instructors outside the owner's group.</li>
        <li>Closed to all: Anyone can view and access the library, but only the owner can add new questions to the library</li>
        <li>Open to group, closed to others: Anyone from the owner's group can view, access, or add new questions to the library.
            Anyone from outside the owner's group can view and access the library, but can not add new questions to the library.</li>
        <li>Open to all:  Anyone can view, access, or add new questions to the library</li>
    </ul>
    <p>Library rights are also influenced by child libraries.  For example, if a child library has Open rights and the parent
        is set as Private, the parent library will still be displayed so that users can access the child library.</p>

    <p>Depending upon the system configuration, you may be limited to creating libraries that are private
        to others outside their group.  If this is the case, a you will need to contact your Group Administrator to create a library
        that is closed or open to all.</p>

    <p>After selecting Rights, select the Parent library.  Click the "Select Library" button to pop up a list of libraries.
        Only parent libraries and empty libraries can act as parents.  The new library will be created as a child library inside
        the selected parent library (like a subdirectory in your computer file system)</p>

    <h4>Modifying Libraries</h4>
    <p>To change a library's name, its rights, or its parent, click the "Modify" link next to the library's name.  If you
        move a library with children, the children will remain inside the parent after it is moved.</p>

    <p>You can change the parent of several libraries at once by clicking the checkboxes next the libraries' names and clicking the
        "Change Parent" button near the top of the page</p>

    <h4>Deleting Libraries</h4>
    <p>You can delete a library by clicking the "Delete" link next the library's name, or by selecting the checkboxes next to
        several libraries and clicking the "Delete" button near the top of the page.  When you delete a library, you will be
        given the option to delete the contained questions, or move them to the unassigned library.  A couple notes:</p>
    <ul>
        <li>A library with children cannot be deleted until the children libraries are deleted</li>
        <li>If a question is contained in more than one library, deleting the library will never delete the question.</li>
    </ul>

    <h4>Transferring Libraries</h4>
    <p>If you are the owner of a library and want to transfer that ownership to another user, you can do so by clicking the "Transfer"
        link next the library's name, or by selecting the checkboxes next to several libraries and clicking the "Transfer"
        button near the top of the page.</p>

    <h2>Managing Questions</h2>
    <p>The Question Set contains the actual IMathAS questions.  The Question Set Manager allows you to
        browse the question set, add new questions, reassign questions to libraries, and make other changes.</p>

    <h4>Browsing the Question Set</h4>
    <p>Begin a search by clicking the "Select Libraries" button.  You can select one or more libraries to view
        at a time.  The contents of the library will not actually be displayed until you click the "Search" button.  You
        can optionally add a search term in the search box to limit the questions displayed.  Currently the search box can
        only handle single word search terms</p>

    <p>In addition to the question description, the question type, how many assessments the question is current being used in,
        and the ownership of the question is displayed.  You can click any of the column headers to sort the table by that value</p>

    <h4>Previewing Questions</h4>
    <p>Click the "Preview" button next to the question's description to pop up a window allowing you to preview and test
        out the question</p>

    <h4>Adding Questions</h4>
    <p>To start a new question from scratch, click the "Add New Question" button.  By default, the question will be
        assigned to the libraries you are currently viewing, but you can the assignment in the question writing form</p>

    <p>To create a new question based on an existing question, click the "Template" button next the question's description.
        This will create a new question, leaving the original untouched, copying the code from the original</p>

    <h4>Modifying/Viewing Questions</h4>
    <p>Click the "Modify" link next to a question's description to modify its code.  If you see a "View" link instead of a "Modify" link,
        then the question owner has not given permissions that allow you to modify the question.  You can view the question's code
        by clicking the "View" link.</p>

    <p>The "Modify / View" link also allows you to change a question's library assignment.  Click "Modify" or "View", then change the
        "My Library Assignments:" selection.  Note that library assignments can be made by anyone, but only the person who made the assignment
        can remove the question from that library.</p>

    <h4>Assigning Libraries</h4>
    <p>As mentioned above, you can change a question's library assignment by clicking the "Modify" or "View" link.</p>

    <p>Alternatively, click the checkbox
        next to the question(s) description, and click the "Library Assignment" button near the top of the page.  Select the
        library/libraries you wish to assign the question to.  You can deselect libraries to remove the
        questions from existing library assignments you've made.</p>

    <h4>Deleting a Question</h4>
    <p>You can delete a question by clicking the "Delete" link next the question's name, or by selecting the checkboxes next to
        several questions and clicking the "Delete" button near the top of the page.</p>

    <h4>Transferring Ownership</h4>
    <p>If you are the owner of a question and want to transfer that ownership to another user, you can do so by clicking the "Transfer"
        link next the question's name, or by selecting the checkboxes next to several questions and clicking the "Transfer"
        button near the top of the page.</p>
</div>

<div id="administrator">
    <h2>Purpose of this document</h2>
    <p>This document contains information on administrating an IMathAS installation.  For
        information on specific features, you might find more info in the <a href="<?php echo AppUtility::getHomeURL()?>docs/help.html" target="_blank">help file</a>.</p>

    <h2>Administration Styles</h2>
    <p>There are several ways in which you can administer an IMathAS installation:</p>
    <ul>
        <li><b>Centralized course creation</b>:  An IMathAS administrator (full admin or group admin) creates courses and assigns them
            to teachers with "Teacher" rights, who can't create their own classrooms.</li>
        <li><b>Teacher-managed course creation</b>:  Instructors are given "Limited Course Creator" rights, allowing them to create
            courses for themselves.</li>
    </ul>

    <h2>Creating Instructor Groups</h2>
    <p>There is support in IMathAS for instructor/admin user Groups, usually intended to group instructors by discipline within
        a college installation, or by college in a multi-college installation.  Group administrators can be set up to handle administration for
        instructors within their group.
    </p>
    <p>Full Admins can create, change, or remove groups by clicking the "Edit Groups" link on the Admin page. </p>

    <h2>Adding Instructors</h2>
    <p>
        Instructors can be added manually, or you can use the "New instructor account request" page distributed with IMathAS to allow instructors
        to request accounts themselves.
    </p>
    <p>
        To add an instructor manaually, click the "Add New User" button at the bottom of the Admin page.
    </p>
    <p>
        If you use the "New instructor account request" page, an email will be sent to the email address you provided when you installed IMathAS
        (the $sendfrom address) to notify you a request has been made.  From the group selection pulldown at the bottom of the Admin page, choose
        "Pending" to list instructors who have made instructor account requests.  You can then change their rights to give them appropriate rights.
        Be aware that if you are requiring email account confirmation for students, unconfirmed student accounts may also show up in the Pending list.
    </p>

    <h2>Creating Courses</h2>
    <p>If the admin is creating courses for instructors, they can click the "Add new course" button to create a new course.  Once created,
        use the "Transfer" link to transfer ownership to the intended instructor.</p>

    <h2>Administator Access</h2>
    <p>When accessing Manage Question Set from the Admin page, an administrator with Group Admin or Full Admin rights will
        be able to modify questions written by members of their group, or anyone for Full Admins.
    </p>
    <p>
        Group Admins can list the courses of any of their group members (or anyone for Full Admins) and enter the course as an instructor
        to help with changes with the course</p>
</div>

<div id="diagnostics">
    <h2>Purpose of this document</h2>
    <p>This document contains information on setting up diagnostic logins in IMathAS</p>

    <h2>What Are Diagnostics</h2>
    <p>Diagnostics allow you to create a special login page for larger-scale diagnostic assessment.
        This provides a way for students to access and take the diagnostic without needing
        to register and enroll in a course.  Also, it delivers them the correct assessment based
        on a selection from a list of options, such as course they want to place into, their grade level, etc.</p>

    <p>Diagnostics are linked with courses, and grades are recorded into that course.  A special diagnostic gradebook
        view is available, tailored for use by testing centers to look up the most recent takers of the diagnostic.  The diagnostic
        can also be integrated with the tutor system in IMathAS to allow individual instructors access to view only their
        students' scores in a multi-instructor shared diagnostic.</p>

    <h2>Setting Up Diagnostics</h2>
    <p>If you have sufficient rights (Diagnostic Creator or Group/Full Admin), click the "Add New Diagnostic" button to set up
        a new diagnostic.  Diagnostics are linked with assessments in a course, so you should first
        create a course and add your diagnostic assessments to that course.</p>

    <p>On the first page, you will be asked for:
    <ul>
        <li>Diagnostic name: the name of your diagnostic to display to students on the login page</li>
        <li>Term designator: Can be changed each term for ongoing assessments to keep track of which term an assessment was taken in.
            Also will create a unique record for each term+student, so same student ID could take the assessment again in the different term.  As a consequence, if you
            choose to "Use Day" as the designator, than a student would not be able to continue a diagnostic the next day; they'd start over under a new
            unique receord.</li>
        <li>Linked with course:  What course contains your diagnostic assessments.  You must be listed as a teacher for the course</li>
        <li>Available:  Whether students can take the diagnostic</li>
        <li>Public:  Whether the diagnostic should be listed on the main Diagnostics list page.  If set to no,
            students will have to be provided with the direct link to the diagnostic.</li>
        <li>Allow Reentry:  Whether students should be forced to complete the diagnostic in one sitting, or whether you'll allow them
            to reaccess the test at a later time.  If Yes, you can set a testing window that limits the number of minutes after first accessing
            the diagnostic they can reenter the diagnostic.  This is convenient in case a student accidently exits the assessment or there is an internet
            issue.</li>
        <li>Unique ID prompt:  How you want to word the prompt for a unique user id.  A student ID number, phone number, or email address could work.</li>
        <li>ID entry format &amp; number of characters:  Lets you specify the format of the unique ID, so ensure valid IDs</li>
        <li>Allow access IP addresses:  IP addressed for which the diagnostic can be taken without a password.  If you want to
            allow access from anywhere, enter "*".  You can use "*" for a wildcard as well, e.g. 123.45.* to allow any IP beginning
            with 123.45.  If you are only going to use password access, leave this part blank.</li>
        <li>Passwords:  Passwords which will allow access to the diagnostic from other IP addresses.  Passwords are not case sensitive.  These
            passwords could be shared directly with a room full of testers (then removed later), or used by proctors to sign students into the diagnostic.</li>
        <li>Super passwords:  These passwords will override the "allow reentry" time limit, if you specified one earlier.  These passwords would be intended
            for use by instructors or proctors, and generally would not be shared directly with students.</li>
        <li>First level selector name:  Students will be asked to select two items from pull-down lists, with the values in the second
            depending upon the values in the first.  The choice in the first list also dictates which assessment is delivered.  For
            selector name, specify what this selection should be called, filling in the blank "Please enter your ______".  In many cases
            the two selectors will be Course and Instructor, or Course and Section</li>
        <li>First level selector options:  Add options for the first level selector.</li>
    </ul>
    For a placement-type test, the first level selector might be "course you want to place into" and the second level selector
    might be some demographic info like "when was the last math class you took?"</p>
    <p>
        On the second page, you will be asked for:
    <ul>
        <li>Second level selector name:  Secondary selection, with values depending upon the first-level selector choice.  Fill in the blank
            "Enter your _________".  Second level selectors will be become the student's section identifier in the course, allowing you to
            use the tutor system with diagnostics.  If you are running a multi-instructor diagnostic, using instructor names as the second
            level selector is appropriate.</li>
        <li>Deliver assessment:  For each first-level selector, specify which assessment should be delivered if the student
            selects that option.  You have the option to "force regen on reentry", which, if reentry is allowed, will on reentry delete the student's
            previous attempt and generate a new version of the diagnostic.</li>
        <li>Second level selector options:  For each first-level selector, specify the options for the second level selector.  There is a checkbox
            that will enable automatic alphabetization of the selectors after submitting.  If your second-level selector list will be the same for
            every first-level selector, you can save time by clicking the "Use these second-level selectors for all first-level selectors?" option.</li>
    </ul>
    <p>You must submit both pages before any changes are saved.  After submitting the second page, you will be provided with the
        direct access link to the diagnostic, which you can provide to students or use to create a link from another website.  </p>

    <p>In courses for which a diagnostic has been set up, the gradebook will display differently, showing the student unique ID,
        the term, and the first and second selector values in addition to the students' names and scores</p>

    <p>If you are doing a mass-testing situation, don't want proctors to have to enter passwords, and don't want to annouce a mass password for
        everyone to use, you can use the "One Time Passwords" link on the Admin page to generate a list of one-time-use passwords that can be
        distributed individually to students.</p>
    </hr>
</div>

<!--//////////////////////////////////////////////////// Writing questions and librarirs/////////////////////////////////-->

<div id="intro-question-writing">
    <h2>Purpose of this document</h2>
    <p>This document will guide you through the process of writing an example IMathAS
        Question.  This is meant to be an introduction.  This is not a comprehensive
        guide; please refer to the <a href="<?php echo AppUtility::getHomeURL()?>docs/help.html" target="_blank">help file</a> for more
        detailed information.</p>


    <h2>Getting Started</h2>
    <p>Start out by clicking the "Manage Question Set" link from the grey control
        box on the course page.  Click "Add New Question".  This will take you to the
        question editor</p>


    <h2>Question Parts</h2>
    <p>A question has several parts:</p>
    <ul>
        <li>Description: What displays to the instructor in the question list.  Be specific in
            your question descriptions.</li>
        <li>Use Rights:  What you will allow others to do with this questions</li>
        <li>Author: This is automatically set by the system.</li>
        <li>Assign to Library:  Which question library your question will be recorded in</li>
        <li>Question Type:  What the question type is (number, function, matrix, etc)</li>
        <li>Common Control:  Code common to both the question and the answer</li>
        <li>Question Control:  Code only needed for the question text</li>
        <li>Question Text:  Where you actually write the question to be displayed to students</li>
        <li>Answer:  Code defining the answer</li>
    </ul>
    <p><b>Note:</b> It is not necessary to use the Question Control and Answer boxes;  you can place all
        your control code in the Common Control box.  For short questions, using just
        Common Control and Question Text is fine.  For complicated questions, you may find using all four
        boxes helpful.</p>
    <p>Each question type has specific requirements.  You can click the "Writing Questions Help" link above
        the Common Control box to pop up that section of the help file</p>


    <h2>Description</h2>
    <p>For this example, let's write a question asking students to write the equation for a graphed parabola.
        In the description box, enter something like "Write equation for parabola, form y=a(x-b)^2+c"</p>


    <h2>Use Rights</h2>
    <p>Let's leave this as the default "Allow use, use as template, no modifications".  This allows other to use
        your question or derive new questions based on this one, but they cannot modify this question itself.</p>


    <h2>Assign to Library</h2>
    <p>If you have set up a scratch-work library, click the "Select Library" button and choose that library.  Otherwise, it
        is fine to leave this as "Unassigned" for now</p>


    <h2>Question Type</h2>
    <p>We want students to enter an equation for the curve, so select the type "Function" from the pull-down.</p>


    <h2>Common Control</h2>
    <p>To write our question, we need to pick values for a, b, and c.  In order for the question to change
        for each student, we need to have these values randomly chosen.  Enter:</p>
<pre>
$b,$c = nonzerodiffrands(-3,3,2)
$a = nonzerorand(-2,2)
$eqn = makepretty("$a*(x-$b)^2+$c")
</pre>
    <p>The first line defines two variables, $b and $c.  Notice that variables start with a dollar sign.  The randomizer
        we are using is "nonzerodiffrands" which, as you might guess, will return different, nonzero integers.  We have specified that
        we want numbers between -3 and 3, and want 2 of them.</p>
    <p>The second line defines the variable $a.  The randomizer "nonzerorand" returns a single nonzero random number, in this case between
        -2 and 2.</p>
    <p>If we simply entered the question as "$a*(x-$b)^2+$c", it will not display the way we want if $b or $c are negative.  To fix this, we
        use the function "makepretty", which cleans up double sign problems.  We put the equation in quotes because it is a string, not a calculation
        we want to perform.</p>


    <p>We have not defined the variables in our equation or its domain.  These default to "x" for the variable, and -5 to 5 for the domain,
        so we really don't need to in this case.</p>


    <h2>Question Control</h2>
    <p>We want to display a graph to the student.  We'll define the graph here:</p>
<pre>
$graph = showplot("$eqn,red",-6,6,-6,6)
</pre>
    <p>"showplot" creates a graph.  In quotes is the definition of the function to be graphed.  It's equation is given by y=$eqn,
        and we want it graphed in red.  Additional options allow defining a limited domain, open and closed dots, width, dashing, etc.
        The next numbers (-6,6,-6,6) define the viewing window.  Additional options allow changing the axis labeling, grid spacing, and the
        display size of the graph</p>


    <h2>Question Text</h2>
    <p>Time to actually write the question!  Enter</p>
<pre>
Find an equation for the graph shown below.  (&lt;i&gt;Hint&lt;/i&gt;: use the form `y=a(x-h)^2+k`)


$graph


`y=` $answerbox
</pre>



    <p>Notice the use of the HTML tag &lt;i&gt; to create italic text.  All HTML tags are
        valid in IMathAS questions.  An empty line, however, is automatically turned into a paragraph break. </p>
    <p>Notice also that the equation in the hint is enclosed in backticks, \`.  Anything enclosed in backticks
        is rendered as pretty math.  Enter the math using standard calculator-style notation.  There are also ways
        to display more advanced math (example: \`int_3^5 x^2 dx\` would display as `int_3^5 x^2 dx`)</p>

    <p>On the last line, the variable $answerbox is used.  This variable allows you to place the answer entry box
        anywhere in the question.  It is not necessary to do so, but in this case, I wanted to preface the answer box
        with "y=" so the student would know they don't need to enter the "y=" part of the equation</p>


    <h2>Answer</h2>
    <p>Time to specify the answer.  Enter:</p>
<pre>
$answer = $eqn
$showanswer = "`y=$eqn`"
</pre>
    <p>The first line defines the answer.  In this case, the answer was just the equation we used for graphing.  The second
        line defines the answer to show to students (if the show answers option is selected).</p>
    <p>We could have given more detail in the $showanswer.  For example, we could have written (without line breaks):</p>

<pre>
$testx = $b+1
$testy = $a*($testx - $b)^2+$c
$showanswer = "Notice the vertex is at ($b,$c).  Using the form `y=a(x-h)^2+k`,
   this gives `y=a(x-($b))^2+($c)`.  Plugging in the point ($testx,$testy)
   for `x` and `y` allows us to solve for a, leading to `y=$eqn`"
</pre>


    <h2>Try it Out</h2>
    <p>Click the "Save and Test Question" button at the bottom of the page.  This will save the question and pop up a window
        displaying the question.  Try entering the answer and clicking "Submit".  The system will display your score
        on the question, and display a new version of the question.  Testing a question several times is essential to ensuring
        that it is behaving the way you want, and you didn't miss something (like not using nonzero random numbers).</p>
    <p>When you are done, click "Update Question", or just navigate back to the Manage Question Set page.  You should now see
        your question in the list (unless you assigned it to a different library than you're currently viewing).</p>


    <h2>Use as Template</h2>
    <p>To create a new question, it is often quickest to not start from scratch.  If you click on the Template link next to any question,
        it will create a new question, starting with all the code from an existing question.  This way you can create a similar but new
        question without starting from scratch and without losing the original question.</p>


    <hr/>
</div>

<!--More question examples-->
<div id="more-question-examples">
<h2>Purpose of this document</h2>
<p>This document contains example IMathAS questions with explanation of the
    code used.  For detailed question language reference, please refer to the
    <a href="<?php echo AppUtility::getHomeURL()?>docs/help.html" target="_blank">help file</a>.</p>

<h2>Example of Function type</h2>
<h4>Common Control</h4>
<pre>
$a,$b = nonzerodiffrands(-8,8,2)
$variables = "x"
$domain = "-5,5"
</pre>
<p>
    The first line defines two variables, $a and $b, as different, nonzero random integers.
    The first two arguments specify that the integers should be chosen between -8 and 8.  The
    third argument specifies that 2 random integers should be chosen</p>
<p>
    $variables is used to define the variables in the expression.  If more than one variable
    is used, enter a list of variables, like $variables = "x,y,x".  This defaults to "x", so
    this line is not really necessary in this problem.</p>


<p>$domain specifies the domain on which the student's answer should be compared to
    the given answer.  Enter as a list "min,max".  The same domain will apply to all
    variables in the expression.  This defaults to -10 to 10</p>


<h4>Question Control</h4>
<pre>
$ansprompt = "Ans="
</pre>
<p>Rather than place the $answerbox in the question text, I'm going to have the
    system place the default answer box at the end of the question.  The $ansprompt

    variable specifies that the box should have "Ans=" displayed in front of the answer box</p>


<h4>Question Text</h4>
<pre>
Simplify `x^$a/x^$b`


Write your answer with positive exponents only.
</pre>


<h4>Answer</h4>
<pre>
$p = abs($a - $b)
$answer = "x^($p)" if ($a>$b)
$answer = "1/x^($p)" if ($a<$b)


$showanswer = "x^($a-$b) = $answer"


$requiretimes = "^,<2,-,=0"
</pre>
<p>The first three lines define the answer.  Note that it would have worked just
    fine to define <code>$answer = makepretty("x^($a-$b)")</code>, but because I want
    to use the answer in the $showanswer to show students later, I instead defined the answer
    using "if" statements.  The "if" allows you define different values to a variable depending
    on values of other variables</p>
<p>The $showanswer line defines the answer to show to students.  There is no default value
    for Function type questions, so you must specify something if you want an answer to be available
    to students.  In this case, I showed the first step as well as the answer</p>


<p>$requiretimes places format requirements on the student's answer.  The list in quotes is in pairs;
    the first value is the symbol to look for, and the second value indicates the number of times that symbol
    should appear.  In this example, the ^ symbol should show up less than two times, and the - symbol should
    show up zero times.  The first rule requires that students cannot simply reenter the original expression
    and get credit.  The second rule requires that students cannot enter negative exponents</p>

<h2>Example of Matching type</h2>
<h4>Common Control</h4>
<pre>
$qarr = array("`sin x`","`cos x`","`x^2`","`x^3`","`e^x`","`log x`","`2^x`")
$aarr = array("`cos x`","`-sin x`","`2x`","`3x^2`","`e^x`","`1/x`","`2^x ln2`")


$questions,$answers = jointshuffle($qarr,$aarr,4,5)

$questiontitle = "`f(x)`";
$answertitle = "`f'(x)`";
</pre>
<p>The first two lines define arrays of functions ($qarr) and their derivatives ($aarr)</p>


<p>The third line creates two new arrays, $questions and $answers, by jointly shuffling the arrays
    (retaining respective pairing), and picking 4 elements of the $qarr, and 5 elements of the $aarr.</p>


<p>The last two lines define the titles (column headers) for the $questions and $answers lists.</p>


<h4>Question Text</h4>
<pre>
Match each function with it's derivative.
</pre>


<h4>Answer</h4>
<p>There is no need to specify anything here</p>
<p>The Matching type requires a $questions array and $answers array.  The $questions will display on the left with
    entry boxes next to each.  The $answers will display on the right, lettered.  If each answer is used at most once,
    then you do not have to do anything else - the first entry of the $answers array will be assumed to be the answer to the first
    entry of the $questions array.  If there are more entries in $answers than $questions, the left over answers are presumed
    to never be used.  If you want an answer to be used more than once, you will need to define a $matchlist</p>

<h2>Load Library Example (Number type)</h2>
Example of using loadlibrary to access functions in a macro file (mean from stats library in this case)

<h4>Common Control</h4>
<pre>
$a = nonzerodiffrands(1,10,5)
</pre>


<p>This line defines an array variable $a to be 5 different nonzero integers between 1 and 10.  Note that since
    a single variable was defined, it was created as an array variable</p>


<h4>Question Control</h4>
<pre>
$table = showarrays("x",$a)
</pre>
<p>This defines $table using a standard display macro that creates a tabular display of the array $a
    with title (header) "x".  If you want to display two lists side-by-side, you can do so, for example: showarrays("x",$a,"y",$b)</p>


<h4>Question Text</h4>
<pre>
Find `bar x`


$table
</pre>


<p>Recall that items in backticks are rendered as math.  The math command "bar" will place a bar over the item that follows it</p>


<h4>Answer</h4>
<pre>
loadlibrary("stats")
$answer = mean($a)
</pre>
<p>The first line loads the stats macro library.  Admins can install new Macro libraries to extend the functionality
    of IMathAS.  The Macro Library Help link will show what libraries are currently installed and the functions they
    provide.</p>
<p>Here we are using the mean function from the stats library to determine the answer.</p>


<h2>Another Example of Matching Type</h2>
<h4>Common Control</h4>
<pre>$a,$b,$c = rands(-3,3,3)
</pre>
<p>This selects three random numbers between -3 and 3</p>
<pre>$cols = singleshuffle("red,green,blue")
</pre>
<p>shuffles the list of colors, placing it in the array $cols</p>
<pre>$graphs = array("$a*x^2+$b*x+$c,$cols[0]","2*$a*x+$b,$cols[1]","$a*x^3/3+$b*x^2/2+$c*x,$cols[2]")
</pre>
<p>We're going to be using the showplot macro.  The first argument is a
    single function or an array of functions.  In this case, we're giving an array of
    functions, though we're only specifying the function and the color.  There
    are other options available.</p>


<pre>$plot = showplot($graphs,-3,3,-5,5,off,off)

</pre>
<p>this actually calls the showplot macro.  After the
    function, the window is specified, then we're setting the
    labels to off, and grid is set to off</p>




<pre>$questions = array("`f(x)`","`f'(x)`","`int f(x)dx`")
$answers = $cols</pre>
<p>this defines the questions and answers.  Note that they
    are matched - the first entry in $answers is the answer
    the first entry in $questions.  Notice that the primary randomization
    in this question is the shuffling of the color array.</p>


<h4>Question Control</h4>
<pre>$questiontitle = "Function"
$answertitle = "Graph Color"</pre>
<p>these set titles for the list of questions and answers</p>


<h4>Question Text</h4>
<pre>Match each function with its graph


$plot</pre>
<h4>Answer</h4>
<p>Nothing is needed here.  The answers are automatically associated with the questions based
    on array order</p>


<h2>Example of Multipart Type</h2>
<h4>Common Control</h4></pre>
<pre>$anstypes = array("calculated","calculated")
$a,$b = nonzerodiffrands(-8,8,2)
$c = nonzerorand(-30,30)</pre>
<p>The first line defines that there will be two parts, both of
    type calculated.  Refer the help for valid anstypes.</p>
<p>The next two lines define our random variables</p>


<h4>Question Control</h4>
<pre>$question = makeprettydisp("{$a}x+{$b}y=$c")
</pre>
<p>Set up the equation</p>


<pre>$hidepreview[1] = true</pre>
<p>in some multipart questions, it might be useful to hide
    the preview button usually provided with calculated and
    function answer types.  You can set $hidepreview to
    hide the preview button.  Note that it is suffixed with
    a [1].  This specifies to apply the option to the
    second calculated type.  All options should be suffixed
    like this in a multipart problem unless the option applies
    to all parts of the problem.</p>
<p>Note that this is a silly example; there is no good reason
    to hide the preview on one part of this question but not the other</p>


<h4>Question Text</h4>
<pre>Find the x and y intercepts of $question


x-int: `x=`$answerbox[0]&lt;br/&gt
y-int: `y=`$answerbox[1]</pre>
<h4>Answer</h4>
<p>Note the use of the $answerbox above.  This places the
    answerboxes in the problem text.  Make sure you put the
    boxes in numerical order; entry tips are given assuming
    this.</p>


<pre>$answer[0] = $c/$a
$answer[1] = $c/$b
</pre>
<p>like with other options, the $answer also needs to be
    suffixed with the question part.</p>


<h2>Example of Number Type</h2>
<h4>Common Control</h4>
<pre>$a = nonzerorand(-5,5)
</pre>
<p>Set $a to be a nonzero random number between -5 and 5</p>


<pre>$b = rrand(.1,5,.1) if ($a &lt; 0)
$b = rrand(-5,-.1,.1) if ($a &gt; 0)
</pre>
<p>a decimal number between -5 and 5, with one decimal place.
    We're going to ensure that $a and $b are different signs
    using the "if" conditional</p>


<pre>$c,$d = nonzerodiffrands(-5,5,2)</pre>
<p>two different, nonzero integers</p>


<h4>Question Control</h4>
<pre>$prob = "`$a + $b + $c + $d`"
</pre>
<p>this could show up as:  -4 + -2.3 + 3 + -1
    the backquotes tell it to display as math</p>


<pre>$prob2 = makeprettydisp("$a + $b + $c + $d")</pre>
<p>if we want to simplify it like: -4 - 2.3 + 3 - 1</p>


<h4>Question Text</h4>
<pre>Find: $prob


or equivalently: $prob2</pre>
<h4>Answer</h4>
<pre>$answer = $a + $b + $c + $d</pre>
<p>for number, we just need to specify the answer.  No
    quotes here because we're calculating, not creating
    a display string</p>


<p>by default, numbers are allowed a .001 relative error.<br/>
    $reltolerance = .0001 would require a higher accuracy<br/>
    $abstolerance = .01 would require an absolute error under .01<br/>
    $answer = "[-10,8)" would accept any answer where
    `-10 &lt;= givenanswer &lt; 8`</p>


<h2>Example of Calculated Type</h2>
<h4>Common Control</h4>
<pre>$a,$b = randsfrom("2,3,5,7,11",2)
</pre>
<p>choose two numbers from a list.  Can also choose from
    an array</p>

<pre>$c = rand(1,10) where ($c % $a != 0)
$d = rand(1,10) where ($d % $b != 0)
</pre>
<p>the "where" statement is used with randomizers. It allows
    you to avoid a specific case.  In this case, we're requiring
    that $a not divide evenly into $c.  The modulus operator, %, gives
    the remainder upon division</p>


<pre>$answerformat = "reducedfraction"</pre>
<p>note that the student could enter 2/5*6/7 and get the
    correct answer.  We can prevent this by adding this line.  $answerformat = "fraction" is also
    an option, if you don't care if the answer is reduced.</p>


<h4>Question Control</h4>


<h4>Question Text</h4>
<pre>Multiply: `$c/$a * $d/$b`


Enter your answer as a single, reduced fraction</pre>


<h4>Answer</h4>


<pre>$answer = $c/$a * $d/$b</pre>
<p>like with the Number type, we supply a number as the
    answer.  The only difference is that the student can
    enter a calculation instead of a number</p>


<h2>Example of Multiple-Choice Type</h2>
<h4>Common Control</h4></pre>
<pre>$a,$b = nonzerodiffrands(-5,5,2)
</pre>
<p>pick two different nonzero numbers. The numbers are important here to ensure that all the
    choices will be different.</p>




<pre>$questions[0] = $a+$b
$questions[1] = $a-$b
$questions[2] = $a*$b</pre>


<p>we can either define the entire $questions array
    at once, or define each piece separately.  The former
    would look like:  $questions = array($a+$b,$a-$b,...</p>


<h4>Question Control</h4>
<pre>$displayformat = "horiz"
$text = makeprettydisp("$a+$b")</pre>


<p>The first line above will lay out the choices horizontally.  To do
    a standard vertical layout, just omit this line</p>


<h4>Question Text</h4>
<pre>Find $text</pre>


<h4>Answer</h4></pre>
<pre>$answer = 0</pre>
<p>Here the answer is the INDEX into the questions array
    that holds the correct answer.  Arrays are zero-indexed,
    so the first entry is at index 0.</p>
<p>In multiple-choice questions, the question order is automatically randomized
    unless you specify otherwise, so it's fine for $answer to always be 0; the location
    of the correct answer will be shuffled</p>


<h2>Example of Multiple Answer Type</h2>
<h4>Common Control</h4>
<pre>$questions = listtoarray("`sin(x)`,`sin^-1(x)`,`tan(x)`,`csc(x)`,`x^2`")</pre>
<p>the $questions array is a list of the options.
    The listtoarray macro converts a list of numbers or
    strings to an array.  Use calclisttoarray to convert
    a list of calculations to an array of numbers</p>


<h4>Question Control</h4>


<h4>Question Text</h4>
<pre>Select all the functions that are periodic</pre>


<h4>Answer</h4>


<pre>$answers = "0,2,3"
</pre>
<p>the answer here is a list of indexes into the $questions
    array that contain correct answers.  Remember that
    arrays are 0-indexed.  Like with multiple-choice, the question order is randomized automatically.</p>


<p>Normally, each part is given equal weight (each checkbox is worth 1/5 point).  If you wish
    to divide the point score only by the number of correct
    answers, use this line: $scoremethod = "answers"</p>


<h2>A Graphing Example (Multipart)</h2>
<h4>Common Control</h4>
<pre>$anstypes = listtoarray("number,number,number,number")
</pre>

<p>Specify the answer types.  In this case, four number answers</p>




<pre>$graphs[0] = "-x-5,black,-5,-1,,closed"
$graphs[1] = "-2x+3,black,-1,2,open"
$graphs[2] = "-2x+3,black,2,5,open"</pre>
<p>Define the graphs.  For each graph, it's:
    function,color,xmin,xmax,startmark,endmark</p>


<pre>$graphs[3] = "2,black,2,2,closed"
</pre>
<p>last one is really just a dot, but we define it as
    a function</p>


<pre>$plot = showplot($graphs,-5,5,-5,5,1,1)</pre>
<p>The inputs here are: graphs,xmin,xmax,ymin,ymax,label spacing,grid spacing</p>


<h4>Question Control</h4>
<p>this question is not randomized; it's just meant for
    illustration of graphing options.</p>


<h4>Question Text</h4>
<pre>The graph below is the function `f(x)`


$plot


Find `lim_(x-&gt;-1^+) \ f(x)`  $answerbox[0]


Find `lim_(x-&gt;-1^-) \ f(x)`  $answerbox[1]


Find `lim_(x-&gt;-1) \ f(x)`  $answerbox[2]


Find `lim_(x-&gt;2) \ f(x)` $answerbox[3]
</pre>
<p>the backslashes above add extra spacing between the
    limit and the f(x)</p>
<h4>Answer</h4>
<pre>
$answer[0] = 5
$answer[1] = -4
$answer[2] = "DNE"
$answer[3] = -1
</pre>
<p>Define the part answers.  "DNE" and "oo" (for infinity) are allowed string
    answers to number questions</p>

<hr/>
</div>

<!--question oddities-->
<div id="question-oddities">
    <h2>Purpose of this document</h2>
    <p>This document describes some of the common pitfalls and oddities of the IMathAS question language.
        For detailed question language reference, please refer to the
        <a href="<?php echo AppUtility::getHomeURL()?>docs/help.html" target="_blank">help file</a>.</p>

    <h2>IMathAS Question Writing Oddities</h2>

    <h4>Fractional Exponent Display</h4>
    <p>
        Fractional exponents do not seem to display well with MathML.  For example, x^(2/3) will display as `x^(2/3)`.
        The best approach is to try x^(2//3), which renders as `x^(2//3)`.  If you want to raise up the exponent higher,
        a silly trick to try is x^({::}^(2/3)).  The {::} creates an invisible item.  This renders as `x^({::}^(2/3))`.
    </p>

    <h4>Curly Braces</h4>
    <p>
        Beware of using curly braces {}.  While curly braces can be used for display or for grouping, like in the TeX-style
        \frac{3}{5}, strange things can happen if you place variables inside the curly braces.  This is because PHP, the back-end
        interpreter, uses curly braces to isolate variables from surrounding text.
    </p><p>
        For example, if you wanted to display `3x` rather than `3*x`, then you need to enter 3x rather than 3*x.  With a
        variable coefficient, writing $ax doesn't work, since the interpreter thinks that "$ax" is the variable.  Curly braces
        can avoid this, allowing you to write {$a}x to achieve the desired result.  Alternatively, writing $a x works as well.  In
        rendered math (inside backticks), extra spaces are removed.
    </p><p>
        As a side effect, writing \frac{$a}{$b} causes problems, since the interpreter essentially removes the curly braces
        during variable interpolation, leaving \frac34 (if $a=3,$b=4).  A simple way to avoid this is to add spaces:  enter
        \frac{ $a }{ $b } instead, and the interpreter will leave the curly braces alone, leaving \frac{ 3 }{ 4 }, which will
        correctly display as the desired `3/4`.
    </p>

    <h4>Dollar sign</h4>
    <p>
        Because dollar signs are used for variables, entering a dollar sign in question text requires caution.
        If $a=5, entering $$a will display correctly as $5, but entering ${$a} will not (it's something called a "variable variable" in
        PHP).  To be extra safe, entering $ $a is recommended, or \$$a (the backslash says "don't try to interpret the next symbol").
    </p>

    <h4>Array Variables</h4>
    <p>
        You can define array variables, like $a = rands(1,5,3).  $a is now an array of three numbers;
        the elements can be accessed as $a[0], $a[1], and $a[2] (note that arrays are zero-indexed).
        If you use this approach, enclose the variable reference in parenthesis in calculations, like
        $new = ($a[0])^2, and in curly brackets inside strings, like $string = "there were {$a[0]} people".
    </p>

    <h4>Variables with numbers in the name</h4>
    <p>Variables like $a1 are fine to use, but like array variables, should be enclosed in parentheses to prevent
        misinterpretation.  For example, use ($a1)^($a2) instead of $a1^$a2</p>

    <h4>Function type $variables that share letters with functions</h4>
    <p>When defining variables for Function type answer, beware that if the variable shares a letter with a function being
        used, you have to be a bit careful.  For example, if $variables="r", and you typed $answer = "rsqrt(2)", the system will
        get confused.  This can be solved by putting an explicit multiplication between the r and the square root:  $answer = "r*sqrt(2)".
        Students in their entry will also need to either put an explicit multiplication sign, or at least leave a space between the variable
        and the function name</p>

    <h4>Makepretty</h4>
    <p>
        If you define:
<pre>
$a,$b,$c = rand(-5,5,3)
$eqn = "$a x^2 + $b x + $c"
</pre>
    <p>then there is potential your $eqn would display as `4x^2+-3x+2` (that's 4x^2+-3x+2).  To clean up the
        double sign issue, use the makepretty function:</p>
<pre>
$eqn = makepretty("$a x^2 + $b x + $c")
</pre>
    </p>
    <p>Makepretty is automatically run on $answer for Function type problems</p>

    <h4>Less than and Greater than signs</h4>
    <p>Because HTML uses angle brackets to denote HTML tags, and since IMathAS allows HTML tags for formatting
        purposes, the use of &lt; and &gt; in problem text can sometimes be problematic.  The system attempts to
        differential between HTML tags and inequalities, but does not always do so successfully.</p>

    <p>Generally, same direction inequalities are handled okay, such as 3 &lt; x &lt 5.  But mixed inequalities, such
        as "x &lt; 3 and x &gt; 1" are sometimes mishandled.  To avoid this, it is recommended that you use the HTML &amp;lt; and &amp;gt;
        in place of &lt; and &gt;.  Inside backticks (rendered as math), lt and gt are sufficient to denote &lt; and &gt;.  You can also
        use le and ge or leq and geq inside backticks for `le` and `ge`.</p>
    <hr/>
</div>

<div id="asciimath-syntax">
    <p>This document is a symbol reference for the ASCIIMath language.</p>
    <p>For a more tutorial-style approach, try <a href="http://www.wjagray.co.uk/maths/ASCIIMathTutorial.html">this site</a>.</p>

    <table>
        <tr><th>Basic Operations</th><th>Relations</th><th>Calculus</th><th>Symbols</th></tr>
        <tr valign="top"><td>
                <table class=centered>
                    <tr><th>Type</th><th>See</th></tr>
                    <tr><td>+	</td><td>`+`	</td></tr>
                    <tr><td>-	</td><td>`-`	</td></tr>
                    <tr><td>+-	</td><td>`+-`	</td></tr>
                    <tr><td>*	</td><td>`*`	</td></tr>
                    <tr><td>**	</td><td>`**`	</td></tr>
                    <tr><td>-:	</td><td>`-:`	</td></tr>
                    <tr><td>1/2	</td><td>`1/2`	</td></tr>
                    <tr><td>1//2	</td><td>`1//2`	</td></tr>
                    <tr><td>xx	</td><td>`xx`	</td></tr>
                    <tr><td>3^4	</td><td>`3^4`	</td></tr>
                    <tr><td>f@g	</td><td>`f@g`	</td></tr>
                    <tr><td>sqrt(5)	</td><td>`sqrt(5)`	</td></tr>
                    <tr><td>root(3)(5)	</td><td>`root(3)(5)`	</td></tr>
                    <tr><td>sin^-1(x)	</td><td>`sin^-1(x)`	</td></tr>
                </table>
            </td>

            <td>
                <table class=centered>
                    <tr><th>Type</th><th>See</th></tr>
                    <tr><td>!=	</td><td>`!=`	</td></tr>
                    <tr><td>&lt; or lt</td><td>`lt`	</td></tr>
                    <tr><td>&gt; or gt</td><td>`gt`	</td></tr>
                    <tr><td>&lt;= or leq</td><td>`leq`	</td></tr>
                    <tr><td>&gt;= or geq</td><td>`geq`	</td></tr>
                    <tr><td>in	</td><td>`in`	</td></tr>
                    <tr><td>-=	</td><td>`-=`	</td></tr>
                    <tr><td>~=	</td><td>`~=`	</td></tr>
                    <tr><td>~~	</td><td>`~~`	</td></tr>
                    <tr><td>rarr	</td><td>`rarr`	</td></tr>
                    <tr><td>->	</td><td>`->`	</td></tr>
                    <tr><td>=>	</td><td>`=>`	</td></tr>
                    <tr><td>iff	</td><td>`iff`	</td></tr>
                </table>
            </td>

            <td>
                <table class=centered>
                    <tr><th>Type</th><th>See</th></tr>
                    <tr><td>int	</td><td>`int`	</td></tr>
                    <tr><td>int_3^5</td><td>`int_3^5`	</td></tr>
                    <tr><td>sum	</td><td>`sum`	</td></tr>
                    <tr><td>sum_(i=0)^oo	</td><td>`sum_(i=0)^oo`	</td></tr>
                    <tr><td>lim_(x->2^+)	</td><td>`lim_(x->2^+)`	</td></tr>
                    <tr><td>oint	</td><td>`oint`	</td></tr>
                    <tr><td>del	</td><td>`del`	</td></tr>
                    <tr><td>grad	</td><td>`grad`	</td></tr>
                    <tr><td>oo	</td><td>`oo`	</td></tr>
                </table>
            </td>

            <td>
                <table class=centered>
                    <tr><th>Type</th><th>See</th></tr>
                    <tr><td>4^@	</td><td>`4^@`	</td></tr>
                    <tr><td>O/	</td><td>`O/`	</td></tr>
                    <tr><td>/_	</td><td>`/_`	</td></tr>
                    <tr><td>RR	</td><td>`RR`	</td></tr>
                    <tr><td>bb A	</td><td>`bb A`	</td></tr>
                    <tr><td>bbb A	</td><td>`bbb A`	</td></tr>
                    <tr><td>hat x	</td><td>`hat x`	</td></tr>
                    <tr><td>bar x	</td><td>`bar x`	</td></tr>
                    <tr><td>vec x	</td><td>`vec x`	</td></tr>
                    <tr><td>dot x	</td><td>`dot x`	</td></tr>
                    <tr><td>(: 4,2 :)	</td><td>`(: 4,2 :)`	</td></tr>
                    <tr><td>[(1,2),(3,4)]	</td><td>`[(1,2),(3,4)]`	</td></tr>
                    <tr><td>{(x,x lt 1),<br/>(2,x ge 1):}</td><td>`{(x,x lt 1),(2,x ge 1) :}`	</td></tr>

                </table>
            </td>
        </tr>

        <tr>
            <td colspan="4">
                Greek letters:
                alpha `alpha`
                beta `beta`
                chi `chi`
                delta `delta`
                Delta `Delta`
                epsilon `epsilon`
                varepsilon `varepsilon`
                eta `eta`
                gamma `gamma`
                Gamma `Gamma`
                iota `iota`
                kappa `kappa`
                lambda `lambda`
                Lambda `Lambda`
                mu `mu`
                nu `nu`
                omega `omega`
                Omega `Omega`
                phi `phi`
                varphi `varphi`
                Phi `Phi`
                pi `pi`
                Pi `Pi`
                psi `psi`
                Psi `Psi`
                rho `rho`
                sigma `sigma`
                Sigma `Sigma`
                tau `tau`
                theta `theta`
                vartheta `vartheta`
                Theta `Theta`
                upsilon `upsilon`
                xi `xi`
                Xi `Xi`
                zeta `zeta`
            </td>
    </table>

    <p>For more examples and full syntax details, please visit <a href="http://www1.chapman.edu/~jipsen/mathml/asciimathsyntax.html">ASCIIMath's</a>
        website.  ASCIIMathML was developed by <a href="http://www1.chapman.edu/~jipsen/">Peter Jipsen</a>, Chapman University.</p>

    <hr/>
</div>

<!--Installation.-->
<div id="installation">
<p>The most recent version of the code is available on <a href="https://github.com/drlippman/imathas">GitHub</a></p>

<h3>What is IMathAS</h3>
<p>IMathAS is an Internet Mathematics Assessment System. It is primarily a
    web-based math assessment tool for delivery and automatic grading of math homework
    and tests. Questions are algorithmically generated and numerical and math expression
    answers can be computer graded. Beyond that, IMathAS includes learning management tools,
    including posting of announcements, text files, and attachments, as well as discussion
    forums and a full gradebook. In postings and assessments, IMathAS allows accurate display
    of math and graphs, with simple calculator-style math entry and point-and-click graph
    creation.   It is most similar to (and inspired by) <a href="http://webwork.math.rochester.edu/">WebWork</a>
    and <a href="http://wims.unice.fr/">WIMS</a>, and similar to commercial and publisher-produced systems
    like iLrn, MathXL, WebAssign, etc.</p>

<p>IMathAS was written by <a href="http://www.pierce.ctc.edu/dlippman">David Lippman</a> (c) 2006-2014,
    with with partial support from the
    <a href="http://www.lumenlearning.com">Lumen Learning</a>,
    <a href="http://www.sbctc.ctc.edu/College/_g-elchome.aspx">WA State E-Learning Council</a>, the
    <a href="http://www.transitionmathproject.org/">Transition Math Project</a>, and
    <a href="http://www.pierce.ctc.edu">Pierce College</a>.  It is distributed under
    the <a href="http://www.gnu.org/copyleft/gpl.html">GPL</a>.  See the <a href="<?php echo AppUtility::getHomeURL()?>docs/license.txt">license.txt</a> file distributed with IMathAS for more details
    and credits for components attributed to others.</p>

<h3>Features</h3>
<h4>IMathAS was built on three primary philosophies:</h4>
<ul>
    <li><b>Math</b>:  The system was designed for Math assessment; no effort was made for the system to be multi-purpose.</li>
    <li><b>Sharing</b>:  The system was setup to encourage sharing of questions within a system and outside.  Questions are grouped into
        question libraries, and are not tied directly to a specific assessment.  Unless marked Private, questions can be used by
        anyone on the system, or used as a template for writing new questions.  Assessments and course setups can be copied
        between users with permission.  Export and Import allows the
        sharing of question sets between systems.  Also, macro libraries allow for the expansion of IMathAS's question language.
        Users with expertise in a field can develop macro extensions, and share them with other users.</li>
    <li><b>Ease of Install</b>:  The system uses standard PHP and MySQL.  It requires no special compilation options or installation
        of external programs.</li>
</ul>

<h4>Core Features include:</h4>
<ul>
    <li>Display:</li>
    <ul>
        <li>Rich Math and Graph display, using standards-based MathML and SVG, powered
            by Peter Jipsen's <a href="http://www1.chapman.edu/~jipsen/svg/asciisvg.html">ASCIIsvg</a> and
            <a href="http://www1.chapman.edu/~jipsen/mathml/asciimath.html">ASCIIMathML</a>, or using image fallbacks</li>
        <li>Rich Text Editor with built-in Math and Graph support for text items displayed in a course and forum posts</li>
        <li>Alternate display options for screenreaders and browsers without needed support</li>
    </ul>
    <li>Assessment:</li>
    <ul>
        <li>Question types including:</li>
        <ul>
            <li>Functions, with answers like "sin(x)"</li>
            <li>Numbers, compared to a given tolerance</li>
            <li>Calculated Numbers, like 5/3 or 2^5</li>
            <li>Multiple-Choice</li>
            <li>Multiple-Answer</li>
            <li>Matching</li>
            <li>String</li>
            <li>Essay (not computer graded)</li>
            <li>Numerical Matrix</li>
            <li>Calculated Matrix</li>
            <li>Interval</li>
            <li>Calculated Interval</li>
            <li>N-tuples</li>
            <li>Drawing / Graphing</li>
            <li>File Upload</li>
            <li>Multipart</li>
        </ul>
        <li>Multiple display options and assessment settings, including an option for practice sets, and
            due date exceptions for individual students</li>
        <li>Algorithmically generated questions, with a relatively simple-to-use question language
            (loosely based on PHP) with many built-in randomizers and display macros</li>
        <li>Group assessments</li>
        <li>Expandable question language, by installing additional macros</li>
    </ul>
    <li>Other Course Management Features:</li>
    <ul>
        <li>Post text items, uploaded files, or web links to the classroom</li>
        <li>Discussion Forums, optionally gradable </li>
        <li>Full gradebook with support for online and offline grades and flexible grading schemes</li>
    </ul>
    <li>Administration:</li>
    <ul>
        <li>Flexible administration:  IMathAS can be centrally administered,
            or teachers can be given rights to create their own courses</li>
        <li>Teachers can be grouped by school or other division, with one or more group administrators</li>
        <li>Courses can have one or more teachers</li>
    </ul>
    <li>Mash-ups:</li>
    <ul>
        <li>Support for <a href="http://www.imsglobal.org/toolsinteroperability2.cfm">LTI 1.1</a>, both as a consumer and producer</li>
    </ul>
</ul>

<h4>Details</h4>
For details on how the system works, look over the <a href="<?php echo AppUtility::getHomeURL()?>docs/help.html" target="_blank">Help File</a> or view
<a href="http://www.imathas.com/support.html">training and support</a> resources.

<h3>Why a new system</h3>
<p>You may be wondering why another system, when excellent systems already exist.</p>

<p>For my purposes, WebWork and WIMS were too difficult to install (since I don't have direct
    access to my school's webserver).  I didn't want to use commercial or publisher produced systems
    because I don't want students with used books to incur additional cost.</p>


<h3>Installation</h3>
<p>
<ol>
    <li>Download IMathAS, extract it, and copy the files to your webserver.</li>
    <li>Alternatively, if you have shell access to your server, enter the directory you want IMathAS in,
        and checkout the code from SVN:  <i>svn checkout http://imathas.googlecode.com/svn/trunk/</i>.
        Using SVN greatly simplifies upgrading.</li>
    <li>If your web host requires that you create databases through the web panel, create a database
        and database user for IMathAS now.  If you have a username/password with database
        creation priviledges, you can wait and use it later in the install process.</li>
    <li>Open a browser and access install.php.  This script will write the config.php file, change directory
        permissions, copy distribution files, and set up the database.  At the end of the install you
        will be given the opportunity to install a small set of example questions.  <i>Note</i>:
        If you are running on a Windows server, you may need to set directory permissions manually.</li>
    <li>Log into IMathAS.  If you didn't change the
        initial imathas user settings when running install.php, log in as 'root' with password 'root'.
        If you did not change the inital imathas user settings, click the "Change Password"
        link now to change the password to something substantial.  Alternatively, you can
        go into the Admin page, create a new user with Admin rights, then delete the 'root'
        admin.</li>
    <li>Edit loginpage.php and infoheader.php if desired.  If you plan to use the new instructor account
        request page, edit newinstructor.php</li>
</ol>
</p>

<h3>Upgrading</h3>
To upgrade your installation:
<p>
<ol>
    <li>If you installed using SVN checkout, then run <i>svn update</i> to update the code files.</li>
    <li>If you copied files to your webserver to install, copy the updated files to your server to overwrite the old ones.  It is
        recommended that you <i>not</i> delete the original files first, or you will lose your configuration files and
        and the database upgrade record file (upgradecounter.txt).</li>
    <li><i>Note:</i> Several files (config.php, loginpage.php, newinstructor.php, and infoheader.php) were copied from corresponding
        distribution files when you first installed, and will not be overwritten when you update, whether you follow method 1 or 2.</li>
    <li>Log into your installation using an Administrator account, and access the /upgrade.php file to make any necessary
        database upgrades</li>
</ol>
</p>

<h3>Installation Issues/Troubleshooting</h3>
<p>
<ul>
    <li>If you receive an 500 access error trying to access install.php, try deleting the .htaccess file in the
        IMathAS root directory.  This file is used to give some advice to the web server about file compression
        and file types, but could potentially cause problems depending on the server configuration</li>
    <li>If you find graphics not displaying, make sure your PHP installation supports GD2.</li>
</ul>
</p>


<h3>Upgrading</h3>
<p>If you are upgrading from IMathAS 1.6 or later, access update.php to install any new database changes
    and learn about any other changes necessary.  If you are upgrading from a version earlier
    than 1.6, you'll need to manually apply changes from the upgrade.txt file.
</p>

<h3>System Requirements</h3>
<h4>Versions</h4>
<p>PHP 5 and MySQL 4+ are recommended, and required from some features.
    Most of the system will work with PHP 4.2+
    and MySQL 3.23, but future compatibility is not guaranteed.</p>
<p>PHP with GD2 and Freetype are recommended for best image-based graph support</p>
<p>IMathAS will <b>not work</b> with the suhosin extension for PHP, which disables the predictable random number generation IMathAS relies on.</p>

<h4>Server Requirements</h4>
<p>An installation serving 7000+ students
    with 300+ concurrent users has operated well on a commercial shared web server.</p>

<h4>Security</h4>
<p>IMathAS uses a standard databased-stored sessions-based system.  If a user does
    not log out, the session is cleared from database after 24 hours.</p>

<p>Questions (written by teachers) are passed through an interpreter that only allows
    authorized functions to be used.  Student answers are evaluated client-side using
    JavaScript, and are never evaled server-side.</p>

<h3>Install Notes</h3>
<p>
    The install.php script automatically handles the following install steps.  They're
    listed here in case anything goes wrong:
<ul>
    <li>Change permissions (chmod) of the following directories to allow the webserver process
        to write to the directories:
        <ul>
            <li>assessment/libs</li>
            <li>assessment/qimages</li>
            <li>admin/import</li>
            <li>course/files</li>
            <li>filter/graph/imgs</li>
            <li>filestore (if you're not using S3 for file storeage)</li>
        </ul>
        <br/>
        <i>Note</i>: If you are running on a Windows server, you may need to set directory permissions manually. <br/>
        <i>Note</i>: For security, the admin/import directory should not be web-readable.  A .htaccess file is included to
        prevent access.  If your server doesn't obey .htaccess files, you may need to do additional tweaking. </li>
    <li>Rename (in the main directory):
        <ul>
            <li>config.php.dist to config.php</li>
            <li>infoheader.php.dist to infoheader.php</li>
            <li>loginpage.php.dist to loginpage.php</li>
            <li>newinstructor.php.dist to newinstructor.php</li>
        </ul></li>
    <li>Edit config.php.  Change these options to your liking:</li>
    <ul>
        <li>$dbserver:  The address of your database server.  Probably www.yoursite.edu or localhost</li>
        <li>$dbname:  The name of the IMathAS database</li>
        <li>$dbusername:  The username of the IMathAS database user.</li>
        <li>$dbpassword:  The password for the IMathAS database user.  Choose something really complicated</li>
        <li>$installname: The name of your installation, for personalization.</li>
        <li>$longloginprompt:  How you want to prompt new students for a username</li>
        <li>$loginprompt:  How you want to prompt students for a username</li>
        <li>$loginformat:  Enforce a format requirement on the username</li>
        <li>$emailconfirmation:  If set to true, new users will have to respond to an email sent by the system
            before being able to enroll in any classes</li>
        <li>$sendfrom:  An email address to send confirmation and notification emails from.</li>
        <li>$imasroot:  The web root of the imathas install (ie, http://yoursite.edu $imasroot)</li>
        <li>$mathimgurl: An absolute path or full url to a Mimetex CGI installation, for math image fallback</li>
        <li>$colorshift:  Whether icons should change colors as due date approaches.  I thought this was cute,
            but others might find it annoying</li>
        <li>$smallheaderlogo: Text or an HTML image tag for a small (120 x 80) logo to display at the top right of
            course pages</li>
        <li>$allownongrouplibs:  Whether non-admins should be allowed to create non-group libraries.
            On a single-school install, set to true; for larger installs that plan to
            use the Groups features, set to false</li>
        <li>$allowcourseimport:  Whether anyone should be able to import/export questions and libraries from the
            course page.  Intended for easy sharing between systems, but the course page
            is cleaner if turned off.</li>
        <li>$allowmacroinstall:  Whether admins should be able to install macro files.  Macros files hold a large
            security risk, and should only be installed from trusted sources.  For a single-admin system, it is
            recommended that you leave this as false, and change it when you need to install a macro</li>
        <li>$sessionpath:  change the session file path different than the default.
            This is usually not necessary unless your site is on a server farm, or
            you're on a shared server and want more security of session data</li>
        <li>$mathchaturl:  a URL to the live chat server.  Leave unchanged to use the local install's
            server.  Comment out to disable the chat server.  You can also use a different install to
            offload chat on a different server</li>
        <li>$enablebasiclti:  Set to true to enable use of IMathAS as a BasicLTI producer.</li>
        <li>$AWSkey,$AWSsecret,$AWSbucket:  To allow students and teachers to upload files through
            the text editor, and to enable file upload questions, this specifies an Amazon S3
            key, secret, and bucket to use for file storage.  Local storage is not yet implemented.</li>
    </ul>
    <li>Run the dbsetup.php script (access http://yoursite.edu $imasroot/dbsetup.php), or send it
        to your system administrator and ask them to run it, if you don't have creation access
        to the database server.  Alternatively, have your system administrator create a database and database user,
        and use these names in the config.php file before running dbsetup.php.  This script sets up the necessary database user, database, and tables for IMathAS.</li>

</ul>
</p>
</div>
</div>