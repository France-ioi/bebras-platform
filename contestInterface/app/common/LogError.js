import fetch from './Fetch';

var logToConsole = function (logStr) {
    if (window.console) {
       console.error(logStr);
    }
 };

/* global error handler */
var nbErrorsSent = 0;

var logError = function () {
    var chunks = [];
    try {
        var n = arguments.length, i;
        if (app.currentQuestionKey !== undefined) {
            chunks.push(["questionKey", app.currentQuestionKey]);
        }
        for (i = 0; i < n; i++) {
        var arg = arguments[i];
        if (typeof arg === "string") {
            chunks.push([i, arg]);
        } else if (typeof arg === "object") {
            if (typeof arg.name === "string") {
                chunks.push([i, "name", arg.name]);
            }
            if (typeof arg.message === "string") {
                chunks.push([i, "message", arg.message]);
            }
            if (typeof arg.stack === "string") {
                chunks.push([i, "stack", arg.stack]);
            }
            if (typeof arg.details === "object" && arg.details !== null) {
                var details = arg.details;
                if (details.length >= 4) {
                    chunks.push([i, "details", "message", details[0]]);
                    chunks.push([i, "details", "file", details[1]]);
                    chunks.push([i, "details", "line", details[2]]);
                    chunks.push([i, "details", "column", details[3]]);
                    var ex = details[4];
                    if (ex && typeof ex === "object") {
                    chunks.push([i, "details", "ex", "name", ex.name]);
                    chunks.push([i, "details", "ex", "message", ex.message]);
                    chunks.push([i, "details", "ex", "stack", ex.stack]);
                    }
                } else {
                    chunks.push([i, "details", "keys", Object.keys(details)]);
                }
            }
            chunks.push([i, "keys", Object.keys(arg)]);
        } else {
            chunks.push([i, "type", typeof arg]);
        }
        }
    } catch (ex) {
        chunks.push(["oops", ex.toString()]);
        if (typeof ex.stack === "string") {
        chunks.push(["oops", "stack", ex.stack]);
        }
    }
    var logStr;
    try {
        logStr = JSON.stringify(chunks);
    } catch (ex) {
        logStr = ex.toString();
        if (typeof ex.stack === "string") {
        logStr += "\n" + ex.stack;
        }
    }
    logToConsole(logStr);
    nbErrorsSent = nbErrorsSent + 1;
    if (nbErrorsSent > 10) {
        return;
    }
    fetch('logError.php', {errormsg: logStr, questionKey: app.currentQuestionKey}, function (data) {
        if (!data || !data.success) {
        logToConsole('error from logError.php');
        }
    }).fail(function () {
        logToConsole('error calling logError.php');
    });
};

window.onerror = function () {
    console.error(arguments)
    logError({
        message: 'global error handler',
        details: Array.prototype.slice.call(arguments)
    });
};

export default logError;