import config from './Config';
/**
 * Log activity on a question (question load, attempt)
 */
function logActivity(teamID, questionID, type, answer, score) {
    if (typeof window.config == "undefined") {
        config.get(function() {
            logActivity(teamID, questionID, type, answer, score);
        });
        return;
    }
    if (!window.config.logActivity) {
        return;
    }
    $.post("activity.php", {
        teamID: teamID,
        questionID: questionID,
        type: type,
        answer: answer,
        score: score
    });
}


export default logActivity;