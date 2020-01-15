<div id="preloadPage" style="display: none">
    <div class="section">
        <div class="tabTitle" data-i18n="home_preload_title"></div>
        <div class="divInput form-inline">
            <button type="button" onclick="preloadPageNavBack()" data-i18n="back" class="btn btn-primary"></button>
        </div>
        <div class="divInput form-inline">
            <input value="8ki94wyg" id="preloadPageCode" type="text" class="form-control" autocorrect="off" autocapitalize="none" />
            <button type="button" id="preloadPageCodeBtn" onclick="preloadPageAddCode()" data-i18n="preload_page_btn_add" class="btn btn-primary"></button>
        </div>
        <div id="preloadCodeResult"></div>
        <div id="preloadPageData" style="display: none">
            <p>
                <span data-i18n="preload_page_list_title"></span>
                <span id="preloadedCodesList"></span>
            </p>
            <button type="button" onclick="preloadPageClear()" data-i18n="preload_page_btn_clear" class="btn btn-primary"></button>
        </div>
    </div>
</div>