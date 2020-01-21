import fetch from './Fetch';
/**
 * Log activity on a question (question load, attempt)
 */
function logActivity(teamID, questionID, type, answer, score) {
    if (!window.config.logActivity) {
        return;
    }
    fetch("data.php", {
        controller: "Activity",
        action: "add",
        teamID: teamID,
        questionID: questionID,
        type: type,
        answer: answer,
        score: score
    });
}


export default logActivity;