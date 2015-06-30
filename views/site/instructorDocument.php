<?php
use yii\helpers\Html;
use app\components\AppUtility;

$this->title = Yii::t('yii', 'Instructor documents');
$this->params['breadcrumbs'][] = ['label' => 'About Us', 'url' => ['/site/about']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="site-about">
    <h1><?= Html::encode($this->title) ?></h1>
</div>

<h2>Guides</h2>
<h4>Use Guides</h4>
<ul>
    <li><a href="http://localhost/openmath/web/GettingStarted.pdf" target="_blank">Getting Started in WAMAP</a> (PDF file).  A walkthrough of creating a course in WAMAP, with screenshots.  This
        version is specific to the WAMAP installation of IMathAS, but applies to any installation.</li>
    <li><a href="#getting-started">Getting Started in IMathAS</a>.  A walkthrough of creating
        your first IMathAS course.</li>
    <li><a href="#common-task">Common Tasks in IMathAS</a>.  A walkthrough of common instructor activities.</li>
    <li><a href="#manage-question-library">Managing Libraries and Questions in IMathAS</a>.  A guide to the library and question
        set management tools</li>
    <li><a href="#administrator">Administration</a>.  A guide to administering IMathAS for Full and Group Admins.</li>
    <li><a href="#diagnostics">Diagnostics</a>.  A guide to setting up diagnostic assessment login pages.</li>

</ul>

<h4>Video Guides</h4>
<ul>
    <li><a href="#getting-started">Getting Started in IMathAS</a> video guide.</li>
    <li><a href="#">Training Videos</a>.  A complete training course set of video guides.</li>
    <li><a href="#">Examples of Course Layouts</a>.  Some ideas for how to lay out a course page</li>
    <li><a href="#">Using Course Templates</a>.  How to copy pre-created course templates to use
        course assignments created by your colleagues.</li>
    <li><a href="#">Video versions of several other guides</a> are also available.</p></li>
</ul>
<h4>Question Writing Guides</h4>
<ul>
    <li><a href="#">Intro to Question Writing in IMathAS</a>.  A step-by-step guide for writing
        your first IMathAS question.</li>
    <li><a href="#">More Question Examples</a>.  Examples of several questions, with explanation.</li>
    <li><a href="#">Question Oddities</a>.  Common pitfalls and oddities in the IMathAS question language.</li>
    <li><a href="#">Language Quick Reference</a>.  Short document with quick function reference.</li>
</ul>

<h2>Documentation</h2>
<ul>
    <li><a href="#">Help file</a>.  Detailed documentation of all of IMathAS's features and
        question language</li>
    <li><a href="#">Getting enrolled for students</a>.  Not so much documentation, but
        a word document containing instructions for students to sign up and enroll in your course.  Suitable for including
        in your syllabus or handing out to students</li>
    <li><a href="#">ASCIIMath syntax reference</a>.  A reference sheet for ASCIIMath symbol entry.</li>
    <li><a href="#">Installation</a>.  Step-by-step installation instructions, part of the system's
        readme file.  This is only needed for server installation - teachers should not need this</li>
</ul>

<p>Many of these guides were written with development grant support from the WA State Distance Learning Council</p>

<!--Getting started with IMathAS-->
<div id="getting-started">

    <div class=title>
        <h1>Getting Started with IMathAS</h1>
    </div>
    <h2>Purpose of this document</h2>
    <p>This document will guide you through the process of creating your first IMathAS
        course and setting up a few items and assessments.  This is not a comprehensive
        guide; please refer to the <a href="http://localhost/openmath/help.html" target="_blank">help file</a> for more
        detailed information on any process.</p>

    <p>This document presumes that you have Limited Course Creator rights.  If you have
        Teacher rights, you will not be able to add a new course to the system yourself;  contact
        your IMathAS admin to setup the course, then start this document at step 2. </p>

    <h2>1. Adding a New Course</h2>
    <ol>
        <li>After logging in to IMathAS, click on "Go to Admin Page" in the grey control panel box.</li>
        <li>Click the "Add New Course" button.</li>
        <li>Enter your course name and an enrollment key.  The course name is the name that will show
            up in your "Classes You're Teaching" list and in the student's "Classes You're Taking" list.  The
            enrollment key is the password that students must enter to enroll themselves in your course.</li>
        <li>Click "Submit".</li>
        <li>Back at the Admin page, note the Course ID of the course you just created.  Your students
            will need both your Course ID and enrollment key to sign up for your course.</li>
        <li>Click on the Course Name to enter your course.  In the future, the course will show up on your
            Home Page under "Classes You're Teaching".
    </ol>

    <p><em>Note</em>: If you have Admin or Course Creator rights, you will not automatically be
        registered as instructor for the course.  To do so, click the "Add/Remove Teachers" link, then
        click the "Add as Teacher" link next to your name.</p>

    <h2>2.  The Course Page</h2>
    <p>When you first access your course, the only thing you will see is two grey control panel
    boxes.  To familiarize yourself with the control options, here are some brief descriptions:</p>

    <ul>
        <li><strong>Add an item</strong>:  Used to add items to your course</li>
        <li><strong>List Students</strong>:  List the students in your course.  This page also provides options for
        importing students into your course and for setting testing due date exceptions.</li>
        <li><strong>Show Gradebook</strong>:  Your course gradebook.  From here you can review individual students'
        tests, adjust grades, accept overtime tests, and export the gradebook to a file.</li>
        <li><strong>Manage Question Set</strong>:  Search, modify, and add questions to the system question database.</li>
        <li><strong>Manage Libraries</strong>:  Add to or modify the question library structure.</li>
        <li><strong>Copy Course Items</strong>:  Copy items in your course, or copy items from other instructors' courses</li>
        <li><strong>Export/Import Course Items</strong>:  For sharing course items with other IMathAS installations.</li>
        <li><strong>Shift all Course Dates</strong>:  Change all course item dates at one time.</li>
        <li><strong>Mass Change Assessments</strong>:  Change several assessments' settings at once.</li>

    </ul>
    <p>Once you add items to the course page, each item will have an icon attached to it.  The icons change
    color based on the due date;  grey when unavailable, and green for 2 weeks or more until due date, changing to yellow
    then red as the due date approaches</p>

    <h2>3. Add an Inline Text Item</h2>
    <p>Inline Text items display announcements or other text on the course page.</p>
    <ol>
        <li>Select "Inline Text" from the "Add An Item" pulldown</li>
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
    guide; please refer to the <a href="http://localhost/openmath/help.html" target="_blank">help file</a> for more
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
<p>The <a href="gettingstarted.html">Getting Started</a> guide explained the assessment options.  Here are some other
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
        guide; please refer to the <a href="http://localhost/openmath/help.html" target="_blank">help file</a> for more
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
        information on specific features, you might find more info in the <a href="http://localhost/openmath/help.html" target="_blank">help file</a>.</p>

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