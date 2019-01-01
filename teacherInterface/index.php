<?php
  include('./config.php');
  header('Content-type: text/html');
?><!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link rel="shortcut icon" href="<?= $config->faviconfile ?>" />
<title data-i18n="page_title"></title>
<?php stylesheet_tag('/bower_components/jquery-ui/themes/base/jquery-ui.min.css'); ?>
<?php stylesheet_tag('/bower_components/jqgrid/css/ui.jqgrid.css'); ?>
<?php stylesheet_tag('/admin.css'); ?>
</head>
<body>
<div id="divHeader">
  <div id="leftTitle" data-i18n="[html]main_logo"></div>
  <div id="headerGroup">
    <h1 id="headerH1" data-i18n="title"></h1>
    <h2 id="headerH2" data-i18n="[html]subtitle"></h2>
    <button type="button" id="logoutLink" style="display:none;" onclick="logout()" class="btn"><span data-i18n="logout"></span></button>
    <p id="login_link_to_home" data-i18n="[html]login_link_to_home"></p>
  </div>
</div>

<form autocomplete="off">
   <div id="edit_form" style="display:none;" class="dialog">
   </div>

   <div id="main_screen">
      <div id="loading" class="dialog">
         <span style="color:red;font-weight:bold" data-i18n="loading"></span>
      </div>

      <div id="headerWarning" class="dialog" style="display:none"><!--
            <p><b>Relisez et corrigez</b> d'éventuelles erreurs dans les noms et prénoms de vos élèves. Lorsqu'un paricipant est un enseignant et non un élève, ajoutez [E] devant son nom afin de vous assurer qu'il ne sera pas considéré dans le classement des élèves. Par exemple si l'enseignant est Jacques Dupont, mettez "[E] Dupont" pour son nom</p>
            <p>Les <b>scores de vos élèves</b> sont disponibles. Vous avez jusqu'au 23 Novembre pour nous signaler toute anomalie. Les scores <b>deviendront définitfs le 25 Novembre</b>. Assurez-vous que si l'un ou plusieurs de vos élèves ont eu une erreur de connexion à la fin de leur épreuve avec un code à nous envoyer par mail, ils nous envoient bien ce mail avant le 23 Novembre s'ils ne l'ont pas déjà fait.</p>
            <p>Notez aussi que si vous avez mis par erreur "oui" dans la colonne <b>Hors classement</b> d'un groupe d'élèves, il est encore temps de le modifier pour que vos élèves soient classés. Vous pouvez aussi modifier le nom d'un groupe pour le reconnaître plus facilement dans la liste des équipes.</p>
            -->
         <div data-i18n="[html]announcement"></div>
         <div id='spread-castor'>
            <h2 data-i18n="[html]spread_castor_title"></h2>
            <div id='spread-castor-table'></div>
            <div style='padding-bottom:0.2em' data-i18n="spread_castor_message"></div>
            <div data-i18n="spread_castor_email">
               <input id='spread-castor-email' type='text'>
               <button type="button" id='spread-castor-send' data-i18n="spread_castor_validate" class="btn btn-default"></button>
            </div>
            <div id='spread-castor-message'></div>
         </div>
      </div>

      <div id="login_form" class="dialog" style="display:none">
         <h2 data-i18n="login_teacher_wannabe_admin"></h2>
         <a href="#" onclick="newUser()" data-i18n="login_register"></a>
         <h2 data-i18n="login_are_you_admin"></h2>
         <p data-i18n="login"></p>
         <div id="divInput" class="formWrapper">
            <label><span data-i18n="login_email" class="label"></span> <input id="email" type="text"></label>
            <label><span data-i18n="login_password" class="label"></span> <input id="password" type="password" onkeypress="if (event.keyCode == 13) {login();  return false;}"></label>
            <button type="button" data-i18n="login_connexion" id="buttonLogin" onclick="login()" class="btn btn-default"></button><br />
         </div>
         <div id="login_error" style="color:red"></div>
         <h2 data-i18n="login_lost_password"></h2>
         <div class="formWrapper">
            <label><span data-i18n="login_input_email" class="label"></span> <input id="recoverEmail" type="text"></label>
            <button type="button" data-i18n="login_get_new_password" id="buttonRecover" onclick="recover()" class="btn btn-default"></button>
         </div>
      </div>

      <div id="admin_view" style="display:none">
         <div id="filters"></div>
         <ul>
            <li><a href="#tabs-help" data-i18n="help_title"></a></li>
            <li><a href="#tabs-users" data-i18n="users_title"></a></li>
            <li><a href="#tabs-schools" data-i18n="schools_title"></a></li>
            <li><a href="#tabs-groups" data-i18n="groups_title"></a></li>
            <li><a href="#tabs-teams" id="li-tabs-teams" data-i18n="teams_title"></a></li>
            <li><a href="#tabs-contestants" id="li-tabs-contestants" data-i18n="contestants_title"></a></li>
            <li><a href="#tabs-awards" id="li-tabs-awards" data-i18n="awards_title"></a></li>
            <li><a href="#tabs-certificates" id="li-tabs-certificates" data-i18n="certificates_title"></a></li>
            <li><a href="#tabs-questions" id="li-tabs-questions" data-i18n="questions_title"></a></li>
            <li><a href="#tabs-contests" id="li-tabs-contests" data-i18n="contests_title"></a></li>
         </ul>

         <div id="tabs-help" data-i18n="[html]help_content"></div>

         <div id="tabs-users">
            <table>
               <tr><td><b data-i18n="users_gender"></b></td><td id="user-gender"></td></tr>
               <tr><td><b data-i18n="users_lastname"></b></td><td id="user-lastName"></td></tr>
               <tr><td><b data-i18n="users_firstname"></b></td><td id="user-firstName"></td></tr>
               <tr><td><b data-i18n="users_official_email"></b></td><td id="user-officialEmail"></td></tr>
               <tr><td><b data-i18n="users_alternative_email"></b></td><td id="user-alternativeEmail"></td></tr>
            </table>
            <button type="button" onclick="editUser()" data-i18n="edit_user" class="btn btn-default"></button>
            <div>
               <button type="button" id="buttonRefreshUsers" style="display:none" data-i18n="refresh_list" onclick="refreshGrid('user')" class="btn btn-default"></button>
               <button type="button" id="linkExportUsers" style="display:none" onclick="exportCSV('user')" data-i18n="export_to_csv" class="btn btn-default"></button>
               <div class="gridTable">
                  <table id="grid_user"><tbody><tr><td/></tr></tbody></table>
                  <div id="pager_user"></div>
               </div>
               <button type="button" id="buttonDeleteSelected_user" style="display:none" data-i18n="users_delete_selected" class="btn btn-default"></button>
            </div>
         </div>

         <div id="tabs-schools">
            <div id="advSchools" style="display:none">
               <h2 data-i18n="schools_list"></h2>
               <div>
                  <button type="button" id="buttonRefreshSchools" style="display:none" data-i18n="refresh_list" onclick="refreshGrid('school')" class="btn btn-default"></button>
                  <button type="button" onclick="exportCSV('school')" data-i18n="export_to_csv" class="btn btn-default"></button>
                  <div class="gridTable">
                     <table id="grid_school"><tbody><tr><td/></tr></tbody></table>
                     <div id="pager_school"></div>
                  </div>
                  <button type="button" onclick="searchSchool()" onclick_old="newSchool()" data-i18n="schools_add" class="btn btn-default"></button>
                  <button type="button" id="buttonDeleteSelected_school" data-i18n="schools_delete" class="btn btn-default"></button>
               </div>
               <button type="button" id="buttonComputeCoords_school" style="display:none" data-i18n="schools_recompute_coords" class="btn btn-default"></button>
               <div id="computeCoordsLog"></div>
               <h2 id="school_print_certificates_title" data-i18n="school_print_certificates_title"></h2>
               <p id="school_print_certificates_help" data-i18n="school_print_certificates_help"></p>
               <div id="school_print_certificates_contests" class=""></div>
               <h2 id="school_print_awards_title" data-i18n="school_print_awards_title"></h2>
               <p id="school_print_awards_help" data-i18n="school_print_awards_help"></p>
               <button type="button" id="buttonPrintAwards_school" onclick="printSchoolAwards()" data-i18n="school_print_awards" class="btn btn-default"></button>
            </div>
            <div data-i18n="[html]colleagues"></div>
            <div class="gridTable">
               <table id="grid_colleagues"><tbody><tr><td/></tr></tbody></table>
               <div id="pager_colleagues"></div>
            </div>
         </div>


         <div id="tabs-groups">
            <div data-i18n="[html]groups_intro"></div>
            <button type="button" data-i18n="refresh_list" onclick="refreshGrid('group')" class="btn btn-default"></button>
            <button type="button" onclick="exportCSV('group')" data-i18n="export_to_csv" class="btn btn-default"></button>
            <div class="gridTable">
               <table id="grid_group"><tbody><tr><td/></tr></tbody></table>
               <div id="pager_group"></div>
            </div>
            <button type="button" data-i18n="groups_create" onclick="newGroup()" class="btn btn-default"></button>
            <button type="button" id="buttonEditSelected_group" data-i18n="groups_edit_selected" onclick="editGroup()" class="btn btn-default"></button>
            <button type="button" id="buttonDeleteSelected_group" data-i18n="groups_delete_selected" class="btn btn-default"></button>
            <button type="button" id="buttonGradeSelected_group" data-i18n="groups_grade_selected" onclick="gradeGroup()" class="btn btn-default"></button>
            <button type="button" id="buttonDisplaySelected_group" onclick="displayScoresGroup()" class="btn btn-default" value="test" data-i18n="groups_view_details"></button>
            <div id="gradeGroupState" style="display:none;"></div>
            <h2 data-i18n="groups_sheet_title"></h2>
            <p data-i18n="[html]groups_sheet_intro"></p>
            <ul>
               <li><button type="button" id="printNotice_group" data-i18n="groups_sheet_button_print" onclick="printGroup()" class="btn btn-default"></button></li>
               <li><button type="button" id="printNotice_groupAll" data-i18n="groups_sheet_button_print_all" onclick="printGroupAll()" class="btn btn-default"></button></li>
            </ul>
            <h2 id="group_print_certificates_title" data-i18n="group_print_certificates_title"></h2>
            <p id="group_print_certificates_help" data-i18n="group_print_certificates_help"></p>
            <button type="button" id="buttonPrintCertificates_group" onclick="printGroupCertificates()" data-i18n="group_print_certificates" class="btn btn-default"></button>
            <h2 id="group_print_awards_title" data-i18n="group_print_awards_title"></h2>
            <p id="group_print_awards_help" data-i18n="group_print_awards_help"></p>
            <button type="button" id="buttonPrintAwards_group" onclick="printGroupAwards()" data-i18n="group_print_awards" class="btn btn-default"></button>
         </div>

         <div id="tabs-teams">
            <div data-i18n="[html]teams_intro"></div>
            <button type="button" data-i18n="refresh_list" onclick="refreshGrid('team_view')" class="btn btn-default"></button>
            <button type="button" onclick="exportCSV('team_view')" data-i18n="export_to_csv" class="btn btn-default"></button>
            <div class="gridTable">
               <table id="grid_team_view"><tbody><tr><td/></tr></tbody></table>
               <div id="pager_team_view"></div>
            </div>
            <button type="button" id="buttonDeleteSelected_team_view" data-i18n="teams_delete_selected" style="display:none" class="btn btn-default"></button>
             <h2 id="group_print_certificates_title" data-i18n="group_print_certificates_title"></h2>
            <p id="team_print_certificates_help" data-i18n="team_print_certificates_help"></p>
            <button type="button" id="buttonPrintCertificates_team" onclick="preparePrintTeamCertificates()" data-i18n="team_print_certificates" class="btn btn-default"></button> <button type="button" id="buttonDoPrintCertificates_team" onclick="printTeamCertificates()" class="btn btn-default" style="display:none"></button> 
        </div>

         <div id="tabs-contestants">
            <div data-i18n="[html]contestants_intro"></div>
            <button type="button" data-i18n="refresh_list" onclick="refreshGrid('contestant')" class="btn btn-default"></button>
            <button type="button" onclick="exportCSV('contestant')" data-i18n="export_to_csv" class="btn btn-default"></button>
            <div class="gridTable">
               <table id="grid_contestant"><tbody><tr><td/></tr></tbody></table>
               <div id="pager_contestant"></div>
            </div>
            <button type="button" id="buttonDeleteSelected_contestant" data-i18n="contestants_delete_selected" style="display:none" class="btn btn-default"></button>
         </div>

         <div id="tabs-certificates">
            <div data-i18n="[html]certificates_intro"></div>
         </div>

         <div id="tabs-questions">
            <div class="gridTable">
               <table id="grid_question"><tbody><tr><td/></tr></tbody></table>
               <div id="pager_question"></div>
            </div>
            <div>
               <button type="button" data-i18n="questions_create" onclick="newItem('question')" class="btn btn-default"></button>
               <button type="button" id="buttonDeleteSelected_question" data-i18n="questions_delete_selected" class="btn btn-default"></button>
            </div>
            <iframe id="preview_question" src="" style="width:800px;height:800px;"></iframe>
         </div>

         <div id="tabs-contests">
            <div class="gridTable">
               <table id="grid_contest"><tbody><tr><td/></tr></tbody></table>
               <div id="pager_contest"></div>
            </div>
            <button type="button" data-i18n="contests_create" onclick="newItem('contest')" class="btn btn-default"></button>
            <button type="button" id="buttonDeleteSelected_contest" data-i18n="contests_delete_selected" class="btn btn-default"></button>
            <button type="button" id="generateContest" data-i18n="contests_regenerate" onclick="genContest()" class="btn btn-default"></button><br/>
            <button type="button" id="buttonGradeContest" data-i18n="contests_grade" onclick="gradeContest()" class="btn btn-default"></button>
            <button type="button" id="buttonComputeScoresContest" data-i18n="contests_total_scores" onclick="computeTotalScoresContest()" class="btn btn-default"></button>
            <button type="button" id="buttonRankContest" data-i18n="contests_rank" onclick="rankContest()" class="btn btn-default"></button>
            <!--<button style="display:none;" type="button" id="buttonGenerateAlgoreaCodes" data-i18n="generate_algorea_codes" onclick="generateAlgoreaCodes()" class="btn btn-default"></button>-->
            <button type="button" id="buttonUnofficializeContest" data-i18n="contests_switch_to_unofficial" onclick="alert(t('admin.feature_not_available'))" class="btn btn-default"></button><br/>
            <div id="gradeContestState"></div>
            <br/><br/>
            <h2 data-i18n="contests_questions"></h2>
            <div class="gridTable">
               <table id="grid_contest_question"><tbody><tr><td/></tr></tbody></table>
               <div id="pager_contest_question"></div>
            </div>
            <button type="button" data-i18n="contests_questions_add" onclick="newContestQuestion()" class="btn btn-default"></button>
            <button type="button" id="buttonDeleteSelected_contest_question" data-i18n="contests_questions_delete" class="btn btn-default"></button>
         </div>

         <div id="tabs-awards">
            <div data-i18n="[html]awards_content_intro"></div>
            <div data-i18n="[html]awards_content_detail"></div>
            <div>
               <p id="noPersonalCode" data-i18n="[html]awards_personal_code_nocode"></p>
               <p id="withPersonalCode" data-i18n="[html]awards_personal_code_withcode"></p>
            </div>
            <p style="display:none;" id="linkExportAlgoreaCodes" data-i18n="[html]generate_algorea_codes"></p>
            <button type="button" id="linkExportAwards1" onclick="exportCSV('award1')" data-i18n="export_to_csv" class="btn btn-default"></button>
            <div class="gridTable">
               <table id="grid_award1"><tbody><tr><td/></tr></tbody></table>
               <div id="pager_award1"></div>
            </div>
            <h2 id="custom_award_title"></h2>
            <div id="custom_award_help"></div>
            <div id="custom_award_data"></div>
          </div>
      </div>
   </div>
   <div id="divError">
      <b data-i18n="error_server_response"></b> <p style="float:right;"><a href="#" onclick="$('#divError').hide()">[<span data-i18n="error_close"></span>]</a></p><br/>
      <span id="contentError"></span>
   </div>
   <div id="divSchoolSearch" style="display:none" class="dialog">
      <p data-i18n="schools_search_text">
      </p>
      <table id="grid_school_search"><tbody><tr><td/></tr></tbody></table>
      <div id="pager_school_search"></div> 
      <button type="button" data-i18n="school_select" onclick="selectSchool()"></button>
      <p>
         <span data-i18n="schools_create_text"></span>
         <button type="button" data-i18n="schools_create" onclick="newSchool()"/>
      </p>
      <p>
         <button type="button" data-i18n="cancel" onclick="endSearchSchool()"/>
      </p>
   </div>
</form>
<?php
   global $config;
   $language = $config->defaultLanguage;
   $countryCode = $config->teacherInterface->countryCode;
   $domainCountryCode = $config->teacherInterface->domainCountryCode;
   // JSON3 shim for IE6-9 compatibility.
   script_tag('/bower_components/json3/lib/json3.min.js');
   // jquery 1.9 is required for IE6+ compatibility.
   script_tag('/bower_components/jquery/jquery.min.js');
   // Ajax CORS support for IE9 and lower.
   script_tag('/bower_components/jQuery-ajaxTransport-XDomainRequest/jquery.xdomainrequest.min.js');
   script_tag('/bower_components/jstz/index.js'); // no proper bower packaging, must be updated by hand (in bower.json)
   script_tag('/bower_components/jquery-ui/jquery-ui.min.js');
   script_tag('/bower_components/i18next/i18next.min.js');
   script_tag('/bower_components/pem-platform/task-pr.js');
   script_tag('/bower_components/jqgrid/js/minified/jquery.jqGrid.min.js');
   script_tag('/bower_components/jqgrid/js/i18n/grid.locale-' . $language . '.js');
   script_tag('/bower_components/jstz/index.js');
   script_tag('/regions/' . strtoupper($countryCode) . '/regions.js');
   script_tag('/admin.js');
   script_tag('/gradeContest.js');
?>
<script>
   window.config = <?= json_encode([
      'defaultLanguage' => $language,
      'maintenanceUntil' => $config->maintenanceUntil,
      'countryCode' => $countryCode,
      'domainCountryCode' => $domainCountryCode,
      'infoEmail' => $config->email->sInfoAddress,
      'forceOfficialEmailDomain' => $config->teacherInterface->forceOfficialEmailDomain,
      'contestPresentationURL' => $config->contestPresentationURL,
      'contestURL' => $config->contestInterface->baseUrl,
      'i18nResourcePath' => static_asset('/i18n/__lng__/__ns__.json'),
      'customStringsName' => $config->customStringsName,
      'allowCertificates' => $config->certificates->allow,
      'useAlgoreaCodes' => $config->teacherInterface->useAlgoreaCodes,
      'grades' => $config->grades,
      'noGender' => (isset($config->teacherInterface->noGender) && $config->teacherInterface->noGender)
   ]) ?>;
   init();
</script>
</body>
</html>
