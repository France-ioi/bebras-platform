import questionIframeOld from "./QuestionIframeOld";
import questionIframeNew from "./QuestionIframeNew";

if(window.contestLoaderVersion === '2') {
    var questionIframe = questionIframeNew;
} else {
    var questionIframe = questionIframeOld;
}

//console.log(window.contestLoaderVersion, questionIframe.version)

export default questionIframe;