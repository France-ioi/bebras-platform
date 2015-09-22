<?php
  include('./config.php');
  header('Content-type: text/html');
?><!DOCTYPE html>
<html>
<head>
<meta charset='utf-8'>
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<title data-i18n="general_page_title"></title>
<?php stylesheet_tag('/style.css'); ?>
</head><body>
<form autocomplete="off">
<div id="divHeader">
     <table style="width:100%"><tr>
         <td style="width:20%"><img src="images/castor_small.png"/></td>
         <td><p id="headerH1" data-i18n="general_title"></p>
         <p id="headerH2" data-i18n="general_subtitle"></p></td>
         <td></td>
      </tr></table>
</div>
<div id="divCheckGroup" class="dialog">
   <p data-i18n="[html]general_instructions">
   </p>
   <p>
   <b data-i18n="general_choice"></b>
   </p>
   <button type="button" id="button-school" class="tabButton selected" onclick="selectMainTab('school');return false;" data-i18n="[html]general_start_contest"></button>
   <button type="button" id="button-home" class="tabButton" onclick="selectMainTab('home');return false;" data-i18n="[html]general_public_contests"></button>
   <button type="button" id="button-continue" class="tabButton" onclick="selectMainTab('continue');return false;" data-i18n="[html]general_view_results"></button>
   <div id="tab-home" style="display:none">
      <!-- Display the first div during the contest week -->
      <div id="warningPublicGroups" style="display:none;background:#F33;width:650px;text-align:center;padding:5px;margin:10px">
         <p><b data-i18n="tab_home_warning_not_contest"></b></p>
         <button type="button" onclick="selectMainTab('school');return false;" data-i18n="[html]tab_home_button_wrong_choice"></button>
         <button type="button" onclick="confirmPublicGroup();return false;" data-i18n="[html]tab_home_button_confirm_choice"></button>
      </div>
      <div id="publicGroups" style="display:block">
         <span id="loadPublicGroups" style="color:red" data-i18n="tab_public_loading"></span>
         <div id="contentPublicGroups" style="display:none;width:800px">
            <p><b data-i18n="[html]tab_public_contests_info"></b></p>
            <p data-i18n="tab_public_contests_score_explanation"></p>
            <div id="listPublicGroups">
            </div>
            <p data-i18n="[html]tab_public_contests_organization"></p>
         </div>
      </div>
   </div>
   <div id="tab-school">
<!--      <p>Pour <b>voir votre score détaillé</b> si vous avez participé au concours 2012, cliquez sur "Continuer le concours" et saisissez votre code personnel fourni au début de l'épreuve. Vous aurez aussi accès aux réponses et à une <b>correction détaillée</b> en dessous de chaque question.</p>
      <h3>Vous démarrez un concours en classe, pour la première fois ?</h3>-->
      <p data-i18n="tab_start_contest_enter_code"><br />
         <div id="divInput">
            <input id="groupCode" type="text"/>
               &nbsp;&nbsp;&nbsp;<button type="button" id="buttonCheckGroup" onclick="checkGroup()" data-i18n="tab_start_contest_start_button"></button>
               <br /><span id="CheckGroupResult" style="color:red"></span>
         </div>
      </p>
      <div id="recoverGroup" style="display:none;">
         <p data-i18n="[html]group_session_expired_recover"></p>
         <input id="recoverGroupPass" type="password"/>
         &nbsp;&nbsp;&nbsp;<button type="button" id="buttonRecoverGroup" onclick="recoverGroup()" data-i18n="submitPass"></buton>
         <br><span id="recoverGroupResult" style="color:red"></span>
         <p data-i8n="[html]others_retry"></p>
      </div>
   </div>
   <div id="tab-continue" style="display:none">
      <p><span data-i18n="tab_view_results_access_code"></span>
         <div id="divInput">
            <input id="interruptedPassword" type="password">
            &nbsp;&nbsp;&nbsp;<button type="button" id="buttonInterrupted" onclick="checkPasswordInterrupted()" data-i18n="tab_view_results_view_results_button"></button>
            <br/><span id="InterruptedResult" style="color:red"></span>
          </div>
      </p>
      <p data-i18n="tab_view_results_info_1"></p>
      <p><b data-i18n="tab_view_results_info_2"></b></p>
      <!--<p>Si vous ne disposez pas de mot de passe mais que vous êtes en classe, alors entrez le code de groupe fourni par votre enseignant.</p>-->
      <p data-i18n="tab_view_results_info_3"></p>
      <p data-i18n="tab_view_results_info_4"></p>
      <div id="divRelogin" style="display:none">
         <p data-i18n="tab_view_select_team_in_list"></p>
         <p><select id="selectTeam"><option value='0' data-i18n="tab_view_select_team"></option></select></p>
         <p data-i18n="tab_view_ask_password_to_teacher"></p>
         <p>
            <div id="divInput">
                   <input id="groupPassword" type="password">
                   &nbsp;&nbsp;&nbsp;<button type="button" id="buttonRelogin" onclick="relogin()" data-i18n="tab_view_restart_contest"></button>
                   <br/><span id="ReloginResult" style="color:red"></span>
             </div>
         </p>
      </div>
   </div>
</div>
<div id="divCheckNbContestants" style="display:none" class="dialog">
   <p data-i18n="nb_contestants_question"></p>
      <div id="divInput">
         <button type="button" onclick="setNbContestants(1)" data-i18n="nb_contestants_one"></button>
         &nbsp;&nbsp;&nbsp;&nbsp;<button type="button" onclick="setNbContestants(2)" data-i18n="nb_contestants_two"></button>
      </div>
   </p>
</div>
<div id="divLogin" style="display:none" class="dialog">
   <p> <span data-i18n="[html]login_input_firstname"></span> <input id="firstName1" type="text" autocomplete="off"></input></p>
   <p> <span data-i18n="[html]login_input_lastname"></span> <input id="lastName1" type="text" autocomplete="off"></input></p>
   <p> <span data-i18n="login_ask_gender"></span> <br/>
         <div id="divInput">
            <input type="radio" id="genre1_female" name="genre1" value="1" autocomplete="off"><label for="genre1_female" data-i18n="login_female"></label>
            <br><input type="radio" id="genre1_male" name="genre1" value="2" autocomplete="off"><label for="genre1_male" data-i18n="login_male"></label>
         </div>
   </p>
   <div id="contestant2" style="display:none">
      <p><b data-i18n="login_teammate"></b></p>
      <p><span data-i18n="[html]login_input_firstname"></span> <input id="firstName2" type="text" autocomplete="off"></input></p>
      <p><span data-i18n="[html]login_input_lastname"></span> <input id="lastName2" type="text" autocomplete="off"></input></p>
      <p><span data-i18n="login_ask_gender"></span> <br/>
         <div id="divInput">
         <input type="radio" id="genre2_female" name="genre2" value="1" autocomplete="off"/><label for="genre2_female" data-i18n="login_female"></label><br>
         <input type="radio" id="genre2_male" name="genre2" value="2" autocomplete="off"/><label for="genre2_male" data-i18n="login_male"></label></input>
         </div>
      </p>
   </div>
   <p><button type="button" id="buttonLogin" onclick="validateLoginForm()" data-i18n="login_start_contest"></button><span id="LoginResult" style="color:red"></span></p>
</div>
<div id="divPassword" style="display:none" class="dialog">
   <p data-i18n="[html]password_warning">
   </p>
   <p>
   Code d'accès: <span id="teamPassword" class="selectable" style="font-size:2em"></span>
   </p>
         <div id="divInput">
            <button type="button" data-i18n="password_confirm" id="buttonConfirmTeamPassword" onclick="confirmTeamPassword()"></button>
         </div>
</div>
<div id="divImagesLoading" style="display:none" class="dialog">
  <span id="nbImagesLoaded">0</span> <span data-i18n="images_preloaded"></span>
</div>
<div id="divQuestions" style="display:none">
   <div class="questionListHeader">
      <table id="chrono" width="95%">
         <tr><td class="fullFeedback">Temps&nbsp;: </td><td><span id='minutes'></span>:<span id='seconds'></span></td></tr>
         <tr><td class="fullFeedback">Score&nbsp;:</td><td><span id='scoreTotalFullFeedback'></span></td></tr>
      </table>
<!--      <p>Cliquez ci-dessous pour changer de question :</p>-->
      <p></p>
      <div id="scoreBonus" style="display:none"><b data-i18n="questions_bonus"></b><br/></div>
      <div id="rank" width="95%"></div>
   </div>
   <div id="questionList" class='questionList'>
   <span style="color:red" data-i18n="questions_loading"></span>
   </div>
   <p></p>
   <div style="text-align:center;width:180px;">
      <button type="button" id="buttonClose" style="display:none;" data-i18n="questions_finish_early" onclick='tryCloseContest()'></button>
   </div>
   <table class="questionsTable"><tr><td>
   <div id="divQuestionParams">
      <table style="width:100%"><tr>
         <td style="width:10%"><img src="images/castor_small.png" style="width:65px" /></td>
         <td><div id="questionTitle"></div></td>
         <td style="width:25%"><!--<div id="questionType"></div>--><div id="questionPoints"></div></td>
      </tr></table>
   </div>
   </td></tr>
   <tr><td>
   <span id="divQuestionsContent">
   </span>
   <span id="divSolutionsContent" style="display:none">
   </span>
   <span id="divGradersContent" style="display:none">
   </span>
   </td></tr></table>
</div>
<div id="question-iframe-container">
   <iframe src="about:blank" id="question-iframe" scrolling="no"></iframe>
</div>
<div id="divClosed" style="display:none" class="dialog">
   <h3 id="divClosedMessage">
   </h3>
   <div id="divClosedPleaseWait" style="display:none">
      <p style='margin:200px 0 200px 0' data-i18n="[html]closed_please_wait">
      </p>
   </div>
   <div id="divClosedEncodedAnswers" style="display:none">
      <p data-i18n="[html]closed_connexion_error">
      </p>
      <textarea cols=60 rows=20 id="encodedAnswers"></textarea>
   </div>
   <div id="divClosedRemindPassword" style="display:none">
      <p>
         <b data-i18n="closed_remind_password"></b>
      </p>
      <p>
         <span data-i18n="closed_your_password"></span> <span class='selectable' id="remindTeamPassword"></span>
      </p>
   </div>
</div>
<div id="divError">
   <b data-i18n="error_server"></b> <p style="float:right;"><a href="#" onclick="$('#divError').hide()">[<span data-i18n="error_close"></span>]</a></p><br/>
   <span id="contentError"></span>
</div>
</form>
<!--<iframe id="trackingFrame" src="http://eval02.france-ioi.org/castor_tracking/index.html" style="display:none"></iframe>-->
<?php
  // JSON3 shim for IE6-9 compatibility.
  script_tag('/bower_components/json3/lib/json3.min.js');
  // jquery 1.9 is required for IE6+ compatibility.
  script_tag('/bower_components/jquery/jquery.min.js');
  // Ajax CORS support for IE9 and lower.
  script_tag('/bower_components/jQuery-ajaxTransport-XDomainRequest/jquery.xdomainrequest.min.js');
  script_tag('/bower_components/jquery-ui/jquery-ui.min.js');
  script_tag('/bower_components/jquery-ui-touch-punch/jquery.ui.touch-punch.min.js');
  script_tag('/bower_components/jquery-postmessage/jquery.ba-postmessage.min.js');
  script_tag('/bower_components/i18next/i18next.min.js');
  script_tag('/bower_components/utf8/utf8.js');
  script_tag('/bower_components/base64/base64.min.js');
  script_tag('/bower_components/pem-platform/task-pr.js');
  script_tag('/common.js');
  global $config;
?>
<script>
  window.contestsRoot = <?= json_encode(static_asset('/contests')) ?>;
  i18n.init(<?= json_encode([
    'lng' => $config->defaultLanguage,
    'fallbackLng' => [$config->defaultLanguage],
    'getAsync' => true,
    'resGetPath' => static_asset('/i18n/__lng__/__ns__.json')
  ]) ?>, function () {
    $("title").i18n();
    $("body").i18n();
  });
</script>
</body></html>
