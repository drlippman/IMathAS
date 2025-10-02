<?php

// html markup macros

array_push(
    $GLOBALS['allowedmacros'],
    'formhoverover',
    'formpopup',
    'forminlinebutton'
);

function formhoverover($label, $tip) {
    if (function_exists('filter')) {
        $tip = filter($tip);
    }
    $tip = htmlentities($tip);
    $tip = str_replace('`', '&#96;', $tip);
    return '<span role="button" tabindex="0" class="link" data-tip="' . $tip . '" onmouseover="tipshow(this)" onfocus="tipshow(this)" onmouseout="tipout()" onblur="tipout()">' . $label . '</span>';
}

function formpopup($label, $content, $width = 600, $height = 400, $type = 'link', $scroll = 'null', $id = 'popup', $ref = '', $presanitized = false) {
    global $urlmode;
    if (!$presanitized) {
        $labelSanitized = Sanitize::encodeStringForDisplay($label);
    } else {
        $labelSanitized = $label;
    }
    if (!is_scalar($content)) {
        echo "invalid content in formpopup";
        return '';
    }
    if (!is_scalar($label)) {
        echo "invalid label in formpopup";
        return '';
    }
    if (is_array($width)) {
        echo "width should not be array in formpopup";
        $width = 600;
    }

    if ($scroll != null) {
        $scroll = ',' . $scroll;
    }
    if ($height == 'fit') {
        $height = "'fit'";
    }
    if ($ref != '') {
        if (strpos($ref, '-') !== false) {
            $ref = explode('-', $ref);
            $contentadd = 'Q' . $ref[1] . ': ';
            $ref = $ref[0];
        } else {
            $contentadd = '';
        }
        if (strpos($content, 'watchvid.php') !== false) {
            $cp = explode('?url=', $content);
            $rec = "recclick('extref',$ref,'" . $contentadd . trim(htmlentities(urldecode($cp[1]))) . "');";
        } else {
            $rec = "recclick('extref',$ref,'" . $contentadd . trim(htmlentities($content)) . "');";
        }
    } else {
        $rec = '';
    }
    if (strpos($label, '<img') !== false) {
        return '<button type="button" class="nopad plain" onClick="' . $rec . 'popupwindow(\'' . $id . '\',\'' . Sanitize::encodeStringForJavascript($content) . '\',' . $width . ',' . $height . $scroll . ')">' . $label . '</button>';
    } else {
        if ($type == 'link') {
            return '<a href="#" onClick="' . $rec . 'popupwindow(\'' . $id . '\',\'' . Sanitize::encodeStringForJavascript($content) . '\',' . $width . ',' . $height . $scroll . ');return false;">' . $labelSanitized . '</a>';
        } else if ($type == 'button') {
            if (substr($content, 0, 31) == 'http://www.youtube.com/watch?v=') {
                $content = $GLOBALS['basesiteurl'] . "/assessment/watchvid.php?url=" . Sanitize::encodeUrlParam($content);
                $width = 660;
                $height = 525;
            }
            return '<button type="button" onClick="' . $rec . 'popupwindow(\'' . $id . '\',\'' . Sanitize::encodeStringForJavascript($content) . '\',' . $width . ',' . $height . $scroll . ')">' . $labelSanitized . '</button>';
        }
    }
}

function forminlinebutton($label, $content, $style = 'button', $outstyle = 'block') {
    if (!is_scalar($content)) {
        echo "invalid content in forminlinebutton";
        return '';
    }
    if (!is_scalar($style)) {
        echo "invalid style in forminlinebutton";
        return '';
    }
    if (!is_scalar($label)) {
        echo "invalid label in forminlinebutton";
        return '';
    }

    $r = uniqid();
    $label = str_replace('"', '', $label);
    $common = 'id="inlinebtn' . $r . '" aria-controls="inlinebtnc' . $r . '" aria-expanded="false" onClick="toggleinlinebtn(\'inlinebtnc' . $r . '\', \'inlinebtn' . $r . '\');return false;"';
    if ($style == 'link') {
        $out = '<a href="#" ' . $common . '>' . $label . '</a>';
    } else {
        $out = '<button type="button" ' . $common . '>' . $label . '</button>';
    }
    if ($outstyle == 'inline') {
        $out .= ' <span id="inlinebtnc' . $r . '" style="display:none;" aria-hidden="true">' . $content . '</span>';
    } else {
        $out .= '<div id="inlinebtnc' . $r . '" style="display:none;" aria-hidden="true">' . $content . '</div>';
    }
    return $out;
}
