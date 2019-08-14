import team from '../team';
import contest from '../contest';


var page_storage = {

    key: 'DEBUG_PANEL_PAGE_IDX',

    get: function() {
        var idx = window.localStorage.getItem(this.key);
        return parseInt(JSON.parse(idx), 10);
    },

    set: function(idx) {
        window.localStorage.setItem(this.key, JSON.stringify(idx));
    }
}





var panel = {

    pages: [
        {
            title: 'Home page',
            callback: function() {
            }
        },
        /*
        {
            title: 'Start contest',
            callback: function() {
                window.selectMainTab('school');
            }
        },
        */
        {
            title: '-- Start contest - Personal Information',
            callback: function() {
                window.checkGroup();
            }
        },
        {
            title: '-- Start contest - Access Code',
            callback: function() {
                window.checkGroup();
                team.createTeam([]);
            }
        },
        {
            title: 'Contest',
            callback: function() {
                window.checkGroup();
                team.createTeam([], function() {
                    window.confirmTeamPassword();
                });
            }
        },
        {
            title: '-- Contest - Thanks for participating',
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
        /*
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
        */
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
        var select = panel.find('select');
        select.on('change', function() {
            var page_idx = $(this).val();
            page_storage.set(page_idx);
            window.location.reload();
        });

        var page_idx = page_storage.get();
        if(this.pages[page_idx]) {
            this.pages[page_idx].callback();
            select.val(page_idx);
        }

    }

}

export default panel;