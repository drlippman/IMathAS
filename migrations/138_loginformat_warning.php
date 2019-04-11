<?php

echo "<p style='color: red;'>✓ NOTE: To avoid validation issues with LTI-created user accounts on the Change Student Info page, ";
echo "edit your config.php and ensure your \$loginformat allows usernames in the format <code>lti-\\d+</code></p>";

return true;
