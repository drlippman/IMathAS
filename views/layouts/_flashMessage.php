<div id="flash-message">
    <?php
    $flashes = Yii::$app->session->getAllFlashes();
    if (isset($flashes)) {
        foreach (Yii::$app->session->getAllFlashes() as $key => $message) {
            echo '<div class="alert alert-' . $key . '">' . $message . "</div>\n";
        }
    }
    ?>
</div>