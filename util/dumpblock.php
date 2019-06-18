<?php

require("../init.php");
if ($myrights < 100) {
  exit;
}
if (!isset($_GET['cid']) || !isset($_GET['folder'])) {
  echo 'Call with cid= and folder=. You can get these by isolating the desired block and looking at the URL';
  exit;
}

$block = $_GET['folder'];

$out = array();

$stm = $DBH->prepare("SELECT itemorder FROM imas_courses WHERE id=:id");
$stm->execute(array(':id'=>$cid));
$items = unserialize($stm->fetchColumn(0));
$blocktree = explode('-',$block);
$sub = $items;
$blockObject = $items;
for ($i=1;$i<count($blocktree);$i++) {
  $blockObject = $sub[$blocktree[$i]-1]; //-1 to adjust for 1-indexing
  $sub = $blockObject['items'];
}

$out['blockobject'] = $blockObject;

$itemIds = array();
function getItemIds($items, &$itemIds) {
  foreach ($items as $item) {
    if (is_array($item)) {
      getItemIds($item['items'], $itemIds);
    } else {
      $itemIds[] = $item;
    }
  }
}
getItemIds($sub, $itemIds);


if (count($itemIds)==0) {
  echo "No items";
  exit;
}

$itemlist = implode(',', $itemIds);
$stm = $DBH->query("SELECT * FROM imas_items WHERE id IN ($itemlist)");
$typelists = array();
while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
  if (!isset($out['items'])) {
    $out['items'] = array(
      'fields' => array_keys($row),
      'values' => array()
    );
  }
  $out['items']['values'][] = array_values($row);
  if (!isset($typelist[$row['itemtype']])) {
    $typelist[$row['itemtype']] = array();
  }
  $typelist[$row['itemtype']][] = $row['typeid'];
}

// TODO:
// Need to still grab and restore:
//  imas_forum_posts
//  imas_forum_threads
//  imas_forum_views
//  imas_grades (for forum posts)
//  imas_wiki_revisions
//  imas_wiki_views
//
// Probably can't recover:
//  imas_instr_files (since file will have been deleted)

if (isset($typelist['Assessment'])) {
  $idlist = implode(',', $typelist['Assessment']);
  $stm = $DBH->query("SELECT * FROM imas_assessments WHERE id IN ($idlist)");
  while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
    if (!isset($out['assessments'])) {
      $out['assessments'] = array(
        'fields' => array_keys($row),
        'values' => array()
      );
    }
    $out['assessments']['values'][] = array_values($row);
  }
  $stm = $DBH->query("SELECT * FROM imas_questions WHERE assessmentid IN ($idlist)");
  while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
    if (!isset($out['questions'])) {
      $out['questions'] = array(
        'fields' => array_keys($row),
        'values' => array()
      );
    }
    $out['assessment_sessions']['values'][] = array_values($row);
  }
  $stm = $DBH->query("SELECT * FROM imas_assessment_sessions WHERE assessmentid IN ($idlist)");
  while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
    if (!isset($out['assessment_sessions'])) {
      $out['assessment_sessions'] = array(
        'fields' => array_keys($row),
        'values' => array()
      );
    }
    $out['assessment_sessions']['values'][] = array_values($row);
  }
  $stm = $DBH->query("SELECT * FROM imas_exceptions WHERE assessmentid IN ($idlist) AND itemtype='A'");
  while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
    if (!isset($out['exceptions'])) {
      $out['exceptions'] = array(
        'fields' => array_keys($row),
        'values' => array()
      );
    }
    $out['exceptions']['values'][] = array_values($row);
  }
}

if (isset($typelist['InlineText'])) {
  $idlist = implode(',', $typelist['InlineText']);
  $stm = $DBH->query("SELECT * FROM imas_inlinetext WHERE id IN ($idlist)");
  while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
    if (!isset($out['inlinetext'])) {
      $out['inlinetext'] = array(
        'fields' => array_keys($row),
        'values' => array()
      );
    }
    $out['inlinetext']['values'][] = array_values($row);
  }
}

if (isset($typelist['LinkedText'])) {
  $idlist = implode(',', $typelist['LinkedText']);
  $stm = $DBH->query("SELECT * FROM imas_linkedtext WHERE id IN ($idlist)");
  while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
    if (!isset($out['linkedtext'])) {
      $out['linkedtext'] = array(
        'fields' => array_keys($row),
        'values' => array()
      );
    }
    $out['linkedtext']['values'][] = array_values($row);
  }
}

if (isset($typelist['Forum'])) {
  $idlist = implode(',', $typelist['Forum']);
  $stm = $DBH->query("SELECT * FROM imas_forums WHERE id IN ($idlist)");
  while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
    if (!isset($out['forums'])) {
      $out['forums'] = array(
        'fields' => array_keys($row),
        'values' => array()
      );
    }
    $out['forums']['values'][] = array_values($row);
  }
}

if (isset($typelist['Wiki'])) {
  $idlist = implode(',', $typelist['Wiki']);
  $stm = $DBH->query("SELECT * FROM imas_wikis WHERE id IN ($idlist)");
  while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
    if (!isset($out['wiki'])) {
      $out['wikis'] = array(
        'fields' => array_keys($row),
        'values' => array()
      );
    }
    $out['wikis']['values'][] = array_values($row);
  }
}

if (isset($typelist['Drill'])) {
  $idlist = implode(',', $typelist['Drill']);
  $stm = $DBH->query("SELECT * FROM imas_drillassess WHERE id IN ($idlist)");
  while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
    if (!isset($out['drillassess'])) {
      $out['drillassess'] = array(
        'fields' => array_keys($row),
        'values' => array()
      );
    }
    $out['drillassess']['values'][] = array_values($row);
  }

  $stm = $DBH->query("SELECT * FROM imas_drillassess_sessions WHERE drillassessid IN ($idlist)");
  while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
    if (!isset($out['drillassess_sessions'])) {
      $out['drillassess_sessions'] = array(
        'fields' => array_keys($row),
        'values' => array()
      );
    }
    $out['drillassess_sessions']['values'][] = array_values($row);
  }
}

header('Content-type: text/imas');
header("Content-Disposition: attachment; filename=\"imasblockdump-$cid.imas\"");

echo json_encode($out, JSON_HEX_QUOT|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS);
exit;
