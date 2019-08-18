<div id="divHeader">
    <div id="leftTitle" data-i18n="[html]left_title"></div>
    <div id="headerGroup">
        <h1 id="headerH1" data-i18n="general_title"></h1>
        <h2 id="headerH2" data-i18n="general_subtitle"></h2>
        <p id="login_link_to_home" data-i18n="[html]general_instructions"></p>
    </div>
    <div class="language_panel">
        <span data-i18n="general_interface_language"></span>
        <select id="interface_language" onchange="changeInterfaceLanguage()">
            <?php
                $lang = isset($_SESSION["language"]) ? $_SESSION["language"] : null;
                if (!isset($config->contestInterface->languages[$lang])) {
                    $lang = $config->defaultLanguage;
                }
                foreach ($config->contestInterface->languages as $k => $name) {
                    echo "<option value=\"" . $k . "\"" . ($k == $lang ? " selected=\"selected\"" : "") . ">" . $name . "</option>";
                }
            ?>
        </select>
    </div>
</div>