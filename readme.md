PDO Conversion progress
Total todo: ~2700.  Done: ~1825

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

TODO: Look for branched query declaration, like
````
$query = "SELECT id FROM table WHERE A=:A"
if ($cond==true) {
  $query .= " AND B=:B"
}
$stm = $DBH->prepare($query);
$stm->execute(array(":A"=>$A, ":B"=>$B));
````
Apparently including more variables in the execute than are used in the query will
cause an error.  I'm pretty sure I did that a few times, and those will need to
be fixed.

TODO: Error handling / exception catching

Progress
````
/                   done
C actions         39
C bltilaunch      117
C calcqtimes      10
0 canvas          0
0 canvasnav       0
0 checkbrowser    0
  config.dist     recheck
C dbsetup         63   check against config and install when Done
C DEembedq        3
C directaccess    8
C embedq          1
C footer          1
C forms           12
C gethomemenu     2
C getpostlist     6
  getxml            no longer relevant- should be removed
  google-postreader no longer relevant- should be removed
0 header          0
0 help            0
C index           12
0 infoheader.dist
C install         1   check - need to remove addslashes after done
C installexamples 3
0 loginpage.dist
C ltihome         30
0 ltisessionsetup
0 multiembedq
C newinstructor.dist  4
C OEAembedq       3
C showlinkedtextpublic  5
C upgrade         237  lots of logic to check
C validate        26

/admin

/assessment
 0 asidutil
 C catscores      4

 /libs
  0 all

/course
 C addassessment    24
 C addblock         5
 C addcalendar      6
 C adddrillassess   14 messy logic on search
 * addforum            come back to this after merging forum exception branched
 C addgrades        26
 C addinlinetext    17
 C addlinkedtext    13
 C addoutcomes      12
 C addquestions     29 messy logic on search like adddrill
 C addquestionssave 3
 C addrubric        4
 C addvideotimes    3
 C addwiki          9
 C assessendmsg     2
 C categorize       8
 C chgassessments   11
 C chgblocks        3
 * chkforums          come back to this after merging forum exception branched
 C chgoffline       6
 C claimbadge       3
 C contentstats     3
 C copyitems        41
 C copyoneitem      6
 C course           12
 0 coursereports
 C courseshowitems  27
 C coursetolibrary  2
 C definebadges     9
 C deleteassessment 9
 C deleteblock      5
 C deletedrillassess  7
 C deleteforum      11
 C deleteinlinetext 9
 C deletelinkedtext 9
 C deletewiki       8
 C delitembyid      24  
 C drillassess      8
 C edittoolscores   11
 0 embedhelper      
 C enrollfromothercourse  4
 C exception        13
 C gb-aidexport     5
 C gb-aidexport     4
 C gb-itemanalysis  7
 C gb-itemanalysisdetail  4
 C gb-itemresults   4
 C gb-testing       1
 C gb-viewasid      38
 C gb-viewdrill     2
 C gbcomments       5
 C gbsettings       13
   gbtable.php          old and not used - remove
 C gbtable2.php     17
 C getblockitems    6
 C getblockitemspublic  1
 C gradeallq        9
 C gradebook        17
 C improveoerassess 3
 C isolateassessbygroup 6
 C isolateassessgrade   7
 C latepasses       6
 C libtree          1
 C libtree2         1
 C listusers        27
 C lockstu          5
 C logingrid        2
 C managecalitems   4
 C managelibs       35
 C manageqset       60
 C managestugrps    44
 C managetutors     9
 C masschgdates     15
 C massexeption     16
 C masssend         11
 C mergeassess      16
 C moddataset       36
 C modquestion      8
 C modquestiongrid  11
 C modtutorialq     24
 C outcomemap       8
 C outcomereport    3
 C outcometable     11
 C printlayout      6
 C printlayoutbare  4
 C printlayoutword  4
 0 printtest
 C public           1
 C quickdrill       2
 C rectrack         3
 C redeemlatepass   16
 C redeemlatepassforum  13
 C report-weeklylab 4
 C reviewlibrary    22
 C savebrokenqflag  4
 C savelibassignflag  1
 C savequickreorder 4
 C savemsgmodal     5
 C showcalendar     1
 C showlicense      1
 C showlinkedtext   3
 C showlinkedtextpublic 3
 C showstugroup     1
 C testquestion     2
 C timeshift        11
 C treereader       11
 C unenroll         9
 C uploadgrades     3
 C uploadmultgrades 8
 C verifybadge      9
 C viewactionlog    9
 C viewemails       1
 C viewforumgraph   8
 C viewgrade        1
 C viewloginlog     2
 C viewsource       1


/diag              done
  C index          18

/filter           done
 0 filter
 /basiclti
  C post          3
  0 blti_util
 /graph
  0 all
 /math
  0 all
 /simplelti
  0 all  

/forums

/includes             done
 C calendardisp     11
 C copyiteminc      63  complex logic changes
 0 DEutil
 0 diff
 C filehandler      4   several hand-santized weird queries
 0 htmlawed
 0 htmlutil
 0 JSON
 C JWT              1
 C ltiauthstore     5
 C ltioutcomes      5
 0 OAuth
 0 parsedatetime
 0 password
 0 rubric
 0 S3
 C stugroups        18
 0 tar.class  
 C unenroll         30  many hand-santized for simplicity
 C updateassess     4
 0 userpics   

/mathchat             should probably remove - obsolete

/msgs                 done   need to check userid on a lot of mark unread/read/etc actions
 C allstumsgslist     6
 C msghistory         5
 C msglist            32  lots of redundant code in here
 C newmsglist         6
 C savetagged         1
 C sentlist           10
 C viewmsg            10

/util                 done
 C blocksearch        1
 C getqcnt            7
 C getstucnt          8   all using safe values
 C getstucntdet       2
 C itemsearch         1
 C listdeprecated     1
 C listextref         1
 C listwronglibs      1
 C makeconditional    4
 C mergescores        6
 C mergestus          4
 C mergeteachers      18
 C replacevids        6
 C rescoreassess      4
 C updatedeprecated   3
 C updateextref       3
 C updatewronglibs    1
 C utils              9

/wikis                done
 C editwiki           8
 C viewwiki           16
 C viewwikipublic     4
 C wikirev            2
````
