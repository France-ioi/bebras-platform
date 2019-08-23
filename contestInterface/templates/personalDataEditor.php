<div id="divPersonalDataEditor" style="display:none">
    <div class="panel" cellspacing=0>
        <div class="panel-head"><b data-i18n="personal_data_edit"></b></div>
        <div class="panel-body">
            <div class="form-inline">
                <p>
                    <span data-i18n="[html]login_input_firstname"></span>
                    <input id="pde_firstName" type="text" autocomplete="off" class="form-control" />
                    <span id="pde_firstName_confirmed" class="alert confirmed_value">*</span>
                </p>
                <p>
                    <span data-i18n="[html]login_input_lastname"></span>
                    <input id="pde_lastName" type="text" autocomplete="off" class="form-control" />
                    <span id="pde_lastName_confirmed" class="alert confirmed_value">*</span>
                </p>
                <p>
                    <span data-i18n="[html]login_input_email"></span>
                    <input id="pde_email" type="text" autocomplete="off" class="form-control" />
                    <span id="pde_email_confirmed" class="alert confirmed_value">*</span>
                </p>
                <p>
                    <span data-i18n="[html]login_input_zipCode"></span>
                    <input id="pde_zipCode" type="text" autocomplete="off" class="form-control" />
                    <span id="pde_zipCode_confirmed" class="alert confirmed_value">*</span>
                </p>
                <div>
                    <span data-i18n="login_ask_gender"></span>
                    <span id="pde_genre_confirmed" class="alert confirmed_value">*</span>
                    <br />
                    <div class="divInput">
                        <input type="radio" id="pde_male" name="pde_genre" value="2" autocomplete="off"><label for="pde_male" data-i18n="login_male"></label>
                        <br /><input type="radio" id="pde_female" name="pde_genre" value="1" autocomplete="off"><label for="pde_female" data-i18n="login_female"></label>
                    </div>
                </div>
                <p>
                    <span data-i18n="grade_question"></span>
                    <select id="pde_grade">
                        <option value="" data-i18n="grade_select" selected></option>
                        <?php
                            foreach ($config->grades as $grade) {
                                echo "<option value='" . $grade . "' data-i18n='grade_" . $grade . "'></option>";
                            }
                        ?>
                    </select>
                    <span id="pde_grade_confirmed" class="alert confirmed_value">*</span>
                </p>
                <p>
                    <span data-i18n="[html]login_input_studentId"></span>
                    <input id="pde_studentID" type="text" autocomplete="off" class="form-control" />
                    <span id="pde_studentID_confirmed" class="alert confirmed_value">*</span>
                </p>
            </div>
        </div>
    </div>
    <div class="clearfix">
        <button type="button" id="buttonPersonalDataEditorSubmit" onclick="personalDataEditorSubmit()" data-i18n="save" class="btn btn-default"></button>
        <button type="button" id="buttonPersonalDataEditorCancel" onclick="personalDataEditorCancel()" data-i18n="cancel" class="btn btn-default"></button>
        <p><span id="personalDataEditorResult" style="color:red;font-weight:bold"></span></p>
    </div>
</div>