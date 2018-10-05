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
R actions         39
R bltilaunch      117
R calcqtimes      10
0 canvas          0
0 canvasnav       0
0 checkbrowser    0
  config.dist     recheck
R dbsetup         63 check against config and install when Done
R DEembedq        3
R directaccess    8
R embedq          1
R footer          1
R forms           12
R gethomemenu     2
R getpostlist     6
  getxml            no longer relevant- should be removed
  google-postreader no longer relevant- should be removed
0 header          0
0 help            0
R index           12
0 infoheader.dist
R install         1   
R installexamples 3
0 loginpage.dist
R ltihome         30
0 ltisessionsetup
0 multiembedq
R newinstructor.dist  4
R OEAembedq       3
C showlinkedtextpublic  5
C upgrade         237  
R validate        26

/admin            done
 C actions        120
 C admin          5
 C approvepending 7
 C ccexport       13
 C diagonetime    6
 C diagsetup      6
 C export         7
 C exportitems    17
 C exportlib      7
 C externaltools  5
 C forms          19
 C hidefromcourselist 1
 C import         16
 C importitems    24
 C importlib      18
 C importstu      4
 C jsonexport     3
 C ltioutcomeservice  6
 C pushoutchg     7
 C unhidefromcourselist 2



/assessment       done
 0 asidutil
 C catscores      4
 0 checkint  
 C displayq2      5
 0 header
 C interpret5     2
 0 macros
 0 mathphp2
 C printtest      5
 0 showsoln
 C showtest       60
 C testutil       4
 0 watchvid
 R catscores      4

 /libs          done
  0 all

/course
 R addassessment    24
 R addblock         5
 R addcalendar      6
 R adddrillassess   14
 C addforum         19   
 R addgrades        26
 R addinlinetext    17
 R addlinkedtext    13
 R addoutcomes      12
 R addquestions     29
 R addquestionssave 3
 R addrubric        4
 R addvideotimes    3
 R addwiki          9
 R assessendmsg     2
 R categorize       8
 R chgassessments   11
 R chgblocks        3
 C chgforums        7
 R chgoffline       6
 R claimbadge       3
 R contentstats     3
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
 R gb-testing       1
 C gb-viewasid      38
 R gb-viewdrill     2
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

/forums							done
R forums             7  
R listlikes          2
R listviews          2
R newthreads         6
R posthandler        53
R posts              22
R postsbyname        11
R recordlikes        5
R savetagged         2
R thread             25

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
 C rescuecourse       4
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

testing
main page.
	chg userinfo
admin listing.  show group. show instructor's courses.
	add course (no copy).  
	add course (template: gbcats and outcomes come through)
	add/remove teacher
	edit groups
	LTI provider creds
	edit diagnostic
		generate one-time passwords
	Add new admin
	delete course:  safe and true.
add assessment.  
  Add questions: select from libraries, other assessments.
  remove question.
  rearrange question.
  group/ungroup.
  change question settings
  add set
	clear assess atempts
	categorize q's
	end msg
	video cues
	print
		oriignal printtest
		cut-and-paste
		word
add inlinetext
add linkedtext (file, page of text)
  view linkedtext item
	delete linked text with file
add wiki
  view.  edit.  edit again. view revision history. clear history. current version snapshot
add drill.  
  Add question to drill
  Open drill
add forum.  modify
add a block
show block content: expand, folder, treereader
delete block
single item copy (assessment)

course page pulldown reorder
quick view, rearrange, edit, save
show calendar
  manage events:add, delete
show Roster
  login log
  activity log
  login grid
  assign section/code
  manage tutors
  enroll from another course
	enroll known.  create new stu.
	import stu from file
  unenroll
  lock (link and button)
  copy emails
	manage latepasses - assign latepasses
show Gradebook
  single stu gradebook
    view assessment, change score.  clearq. clear scores. clear attempt.  Change score for group.
    individual assess exception
    Edit offline in place
    edit one offline grade
		send message (modal)
  add offline grade
    edit grade one stu
  manage offline: change and delete
  gb settings. view and categories
  mass exception:  
		set, clear.  require redo q
		set, clear forum exception
  Lock
  Unenroll all
  set GB comment
  print report
	isolate assess. isolate by group

item analysis page
  item breakdown
  grade one q for all: view, score.  One-at-a-time.
  summary of assessment results
  assessment results export
content stats page
weeklylab report
Groups:
  create groupset. rename. copy. delete.
  Add stu to group. remove from group
  Add stu to group: assessment copies
	pre-create groups. Student starts, copies made for all members.
		change grade for all.  For one.
		remove one stu from group - future actions unlinked

Outcomes:
  create outcomes
	outcome map
  view outcome report.  Export to CSV
Manage Questions
  search by term all libs
  search id=
  search mine only
	mark wrong libs. mark broken
	transfer one, group
	Library Assignment
	Change rights
	delete one, group
	change license
	list library
Manage Libraries
	Add library. modify.
	change parent
	change rights
	delete ** test question handling on delete
Export
	CC export
	imathas export
	export OEA JSON
Mass chg assessment
	basic change with a couple items
Mass chg forums
Mass change Blocks
Mass Chg dates
time shift
Main message inbox
	Add new message
	View message
	View conversation
	Reply
	Flag. Limit to flagged.
	mark unread.
	mark read.
	filter by course. by recipient
	Use question in message
	Delete
Sent messages.  filter by course. by recipient
New messages
Display assessment
	Print Version
Edit question. Add new question
	test question
	Add image, template question with image. delete question w image
Tutorial style editor, Add and edit
copy course items
	copy block of items (all types)
	copy whole course
	copy offline. copy outcomes
	copy calendar items
import items (basic)
Take assessment as student (no group)
	view category summary at end
	embedded display
	livepoll
	message instructor about question
	post question to forum
Take assessment as student (group)
	Add self to group
	Add another stu to group (copies assessment, inc if already has attempt (ins and upd actions))
Manage groups:
	Add student to existing group, copies assessment
Take drill as student
	view drill scores
Hide/unhide course from course list
Review library
Tree reader
Utils:
	lookup. approvepending. stucnt, detstucnt. replace youtube vids.
	emulate user. list/update extref. list wronglibs
Edit rubrics
	use rubric
External tools:
	Add, edit, delete.
	Launch external tool
Export libraries
Import libraries
Diagnostics
	Listing page
	enter diag, take diag, view score
Redeem latepass.  Un-use latepass
embedq, OEAembedq
Quickdrill generator. Run quickdrill
Direct access:
	existing student
	existing account
	create new account and enroll
LTI:
	Launch (ltiorgname didn't display - possible $SESSION issue? But OK in DB)
		Instructor: require login
		Student:  create account
		New instructor, trigger course copy
	LTI home:
		No place_aid launch: Make placement, change placement
	place_aid launch to assessment
	grade returns
	global key launch
Remove mathchat and all references
Fresh install, dbsetup.  
Upgrade
	Don't throw exception on redo query
	Actual upgrade path rewrites untested
main forum list view
	search by thread, by post
new threads list
	mark all read
forum thread list
	search, search all forums
	flag post
	limit to flagged
	move to different forum
	move to be a reply
	remove thread
	mark all read
	list thread views
	filter by group
	filter by category
View post
	next/prev
	mark unread
	assign score, change score
	Mark unread
	like post
	view likes
List posts by name
	change scores
	mark all read
Add thread
	modify post
	post reply
	group thread
	thread with file, delete
	post with category
guest login
	autoenroll in guest access courses
enroll in self-study course

TO TEST:
external tool consumer outcome service (need live)

convert specialty MOM/WAMAP pages, like flashcards
