<?php
// Gradebook mode (imas_gbscheme.defgbmode / $gbmode) helpers.
//
// $gbmode is a plain bitfield.  Each field occupies [shift, number of bits]:
//
//   bits 0-2   availshow       0 past due, 1 past & available, 2 all,
//                              3 past & attempted, 4 available only
//   bits 3-4   hidenc          0 show NC, 1 student-visible only, 2 hide all NC
//   bit  5     links           0 view/edit, 1 question breakdown (scores)
//   bit  6     hidelocked      1 = hide locked students
//   bit  7     totonleft       1 = totals columns on left
//   bit  8     avgontop        1 = average row on top
//   bit  9     lastlogin       1 = show last login column
//   bit  10    showpercents    1 = show percents instead of points
//   bits 11-12 showpics        0 none, 1 small, 2 big
//   bit  13    headerunlocked  1 = headers not locked
//   bit  14    hidesection     1 = hide section column
//   bit  15    hidecode        1 = hide code column
//   bit  16    shortenheaders  1 = try to shorten header names
//
// Before the 2026-11 migration, the value was a decimal-digit encoding; see
// gbmodeFromLegacy().

const GBMODE_FIELDS = [
	'availshow'      => [0, 3],
	'hidenc'         => [3, 2],
	'links'          => [5, 1],
	'hidelocked'     => [6, 1],
	'totonleft'      => [7, 1],
	'avgontop'       => [8, 1],
	'lastlogin'      => [9, 1],
	'showpercents'   => [10, 1],
	'showpics'       => [11, 2],
	'headerunlocked' => [13, 1],
	'hidesection'    => [14, 1],
	'hidecode'       => [15, 1],
	'shortenheaders' => [16, 1],
];
const GBMODE_MASK = 0x1FFFF;
const GBMODE_DEFAULT = 17; // availshow=1 (past & available), hidenc=2 (hide NC)

// max valid value of the multi-valued fields; anything larger is clamped
const GBMODE_MAXVAL = ['availshow' => 4, 'hidenc' => 2, 'showpics' => 2];
// value to use if a multi-valued field holds something invalid
const GBMODE_BADVAL = ['availshow' => 1, 'hidenc' => 2, 'showpics' => 0];

/**
 * Value of one field
 */
function gbmodeGet($gbmode, $field) {
	list($shift, $bits) = GBMODE_FIELDS[$field];
	$val = ((int) $gbmode >> $shift) & ((1 << $bits) - 1);
	if (isset(GBMODE_MAXVAL[$field]) && $val > GBMODE_MAXVAL[$field]) {
		$val = GBMODE_BADVAL[$field];
	}
	return $val;
}

/**
 * Returns $gbmode with one field replaced
 */
function gbmodeSet($gbmode, $field, $val) {
	list($shift, $bits) = GBMODE_FIELDS[$field];
	$fieldmask = ((1 << $bits) - 1);
	return (((int) $gbmode & ~($fieldmask << $shift)) | (((int) $val & $fieldmask) << $shift)) & GBMODE_MASK;
}

/**
 * All fields, as an array of ints
 */
function gbmodeDecode($gbmode) {
	$out = [];
	foreach (GBMODE_FIELDS as $field => $def) {
		$out[$field] = gbmodeGet($gbmode, $field);
	}
	return $out;
}

/**
 * Build a gbmode from an array of field => value (missing fields = 0)
 */
function gbmodeEncode($fields) {
	$gbmode = 0;
	foreach ($fields as $field => $val) {
		if (isset(GBMODE_FIELDS[$field])) {
			$gbmode = gbmodeSet($gbmode, $field, $val);
		}
	}
	return $gbmode;
}

/**
 * Convert an old decimal-digit gbmode (GFEDCBA) to the bitfield format.
 * Must stay equivalent to the SQL in migrations/20261114_gbmode_bitwise.php
 */
function gbmodeFromLegacy($legacy) {
	$legacy = (int) $legacy;
	$A = $legacy % 10;
	$B = intdiv($legacy, 10) % 10;
	$C = intdiv($legacy, 100) % 10;
	$D = intdiv($legacy, 1000) % 10;
	$E = intdiv($legacy, 10000) % 10;
	$F = intdiv($legacy, 100000) % 10;
	$G = intdiv($legacy, 1000000) % 10;
	$hidenc = $B % 4;
	$showpics = $E & 3;
	return gbmodeEncode([
		'availshow'      => ($A <= 4) ? $A : GBMODE_BADVAL['availshow'],
		'hidenc'         => ($hidenc <= 2) ? $hidenc : GBMODE_BADVAL['hidenc'],
		'links'          => $C & 1,
		'hidelocked'     => ($C & 2) >> 1,
		'totonleft'      => $D & 1,
		'avgontop'       => ($D & 2) >> 1,
		'lastlogin'      => ($D & 4) >> 2,
		'showpercents'   => ($F & 4) >> 2,
		'showpics'       => ($showpics <= 2) ? $showpics : GBMODE_BADVAL['showpics'],
		'headerunlocked' => ($E & 4) >> 2,
		'hidesection'    => $F & 1,
		'hidecode'       => ($F & 2) >> 1,
		'shortenheaders' => $G & 1,
	]);
}

/**
 * SQL expression that converts a legacy decimal-format gbmode column to the
 * bitfield format, so the whole imas_gbscheme table can be converted in one
 * UPDATE.  Must stay equivalent to gbmodeFromLegacy() (see tests/gbmode_tests.php).
 * MySQL's << binds tighter than &, so every operation is parenthesized.
 * @param string $col  column name (trusted, not user input)
 */
function gbmodeLegacyToBitsSql($col = 'defgbmode') {
	$d = function ($k) use ($col) {
		return "(($col DIV " . (10 ** $k) . ") MOD 10)";
	};
	$parts = [
		"(CASE WHEN {$d(0)} <= 4 THEN {$d(0)} ELSE " . GBMODE_BADVAL['availshow'] . " END)",
		"((CASE WHEN ({$d(1)} MOD 4) <= 2 THEN ({$d(1)} MOD 4) ELSE " . GBMODE_BADVAL['hidenc'] . " END) << 3)",
		"(({$d(2)} & 1) << 5)",   // links
		"(({$d(2)} & 2) << 5)",   // hidelocked (2 -> 64)
		"(({$d(3)} & 1) << 7)",   // totonleft
		"(({$d(3)} & 2) << 7)",   // avgontop (2 -> 256)
		"(({$d(3)} & 4) << 7)",   // lastlogin (4 -> 512)
		"(({$d(5)} & 4) << 8)",   // showpercents (4 -> 1024)
		"((CASE WHEN ({$d(4)} & 3) = 3 THEN " . GBMODE_BADVAL['showpics'] . " ELSE ({$d(4)} & 3) END) << 11)",
		"(({$d(4)} & 4) << 11)",  // headerunlocked (4 -> 8192)
		"(({$d(5)} & 1) << 14)",  // hidesection
		"(({$d(5)} & 2) << 14)",  // hidecode (2 -> 32768)
		"(({$d(6)} & 1) << 16)",  // shortenheaders
	];
	return implode(' | ', $parts);
}

/**
 * Default gbmode for new courses.  $CFG['GBS']['gbmode'] is in the bitfield
 * format.  The older $CFG['GBS']['defgbmode'] used the legacy decimal format
 * and is converted automatically.
 */
function gbmodeDefault() {
	global $CFG;
	if (isset($CFG['GBS']['gbmode'])) {
		return ((int) $CFG['GBS']['gbmode']) & GBMODE_MASK;
	} else if (isset($CFG['GBS']['defgbmode'])) {
		return gbmodeFromLegacy($CFG['GBS']['defgbmode']);
	}
	return GBMODE_DEFAULT;
}

/**
 * Determine the gradebook mode to use for the current request.
 * Order: $_GET['gbmode'] (if $useGet), session, course default.
 * The session value is ignored if $_GET['refreshdef'] is set.
 * @param int|string $cid
 * @param bool $useGet  whether to accept a gbmode URL parameter
 * @param bool $store   whether to save the GET value / DB default in the session
 * @param int|string|null $defgbmode  the course's imas_gbscheme.defgbmode, if the
 *                      caller has already queried it; avoids an extra query
 * @return int
 */
function loadGbMode($cid, $useGet = true, $store = false, $defgbmode = null) {
	global $DBH;
	$sesskey = $cid . 'gbmode2';
	if ($useGet && isset($_GET['gbmode']) && $_GET['gbmode'] !== '') {
		$gbmode = ((int) $_GET['gbmode']) & GBMODE_MASK;
		if ($store) { $_SESSION[$sesskey] = $gbmode; }
	} else if (isset($_SESSION[$sesskey]) && !isset($_GET['refreshdef'])) {
		$gbmode = ((int) $_SESSION[$sesskey]) & GBMODE_MASK;
	} else {
		if ($defgbmode === null) {
			$stm = $DBH->prepare("SELECT defgbmode FROM imas_gbscheme WHERE courseid=:courseid");
			$stm->execute([':courseid' => $cid]);
			$defgbmode = $stm->fetchColumn(0);
		}
		$gbmode = $defgbmode;
		$gbmode = ($gbmode === false) ? gbmodeDefault() : ((int) $gbmode) & GBMODE_MASK;
		if ($store) { $_SESSION[$sesskey] = $gbmode; }
	}
	return $gbmode;
}

/**
 * Field definitions as JSON-able array for JS: {field: [shift, mask]}
 */
function gbmodeJsFields() {
	$out = [];
	foreach (GBMODE_FIELDS as $field => $def) {
		$out[$field] = [$def[0], (1 << $def[1]) - 1];
	}
	return $out;
}
