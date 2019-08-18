<div id="divAccessContest" style="display:none">
    <div id="selection-breadcrumb"></div>

    <?php include('selectCategory.php'); ?>
    <?php include('selectLanguage.php'); ?>
    <?php include('selectContest.php'); ?>

    <div id="divDescribeTeam" style="display:none" class="contestSelection-tab">
        <div class="tabTitle" data-i18n="team_nb_members"></div>
        <div id="divCheckNbContestants" style="display:none">
            <p>
                <span data-i18n="nb_contestants_question"></span>
                <span class="btn-group" style="margin-left: 20px;">
                    <button type="button" data-nbcontestants="1" class="btn btn-default nbContestants" data-i18n="nb_contestants_one"></button>
                    <button type="button" data-nbcontestants="2" class="btn btn-default nbContestants" data-i18n="nb_contestants_two"></button>
                </span>
            </p>
        </div>

        <div id="divLogin" style="display:none" class="dialog">
            <?php include('login/teammate1.php'); ?>
            <?php include('login/teammate2.php'); ?>
            <div class="clearfix">
                <button type="button" id="buttonLogin" onclick="validateLoginForm()" data-i18n="login_start_contest" class="btn btn-default"></button>
                <p>
                    <span id="LoginResult" style="color:red;font-weight:bold"></span>
                </p>
            </div>
        </div>
    </div>
</div>