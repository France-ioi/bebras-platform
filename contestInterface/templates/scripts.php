<?php script_tag('/bower_components/jquery/jquery.min.js'); ?>

<!--[if lte IE 9]>
<?php
    // JSON3 shim for IE6-9 compatibility.
    script_tag('/bower_components/json3/lib/json3.min.js');
    // Ajax CORS support for IE9 and lower.
    script_tag('/bower_components/jQuery-ajaxTransport-XDomainRequest/jquery.xdomainrequest.min.js');
?>
<![endif]-->

<?php
    // jquery 1.9 is required for IE6+ compatibility.
    script_tag('/bower_components/jquery-ui/jquery-ui.min.js');
    script_tag('/bower_components/jquery-ui-touch-punch/jquery.ui.touch-punch.min.js');
    script_tag('/bower_components/i18next/i18next.min.js');
    script_tag('/bower_components/utf8/utf8.js');
    script_tag('/bower_components/base64/base64.min.js');
    script_tag('/bower_components/pem-platform/task-pr.js');
    script_tag('/raphael-min.js');

    $i18n_config = [
        'lng' => (isset($_SESSION['language']) ? $_SESSION['language'] : $config->defaultLanguage),
        'fallbackLng' => [$config->defaultLanguage],
        'fallbackNS' => 'translation',
        'ns' => [
            'namespaces' => $config->customStringsName ? [$config->customStringsName, 'translation'] : ['translation'],
            'defaultNs' => $config->customStringsName ? $config->customStringsName : 'translation',
        ],
        'getAsync' => true,
        'resGetPath' => static_asset('/i18n/__lng__/__ns__.json')
    ];
?>

<script>
    function updateQueryStringParameter(uri, key, value) {
        var re = new RegExp("([?&])" + key + "=.*?(&|$)", "i");
        var separator = uri.indexOf('?') !== -1 ? "&" : "?";
        if (uri.match(re)) {
            return uri.replace(re, '$1' + key + "=" + value + '$2');
        } else {
            return uri + separator + key + "=" + value;
        }
    }
    window.contestsRoot = <?= json_encode(upgrade_url($config->teacherInterface->sAbsoluteStaticPath . '/contests')) ?>;
    window.sAbsoluteStaticPath = <?= json_encode(upgrade_url($config->teacherInterface->sAbsoluteStaticPath . '/')) ?>;
    window.sAssetsStaticPath = <?= json_encode(upgrade_url($config->teacherInterface->sAssetsStaticPath . '/')) ?>;
    window.timestamp = <?= $config->timestamp ? $config->timestamp : 'null' ?>;
    window.browserIsMobile = <?= $browserIsMobile ? 'true' : 'false' ?>;
    window.contestLoaderVersion = <?= json_encode($config->contestInterface->contestLoaderVersion) ?>;
    try {
        i18n.init(
            <?= json_encode($i18n_config); ?>,
            function() {
                window.i18nLoaded = true;
                $("title").i18n();
                $("body").i18n();
            }
        );
    } catch (e) {
        // assuming s3 was blocked, so add ?p=1 to url, see contestInterface/config.php
        var newLocation = updateQueryStringParameter(window.location.toString(), 'p', '1');
        if (newLocation != window.location.toString()) {
            window.location = newLocation;
        }
    }
    window.ieMode = false;
</script>

<?php
    script_tag('/build/app.js');
?>

<!--[if IE 6]>
<script>
    window.sAbsoluteStaticPath = <?= json_encode(upgrade_url($config->teacherInterface->sAbsoluteStaticPathOldIE . '/')) ?>;
    window.contestsRoot = <?= json_encode(upgrade_url($config->teacherInterface->sAbsoluteStaticPathOldIE . '/contests')) ?>;
</script>
<![endif]-->

<!--[if lte IE 9]>
<script>
    window.ieMode = true;
</script>
<![endif]-->