<div id="divQuestions" style="display:none" autocomplete="off">
    <div class="oldInterface">
        <div class="questionListHeader">
            <table class="chrono" width="95%">
                <tr class="header_time">
                    <td class="fullFeedback" data-i18n="remaining_time"></td>
                    <td><span class='minutes'></span>:<span class='seconds'></span></td>
                </tr>
                <tr>
                    <td class="fullFeedback" data-i18n="current_score"></td>
                    <td><span class='scoreTotalFullFeedback'></span></td>
                </tr>
            </table>
            <p></p>
            <div class="scoreBonus" style="display:none">
                <b data-i18n="questions_bonus"></b><
                br />
            </div>
            <div class="rank" width="95%"></div>
        </div>
        <div class='questionList'>
            <span style="color:red" data-i18n="questions_loading"></span>
        </div>
        <p></p>

        <div style="text-align:center;width:180px;">
            <button type="button" id="buttonClose" class="buttonClose" style="display:none;" data-i18n="questions_finish_early"></button>
        </div>

        <table class="questionsTable">
            <tr>
                <td>
                    <div id="divQuestionParams">
                        <table style="width:100%">
                            <tr>
                                <td style="width:10%" data-i18n="[html]top_image"></td>
                                <td>
                                    <div class="questionTitle"></div>
                                </td>
                                <td style="width:25%">
                                    <div id="questionPoints"></div>
                                </td>
                            </tr>
                        </table>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="newInterface headerElements">
        <div class="header">
            <table class="header_table">
            <tr>
                <td class="header_logo" data-i18n="[html]top_image_new"></td>
                <td class="header_score"><span data-i18n="current_score"></span><br /><b><span class='scoreTotalFullFeedback'></span></b></td>
                <td class="header_time" id="header_time"><span data-i18n="remaining_time_long"></span> <br /><b><span class='minutes'></span>:<span class='seconds'></span></b></td>
                <td class="header_rank" style="display:none"><span data-i18n="rank"></span> <br /><b><span class="rank" width="95%"></span></b></td>
                <td class="header_button">
                <button class="button_return_list" type="button" data-i18n="return_to_list" onclick="backToList()"></button>
                </td>
                <td class="header_button header_button_fullscreen">
                <button type="button" data-i18n="fullscreen" onclick="toggleFullscreen()"></button>
                </td>
            </tr>
            </table>
        </div>

        <div class="headerAutoHeight">
            <table class="headerAutoHeight_table">
            <tr>
                <td class="headerAutoHeight_logo" data-i18n="[html]top_image_new"></td>
                <td class="headerAutoHeight_time">
                    <b><span class='minutes'></span>:<span class='seconds'></span></b>
                </td>
                <td class="headerAutoHeight_title">
                    <span class="questionTitle" style="padding-right: 20px"></span>
                    <span id="questionStars"></span>
                </td>
                <td class="headerAutoHeight_score">
                    <b><span class='scoreTotalFullFeedback'></span></b>
                </td>
                <td class="headerAutoHeight_button">
                    <button class="button_return_list" type="button" data-i18n="return" onclick="backToList()"></button>
                </td>
                <td class="headerAutoHeight_button header_button_fullscreen">
                    <button type="button" data-i18n="fullscreen" onclick="toggleFullscreen()"></button>
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

    <span id="divQuestionsContent" style="display:none"></span>
    <span id="divSolutionsContent" style="display:none"></span>
    <span id="divGradersContent" style="display:none"></span>
</div>