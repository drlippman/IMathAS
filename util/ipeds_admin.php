<?php
require_once '../init.php';

if ($myrights < 100) {
    echo 'You are not authorized for this page';
    exit;
}
if (empty($CFG['use_ipeds'])) {
    echo 'IPEDS use not enabled';
    exit;
}

$countries = [ 'Afghanistan'=>'AF', 'Albania'=>'AL', 'Algeria'=>'DZ', 'Andorra'=>'AD', 'Angola'=>'AO', 'Anguilla'=>'AI', 'Antarctica'=>'AQ', 'Antigua and Barbuda'=>'AG', 'Argentina'=>'AR', 'Armenia'=>'AM', 'Aruba'=>'AW', 'Australia'=>'AU', 'Austria'=>'AT', 'Azerbaijan'=>'AZ', 'Bahamas (the)'=>'BS', 'Bahrain'=>'BH', 'Bangladesh'=>'BD', 'Barbados'=>'BB', 'Belarus'=>'BY', 'Belgium'=>'BE', 'Belize'=>'BZ', 'Benin'=>'BJ', 'Bermuda'=>'BM', 'Bhutan'=>'BT', 'Bolivia (Plurinational State of)'=>'BO', 'Bonaire, Sint Eustatius and Saba'=>'BQ', 'Bosnia and Herzegovina'=>'BA', 'Botswana'=>'BW', 'Bouvet Island'=>'BV', 'Brazil'=>'BR', 'British Indian Ocean Territory (the)'=>'IO', 'Brunei Darussalam'=>'BN', 'Bulgaria'=>'BG', 'Burkina Faso'=>'BF', 'Burundi'=>'BI', 'Cabo Verde'=>'CV', 'Cambodia'=>'KH', 'Cameroon'=>'CM', 'Canada'=>'CA', 'Cayman Islands (the)'=>'KY', 'Central African Republic (the)'=>'CF', 'Chad'=>'TD', 'Chile'=>'CL', 'China'=>'CN', 'Christmas Island'=>'CX', 'Cocos (Keeling) Islands (the)'=>'CC', 'Colombia'=>'CO', 'Comoros (the)'=>'KM', 'Congo (the Democratic Republic of the)'=>'CD', 'Congo (the)'=>'CG', 'Cook Islands (the)'=>'CK', 'Costa Rica'=>'CR', 'Croatia'=>'HR', 'Cuba'=>'CU', 'Curaçao'=>'CW', 'Cyprus'=>'CY', 'Czechia'=>'CZ', 'Côte d\'Ivoire'=>'CI', 'Denmark'=>'DK', 'Djibouti'=>'DJ', 'Dominica'=>'DM', 'Dominican Republic (the)'=>'DO', 'Ecuador'=>'EC', 'Egypt'=>'EG', 'El Salvador'=>'SV', 'Equatorial Guinea'=>'GQ', 'Eritrea'=>'ER', 'Estonia'=>'EE', 'Eswatini'=>'SZ', 'Ethiopia'=>'ET', 'Falkland Islands (the) [Malvinas]'=>'FK', 'Faroe Islands (the)'=>'FO', 'Fiji'=>'FJ', 'Finland'=>'FI', 'France'=>'FR', 'French Guiana'=>'GF', 'French Polynesia'=>'PF', 'French Southern Territories (the)'=>'TF', 'Gabon'=>'GA', 'Gambia (the)'=>'GM', 'Georgia'=>'GE', 'Germany'=>'DE', 'Ghana'=>'GH', 'Gibraltar'=>'GI', 'Greece'=>'GR', 'Greenland'=>'GL', 'Grenada'=>'GD', 'Guadeloupe'=>'GP', 'Guatemala'=>'GT', 'Guernsey'=>'GG', 'Guinea'=>'GN', 'Guinea-Bissau'=>'GW', 'Guyana'=>'GY', 'Haiti'=>'HT', 'Heard Island and McDonald Islands'=>'HM', 'Holy See (the)'=>'VA', 'Honduras'=>'HN', 'Hong Kong'=>'HK', 'Hungary'=>'HU', 'Iceland'=>'IS', 'India'=>'IN', 'Indonesia'=>'ID', 'Iran (Islamic Republic of)'=>'IR', 'Iraq'=>'IQ', 'Ireland'=>'IE', 'Isle of Man'=>'IM', 'Israel'=>'IL', 'Italy'=>'IT', 'Jamaica'=>'JM', 'Japan'=>'JP', 'Jersey'=>'JE', 'Jordan'=>'JO', 'Kazakhstan'=>'KZ', 'Kenya'=>'KE', 'Kiribati'=>'KI', 'Korea (the Democratic People\'s Republic of)'=>'KP', 'Korea (the Republic of)'=>'KR', 'Kuwait'=>'KW', 'Kyrgyzstan'=>'KG', 'Lao People\'s Democratic Republic (the)'=>'LA', 'Latvia'=>'LV', 'Lebanon'=>'LB', 'Lesotho'=>'LS', 'Liberia'=>'LR', 'Libya'=>'LY', 'Liechtenstein'=>'LI', 'Lithuania'=>'LT', 'Luxembourg'=>'LU', 'Macao'=>'MO', 'Madagascar'=>'MG', 'Malawi'=>'MW', 'Malaysia'=>'MY', 'Maldives'=>'MV', 'Mali'=>'ML', 'Malta'=>'MT', 'Martinique'=>'MQ', 'Mauritania'=>'MR', 'Mauritius'=>'MU', 'Mayotte'=>'YT', 'Mexico'=>'MX', 'Moldova (the Republic of)'=>'MD', 'Monaco'=>'MC', 'Mongolia'=>'MN', 'Montenegro'=>'ME', 'Montserrat'=>'MS', 'Morocco'=>'MA', 'Mozambique'=>'MZ', 'Myanmar'=>'MM', 'Namibia'=>'NA', 'Nauru'=>'NR', 'Nepal'=>'NP', 'Netherlands (the)'=>'NL', 'New Caledonia'=>'NC', 'New Zealand'=>'NZ', 'Nicaragua'=>'NI', 'Niger (the)'=>'NE', 'Nigeria'=>'NG', 'Niue'=>'NU', 'Norfolk Island'=>'NF', 'Norway'=>'NO', 'Oman'=>'OM', 'Pakistan'=>'PK', 'Palestine, State of'=>'PS', 'Panama'=>'PA', 'Papua New Guinea'=>'PG', 'Paraguay'=>'PY', 'Peru'=>'PE', 'Philippines (the)'=>'PH', 'Pitcairn'=>'PN', 'Poland'=>'PL', 'Portugal'=>'PT', 'Qatar'=>'QA', 'Republic of North Macedonia'=>'MK', 'Romania'=>'RO', 'Russian Federation (the)'=>'RU', 'Rwanda'=>'RW', 'Réunion'=>'RE', 'Saint Barthélemy'=>'BL', 'Saint Helena, Ascension and Tristan da Cunha'=>'SH', 'Saint Kitts and Nevis'=>'KN', 'Saint Lucia'=>'LC', 'Saint Martin (French part)'=>'MF', 'Saint Pierre and Miquelon'=>'PM', 'Saint Vincent and the Grenadines'=>'VC', 'Samoa'=>'WS', 'San Marino'=>'SM', 'Sao Tome and Principe'=>'ST', 'Saudi Arabia'=>'SA', 'Senegal'=>'SN', 'Serbia'=>'RS', 'Seychelles'=>'SC', 'Sierra Leone'=>'SL', 'Singapore'=>'SG', 'Sint Maarten (Dutch part)'=>'SX', 'Slovakia'=>'SK', 'Slovenia'=>'SI', 'Solomon Islands'=>'SB', 'Somalia'=>'SO', 'South Africa'=>'ZA', 'South Georgia and the South Sandwich Islands'=>'GS', 'South Sudan'=>'SS', 'Spain'=>'ES', 'Sri Lanka'=>'LK', 'Sudan (the)'=>'SD', 'Suriname'=>'SR', 'Svalbard and Jan Mayen'=>'SJ', 'Sweden'=>'SE', 'Switzerland'=>'CH', 'Syrian Arab Republic'=>'SY', 'Taiwan'=>'TW', 'Tajikistan'=>'TJ', 'Tanzania, United Republic of'=>'TZ', 'Thailand'=>'TH', 'Timor-Leste'=>'TL', 'Togo'=>'TG', 'Tokelau'=>'TK', 'Tonga'=>'TO', 'Trinidad and Tobago'=>'TT', 'Tunisia'=>'TN', 'Turkey'=>'TR', 'Turkmenistan'=>'TM', 'Turks and Caicos Islands (the)'=>'TC', 'Tuvalu'=>'TV', 'Uganda'=>'UG', 'Ukraine'=>'UA', 'United Arab Emirates (the)'=>'AE', 'United Kingdom of Great Britain and Northern Ireland (the)'=>'GB', 'United States'=>'US', 'United States Minor Outlying Islands (the)'=>'UM', 'Uruguay'=>'UY', 'Uzbekistan'=>'UZ', 'Vanuatu'=>'VU', 'Venezuela (Bolivarian Republic of)'=>'VE', 'Viet Nam'=>'VN', 'Virgin Islands (British)'=>'VG', 'Wallis and Futuna'=>'WF', 'Western Sahara'=>'EH', 'Yemen'=>'YE', 'Zambia'=>'ZM', 'Zimbabwe'=>'ZW', 'Åland Islands'=>'AX'];
$countryflip = array_flip($countries);

$states = ['Alabama'=>'AL', 'Alaska'=>'AK', 'American Samoa'=>'AS', 'Arizona'=>'AZ', 'Arkansas'=>'AR', 'Bureau of Indian Education'=>'BI', 'California'=>'CA', 'Colorado'=>'CO', 'Connecticut'=>'CT', 'Delaware'=>'DE', 'District of Columbia'=>'DC', 'Federated States of Micronesia'=>'FM', 'Florida'=>'FL', 'Georgia'=>'GA', 'Guam'=>'GU', 'Hawaii'=>'HI', 'Idaho'=>'ID', 'Illinois'=>'IL', 'Indiana'=>'IN', 'Iowa'=>'IA', 'Kansas'=>'KS', 'Kentucky'=>'KY', 'Louisiana'=>'LA', 'Maine'=>'ME', 'Marshall Islands'=>'MH', 'Maryland'=>'MD', 'Massachusetts'=>'MA', 'Michigan'=>'MI', 'Minnesota'=>'MN', 'Mississippi'=>'MS', 'Missouri'=>'MO', 'Montana'=>'MT', 'Nebraska'=>'NE', 'Nevada'=>'NV', 'New Hampshire'=>'NH', 'New Jersey'=>'NJ', 'New Mexico'=>'NM', 'New York'=>'NY', 'North Carolina'=>'NC', 'North Dakota'=>'ND', 'Northern Marianas'=>'MP', 'Ohio'=>'OH', 'Oklahoma'=>'OK', 'Oregon'=>'OR', 'Palau'=>'PW', 'Pennsylvania'=>'PA', 'Puerto Rico'=>'PR', 'Rhode Island'=>'RI', 'South Carolina'=>'SC', 'South Dakota'=>'SD', 'Tennessee'=>'TN', 'Texas'=>'TX', 'Utah'=>'UT', 'Vermont'=>'VT', 'Virgin Islands'=>'VI', 'Virginia'=>'VA', 'Washington'=>'WA', 'West Virginia'=>'WV', 'Wisconsin'=>'WI', 'Wyoming'=>'WY'];

// Type labels used throughout the page
$typeLabels = [
    'I' => 'HigherEd (US)',
    'A' => 'Public K-12',
    'S' => 'Private K-12',
    'W' => 'Intl HigherEd',
    'U' => 'Intl K-12',
    'C' => 'Custom',
];

$message = '';
$messageClass = '';

// ── POST handlers ──────────────────────────────────────────────────────────────

if (isset($_POST['action'])) {
    $action = $_POST['action'];

    // ── UPDATE existing record ─────────────────────────────────────────────────
    if ($action === 'update') {
        $id      = intval($_POST['id']);
        $type    = Sanitize::simpleString($_POST['type']);
        $ipedsid = Sanitize::stripHtmlTags(trim($_POST['ipedsid']));
        $school  = Sanitize::stripHtmlTags(trim($_POST['school']));
        $agency  = Sanitize::stripHtmlTags(trim($_POST['agency']));
        $country = Sanitize::simpleString($_POST['country']);
        $state   = Sanitize::stripHtmlTags(trim($_POST['state']));
        $zip     = !empty($_POST['zip']) ? intval($_POST['zip']) : null;

        if (empty($ipedsid) || empty($type)) {
            $message = 'Type and IPEDS ID are required.';
            $messageClass = 'error';
        } else {
            $stm = $DBH->prepare(
                'UPDATE imas_ipeds SET type=?, ipedsid=?, school=?, agency=?, country=?, state=?, zip=?
                 WHERE id=?'
            );
            $ok = $stm->execute([$type, $ipedsid, $school, $agency, $country, $state, $zip, $id]);
            if ($ok) {
                $message = 'Record updated successfully.';
                $messageClass = 'success';
            } else {
                $message = 'Update failed. The IPEDS ID may already exist for another record.';
                $messageClass = 'error';
            }
        }
    }

    // ── INSERT new custom record ───────────────────────────────────────────────
    if ($action === 'insert') {
        $type    = Sanitize::simpleString($_POST['new_type']);
        $ipedsid = Sanitize::stripHtmlTags(trim($_POST['new_ipedsid']));
        $school  = Sanitize::stripHtmlTags(trim($_POST['new_school']));
        $agency  = Sanitize::stripHtmlTags(trim($_POST['new_agency']));
        $country = Sanitize::simpleString($_POST['new_country']);
        $state   = Sanitize::stripHtmlTags(trim($_POST['new_state']));
        $zip     = !empty($_POST['new_zip']) ? intval($_POST['new_zip']) : null;

        // Auto-generate an ipedsid if not provided
        if (empty($ipedsid)) {
            $ipedsid = md5($school . $agency . $country . $state . microtime());
        }

        if (empty($type)) {
            $message = 'Type is required.';
            $messageClass = 'error';
        } else {
            $stm = $DBH->prepare(
                'INSERT INTO imas_ipeds (type, ipedsid, school, agency, country, state, zip)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $ok = $stm->execute([$type, $ipedsid, $school, $agency, $country, $state, $zip]);
            if ($ok) {
                $message = 'New record inserted (ID: ' . $DBH->lastInsertId() . ').';
                $messageClass = 'success';
            } else {
                $message = 'Insert failed. The IPEDS ID may already exist.';
                $messageClass = 'error';
            }
        }
    }
}

// ── Search ─────────────────────────────────────────────────────────────────────
$searchResults = [];
$searched = false;
if (isset($_GET['q']) && trim($_GET['q']) !== '') {
    $searched = true;
    $q = trim($_GET['q']);
    $searchType  = $_GET['search_type'] ?? 'name';
    $filterType  = isset($_GET['filter_type']) && array_key_exists($_GET['filter_type'], $typeLabels)
                   ? $_GET['filter_type'] : '';
    $typeClause  = $filterType !== '' ? ' AND type=?' : '';

    // Build a small helper to append the type param when needed
    $bindType = function(array $base) use ($filterType) {
        if ($filterType !== '') { $base[] = $filterType; }
        return $base;
    };

    if ($searchType === 'id') {
        $stm = $DBH->prepare(
            "SELECT * FROM imas_ipeds WHERE ipedsid LIKE ?$typeClause ORDER BY type, school, agency LIMIT 200"
        );
        $stm->execute($bindType(['%' . $q . '%']));
    } else if ($searchType === 'zip' && is_numeric($q)) {
        $stm = $DBH->prepare(
            "SELECT * FROM imas_ipeds WHERE zip = ?$typeClause ORDER BY type, school, agency LIMIT 200"
        );
        $stm->execute($bindType([intval($q)]));
    } else {
        // Full-text search on school + agency, fallback to LIKE
        $stm = $DBH->prepare(
            "SELECT * FROM imas_ipeds
             WHERE (MATCH(school) AGAINST(? IN BOOLEAN MODE)
                OR MATCH(agency) AGAINST(? IN BOOLEAN MODE))$typeClause
             ORDER BY type, school, agency LIMIT 200"
        );
        $ran = $stm->execute($bindType([$q . '*', $q . '*']));
        // If full-text returns nothing try a LIKE fallback
        if ($ran && $stm->rowCount() === 0) {
            $stm = $DBH->prepare(
                "SELECT * FROM imas_ipeds
                 WHERE (school LIKE ? OR agency LIKE ?)$typeClause
                 ORDER BY type, school, agency LIMIT 200"
            );
            $stm->execute($bindType(['%' . $q . '%', '%' . $q . '%']));
        }
    }
    $searchResults = $stm->fetchAll(PDO::FETCH_ASSOC);
}

// ── Helper: build option lists ─────────────────────────────────────────────────
function countryOptions($selected, $countries) {
    $html = '<option value="">Select...</option>';
    foreach ($countries as $name => $code) {
        $sel = ($code === $selected) ? ' selected' : '';
        $html .= '<option value="' . Sanitize::encodeStringForDisplay($code) . '"' . $sel . '>' . Sanitize::encodeStringForDisplay($name) . '</option>';
    }
    return $html;
}

function stateOptions($selected, $states) {
    $html = '<option value="">None / Intl</option>';
    foreach ($states as $name => $code) {
        $sel = ($code === $selected) ? ' selected' : '';
        $html .= '<option value="' . Sanitize::encodeStringForDisplay($code) . '"' . $sel . '>' . Sanitize::encodeStringForDisplay($name) . '</option>';
    }
    return $html;
}

function typeOptions($selected, $typeLabels) {
    $html = '<option value="">Select...</option>';
    foreach ($typeLabels as $code => $label) {
        $sel = ($code === $selected) ? ' selected' : '';
        $html .= '<option value="' . Sanitize::encodeStringForDisplay($code) . '"' . $sel . '>' . Sanitize::encodeStringForDisplay($label) . '</option>';
    }
    return $html;
}

// ── Page output ────────────────────────────────────────────────────────────────
$pagetitle = 'IPEDS Record Admin';
require_once '../header.php';
$curBreadcrumb = "$breadcrumbbase <a href=\"$imasroot/admin/admin2.php\">Admin</a>";
$curBreadcrumb .= " &gt; <a href=\"$imasroot/util/utils.php\">Utils</a>";
?>
<div class="breadcrumb"><?php echo $curBreadcrumb ?> &gt; IPEDS Admin</div>

<style>
.ipeds-admin-wrap { max-width: 1100px; margin: 0 auto; padding: 0 1em 3em; }
.ipeds-admin-wrap h1 { margin-bottom: .25em; }
.msg-success { background:#d4edda; color:#155724; border:1px solid #c3e6cb; padding:.5em 1em; border-radius:4px; margin-bottom:1em; }
.msg-error   { background:#f8d7da; color:#721c24; border:1px solid #f5c6cb; padding:.5em 1em; border-radius:4px; margin-bottom:1em; }
.search-bar { display:flex; gap:.5em; align-items:center; flex-wrap:wrap; margin-bottom:1.2em; }
.search-bar input[type=text] { padding:.35em .5em; font-size:1em; flex:1; min-width:200px; }
.search-bar select { padding:.35em .4em; font-size:1em; }
.search-bar button { padding:.35em .9em; font-size:1em; }
table.ipeds-results { border-collapse:collapse; width:100%; font-size:.93em; }
table.ipeds-results th, table.ipeds-results td { border:1px solid #ccc; padding:.35em .6em; vertical-align:top; }
table.ipeds-results th { background:#f0f0f0; white-space:nowrap; }
table.ipeds-results tr:nth-child(even) { background:#fafafa; }
.edit-row td { background:#fffde7 !important; }
.edit-row input[type=text], .edit-row select { width:100%; box-sizing:border-box; padding:.2em .3em; font-size:.92em; }
.btn-edit   { font-size:.82em; padding:.2em .55em; cursor:pointer; }
.btn-save   { font-size:.82em; padding:.2em .55em; background:#0066cc; color:#fff; border:none; cursor:pointer; border-radius:3px; }
.btn-cancel { font-size:.82em; padding:.2em .55em; cursor:pointer; }
.no-results { color:#666; margin:1em 0; }
details.insert-section { margin-top:2.5em; border:1px solid #bbb; border-radius:4px; padding:.6em 1em 1em; background:#f9f9f9; }
details.insert-section summary { font-size:1.1em; font-weight:bold; cursor:pointer; padding:.3em 0; }
.insert-grid { display:grid; grid-template-columns: repeat(auto-fit, minmax(220px,1fr)); gap:.7em 1.2em; margin-top:.8em; }
.insert-grid label { display:flex; flex-direction:column; font-size:.93em; }
.insert-grid input, .insert-grid select { padding:.3em .4em; font-size:.95em; margin-top:.25em; }
.insert-actions { margin-top:1em; }
.result-count { color:#555; font-size:.9em; margin-bottom:.5em; }
</style>

<div class="ipeds-admin-wrap">
<h1>IPEDS Record Admin</h1>
<p>Search for existing records to view and edit them, or insert a new custom record below.</p>

<?php if ($message): ?>
<div class="msg-<?php echo $messageClass; ?>"><?php echo Sanitize::encodeStringForDisplay($message); ?></div>
<?php endif; ?>

<!-- ── SEARCH FORM ── -->
<form method="get" action="ipeds_admin.php">
    <div class="search-bar">
        <select name="search_type" aria-label="Search by">
            <option value="name"<?php echo (($_GET['search_type'] ?? 'name') === 'name' ? ' selected' : ''); ?>>Name</option>
            <option value="id"<?php   echo (($_GET['search_type'] ?? '') === 'id'   ? ' selected' : ''); ?>>IPEDS ID</option>
            <option value="zip"<?php  echo (($_GET['search_type'] ?? '') === 'zip'  ? ' selected' : ''); ?>>ZIP</option>
        </select>
        <select name="filter_type" aria-label="Institution type">
            <option value="">All types</option>
            <?php foreach ($typeLabels as $code => $label):
                $sel = (($_GET['filter_type'] ?? '') === $code) ? ' selected' : ''; ?>
            <option value="<?php echo Sanitize::encodeStringForDisplay($code); ?>"<?php echo $sel; ?>><?php echo Sanitize::encodeStringForDisplay($label); ?></option>
            <?php endforeach; ?>
        </select>
        <input type="text" name="q" value="<?php echo Sanitize::encodeStringForDisplay($_GET['q'] ?? ''); ?>"
               placeholder="Search IPEDS records…" aria-label="Search terms">
        <button type="submit">Search</button>
        <?php if ($searched): ?>
            <a href="ipeds_admin.php">Clear</a>
        <?php endif; ?>
    </div>
</form>

<!-- ── SEARCH RESULTS ── -->
<?php if ($searched): ?>
    <?php if (empty($searchResults)): ?>
        <p class="no-results">No records found for that query.</p>
    <?php else: ?>
        <p class="result-count"><?php echo count($searchResults); ?> record(s) found
            <?php if (count($searchResults) === 200): echo ' (showing first 200)'; endif; ?>
        </p>
        <table class="ipeds-results" id="results-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Type</th>
                    <th>IPEDS ID</th>
                    <th>School</th>
                    <th>Agency</th>
                    <th>Country</th>
                    <th>State</th>
                    <th>ZIP</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($searchResults as $row):
                $eid = (int)$row['id'];
                $eType    = Sanitize::encodeStringForDisplay($row['type']);
                $eIpedsid = Sanitize::encodeStringForDisplay($row['ipedsid']);
                $eSchool  = Sanitize::encodeStringForDisplay($row['school']);
                $eAgency  = Sanitize::encodeStringForDisplay($row['agency']);
                $eCountry = Sanitize::encodeStringForDisplay($row['country']);
                $eState   = Sanitize::encodeStringForDisplay($row['state']);
                $eZip     = Sanitize::encodeStringForDisplay($row['zip'] ?? '');
                $eTypeLabel = Sanitize::encodeStringForDisplay($typeLabels[$row['type']] ?? $row['type']);
            ?>
            <!-- Display row -->
            <tr id="display-<?php echo $eid; ?>">
                <td><?php echo $eid; ?></td>
                <td><?php echo $eTypeLabel; ?></td>
                <td><?php echo $eIpedsid; ?></td>
                <td><?php echo $eSchool; ?></td>
                <td><?php echo $eAgency; ?></td>
                <td><?php echo $eCountry; ?></td>
                <td><?php echo $eState; ?></td>
                <td><?php echo $eZip; ?></td>
                <td><button class="btn-edit" onclick="showEdit(<?php echo $eid; ?>)">Edit</button></td>
            </tr>
            <!-- Edit row (hidden until Edit clicked) -->
            <tr id="edit-<?php echo $eid; ?>" class="edit-row" style="display:none">
                <td colspan="9">
                    <form method="post" action="ipeds_admin.php?q=<?php echo urlencode($_GET['q'] ?? ''); ?>&search_type=<?php echo urlencode($_GET['search_type'] ?? 'name'); ?>&filter_type=<?php echo urlencode($_GET['filter_type'] ?? ''); ?>">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="id" value="<?php echo $eid; ?>">
                        <div class="insert-grid">
                            <label>Type
                                <select name="type" required>
                                    <?php echo typeOptions($row['type'], $typeLabels); ?>
                                </select>
                            </label>
                            <label>IPEDS ID
                                <input type="text" name="ipedsid" value="<?php echo $eIpedsid; ?>" required maxlength="32">
                            </label>
                            <label>School Name
                                <input type="text" name="school" value="<?php echo $eSchool; ?>" maxlength="255">
                            </label>
                            <label>Agency / District
                                <input type="text" name="agency" value="<?php echo $eAgency; ?>" maxlength="255">
                            </label>
                            <label>Country
                                <select name="country">
                                    <?php echo countryOptions($row['country'], $countries); ?>
                                </select>
                            </label>
                            <label>State
                                <select name="state">
                                    <?php echo stateOptions($row['state'], $states); ?>
                                </select>
                            </label>
                            <label>ZIP
                                <input type="text" name="zip" value="<?php echo $eZip; ?>" maxlength="5" pattern="\d{0,5}">
                            </label>
                        </div>
                        <div style="margin-top:.7em; display:flex; gap:.5em;">
                            <button type="submit" class="btn-save">Save Changes</button>
                            <button type="button" class="btn-cancel" onclick="hideEdit(<?php echo $eid; ?>)">Cancel</button>
                        </div>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
<?php elseif (!$searched): ?>
    <p class="no-results">Enter a search term above to find records.</p>
<?php endif; ?>

<!-- ── INSERT NEW RECORD ── -->
<details class="insert-section"<?php echo (isset($_POST['action']) && $_POST['action'] === 'insert' ? ' open' : ''); ?>>
    <summary>Insert New Custom Record</summary>
    <form method="post" action="ipeds_admin.php<?php echo $searched ? '?q='.urlencode($_GET['q'] ?? '').'&search_type='.urlencode($_GET['search_type'] ?? 'name').'&filter_type='.urlencode($_GET['filter_type'] ?? '') : ''; ?>">
        <input type="hidden" name="action" value="insert">
        <div class="insert-grid">
            <label>Type <span style="color:red">*</span>
                <select name="new_type" required>
                    <?php echo typeOptions('', $typeLabels); ?>
                </select>
            </label>
            <label>IPEDS ID
                <input type="text" name="new_ipedsid" maxlength="32"
                       placeholder="Leave blank to auto-generate">
            </label>
            <label>School Name
                <input type="text" name="new_school" maxlength="255">
            </label>
            <label>Agency / District
                <input type="text" name="new_agency" maxlength="255">
            </label>
            <label>Country
                <select name="new_country">
                    <?php echo countryOptions('US', $countries); ?>
                </select>
            </label>
            <label>State
                <select name="new_state">
                    <?php echo stateOptions('', $states); ?>
                </select>
            </label>
            <label>ZIP (5 digits)
                <input type="text" name="new_zip" maxlength="5" pattern="\d{0,5}">
            </label>
        </div>
        <div class="insert-actions">
            <button type="submit" class="btn-save">Insert Record</button>
        </div>
    </form>
</details>
</div>

<script>
function showEdit(id) {
    document.getElementById('display-' + id).style.display = 'none';
    document.getElementById('edit-' + id).style.display = '';
}
function hideEdit(id) {
    document.getElementById('edit-' + id).style.display = 'none';
    document.getElementById('display-' + id).style.display = '';
}
</script>

<?php require_once '../footer.php'; ?>