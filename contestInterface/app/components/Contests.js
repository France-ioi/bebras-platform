import UI from '../components';
import contests from '../contests';
import contest from '../contest';
import team from '../team';
import group from '../group';

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
        window.startPracticeByID = this.startPracticeByID.bind(this);
        window.startOpenContestByID = this.startOpenContestByID.bind(this);
	},


    load(data, eventListeners) {
        $('#divContests').show();
        this.selectContestsTab('practice');
        contests.getData(this.onContestsData.bind(this))
    },


    show(guest_mode) {
        $('#contests_tabs').toggle(!guest_mode);
        this.selectContestsTab('practice');
        this.load();
    },


    unload() {
        $('#divContests').hide();
    },


    // start contests or restore participation
    startPracticeByID(ID) {
        var contest_group;
        for(var i=0; i<this.data.contests.practice.length; i++) {
            if(this.data.contests.practice[i].ID == ID) {
                contest_group = this.data.contests.practice[i].group;
                break;
            }
        }

        // one contest in group
        if(contest_group.length == 1) {
            UI.PersonalPage.unload();
            if(this.data.results[contest_group[0].ID]) {
                group.checkGroupFromCode("PersonalPage", this.data.results[contest_group[0].ID].password, false, false);
            } else {
                this.startContestByID(ID);
            }
            return;
        }

        // multiple contests in group
        var options = [];
        for(var i=0; i<contest_group.length; i++) {
            if(this.data.results[contest_group[i].ID]) {
                options.push({
                    text: i18n.t('contest_continue_language') + contest_group[i].language,
                    password: this.data.results[contest_group[i].ID].password
                });
            } else {
                options.push({
                    text: i18n.t('contest_start_language') + contest_group[i].language,
                    ID: contest_group[i].ID
                });
            }
        }

        var self = this;
        UI.StartContestMenu.show(options, function(selection) {
            UI.PersonalPage.unload();
            if(selection['password']) {
                group.checkGroupFromCode("PersonalPage", selection['password'], false, false);
            } else if(selection['ID']) {
                self.startContestByID(selection['ID']);
            }
        })
    },


    startOpenContestByID(ID) {
        UI.PersonalPage.unload();
        if(this.data.results[ID]) {
            group.checkGroupFromCode("PersonalPage", this.data.results[ID].password, false, false);
        } else {
            this.startContestByID(ID);
        }
    },


    startContestByID(ID) {
        contest.get(ID, function(data) {
            app.contestID = ID;
            app.contestFolder = data.folder;
            var user = Object.assign({}, UI.PersonalPage.registrationData);
            user.registrationCode = UI.PersonalPage.registrationData.code;
            team.createTeam([user], function() {
                contest.initContestData(data, ID);
                contest.loadContestData(ID, data.folder);
            });
        });
    },



    // tabs

    selectContestsTab(tab) {
        $('#contests_tabs .tab').removeClass('active');
        $('#contests_tabs div[data-tab="' + tab + '"]').addClass('active');
        $('#contests_tabs_content > div').hide();
        $('#contests_tabs_content div[data-tab="' + tab + '"]').show();
    },



    // render lists


    onContestsData(data) {
        this.data = data;
        this.renderPracticeContests(data.contests.practice, data.results);
        if(data.contests['open']) {
            this.renderOpenContests(data.contests.open, data.results);
        }
        if(data.contests['past']) {
            this.renderPastContests(data.contests.past);
        }
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
                    '<div class="contest_item contest_clickable" onclick="startPracticeByID(\'' + contest.ID + '\')">' +
                        this.getContestCaption(contest) +
                        this.getContestImage(contest) +
                        this.getPracticeContestInfo(contest, results) +
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

    renderOpenContests(contests, results) {
        var colors = ['blanche', 'jaune', 'orange', 'verte', 'bleue'];

        $('#contests_open').empty();
        var html = '', contest;
        for(var j=0; j<contests.length; j++) {
            var contest = contests[j];

            var locked = false;
            if(contest.categoryColor !== null && app.user.algoreaCategory !== null) {
                var contextColorIdx = colors.indexOf(contest.categoryColor);
                var userColorIdx = colors.indexOf(app.user.algoreaCategory);
                if(userColorIdx < contextColorIdx) {
                    locked = true;
                }
            }

            if(locked) {
                html += '<div class="contest_item contest_locked">';
            } else {
                html += '<div class="contest_item contest_clickable" onclick="startOpenContestByID(\'' + contest.ID + '\')">';
            }

            html +=
                    this.getContestCaption(contest) +
                    this.getContestImage(contest) +
                    (locked ? this.getLockedContestInfo() : this.getOpenContestInfo(contest, results)) +
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
                        this.getPastContestInfo(contest) +
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
        if(contest.thumbnail) {
            var url = 'contests/' + contest.folder + '/' + contest.thumbnail;
        } else {
            var url = 'images/img-placeholder.png';
        }
        return '<div class="contest_thumb" style="background-image: url(' + url + ')"></div>';
    },


    getPracticeContestInfo(contest, results) {
        if(!contest['group']) return '';
        var lines = [];
        for(var i=0; i<contest.group.length; i++) {
            var group_item = contest.group[i];
            var result = results[group_item.ID];
            if(result) {
                lines.push(
                    this.formatScore(result) +
                    i18n.t('contest_points') +
                    result.date +
                    (group_item.language ? i18n.t('contest_in_lang') + group_item.language : '')
                );
            }
        }
        return '<div class="contest_info">' + lines.join('<br>') + '</div>';
    },


    getOpenContestInfo(contest, results) {
        var lines = [];

        if(results[contest.ID] && results[contest.ID].remainingSeconds > 0 && results[contest.ID].date !== null) {
            lines.push(
                i18n.t('contest_started') +
                Math.floor(results[contest.ID].remainingSeconds / 60) +
                i18n.t('contest_mins_left')
            );
        } else {
            lines.push(i18n.t('contest_open_until') + contest.endDate);
        }
        return '<div class="contest_info">' + lines.join('<br>') + '</div>';
    },


    getLockedContestInfo() {
        return '<div class="contest_info">' + i18n.t('contest_locked') + '</div>';
    },

    getPastContestInfo(contest) {
        var lines = [];
        lines.push(
            contest.score +
            i18n.t('contest_points') +
            contest.date +
            (contest.language ? i18n.t('contest_in_lang') + contest.language : '')
        );
        lines.push(
            contest.rank +
            i18n.t('contest_position') +
            contest.rankTotal
        );
        lines.push(
            i18n.t('grade_' + contest.grade) +
            (contest.nbContestants !== null ? ', ' + i18n.t('contest_info_' + (contest.nbContestants == 1 ? 'individual' : 'team')) : '')
        );
        return '<div class="contest_info">' + lines.join('<br>') + '</div>';
    },


    formatScore(result) {
        var score = '-';
        if (result.sumScores !== null) {
            score = parseInt(result.sumScores, 10);
            if (result.score !== null) {
                score = Math.max(score, parseInt(result.score, 10)) + i18n.t('contest_tmp_score');
            }
        } else if (result.score !== null) {
            score = parseInt(result.score, 10);
        }
        return score;
    }

}