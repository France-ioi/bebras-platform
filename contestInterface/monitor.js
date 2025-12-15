var currentPassword = null;
var checkTeamTimeout = null;
var currentData = [];
var dataDate = new Date();

function renderDateCell(time, currentTime) {
    if(!time) {
        return '<td>-</td>';
    }
    var date = new Date(time);
    var currentDate = new Date(currentTime);
    var localDiff = dataDate.getTime() - currentDate.getTime();
    var adjustedDate = new Date(date.getTime() + localDiff);
    var minutesDiff = Math.floor((currentDate.getTime() - date.getTime()) / (1000 * 60));
    if(minutesDiff < 1) { minutesDiff = '&lt; 1';}
    return '<td>' + adjustedDate.toLocaleString() + '<br>(' + minutesDiff + ' minute(s))</td>';
}

function comparePriority(a, b) {
    return a.priority < b.priority ? 1 : (a.priority == b.priority ? 0 : -1);
}

function compareNames(a, b) {
    return a.name > b.name ? 1 : (a.name == b.name ? 0 : -1);
}

function compareCreationDate(a, b) {
    return a.createTime < b.createTime ? 1 : (a.createTime == b.createTime ? 0 : -1);
}

function chainCompares(compares) {
    return function(a, b) {
        for(var i = 0; i < compares.length; i++) {
            var compare = compares[i];
            var result = compare(a, b);
            if(result != 0) {
                return result;
            }
        }
        return 0;
    }
}

function renderTable() {
    var showTeamCodes = $('#showTeamCodes').is(':checked');
    var compareChains = {
        'status': [comparePriority, compareNames, compareCreationDate],
        'name': [compareNames, comparePriority, compareCreationDate],
        'createTime': [compareCreationDate, comparePriority, compareNames]
    }
    var compare = chainCompares(compareChains[$('#sortBy').val()]);

    var html = '';
    var lines = [];

    for(var i = 0; i < currentData.length; i++) {
        var team = currentData[i];
        var cls = "";
        var priority = 0;
        var name = team.firstName + ' ' + team.lastName;
        var subHtml = '';
        subHtml += '<td>';
        subHtml += name + '<br>';
        if(team.studentId) { subHtml += team.studentId + '<br>'; }
        if(team.zipCode) { subHtml += team.zipCode + '<br>'; }
        subHtml += '<span class="team-code">' + (showTeamCodes ? team.password : team.password.substr(0, 3) + '...' ) + '</span>';
        subHtml += '</td>';
        var pingMinutes = Math.floor(((new Date(team.currentTime)).getTime() - (new Date(team.lastPingTime)).getTime()) / (1000 * 60));
        if(team.finalAnswerTime) {
            cls = "team-finished";
            subHtml += '<td>A terminé l\'épreuve</td>';
        } else if(team.endTime) {
            priority = 5;
            cls = "team-error";
            subHtml += '<td>A terminé l\'épreuve, dernières réponses en attente</td>';
        } else if(!team.startTime) {
            priority = 3;
            cls = "";
            subHtml += '<td>Démarrage de l\'épreuve</td>';
        } else if(!team.lastPingTime) {
            priority = 0;
            cls = "";
            subHtml += '<td>Informations de connexion non disponibles</td>';
        } else if(pingMinutes <= 2) {
            priority = 1;
            cls = "team-ok";
            subHtml += '<td>Connectée</td>';
        } else if(pingMinutes <= 5) {
            priority = 4;
            cls = "team-warning";
            subHtml += '<td>Connexion possiblement perdue</td>';
        } else {
            priority = 6;
            cls = "team-error";
            subHtml += '<td><b>Connexion perdue</b></td>';
        }
        subHtml += renderDateCell(team.lastPingTime, team.currentTime);
        subHtml += renderDateCell(team.lastAnswerTime, team.currentTime);
        subHtml += renderDateCell(team.createTime, team.currentTime);
        subHtml += renderDateCell(team.startTime, team.currentTime);
        subHtml += renderDateCell(team.endTime, team.currentTime);
        subHtml += renderDateCell(team.finalAnswerTime, team.currentTime);
        lines.push({
            priority: priority,
            createTime: team.createTime,
            name: name,
            html: '<tr class="' + cls + '">' + subHtml + '</tr>'
            });
    }
    lines.sort(compare);
    for(var i = 0; i < lines.length; i++) {
        html += lines[i].html;
    }
    $('#result-body').html(html);
    if(currentData.length) {
        $('#update-time').text(new Date().toLocaleString());
    } else {
        $('#update-time').text('-');
    }
}


function checkGroupLoop() {
    if(checkTeamTimeout) {
        clearTimeout(checkTeamTimeout);
    }
    $.post('monitorData.php', {password: currentPassword}, function(data) {
        if(!data.success) {
            $('#check-error').text(data.error);
            return;
        }
        currentData = data.teams;
        currentData.sort(function(a, b) {return a.password > b.password ? 1 : -1; });
        dataDate = new Date();
        renderTable();
        setTimeout(checkGroupLoop, 60000);
    }, 'json');
}

function checkGroup() {
    currentPassword = $('#groupPassword').val();
    currentData = [];
    $('#check-error').text('');
    renderTable();
    checkGroupLoop();
}