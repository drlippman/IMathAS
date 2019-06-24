<?php

$questionSet = [
  0=>[
    'uniqueid' => 1552587894535713,
    'description' => 'Simple single-part number question w video',
    'qtype' => 'number',
    'control' => '$a = rand(1,100)'."\n".'$answer = $a',
    'qtext' => 'Math test: `2/3`.  Type $a',
    'extref' => 'video!!https://www.youtube.com/watch?v=zc2CpyRtjvY!!1'
  ],
  1=> [
    'uniqueid' => 1552587894535714,
    'description' => 'Simple multi-part number question',
    'qtype' => 'multipart',
    'control' => '$anstypes="number,number,number"'."\n".'$a = rands(1,100,3)'."\n".'$answer = $a',
    'qtext' => 'Math test: `3/4`. Type<br/>$a[0]: $answerbox[0]<br/>$a[1]: $answerbox[1]<br/>$a[2]: $answerbox[2]'
  ],
  2=> [
    'uniqueid' => 1552587894535715,
    'description' => 'Simple multi-part number question, singlescore',
    'qtype' => 'multipart',
    'control' => '$anstypes="number,number,number"'."\n".'$scoremethod = "singlescore"'."\n".'$a = rands(1,100,3)'."\n".'$answer = $a',
    'qtext' => '(singlescore). Math test: `4/6`. Type<br/>$a[0]: $answerbox[0]<br/>$a[1]: $answerbox[1]<br/>$a[2]: $answerbox[2]'
  ],
  3=>[
    'uniqueid' => 1552587894535716,
    'description' => 'Solve x-a=b',
    'qtype' => 'number',
    'control' => '$a,$b = diffrands(1,7,2)'."\n".'$answer = $a+$b',
    'qtext' => '<p>Solve: `x - $a = $b`</p><p>`x` = $answerbox</p>',
    'extref' => 'video!!https://www.youtube.com/watch?v=yqdlj0lv7Cc!!1'
  ],
  4=>[
    'uniqueid' => 1552587894535717,
    'description' => 'Solve x/a=b',
    'qtype' => 'number',
    'control' => '$a,$b = diffrands(2,7,2)'."\n".'$answer = $a*$b',
    'qtext' => '<p>Solve: `x/$a = $b`</p><p>`x` = $answerbox</p>',
    'extref' => 'video!!https://www.youtube.com/watch?v=zBqIH-E3ero!!1'
  ],
  5=>[
    'uniqueid' => 1552587894535718,
    'description' => 'Simple choices',
    'qtype' => 'choices',
    'control' => '$a,$b = diffrands(2,7,2)'."\n".'$questions = array($a+$b, $a-$b, $a*$b)'."\n".'$answer = 0',
    'qtext' => '<p>Solve: `$a + $b =`</p>'
  ],
  6=>[
    'uniqueid' => 1552587894535719,
    'description' => 'Simple multians',
    'qtype' => 'multans',
    'control' => '$questions = array("sin", "cos", "tan")'."\n".'$answers = "0,1"',
    'qtext' => '<p>Which of these have a period of `2pi`?</p>'
  ],
  7=>[
    'uniqueid' => 1552587894535720,
    'description' => 'Simple file',
    'qtype' => 'file',
    'control' => '',
    'qtext' => '<p>Upload your work here</p>'
  ],
  8=>[
    'uniqueid' => 1552587894535721,
    'description' => 'Really long question',
    'qtype' => 'number',
    'control' => '$answer = rand(1,100)',
    'qtext' => '<p>This is some text</p><p>And some more</p><p>And some more</p><p>And some more</p><p>And some more</p><p>And some more</p><p>And some more</p><p>And some more</p><p>And some more</p><p>And some more</p><p>And some more</p><p>And some more</p><p>And some more</p><p>And some more</p><p>And some more</p><p>And some more</p><p>And some more</p><p>And some more</p><p>And some more</p><p>Type $answer</p>'
  ],
  9=>[
    'uniqueid' => 1552587894535722,
    'description' => 'Simple calculated',
    'qtype' => 'calculated',
    'control' => '$a = rand(2,9)'."\n".'$answerformat="fraction"'."\n".'$answer="1/$a"',
    'qtext' => '<p>Enter `1/$a`</p>'
  ],
  10=>[
    'uniqueid' => 1552587894535723,
    'description' => 'Simple numfunc',
    'qtype' => 'numfunc',
    'control' => '$a = rand(2,9)'."\n".'$variables="x,y"'."\n".'$answer="$a(x+y)"',
    'qtext' => '<p>Factor `$a x + $a y`</p>'
  ],
  11=>[
    'uniqueid' => 1552587894535724,
    'description' => 'calc ntuple',
    'qtype' => 'calcntuple',
    'control' => '$displayformat="pointlist"'."\n".'$answer="(1/2,3),(4,9)"',
    'qtext' => '<p>Enter (1/2,3),(4,9)</p>'
  ],
  12=>[
    'uniqueid' => 1552587894535725,
    'description' => 'calc complex',
    'qtype' => 'calccomplex',
    'control' => '$answerformat="list,fraction"'."\n".'$answer="1/2+3i,4-1/4i"',
    'qtext' => '<p>Enter 1/2+3i,4-1/4i</p>'
  ],
  13=>[
    'uniqueid' => 1552587894535726,
    'description' => 'calc interval',
    'qtype' => 'calcinterval',
    'control' => '$answerformat="fraction"'."\n".'$answer="[1/2,oo)"',
    'qtext' => '<p>Enter [1/2,oo)</p>'
  ],
  14=>[
    'uniqueid' => 1552587894535727,
    'description' => 'calc interval ineq',
    'qtype' => 'calcinterval',
    'control' => '$answerformat="inequality,fraction"'."\n".'$answer="[1/2,oo)"',
    'qtext' => '<p>Enter `x>=1/2`</p>'
  ],
  15=>[
    'uniqueid' => 1552587894535728,
    'description' => 'matrix plain',
    'qtype' => 'matrix',
    'control' => '$answer="[(1,2),(3,4)]"'."\n".'$stua=$stuanswers[$thisq]'."\n".'$stuav=$stuanswersval[$thisq]',
    'qtext' => '<p>Enter [(1,2),(3,4)]. Stua: $stua. Stuaval: $stuav</p>'
  ],
  16=>[
    'uniqueid' => 1552587894535729,
    'description' => 'calcmatrix plain',
    'qtype' => 'calcmatrix',
    'control' => '$answerformat="fraction"'."\n".'$answer="[(1/2,2),(3,4)]"'."\n".'$stua=$stuanswers[$thisq]'."\n".'$stuav=$stuanswersval[$thisq]',
    'qtext' => '<p>Enter [(1/2,2),(3,4)]. Stua: $stua. Stuaval: $stuav</p>'
  ],
  17=>[
    'uniqueid' => 1552587894535730,
    'description' => 'matrix sized',
    'qtype' => 'matrix',
    'control' => '$answersize="2,3"'."\n".'$answer="[(1,2,3),(4,5,6)]"'."\n".'$stua=$stuanswers[$thisq]'."\n".'$stuav=$stuanswersval[$thisq]',
    'qtext' => '<p>Enter `[(1,2,3),(4,5,6)]`. Stua: $stua. Stuaval: $stuav</p>'
  ],
  18=>[
    'uniqueid' => 1552587894535731,
    'description' => 'calcmatrix sized',
    'qtype' => 'calcmatrix',
    'control' => '$answerformat="fraction"'."\n".'$answersize="2,3"'."\n".'$answer="[(1/2,2,3),(4,5,6)]"'."\n".'$stua=$stuanswers[$thisq]'."\n".'$stuav=$stuanswersval[$thisq]',
    'qtext' => '<p>Enter `[(1/2,2,3),(4,5,6)]`. Stua: $stua. Stuaval: $stuav</p>'
  ],
  19=>[
    'uniqueid' => 1552587894535732,
    'description' => 'twopoint draw',
    'qtype' => 'draw',
    'control' => '$answerformat="twopoint"'."\n".'$snaptogrid=1'."\n".'$answers="x-1"',
    'qtext' => '<p>Graph `y=x-1`</p>'
  ],
  20=>[
    'uniqueid' => 1552587894535733,
    'description' => 'string w preview',
    'qtype' => 'string',
    'control' => '$displayformat="usepreview"'."\n".'$answer="x^2"',
    'qtext' => '<p>Enter `x^2`</p>'
  ],
  21=>[
    'uniqueid' => 1552587894535734,
    'description' => 'Simple numfunc equation',
    'qtype' => 'numfunc',
    'control' => '$answerformat = "equation"'."\n".'$a=rand(1,20)'."\n".'$variables="x,y"'."\n".'$answer="y=x-$a"',
    'qtext' => '<p>Enter `y=x-$a`</p>'
  ],
  22=>[
    'uniqueid' => 1552587894535735,
    'description' => 'Simple matching',
    'qtype' => 'matching',
    'control' => '$questions = array("Apple","Banana","Cucumber")'."\n".'$answers = array("A","B","C")'."\n".'$displayformat="select"',
    'qtext' => '<p>Match each word with its first letter</p>'
  ],
  23=>[
    'uniqueid' => 1552587894535736,
    'description' => 'basic complex',
    'qtype' => 'complex',
    'control' => '$a,$b=diffrands(1,50,2)'."\n".'$answer="$a+$b i"',
    'qtext' => '<p>Enter `$a + $b i`</p>'
  ],
  24=>[
    'uniqueid' => 1552587894535737,
    'description' => 'interval',
    'qtype' => 'interval',
    'control' => '$a=rand(-10,10)'."\n".'$answer="[$a,oo)"',
    'qtext' => '<p>Enter [$a,oo)</p>'
  ],
  25=>[
    'uniqueid' => 1552587894535738,
    'description' => 'interval - normal curve',
    'qtype' => 'interval',
    'control' => '$answerformat="normalcurve"'."\n".'$answer="(-2,1)"',
    'qtext' => '<p>Sketch the region corresponding to `P(-2 lt z lt 1)`</p>'
  ],
  26=>[
    'uniqueid' => 1552587894535739,
    'description' => 'number w nosolninf',
    'qtype' => 'number',
    'control' => '$answerformat="nosolninf"'."\n".'$answer=randfrom(array("DNE",5))',
    'qtext' => '<p>Enter: $answer</p>'
  ],
  27=>[
    'uniqueid' => 1552587894535740,
    'description' => 'ntuple',
    'qtype' => 'ntuple',
    'control' => '$displayformat="point"'."\n".'$a,$b=diffrands(-10,10,2)'."\n".'$answer="($a,$b)"',
    'qtext' => '<p>Enter the point ($a,$b)</p>'
  ],
  28=>[
    'uniqueid' => 1552587894535741,
    'description' => 'calc ntuple vector',
    'qtype' => 'calcntuple',
    'control' => '$displayformat="vector"'."\n".'$a,$b=diffrands(-10,10,2)'."\n".'$answer="<$a,$b>"',
    'qtext' => '<p>Enter the vector `(:$a,$b:)`</p>'
  ],
  29=>[
    'uniqueid' => 1552587894535742,
    'description' => 'old draw',
    'qtype' => 'draw',
    'control' => '$snaptogrid=1'."\n".'$answers=array("1,1","1,3")',
    'qtext' => '<p>Plot the dots (1,1) and (1,3)</p>'
  ],
  30=>[
    'uniqueid' => 1552587894535743,
    'description' => 'essay no editor',
    'qtype' => 'essay',
    'control' => '',
    'qtext' => '<p>Enter something</p>'
  ],
  31=>[
    'uniqueid' => 1552587894535744,
    'description' => 'essay w editor w takeanything',
    'qtype' => 'essay',
    'control' => '$displayformat = "editor"'."\n".'$scoremethod="takeanything"',
    'qtext' => '<p>Enter something</p>'
  ],
  32=>[
    'uniqueid' => 1552587894535745,
    'description' => 'essay w editor Takeanythingorblank',
    'qtype' => 'essay',
    'control' => '$displayformat = "editor"'."\n".'$scoremethod="takeanythingorblank"',
    'qtext' => '<p>Enter something or nothing</p>'
  ],
  33=>[
    'uniqueid' => 1552587894535746,
    'description' => 'string',
    'qtype' => 'string',
    'control' => '$answer="cat"',
    'qtext' => '<p>Enter "cat"</p>'
  ],
  34=>[
    'uniqueid' => 1552587894535747,
    'description' => 'Simple file w takeanythingorblank',
    'qtype' => 'file',
    'control' => '$scoremethod="takeanythingorblank"',
    'qtext' => '<p>Upload your work here (or leave it blank)</p>'
  ],
  35=> [
    'uniqueid' => 1552587894535748,
    'description' => 'multipart w mixed parts',
    'qtype' => 'multipart',
    'control' => '$anstypes="calculated,calcinterval,calccomplex"'."\n".'$answerformat[1]="fraction"'."\n".'$a = rand(2,100)'."\n".'$answer = array("sqrt($a)","(1/$a,oo)","$a+i")',
    'qtext' => 'Type<br/>: `sqrt($a)`: $answerbox[0]<br/>`(1/$a,oo)`: $answerbox[1]<br/>`$a+i`: $answerbox[2]'
  ],
  36=> [
    'uniqueid' => 1552587894535749,
    'description' => 'Basic conditional',
    'qtype' => 'conditional',
    'control' => '$anstypes="number,calculated"'."\n".'$stua=getstuans($stuanswers,$thisq,0)'."\n".'$stub=getstuans($stuanswersval,$thisq,1)'."\n".'$answer = ($stua+$stub == 5)',
    'qtext' => 'Enter two numbers that add to 5'
  ],
  37=>[
    'uniqueid' => 1552587894535750,
    'description' => 'string w typeahead',
    'qtype' => 'string',
    'control' => '$displayformat="typeahead"'."\n".'$questions=array("linear","quadratic","cubic","quartic")',
    'qtext' => '<p>Enter "quadratic". You should see a suggestions list</p>'
  ],
  38=>[
    'uniqueid' => 1552587894535751,
    'description' => 'accounting w blanks and credit/debit',
    'qtype' => 'multipart',
    'control' => '$hidetips = true'."\n".'$abstolerance = .01'."\n".'$scoremethod = "acct"'."\n".'loadlibrary("acct")'."\n".'$furn = rrand(4000,7000,500)'."\n".'$furnc = rrand(1500,2500,500)'."\n".'$ops = array("Cash","Accounts Receivable","Supplies","Equipment","Accounts Payable","Notes Payable","Common Stock","Retained Earnings","Service Revenue","Rent Expense","Furniture", "Prepaid Insurance","Advertising Expense","Unearned Revenue","Wages Expense","Interest Expense","Salaries Expense","Prepaid Rent")'."\n".'$ja[0][\'date\'] = ""'."\n".'$ja[0][\'debits\'] = array("Furniture", $furn)'."\n".'$ja[0][\'credits\'] = array("Cash",$furnc, "Accounts Payable", $furn - $furnc)'."\n".'$jea = makejournal($ja, 0, $ops, $anstypes, $questions, $answer, $showanswer, $displayformat, $answerboxsize)'."\n".'$answer = scorejournal($stuanswers[$thisq], $answer, $ja, 0)',
    'qtext' => '<p>Journalize: Purchased office furniture for $$furn, paying $$furnc cash. The balance must be paid within 60 days.</p><p>$jea</p>'
  ],
  39=>[
    'uniqueid' => 1552587894535752,
    'description' => 'number w hints',
    'qtype' => 'number',
    'control' => '$answer=3'."\n".'$hints = array("","After 1 miss","After 2 or more misses")',
    'qtext' => '<p>The answer is 3. Get it wrong to see hints</p><p>$hintloc</p>',
    'extref' => 'video!!https://www.youtube.com/watch?v=zc2CpyRtjvY!!1'
  ],
  40=>[
    'uniqueid' => 1552587894535753,
    'description' => 'Question w detailed soln',
    'qtype' => 'number',
    'control' => '$a = rand(1,100)'."\n".'$answer = $a',
    'qtext' => 'Type $a',
    'extref' => 'video!!https://www.youtube.com/watch?v=zc2CpyRtjvY!!1',
    'solutionopts' => 7,
    'solution' => 'This is a detailed solution.  Enter enter $a, you click in the box and type $a.'
  ],
  41=>[
    'uniqueid' => 1552587894535754,
    'description' => 'geogebra and inline JS',
    'qtype' => 'number',
    'control' => '$a,$b=diffrands(-4,4,2)'."\n".'$answer=$a'."\n".'loadlibrary("geogebra")'."\n".'$geogebrainit = array("setValue(\\"h\\",$b)")'."\n".'$geogebraget = array("getValue(\\"h\\")")'."\n".'$g = addGeogebra("701939",600,400,$geogebrainit,array(),$geogebraget,$thisq)',
    'qtext' => '<p>The answer is $a. The geogebra should be initialized with a shift of $b, and the answerbox value should fill on submit</p><p>$g</p><p>Test 2 inline JS: <span onclick="this.style.display=\'none\'">Clicking this should make it disappear</span></p><p>Test 3 JS: <span id="test1">Clicking this should make it disappear</span><script type="text/javascript">$("#test1").on("click", function(e){e.target.style.display="none";});</script></p><p>Test 4 JS: <span id="test2">Clicking this should make it disappear</span><script type="text/javascript">$(function() { $("#test2").on("click", function(e){e.target.style.display="none";});});</script></p>'
  ],
  42=>[
    'uniqueid' => 1552587894535755,
    'description' => 'multipart w changing anstypes',
    'qtype' => 'multipart',
    'control' => '$anstypes = "number"'."\n".'$anstypes = "number,number" if ($attemptn > 0)'."\n".'$requestclearla = true if ($attemptn == 1)',
    'qtext' => '<p>One the first try there should be 1 answerblank. On second there should be two and the first entered answer should be cleared</p>'
  ],
  43=> [
    'uniqueid' => 1552587894535756,
    'description' => 'Simple multi-part number question, one showans',
    'qtype' => 'multipart',
    'control' => '$anstypes="number,number,number"'."\n".'$showanswer = "This is a single showanswer"'."\n".'$a = rands(1,100,3)'."\n".'$answer = $a',
    'qtext' => '(singlescore). Math test: `4/6`. Type<br/>$a[0]: $answerbox[0]<br/>$a[1]: $answerbox[1]<br/>$a[2]: $answerbox[2]'
  ]
];

$simpleA = [
  'startdate' =>  -2*24,
  'enddate' => 24*7,
  'reviewdate' => 2000000000,
  'displaymethod' => 'skip',
  'ptsposs' => 10,
  'submitby' => 'by_question',
  'keepscore' => 'best',
  'defregens' => 3,
  'defregenpenalty' => 0,
  'defpoints' => 5,
  'defattempts' => 2,
  'defpenalty' => 0,
  'ver' => 2,
  'questions' => [
    0=>['questionsetid' => 0],
    1=>['questionsetid' => 1],
  ],
  'itemorder' => [0,1]
];

// ensure each assessment keys a unique key, regardless of the group it's in
$assessGroups = [
  [
    'name' => 'Basics',
    'assessments' => [
      [
        'name' => 'HW 1',
        'summary' => 'by-question, skip, showscores during, showans after last try, no penalties<br>
                      3 regens, 2 tries per, no penalties',
        'startdate' =>  -2*24,
        'enddate' => 24*7,
        'reviewdate' => 2000000000,
        'displaymethod' => 'skip',
        'ptsposs' => 20,
        'submitby' => 'by_question',
        'showscores' => 'during',
        'showans' => 'after_lastattempt',
        'defregens' => 3,
        'defregenpenalty' => 0,
        'defpoints' => 5,
        'defattempts' => 2,
        'defpenalty' => 0,
        'ver' => 2,
        'questions' => [
          0=>['questionsetid' => 0],
          1=>['questionsetid' => 1],
          2=>['questionsetid' => 0],
          3=>['questionsetid' => 1],
        ],
        'itemorder' => [0,1,2,3],
        'studata' => [
          'source' => 'hw1',
          'starttime' => -4*24,
          'lastchange' => -3*24
        ]
      ],
      [
        'name' => 'HW 2',
        'summary' => 'by-question, full, showscores during, showans after last try, no penalties<br>
                      3 regens, 2 tries per, no penalties',
        'startdate' =>  -2*24,
        'enddate' => 24*7,
        'reviewdate' => 2000000000,
        'displaymethod' => 'full',
        'ptsposs' => 15,
        'submitby' => 'by_question',
        'showscores' => 'during',
        'showans' => 'after_lastattempt',
        'defregens' => 3,
        'defregenpenalty' => 0,
        'defpoints' => 5,
        'defattempts' => 2,
        'defpenalty' => 0,
        'ver' => 2,
        'questions' => [
          0=>['questionsetid' => 0],
          1=>['questionsetid' => 8],
          2=>['questionsetid' => 1]
        ],
        'itemorder' => [0,1,2]
      ],
      [
        'name' => 'Quiz 1',
        'summary' => 'by-assessment, skip, showscores during, showans after take, no penalties<br>
                      3 regens, 2 tries per, no penalties, keep best',
        'startdate' =>  -2*24,
        'enddate' => 24*7,
        'reviewdate' => 2000000000,
        'displaymethod' => 'skip',
        'ptsposs' => 20,
        'submitby' => 'by_assessment',
        'showscores' => 'during',
        'showans' => 'after_take',
        'keepscore' => 'best',
        'defregens' => 3,
        'defregenpenalty' => 0,
        'defpoints' => 5,
        'defattempts' => 2,
        'defpenalty' => 0,
        'ver' => 2,
        'questions' => [
          0=>['questionsetid' => 0],
          1=>['questionsetid' => 1],
          2=>['questionsetid' => 0],
          3=>['questionsetid' => 1],
        ],
        'itemorder' => [0,1,2,3],
        'studata' => [
          'source' => 'q1',
          'starttime' => -4*24,
          'lastchange' => -1*24
        ]
      ],
      [
        'name' => 'Quiz 2',
        'summary' => 'by-assessment, full, showscores during, showans after take, no penalties<br>
                      3 regens, 2 tries per, no penalties, keep best',
        'startdate' =>  -2*24,
        'enddate' => 24*7,
        'reviewdate' => 2000000000,
        'displaymethod' => 'full',
        'ptsposs' => 15,
        'submitby' => 'by_assessment',
        'showscores' => 'during',
        'showans' => 'after_take',
        'keepscore' => 'best',
        'defregens' => 3,
        'defregenpenalty' => 0,
        'defpoints' => 5,
        'defattempts' => 2,
        'defpenalty' => 0,
        'ver' => 2,
        'questions' => [
          0=>['questionsetid' => 0],
          1=>['questionsetid' => 1],
          2=>['questionsetid' => 0]
        ],
        'itemorder' => [0,1,2]
      ],
      [
        'name' => 'Quiz 3',
        'summary' => 'by-assessment, full, showscores during, showans after take, no penalties<br>
                      3 regens, 1 try per, no penalties, keep best',
        'startdate' =>  -2*24,
        'enddate' => 24*7,
        'reviewdate' => 2000000000,
        'displaymethod' => 'full',
        'ptsposs' => 15,
        'submitby' => 'by_assessment',
        'showscores' => 'during',
        'showans' => 'after_take',
        'keepscore' => 'best',
        'defregens' => 3,
        'defregenpenalty' => 0,
        'defpoints' => 5,
        'defattempts' => 1,
        'defpenalty' => 0,
        'ver' => 2,
        'questions' => [
          0=>['questionsetid' => 0],
          1=>['questionsetid' => 1],
          2=>['questionsetid' => 0]
        ],
        'itemorder' => [0,1,2]
      ],
      [
        'name' => 'VideoCued 1',
        'summary' => 'by-question, video cued, showscores during, showans after last try, no penalties<br>
                      3 regens, 2 tries per, no penalties',
        'startdate' =>  -2*24,
        'enddate' => 24*7,
        'reviewdate' => 2000000000,
        'displaymethod' => 'video_cued',
        'ptsposs' => 15,
        'submitby' => 'by_question',
        'showscores' => 'during',
        'showans' => 'after_lastattempt',
        'defregens' => 3,
        'defregenpenalty' => 0,
        'defpoints' => 5,
        'defattempts' => 2,
        'defpenalty' => 0,
        'ver' => 2,
        'questions' => [
          0=>['questionsetid' => 0],
          1=>['questionsetid' => 1],
          2=>['questionsetid' => 0]
        ],
        'itemorder' => [0,1,2],
        'viddata' => 'a:6:{i:0;a:2:{i:0;s:11:"p_di4Zn4wz4";i:1;s:4:"16:9";}i:1;a:6:{i:0;s:11:"First Q Seg";i:1;s:2:"23";i:2;s:1:"0";i:3;s:2:"33";i:4;b:1;i:5;s:16:"First Q Followup";}i:2;a:3:{i:0;s:12:"Second Q Seg";i:1;s:2:"45";i:2;s:1:"1";}i:3;a:2:{i:0;s:23:"Vid seg between 2 and 3";i:1;s:2:"51";}i:4;a:3:{i:0;s:11:"Third Q seg";i:1;s:2:"55";i:2;s:1:"2";}i:5;a:1:{i:0;s:10:"Conclusion";}}'
      ],
    ]
  ],
  [
    'name' => 'Closed',
    'assessments' => [
      [
        'name' => 'Closed 1',
        'summary' => 'hard hidden.  Check for not available message',
        'avail' => 0,
        'startdate' =>  -2*24,
        'enddate' => 24*7,
        'reviewdate' => 2000000000,
        'ptsposs' => 5,
        'displaymethod' => 'skip',
        'submitby' => 'by_question',
        'ver' => 2,
        'questions' => [
          0=>['questionsetid' => 0],
        ],
        'itemorder' => [0]
      ],
      [
        'name' => 'Closed 2',
        'summary' => 'not yet available',
        'startdate' =>  2*24,
        'enddate' => 24*7,
        'reviewdate' => 2000000000,
        'ptsposs' => 5,
        'displaymethod' => 'skip',
        'submitby' => 'by_question',
        'ver' => 2,
        'questions' => [
          0=>['questionsetid' => 0],
        ],
        'itemorder' => [0]
      ],
      [
        'name' => 'Closed 3',
        'summary' => 'past due, no practice, no latepasses',
        'startdate' =>  -7*24,
        'enddate' => -2*24,
        'reviewdate' => 0,
        'allowlate' => 0,
        'displaymethod' => 'skip',
        'submitby' => 'by_question',
        'defpoints' => 5,
        'ptsposs' => 5,
        'ver' => 2,
        'questions' => [
          0=>['questionsetid' => 0],
        ],
        'itemorder' => [0]
      ],
      [
        'name' => 'Closed 4',
        'summary' => 'past due, no practice, latepasses allowed',
        'startdate' =>  -7*24,
        'enddate' => -1.5*24,
        'reviewdate' => 0,
        'allowlate' => 11,
        'displaymethod' => 'skip',
        'submitby' => 'by_question',
        'defpoints' => 5,
        'ptsposs' => 5,
        'ver' => 2,
        'questions' => [
          0=>['questionsetid' => 0],
        ],
        'itemorder' => [0]
      ],
      [
        'name' => 'Closed 5',
        'summary' => 'past due, practice, no latepasses',
        'startdate' =>  -7*24,
        'enddate' => -2*24,
        'reviewdate' => 2000000000,
        'allowlate' => 0,
        'displaymethod' => 'skip',
        'submitby' => 'by_question',
        'defpoints' => 5,
        'ptsposs' => 10,
        'defregens' => 3,
        'defattempts' => 2,
        'ver' => 2,
        'questions' => [
          0=>['questionsetid' => 0],
          1=>['questionsetid' => 1]
        ],
        'itemorder' => [0,1],
        'studata' => [
          'source' => 'q01byq',
          'starttime' => -4*24,
          'lastchange' => -3*24
        ]
      ],
      [
        'name' => 'Closed 6',
        'summary' => 'past due, practice, latepasses allowed',
        'startdate' =>  -7*24,
        'enddate' => -1.5*24,
        'reviewdate' => 2000000000,
        'allowlate' => 11,
        'displaymethod' => 'skip',
        'submitby' => 'by_question',
        'defpoints' => 5,
        'ptsposs' => 10,
        'ver' => 2,
        'questions' => [
          0=>['questionsetid' => 0],
          1=>['questionsetid' => 1]
        ],
        'itemorder' => [0,1]
      ],
      [
        'name' => 'Closed 7',
        'summary' => 'prereq of 5pts on Closed 6',
        'startdate' =>  -7*24,
        'enddate' => 2*24,
        'reviewdate' => 2000000000,
        'displaymethod' => 'skip',
        'submitby' => 'by_question',
        'reqscoreaid' => -1,
        'reqscore' => 5,
        'reqscoretype' => 1,
        'ptsposs' => 10,
        'ver' => 2,
        'questions' => [
          0=>['questionsetid' => 0],
        ],
        'itemorder' => [0]
      ],
      [
        'name' => 'Closed 8',
        'summary' => 'past due, practice, no latepasses, was by_assessment with penalties',
        'startdate' =>  -7*24,
        'enddate' => -2*24,
        'reviewdate' => 2000000000,
        'allowlate' => 0,
        'displaymethod' => 'full',
        'submitby' => 'by_assessment',
        'defregens' => 3,
        'defregenpenalty' => 0,
        'defpoints' => 5,
        'defattempts' => 2,
        'defpenalty' => 30,
        'ptsposs' => 10,
        'ver' => 2,
        'questions' => [
          0=>['questionsetid' => 0],
          1=>['questionsetid' => 1]
        ],
        'itemorder' => [0,1],
        'studata' => [
          'source' => 'q01bya',
          'starttime' => -4*24,
          'lastchange' => -3*24
        ]
      ],
    ]
  ],
  [
    'name' => 'Features',
    'assessments' => [
      [
        'name' => 'Features 1 with a really long title',
        'summary' => 'check for: external resources, end messages, between-question text,
                      long title handling, post to forum, msg instructor,
                      custom categories and category breakdown, default feedback text',
        'startdate' =>  -2*24,
        'enddate' => 24*7,
        'reviewdate' => 2000000000,
        'displaymethod' => 'skip',
        'ptsposs' => 15,
        'submitby' => 'by_question',
        'showscores' => 'during',
        'showans' => 'after_lastattempt',
        'defregens' => 3,
        'defregenpenalty' => 0,
        'defpoints' => 5,
        'defattempts' => 2,
        'defpenalty' => 0,
        'ver' => 2,
        'questions' => [
          0=>['questionsetid' => 0, 'category' => 'Cat 1'],
          1=>['questionsetid' => 1, 'category' => 'Cat 1'],
          2=>['questionsetid' => 0, 'category' => 'Category 2 with a long name']
        ],
        'posttoforum' => 1,
        'msgtoinstr' => 1,
        'deffeedbacktext' => 'This is default feedback text',
        'intro' => '["<p>This is the general intro text. `2/3`.<\/p>",{"displayBefore":0,"displayUntil":1,"text":"<p>This should show before questions 1 and 2, closed after first. `2/3`.<\/p>","ispage":"0","pagetitle":"","forntype":0},{"displayBefore":2,"displayUntil":2,"text":"<p>This should show before question 3. `2/3`.<\/p>","ispage":0,"pagetitle":"","forntype":1},{"displayBefore":3,"displayUntil":3,"text":"<p>This should show after question 3<\/p>","ispage":0,"pagetitle":"","forntype":1}]',
        'extrefs' => '[{"label":"Textbook","link":"https://www.google.com"},{"label":"Calculator","link":"https://www.desmos.com"}]',
        'endmsg' => 'a:4:{s:4:"type";s:1:"1";s:3:"def";s:15:"Needs more work";s:4:"msgs";a:2:{i:90;s:6:"Great!";i:50;s:13:"Getting there";}s:9:"commonmsg";s:31:"<p>Generic message for all.</p>";}',
        'itemorder' => [0,1,2]
      ],
      [
        'name' => 'Penalties 1',
        'summary' => 'by_question with 10% retry penalty, 20% regen penalty',
        'startdate' =>  -2*24,
        'enddate' => 24*7,
        'reviewdate' => 2000000000,
        'displaymethod' => 'skip',
        'ptsposs' => 15,
        'submitby' => 'by_question',
        'showscores' => 'during',
        'showans' => 'after_lastattempt',
        'defregens' => 3,
        'defregenpenalty' => 20,
        'defpoints' => 5,
        'defattempts' => 4,
        'defpenalty' => 10,
        'ver' => 2,
        'questions' => [
          0=>['questionsetid' => 0, 'category' => 'Cat 1'],
          1=>['questionsetid' => 1, 'category' => 'Cat 1'],
          2=>['questionsetid' => 0, 'category' => 'Cat 2']
        ],
        'itemorder' => [0,1,2],
        'studata' => [
          'source' => 'p1',
          'starttime' => -4*24,
          'lastchange' => -3*24
        ]
      ],
      [
        'name' => 'Penalties 2',
        'summary' => 'by_assessment with 10% retry penalty, 20% regen penalty',
        'startdate' =>  -2*24,
        'enddate' => 24*7,
        'reviewdate' => 2000000000,
        'displaymethod' => 'skip',
        'ptsposs' => 15,
        'submitby' => 'by_assessment',
        'showscores' => 'during',
        'showans' => 'after_lastattempt',
        'defregens' => 3,
        'defregenpenalty' => 20,
        'defpoints' => 5,
        'defattempts' => 4,
        'defpenalty' => 10,
        'ver' => 2,
        'questions' => [
          0=>['questionsetid' => 0, 'category' => 'Cat 1'],
          1=>['questionsetid' => 1, 'category' => 'Cat 1'],
          2=>['questionsetid' => 0, 'category' => 'Cat 2']
        ],
        'itemorder' => [0,1,2]
      ],
      [
        'name' => 'SingleScore',
        'summary' => 'first and third question are singlescore',
        'startdate' =>  -2*24,
        'enddate' => 24*7,
        'reviewdate' => 2000000000,
        'displaymethod' => 'skip',
        'ptsposs' => 15,
        'submitby' => 'by_question',
        'showscores' => 'during',
        'showans' => 'after_lastattempt',
        'defregens' => 3,
        'defregenpenalty' => 0,
        'defpoints' => 5,
        'defattempts' => 2,
        'defpenalty' => 0,
        'ver' => 2,
        'questions' => [
          0=>['questionsetid' => 2],
          1=>['questionsetid' => 1],
          2=>['questionsetid' => 2]
        ],
        'itemorder' => [0,1,2]
      ],
      [
        'name' => 'Category test',
        'summary' => 'category for question 1 is default outcome, for question 2 is assessment, for question 3 is custom outcome',
        'startdate' =>  -2*24,
        'enddate' => 24*7,
        'reviewdate' => 2000000000,
        'displaymethod' => 'skip',
        'ptsposs' => 15,
        'submitby' => 'by_question',
        'showscores' => 'during',
        'showans' => 'after_lastattempt',
        'defregens' => 3,
        'defregenpenalty' => 0,
        'defpoints' => 5,
        'defattempts' => 2,
        'defpenalty' => 0,
        'ver' => 2,
        'defoutcome' => 0,
        'questions' => [
          0=>['questionsetid' => 0, 'category' => 0],
          1=>['questionsetid' => 1, 'category' => 'AID-1'],
          2=>['questionsetid' => 0, 'category' => 1]
        ],
        'itemorder' => [0,1,2]
      ],
      [
        'name' => 'Withdrawn test 1',
        'summary' => 'Q2 is withdrawn full credit, Q3 is withdrawn 0 points',
        'startdate' =>  -2*24,
        'enddate' => 24*7,
        'reviewdate' => 2000000000,
        'displaymethod' => 'skip',
        'ptsposs' => 15,
        'submitby' => 'by_question',
        'showscores' => 'during',
        'showans' => 'after_lastattempt',
        'defregens' => 3,
        'defpoints' => 5,
        'defattempts' => 2,
        'ver' => 2,
        'defoutcome' => 0,
        'questions' => [
          0=>['questionsetid' => 0],
          1=>['questionsetid' => 0, 'withdrawn' => 1],
          2=>['questionsetid' => 0, 'withdrawn' => 1, 'points' => 0],
          3=>['questionsetid' => 0],
        ],
        'itemorder' => [0,1,2,3]
      ],
      [
        'name' => 'Withdrawn test 2',
        'summary' => 'Q2 is withdrawn full credit, Q3 is withdrawn 0 points',
        'startdate' =>  -2*24,
        'enddate' => 24*7,
        'reviewdate' => 2000000000,
        'displaymethod' => 'full',
        'ptsposs' => 15,
        'submitby' => 'by_assessment',
        'showscores' => 'during',
        'showans' => 'after_lastattempt',
        'defregens' => 3,
        'defpoints' => 5,
        'defattempts' => 2,
        'ver' => 2,
        'defoutcome' => 0,
        'questions' => [
          0=>['questionsetid' => 0],
          1=>['questionsetid' => 0, 'withdrawn' => 1],
          2=>['questionsetid' => 0, 'withdrawn' => 1, 'points' => 0],
          3=>['questionsetid' => 0],
        ],
        'itemorder' => [0,1,2,3]
      ],
      [
        'name' => 'Paged Full Test',
        'summary' => 'by-question, full, with pages',
        'startdate' =>  -2*24,
        'enddate' => 24*7,
        'reviewdate' => 2000000000,
        'displaymethod' => 'full',
        'ptsposs' => 15,
        'submitby' => 'by_question',
        'showscores' => 'during',
        'showans' => 'after_lastattempt',
        'defregens' => 3,
        'defregenpenalty' => 0,
        'defpoints' => 5,
        'defattempts' => 2,
        'defpenalty' => 0,
        'ver' => 2,
        'intro' => '["",{"displayBefore":0,"displayUntil":0,"text":"<p>This is the first page text `2/3`<\/p>","ispage":1,"pagetitle":"The Basics","forntype":1},{"displayBefore":0,"displayUntil":0,"text":"<p>May be a little more just for fun `2/3`<\/p>","ispage":0,"pagetitle":"First Page Title","forntype":1},{"displayBefore":0,"displayUntil":0,"text":"<p>With some text `2/3`<\/p>","ispage":1,"pagetitle":"A second page","forntype":1},{"displayBefore":1,"displayUntil":1,"text":"<p>Between text `2/3`<\/p>","ispage":0,"pagetitle":"","forntype":1},{"displayBefore":2,"displayUntil":2,"text":"<p>After text `2/3`<\/p>","ispage":0,"pagetitle":"","forntype":1},{"displayBefore":2,"displayUntil":2,"text":"<p>The third page text `2/3`<\/p>","ispage":1,"pagetitle":"The third page","forntype":1},{"displayBefore":4,"displayUntil":4,"text":"<p>Conclusion text `2/3`<\/p>","ispage":1,"pagetitle":"Conclusion `2/3`","forntype":1}]',
        'questions' => [
          0=>['questionsetid' => 0],
          1=>['questionsetid' => 1],
          2=>['questionsetid' => 2],
          3=>['questionsetid' => 3]
        ],
        'itemorder' => [0,1,2,3]
      ],
      [
        'name' => 'Jump to Answer',
        'summary' => 'by-question, skip, Jump to Answer<br>
                      3 regens, 2 tries per, no penalties',
        'startdate' =>  -2*24,
        'enddate' => 24*7,
        'reviewdate' => 2000000000,
        'displaymethod' => 'skip',
        'ptsposs' => 15,
        'submitby' => 'by_question',
        'showscores' => 'during',
        'showans' => 'jump_to_answer',
        'defregens' => 100,
        'defregenpenalty' => 0,
        'defpoints' => 5,
        'defattempts' => 2,
        'defpenalty' => 0,
        'ver' => 2,
        'questions' => [
          0=>['questionsetid' => 0],
          1=>['questionsetid' => 1],
          2=>['questionsetid' => 0]
        ],
        'itemorder' => [0,1,2]
      ],
      [
        'name' => 'Password',
        'summary' => 'Password: abc',
        'startdate' =>  -2*24,
        'enddate' => 24*7,
        'password' => 'abc',
        'reviewdate' => 2000000000,
        'displaymethod' => 'skip',
        'ptsposs' => 15,
        'submitby' => 'by_question',
        'showscores' => 'during',
        'showans' => 'after_lastattempt',
        'defregens' => 3,
        'defpoints' => 5,
        'defattempts' => 2,
        'ver' => 2,
        'questions' => [
          0=>['questionsetid' => 0],
          1=>['questionsetid' => 1],
          2=>['questionsetid' => 0]
        ],
        'itemorder' => [0,1,2]
      ],
      [
        'name' => 'Livepoll',
        'summary' => 'livepoll',
        'startdate' =>  -2*24,
        'enddate' => 24*7,
        'reviewdate' => 2000000000,
        'displaymethod' => 'livepoll',
        'ptsposs' => 15,
        'submitby' => 'by_question',
        'showscores' => 'during',
        'showans' => 'after_lastattempt',
        'defregens' => 3,
        'defregenpenalty' => 0,
        'defpoints' => 5,
        'defattempts' => 100,
        'defpenalty' => 0,
        'ver' => 2,
        'questions' => [
          0=>['questionsetid' => 0],
          1=>['questionsetid' => 1],
          2=>['questionsetid' => 0]
        ],
        'itemorder' => [0,1,2]
      ],
    ]
  ],
  [
    'name' => 'Timed',
    'assessments' => [
      [
        'name' => 'Timed 1',
        'summary' => 'by-question, skip, kickout timelimit of 20s',
        'timelimit' => -20,
        'startdate' =>  -2*24,
        'enddate' => 24*7,
        'reviewdate' => 2000000000,
        'displaymethod' => 'skip',
        'ptsposs' => 15,
        'submitby' => 'by_question',
        'showscores' => 'during',
        'showans' => 'after_lastattempt',
        'defregens' => 3,
        'defregenpenalty' => 0,
        'defpoints' => 5,
        'defattempts' => 2,
        'defpenalty' => 0,
        'ver' => 2,
        'questions' => [
          0=>['questionsetid' => 0],
          1=>['questionsetid' => 1],
          2=>['questionsetid' => 0]
        ],
        'itemorder' => [0,1,2]
      ],
      [
        'name' => 'Timed 2',
        'summary' => 'by-assess, skip, 20 retakes, kickout timelimit of 20s',
        'timelimit' => -20,
        'startdate' =>  -2*24,
        'enddate' => 24*7,
        'reviewdate' => 2000000000,
        'displaymethod' => 'skip',
        'ptsposs' => 15,
        'submitby' => 'by_assessment',
        'showscores' => 'during',
        'showans' => 'after_lastattempt',
        'defregens' => 20,
        'defregenpenalty' => 0,
        'defpoints' => 5,
        'defattempts' => 2,
        'defpenalty' => 0,
        'ver' => 2,
        'questions' => [
          0=>['questionsetid' => 0],
          1=>['questionsetid' => 1],
          2=>['questionsetid' => 0]
        ],
        'itemorder' => [0,1,2]
      ],
      [
        'name' => 'Timed 3',
        'summary' => 'by-question, skip, timelimit of 20s w 30s grace',
        'timelimit' => 20,
        'overtime_grace' => 30,
        'overtime_penalty' => 20,
        'startdate' =>  -2*24,
        'enddate' => 24*7,
        'reviewdate' => 2000000000,
        'displaymethod' => 'skip',
        'ptsposs' => 15,
        'submitby' => 'by_question',
        'showscores' => 'during',
        'showans' => 'after_lastattempt',
        'defregens' => 3,
        'defregenpenalty' => 0,
        'defpoints' => 5,
        'defattempts' => 2,
        'defpenalty' => 0,
        'ver' => 2,
        'questions' => [
          0=>['questionsetid' => 0],
          1=>['questionsetid' => 1],
          2=>['questionsetid' => 0]
        ],
        'itemorder' => [0,1,2]
      ],
      [
        'name' => 'Timed 4',
        'summary' => 'by-assess, skip, 20 retakes, timelimit of 20s w 30s grace',
        'timelimit' => 20,
        'overtime_grace' => 30,
        'overtime_penalty' => 20,
        'startdate' =>  -2*24,
        'enddate' => 24*7,
        'reviewdate' => 2000000000,
        'displaymethod' => 'skip',
        'ptsposs' => 15,
        'submitby' => 'by_assessment',
        'showscores' => 'during',
        'showans' => 'after_lastattempt',
        'defregens' => 20,
        'defregenpenalty' => 0,
        'defpoints' => 5,
        'defattempts' => 2,
        'defpenalty' => 0,
        'ver' => 2,
        'questions' => [
          0=>['questionsetid' => 0],
          1=>['questionsetid' => 1],
          2=>['questionsetid' => 0]
        ],
        'itemorder' => [0,1,2]
      ]
    ]
  ],
  [
    'name' => 'Small Features',
    'assessments' => [
      [
        'name' => 'Hard to print',
        'summary' => 'hard to print enabled',
        'startdate' =>  -2*24,
        'enddate' => 24*7,
        'reviewdate' => 2000000000,
        'noprint' => 1,
        'displaymethod' => 'skip',
        'ptsposs' => 10,
        'submitby' => 'by_question',
        'showscores' => 'during',
        'showans' => 'after_lastattempt',
        'defregens' => 3,
        'defpoints' => 5,
        'defattempts' => 2,
        'ver' => 2,
        'questions' => [
          0=>['questionsetid' => 0],
          1=>['questionsetid' => 1]
        ],
        'itemorder' => [0,1]
      ],
      [
        'name' => 'No scores shown (tutorial style)',
        'summary' => '',
        'istutorial' => 1,
        'startdate' =>  -2*24,
        'enddate' => 24*7,
        'reviewdate' => 2000000000,
        'noprint' => 1,
        'displaymethod' => 'skip',
        'ptsposs' => 10,
        'submitby' => 'by_question',
        'showscores' => 'during',
        'showans' => 'after_lastattempt',
        'defregens' => 3,
        'defpoints' => 5,
        'defattempts' => 2,
        'ver' => 2,
        'questions' => [
          0=>['questionsetid' => 0],
          1=>['questionsetid' => 1]
        ],
        'itemorder' => [0,1]
      ]
    ]
  ],
  [
    'name' => 'Question types',
    'assessments' => [
      [
        'name' => 'Qtypes 1',
        'summary' => 'number,calculated,numfunc,choices,multans,matching',
        'startdate' =>  -2*24,
        'enddate' => 24*7,
        'reviewdate' => 2000000000,
        'noprint' => 1,
        'displaymethod' => 'skip',
        'ptsposs' => 40,
        'submitby' => 'by_question',
        'showscores' => 'during',
        'showans' => 'after_lastattempt',
        'defregens' => 100,
        'defpoints' => 5,
        'defattempts' => 100,
        'ver' => 2,
        'questions' => [
          0=>['questionsetid' => 3],
          1=>['questionsetid' => 26],
          2=>['questionsetid' => 9],
          3=>['questionsetid' => 10],
          4=>['questionsetid' => 21],
          5=>['questionsetid' => 5],
          6=>['questionsetid' => 6],
          7=>['questionsetid' => 22]
        ],
        'itemorder' => [0,1,2,3,4,5,6,7]
      ],
      [
        'name' => 'Qtypes 2',
        'summary' => 'matrix,calcmatrix',
        'startdate' =>  -2*24,
        'enddate' => 24*7,
        'reviewdate' => 2000000000,
        'noprint' => 1,
        'displaymethod' => 'skip',
        'ptsposs' => 20,
        'submitby' => 'by_question',
        'showscores' => 'during',
        'showans' => 'after_lastattempt',
        'defregens' => 100,
        'defpoints' => 5,
        'defattempts' => 100,
        'ver' => 2,
        'questions' => [
          0=>['questionsetid' => 15],
          1=>['questionsetid' => 16],
          2=>['questionsetid' => 17],
          3=>['questionsetid' => 18]
        ],
        'itemorder' => [0,1,2,3]
      ],
      [
        'name' => 'Qtypes 3',
        'summary' => 'complex.interval,ntuple',
        'startdate' =>  -2*24,
        'enddate' => 24*7,
        'reviewdate' => 2000000000,
        'noprint' => 1,
        'displaymethod' => 'skip',
        'ptsposs' => 45,
        'submitby' => 'by_question',
        'showscores' => 'during',
        'showans' => 'after_lastattempt',
        'defregens' => 100,
        'defpoints' => 5,
        'defattempts' => 100,
        'ver' => 2,
        'questions' => [
          0=>['questionsetid' => 23],
          1=>['questionsetid' => 12],
          2=>['questionsetid' => 24],
          3=>['questionsetid' => 25],
          4=>['questionsetid' => 13],
          5=>['questionsetid' => 14],
          6=>['questionsetid' => 27],
          7=>['questionsetid' => 11],
          8=>['questionsetid' => 28]
        ],
        'itemorder' => [0,1,2,3,4,5,6,7,8]
      ],
      [
        'name' => 'Qtypes 4',
        'summary' => 'draw,multipart,conditional',
        'startdate' =>  -2*24,
        'enddate' => 24*7,
        'reviewdate' => 2000000000,
        'displaymethod' => 'skip',
        'ptsposs' => 30,
        'submitby' => 'by_question',
        'showscores' => 'during',
        'showans' => 'after_lastattempt',
        'defregens' => 3,
        'defregenpenalty' => 0,
        'defpoints' => 5,
        'defattempts' => 2,
        'defpenalty' => 0,
        'ver' => 2,
        'questions' => [
          0=>['questionsetid' => 19],
          1=>['questionsetid' => 29],
          2=>['questionsetid' => 1],
          3=>['questionsetid' => 35],
          4=>['questionsetid' => 2],
          5=>['questionsetid' => 36],
          6=>['questionsetid' => 43]
        ],
        'itemorder' => [0,1,2,3,4,5,6]
      ],
      [
        'name' => 'Qtypes 5',
        'summary' => 'string, essay, file',
        'startdate' =>  -2*24,
        'enddate' => 24*7,
        'reviewdate' => 2000000000,
        'displaymethod' => 'skip',
        'ptsposs' => 35,
        'submitby' => 'by_question',
        'showscores' => 'during',
        'showans' => 'after_lastattempt',
        'defregens' => 3,
        'defregenpenalty' => 0,
        'defpoints' => 5,
        'defattempts' => 2,
        'defpenalty' => 0,
        'ver' => 2,
        'questions' => [
          0=>['questionsetid' => 30],
          1=>['questionsetid' => 31],
          2=>['questionsetid' => 32],
          3=>['questionsetid' => 33],
          4=>['questionsetid' => 20],
          5=>['questionsetid' => 7],
          6=>['questionsetid' => 34]
        ],
        'itemorder' => [0,1,2,3,4,5,6]
      ],
      [
        'name' => 'Qtypes 6',
        'summary' => 'accounting, hints, solutions, weird stuff',
        'startdate' =>  -2*24,
        'enddate' => 24*7,
        'reviewdate' => 2000000000,
        'displaymethod' => 'skip',
        'ptsposs' => 25,
        'submitby' => 'by_question',
        'showscores' => 'during',
        'showans' => 'after_lastattempt',
        'defregens' => 3,
        'defregenpenalty' => 0,
        'defpoints' => 5,
        'defattempts' => 2,
        'defpenalty' => 0,
        'ver' => 2,
        'questions' => [
          0=>['questionsetid' => 38],
          1=>['questionsetid' => 39],
          2=>['questionsetid' => 40],
          3=>['questionsetid' => 41],
          4=>['questionsetid' => 42]
        ],
        'itemorder' => [0,1,2,3,4]
      ]
    ]
  ],
  [
    'name' => 'Group',
    'assessments' => [
      [
        'name' => 'Group 1',
        'summary' => 'student-created groups',
        'startdate' =>  -2*24,
        'enddate' => 24*7,
        'reviewdate' => 2000000000,
        'displaymethod' => 'skip',
        'ptsposs' => 15,
        'submitby' => 'by_question',
        'showscores' => 'during',
        'showans' => 'after_lastattempt',
        'defregens' => 3,
        'defregenpenalty' => 0,
        'defpoints' => 5,
        'defattempts' => 2,
        'defpenalty' => 0,
        'isgroup' => 2,
        'groupmax' => 4,
        'groupsetid' => 1,
        'ver' => 2,
        'questions' => [
          0=>['questionsetid' => 5],
          1=>['questionsetid' => 6]
        ],
        'itemorder' => [0,1]
      ],
      [
        'name' => 'Group 2',
        'summary' => 'instructor-set groups',
        'startdate' =>  -2*24,
        'enddate' => 24*7,
        'reviewdate' => 2000000000,
        'displaymethod' => 'skip',
        'ptsposs' => 15,
        'submitby' => 'by_question',
        'showscores' => 'during',
        'showans' => 'after_lastattempt',
        'defregens' => 3,
        'defregenpenalty' => 0,
        'defpoints' => 5,
        'defattempts' => 2,
        'defpenalty' => 0,
        'isgroup' => 3,
        'groupsetid' => 1,
        'ver' => 2,
        'questions' => [
          0=>['questionsetid' => 5],
          1=>['questionsetid' => 6]
        ],
        'itemorder' => [0,1]
      ]
    ]
  ],
  [
    'name' => 'Scores/Answers/Views',
    'assessments' => [
      array_merge($simpleA, [
        'name' => 'HW - Immediate show',
        'summary' => 'During assess, scores should show immediately, answers after last try.<br/>Work and scores should be viewing in GB immediately, and answers after due date.',
        'submitby' => 'by_question',
        'showscores' => 'during',
        'showans' => 'after_lastattempt',
        'viewingb' => 'immediately',
        'scoresingb' => 'immediately',
        'ansingb' => 'after_due'
      ]),
      array_merge($simpleA, [
        'name' => 'HW - Delayed in GB',
        'summary' => 'During assess, scores should show immediately, answers after last try.<br/>Scores should be viewing in GB immediately, But work should only show after due date, and answers should never show.',
        'submitby' => 'by_question',
        'showscores' => 'during',
        'showans' => 'after_lastattempt',
        'viewingb' => 'after_due',
        'scoresingb' => 'immediately',
        'ansingb' => 'never'
      ]),
      array_merge($simpleA, [
        'name' => 'HW - No scores during',
        'summary' => 'During assess, no scores should show during, but multiple tries allowed to change answer.<br/>Work should be reviewable in GB immediately, but scores should not show until after due date.',
        'submitby' => 'by_question',
        'defregens' => 1,
        'showscores' => 'none',
        'showans' => 'never',
        'viewingb' => 'immediately',
        'scoresingb' => 'after_due',
        'ansingb' => 'after_due'
      ]),
      array_merge($simpleA, [
        'name' => 'Quiz - no scores during, in GB after',
        'summary' => 'During assess, no scores show.<br/>Work, scores, and ans should be viewable in GB after a take is submitted.',
        'submitby' => 'by_assessment',
        'defattempts' => 1,
        'showscores' => 'none',
        'showans' => 'never',
        'viewingb' => 'after_take',
        'scoresingb' => 'after_take',
        'ansingb' => 'after_take'
      ]),
      array_merge($simpleA, [
        'name' => 'Quiz - total at end',
        'summary' => 'During assess, only total score shows at end of attempt.<br/>Work should be viewable in GB always; scores after a take is submitted (but only total score until after due date?), answers after due date.',
        'submitby' => 'by_assessment',
        'defattempts' => 1,
        'showscores' => 'total',
        'showans' => 'never',
        'viewingb' => 'immediately',
        'scoresingb' => 'after_take',
        'ansingb' => 'after_due'
      ]),
      array_merge($simpleA, [
        'name' => 'Quiz - scores at end',
        'summary' => 'During assess, scores and ans show at end of attempt.<br/>Work should never be viewable in GB; scores after a take is submitted',
        'submitby' => 'by_assessment',
        'defattempts' => 1,
        'showscores' => 'at_end',
        'showans' => 'after_take',
        'viewingb' => 'never',
        'scoresingb' => 'after_take',
        'ansingb' => 'never'
      ]),
      array_merge($simpleA, [
        'name' => 'Quiz - typical',
        'summary' => 'During assess, scores show on each, ans after last try.<br/>Work and answers are viewable in GB after take; scores immediately (what does this mean?)<br/>Best score is kept',
        'submitby' => 'by_assessment',
        'showscores' => 'during',
        'showans' => 'after_lastattempt',
        'viewingb' => 'after_take',
        'scoresingb' => 'immediately',
        'ansingb' => 'after_take'
      ]),
      array_merge($simpleA, [
        'name' => 'Quiz - keep last',
        'summary' => 'Last score is used for grade',
        'submitby' => 'by_assessment',
        'keepscore' => 'last',
        'showscores' => 'during',
        'showans' => 'after_lastattempt',
        'viewingb' => 'after_take',
        'scoresingb' => 'immediately',
        'ansingb' => 'after_take'
      ]),
      array_merge($simpleA, [
        'name' => 'Quiz - avg',
        'summary' => 'Average score is used for grade',
        'submitby' => 'by_assessment',
        'keepscore' => 'average',
        'showscores' => 'during',
        'showans' => 'after_lastattempt',
        'viewingb' => 'after_take',
        'scoresingb' => 'immediately',
        'ansingb' => 'after_take'
      ])
    ]
  ]
];

$studatarec = array(
  'q01byq' => array(
    'scoreddata' => '{"submissions":[4,7,15,20,29],"autosaves":[],"scored_version":0,"assess_versions":[{"starttime":1553629961,"lastchange":1553629990,"status":0,"score":10,"questions":[{"score":5,"rawscore":1,"scored_version":0,"question_versions":[{"qid":"8037424","seed":5424,"tries":[[{"sub":0,"raw":"0","time":4,"stuans":"5"},{"sub":1,"raw":"1","time":7,"stuans":"67"}]],"answeights":[1]}]},{"score":5,"rawscore":3,"scored_version":1,"question_versions":[{"qid":"8037425","seed":9190,"tries":[[{"sub":2,"raw":"1","time":5,"stuans":"11"},{"sub":3,"raw":"1","time":9,"stuans":"11"}],[{"sub":2,"raw":"1","time":5,"stuans":"35"},{"sub":3,"raw":"1","time":9,"stuans":"35"}],[{"sub":3,"raw":"0","time":9,"stuans":"78"}]],"answeights":[0.333,0.333,0.334]},{"qid":"8037425","seed":7550,"tries":[[{"sub":4,"raw":"1","time":15,"stuans":"28"}],[{"sub":4,"raw":"1","time":15,"stuans":"97"}],[{"sub":4,"raw":"1","time":15,"stuans":"8"}]],"answeights":[0.333,0.333,0.334]}]}]}]}',
    'score' => 10,
    'status' => 0
  ),
  'q01bya' => array(
    'scoreddata' => '{"submissions":[4,12,15,74,81],"autosaves":[],"scored_version":1,"assess_versions":[{"starttime":1553631054,"lastchange":1553631069,"status":1,"score":9.499,"questions":[{"score":5,"rawscore":1,"scored_version":0,"question_versions":[{"qid":"8037429","seed":629,"tries":[[{"sub":0,"raw":"1","time":0,"stuans":"77"}]],"answeights":[1]}]},{"score":4.499,"rawscore":3,"scored_version":0,"question_versions":[{"qid":"8037430","seed":5417,"tries":[[{"sub":1,"raw":"1","time":0,"stuans":"39"},{"sub":2,"raw":"1","time":0,"stuans":"39"}],[{"sub":1,"raw":"1","time":0,"stuans":"87"},{"sub":2,"raw":"1","time":0,"stuans":"87"}],[{"sub":1,"raw":"0","time":0,"stuans":"3"},{"sub":2,"raw":"1","time":0,"stuans":"46"}]],"answeights":[0.333,0.333,0.334]}]}]},{"starttime":1553631124,"lastchange":1553631135,"status":1,"score":10,"questions":[{"score":5,"rawscore":1,"scored_version":0,"question_versions":[{"qid":"8037429","seed":4826,"tries":[[{"sub":3,"raw":"1","time":0,"stuans":"28"}]],"answeights":[1]}]},{"score":5,"rawscore":3,"scored_version":0,"question_versions":[{"qid":"8037430","seed":6369,"tries":[[{"sub":4,"raw":"1","time":0,"stuans":"68"}],[{"sub":4,"raw":"1","time":0,"stuans":"29"}],[{"sub":4,"raw":"1","time":0,"stuans":"97"}]],"answeights":[0.333,0.333,0.334]}]}]}]}',
    'score' => 10,
    'status' => 0
  ),
  'hw1' => array(
    'scoreddata' => '{"submissions":[6,10,21,34,41,45,55,58,63],"autosaves":[],"scored_version":0,"assess_versions":[{"starttime":1560052898,"lastchange":1560052961,"status":0,"score":18.3,"questions":[{"score":5,"rawscore":1,"scored_version":0,"question_versions":[{"qid":"!Q0!","seed":7069,"tries":[[{"sub":0,"time":6,"stuans":"66","stuansval":"","raw":0},{"sub":1,"time":10,"stuans":"77","stuansval":"","raw":1}]],"answeights":[1],"scored_try":[1]}],"time":16},{"score":3.33,"rawscore":0.6667,"scored_version":0,"question_versions":[{"qid":"!Q1!","seed":7976,"tries":{"0":[{"sub":2,"time":10,"stuans":"39","stuansval":"","raw":1}],"2":[{"sub":2,"time":10,"stuans":"71","stuansval":"","raw":1}]},"answeights":[1,1,1],"scored_try":[0,-1,0]}],"time":10},{"score":5,"rawscore":1,"scored_version":0,"question_versions":[{"qid":"!Q2!","seed":3888,"tries":[[{"sub":3,"time":2,"stuans":"33","stuansval":"","raw":0},{"sub":4,"time":9,"stuans":"76","stuansval":"","raw":1}]],"answeights":[1],"scored_try":[1]},{"qid":"!Q2!","seed":1631,"tries":[[{"sub":5,"time":12,"stuans":"33","stuansval":"","raw":0}]],"answeights":[1]}],"time":23},{"score":5,"rawscore":1,"scored_version":0,"question_versions":[{"qid":"!Q3!","seed":4534,"tries":[[{"sub":6,"time":6,"stuans":"21","stuansval":"","raw":0},{"sub":7,"time":8,"stuans":"30","stuansval":"","raw":1}],[{"sub":6,"time":6,"stuans":"33","stuansval":"","raw":0},{"sub":8,"time":14,"stuans":"36","stuansval":"","raw":1}],[{"sub":6,"time":6,"stuans":"54","stuansval":"","raw":0},{"sub":8,"time":14,"stuans":"39","stuansval":"","raw":1}]],"answeights":[1,1,1],"scored_try":[1,1,1]}],"time":28}]}]}',
    'score' => 18.30,
    'status' => 16,
    'timeontask' => 77
  ),
  'q1' => array(
    'scoreddata' => '{"submissions":[3,8,11,16,92,97,100,107,113,122,128],"autosaves":[],"scored_version":1,"assess_versions":[{"starttime":1560052989,"lastchange":1560053005,"status":1,"score":11.7,"questions":[{"score":5,"rawscore":1,"scored_version":0,"question_versions":[{"qid":"!Q0!","seed":115,"tries":[[{"sub":0,"time":2,"stuans":"5","stuansval":"","raw":1}]],"answeights":[1],"scored_try":[0]}],"time":2},{"score":0,"rawscore":0,"scored_version":0,"question_versions":[{"qid":"!Q1!","seed":1505,"tries":[],"answeights":[1,1,1],"scored_try":[-1,-1,-1]}],"time":0},{"score":5,"rawscore":1,"scored_version":0,"question_versions":[{"qid":"!Q2!","seed":535,"tries":[[{"sub":1,"time":3,"stuans":"33","stuansval":"","raw":0},{"sub":2,"time":6,"stuans":"39","stuansval":"","raw":1}]],"answeights":[1],"scored_try":[1]}],"time":9},{"score":1.67,"rawscore":0.3333,"scored_version":0,"question_versions":[{"qid":"!Q3!","seed":6873,"tries":[[{"sub":3,"time":4,"stuans":"29","stuansval":"","raw":1}]],"answeights":[1,1,1],"scored_try":[0,-1,-1]}],"time":4}]},{"starttime":1560053075,"lastchange":1560053102,"status":1,"score":11.7,"questions":[{"score":5,"rawscore":1,"scored_version":0,"question_versions":[{"qid":"!Q0!","seed":4942,"tries":[[{"sub":4,"time":5,"stuans":"3","stuansval":"","raw":1}]],"answeights":[1],"scored_try":[0]}],"time":5},{"score":5,"rawscore":1,"scored_version":0,"question_versions":[{"qid":"!Q1!","seed":5228,"tries":[[{"sub":5,"time":5,"stuans":"91","stuansval":"","raw":1}],[{"sub":5,"time":5,"stuans":"36","stuansval":"","raw":1}],[{"sub":6,"time":7,"stuans":"91","stuansval":"","raw":1}]],"answeights":[1,1,1],"scored_try":[0,0,0]}],"time":12},{"score":0,"rawscore":0,"scored_version":0,"question_versions":[{"qid":"!Q2!","seed":3777,"tries":[[{"sub":7,"time":6,"stuans":"83","stuansval":"","raw":0}]],"answeights":[1],"scored_try":[0]}],"time":6},{"score":1.67,"rawscore":0.3333,"scored_version":0,"question_versions":[{"qid":"!Q3!","seed":4295,"tries":[[{"sub":8,"time":5,"stuans":"28","stuansval":"","raw":1}],[{"sub":8,"time":5,"stuans":"31","stuansval":"","raw":0}],[{"sub":8,"time":5,"stuans":"51","stuansval":"","raw":0}]],"answeights":[1,1,1],"scored_try":[0,0,0]}],"time":5}]},{"starttime":1560053108,"lastchange":1560053117,"status":0,"score":6.7,"questions":[{"score":5,"rawscore":1,"scored_version":0,"question_versions":[{"qid":"!Q0!","seed":3676,"tries":[[{"sub":9,"time":3,"stuans":"30","stuansval":"","raw":1}]],"answeights":[1],"scored_try":[0]}],"time":3},{"score":1.67,"rawscore":0.3333,"scored_version":0,"question_versions":[{"qid":"!Q1!","seed":8281,"tries":[[{"sub":10,"time":4,"stuans":"41","stuansval":"","raw":1}],[{"sub":10,"time":4,"stuans":"","stuansval":"","raw":0}],[{"sub":10,"time":4,"stuans":"","stuansval":"","raw":0}]],"answeights":[1,1,1],"scored_try":[0,0,0]}],"time":4},{"score":0,"rawscore":0,"scored_version":0,"question_versions":[{"qid":"!Q2!","seed":7450,"tries":[]}]},{"score":0,"rawscore":0,"scored_version":0,"question_versions":[{"qid":"!Q3!","seed":7646,"tries":[]}]}]}]}',
    'score' => 11.70,
    'status' => 65,
    'timeontask' => 50
  ),
  'p1' => array(
    'scoreddata' => '{"submissions":[3,7,22,32,39,41,44],"autosaves":[],"scored_version":0,"assess_versions":[{"starttime":1560055857,"lastchange":1560055901,"status":0,"score":12.5,"questions":[{"score":4.5,"rawscore":1,"scored_version":0,"question_versions":[{"qid":"!Q0!","seed":7075,"tries":[[{"sub":0,"time":4,"stuans":"26","stuansval":"","raw":0},{"sub":1,"time":7,"stuans":"30","stuansval":"","raw":1}]],"answeights":[1],"scored_try":[1]}],"time":11},{"score":4,"rawscore":1,"scored_version":1,"question_versions":[{"qid":"!Q1!","seed":9329,"tries":[[{"sub":2,"time":12,"stuans":"42","stuansval":"","raw":0}],[{"sub":2,"time":12,"stuans":"15","stuansval":"","raw":1}],[{"sub":2,"time":12,"stuans":"87","stuansval":"","raw":1}]],"answeights":[1,1,1],"scored_try":[0,0,0]},{"qid":"!Q1!","seed":3755,"tries":[[{"sub":3,"time":18,"stuans":"58","stuansval":"","raw":1}],[{"sub":3,"time":18,"stuans":"8","stuansval":"","raw":1}],[{"sub":3,"time":18,"stuans":"34","stuansval":"","raw":1}]],"answeights":[1,1,1],"scored_try":[0,0,0]}],"time":30},{"score":4,"rawscore":1,"scored_version":0,"question_versions":[{"qid":"!Q2!","seed":6600,"tries":[[{"sub":4,"time":5,"stuans":"33","stuansval":"","raw":0},{"sub":5,"time":7,"stuans":"22","stuansval":"","raw":0},{"sub":6,"time":9,"stuans":"57","stuansval":"","raw":1}]],"answeights":[1],"scored_try":[2]}],"time":21}]}]}',
    'score' => 12.50,
    'status' => 0,
    'timeontask' => 62
  )
);
