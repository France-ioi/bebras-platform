<div id="divClosed" style="display:none" class="dialog" autocomplete="off">
    <h3 id="divClosedMessage"></h3>
    <div id="divClosedPleaseWait" style="display:none">
        <p style='margin:200px 0 200px 0' data-i18n="[html]closed_please_wait"></p>
    </div>
    <div id="divClosedEncodedAnswers" style="display:none">
        <p data-i18n="[html]closed_connexion_error"></p>
        <textarea cols=60 rows=20 id="encodedAnswers"></textarea>
        <button type="button" onclick="saveEncodedAnswers()" data-i18n="download_encoded_answers" class="btn btn-primary"></button>
    </div>
    <div id="divClosedReminder" style="display:none">
        <div id="closedReminderPassword" style="display: none">
            <p>
                <b data-i18n="closed_remind_password"></b>
            </p>
            <p>
                <span data-i18n="closed_your_password"></span>
                <span class='selectable' id="remindTeamPassword"></span>
            </p>
        </div>
        <p id="scoreReminder" style="display:none">
            <span data-i18n="score"></span> <span id="remindScore"></span>
        </p>
        <p id="closedReminderNav" style="display: none">
            <button type="button" onclick="navPersonalPage()" data-i18n="closed_reminder_personal_page" class="btn btn-primary"></button>
            <button type="button" onclick="navLogout()" data-i18n="closed_reminder_logout" class="btn btn-primary"></button>
        </p>
    </div>
</div>