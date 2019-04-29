import contest from '../contest'
import questionIframe from '../common/QuestionIframe';
import logError from '../common/LogError';

export default {
	init () {
		$('#buttonClose').click(function() {
			contest.tryCloseContest();
		})
	},
	load (data, eventListeners) {
		$("#question-iframe-container").show();
	},
	unload () {
		$("#question-iframe-container").hide();
	},
	showNewInterface () {
		$("#question-iframe-container").addClass("newInterfaceIframeContainer").show();
	},
	showOldInterface () {
		$("#question-iframe-container").addClass("oldInterfaceIframeContainer").show();
		$("#question-iframe-container").css("position", "absolute");
	},
	getIframe () {
		return $('#question-iframe')[0];
	},
	getHeight () {
		return $('#question-iframe').height()
	},
	updateIFrame () {
		$('#question-iframe').remove();

		var iframe = document.createElement('iframe');
		iframe.setAttribute('id', 'question-iframe');
		iframe.setAttribute('scrolling', 'no');
		iframe.setAttribute('src', 'about:blank');
		iframe.setAttribute('allowFullScreen', '');

		var content = '<!DOCTYPE html>' +
			'<html><head><meta http-equiv="X-UA-Compatible" content="IE=edge"></head>' +
			'<body></body></html>';
		var ctnr = document.getElementById('question-iframe-container');
		ctnr.appendChild(iframe);

		iframe.contentWindow.document.open('text/html', 'replace');
		iframe.contentWindow.document.write(content);
		if (typeof iframe.contentWindow.document.close === 'function') {
			iframe.contentWindow.document.close();
		}

		// Chrome doesn't allow to set this attribute until iframe contents are
		// loaded
		iframe.setAttribute('allowFullScreen', true);
	},
	updateBorder (body, newInterface) {
		var border = "border: 1px solid #000000;";
		if (newInterface) {
		   border = "";
		}
		body.append('<div id="jsContent"></div><div id="container" style="' + border + 'padding: 5px;"><div class="question" style="font-size: 20px; font-weight: bold;">' + i18n.t("content_is_loading") + '</div></div>');
	},
	updatePadding (doc, px) {
		$('#container', doc).css('padding', px);
	},
	updateHeight (height, questionIframe) {
		if (height < 700 && !questionIframe.autoHeight) {
			height = 700;
		}
		$('#question-iframe').css('height', height + 'px');
	},
	loadQuestion (body, questionKey) {
		body.find('#container > .question').remove();
		// We cannot just clone the element, because it'll result in an strange id conflict, even if we put the result in an iframe
		var questionContent = $('#question-' + questionKey).html();
		if (!questionContent) {
			questionContent = i18n.t("error_loading_content");
		}
		body.find('#container').append('<div id="question-' + questionKey + '" class="question">' + questionContent + '</div>');
	},
	loadQuestionJS (questionIframe, questionKey) {
		$('.js-module-' + questionKey).each(function () {
			var jsModuleId = 'js-module-' + $(this).attr('data-content');
			var jsModuleDiv = $('#' + jsModuleId);
			if (jsModuleDiv.length) {
				questionIframe.addJsContent(jsModuleDiv.attr('data-content'));
			} else {
				// This module was split in parts, fetch each part
				var jsModulePart = 0;
				var jsContent = '';
				jsModuleDiv = $('#' + jsModuleId + '_0');
				while (jsModuleDiv.length) {
					jsContent += jsModuleDiv.attr('data-content');
					jsModulePart += 1;
					jsModuleDiv = $('#' + jsModuleId + '_' + jsModulePart);
				}
				if (jsContent) {
					questionIframe.addJsContent(jsContent);
				} else {
					logError('Unable to find JS module ' + jsModuleId);
				}
			}
			//questionIframe.addJsContent($('#'+jsModuleId).attr('data-content'));
		});
	},
	loadQuestionCSS (questionIframe, questionKey) {
		$('.css-module-' + questionKey).each(function () {
			var cssModuleId = 'css-module-' + $(this).attr('data-content');
			questionIframe.addCssContent($('#' + cssModuleId).attr('data-content'));
		});
	},
	hideQuestionIframe () {
		$('#question-iframe-container').css('width', '0');
		$('#question-iframe-container').css('height', '0');
		$('#question-iframe').css('width', '0');
		$('#question-iframe').css('height', '0');
	},
	showQuestionIframe () {
		$('#question-iframe-container').css('width', 'auto');
		$('#question-iframe-container').css('height', 'auto');
		$('#question-iframe').css('width', '782px');
		$('#question-iframe').css('height', 'auto');
	},
	updateSolutionChoices (questionIframe, questionKey) {
		for (var iChoice = 0; iChoice < 10; iChoice++) {
			questionIframe.body.find('#container .' + questionKey + "_choice_" + (iChoice + 1))
				.html(questionIframe.body.find('#container #answerButton_' + questionKey + "_" + (iChoice + 1) + " input").val());
		}
	},
	updateContainerCss () {
		$('#question-iframe-container').css('left', '273px');
	}
};