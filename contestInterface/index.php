<?php
  include(__DIR__.'/config.php');
  header('Content-type: text/html');
?><!DOCTYPE html>
<html>
<head>
<meta charset='utf-8'>
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<link rel="shortcut icon" href="<?= $config->faviconfile ?>" />
<title data-i18n="general_page_title"></title>
<?php stylesheet_tag('/style.css'); ?>
</head><body>
<div id="divHeader">
  <div id="leftTitle" data-i18n="[html]left_title"></div>
  <div id="headerGroup">
    <h1 id="headerH1" data-i18n="general_title"></h1>
    <h2 id="headerH2" data-i18n="general_subtitle"></h2>
    <p id="login_link_to_home" data-i18n="[html]general_instructions"></p>
  </div>
</div>
<form id="mainContent" autocomplete="off">

<?php
// Check browser parameters
$browserVerified = true;
$browserOld = false;
if($config->contestInterface->browserCheck) {
  require_once __DIR__.'/../vendor/autoload.php';
  $browser = new WhichBrowser\Parser($_SERVER['HTTP_USER_AGENT']);
  if($config->contestInterface->browserCheck == 'bebras-platform') {
    $browserVerified = $browser->isBrowser('Firefox', '>=', '3.6') ||
         $browser->isBrowser('Chrome', '>=', '5') ||
         $browser->isBrowser('Silk', '>=', '5') ||
         $browser->isBrowser('Safari', '>=', '9') ||
         $browser->isBrowser('Internet Explorer', '>=', '8') ||
         $browser->isBrowser('Edge');
  } elseif($config->contestInterface->browserCheck == 'quickAlgo') {
    $browserVerified = $browser->isBrowser('Firefox', '>=', '43') ||
         $browser->isBrowser('Chrome', '>=', '35') ||
         $browser->isBrowser('Silk', '>=', '35') ||
         $browser->isBrowser('Safari', '>=', '9') ||
         $browser->isBrowser('Edge', '>=', '12');
  }
  $browserOld = $browser->isBrowser('Firefox', '<', '60') ||
                $browser->isBrowser('Chrome', '<', '64') ||
                $browser->isBrowser('Silk', '<', '64') ||
                $browser->isBrowser('Safari', '<', '9') ||
                $browser->isBrowser('Edge', '<', '41') ||
                $browser->isBrowser('Internet Explorer');
}

if(!$browserVerified) {
    // The message changes depending on the browserCheck value
    echo '<div id="browserAlert" data-i18n="[html]browser_support_' . $config->contestInterface->browserCheck . '"></div>';
}

$browserIsMobile = $browser->isType('mobile', 'tablet', 'ereader');
?>


  <nav id="mainNav">
    <ul>
      <li id="button-school" class="selected" onclick="selectMainTab('school');return false;" data-i18n="general_nav_start_contest"></li>
      <li id="button-home" onclick="selectMainTab('home');return false;" data-i18n="general_nav_public_contests"></li>
      <li id="button-continue" onclick="selectMainTab('continue');return false;" data-i18n="general_nav_continue_contest"></li>
  <!--
    <li id="button-results" onclick="selectMainTab('results');return false;" data-i18n="general_nav_view_results"></li>
      <li id="button-contests" onclick="selectMainTab('contests');return false;" data-i18n="general_nav_view_other_contests"></li>
  -->
    </ul>
  </nav>
  <div id="divCheckGroup" class="dialog">
  <div id="tab-home" style="display:none" class="tabContent">
    <div class="tabTitle" data-i18n="general_public_contests"></div>
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
            <p id="publicContestExplanation"></p>
            <div id="listPublicGroups">
            </div>
            <p data-i18n="[html]tab_public_contests_organization"></p>
         </div>
      </div>
  </div>

  <div id="tab-school" class="tabContent">
    <!--
    <p>Pour <b>voir votre score détaillé</b> si vous avez participé au concours 2012, cliquez sur "Continuer le concours" et saisissez votre code personnel fourni au début de l'épreuve. Vous aurez aussi accès aux réponses et à une <b>correction détaillée</b> en dessous de chaque question.</p>
    <h3>Vous démarrez un concours en classe, pour la première fois ?</h3>
    -->
    <div id="submitParticipationCode" <?=(!$browserVerified || $browserOld) ? 'class="needBrowserConfirm"' : '' ?>>
      <div class="tabTitle" data-i18n="general_start_contest"></div>
      <p class="stepName" data-i18n="[html]tab_start_contest_enter_code"></p>
      <div class="browserConfirm">
        <span data-i18n="[html]<?=$browserVerified ? 'browser_support_old' : 'browser_support_confirm'?>"></span><br>
        <button type="button" onclick="confirmUnsupportedBrowser()" data-i18n="browser_support_confirm_btn" class="btn btn-primary"></button>
      </div>
      <div class="divInput form-inline">
        <input id="groupCode" type="text" class="form-control" autocorrect="off" autocapitalize="none"/>
        <button type="button" id="buttonCheckGroup" onclick="checkGroup()" data-i18n="tab_start_contest_start_button" class="btn btn-primary"></button>
        <div><span id="CheckGroupResult" style="color:red"></span></div>
      </div>
    </div>
    <div id="recoverGroup" style="display:none;">
      <p data-i18n="[html]group_session_expired_recover"></p>
      <div class="divInput form-inline">
        <input id="recoverGroupPass" type="password" class="form-control" autocorrect="off" autocapitalize="none" />
        <button type="button" id="buttonRecoverGroup" onclick="recoverGroup()" data-i18n="submitPass" class="btn btn-default"></button>
        <div><span id="recoverGroupResult" style="color:red"></span></div>
      </div>
      <p data-i8n="[html]others_retry"></p>
    </div>
  </div><!-- #tab-school -->

  <div id="tab-continue" style="display:none" class="tabContent">
    <div class="tabTitle" data-i18n="general_continue_contest"></div>
    <p><span data-i18n="tab_view_results_access_code"></span></p>
    <div class="divInput form-inline">
      <input id="interruptedPassword" type="text" class="form-control" autocorrect="off" autocapitalize="none">
      <button type="button" id="buttonInterrupted" class="btn btn-default" onclick="checkPasswordInterrupted()" data-i18n="tab_view_results_view_results_button"></button>
      <div><span id="InterruptedResult" style="color:red"></span></div>
    </div>

    <p data-i18n="tab_view_results_info_1"></p>
    <p><b data-i18n="tab_view_results_info_2"></b></p>
    <!--<p>Si vous ne disposez pas de mot de passe mais que vous êtes en classe, alors entrez le code de groupe fourni par votre enseignant.</p>-->
    <p data-i18n="tab_view_results_info_3"></p>
    <p data-i18n="tab_view_results_info_4"></p>
    <div id="divRelogin" style="display:none">
      <p data-i18n="tab_view_select_team_in_list"></p>
      <div class="divInput">
        <select id="selectTeam"><option value='0' data-i18n="tab_view_select_team"></option></select>
      </div>
      <p data-i18n="tab_view_ask_password_to_teacher"></p>
      <div class="divInput form-inline">
        <input id="groupPassword" type="password" class="form-control" autocorrect="off" autocapitalize="none">
        <button type="button" id="buttonRelogin" class="btn btn-default" onclick="relogin()" data-i18n="tab_view_restart_contest"></button>
        <div><span id="ReloginResult" style="color:red"></span></div>
      </div>
    </div>
  </div><!-- #tab-continue -->

  <div id="tab-contests" style="display:none" class="tabContent">
    <div class="tabTitle" data-i18n="general_view_other_contests"></div>
    <div data-i18n="[html]tab_view_other_contests"></div>
  </div>
</div>

<div id="divAccessContest" style="display:none">
  <div id="selection-breadcrumb"></div>
  <div id="selectCategory" class="contestSelection-tab" style="display:none">
    <p id="extraMessageCategory" style="font-weight: bold"></p>
    <div class="tabTitle" data-i18n="select_category"></div>
    <div class="categoryWarning" data-i18n="[html]select_category_explanation"></div>

    <p class="categoryWarning" data-i18n="[html]select_category_warning"></p>
    <table class="colorCategories selectorTable"><tbody>
      <tr class="colorCategory white categoryChoice categorySelector" id="cat_blanche" data-category="blanche">
        <td class="selectorCell">
          <div class="selector_arrowForward" ><span> </span></div>
        </td>
        <td class="colorCategoryTitle selectorTitle"><button type="button" class="btn btn-default" data-i18n="category_white"></button></td>
        <td data-i18n="[html]category_white_description"></td>
        <td></td>
      </tr>

      <tr class="colorCategory yellow categoryChoice categorySelector" id="cat_jaune" data-category="jaune">
        <td class="selectorCell">
          <div class="selector_arrowForward" ><span> </span></div>
        </td>
        <td class="colorCategoryTitle selectorTitle"><button type="button" class="btn btn-default" data-i18n="category_yellow"></button></td>
        <td data-i18n="[html]category_yellow_description"></td>
        <td></td>
      </tr>

      <tr class="colorCategory orange categoryChoice categorySelector" id="cat_orange" data-category="orange">
        <td class="selectorCell">
          <div class="selector_arrowForward" ><span> </span></div>
        </td>
        <td class="colorCategoryTitle selectorTitle"><button type="button" class="btn btn-default" data-i18n="category_orange"></button></td>
        <td data-i18n="[html]category_orange_description"></td>
        <td></td>
      </tr>

      <tr class="colorCategory green categoryChoice categorySelector" id="cat_verte" data-category="verte">
        <td class="selectorCell">
          <div class="selector_arrowForward" ><span> </span></div>
        </td>
        <td class="colorCategoryTitle selectorTitle"><button type="button" class="btn btn-default" data-i18n="category_green"></button></td>
        <td data-i18n="[html]category_green_description"></td>
        <td></td>
      </tr>

      <tr class="colorCategory blue categoryChoice categorySelector" id="cat_bleue" data-category="bleue">
        <td class="selectorCell">
          <div class="selector_arrowForward" ><span> </span></div>
        </td>
        <td class="colorCategoryTitle selectorTitle"><button type="button" class="btn btn-default" data-i18n="category_blue"></button></td>
        <td data-i18n="[html]category_blue_description"></td>
        <td></td>
      </tr>
      <tr class="colorCategory white categoryChoice categorySelector" id="cat_cm1cm2" data-category="cm1cm2">
        <td class="selectorCell">
          <div class="selector_arrowForward" ><span> </span></div>
        </td>
        <td class="colorCategoryTitle selectorTitle"><button type="button" class="btn btn-default" data-i18n="category_grades_4_5"></button></td>
        <td data-i18n="[html]category_grades_4_5_description"></td>
        <td></td>
      </tr>
      <tr class="colorCategory white categoryChoice categorySelector" id="cat_6e5e" data-category="6e5e">
        <td class="selectorCell">
          <div class="selector_arrowForward" ><span> </span></div>
        </td>
        <td class="colorCategoryTitle selectorTitle"><button type="button" class="btn btn-default" data-i18n="category_grades_6_7"></button></td>
        <td data-i18n="category_grades_6_7_description"></td>
        <td></td>
      </tr>
      <tr class="colorCategory white categoryChoice categorySelector" id="cat_4e3e" data-category="4e3e">
        <td class="selectorCell">
          <div class="selector_arrowForward" ><span> </span></div>
        </td>
        <td class="colorCategoryTitle selectorTitle"><button type="button" class="btn btn-default" data-i18n="category_grades_8_9"></button></td>
        <td data-i18n="category_grades_8_9_description"></td>
        <td></td>
      </tr>
      <tr class="colorCategory white categoryChoice categorySelector" id="cat_2depro" data-category="2depro">
        <td class="selectorCell">
          <div class="selector_arrowForward" ><span> </span></div>
        </td>
        <td class="colorCategoryTitle selectorTitle"><button type="button" class="btn btn-default" data-i18n="category_grades_13"></button></td>
        <td data-i18n="category_grades_13_description"></td>
        <td></td>
      </tr>
      <tr class="colorCategory white categoryChoice categorySelector" id="cat_2de" data-category="2de">
        <td class="selectorCell">
          <div class="selector_arrowForward" ><span> </span></div>
        </td>
        <td class="colorCategoryTitle selectorTitle"><button type="button" class="btn btn-default" data-i18n="category_grades_10"></button></td>
        <td data-i18n="category_grades_10_description"></td>
        <td></td>
      </tr>
      <tr class="colorCategory white categoryChoice categorySelector" id="cat_1reTalepro" data-category="1reTalepro">
        <td class="selectorCell">
          <div class="selector_arrowForward" ><span> </span></div>
        </td>
        <td class="colorCategoryTitle selectorTitle"><button type="button" class="btn btn-default" data-i18n="category_grades_14_15"></button></td>
        <td data-i18n="[html]category_grades_14_15_description"></td>
        <td></td>
      </tr>
      <tr class="colorCategory white categoryChoice categorySelector" id="cat_1reTale" data-category="1reTale">
        <td class="selectorCell">
          <div class="selector_arrowForward" ><span> </span></div>
        </td>
        <td class="colorCategoryTitle selectorTitle"><button type="button" class="btn btn-default" data-i18n="category_grades_11_12"></button></td>
        <td data-i18n="[html]category_grades_11_12_description"></td>
        <td></td>
      </tr>
      <tr class="colorCategory white categoryChoice categorySelector" id="cat_all" data-category="all">
        <td class="selectorCell">
          <div class="selector_arrowForward" ><span> </span></div>
        </td>
        <td class="colorCategoryTitle selectorTitle"><button type="button" class="btn btn-default" data-i18n="category_grades_all"></button></td>
        <td data-i18n="[html]category_grades_all_description"></td>
        <td></td>
      </tr>
    </tbody></table>
  </div>

  <div id="selectLanguage" style="display:none" class="contestSelection-tab">
    <p id="extraMessage" style="font-weight: bold"></p>
    <div class="tabTitle" data-i18n="select_language"></div>
    <p data-i18n="select_language_advice"></p>
    <table class="languageTable selectorTable"><tbody>
      <tr>
        <td class="languageSelector selectorCell" data-language="blockly">
          <div class="selector_arrowForward" ><span> </span></div>
        </td>
        <td class="languageSelector selectorTitle" data-language="blockly"><button type="button" class="btn btn-default" data-i18n="language_blockly"></button></td>
        <td class="languageSelector" data-language="blockly">
          <img src="images/blockly.png" alt="exemple d'utilisation de Blockly">
        </td>
        <td class="languageSelector languageDescription" data-language="blockly" data-i18n="[html]language_blockly_description">
        </td>
      </tr>
      <tr>
        <td class="languageSelector selectorCell" data-language="scratch">
          <div class="selector_arrowForward" ><span> </span></div>
        </td>
        <td class="languageSelector selectorTitle" data-language="scratch"><button type="button" class="btn btn-default" data-i18n="language_scratch"></button></td>
        <td class="languageSelector" data-language="scratch">
          <img src="images/scratch.png" alt="exemple d'utilisation de Scratch">
        </td>
        <td class="languageSelector languageDescription" data-language="scratch" data-i18n="[html]language_scratch_description">
        </td>
      </tr>
      <tr>
        <td class="languageSelector selectorCell" data-language="python">
          <div class="selector_arrowForward" ><span> </span></div>
        </td>
        <td class="languageSelector selectorTitle" data-language="python"><button type="button" class="btn btn-default" data-i18n="language_python"></button></td>
        <td class="languageSelector" data-language="python">
          <img src="images/python.png" alt="exemple d'utilisation de Python">
        </td>
        <td class="languageSelector languageDescription" data-language="python" data-i18n="[html]language_python_description">
        </td>
      </tr>
    </tbody></table>
  </div>

  <div id="selectContest" style="display:none" class="contestSelection-tab">
    <div class="tabTitle" data-i18n="select_contest"></div>
    <table id="selectContestItems" class="selectorTable">
    </table>
  </div>

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
      <div class="login_box panel">
        <div class="panel-head"><b data-i18n="login_teammate"></b><b> 1</b></div>
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
              <input id="registrationCode1" type="text" autocomplete="off" class="form-control" /></p>
            <p><i data-i18n="login_registrationCode_description"></i></p>
            <button id="validateRegCode1" type='button' onclick="validateRegistrationCode(1)" class="btn btn-default" data-i18n="login_validate_code"></button>
            <p><span id="errorRegistrationCode1" style="color:red;font-weight:bold"></span></p>
          </div>
          <div id="noRegistrationCode1" style="display:none" class="form-inline">
            <p id="login-input-firstName-1">
              <span data-i18n="[html]login_input_firstname"></span>
              <input id="firstName1" type="text" autocomplete="off" class="form-control" /></p>
            <p id="login-input-lastName-1">
              <span data-i18n="[html]login_input_lastname"></span>
              <input id="lastName1" type="text" autocomplete="off" class="form-control" /></p>
            <p id="login-input-email-1">
              <span data-i18n="[html]login_input_email"></span>
              <input id="email1" type="text" autocomplete="off" class="form-control" /></p>
            <p id="login-input-zipCode-1">
              <span data-i18n="[html]login_input_zipCode"></span>
              <input id="zipCode1" type="text" autocomplete="off" class="form-control" /></p>
            <div id="login-input-genre-1">
              <span data-i18n="login_ask_gender"></span>
              <br/>
              <div class="divInput">
                <input type="radio" id="genre1_male" name="genre1" value="2" autocomplete="off"><label for="genre1_male" data-i18n="login_male"></label><br/>
                <br /><input type="radio" id="genre1_female" name="genre1" value="1" autocomplete="off"><label for="genre1_female" data-i18n="login_female"></label>
              </div>
            </div>
            <p id="login-input-grade-1">
              <span data-i18n="grade_question"></span>
              <select id="grade1">
                <option value="" data-i18n="grade_select" selected></option>
<?php
               foreach ($config->grades as $grade) {
                  echo "<option value='".$grade."' data-i18n='grade_".$grade."'></option>";
               }
?>
               </select>
            </p>
            <p id="login-input-studentId-1">
              <span data-i18n="[html]login_input_studentId"></span>
              <input id="studentId1" type="text" autocomplete="off" class="form-control" /></p>
          </div>
        </div>
      </div>
      <div class="login_box panel" cellspacing=0 id="contestant2" style="display:none">
        <div class="panel-head"><b data-i18n="login_teammate"></b><b> 2</b></div>
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
              <input id="registrationCode2" type="text" autocomplete="off" class="form-control" /></p>
            <p><i data-i18n="login_registrationCode_description"></i></p>
            <button id="validateRegCode2" type='button' onclick="validateRegistrationCode(2)" class="btn btn-default" data-i18n="login_validate_code"></button>
            <p><span id="errorRegistrationCode2" style="color:red;font-weight:bold"></span></p>
          </div>
          <div id="noRegistrationCode2" style="display:none" class="form-inline">
            <p id="login-input-firstName-2">
              <span data-i18n="[html]login_input_firstname"></span>
              <input id="firstName2" type="text" autocomplete="off" class="form-control" /></p>
            <p id="login-input-lastName-2">
              <span data-i18n="[html]login_input_lastname"></span>
              <input id="lastName2" type="text" autocomplete="off" class="form-control" /></p>
            <p id="login-input-email-2">
              <span data-i18n="[html]login_input_email"></span>
              <input id="email2" type="text" autocomplete="off" class="form-control" /></p>
            <p id="login-input-zipCode-2">
              <span data-i18n="[html]login_input_zipCode"></span>
              <input id="zipCode2" type="text" autocomplete="off" class="form-control" /></p>
            <div id="login-input-genre-2">
              <span data-i18n="login_ask_gender"></span>
              <br />
              <div class="divInput">
                <input type="radio" id="genre2_male" name="genre2" value="2" autocomplete="off"><label for="genre2_male" data-i18n="login_male"></label>
                <br /><input type="radio" id="genre2_female" name="genre2" value="1" autocomplete="off"><label for="genre2_female" data-i18n="login_female"></label>
              </div>
            </div>
            <p id="login-input-grade-2">
              <span data-i18n="grade_question"></span>
              <select id="grade2">
                <option value="" data-i18n="grade_select" selected></option>
<?php
               foreach ($config->grades as $grade) {
                  echo "<option value='".$grade."' data-i18n='grade_".$grade."'></option>";
               }
?>
              </select></p>
            <p id="login-input-studentId-2">
              <span data-i18n="[html]login_input_studentId"></span>
              <input id="studentId2" type="text" autocomplete="off" class="form-control" /></p>
          </div>
        </div>
      </div>
      <div class="clearfix">
        <button type="button" id="buttonLogin" onclick="validateLoginForm()" data-i18n="login_start_contest" class="btn btn-default"></button>
        <p><span id="LoginResult" style="color:red;font-weight:bold"></span></p>
      </div>
    </div><!-- #divLogin -->
  </div>
</div><!-- #divCheckNbContestants -->
<div id="divStartContest" style="display:none">
   <div data-i18n="[html]contest_start_intro"></div>
   <table>
      <tr>
         <td>   <button type="button" onclick="reallyStartContest()" class="btn btn-primary" data-i18n="contest_start_yes"></button></td>
         <td style="width:50px">
         <td><button type="button" onclick="cancelStartContest()" class="btn btn-primary" data-i18n="contest_start_no"></button></td>
      </tr>
   </table>
</div>

<div id="divAllContestsDone" style="display:none">
   <h2 data-i18n="contest_already_done"></h2>
   <p data-i18n="contest_already_done_details"></p>
   <button type="button" onclick="cancelStartContest()" class="btn btn-primary">Retour</button>
</div>


<div id="divPersonalPage" style="display:none">
   <h2>Page personnelle</h2> 
   <p>
   <table id="personalData">
      <tr><td>Nom :</td><td id="persoLastName"></td></tr>
      <tr><td>Prénom :</td><td id="persoFirstName"></td></tr>
      <tr><td>Classe :</td><td id="persoGrade"></td></tr>
      <tr><td>Qualifié pour la catégorie :</td><td id="persoCategory"></td></tr>
      <tr><td>Qualifié en demi-finale :</td><td id="persoSemifinal"></td></tr>      
   </table>
   </p>
   <p>   
   <table>
      <tr>
         <td><button type="button" id="buttonStartPreparation" onclick="startPreparation()" class="btn btn-primary">Démarrer une préparation</button></td>
         <td style="width:50px">
         <td><button type="button" id="buttonStartContest" onclick="startContest()" class="btn btn-primary" >Démarrer le concours</button></td>
      </tr>
   </table>
   </p>
   <p id="contestAtHomePrevented" style="display:none">
       Votre enseignant a indiqué que le concours officiel doit se faire en classe, avec un code de groupe.<br/>
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
         <td>Classement<br/>établissement</td>
         <td>Accès</td>
      </tr>
   </table>
</div>

<div id="divPassword" style="display:none" class="dialog">
  <p data-i18n="[html]password_warning"></p>
  <p><span data-i18n="access_code"></span> <span id="teamPassword" class="selectable" style="font-size:2em"></span></p>
  <div class="divInput">
    <button type="button" data-i18n="password_confirm" id="buttonConfirmTeamPassword" onclick="confirmTeamPassword()" class="btn btn-default"></button>
  </div>
</div>
</form>
<div id="divImagesLoading" style="display:none" class="dialog">
  <span id="nbImagesLoaded">0</span> <span data-i18n="images_preloaded"></span>
</div>

<div id="divQuestions" style="display:none" autocomplete="off">
   <div class="oldInterface">
      <div class="questionListHeader">
         <table class="chrono" width="95%">
            <tr class="header_time"><td class="fullFeedback" data-i18n="remaining_time"></td><td><span class='minutes'></span>:<span class='seconds'></span></td></tr>
            <tr><td class="fullFeedback" data-i18n="current_score"></td><td><span class='scoreTotalFullFeedback'></span></td></tr>
         </table>
         <p></p>
         <div class="scoreBonus" style="display:none"><b data-i18n="questions_bonus"></b><br/></div>
         <div class="rank" width="95%"></div>
      </div>
      <div class='questionList'>
         <span style="color:red" data-i18n="questions_loading"></span>
      </div>
      <p></p>
      <div style="text-align:center;width:180px;">
         <button type="button" id="buttonClose" class="buttonClose" style="display:none;" data-i18n="questions_finish_early" onclick='tryCloseContest()'></button>
      </div>
      <table class="questionsTable">
         <tr><td>
            <div id="divQuestionParams">
               <table style="width:100%"><tr>
                  <td style="width:10%" data-i18n="[html]top_image"></td>
                  <td><div class="questionTitle"></div></td>
                  <td style="width:25%"><div id="questionPoints"></div></td>
               </tr></table>
            </div>
         </td></tr>
      </table>
   </div>
   <div class="newInterface headerElements">
      <div class="header">
         <table class="header_table">
            <tr>
               <td class="header_logo" data-i18n="[html]top_image_new"></td>
               <td class="header_score"><span data-i18n="current_score"></span><br/><b><span class='scoreTotalFullFeedback'></span></b></td>
               <td class="header_time" id="header_time"><span data-i18n="remaining_time_long"></span> <br/><b><span class='minutes'></span>:<span class='seconds'></span></b></td>
               <td class="header_rank" style="display:none"><span data-i18n="rank"></span> <br/><b><span class="rank" width="95%"></span></b></td>
               <td class="header_button">
                 <button class="button_return_list" type="button" data-i18n="return_to_list" onclick="backToList()" ></button>
               </td>
               <td class="header_button header_button_fullscreen">
                 <button type="button" data-i18n="fullscreen" onclick="toggleFullscreen()" ></button>
               </td>
            </tr>
         </table>
      </div>
      <div class="headerAutoHeight">
         <table class="headerAutoHeight_table">
            <tr>
               <td class="headerAutoHeight_logo" data-i18n="[html]top_image_new"></td>
               <td class="headerAutoHeight_time"><b><span class='minutes'></span>:<span class='seconds'></span></b></td>
               <td class="headerAutoHeight_title"><span class="questionTitle" style="padding-right: 20px"></span><span id="questionStars"></span></td>
               <td class="headerAutoHeight_score"><b><span class='scoreTotalFullFeedback'></span></b></td>
               <td class="headerAutoHeight_button">
                 <button class="button_return_list" type="button" data-i18n="return" onclick="backToList()" ></button>
               </td>
               <td class="headerAutoHeight_button header_button_fullscreen">
                 <button type="button" data-i18n="fullscreen" onclick="toggleFullscreen()" ></button>
               </td>
            </tr>
         </table>
      </div>
      <div class="header_sep_top"></div>
      <div class="layout_table_wrapper">
         <div class="questionListIntro" style="text-align:left;line-height:170%" id="questionListIntro">
            <ul data-i18n="[html]question_list_intro">
            </ul>
         </div>
         <div class="questionList task_icons">
            <span style="color:red" data-i18n="questions_loading"></span>
         </div>
      </div>
   </div>
   <span id="divQuestionsContent" style="display:none">
   </span>
   <span id="divSolutionsContent" style="display:none">
   </span>
   <span id="divGradersContent" style="display:none">
   </span>
</div>

<div id="question-iframe-container" style="display:none" autocomplete="off">
   <!--<div class="questionIframeLoading" data-i18n="questions_loading"></div>-->
   <div class="newInterface questionIframeHeader">
      <span class="questionTitle" style="padding-right: 20px"></span><span id="questionIframeStars"></span>
   </div>
   <iframe src="about:blank" id="question-iframe" scrolling="no" allowfullscreen></iframe>
</div>
<div id="divFooter" style="display:none;text-align:center" autocomplete="off">
   <div class="header_sep_bottom"></div>
   <button type="button" id="buttonCloseNew" class="buttonClose" data-i18n="questions_finish_early" onclick='tryCloseContest()'></button>
</div>

<div id="divClosed" style="display:none" class="dialog" autocomplete="off">
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
         <b data-i18n="[html]closed_remind_password"></b>
      </p>
      <p>
         <span data-i18n="closed_your_password"></span> <span class='selectable' id="remindTeamPassword"></span>
      </p>
      <p id="scoreReminder" style="display:none">
         <span data-i18n="score"></span> <span id="remindScore"></span>
      </p>
   </div>
</div>
<div id="divError" autocomplete="off">
   <b data-i18n="error_server"></b> <p style="float:right;"><a href="#" onclick="$('#divError').hide()">[<span data-i18n="error_close"></span>]</a></p><br/>
   <span id="contentError"></span>
</div>
<?php
  script_tag('/bower_components/jquery/jquery.min.js');
?>
<!--[if lte IE 9]>
  <?php
  // JSON3 shim for IE6-9 compatibility.
  script_tag('/bower_components/json3/lib/json3.min.js');
  // Ajax CORS support for IE9 and lower.
  script_tag('/bower_components/jQuery-ajaxTransport-XDomainRequest/jquery.xdomainrequest.min.js');
  ?>
<![endif]-->
<?php
  // jquery 1.9 is required for IE6+ compatibility.
  script_tag('/bower_components/jquery-ui/jquery-ui.min.js');
  script_tag('/bower_components/jquery-ui-touch-punch/jquery.ui.touch-punch.min.js');
  script_tag('/bower_components/i18next/i18next.min.js');
  script_tag('/bower_components/utf8/utf8.js');
  script_tag('/bower_components/base64/base64.min.js');
  script_tag('/bower_components/pem-platform/task-pr.js');
  script_tag('/raphael-min.js');
  script_tag('/common.js');
  global $config;
?>
<script>
  function updateQueryStringParameter(uri, key, value) {
    var re = new RegExp("([?&])" + key + "=.*?(&|$)", "i");
    var separator = uri.indexOf('?') !== -1 ? "&" : "?";
    if (uri.match(re)) {
      return uri.replace(re, '$1' + key + "=" + value + '$2');
    }
    else {
      return uri + separator + key + "=" + value;
    }
  }
  window.contestsRoot = <?= json_encode(upgrade_url($config->teacherInterface->sAbsoluteStaticPath.'/contests')) ?>;
  window.sAbsoluteStaticPath = <?= json_encode(upgrade_url($config->teacherInterface->sAbsoluteStaticPath.'/')) ?>;
  window.sAssetsStaticPath = <?= json_encode(upgrade_url($config->teacherInterface->sAssetsStaticPath.'/')) ?>;
  window.timestamp = <?= $config->timestamp ?>;
  window.browserIsMobile = <?=$browserIsMobile ? 'true' : 'false' ?>;
  try {
    i18n.init(<?= json_encode([
      'lng' => $config->defaultLanguage,
      'fallbackLng' => [$config->defaultLanguage],
      'fallbackNS' => 'translation',
      'ns' => [
        'namespaces' => $config->customStringsName ? [$config->customStringsName, 'translation'] : ['translation'],
        'defaultNs' => $config->customStringsName ? $config->customStringsName : 'translation',
      ],
      'getAsync' => true,
      'resGetPath' => static_asset('/i18n/__lng__/__ns__.json')
    ]); ?>, function () {
      window.i18nLoaded = true;
      $("title").i18n();
      $("body").i18n();
    });
  } catch(e) {
    // assuming s3 was blocked, so add ?p=1 to url, see contestInterface/config.php
    var newLocation = updateQueryStringParameter(window.location.toString(), 'p', '1');
    if (newLocation != window.location.toString()) {
      window.location = newLocation;
    }
  }
  window.ieMode = false;
</script>
<!--[if IE 6]>
<script>
window.sAbsoluteStaticPath = <?= json_encode(upgrade_url($config->teacherInterface->sAbsoluteStaticPathOldIE.'/')) ?>;
window.contestsRoot = <?= json_encode(upgrade_url($config->teacherInterface->sAbsoluteStaticPathOldIE.'/contests')) ?>;
</script>
<![endif]-->
<!--[if lte IE 9]>
<script>
window.ieMode = true;
</script>
<![endif]-->
</body></html>
