<div id="divPersonalPage" style="display:none">
    <div id="pp_regular_mode">
        <h2 data-i18n="personal_data_title_regular"></h2>
        <p>
            <button type="button" onclick="personalPageDataEdit()" class="btn btn-primary" data-i18n="personal_data_edit"></button>
            <table id="personalData">
                <tr>
                    <td data-i18n="personal_data_lname"></td>
                    <td id="persoLastName"></td>
                </tr>
                <tr>
                    <td data-i18n="personal_data_fname"></td>
                    <td id="persoFirstName"></td>
                </tr>
                <tr id="pp_row_grade">
                    <td data-i18n="personal_data_grade"></td>
                    <td id="persoGrade"></td>
                </tr>
                <tr id="pp_row_category">
                    <td data-i18n="personal_data_category"></td>
                    <td id="persoCategory"></td>
                </tr>
            </table>
        </p>
    </div>
    <div id="pp_guest_mode">
        <h2 data-i18n="personal_data_title_guest"></h2>
        <p>
            <button type="button" onclick="personalPageCreateAccount()" class="btn btn-primary" data-i18n="personal_data_create_account"></button>
        </p>
    </div>
</div>