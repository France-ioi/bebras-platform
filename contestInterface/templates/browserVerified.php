<?php
    if (!$browserVerified) {
        // The message changes depending on the browserCheck value
        echo '<div id="browserAlert" data-i18n="[html]browser_support_' . $config->contestInterface->browserCheck . '"></div>';
    }
?>