<?php
//IMathAS:  Copy One Course Item to another course (modal contents + ajax handlers)

/*** master php includes *******/
require_once "../init.php";
require_once "../includes/copyiteminc.php";
require_once "../includes/htmlutil.php";

$cid = Sanitize::courseId($_GET['cid']);

if (!isset($teacherid)) {
    echo "You need to log in as a teacher to access this page";
    exit;
}

$itemid = intval($_GET['item'] ?? 0);
$srccid = $cid;

// verify the item is a copyable item in this course
$stm = $DBH->prepare("SELECT itemtype FROM imas_items WHERE id=? AND courseid=?");
$stm->execute([$itemid, $srccid]);
$itemtype = $stm->fetchColumn(0);
if ($itemtype === false || $itemtype == 'Calendar') {
    echo "Invalid item";
    exit;
}

// verify the user teaches the given destination course; returns itemorder, etc. or false
function getDestCourse($destcid) {
    global $DBH, $userid;
    $stm = $DBH->prepare("SELECT ic.id,ic.itemorder,ic.blockcnt,ic.dates_by_lti,ic.UIver FROM imas_courses AS ic JOIN imas_teachers AS it ON it.courseid=ic.id WHERE ic.id=? AND it.userid=?");
    $stm->execute([intval($destcid), $userid]);
    return $stm->fetch(PDO::FETCH_ASSOC);
}

// build a flat list of blocks: [path, name, depth]
function listBlocks($items, $parent, $depth, &$out) {
    foreach ($items as $k => $item) {
        if (is_array($item)) {
            $path = $parent . '-' . ($k + 1);
            $out[] = ['path' => $path, 'name' => $item['name'], 'depth' => $depth];
            if (!empty($item['items'])) {
                listBlocks($item['items'], $path, $depth + 1, $out);
            }
        }
    }
}

if (isset($_GET['getblocks'])) {
    header('Content-Type: application/json');
    $dest = getDestCourse($_GET['getblocks']);
    if ($dest === false) {
        echo json_encode(['error' => _('Invalid course')]);
        exit;
    }
    $items = unserialize($dest['itemorder']);
    $blocks = [];
    if (is_array($items)) {
        listBlocks($items, '0', 0, $blocks);
    }
    echo json_encode(['blocks' => $blocks], JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

// list the items directly in the block at $path of the destination course, as [id, name]
// where id is the imas_items id, or 'B'.block id for sub-blocks (as in moveitem.php)
if (isset($_GET['getitems'])) {
    header('Content-Type: application/json');
    $dest = getDestCourse($_GET['getitems']);
    $path = $_GET['block'] ?? 'none';
    if ($dest === false || ($path !== 'none' && !preg_match('/^0(-[0-9]+)+$/', $path))) {
        echo json_encode(['error' => _('Invalid course')]);
        exit;
    }
    $sub = unserialize($dest['itemorder']);
    if (!is_array($sub)) {
        $sub = [];
    }
    if ($path !== 'none') {
        $blocktree = explode('-', $path);
        for ($i = 1; $i < count($blocktree); $i++) {
            if (!isset($sub[$blocktree[$i] - 1]) || !is_array($sub[$blocktree[$i] - 1])) {
                echo json_encode(['error' => _('Invalid location')]);
                exit;
            }
            $sub = $sub[$blocktree[$i] - 1]['items'];
        }
    }
    $destcid = $dest['id'];
    // look up names only for the items in this block
    $itemids = array_values(array_filter($sub, function ($v) { return !is_array($v); }));
    $iteminfo = [];
    if (count($itemids) > 0) {
        $ph = Sanitize::generateQueryPlaceholders($itemids);
        $query = "SELECT ii.id,ii.itemtype,COALESCE(ia.name,iit.title,il.title,f.name,w.name,d.name) FROM imas_items AS ii ";
        $query .= "LEFT JOIN imas_assessments AS ia ON ii.itemtype='Assessment' AND ia.id=ii.typeid ";
        $query .= "LEFT JOIN imas_inlinetext AS iit ON ii.itemtype='InlineText' AND iit.id=ii.typeid ";
        $query .= "LEFT JOIN imas_linkedtext AS il ON ii.itemtype='LinkedText' AND il.id=ii.typeid ";
        $query .= "LEFT JOIN imas_forums AS f ON ii.itemtype='Forum' AND f.id=ii.typeid ";
        $query .= "LEFT JOIN imas_wikis AS w ON ii.itemtype='Wiki' AND w.id=ii.typeid ";
        $query .= "LEFT JOIN imas_drillassess AS d ON ii.itemtype='Drill' AND d.id=ii.typeid ";
        $query .= "WHERE ii.courseid=? AND ii.id IN ($ph)";
        $stm = $DBH->prepare($query);
        $stm->execute(array_merge([$destcid], array_map('intval', $itemids)));
        while ($row = $stm->fetch(PDO::FETCH_NUM)) {
            if ($row[1] == 'Calendar') {
                $iteminfo[$row[0]] = _('Calendar');
            } else if ($row[2] !== null) {
                $iteminfo[$row[0]] = $row[2];
            }
        }
    }
    $out = [];
    foreach ($sub as $item) {
        if (is_array($item)) {
            $out[] = ['id' => 'B' . $item['id'], 'name' => $item['name']];
        } else if (isset($iteminfo[$item])) {
            $out[] = ['id' => (string) $item, 'name' => $iteminfo[$item]];
        }
    }
    echo json_encode(['items' => $out], JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

if (isset($_POST['destcid'])) {
    header('Content-Type: application/json');
    $dest = getDestCourse($_POST['destcid']);
    if ($dest === false) {
        echo json_encode(['error' => _('Invalid course')]);
        exit;
    }
    $addto = $_POST['addto'] ?? 'none';
    if ($addto !== 'none' && !preg_match('/^0(-[0-9]+)+$/', $addto)) {
        echo json_encode(['error' => _('Invalid location')]);
        exit;
    }
    $addwhere = $_POST['addwhere'] ?? 'end';
    if (!in_array($addwhere, ['start', 'end', 'after'])) {
        $addwhere = 'end';
    }
    $afteritem = $_POST['afteritem'] ?? '';
    if ($addwhere == 'after' && $afteritem !== '' && !preg_match('/^B?[0-9]+$/', $afteritem)) {
        echo json_encode(['error' => _('Invalid item')]);
        exit;
    }

    $destcid = $dest['id'];
    $samecourse = ($destcid == $srccid);
    $cid = $destcid; // copyitem() and friends copy into global $cid
    $sourcecid = $srccid;
    $_POST['append'] = $samecourse ? ' (Copy)' : '';
    $datesbylti = $dest['dates_by_lti'];
    $convertAssessVer = $dest['UIver'];

    $DBH->beginTransaction();

    // map gradebook categories and outcomes by name to existing ones in the destination
    $gbcats = [];
    $stm = $DBH->prepare("SELECT tc.id,toc.id FROM imas_gbcats AS tc JOIN imas_gbcats AS toc ON tc.name=toc.name WHERE tc.courseid=:courseid AND toc.courseid=:courseid2");
    $stm->execute([':courseid' => $srccid, ':courseid2' => $destcid]);
    while ($row = $stm->fetch(PDO::FETCH_NUM)) {
        $gbcats[$row[0]] = $row[1];
    }
    $outcomes = [];
    $stm = $DBH->prepare("SELECT tc.id,toc.id FROM imas_outcomes AS tc JOIN imas_outcomes AS toc ON tc.name=toc.name WHERE tc.courseid=:courseid AND toc.courseid=:courseid2");
    $stm->execute([':courseid' => $srccid, ':courseid2' => $destcid]);
    while ($row = $stm->fetch(PDO::FETCH_NUM)) {
        $outcomes[$row[0]] = $row[1];
    }

    if (!$samecourse) {
        prepopulate_forumtrack($srccid, $destcid);
    }

    $newitem = copyitem($itemid, $gbcats);
    if ($newitem === false) {
        $DBH->rollBack();
        echo json_encode(['error' => _('Unable to copy item')]);
        exit;
    }
    $newitems = [$newitem];
    doaftercopy($srccid, $newitems);

    // reload itemorder, since the copy may have modified it
    $stm = $DBH->prepare("SELECT itemorder FROM imas_courses WHERE id=:id");
    $stm->execute([':id' => $destcid]);
    $items = unserialize($stm->fetchColumn(0));
    if (!is_array($items)) {
        $items = [];
    }
    $sub =& $items;
    if ($addto !== 'none') {
        $blocktree = explode('-', $addto);
        for ($i = 1; $i < count($blocktree); $i++) {
            if (!isset($sub[$blocktree[$i] - 1]) || !is_array($sub[$blocktree[$i] - 1])) {
                $DBH->rollBack();
                echo json_encode(['error' => _('Invalid location')]);
                exit;
            }
            $sub =& $sub[$blocktree[$i] - 1]['items'];
        }
    }
    $pos = count($sub); // end, or fallback if the "after" item can't be found
    if ($addwhere == 'start') {
        $pos = 0;
    } else if ($addwhere == 'after') {
        foreach ($sub as $k => $blockitem) {
            if (is_array($blockitem) ? ('B' . $blockitem['id'] == $afteritem) : ($blockitem == $afteritem)) {
                $pos = $k + 1;
                break;
            }
        }
    }
    array_splice($sub, $pos, 0, $newitems);
    unset($sub);

    $stm = $DBH->prepare("UPDATE imas_courses SET itemorder=:itemorder WHERE id=:id");
    $stm->execute([':itemorder' => serialize($items), ':id' => $destcid]);
    copyrubrics();
    $DBH->commit();

    echo json_encode(['success' => true]);
    exit;
}

// Page output: course list, in the user's course list order
$stm = $DBH->prepare("SELECT jsondata FROM imas_users WHERE id=?");
$stm->execute([$userid]);
$userjson = json_decode($stm->fetchColumn(0), true);

$stm = $DBH->prepare("SELECT ic.id,ic.name,it.hidefromcourselist FROM imas_courses AS ic JOIN imas_teachers AS it ON it.courseid=ic.id WHERE it.userid=? AND ic.available<4 AND ic.id<>? ORDER BY ic.name");
$stm->execute([$userid, $srccid]);
$myCourses = [];
while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
    if (!$row['hidefromcourselist']) {
        $myCourses[$row['id']] = $row;
    }
}
$printed = [];
// course folders are shown as disabled options, with nesting shown by indenting
function printCourseOrder($order, $myCourses, &$printed, $level = 0) {
    foreach ($order as $item) {
        if (is_array($item)) {
            ob_start();
            printCourseOrder($item['courses'] ?? [], $myCourses, $printed, $level + 1);
            $sub = ob_get_clean();
            if ($sub != '') {
                echo '<option value="" disabled>' . str_repeat('&nbsp;&nbsp;', $level);
                echo Sanitize::encodeStringForDisplay($item['name']) . '</option>' . $sub;
            }
        } else if (isset($myCourses[$item]) && !in_array($item, $printed)) {
            printCourseOption($myCourses[$item], $level);
            $printed[] = $item;
        }
    }
}
function printCourseOption($course, $level = 0) {
    echo '<option value="' . Sanitize::onlyInt($course['id']) . '">';
    echo str_repeat('&nbsp;&nbsp;', $level);
    echo Sanitize::encodeStringForDisplay($course['name']) . '</option>';
}

$flexwidth = true;
$nologo = true;
$placeinhead = '<style type="text/css"> select { max-width: 100%;} </style>';
require_once "../header.php";
?>
<div id="headerforms" class="pagetitle">
 <h1><?php echo _('Copy to...'); ?></h1>
</div>
<p>
<label for="destcourse"><?php echo _('Copy to course:'); ?></label><br/>
<select id="destcourse">
 <option value=""><?php echo _('Select a course...'); ?></option>
 <option value="<?php echo intval($srccid);?>"><?php echo _('This Course'); ?></option>
 <option disabled>-------------</option>
<?php
if (isset($userjson['courseListOrder']['teach'])) {
    printCourseOrder($userjson['courseListOrder']['teach'], $myCourses, $printed);
}
foreach ($myCourses as $id => $course) {
    if (!in_array($id, $printed)) {
        printCourseOption($course);
    }
}
?>
</select>
</p>
<p>
<label for="copyinto"><?php echo _('Copy into:'); ?></label>
<select id="copyinto" disabled>
 <option value="none"><?php echo _('Main course page'); ?></option>
</select>
</p>
<p>
<label for="placeat"><?php echo _('Place at:'); ?></label>
<select id="placeat" disabled>
 <option value="start"><?php echo _('Start'); ?></option>
 <option value="end" selected><?php echo _('End'); ?></option>
 <option value="after"><?php echo _('After item...'); ?></option>
</select>
<select id="afteritem" aria-label="<?php echo _('Place after item'); ?>" hidden></select>
</p>
<p>
<button type="button" id="cancelbtn" class="secondarybtn"><?php echo _('Cancel'); ?></button>
<button type="button" id="copybtn" class="primary" disabled><?php echo _('Copy'); ?></button>
</p>
<p id="status" role="status"></p>
<script type="text/javascript">
$(function() {
  var base = imasroot + "/course/copytocourse.php?cid=" + cid + "&item=<?php echo $itemid; ?>";
  var $dest = $("#destcourse"), $into = $("#copyinto"), $place = $("#placeat"),
      $btn = $("#copybtn"), $status = $("#status");
  function resetBlocks() {
    $into.empty().append($("<option>", {value: "none", text: _("Main course page")}));
  }
  $dest.on("change", function() {
    $status.text("").removeClass("noticetext");
    resetBlocks();
    var destid = $dest.val();
    $btn.prop("disabled", destid === "");
    $into.prop("disabled", destid === "");
    $place.prop("disabled", destid === "");
    if (destid === "") { return; }
    $btn.prop("disabled", true); // until block list loads
    $.getJSON(base + "&getblocks=" + encodeURIComponent(destid)).done(function(data) {
      if ($dest.val() !== destid) { return; } // selection changed while loading
      if (data.error) {
        $status.text(data.error).addClass("noticetext");
        return;
      }
      data.blocks.forEach(function(b) {
        var pad = new Array(b.depth + 1).join("   ");
        $into.append($("<option>", {value: b.path, text: pad + b.name}));
      });
      $btn.prop("disabled", false);
      if ($place.val() === "after") { loadAfterItems(); }
    }).fail(function() {
      $status.text(_("Error loading course contents")).addClass("noticetext");
    });
  });
  // load the items in the chosen block into the "after item" select
  var $after = $("#afteritem");
  function loadAfterItems() {
    $after.empty();
    if ($place.val() !== "after") {
      $after.prop("hidden", true);
      $btn.prop("disabled", $dest.val() === "");
      return;
    }
    $after.prop("hidden", false);
    $btn.prop("disabled", true); // until item list loads
    var destid = $dest.val(), blk = $into.val();
    $.getJSON(base + "&getitems=" + encodeURIComponent(destid) + "&block=" + encodeURIComponent(blk)).done(function(data) {
      if ($dest.val() !== destid || $into.val() !== blk || $place.val() !== "after") { return; }
      if (data.error) {
        $status.text(data.error).addClass("noticetext");
        return;
      }
      if (data.items.length === 0) {
        $after.append($("<option>", {value: "", text: _("(no items)")}));
        $btn.prop("disabled", false); // will be placed at the end
        return;
      }
      data.items.forEach(function(it) {
        $after.append($("<option>", {value: it.id, text: it.name}));
      });
      $after.val(data.items[data.items.length - 1].id);
      $btn.prop("disabled", false);
    }).fail(function() {
      $status.text(_("Error loading course contents")).addClass("noticetext");
    });
  }
  $place.on("change", loadAfterItems);
  $into.on("change", function() {
    if ($place.val() === "after") { loadAfterItems(); }
  });
  $("#cancelbtn").on("click", function() { window.parent.GB_hide(); });
  $btn.on("click", function() {
    $btn.prop("disabled", true);
    $status.text(_("Copying...")).removeClass("noticetext");
    $.post(base, {
      destcid: $dest.val(),
      addto: $into.val(),
      addwhere: $place.val(),
      afteritem: $after.val() || ""
    }, null, "json").done(function(data) {
      if (data.success) {
        if ($dest.val() == cid) {
          window.parent.location.reload();
        } else {
          // stays disabled until a different course is selected
          $status.text(_("Copied successfully.") + " ");
          $("<button>", {type: "button", "class": "secondarybtn", text: _("Done")})
            .on("click", function() { window.parent.GB_hide(); })
            .appendTo($status).focus();
        }
      } else {
        $status.text(data.error || _("Error copying item")).addClass("noticetext");
        $btn.prop("disabled", false);
      }
    }).fail(function() {
      $status.text(_("Error copying item")).addClass("noticetext");
      $btn.prop("disabled", false);
    });
  });
});
</script>
<?php
require_once "../footer.php";
