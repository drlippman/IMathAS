<?php 

// TODO: 
require('../init.php');

if ($myrights < 40) {
    echo 'You are not authorized for this page';
    exit;
}
if (empty($CFG['use_ipeds'])) {
    echo 'IPEDS use not enabled';
    exit;
}

if ($myrights == 100 && isset($_REQUEST['groupid'])) {
    $grp = intval($_REQUEST['groupid']);
} else {
    $grp = $groupid;
}
$countries = [ 'Afghanistan'=>'AF', 'Albania'=>'AL', 'Algeria'=>'DZ', 'Andorra'=>'AD', 'Angola'=>'AO', 'Anguilla'=>'AI', 'Antarctica'=>'AQ', 'Antigua and Barbuda'=>'AG', 'Argentina'=>'AR', 'Armenia'=>'AM', 'Aruba'=>'AW', 'Australia'=>'AU', 'Austria'=>'AT', 'Azerbaijan'=>'AZ', 'Bahamas (the)'=>'BS', 'Bahrain'=>'BH', 'Bangladesh'=>'BD', 'Barbados'=>'BB', 'Belarus'=>'BY', 'Belgium'=>'BE', 'Belize'=>'BZ', 'Benin'=>'BJ', 'Bermuda'=>'BM', 'Bhutan'=>'BT', 'Bolivia (Plurinational State of)'=>'BO', 'Bonaire, Sint Eustatius and Saba'=>'BQ', 'Bosnia and Herzegovina'=>'BA', 'Botswana'=>'BW', 'Bouvet Island'=>'BV', 'Brazil'=>'BR', 'British Indian Ocean Territory (the)'=>'IO', 'Brunei Darussalam'=>'BN', 'Bulgaria'=>'BG', 'Burkina Faso'=>'BF', 'Burundi'=>'BI', 'Cabo Verde'=>'CV', 'Cambodia'=>'KH', 'Cameroon'=>'CM', 'Canada'=>'CA', 'Cayman Islands (the)'=>'KY', 'Central African Republic (the)'=>'CF', 'Chad'=>'TD', 'Chile'=>'CL', 'China'=>'CN', 'Christmas Island'=>'CX', 'Cocos (Keeling) Islands (the)'=>'CC', 'Colombia'=>'CO', 'Comoros (the)'=>'KM', 'Congo (the Democratic Republic of the)'=>'CD', 'Congo (the)'=>'CG', 'Cook Islands (the)'=>'CK', 'Costa Rica'=>'CR', 'Croatia'=>'HR', 'Cuba'=>'CU', 'Curaçao'=>'CW', 'Cyprus'=>'CY', 'Czechia'=>'CZ', 'Côte d\'Ivoire'=>'CI', 'Denmark'=>'DK', 'Djibouti'=>'DJ', 'Dominica'=>'DM', 'Dominican Republic (the)'=>'DO', 'Ecuador'=>'EC', 'Egypt'=>'EG', 'El Salvador'=>'SV', 'Equatorial Guinea'=>'GQ', 'Eritrea'=>'ER', 'Estonia'=>'EE', 'Eswatini'=>'SZ', 'Ethiopia'=>'ET', 'Falkland Islands (the) [Malvinas]'=>'FK', 'Faroe Islands (the)'=>'FO', 'Fiji'=>'FJ', 'Finland'=>'FI', 'France'=>'FR', 'French Guiana'=>'GF', 'French Polynesia'=>'PF', 'French Southern Territories (the)'=>'TF', 'Gabon'=>'GA', 'Gambia (the)'=>'GM', 'Georgia'=>'GE', 'Germany'=>'DE', 'Ghana'=>'GH', 'Gibraltar'=>'GI', 'Greece'=>'GR', 'Greenland'=>'GL', 'Grenada'=>'GD', 'Guadeloupe'=>'GP', 'Guatemala'=>'GT', 'Guernsey'=>'GG', 'Guinea'=>'GN', 'Guinea-Bissau'=>'GW', 'Guyana'=>'GY', 'Haiti'=>'HT', 'Heard Island and McDonald Islands'=>'HM', 'Holy See (the)'=>'VA', 'Honduras'=>'HN', 'Hong Kong'=>'HK', 'Hungary'=>'HU', 'Iceland'=>'IS', 'India'=>'IN', 'Indonesia'=>'ID', 'Iran (Islamic Republic of)'=>'IR', 'Iraq'=>'IQ', 'Ireland'=>'IE', 'Isle of Man'=>'IM', 'Israel'=>'IL', 'Italy'=>'IT', 'Jamaica'=>'JM', 'Japan'=>'JP', 'Jersey'=>'JE', 'Jordan'=>'JO', 'Kazakhstan'=>'KZ', 'Kenya'=>'KE', 'Kiribati'=>'KI', 'Korea (the Democratic People\'s Republic of)'=>'KP', 'Korea (the Republic of)'=>'KR', 'Kuwait'=>'KW', 'Kyrgyzstan'=>'KG', 'Lao People\'s Democratic Republic (the)'=>'LA', 'Latvia'=>'LV', 'Lebanon'=>'LB', 'Lesotho'=>'LS', 'Liberia'=>'LR', 'Libya'=>'LY', 'Liechtenstein'=>'LI', 'Lithuania'=>'LT', 'Luxembourg'=>'LU', 'Macao'=>'MO', 'Madagascar'=>'MG', 'Malawi'=>'MW', 'Malaysia'=>'MY', 'Maldives'=>'MV', 'Mali'=>'ML', 'Malta'=>'MT', 'Martinique'=>'MQ', 'Mauritania'=>'MR', 'Mauritius'=>'MU', 'Mayotte'=>'YT', 'Mexico'=>'MX', 'Moldova (the Republic of)'=>'MD', 'Monaco'=>'MC', 'Mongolia'=>'MN', 'Montenegro'=>'ME', 'Montserrat'=>'MS', 'Morocco'=>'MA', 'Mozambique'=>'MZ', 'Myanmar'=>'MM', 'Namibia'=>'NA', 'Nauru'=>'NR', 'Nepal'=>'NP', 'Netherlands (the)'=>'NL', 'New Caledonia'=>'NC', 'New Zealand'=>'NZ', 'Nicaragua'=>'NI', 'Niger (the)'=>'NE', 'Nigeria'=>'NG', 'Niue'=>'NU', 'Norfolk Island'=>'NF', 'Norway'=>'NO', 'Oman'=>'OM', 'Pakistan'=>'PK', 'Palestine, State of'=>'PS', 'Panama'=>'PA', 'Papua New Guinea'=>'PG', 'Paraguay'=>'PY', 'Peru'=>'PE', 'Philippines (the)'=>'PH', 'Pitcairn'=>'PN', 'Poland'=>'PL', 'Portugal'=>'PT', 'Qatar'=>'QA', 'Republic of North Macedonia'=>'MK', 'Romania'=>'RO', 'Russian Federation (the)'=>'RU', 'Rwanda'=>'RW', 'Réunion'=>'RE', 'Saint Barthélemy'=>'BL', 'Saint Helena, Ascension and Tristan da Cunha'=>'SH', 'Saint Kitts and Nevis'=>'KN', 'Saint Lucia'=>'LC', 'Saint Martin (French part)'=>'MF', 'Saint Pierre and Miquelon'=>'PM', 'Saint Vincent and the Grenadines'=>'VC', 'Samoa'=>'WS', 'San Marino'=>'SM', 'Sao Tome and Principe'=>'ST', 'Saudi Arabia'=>'SA', 'Senegal'=>'SN', 'Serbia'=>'RS', 'Seychelles'=>'SC', 'Sierra Leone'=>'SL', 'Singapore'=>'SG', 'Sint Maarten (Dutch part)'=>'SX', 'Slovakia'=>'SK', 'Slovenia'=>'SI', 'Solomon Islands'=>'SB', 'Somalia'=>'SO', 'South Africa'=>'ZA', 'South Georgia and the South Sandwich Islands'=>'GS', 'South Sudan'=>'SS', 'Spain'=>'ES', 'Sri Lanka'=>'LK', 'Sudan (the)'=>'SD', 'Suriname'=>'SR', 'Svalbard and Jan Mayen'=>'SJ', 'Sweden'=>'SE', 'Switzerland'=>'CH', 'Syrian Arab Republic'=>'SY', 'Taiwan'=>'TW', 'Tajikistan'=>'TJ', 'Tanzania, United Republic of'=>'TZ', 'Thailand'=>'TH', 'Timor-Leste'=>'TL', 'Togo'=>'TG', 'Tokelau'=>'TK', 'Tonga'=>'TO', 'Trinidad and Tobago'=>'TT', 'Tunisia'=>'TN', 'Turkey'=>'TR', 'Turkmenistan'=>'TM', 'Turks and Caicos Islands (the)'=>'TC', 'Tuvalu'=>'TV', 'Uganda'=>'UG', 'Ukraine'=>'UA', 'United Arab Emirates (the)'=>'AE', 'United Kingdom of Great Britain and Northern Ireland (the)'=>'GB', 'United States Minor Outlying Islands (the)'=>'UM', 'Uruguay'=>'UY', 'Uzbekistan'=>'UZ', 'Vanuatu'=>'VU', 'Venezuela (Bolivarian Republic of)'=>'VE', 'Viet Nam'=>'VN', 'Virgin Islands (British)'=>'VG', 'Wallis and Futuna'=>'WF', 'Western Sahara'=>'EH', 'Yemen'=>'YE', 'Zambia'=>'ZM', 'Zimbabwe'=>'ZW', 'Åland Islands'=>'AX'];
$countryflip = array_flip($countries);


if (isset($_POST['postback'])) {
    if ($myrights == 100 && !empty($_POST['ipeddel'])) {
        $qstr = [];
        $qarr = [];
        foreach ($_POST['ipeddel'] as $v) {
            list($type,$ipedsid) = explode('-',$v);
            $qstr[] = '(type=? AND ipedsid=?)';
            $qarr[] = $type;
            $qarr[] = $ipedsid;
        }
        $query = 'DELETE FROM imas_ipeds_group WHERE ('.implode(' OR ', $qstr).') AND groupid=?';
        $qarr[] = intval($grp);
        $stm = $DBH->prepare($query);
        $stm->execute($qarr);
    }
    if ($myrights == 100 && !empty($_POST['otherschool']) && 
        ($_POST['schoolloc']=='intl' && $_POST['intlipeds']=='0')
    ) {
        // create new ipeds record for an intl school 
        if ($_POST['schooltype'] == 'coll') {
            $type = 'W';
        } else {
            $type = 'U';
        }
        $newipedsid = md5($_POST['otherschool'].$_POST['country']);
        $query = 'INSERT INTO imas_ipeds (type,ipedsid,school,country) VALUES (?,?,?,?)';
        $stm = $DBH->prepare($query);
        $stm->execute(array(
            $type, 
            $newipedsid,
            Sanitize::stripHtmlTags($_POST['otherschool']), 
            Sanitize::simpleString($_POST['country'])
        ));
    } else if ($myrights == 100 && !empty($_POST['otherschool']) &&
        (!empty($_POST['otheragency']) || $_POST['schooltype'] != 'pubk12') &&
        ($_POST['schoolloc']=='us' && $_POST['ipeds']=='0')
    ) {
        // create new ipeds record for an intl school 
        if ($_POST['schooltype'] == 'coll') {
            $type = 'I';
            $newipedsid = md5($_POST['otherschool'].'US');
            $agency = '';
        } else if ($_POST['schooltype'] == 'pubk12') {
            $type = 'A';
            $newipedsid = md5($_POST['otheragency'].'US');
            $agency = Sanitize::stripHtmlTags($_POST['otheragency']);
        } else {
            $type = 'S';
            $newipedsid = md5($_POST['otherschool'].'US');
            $agency = '';
        }
        
        $query = 'INSERT INTO imas_ipeds (type,ipedsid,school,agency,country,state) VALUES (?,?,?,?,?,?)';
        $stm = $DBH->prepare($query);
        $stm->execute(array(
            $type, 
            $newipedsid,
            Sanitize::stripHtmlTags($_POST['otherschool']),
            $agency, 
            'US',
            Sanitize::stripHtmlTags($_POST['state'])
        ));
    } else if (!empty($_POST['ipeds']) && $_POST['ipeds'] != '0') {
        list($type,$newipedsid) = explode('-', $_POST['ipeds']);
    } else if (!empty($_POST['intlipeds']) && $_POST['intlipeds'] != '0') {
        list($type,$newipedsid) = explode('-', $_POST['intlipeds']);
    }
    if (!empty($newipedsid) && $newipedsid != '0') {
        $stm = $DBH->prepare('INSERT IGNORE imas_ipeds_group (type,ipedsid,groupid) VALUES (?,?,?)');
		$stm->execute(array($type, $newipedsid, intval($grp)));
    }
    header('Location: ' . $GLOBALS['basesiteurl'] . '/index.php');
    exit;
}

$stm = $DBH->prepare('SELECT name FROM imas_groups WHERE id=?');
$stm->execute(array($grp));
$groupname = $stm->fetchColumn(0);

$query = 'SELECT DISTINCT ii.type,ii.ipedsid,IF(ii.type="A",ii.agency,ii.school) AS name,
    ii.country,ii.state
    FROM imas_ipeds AS ii JOIN imas_ipeds_group AS iig
    ON iig.type=ii.type AND iig.ipedsid=ii.ipedsid WHERE iig.groupid=?';
$stm = $DBH->prepare($query);
$stm->execute(array($grp));
$ipeds = $stm->fetchAll(PDO::FETCH_ASSOC);

$placeinhead .= '<script type="text/javascript" src="'.$staticroot.'/javascript/ipedssearch.js"></script>';
require('../header.php');
echo '<div class=breadcrumb>'.$breadcrumbbase.' IPEDS/NCES Association</div>';
echo '<form method=post action="ipedslink.php">';
if ($myrights == 100) {
    echo '<input type=hidden name=groupid value="'.intval($grp).'"/>';
}
echo '<h1>Group IPEDS / NCES Associations</h1>';
echo '<h2>Group: ' . Sanitize::encodeStringForDisplay($groupname) . '</h2>';
if (!empty($ipeds)) {
    echo '<h3>'._('Current Association:').'</h3>';
    echo '<ul class=nomark>';
    foreach ($ipeds as $iped) {
        $id = Sanitize::encodeStringForDisplay($iped['type'].'-'.$iped['ipedsid']);
        echo '<li><label>';
        if ($myrights == 100) {
            echo '<input type=checkbox name="ipeddel[]" value="'.$id.'"> ';
        }
        echo Sanitize::encodeStringForDisplay($iped['name']).' (';
        if ($iped['type']=='U') {
            echo 'Intl K12, '.$countryflip[$iped['country']];
        } else if ($iped['type']=='W') {
            echo 'Intl HigherEd, '.$countryflip[$iped['country']];
        } else if ($iped['type']=='A') {
            echo 'Public K12, '.$iped['state'];
        } else if ($iped['type']=='S') {
            echo 'Priv K12, '.$iped['state'];
        } else if ($iped['type']=='I') {
            echo 'HigherEd, '.$iped['state'];
        } else if ($iped['type']=='C') {
            echo 'Custom, '.$countryflip[$iped['country']];
        }
        echo ')</label></li>';
    }
    echo '</ul>';
    if ($myrights < 100) {
        echo '<p>If this association is incorrect, please contact the site administrator to have it changed.</p>';
    } else {
        echo '<p>Select an assocation to delete it.</p>';
    }
}
if ($myrights == 100 || empty($ipeds)) {
    ?>
    <h3>Add Association</h3>
    <p><label>School type: <select name=schooltype id=schooltype>
        <option value="">Select...</option>
        <option value="coll">A College or University</option>
        <option value="pubk12">A Public K-12 School</option>
        <option value="privk12">A Private K-12 School</option>
        </select></label>
    <br>
    <label>Location: <select name=schoolloc id=schoolloc>
        <option value="">Select...</option>
        <option value="us">United States or U.S. Territories</option>
        <option value="intl">Outside the United States</option>
        </select></label>
    </p>
    <div id=ussel class=selopt style="display:none">
        <p><label>State: <select id=state name=state>
            <option value="">Select...</option>
        <?php
        $states = ['Alabama'=>'AL', 'Alaska'=>'AK', 'American Samoa'=>'AS', 'Arizona'=>'AZ', 'Arkansas'=>'AR', 'Bureau of Indian Education'=>'BI', 'California'=>'CA', 'Colorado'=>'CO', 'Connecticut'=>'CT', 'Delaware'=>'DE', 'District of Columbia'=>'DC', 'Federated States of Micronesia'=>'FM', 'Florida'=>'FL', 'Georgia'=>'GA', 'Guam'=>'GU', 'Hawaii'=>'HI', 'Idaho'=>'ID', 'Illinois'=>'IL', 'Indiana'=>'IN', 'Iowa'=>'IA', 'Kansas'=>'KS', 'Kentucky'=>'KY', 'Louisiana'=>'LA', 'Maine'=>'ME', 'Marshall Islands'=>'MH', 'Maryland'=>'MD', 'Massachusetts'=>'MA', 'Michigan'=>'MI', 'Minnesota'=>'MN', 'Mississippi'=>'MS', 'Missouri'=>'MO', 'Montana'=>'MT', 'Nebraska'=>'NE', 'Nevada'=>'NV', 'New Hampshire'=>'NH', 'New Jersey'=>'NJ', 'New Mexico'=>'NM', 'New York'=>'NY', 'North Carolina'=>'NC', 'North Dakota'=>'ND', 'Northern Marianas'=>'MP', 'Ohio'=>'OH', 'Oklahoma'=>'OK', 'Oregon'=>'OR', 'Palau'=>'PW', 'Pennsylvania'=>'PA', 'Puerto Rico'=>'PR', 'Rhode Island'=>'RI', 'South Carolina'=>'SC', 'South Dakota'=>'SD', 'Tennessee'=>'TN', 'Texas'=>'TX', 'Utah'=>'UT', 'Vermont'=>'VT', 'Virgin Islands'=>'VI', 'Virginia'=>'VA', 'Washington'=>'WA', 'West Virginia'=>'WV', 'Wisconsin'=>'WI', 'Wyoming'=>'WY'];
        foreach ($states as $name=>$code) {
            echo '<option value='.$code.'>'.$name.'</option>';
        }
        ?>
        </select></label>
        <div id="uswrap">
            <p>
                <span class="collsrc locdesc" style="display:none">
                    Please enter the name of your institution or it's 5-digit ZIP code and click Search,
                    then select your institution from the list.
                </span>
                <span class="pubk12src locdesc" style="display:none">
                    Please enter the name of your school or school district and click Search,
                    then select your school from the list.
                </span>
                <span class="privk12src locdesc" style="display:none">
                    Please enter the name of your school and click Search,
                    then select your school from the list.
                </span>

                <br>
                <input id=searchterms aria-label="school search terms">
                <button type=button id=dosearch>Search</button>
            </p>
            <p id=searchresultwrapper style="display:none">
                <label for=ipeds>Select your institution:</label>
                <br/><select name=ipeds id=ipeds></select>
            </p>
        </div>
    </div>
    <div id=intlsel class=selopt style="display:none">
        <p><label for=country>Select your country:</label>
            <select id=country name=country>
                <option value="">Select...</option>
            <?php
            foreach ($countries as $name=>$code) {
                echo '<option value='.$code.'>'.$name.'</option>';
            }
            ?>
            </select>
        </p>
        <p id=intlwrap style="display:none">
            <label for=intlipeds>Select your institution or affiliation:</label><br>
            <select name=intlipeds id=intlipeds></select>
        </p>
    </div>
    <?php
    if ($myrights == 100) {
        echo '<p id=otherschool style="display:none">
        <label for=otherschool>Give a school name to create a new custom record:</label><br>
        <input name=otherschool size=40 />
        </p>';
        echo '<p id=otheragency style="display:none">
        <label for=otheragency>Give a school district name to create a new custom record:</label><br>
        <input name=otheragency size=40 />
        </p>';
    }
    ?>
    <script type="text/javascript">
    $(function() {
        $('#schooltype').on('change', function () {
            $('.locdesc').hide();
            $('.'+this.value+'src').slideDown();
            $('#country').val('');
            $('#searchresultwrapper').hide();
        });
        $('#schoolloc').on('change', function () {
            $('.selopt').hide();
            $('#'+this.value+'sel').slideDown();
        });
        $('#state').on('change', function () {
            var state = this.value;
            $('#searchresultwrapper').hide();
            if (state != '') {
                $("#uswrap").show();
            } else {
                $("#uswrap").hide();
            }
        });
        $('#searchterms').on('input', function () {
            $('#searchresultwrapper').hide();
        });
        $('#dosearch').on('click', function () {
            ipedssearch({
                type: 'name',
                ipedtypefield: 'schooltype',
                searchfield: 'searchterms',
                resultfield: 'ipeds',
                state: 'state',
                wrapper: 'searchresultwrapper',
                includeselect: true
            });
        });
        $('#ipeds').on('change', function () {
            var val = this.value;
            if (val == '0') {
                $('#otherschool').slideDown();
                if ($('#schooltype').val() == 'pubk12') {
                    $('#otheragency').slideDown();
                } else {
                    $('#otheragency').slideUp();
                }
            } else {
                $('#otherschool,#otheragency').slideUp();
            }
            
        });
        $('#country').on('change', function () {
            var country = this.value;
            if (country != '') {
                ipedssearch({
                    type: 'country',
                    searchfield: 'country',
                    resultfield: 'intlipeds',
                    wrapper: 'intlwrap',
                    includeselect: true
                });
            }
        });
        $("#intlipeds").on('change', function () {
            var val = this.value;
            if (val == '0') {
                $('#otherschool').slideDown();
            } else {
                $('#otherschool').slideUp();
            }
        });
    });
    </script>
    <button type=submit>Update Association</button>
    <input type=hidden name=postback value=1>
    </form>
    <?php
}

require('../footer.php');
