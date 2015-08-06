<?php
  include('../common.php');
  header('Content-type: text/html');
?><!DOCTYPE html>
<html>
<head>
<meta charset='utf-8'>
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<style>
* {
  -moz-user-select: -moz-none;
  -webkit-user-select: none;
  -o-user-select: none;
  user-select: none;
  -khtml-user-select: none;
  font-family: arial;
}

body {
   margin-left:0px;
}

.fullFeedback {
  display: none;
}

.check {
  color: green;
  font-weight:bold;
  margin-left:-1px;
  margin-right:-2px;
}

a {
   color: #4D87CE;
   font-weight: bold;
   text-decoration: none;
}

#chrono {
  font-size: 2em;
  background-color: #4D87CE;
  text-align: center;
  height: 50px;
  padding-top: 10px;
  padding-bottom: 10px;
}

#rank {
  display:none;
  font-size: 1.2em;
  background-color: #4D87CE;
  text-align: center;
  height: 50px;
  line-height: 50px;
  padding-top: 10px;
  padding-bottom: 10px;
  padding-right: 10px;
  vertical-align: middle;
  margin-right: 15px;
  margin-bottom: 10px;
  margin-top: -10px;
}

.selectable, input, textarea {
  -moz-user-select: text;
  -webkit-user-select: text;
  -o-user-select: text;
  user-select: text;
  -khtml-user-select: text;
}

.questionListHeader {
   width:195px;
   padding-right: 5px;
}

.questionsTable {
   position: absolute;
   top: 5px;
   left:245px;
}

.questionsTable td {
   position:relative;
   left: -2px;
}

.question {
   display:none;
   width:800px;
   border: solid black 1px;
   padding: 10px;
}

#divClosed {
   display:none;
   position:absolute;
   top:200px;
   left:245px;
   width:800px;
   border: solid black 1px;
   padding: 10px;
   z-index: 100;
   background: white;
}

#divQuestionParams {
   width:810px;
   height: 70px;
   border: solid black 1px;
   padding-left: 5px;
   padding-right: 5px;
}

#questionTitle {
   text-align: center;
   font-size: 2em;
   font-weight: bold;
}

#questionPoints{
   font-size: 0.9em;
}

.question h1 {
   display: none;
}

.questionLink, .questionLinkSelected {
   font-size: 0.9em;
   line-height:180%;
   color: #4D87CE;
   width: 170px;
   font-weight: bold;
   cursor: pointer;
   padding: 0px;
}

.questionLink:hover {
   background-color: #E0E0E0;
   color: #2A65AD;
}

.questionBullet {
   width: 14px;
   padding: 0px;
}

.questionScore {
   text-align: right;
   padding: 0px 0px 0px 1px;
}

.questionLinkSelected {
   background-color: #808080;
   color: black;
}

.groupRow:hover {
   background-color: #E0E0E0;
}

#headerH1 {
   font-family:"Century Gothic", "Trebuchet MS", "Arial Narrow", Arial, sans-serif;
   font-size:40px;
   text-transform:uppercase;
   font-weight:normal;
   margin:0;
   padding:0;
   padding-top:30px;
   color: #736451;
   margin-bottom: 10px;
   text-align: left;
}

#headerH2 {
   font-family: "Century Gothic", "Trebuchet MS", "Arial Narrow", Arial, sans-serif;
   font-size: 24px;
   text-transform: uppercase;
   text-align: left;
   font-weight: normal;
   margin: 0;
   padding: 0;
   color: #000000;
   border-bottom: 1px solid #eeeeee;
}
.div h3 {
   font-family: "Century Gothic", "Trebuchet MS", "Arial Narrow", Arial, sans-serif;
   font-size: 24px;
   /*font-color: #736451; Invalid, use color ? */
   text-align: left;
   font-weight: normal;
}
#divInput {
     padding-left: 100px;
}

.dialog {
   padding-left: 245px;
}

.questionScores, .questionScores td {
   border: solid black 1px;
}

.questionScores td {
   padding: 0 5px 0 5px;
   text-align: center;
}

.scoreNothing, .scoreBad, .scoreGood {
   font-size: 1.5em;
   font-weight: bold;
}

.scoreNothing {
   color: black;
}

.scoreBad {
   color: #FF4040;
}

.scoreGood {
   color: #4D87CE;
}

.tabButton {
   width:250px;
   font-size: 16px;
   color:#000;
   text-decoration:none;
   padding:10px;
   border:1px solid #333;
   text-align:center;
   font-weight: normal;
   background-color: #F0E9D8;
   -moz-border-radius:5px;
   -webkit-border-radius:5px;
   -o-border-radius:5px;
   border-radius:5px;
}

.tabButton.selected {
   background-color: #E2D9C8;
   border:3px solid #333;
   font-weight: bold;
}

#divQuestionsContent {
   display: none;
}

#question-iframe {
   width: 822px;
   overflow: hidden;
   border: 0;
   width: 0;
   height: 0;
}
#question-iframe-container {
   position: absolute;
   left: 248px;
   top: 88px;
   width: 0;
   height: 0;
}

.hidden {
  display: none;
}

#divError {
  position:absolute;
  top:100px;
  left:100px;
  width:800px;
  height:600px;
  background:#FAA;
  z-index:100;
  display:none;
}

#contentError {
  -moz-user-select: -moz-text;
  -webkit-user-select: text;
  -o-user-select: text;
  user-select: text;
}

</style>
<title data-i18n="general_page_title"></title>
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
   <p data-i18n=general_instructions">
   </p>
   <p>
   <b data-i18n="general_choice"></b>
   </p>
   <!-- <p><span style="font-size:24px;">Bienvenue sur la plateforme du concours Castor<br /> Session 2012</span></p> -->
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
  echo('<!--[if lte IE 9]>');
  script_tag('/bower_components/jquery.xdomainrequest/jquery.xdomainrequest.min.js');
  echo('<![endif]-->');
  script_tag('/bower_components/jquery-ui/jquery-ui.min.js');
  script_tag('/bower_components/jquery-postmessage/jquery.ba-postmessage.min.js');
  script_tag('/bower_components/i18next/i18next.min.js');
  script_tag('/bower_components/utf8/utf8.js');
  script_tag('/bower_components/base64/base64.min.js');
  script_tag('/contestInterface/jquery.ui.touch-punch.min.js');
  script_tag('/contestInterface/integrationAPI/task-pr.js?v={{rand}}'); # XXX cache-busting
  script_tag('/contestInterface/common.js?v={{rand}}');
  global $config;
?>
<script>
  var config = <?= json_encode([
     'defaultLanguage' => $config->defaultLanguage,
     'sAssetsStaticPath' => static_asset('/contestInterface'),
     'sAbsoluteStaticPath' => $config->teacherInterface->sAbsoluteStaticPath
  ]) ?>;
  var contestsRoot = <?= json_encode(static_asset('/contestInterface/contests/')) ?>;
  i18n.init(<?= json_encode([
    'lng' => $config->defaultLanguage,
    'fallbackLng' => [$config->defaultLanguage],
    'getAsync' => false,
    'resGetPath' => static_asset('/contestInterface/i18n/__lng__/__ns__.json')
  ]) ?>);
  $("title").i18n();
  $("body").i18n();
</script>
</body></html>