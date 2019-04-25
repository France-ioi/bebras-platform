var Utils = {

    disableButton: function(buttonId) {
        var button = $("#" + buttonId);
        if (button.attr("disabled")) {
            return false;
        }
        button.attr("disabled", true);
        return true;
    },

    enableButton: function(buttonId) {
        var button = $("#" + buttonId);
        button.attr("disabled", false);
    },

    pad2: function(number) {
        if (number < 10) {
            return "0" + number;
        }
        return number;
    },

    /*
     * Returns an array with numbers 0 to nbValues -1.
     * Unless preventShuffle is true, the order is "random", but
     * is fully determined by the value of the integer orderKey
     */
    getShuffledOrder: function(nbValues, orderKey, preventShuffle) {
        var order = [];
        for (var iValue = 0; iValue < nbValues; iValue++) {
            order.push(iValue);
        }
        if (preventShuffle) {
            return order;
        }
        for (iValue = 0; iValue < nbValues; iValue++) {
            var pos = iValue + (orderKey % (nbValues - iValue));
            var tmp = order[iValue];
            order[iValue] = order[pos];
            order[pos] = tmp;
        }
        return order;
    }
};

export default Utils;