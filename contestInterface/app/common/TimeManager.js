import Utils from "./Utils";
import UI from '../components';

/*
 * TimeManager is in charge of checking and displaying how much time contestants
 * still have to answer questions.
 * all times are in seconds since 01/01/70
 */
var TimeManager = {
    ended: false, // is set to true once the contest is closed
    initialRemainingSeconds: null, // time remaining when the contest is loaded (in case of an interruption)
    timeStart: null, // when the contest was loaded (potentially after an interruption)
    totalTime: null, // time allocated to this contest
    endTimeCallback: null, // function to call when out of time
    interval: null,
    prevTime: null,
    synchronizing: false,
    syncCounter: 0, // counter used to limit number of pending getRemainingTime requests

    setTotalTime: function(totalTime) {
        this.totalTime = totalTime;
    },

    init: function(isTimed, initialRemainingSeconds, ended, contestOverCallback, endTimeCallback) {
        this.initialRemainingSeconds = parseInt(initialRemainingSeconds);
        this.ended = ended;
        this.endTimeCallback = endTimeCallback;
        var curDate = new Date();
        this.timeStart = curDate.getTime() / 1000;
        if (this.ended) {
            contestOverCallback();
        } else if (isTimed) {
            this.prevTime = this.timeStart;
            this.updateTime();
            this.interval = setInterval(this.updateTime, 1000);
            this.minuteInterval = setInterval(
                this.minuteIntervalHandler,
                60000
            );
        } else {
            UI.OldContestHeader.hideHeaderTime();
        }
    },

    getRemainingSeconds: function() {
        var curDate = new Date();
        var curTime = curDate.getTime() / 1000;
        var usedSeconds = curTime - this.timeStart;
        var remainingSeconds = this.initialRemainingSeconds - usedSeconds;
        if (remainingSeconds < 0) {
            remainingSeconds = 0;
        }
        return remainingSeconds;
    },

    // fallback when sync with server fails:
    simpleTimeAdjustment: function() {
        var curDate = new Date();
        var timeDiff = curDate.getTime() / 1000 - TimeManager.prevTime;
        TimeManager.timeStart += timeDiff - 1;
        setTimeout(function() {
            TimeManager.syncWithServer();
        }, 120000);
    },

    syncWithServer: function() {
        if (this.syncCounter >= 1) {
            //console.log('ignored spurious call to syncWithServer');
            return;
        }
        this.syncCounter += 1;
        TimeManager.synchronizing = true;
        // common selector, edits many places
        UI.OldContestHeader.updateMinutes("");
        UI.OldContestHeader.updateSeconds("synchro...");
        var self = this;
        $.post(
            "data.php",
            { SID: app.SID, action: "getRemainingSeconds", teamID: app.teamID },
            function(data) {
                if (data.success) {
                    var remainingSeconds = self.getRemainingSeconds();
                    TimeManager.timeStart =
                        TimeManager.timeStart +
                        parseInt(data.remainingSeconds) -
                        remainingSeconds;
                    /*
                var curDate = new Date();
                var curTime = curDate.getTime() / 1000;
                console.log("remainingSeconds before sync : " + remainingSeconds + " timeStart : " + TimeManager.timeStart);
                TimeManager.timeStart = curTime - (TimeManager.initialRemainingSeconds - parseInt(data.remainingSeconds));
                remainingSeconds = self.getRemainingSeconds();
                console.log("remainingSeconds after sync : " + remainingSeconds + " timeStart : " + TimeManager.timeStart);
                this.prevTime = curTime;
                */
                } else {
                    TimeManager.simpleTimeAdjustment();
                }
            },
            "json"
        )
            .done(function() {
                var curDate = new Date();
                TimeManager.prevTime = curDate.getTime() / 1000;
                TimeManager.synchronizing = false;
            })
            .fail(function() {
                TimeManager.simpleTimeAdjustment();
                TimeManager.synchronizing = false;
            });
    },

    minuteIntervalHandler: function() {
        TimeManager.syncCounter = 0;
    },

    updateTime: function() {
        if (TimeManager.ended || TimeManager.synchronizing) {
            return;
        }
        var curDate = new Date();
        var curTime = curDate.getTime() / 1000;
        var timeDiff = Math.abs(curTime - TimeManager.prevTime);
        // We traveled through time, more than 60s difference compared to 1 second ago !
        if (timeDiff > 60 || timeDiff < -60) {
            TimeManager.syncWithServer();
            return;
        }
        TimeManager.prevTime = curTime;
        var remainingSeconds = TimeManager.getRemainingSeconds();
        var minutes = Math.floor(remainingSeconds / 60);
        var seconds = Math.floor(remainingSeconds - 60 * minutes);
        UI.OldContestHeader.updateMinutes(minutes);
        UI.OldContestHeader.updateSeconds(Utils.pad2(seconds));
        if (remainingSeconds <= 0) {
            clearInterval(this.interval);
            clearInterval(this.minuteInterval);
            TimeManager.endTimeCallback();
        }
    },

    setEnded: function(ended) {
        this.ended = ended;
    },

    stopNow: function() {
        var curDate = new Date();
        this.ended = true;
    },

    isContestOver: function() {
        return this.ended;
    }
};


export default TimeManager;