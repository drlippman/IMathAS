<?php

require('../init.php');
$flexwidth = true;
$nologo = true;
$placeinhead = '<script>
function doreview(val) {
    window.parent.senda11yreview(val);
    window.parent.GB_hide();
}
</script>';
$pagetitle = _('Accessibility Review');
require('../header.php');

//echo '<h1>'._('Accessibility Review').'</h1>';

echo '<p>'._('Before leaving an accessibility review, please make sure you have checked the question for common issues:').'</p>';
echo '<ul>';
echo '<li>'._('Ensure color is the not the only way used to identify something (like "in the red graph") with no other labels.').'</li>';
echo '<li>'._('Ensure there is sufficient contrast between text and background colors if either have been changed from defaults.').'</li>';
echo '<li>'._('If there is an auto-generated image, use the "test with accessibility settings" to view the accessible alternative, and ensure it is sufficient to answer the question.').'</li>';
echo '<li>'._('If a question has a static image (jpg, gif, png), ensure it has alt-text that is sufficient to answer the question. You may need to use the "inspect" feature in your browser to view the alt text.').'</li>';
echo '<li>'._('If a question has answerboxes floated over an image, ensure it uses $readerlabel in the code to define the meaning of each box, or use "inspect" to ensure each has an aria-label that is sufficient to understand what it is the input for.').'</li>';
echo '<li>'._('If a question uses a visual interactive, like jsxgraph, geogebra, or simulation, ensure an accessible alternate has been specified, or use the "test with accessibility settings" to ensure an accessible option has been coded in.').'</li>';
echo '<li>'._('Ensure tables are not used for layout without a role=presentation present. (This does not apply to data tables). If the table cells stack when you shrink the window width, it has the right role.').'</li>';
echo '<li>'._('Any videos embedded in the question should have manually-edited captions. Please do not leave a negative review for attached "help" videos without captions, since those can be disabled by the person using the question.').'</li>';
echo '</ul>';

echo '<p>'._('If the question appears to be accessible, to the best of your understanding, leave review indiciting it "looks good".').'</p>';
echo '<p><button onclick="doreview(1)">'._('Leave "Looks Good" Review').'</button></p>';

echo '<p>'._('If the question does not appear to be accessible, leave a review indicating it "needs work", then use the "Message Owner" option to send a message to the owner letting them know what you found so they can work on fixing it.').'</p>';
echo '<p><button onclick="doreview(0)">'._('Leave "Needs Work" Review').'</button></p>';

echo '<p>'._('If you are not sure, you can close this window without leaving a review.').'</p>';
require_once "../footer.php";
exit;