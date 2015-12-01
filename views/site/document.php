

<?php
use app\assets\AppAsset;
echo '<div class="tab-content shadowBox padding-left-right-twenty padding-top-bottom-two-em">';
   $homepage = file_get_contents('../docs/docs.php');
    print_r($homepage);
echo '</div>';
?>


