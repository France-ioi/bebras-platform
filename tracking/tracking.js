/* Copyright (c) 2012 Association France-ioi, MIT License http://opensource.org/licenses/MIT */

var allData = [];
var dataSending = [];
var sending = false;
var toSend = false;

function initErrorHandler() {
   $( "body" ).ajaxError(function(e, jqxhr, settings, exception) {
      sending = false;
      toSend = true;
   });
}

$(function() {
   initErrorHandler();
   $.receiveMessage(
      function(e){
         var data = $.parseJSON(e.data);
         if (data.send === true) {
            toSend = true;
         } else {
            var d = new Date();
            data.clientTime = Math.floor(d.getTime()/1000);
            allData.push(data);
         }
         if (allData.length > 50) { // TODO: 50 when not testing
            toSend = true;
         }
         if (toSend) {
            if (sending) {
               return;
            }
            for (var iData = 0; iData < allData.length; iData++) {
//               for (var iDup = 0; iDup < 1000; iDup++) {
                  dataSending.push(allData[iData]);
//               }
            }
            allData = [];
            toSend = false;
            sending = true;
            $.post("trackData.php", {data: dataSending}, function() { // TODO: correct url
               dataSending = [];
               sending = false;
            }, "json");
         }
      }
   );
});
