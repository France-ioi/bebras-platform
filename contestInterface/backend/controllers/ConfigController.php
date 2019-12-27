<?php
// Not used anymore

class ConfigController extends Controller
{

    public function get()
    {
        global $config;
        $clientConfig = array(
            "imagesURLReplacements" => $config->imagesURLReplacements,
            "imagesURLReplacementsNonStatic" => $config->imagesURLReplacementsNonStatic,
            "upgradeToHTTPS" => $config->upgradeToHTTPS,
            "logActivity" => $config->contestInterface->logActivity
        );
        exitWithJson([
            "success" => true,
            "config" => $clientConfig
        ]);
    }
}
