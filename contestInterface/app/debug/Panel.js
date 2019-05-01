import team from '../team';
import contest from '../contest';

var panel = {

    pages: [
        {
            title: '1. Start contest',
            callback: function() {
                window.selectMainTab('school');
            }
        },
        {
            title: '1.1. Start contest - Personal Information',
            callback: function() {
                window.checkGroup();
            }
        },
        {
            title: '1.2. Start contest - Access Code',
            callback: function() {
                window.checkGroup();
                team.createTeam([]);
            }
        },
        {
            title: '2. Contest (old interface)',
            callback: function() {
                window.checkGroup();
                team.createTeam([], function() {
                    window.confirmTeamPassword();
                });
            }
        },
        {
            title: '2.1. Contest - Thanks for participating',
            callback: function() {
                window.checkGroup();
                team.createTeam([], function() {
                    window.confirmTeamPassword();
                    setTimeout(function() {
                        contest.tryCloseContest();
                    }, 1000)
                });
            }
        },



        {
            title: 'Practice',
            callback: function() {
                window.selectMainTab('home');
            }
        },
        {
            title: 'Continue a contest',
            callback: function() {
                window.selectMainTab('continue');
            }
        },
    ],


    getOptions: function() {
        var html = '';
        for(var i=0; i<this.pages.length; i++) {
            html += '<option value="' + i + '">' + this.pages[i].title + '</option>';
        }
        return html;
    },


    init: function() {
        var panel = $(
            '<div id="debug-panel">' +
                '<select>' + this.getOptions() + '</select>' +
            '<div>'
        );
        $(document.body).append(panel);
        var self = this;
        panel.find('select').on('change', function() {
            var idx = $(this).val();
            if(self.pages[idx]) {
                self.pages[idx].callback();
            }
        });
    },


    setMode: function() {

    }

}

export default panel;