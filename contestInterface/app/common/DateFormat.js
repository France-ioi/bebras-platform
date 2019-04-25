function toDate(dateStr, sep, fromServer) {
    var dateOnly = dateStr.split(" ")[0];
    var timeParts = dateStr.split(" ")[1].split(":");
    var parts = dateOnly.split(sep);
    if (fromServer) {
        return new Date(Date.UTC(parts[0], parts[1] - 1, parts[2], timeParts[0], timeParts[1]));
    }
    return new Date(parts[2], parts[1] - 1, parts[0], timeParts[0], timeParts[1]);
}


function dateToDisplay(d) {
    return $.datepicker.formatDate("dd/mm/yy", d);
}


function utc(cellValue) {
    if ((cellValue == undefined) || (cellValue == "0000-00-00 00:00:00") || (cellValue == "")) {
        return "";
    }
    var localDate = toDate(cellValue, "-", true, true);
    return dateToDisplay(localDate);
}


export default {
    utc
}