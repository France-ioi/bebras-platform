<?php

class LanguageController extends Controller
{

    public function set() {
        global $config;
        $lang = isset($_POST["language"]) ? $_POST["language"] : null;
        if (!isset($config->contestInterface->languages[$lang])) {
            $lang = $config->defaultLanguage;
        }

        $_SESSION["language"] = $lang;
        exitWithJson(array("success" => true));
    }

}