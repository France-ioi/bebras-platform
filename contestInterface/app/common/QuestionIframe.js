import questionIframeOld from "./QuestionIframeOld";
import questionIframeNew from "./QuestionIframeNew";

if(window.config.contestLoaderVersion === '2') {
    var questionIframe = questionIframeNew;
} else {
    var questionIframe = questionIframeOld;
}

export default questionIframe;