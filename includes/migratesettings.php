<?php
// IMathAS: Assessment settings migration
// (c) 2019 David Lippman

require(__DIR__ . '/convertintro.php');

function migrateAssessSettings($settings, $oldUIver, $newUIver) {
  if ($oldUIver == 1 && $newUIver == 2) {
    return migrateAssessSettings1to2($settings);
  } else {
    return false;
  }
}

function migrateQuestionSettings($settings, $defaults, $oldUIver, $newUIver) {
  if ($oldUIver == 1 && $newUIver == 2) {
    return migrateQuestionSettings1to2($settings, $defaults);
  } else {
    return false;
  }
}

function migrateAssessSettings1to2($settings) {
  // map deffedback, showans to submitby, showscores, showans, regens
  list($testtype,$showans) = explode('-', $settings['deffeedback']);
  $reattDiffVer = ($settings['shuffle']&8)==8;

  // map testtype and showans to submitby, showscores, showans
  $settings['deffeedback'] = '';
  if ($testtype == "Homework" || $testtype == "Practice") {
    $settings['submitby'] = 'by_question';
    $settings['defregens'] = 100;
    $settings['defregenpenalty'] = 0;
    $settings['showscores'] = 'during';
    if ($showans == 'V' || $showans == 'N') {
      $settings['showans'] = 'never';
    } else if ($showans == 'F' || $showans == 'R') {
      $settings['showans'] = 'after_lastattempt';
    } else if ($showans == 'J') {
      $settings['showans'] = 'jump_to_answer';
    } else if ($showans == 'I' || $showans == '0') {
      $settings['showans'] = 'after_1';
    } else if (is_numeric($showans)) {
      $settings['showans'] = 'after_'.$showans;
    }
  } else if ($testtype == "NoScores") {
    if ($reattDiffVer) {
      $settings['submitby'] = 'by_assessment';
      $settings['keepscore'] = 'last';
      $settings['defregens'] = $settings['defattempts'];
      $settings['defregenpenalty'] = getBasePenalty($settings['defpenalty']);
      $settings['defpenalty'] = '0';
      $settings['defattempts'] = 1;
    } else {
      $settings['submitby'] = 'by_question';
      $settings['defregens'] = 1;
    }
    $settings['showscores'] = 'never';
    $settings['showans'] = 'never';
  } else if ($testtype == "EndScore") {
    $settings['submitby'] = 'by_assessment';
    $settings['keepscore'] = 'best';
    $settings['defregens'] = $settings['defattempts'];
    $settings['defregenpenalty'] = getBasePenalty($settings['defpenalty']);
    $settings['defpenalty'] = '0';
    $settings['defattempts'] = 1;
    $settings['showscores'] = 'total';
    $settings['showans'] = 'never';
  } else if ($testtype == "EachAtEnd" ||
      $testtype == "EndReview" ||
      $testtype == "EndReviewWholeTest"
  ) {
    $settings['submitby'] = 'by_assessment';
    $settings['keepscore'] = 'best';
    $settings['defregens'] = $settings['defattempts'];
    $settings['defregenpenalty'] = getBasePenalty($settings['defpenalty']);
    $settings['defpenalty'] = '0';
    $settings['defattempts'] = 1;
    $settings['showscores'] = 'at_end';
    if ($showans == 'V' || $showans == 'N' || $showans == 'A') {
      $settings['showans'] = 'never';
    } else {
      $settings['showans'] = 'after_take';
    }
  } else if ($testtype == "AsGo") {
    $settings['showscores'] = 'during';
    if ($reattDiffVer) {
      $settings['submitby'] = 'by_assessment';
      $settings['keepscore'] = 'best';
      $settings['defregens'] = $settings['defattempts'];
      $settings['defregenpenalty'] = getBasePenalty($settings['defpenalty']);
      $settings['defpenalty'] = '0';
      $settings['defattempts'] = 1;
    } else {
      $settings['submitby'] = 'by_question';
      $settings['defregens'] = 1;
      if ($showans == 'V' || $showans == 'N' || $showans == 'A') {
        $settings['showans'] = 'never';
      } else if ($showans == 'F' || $showans == 'R') {
        $settings['showans'] = 'after_lastattempt';
      } else if ($showans == 'J') {
        $settings['showans'] = 'jump_to_answer';
      } else if ($showans == 'I') {
        $settings['showans'] = 'after_1';
      } else if (is_numeric($showans)) {
        $settings['showans'] = 'after_'.$showans;
      }
    }
  }

  // map showans to viewingb, scoresingb, ansingb
  if ($showans == 'V') { // Never, but allow students to review
    $settings['viewingb'] = 'immediately';
    $settings['scoresingb'] = 'never';
    $settings['ansingb'] = 'never';
  } else if ($showans == 'N') { // Never, and don't allow students to review
    $settings['viewingb'] = 'never';
    $settings['scoresingb'] = 'never';
    $settings['ansingb'] = 'never';
  } else if ($showans == 'A') { // After the due date
    $settings['viewingb'] = 'after_due';
    $settings['scoresingb'] = 'after_due';
    $settings['ansingb'] = 'after_due';
  } else { // All others
    if ($settings['submitby'] == 'by_question') {
      $settings['viewingb'] = 'immediately';
      $settings['scoresingb'] = 'immediately';
      $settings['ansingb'] = 'after_due';
    } else {
      $settings['viewingb'] = 'immediately';
      $settings['scoresingb'] = 'after_take';
      $settings['ansingb'] = 'after_take';
    }
  }
  if ($testtype == "EndScore" && ($showans != 'V' && $showans != 'N')) {
    // override above - doesn't make sense to show detailed score
    // until after due date in this mode
    $settings['viewingb'] = 'after_due';
    $settings['ansingb'] = 'after_due';
  }
  // fix possible invalid settings
  if ($settings['showscores'] == 'during') {
    if ($settings['submitby'] == 'by_question') {
      $settings['scoresingb'] = 'immediately';
    } else {
      $settings['scoresingb'] = 'after_take';
    }
  }

  // remap displaymethod
  if ($settings['displaymethod'] == 'AllAtOnce' ||
    $settings['displaymethod'] == 'Seq' ||
    $settings['displaymethod'] == 'Embed'
  ) {
    $settings['displaymethod'] = 'full';
  } else if ($settings['displaymethod'] == 'OneByOne' ||
    $settings['displaymethod'] == 'SkipAround'
  ) {
    $settings['displaymethod'] = 'skip';
  } else if ($settings['displaymethod'] == 'VideoCue') {
    $settings['displaymethod'] = 'video_cued';
  } else if ($settings['displaymethod'] == 'LivePoll') {
    $settings['displaymethod'] = 'livepoll';
  }

  // can't do unlimited tries anymore
  if ($settings['defattempts'] == 0) {
    $settings['defattempts'] = 100;
  }
  // can't do unlimited regens anymore
  if ($settings['defregens'] == 0) {
    $settings['defregens'] = 100;
  }

  // convert 'after n missed attempts' or 'on last attempt' penalties
  if ($settings['defpenalty'] === '') {
    $settings['defpenalty'] = '0';
  }
  if ($settings['defpenalty'][0] == 'S') {
    $after = $settings['defpenalty'][1] + 1;
    if ($after < $settings['defattempts']) {
      $settings['defpenalty'] = 'S'.$after.substr($settings['defpenalty'],2);
    } else {
      $settings['defpenalty'] = substr($settings['defpenalty'],2);
    }
  } else if ($settings['defpenalty'][0] == 'L') {
    if ($settings['defattempts'] > 1 && $settings['defattempts'] < 11) {
      $after = $settings['defattempts'] - 1;
      $settings['defpenalty'] = 'S'.$after.substr($settings['defpenalty'],1);
    } else {
      $settings['defpenalty'] = substr($settings['defpenalty'],1);
    }
  }

  // always show categories
  $settings['showcat'] = 1;

  // handle "all items same random seed"
  if (($settings['shuffle']&2)==2 && $settings['submitby'] == 'by_question') {
    // turn off
    // $settings['shuffle'] = ($settings['shuffle'] & ~2);

    // set defregens to 1 so it will work
    $settings['defregens'] = 1;
  }

  // turn off "reattempts diff versions" bit
  $settings['shuffle'] = ($settings['shuffle'] & ~8);

  // convert showhints
  if ($settings['showhints'] > 0) {
    $settings['showhints'] = 7;
  }

  // convert showtips
  if ($settings['showtips'] == 1) {
    $settings['showtips'] = 2;
  }

  // always use eqn editor
  $settings['eqnhelper'] = 2;

  if ($settings['isgroup'] == 1) {
    $settings['isgroup'] = 2;
  }

  // handle overtime timelimit
  if ($settings['timelimit'] > 0) {
    $settings['overtime_grace'] = min($settings['timelimit'], 300);
    $settings['overtime_penalty'] = 0;
  }

  // convert intro to new format, if needed
  $newintrojson = convertintro($settings['intro']);
  if ($newintrojson !== false) {
    $settings['intro'] = json_encode($newintrojson[0], JSON_INVALID_UTF8_IGNORE);
  }

  $settings['ver'] = 2;

  return $settings;
}

function getBasePenalty($pen) {
  $pen = (string) $pen;
  if ($pen === '') { $pen = '0'; }
  if ($pen[0]=='S') {
    return substr($pen,2);
  } else if ($pen[0]=='L') {
    return substr($pen,1);
  } else {
    return $pen;
  }
}

function migrateQuestionSettings1to2($settings, $defaults) {
  // can't handle unlimited tries
  if ($settings['attempts'] == 0) {
    $settings['attempts'] = 100;
  }

  // convert 'after n missed attempts' or 'on last attempt' penalties
  if ($settings['attempts'] == 9999) {
    $refattempts = $defaults['defattempts'];
  } else {
    $refattempts = $settings['attempts'];
  }
  if ($settings['penalty'] === '') { $settings['penalty'] = '0'; }
  if ($settings['penalty'][0] == 'S') {
    $after = $settings['penalty'][1] + 1;
    if ($after < $refattempts) {
      $settings['penalty'] = 'S'.$after.substr($settings['penalty'],2);
    } else {
      $settings['penalty'] = substr($settings['penalty'],2);
    }
  } else if ($settings['penalty'][0] == 'L') {
    if ($refattempts > 1 && $refattempts < 11) {
      $after = $refattempts - 1;
      $settings['penalty'] = 'S'.$after.substr($settings['penalty'],1);
    } else {
      $settings['penalty'] = substr($settings['penalty'],1);
    }
  }

  // convert regen
  if ($settings['regen'] < 3) {
    $settings['regen'] = 0;
  } else {
    $settings['regen'] = 1;
  }

  // convert 'show answers'
  if ($settings['showans'] != 'N') {
    $settings['showans'] = 0;
  }

  // convert showhints
  if ($settings['showhints'] == 0) {
    $settings['showhints'] = -1;
  } else if ($settings['showhints'] == 1) {
    $settings['showhints'] = 0;
  } else {
    $settings['showhints'] = 3;
  }

  return $settings;
}
