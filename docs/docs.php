<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<?php
	$dbsetup = true; //prevents connection to database
	include("../config.php");
?>
<html>
<head>
<title><?php echo $installname; ?></title>
<link rel="stylesheet" href="<?php echo $imasroot;?>/infopages.css" type="text/css">
<link rel="shortcut icon" href="/favicon.ico" >
</head>
<body>
<?php
	$pagetitle = "Documentation";
	require("../infoheader.php");
?>


<h2>Guides</h2>
<h4>Use Guides</h4>
<ul>
<li><a href="gettingstarted.html">Getting Started in IMathAS</a>.  A walkthrough of creating
   your first IMathAS course.</li>
<li><a href="commontasks.html">Common Tasks in IMathAS</a>.  A walkthrough of common instructor activities.</li>
<li><a href="managing.html">Managing Libraries and Questions in IMathAS</a>.  A guide to the library and question 
   set management tools</li>
</ul>

<h4>Video Guides</h4>
<ul>
<li><a href="http://www.imathas.com/imathas/docs/gettingstarted/gettingstarted.html">Getting Started in IMathAS</a> video guide.</li>
<li><a href="http://www.imathas.com/imathas/docs/wamaplayouts.html">Examples of Course Layouts</a>.  Some ideas for how to lay out a course page</li>
<li><a href="http://www.imathas.com/imathas/docs/wamaptemplate2.html">Using Course Templates</a>.  How to copy pre-created course templates to use
  course assignments created by your colleagues.</li>
<li><a href="http://www.imathas.com/imathas/docs/docs.html">Video versions of several other guides</a> are also available.</p>
</ul>
<h4>Question Writing Guides</h4>
<ul>
<li><a href="introquestionwriting.html">Intro to Question Writing in IMathAS</a>.  A step-by-step guide for writing
   your first IMathAS question.</li>
<li><a href="morequestions.html">More Question Examples</a>.  Examples of several questions, with explanation.</li>
<li><a href="questionoddities.html">Question Oddities</a>.  Common pitfalls and oddities in the IMathAS question language.</li>
<li><a href="langquickref.doc">Language Quick Reference</a>.  Short document with quick function reference.</li>
</ul>

<h2>Documentation</h2>
<ul>
<li><a href="../help.html">Help file</a>.  Detailed documentation of all of IMathAS's features and
   question language</li>
<li><a href="asciimathref.html">ASCIIMath syntax reference</a>.  A reference sheet for ASCIIMath symbol entry.</li>
<li><a href="../readme.html">Installation</a>.  Step-by-step installation instructions, part of the system's
   readme file.  This is only needed for server installation - teachers should not need this</li>
</ul>

<p>Many of these guides were written with development grant support from the WA State Distance Learning Council</p>
</body>
</html>
