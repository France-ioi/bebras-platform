<?php

class LanguageController extends Controller
{

    public function set($request) {
        global $config;
        $lang = isset($request["language"]) ? $request["language"] : null;
        if (!isset($config->contestInterface->languages[$lang])) {
            $lang = $config->defaultLanguage;
        }

        $_SESSION["language"] = $lang;
        exitWithJson(array("success" => true));
    }

}