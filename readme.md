PDO Conversion progress
Total todo: 2757

Most queries have been semi-automatically converted, and should be fine.  The
original queries are still in the code, prefixed by `//DB`.

The big things to look for:
- is $DBH declared as global if the calls are inside a function?
- if the variable is a complex array reference, like $A[$B[1]], the autoconversion
  might not have worked.
- if a query is done in a loop, ideally it only will be "prepared" once
- variables should not have "addslashes" applied before insert - that's handled automatically.
- nor should stripslashes be necessary. The auto-escaping of request variables in config.php will be removed.

Here is a list of files that have been worked on.  Files prefixed with "C" have been converted.
Files prefixed with "0" did not contain any queries, and can be skipped over.

Once a file has been reviewed, please change the C to R.

````
/                   done
C actions.php       39
C bltilaunch.php    117
C calcqtimes.php    10
0 canvas.php        0
0 canvasnav.php     0
0 checkbrowser.php  0
  config.php.dist   recheck
C dbsetup.php       63   check against config and install when Done
C DEembedq.php      3
C directaccess.php  8
C embedq.php        1
C footer.php        1
C forms.php         12
C gethomemenu.php   2
C getpostlist.php   6
  getxml.php            no longer relevant- should be removed
  google-postreader.php no longer relevant- should be removed
0 header.php        0
0 help.php          0
C index.php         12
0 infoheader.php.dist
C install.php       1   check
C installexamples.php 3
0 loginpage.dist.php
C ltihome.php       30
0 ltisessionsetup.php
0 multiembedq.php
C newinstructor.php.dist  4
C OEAembedq.php     3
C showlinkedtextpublic.php  5
C upgrade.PHP       237  lots of logic to check
C validate.php      26

/assessment
 0 asidutil.php
 C catscores.php      4

 /libs
  0 all

/course

/diag                 done
  C index.PHP         18

/filter               done
 0 filter.php
 /basiclti
  C post.php          3
  0 blti_util.php
 /graph
  0 all
 /math
  0 all
 /simplelti
  0 all  

/forums

/includes
 C calendardisp.php   11
 C copyiteminc.php    63  complex logic changes
 0 DEutil.php
 0 diff.php
 C filehandler.php    4   several hand-santized weird queries
 0 htmlawed.php
 0 htmlutil.php
 0 JSON.php
 C JWT.php            1
 C ltiauthstore.php   5
 C ltioutcomes.php    5
 0 OAuth.php
 0 parsedatetime.php
 0 password.php
 0 rubric.php
 0 S3.php
 C stugroups.php      18
 0 tar.class.php  
 C unenroll.php       30  many hand-santized for simplicity
 C updateassess.php   4
 0 userpics.php   

/util

/wikis
````
