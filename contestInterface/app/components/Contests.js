import UI from '../components';
import contests from '../contests';
import ContestQuestionRecoveryPage from './ContestQuestionRecoveryPage';

var types_order = [
    'algorea_white',
    'algorea_yellow',
    'algorea_orange',
    'algorea_green',
    'alkindi'
];


export default {


	init () {
        window.selectContestsTab = this.selectContestsTab.bind(this);
        window.startContestByID = this.startContestByID.bind(this);
	},


    load(data, eventListeners) {
        $('#divContests').show();
        this.selectContestsTab('practice');
        contests.getData(this.render.bind(this))
    },


    unload() {
        $('#divContests').hide();
    },


    startContestByID(id) {
        console.log(startContestByID, id)
    },


    selectContestsTab(tab) {
        $('#contests_tabs .tab').removeClass('active');
        $('#contests_tabs div[data-tab="' + tab + '"]').addClass('active');
        $('#contests_tabs_content > div').hide();
        $('#contests_tabs_content div[data-tab="' + tab + '"]').show();
    },


    render(data) {
        this.renderPracticeContests(data.contests.practice, data.results);
        this.renderOpenContests(data.contests.open);
        this.renderPastContests(data.contests.past, data.results);
    },


    renderPracticeContests(contests, results) {
        $('#contests_practice').empty();
        for(var i=0; i<types_order.length; i++) {
            var type = types_order[i];

            var html = '', contest;
            for(var j=0; j<contests.length; j++) {
                var contest = contests[j];
                if(contest.type != type) continue;
                html +=
                    '<div class="contest_item contest_clickable" onclick="startContestByID(' + contest.ID + ')">' +
                        this.getContestCaption(contest) +
                        this.getContestImage(contest) +
                        this.getContestResultInfo(contest, results) +
                    '</div>';
            }
            if(html != '') {
                $('#contests_practice').append(
                    '<h2>' + i18n.t('contest_type_' + type) + '</h2>' +
                    '<div class="contests_list">' + html + '</div>'
                );
            }
        }
    },

    renderOpenContests(contests) {
        $('#contests_open').empty();
        var html = '', contest;
        for(var j=0; j<contests.length; j++) {
            var contest = contests[j];
            html +=
                '<div class="contest_item contest_clickable" onclick="startContestByID(' + contest.ID + ')">' +
                    this.getContestCaption(contest) +
                    this.getContestImage(contest) +
                    this.getOpenContestInfo(contest) +
                '</div>';
        }
        if(html != '') {
            $('#contests_open').append(
                '<h2>' + i18n.t('contests_open') + '</h2>' +
                '<div class="contests_list">' + html + '</div>'
            );
        }
    },


    renderPastContests(contests) {
        $('#contests_past').empty();
        if(!contests.length) return;

        for(var i=0; i<types_order.length; i++) {
            var type = types_order[i];
            var html = '', contest;
            for(var j=0; j<contests.length; j++) {
                contest = contests[j];
                html +=
                    '<div class="contest_item">' +
                        this.getContestCaption(contest) +
                        this.getContestImage(contest) +
                        this.getContestResultInfo(contest, results) +
                    '</div>';
            }
            if(html != '') {
                $('#contests_past').append(
                    '<h2>' + i18n.t('contest_type_' + type) + '</h2>' +
                    '<div class="contests_list">' + html + '</div>'
                );
            }
        }
    },


    getContestCaption(contest) {
        return '<div class="contest_caption">' + contest.name + ' ' + contest.year + '</div>';
    },

    getContestImage(contest) {
        var url = 'images/img-placeholder.png';
        return '<div class="contest_thumb" style="background-image: url(' + url + ')"></div>';
    },

    getContestResultInfo(contest, results) {
        var lines = [];
        for(var id in contest.languages) {
            if(id in results) {
                lines.push(
                    results[id].score +
                    i18n.t('contest_points') +
                    results[id].date +
                    (contest.languages[id] ? i18n.t('contest_in_lang') + contest.languages[id] : '')
                );
            }
        }
        return '<div class="contest_info">' + lines.join('<br>') + '</div>';
    },

    getOpenContestInfo(contest) {
        var status = 'todo';
        return '<div class="contest_info">' + status + '</div>';
    }

}