<div class="login_box panel">
    <div class="panel-head">
        <b data-i18n="login_teammate"></b><b> 1</b>
    </div>
    <div class="panel-body">

        <div id="askRegistrationCode1">
            <span data-i18n="login_has_registrationCode"></span>
            <span class='btn-group' style='margin-left: 10px;'>
                <button type="button" class='btn btn-default yesno' onclick='hasRegistration(1, true)' id="hasReg1Yes" data-i18n="yes"></button>
                <button type="button" class='btn btn-default yesno' onclick='hasRegistration(1, false)' id="hasReg1No" data-i18n="no"></button>
            </span>
        </div>
        <div id="yesRegistrationCode1" style="text-align:center;display:none" class="form-inline">
            <p id="login-input-registrationCode-1">
                <span data-i18n="[html]login_input_registrationCode"></span>
                <input id="registrationCode1" type="text" autocomplete="off" class="form-control" />
            </p>
            <p>
                <i data-i18n="login_registrationCode_description"></i>
            </p>
            <button id="validateRegCode1" type='button' onclick="validateRegistrationCode(1)" class="btn btn-default" data-i18n="login_validate_code"></button>
            <p>
                <span id="errorRegistrationCode1" style="color:red;font-weight:bold"></span>
            </p>
        </div>
        <div id="noRegistrationCode1" style="display:none" class="form-inline">
            <p id="login-input-firstName-1">
                <span data-i18n="[html]login_input_firstname"></span>
                <input id="firstName1" type="text" autocomplete="off" class="form-control" />
            </p>
            <p id="login-input-lastName-1">
                <span data-i18n="[html]login_input_lastname"></span>
                <input id="lastName1" type="text" autocomplete="off" class="form-control" />
            </p>
            <p id="login-input-email-1">
                <span data-i18n="[html]login_input_email"></span>
                <input id="email1" type="text" autocomplete="off" class="form-control" />
            </p>
            <p id="login-input-zipCode-1">
                <span data-i18n="[html]login_input_zipCode"></span>
                <input id="zipCode1" type="text" autocomplete="off" class="form-control" />
            </p>
            <div id="login-input-genre-1">
                <span data-i18n="login_ask_gender"></span>
                <br />
                <div class="divInput">
                    <input type="radio" id="genre1_male" name="genre1" value="2" autocomplete="off"><label for="genre1_male" data-i18n="login_male"></label><br />
                    <br />
                    <input type="radio" id="genre1_female" name="genre1" value="1" autocomplete="off"><label for="genre1_female" data-i18n="login_female"></label>
                </div>
            </div>
            <p id="login-input-grade-1">
                <span data-i18n="grade_question"></span>
                <select id="grade1">
                    <option value="" data-i18n="grade_select" selected></option>
                    <?php
                        foreach ($config->grades as $grade) {
                            echo "<option value='" . $grade . "' data-i18n='grade_" . $grade . "'></option>";
                        }
                    ?>
                </select>
            </p>
            <p id="login-input-studentId-1">
                <span data-i18n="[html]login_input_studentId"></span>
                <input id="studentId1" type="text" autocomplete="off" class="form-control" />
            </p>
        </div>
    </div>
</div>