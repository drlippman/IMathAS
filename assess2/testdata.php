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
  ]
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
          1=>['questionsetid' => 1],
          2=>['questionsetid' => 0]
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
      ]
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
        'itemorder' => [0,1,2]
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
      ]
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
      ]
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
  )
);
