<div id="homePage">
    <div class="section">
        <div id="submitParticipationCode" <?= (!$browserVerified || $browserOld) ? 'class="needBrowserConfirm"' : '' ?>>
            <div class="browserConfirm">
                <span data-i18n="[html]<?= $browserVerified ? 'browser_support_old' : 'browser_support_confirm' ?>"></span><br>
                <button type="button" onclick="confirmUnsupportedBrowser()" data-i18n="browser_support_confirm_btn" class="btn btn-primary"></button>
            </div>
            <div class="tabTitle" data-i18n="home_code_header"></div>
            <p class="stepName" data-i18n="home_code_text"></p>
            <div class="divInput form-inline">
                <input value="8ki94wyg" id="groupCode" type="text" class="form-control" autocorrect="off" autocapitalize="none" />
                <button type="button" onclick="checkGroup()" data-i18n="home_code_submit" class="btn btn-primary"></button>
                <div>
                    <span id="CheckGroupResult" style="color:red"></span>
                </div>
            </div>
        </div>
        <div id="recoverGroup" style="display:none;">
            <p data-i18n="[html]group_session_expired_recover"></p>
            <div class="divInput form-inline">
                <input id="recoverGroupPass" type="password" class="form-control" autocorrect="off" autocapitalize="none" />
                <button type="button" id="buttonRecoverGroup" onclick="recoverGroup()" data-i18n="submitPass" class="btn btn-default"></button>
                <div>
                    <span id="recoverGroupResult" style="color:red"></span>
                </div>
            </div>
            <p data-i8n="[html]others_retry"></p>
        </div>
    </div>

    <div class="section">
        <div class="tabTitle" data-i18n="home_nocode_header"></div>
        <div class="divInput form-inline">
            <button type="button" onclick="createGuest()" class="btn btn-primary" data-i18n="home_nocode_guest"></button>
            <button type="button" onclick="registerUser()" class="btn btn-primary" data-i18n="home_nocode_register"></button>
        </div>
    </div>

    <div class="section">
        <div class="tabTitle" data-i18n="home_continue_header"></div>
        <p class="stepName" data-i18n="home_continue_text1"></p>
        <p class="stepName" data-i18n="home_continue_text2"></p>
        <div class="divInput form-inline">
            <input id="interruptedPassword" type="text" class="form-control" autocorrect="off" autocapitalize="none" />
            <button type="button" id="buttonInterrupted" onclick="checkPasswordInterrupted()" data-i18n="home_continue_submit" class="btn btn-primary"></button>
            <div><span id="InterruptedResult" style="color:red"></span></div>
        </div>
        <div id="divRelogin" style="display:none">
            <p data-i18n="tab_view_select_team_in_list"></p>
            <div class="divInput">
                <select id="selectTeam">
                    <option value='0' data-i18n="tab_view_select_team"></option>
                </select>
            </div>
            <p data-i18n="tab_view_ask_password_to_teacher"></p>
            <div class="divInput form-inline">
                <input id="groupPassword" type="password" class="form-control" autocorrect="off" autocapitalize="none">
                <button type="button" id="buttonRelogin" class="btn btn-default" onclick="relogin()" data-i18n="tab_view_restart_contest"></button>
                <div>
                    <span id="ReloginResult" style="color:red"></span>
                </div>
            </div>
        </div>
    </div>

    <div class="section" id="homePagePreloadSection">
        <div class="tabTitle" data-i18n="home_preload_title"></div>
        <div class="divInput form-inline">
            <button type="button" onclick="showPreloadPage()" class="btn btn-primary" data-i18n="home_preload_page_link"></button>
        </div>
    </div>
</div>
