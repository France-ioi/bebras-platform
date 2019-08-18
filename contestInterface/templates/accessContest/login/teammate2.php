<div class="login_box panel" cellspacing=0 id="contestant2" style="display:none">
    <div class="panel-head">
        <b data-i18n="login_teammate"></b><b> 2</b>
    </div>
    <div class="panel-body">
        <div id="askRegistrationCode2">
            <span data-i18n="login_has_registrationCode"></span>
            <span class='btn-group' style='margin-left: 10px;'>
                <button type="button" class='btn btn-default yesno' onclick='hasRegistration(2, true)' id="hasReg2Yes" data-i18n="yes"></button>
                <button type="button" class='btn btn-default yesno' onclick='hasRegistration(2, false)' id="hasReg2No" data-i18n="no"></button>
            </span>
        </div>
        <div id="yesRegistrationCode2" style='text-align:center;display:none' class="form-inline">
            <p id="login-input-registrationCode-2">
                <span data-i18n="[html]login_input_registrationCode"></span>
                <input id="registrationCode2" type="text" autocomplete="off" class="form-control" />
            </p>
            <p>
                <i data-i18n="login_registrationCode_description"></i>
            </p>
            <button id="validateRegCode2" type='button' onclick="validateRegistrationCode(2)" class="btn btn-default" data-i18n="login_validate_code"></button>
            <p>
                <span id="errorRegistrationCode2" style="color:red;font-weight:bold"></span>
            </p>
        </div>
        <div id="noRegistrationCode2" style="display:none" class="form-inline">
            <p id="login-input-firstName-2">
                <span data-i18n="[html]login_input_firstname"></span>
                <input id="firstName2" type="text" autocomplete="off" class="form-control" />
            </p>
            <p id="login-input-lastName-2">
                <span data-i18n="[html]login_input_lastname"></span>
                <input id="lastName2" type="text" autocomplete="off" class="form-control" />
            </p>
            <p id="login-input-email-2">
                <span data-i18n="[html]login_input_email"></span>
                <input id="email2" type="text" autocomplete="off" class="form-control" />
            </p>
            <p id="login-input-zipCode-2">
                <span data-i18n="[html]login_input_zipCode"></span>
                <input id="zipCode2" type="text" autocomplete="off" class="form-control" />
            </p>
            <div id="login-input-genre-2">
                <span data-i18n="login_ask_gender"></span>
                <br />
                <div class="divInput">
                    <input type="radio" id="genre2_male" name="genre2" value="2" autocomplete="off"><label for="genre2_male" data-i18n="login_male"></label>
                    <br />
                    <input type="radio" id="genre2_female" name="genre2" value="1" autocomplete="off"><label for="genre2_female" data-i18n="login_female"></label>
                </div>
            </div>
            <p id="login-input-grade-2">
                <span data-i18n="grade_question"></span>
                <select id="grade2">
                    <option value="" data-i18n="grade_select" selected></option>
                    <?php
                    foreach ($config->grades as $grade) {
                        echo "<option value='" . $grade . "' data-i18n='grade_" . $grade . "'></option>";
                    }
                    ?>
                    </select>
            </p>
            <p id="login-input-studentId-2">
                <span data-i18n="[html]login_input_studentId"></span>
                <input id="studentId2" type="text" autocomplete="off" class="form-control" />
            </p>
        </div>
    </div>
</div>