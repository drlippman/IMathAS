<?php
$nologo = true;
	$dbsetup = true; //prevents connection to database
	include("../init_without_validate.php");
	$placeinhead = "<link rel=\"stylesheet\" href=\"$imasroot/infopages.css\" type=\"text/css\">\n";
	require("../header.php");
	$pagetitle = "Documentation";
	require("../infoheader.php");
	
	
?>

<h1>Guides</h1>
<h3>Use Guides</h3>
<ul>
<li><a href="GettingStarted.pdf">Getting Started in WAMAP</a> (PDF file).  A walkthrough of creating a course in WAMAP, with screenshots.  This
   version is specific to the WAMAP installation of IMathAS, but applies to any installation.</li>
<li><a href="gettingstarted.html">Getting Started in IMathAS</a>.  A walkthrough of creating
   your first IMathAS course.</li>
<li><a href="commontasks.html">Common Tasks in IMathAS</a>.  A walkthrough of common instructor activities.</li>
<li><a href="managing.html">Managing Libraries and Questions in IMathAS</a>.  A guide to the library and question 
   set management tools</li>
<li><a href="administration.html">Administration</a>.  A guide to administering IMathAS for Full and Group Admins.</li>
<li><a href="diagnostics.html">Diagnostics</a>.  A guide to setting up diagnostic assessment login pages.</li>

</ul>

<h3>Video Guides</h3>
<ul>
<li><a href="http://www.imathas.com/imathas/docs/gettingstarted/gettingstarted.html">Getting Started in IMathAS</a> video guide.</li>
<li><a href="http://www.imathas.com/training.html">Training Videos</a>.  A complete training course set of video guides.</li>
<li><a href="http://www.imathas.com/imathas/docs/wamaplayouts.html">Examples of Course Layouts</a>.  Some ideas for how to lay out a course page</li>
<li><a href="http://www.imathas.com/imathas/docs/wamaptemplate2.html">Using Course Templates</a>.  How to copy pre-created course templates to use
  course assignments created by your colleagues.</li>
<li><a href="http://www.imathas.com/imathas/docs/docs.html">Video versions of several other guides</a> are also available.</p>
</ul>
<h3>Question Writing Guides</h3>
<ul>
<li><a href="introquestionwriting.html">Intro to Question Writing in IMathAS</a>.  A step-by-step guide for writing
   your first IMathAS question.</li>
<li><a href="morequestions.html">More Question Examples</a>.  Examples of several questions, with explanation.</li>
<li><a href="questionoddities.html">Question Oddities</a>.  Common pitfalls and oddities in the IMathAS question language.</li>
<li><a href="langquickref.doc">Language Quick Reference</a>.  Short document with quick function reference.</li>
</ul>

<h1>Documentation</h1>
<ul>
<li><a href="../help.html">Help file</a>.  Detailed documentation of all of IMathAS's features and
   question language</li>
<li><a href="AccessingOnlineHomeworkinWAMAP.doc">Getting enrolled for students</a>.  Not so much documentation, but
   a word document containing instructions for students to sign up and enroll in your course.  Suitable for including
   in your syllabus or handing out to students</li>
<li><a href="asciimathref.html">ASCIIMath syntax reference</a>.  A reference sheet for ASCIIMath symbol entry.</li>
<li><a href="../readme.html">Installation</a>.  Step-by-step installation instructions, part of the system's
   readme file.  This is only needed for server installation - teachers should not need this</li>
</ul>

<p>Many of these guides were written with development grant support from the WA State Distance Learning Council</p>
</div>
</body>
</html>
