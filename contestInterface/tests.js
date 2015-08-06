/* Copyright (c) 2012 Association France-ioi, MIT License http://opensource.org/licenses/MIT */

var startTime;
var curTest = 0;
var nbTests = 1000000;
var typeTest = 1;

function startTest(typeT) {
   typeTest = typeT;
   var d = new Date();
   startTime = d.getTime();
   test();
}

function test() {
   var batchSize = 1;
   var d = new Date();
   diffTime = Math.floor(d.getTime() - startTime) / 1000;
   $("#nbSecs").html(diffTime);
   if (curTest > 0)
      $("#insertsPerSecond").html(curTest*batchSize/diffTime);
   curTest++;
   $("#nbAttempts").html(curTest);
   for (var iAns = 0; iAns < batchSize; iAns++) {
      var qid = Math.floor(Math.random() * 1000000000);
      questionKeys[qid] = "test";
      answersToSend[qid] = { answer: "test" + (curTest * 1000 + iAns), score: curTest, sending:false };
   }
   sendAnswers();
}

