<?php

$errors = [[],[],[]];
$vidids = [];
$vidlocs = [];
$asciisvgpattern = '/^showasciisvg\(\s*((("([^"\\\\]|\\\\.)*"|\'([^\'\\\\]|\\\\.)*\'|[^,()])+,\s*){0,2}("([^"\\\\]|\\\\.)*"|\'([^\'\\\\]|\\\\.)*\'|[^,()])?\s*)?\)$/';
function a11yscan($content, $field, $type, $itemname, $link='',$hasa11yalt=false,$link2=null,$errorlevel=1) {
    global $asciisvgpattern,$vidids,$vidlocs;
    $addederror = false;
    // ensure regex considers \" as well as " to account for encoding
    $content = str_replace(['\\\'','\\"', "'"], ['"','"','"'], $content);
    // look for empty text, or missing alt text.  sloppy, but works
    if (preg_match('/(<img[^>]*?alt="(.*?)"[^>]*?>|<img[^>]*>)/', $content, $matches)) {
        if (!isset($matches[2])) { // used second pattern; missing alt text
            adderror($errorlevel,_('Missing alt text'), $field, $type, $itemname, $link, $link2);
        } else if (trim($matches[2]) == '' && strpos($matches[0], 'role="presentation"') === false) {
            adderror($errorlevel,_('Blank alt text'), $field, $type, $itemname, $link, $link2); 
        }
    }
    // look for asciisvg call with undefined alt text.
    // we'll assume if alt text is defined but blank that it was intentional
    // does not account for use of replacealttext later in the question
    if (!$hasa11yalt && preg_match($asciisvgpattern, $content)) {
        adderror($errorlevel,_('Likely useless auto-generated alt text from showasciisvg'), $field, $type, $itemname, $link, $link2); 
        $addederror = true;
    }
    if (!$hasa11yalt && strpos($content,'textonimage(') !== false && 
        strpos($content,'replacealttext(') === false
    ) {
        // textonimage without replacealttext probably 
        adderror($errorlevel,_('Potential issue: textonimage used without replacealttext'), $field, $type, $itemname, $link, $link2); 
        $addederror = true;
    }
    if (!$hasa11yalt && preg_match('/textonimage\([^\)]*\[AB/', $content) &&
        strpos($content,'readerlabel') === false
    ) {
        //textonimage with AB without readerlabel
        adderror($errorlevel,_('Potential issue: [AB] in textonimage used without readerlabel'), $field, $type, $itemname, $link, $link2); 
        $addederror = true;
    }
    if (!$hasa11yalt && strpos($content,'jsxgraph') !== false && strpos($content,'graphdispmode')===false) {
        adderror($errorlevel,_('Potential issue: question may use jsxgraph; check for accessible alt'), $field, $type, $itemname, $link, $link2); 
        $addederror = true;
    }
    if (!$hasa11yalt && strpos($content,'geogebra') !== false && strpos($content,'graphdispmode')===false) {
        adderror($errorlevel,_('Potential issue: question may use geogebra; check for accessible alt'), $field, $type, $itemname, $link, $link2); 
        $addederror = true;
    }
    // look for youtube videos
    if (preg_match_all('/((youtube\.com|youtu\.be)[^>\s]*?)"/', $content, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $m) {
            if (($vidid = getvideoid($m[1])) !== '') {
                $vidids[] = $vidid;
                $vidlocs[$vidid] = [$field, $type, $itemname, $link];
            }
        }
    }
    if (!$addederror && $errorlevel == 2) {
        adderror($errorlevel,_('Negative accessibility reviews'), $field, $type, $itemname, $link, $link2); 
    }
    return $addederror;
}
function scancolors($items, $parent) {
    global $errors,$cid;
    foreach ($items as $k=>$item) {
        if (is_array($item)) {
            $bnum = $k+1;
            if (!empty($item['colors'])) {
                list($titlebg,$titletext,$blockbg) = explode(',', $item['colors']);
                if (calculateLuminosityRatio($titletext,$titlebg) < 4.5) {
                    adderror(1,
                        _('Insufficient color contrast'), 
                        _('title text and background'), 
                        _('Block'),
                        $item['name'],
                        "course/addblock.php?cid=$cid&id=" . $parent.'-'.$bnum);
                }
            }
            if (!empty($item['items'])) {
                scancolors($item['items'], $parent.'-'.$bnum);
            }
        }
    }
}

function adderror($errorlevel,$descr, $loc, $itemtype, $itemname, $link, $link2 = null) {
    global $errors;
    /*if ($itemtype !== null) {
        if ($link2 !== null) {
            $errors[] = [sprintf('%s in %s', $descr, $loc), $link, 
                sprintf('of %s %s', $itemtype, $itemname), $link2];
        } else {
            $errors[] = [sprintf('%s in %s of %s %s', $descr, $loc, $itemtype, $itemname), $link];
        }
    } else {
        $errors[] = [sprintf('%s in %s', $descr, $loc), $link];
    }*/
    $errors[$errorlevel][] = [$descr, $loc, $itemtype, $itemname, $link2, $link];
}

// From https://github.com/gdkraus/wcag2-color-contrast

// calculates the luminosity of an given RGB color
// the color code must be in the format of RRGGBB
// the luminosity equations are from the WCAG 2 requirements
// http://www.w3.org/TR/WCAG20/#relativeluminancedef

function calculateLuminosity($color) {

    $r = hexdec(substr($color, 0, 2)) / 255; // red value
    $g = hexdec(substr($color, 2, 2)) / 255; // green value
    $b = hexdec(substr($color, 4, 2)) / 255; // blue value
    if ($r <= 0.03928) {
        $r = $r / 12.92;
    } else {
        $r = pow((($r + 0.055) / 1.055), 2.4);
    }

    if ($g <= 0.03928) {
        $g = $g / 12.92;
    } else {
        $g = pow((($g + 0.055) / 1.055), 2.4);
    }

    if ($b <= 0.03928) {
        $b = $b / 12.92;
    } else {
        $b = pow((($b + 0.055) / 1.055), 2.4);
    }

    $luminosity = 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    return $luminosity;
}

// calculates the luminosity ratio of two colors
// the luminosity ratio equations are from the WCAG 2 requirements
// http://www.w3.org/TR/WCAG20/#contrast-ratiodef

function calculateLuminosityRatio($color1, $color2) {
    if ($color1[0]=='#') {
        $color1 = substr($color1,1);
    }
    if ($color2[0]=='#') {
        $color2 = substr($color2,1);
    }
    $l1 = calculateLuminosity($color1);
    $l2 = calculateLuminosity($color2);

    if ($l1 > $l2) {
        $ratio = (($l1 + 0.05) / ($l2 + 0.05));
    } else {
        $ratio = (($l2 + 0.05) / ($l1 + 0.05));
    }
    return $ratio;
}

// returns an array with the results of the color contrast analysis
// it returns akey for each level (AA and AAA, both for normal and large or bold text)
// it also returns the calculated contrast ratio
// the ratio levels are from the WCAG 2 requirements
// http://www.w3.org/TR/WCAG20/#visual-audio-contrast (1.4.3)
// http://www.w3.org/TR/WCAG20/#larger-scaledef

function evaluateColorContrast($color1, $color2) {
    $ratio = calculateLuminosityRatio($color1, $color2);

    $colorEvaluation["levelAANormal"] = ($ratio >= 4.5 ? 'pass' : 'fail');
    $colorEvaluation["levelAALarge"] = ($ratio >= 3 ? 'pass' : 'fail');
    $colorEvaluation["levelAAMediumBold"] = ($ratio >= 3 ? 'pass' : 'fail');
    $colorEvaluation["levelAAANormal"] = ($ratio >= 7 ? 'pass' : 'fail');
    $colorEvaluation["levelAAALarge"] = ($ratio >= 4.5 ? 'pass' : 'fail');
    $colorEvaluation["levelAAAMediumBold"] = ($ratio >= 4.5 ? 'pass' : 'fail');
    $colorEvaluation["ratio"] = $ratio;

    return $colorEvaluation;
}