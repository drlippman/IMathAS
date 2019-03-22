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
        'ptsposs' => 5,
        'ver' => 2,
        'questions' => [
          0=>['questionsetid' => 0],
        ],
        'itemorder' => [0]
      ],
      [
        'name' => 'Closed 6',
        'summary' => 'past due, practice, latepasses allowed',
        'startdate' =>  -7*24,
        'enddate' => -1.5*24,
        'reviewdate' => 2000000000,
        'allowlate' => 11,
        'ptsposs' => 5,
        'ver' => 2,
        'questions' => [
          0=>['questionsetid' => 0],
        ],
        'itemorder' => [0]
      ],
      [
        'name' => 'Closed 7',
        'summary' => 'prereq of 5pts on Closed 6',
        'startdate' =>  -7*24,
        'enddate' => 2*24,
        'reviewdate' => 2000000000,
        'reqscoreaid' => -1,
        'reqscore' => 5,
        'reqscoretype' => 1,
        'ptsposs' => 5,
        'ver' => 2,
        'questions' => [
          0=>['questionsetid' => 0],
        ],
        'itemorder' => [0]
      ]
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
