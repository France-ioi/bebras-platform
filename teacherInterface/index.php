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
<div autocomplete="off">
   <div id="divHeader">
        <table style="width:100%"><tr>
            <td style="width:20%" data-i18n="[html]main_logo"></td>
            <td>
            <a id="logoutLink" style="display:none;" href="#" onclick="logout()">[<span data-i18n="logout"></span>]</a>
            <p class="headerH1" data-i18n="title"></p>
            <p class="headerH2" data-i18n="[html]subtitle"></p>
            </td>
            <td></td>
         </tr>
         <tr id="headerWarning" style="display:none"><td></td><td style="width:900px">         <!--
<p><b>Relisez et corrigez</b> d'éventuelles erreurs dans les noms et prénoms de vos élèves. Lorsqu'un paricipant est un enseignant et non un élève, ajoutez [E] devant son nom afin de vous assurer qu'il ne sera pas considéré dans le classement des élèves. Par exemple si l'enseignant est Jacques Dupont, mettez "[E] Dupont" pour son nom</p>
         <p>Les <b>scores de vos élèves</b> sont disponibles. Vous avez jusqu'au 23 Novembre pour nous signaler toute anomalie. Les scores <b>deviendront définitfs le 25 Novembre</b>. Assurez-vous que si l'un ou plusieurs de vos élèves ont eu une erreur de connexion à la fin de leur épreuve avec un code à nous envoyer par mail, ils nous envoient bien ce mail avant le 23 Novembre s'ils ne l'ont pas déjà fait.</p>
         <p>Notez aussi que si vous avez mis par erreur "oui" dans la colonne <b>Hors classement</b> d'un groupe d'élèves, il est encore temps de le modifier pour que vos élèves soient classés. Vous pouvez aussi modifier le nom d'un groupe pour le reconnaître plus facilement dans la liste des équipes.</p>
         -->
            <table>
            <tr>
            <td>

            <div data-i18n="[html]announcement"></div>

            </td>
            <td style='vertical-align: top;'>
               <div id='spread-castor'>
                  <h2 data-i18n="[html]spread_castor_title"></h2>
                  <div id='spread-castor-table'></div>
                  <div style='padding-bottom:0.2em' data-i18n="spread_castor_message"></div>
                  <div data-i18n="spread_castor_email">
                     <input id='spread-castor-email' type='text'>
                     <button type="button" id='spread-castor-send' data-i18n="spread_castor_validate"></button>
                  </div>
                  <div id='spread-castor-message'></div>
               </div>
            </td>
            </tr>
            </table>

         </tr>
         </table>
   </div>

   <div id="edit_form" style="display:none;" class="dialog">
   </div>

   <div id="main_screen">
      <div id="loading" class="dialog">
         <span style="color:red;font-weight:bold" data-i18n="loading"></span>
      </div>
      <div id="login_form" class="dialog" style="display:none">
         <p id="login_link_to_home" data-i18n="[html]login_link_to_home"></p>
         <h3 data-i18n="login_teacher_wannabe_admin"></h3>
         <a href="#" onclick="newUser()" data-i18n="login_register"></a>
        <h3 data-i18n="login_are_you_admin"></h3>
         <span data-i18n="login"></span>
         <div id="divInput">
         <span data-i18n="login_email"></span> <input id="email" type="text"><br/>
         <span data-i18n="login_password"></span> <input id="password" type="password" onkeypress="if (event.keyCode == 13) {login();  return false;}"><br/>
         <button type="button" data-i18n="login_connexion" id="buttonLogin" onclick="login()"></button><br />
         </div>
         <div id="login_error" style="color:red"></div>
        <h3 data-i18n="login_lost_password"></h3>
        <span data-i18n="login_input_email"></span> <input id="recoverEmail" type="text"><br/>
        <button type="button" data-i18n="login_get_new_password" id="buttonRecover" onclick="recover()"></button><br />
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
         <div id="tabs-help" data-i18n="[html]help_content">
         </div>
         <div id="tabs-users">
            <p>
            <button type="button" onclick="editUser()" data-i18n="users_edit_infos"></button>
            </p>
            <table>
               <tr><td><b data-i18n="users_gender"></b></td><td id="user-gender"></td></tr>
               <tr><td><b data-i18n="users_lastname"></b></td><td id="user-lastName"></td></tr>
               <tr><td><b data-i18n="users_firstname"></b></td><td id="user-firstName"></td></tr>
               <tr><td><b data-i18n="users_official_email"></b></td><td id="user-officialEmail"></td></tr>
               <tr><td><b data-i18n="users_alternative_email"></b></td><td id="user-alternativeEmail"></td></tr>
            </table>
            <button type="button" id="buttonRefreshUsers" style="display:none" data-i18n="refresh_list" onclick="refreshGrid('user')"></button>
            <a id="linkExportUsers" style="display:none" href="#" onclick="exportCSV('user')" data-i18n="export_to_csv"></a><br/>
            <table id="grid_user"><tbody><tr><td/></tr></tbody></table>
            <div id="pager_user"></div> 
            <button type="button" id="buttonDeleteSelected_user" style="display:none" data-i18n="users_delete_selected"></button>
         </div>
         <div id="tabs-schools">
            <p>
            <div id="advSchools" style="display:none">
               <p data-i18n="schools_list"></p>
               <button type="button" id="buttonRefreshSchools" style="display:none" data-i18n="refresh_list" onclick="refreshGrid('school')"></button>
               <a href="#" onclick="exportCSV('school')" data-i18n="export_to_csv"></a>
               <table id="grid_school"><tbody><tr><td/></tr></tbody></table>
               <div id="pager_school"></div> 
               <button type="button" onclick="searchSchool()" onclick_old="newSchool()" data-i18n="schools_add"></button>
               <button type="button" id="buttonDeleteSelected_school" data-i18n="schools_delete"></button><br/><br/>
               <button type="button" id="buttonComputeCoords_school" style="display:none" data-i18n="schools_recompute_coords"></button><br/>
               <div id="computeCoordsLog"></div>
               <h3 id="school_print_certificates_title" data-i18n="school_print_certificates_title"></h3>
               <p id="school_print_certificates_help" data-i18n="school_print_certificates_help"></p>
               <div id="school_print_certificates_contests"></div>
            </div>
            </p>   
            <p data-i18n="[html]colleagues">
            </p>
            <p>
            <table id="grid_colleagues"><tbody><tr><td/></tr></tbody></table>
            <div id="pager_colleagues"></div> 
            </p>
         </div>
         <div id="tabs-groups">
            <p data-i18n="[html]groups_intro">
            <button type="button" data-i18n="refresh_list" onclick="refreshGrid('group')"></button><a href="#" onclick="exportCSV('group')" data-i18n="export_to_csv"></a>
            <table id="grid_group"><tbody><tr><td/></tr></tbody></table>
            <div id="pager_group"></div> 
            <div style="margin-top:10px"></div><br/>
            <button type="button" data-i18n="groups_create" onclick="newGroup()"></button>
            <button type="button" id="buttonEditSelected_group" data-i18n="groups_edit_selected" onclick="editGroup()"></button>
            <button type="button" id="buttonDeleteSelected_group" data-i18n="groups_delete_selected"></button>
            <button type="button" id="buttonGradeSelected_group" data-i18n="groups_grade_selected" onclick="gradeGroup()"></button><br/>
            <div id="gradeGroupState" style="display:none;"></div>
            <div style="margin-top:10px"></div><br/>
            <span data-i18n="[html]group_print_certificates_help"></span>
            <h3 id="group_print_certificates_title" data-i18n="group_print_certificates_title"></h3>
            <p id="group_print_certificates_help" data-i18n="group_print_certificates_help"></p>
            <button type="button" id="buttonPrintCertificates_group" onclick="printGroupCertificates()" data-i18n="group_print_certificates"></button>
            <h3 data-i18n="groups_sheet_title"></h3>
            <p data-i18n="[html]groups_sheet_intro">
            </p>
            <ul>
            <li>
            <button type="button" id="printNotice_group" data-i18n="groups_sheet_button_print" onclick="printGroup()"></button>
            </li>
            <li>
            <button type="button" id="printNotice_groupAll" data-i18n="groups_sheet_button_print_all" onclick="printGroupAll()"></button>
            </li>
            </ul>
         </div>
         <div id="tabs-teams">
            <p data-i18n="[html]teams_intro"></p>
            <button type="button" data-i18n="refresh_list" onclick="refreshGrid('team_view')"></button><a href="#" onclick="exportCSV('team_view')" data-i18n="export_to_csv"></a>
            <table id="grid_team_view"><tbody><tr><td/></tr></tbody></table>
            <div id="pager_team_view"></div> 
            <button type="button" id="buttonDeleteSelected_team_view" data-i18n="teams_delete_selected" style="display:none"></button>
         </div>
         <div id="tabs-contestants">
            <p data-i18n="[html]contestants_intro"></p>
            
            <button type="button" data-i18n="refresh_list" onclick="refreshGrid('contestant')"></button><a href="#" onclick="exportCSV('contestant')" data-i18n="export_to_csv"></a>
            <table id="grid_contestant"><tbody><tr><td/></tr></tbody></table>
            <div id="pager_contestant"></div> 
            <button type="button" id="buttonDeleteSelected_contestant" data-i18n="contestants_delete_selected" style="display:none"></button>
         </div>
         <div id="tabs-certificates">
            <p data-i18n="[html]certificates_intro"></p>
         </div>
         <div id="tabs-questions">
            <table id="grid_question"><tbody><tr><td/></tr></tbody></table>
            <div id="pager_question"></div> 
            <div>
               <button type="button" data-i18n="questions_create" onclick="newItem('question')"></button>
               <button type="button" id="buttonDeleteSelected_question" data-i18n="questions_delete_selected"></button>
            </div>
            <iframe id="preview_question" src="" style="width:800px;height:800px;"></iframe>
         </div>
         <div id="tabs-contests">
            <table id="grid_contest"><tbody><tr><td/></tr></tbody></table>
            <div id="pager_contest"></div> 
            <button type="button" data-i18n="contests_create" onclick="newItem('contest')"></button>
            <button type="button" id="buttonDeleteSelected_contest" data-i18n="contests_delete_selected"></button>
            <button type="button" id="generateContest" data-i18n="contests_regenerate" onclick="genContest()"></button><br/>
            <button type="button" id="buttonGradeContest" data-i18n="contests_grade" onclick="gradeContest()"></button>
            <button type="button" id="buttonComputeScoresContest" data-i18n="contests_total_scores" onclick="computeTotalScoresContest()"></button>
            <button type="button" id="buttonRankContest" data-i18n="contests_rank" onclick="rankContest()"></button>
            <!--<button style="display:none;" type="button" id="buttonGenerateAlgoreaCodes" data-i18n="generate_algorea_codes" onclick="generateAlgoreaCodes()"></button>-->
            <button type="button" id="buttonUnofficializeContest" data-i18n="contests_switch_to_unofficial" onclick="alert(t('admin.feature_not_available'))"></button><br/>
            <div id="gradeContestState"></div>
            <br/><br/>
            <p data-i18n="contests_questions"></p>
            <table id="grid_contest_question"><tbody><tr><td/></tr></tbody></table>
            <div id="pager_contest_question"></div> 
            <button type="button" data-i18n="contests_questions_add" onclick="newContestQuestion()"></button>
            <button type="button" id="buttonDeleteSelected_contest_question" data-i18n="contests_questions_delete"></button>
         </div>
         <div id="tabs-awards">
            <div data-i18n="[html]awards_content_intro"></div>
            <div data-i18n="[html]awards_content_detail"></div>
            <div>
               <p id="noPersonalCode" data-i18n="[html]awards_personal_code_nocode"></p>
               <p id="withPersonalCode" data-i18n="[html]awards_personal_code_withcode"></p>
            </div>
            <p style="display:none;text-align:center;" id="linkExportAlgoreaCodes" data-i18n="[html]generate_algorea_codes"></p>
            <p><a id="linkExportAwards1" href="#" onclick="exportCSV('award1')" data-i18n="export_to_csv"></a></p>
            <table id="grid_award1"><tbody><tr><td/></tr></tbody></table>
            <div id="pager_award1"></div>
            <h3 id="custom_award_title"></h3>
            <div id="custom_award_help"></div>
            <div id="custom_award_data"></div>
          </div>
      </div>
   </d style="display:none;" id="linkExportAlgoreaCodes"se"></b> <p style="float:right;"><aihrpf="#" onclick="$('#divError').hide()">[<span data-i18n="error_close"></span>]</a></p><br/>
      <span id="contentError"></span>
   </div>
   <div id="divSchoolSearch"style="display:none" class="dialog">
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
</div>
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
   ]) ?>;
   init();
</script>
</body>
</html>
