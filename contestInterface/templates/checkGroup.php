<div id="divCheckGroup" class="dialog">
    <div id="tab-home" style="display:none" class="tabContent">
        <div class="tabTitle" data-i18n="general_public_contests"></div>
        <!-- Display the first div during the contest week -->
        <div id="warningPublicGroups" style="display:none;background:#F33;width:650px;text-align:center;padding:5px;margin:10px">
            <p><b data-i18n="tab_home_warning_not_contest"></b></p>
            <button type="button" onclick="selectMainTab('school');return false;" data-i18n="[html]tab_home_button_wrong_choice"></button>
            <button type="button" onclick="confirmPublicGroup();return false;" data-i18n="[html]tab_home_button_confirm_choice"></button>
        </div>
        <div id="publicGroups" style="display:block">
            <span id="loadPublicGroups" style="color:red; display: none;" data-i18n="tab_public_loading"></span>
            <div id="contentPublicGroups" style="display:none;width:800px">
                <p><b data-i18n="[html]tab_public_contests_info"></b></p>
                <p id="publicContestExplanation"></p>
                <div id="listPublicGroups"></div>
                <p data-i18n="[html]tab_public_contests_organization"></p>
            </div>
        </div>
    </div>

    <div id="tab-continue" style="display:none" class="tabContent">
        <div class="tabTitle" data-i18n="general_continue_contest"></div>
        <p>
            <span data-i18n="tab_view_results_access_code"></span>
        </p>
        <div class="divInput form-inline">
            <input id="interruptedPassword" type="text" class="form-control" autocorrect="off" autocapitalize="none">
            <button type="button" id="buttonInterrupted" class="btn btn-default" onclick="checkPasswordInterrupted()" data-i18n="tab_view_results_view_results_button"></button>
            <div>
                <span id="InterruptedResult" style="color:red"></span>
            </div>
        </div>

        <p data-i18n="tab_view_results_info_1"></p>
        <p><b data-i18n="tab_view_results_info_2"></b></p>
        <p data-i18n="tab_view_results_info_3"></p>
        <p data-i18n="tab_view_results_info_4"></p>
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

    <div id="tab-contests" style="display:none" class="tabContent">
        <div class="tabTitle" data-i18n="general_view_other_contests"></div>
        <div data-i18n="[html]tab_view_other_contests"></div>
    </div>
</div>