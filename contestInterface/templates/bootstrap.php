<?php
include(__DIR__ . '/../config.php');
header('Content-type: text/html');
session_name('contest2');
session_start();

// Check browser parameters
$browserVerified = true;
$browserOld = false;
if ($config->contestInterface->browserCheck) {
    require_once __DIR__ . '/../../vendor/autoload.php';
    $browser = new WhichBrowser\Parser($_SERVER['HTTP_USER_AGENT']);
    if ($config->contestInterface->browserCheck == 'bebras-platform') {
        $browserVerified = $browser->isBrowser('Firefox', '>=', '3.6') ||
            $browser->isBrowser('Chrome', '>=', '5') ||
            $browser->isBrowser('Silk', '>=', '5') ||
            $browser->isBrowser('Safari', '>=', '9') ||
            $browser->isBrowser('Internet Explorer', '>=', '8') ||
            $browser->isBrowser('Edge');
    } elseif ($config->contestInterface->browserCheck == 'quickAlgo') {
        $browserVerified = $browser->isBrowser('Firefox', '>=', '43') ||
            $browser->isBrowser('Chrome', '>=', '35') ||
            $browser->isBrowser('Silk', '>=', '35') ||
            $browser->isBrowser('Safari', '>=', '9') ||
            $browser->isBrowser('Edge', '>=', '12');
    }
    $browserOld = $browser->isBrowser('Firefox', '<', '60') ||
        $browser->isBrowser('Chrome', '<', '64') ||
        $browser->isBrowser('Silk', '<', '64') ||
        $browser->isBrowser('Safari', '<', '9') ||
        $browser->isBrowser('Edge', '<', '41') ||
        $browser->isBrowser('Internet Explorer');
}
$browserIsMobile = $browser->isType('mobile', 'tablet', 'ereader');