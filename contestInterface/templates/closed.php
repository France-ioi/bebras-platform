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
    <div id="divClosedRemindPassword" style="display:none">
        <p>
            <b data-i18n="closed_remind_password"></b>
        </p>
        <p>
            <span data-i18n="closed_your_password"></span> <span class='selectable' id="remindTeamPassword"></span>
        </p>
        <p id="scoreReminder" style="display:none">
            <span data-i18n="score"></span> <span id="remindScore"></span>
        </p>
    </div>
</div>