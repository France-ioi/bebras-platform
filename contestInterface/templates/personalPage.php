<div id="divPersonalPage" style="display:none">
    <h2 data-i18n="personal_data_title"></h2>
    <p>
        <button type="button" onclick="personalDataEdit()" class="btn btn-primary" data-i18n="personal_data_edit"></button>
        <table id="personalData">
            <tr>
                <td data-i18n="personal_data_lname"></td>
                <td id="persoLastName"></td>
            </tr>
            <tr>
                <td data-i18n="personal_data_fname"></td>
                <td id="persoFirstName"></td>
            </tr>
            <tr>
                <td data-i18n="personal_data_grade"></td>
                <td id="persoGrade"></td>
            </tr>
            <tr>
                <td data-i18n="personal_data_category"></td>
                <td id="persoCategory"></td>
            </tr>
        </table>
    </p>
    <p>
        <table>
            <tr>
                <td><button type="button" id="buttonStartPreparation" onclick="startPreparation()" class="btn btn-primary">Démarrer une préparation</button></td>
                <td style="width:50px">
                <td><button type="button" id="buttonStartContest" onclick="startContest()" class="btn btn-primary">Démarrer le concours</button></td>
            </tr>
        </table>
    </p>
    <p id="contestAtHomePrevented" style="display:none">
    Votre enseignant a indiqué que le concours officiel doit se faire en classe, avec un code de groupe.<br />
    Vous ne pouvez donc pas commencer le concours depuis cette interface, mais vous pouvez faire des préparations à la maison.
    </p>
    <h3>Participations :</h3>
    <table id="pastParticipations" cellspacing=0>
        <tr>
            <td>Épreuve</td>
            <td>Date</td>
            <td>Équipe</td>
            <td>Statut</td>
            <td>Score</td>
            <td>Classement</td>
            <td>Classement<br />établissement</td>
            <td>Accès</td>
        </tr>
    </table>
</div>