<?php

require_once __DIR__.'/../../tasks/bebras/Bebras.php';

class ContestOld
{


    public function __construct($config, $filesystem) {
        $this->config = $config;
        $this->filesystem = $filesystem;
    }


    public function contestMkdir($dir)
    {
        $this->filesystem->myMkdir(
            $this->filesystem->joinPaths($this->contestFolder, $dir)
        );
    }

    /* Copy file at absolute path $src to public contest-relative path $dst */
    public function contestCopyFile($src, $dst)
    {
        $this->filesystem->myCopyFile(
            $src,
            $this->filesystem->joinPaths($this->contestFolder, $dst)
        );
    }

    /* Write $content to contest-relative path $dst, public unless $adminOnly is true */
    public function contestPutContents($dst, $content, $adminOnly = false)
    {
        $this->filesystem->myPutContents(
            $this->filesystem->joinPaths($this->contestFolder, $dst),
            $content,
            $adminOnly
        );
    }


    public function prepare() {

    }


    public function generate($tasks, $contestID, $contestFolder, $fullFeedback = false, $status = 'RunningContest')
    {
        $this->contestFolder = $contestFolder;
        //$this->contestFolder = $_REQUEST['contestFolder'];

        $strQuestions = "<!doctype html>\n";
        $strQuestionsArr = array();
        $strSolutions = "<!doctype html>\n";
        $images = array();
        $imagesSols = array();
        $jsModulesRes = array();
        $cssModulesRes = array();
        $strGraders = "<!doctype html>\n";

        $numPart = 0;
        $nameParts = "";
        $buffer = "";
        foreach ($tasks as $curTask) {
            $strQuestion = "";
            $jsQuestions = '';
            $cssQuestions = '';
            $cssSolutions = '';
            $jsModules = array();
            $jsCurrentModules = array();
            $cssCurrentModules = array();
            $cssModules = array();

            $curKey = $curTask['key'];
            $task = new PEMTaskCompiler(
                $curTask['bebras'],
                $curTask['key'],
                __DIR__ . '/../bebras-tasks/' . $curTask['url'],
                true
            );

            // Create the task directory.
            $this->contestMkdir($curKey);

            // Copy bebras.js
            $bebrasJsContent = 'var json = ' . $task->getBebrasJson() . '; function getTaskResources() { return json; }';
            $bebrasJsDstFile = $curKey . '/bebras.js';
            $this->contestPutContents($bebrasJsDstFile, $bebrasJsContent);

            $curImages = $task->copyImages(
                PEMTaskCompiler::TASK,
                $curKey,
                array($this, 'contestCopyFile')
            );
            $images = array_merge($images, Bebras::addAbsoluteStaticPath($curImages, $contestFolder . '/' . $curKey));
            $curImagesSols = $task->copyImages(
                PEMTaskCompiler::SOLUTION,
                $curKey,
                array($this, 'contestCopyFile')
            );
            $imagesSols = array_merge($imagesSols, Bebras::addAbsoluteStaticPath($curImagesSols, $contestFolder . '/' . $curKey));

            // Convert JS and CSS image path
            $questionJs = $task->getJavascript(PEMTaskCompiler::TASK | PEMTaskCompiler::SAT | PEMTaskCompiler::DISPLAY | PEMTaskCompiler::PROXY);
            $solutionJs = $task->getJavascript(PEMTaskCompiler::SOLUTION);
            $questionCss = $task->getCss(PEMTaskCompiler::TASK | PEMTaskCompiler::SAT | PEMTaskCompiler::DISPLAY | PEMTaskCompiler::PROXY);
            $solutionCss = $task->getCss(PEMTaskCompiler::SOLUTION);

            // Javascript & css modules
            $modules = $task->getModules();
            $jsCurrentModules = $modules['jsModules']['ref'];
            $jsModulesRes = array_merge($jsModulesRes, $jsCurrentModules);
            $cssCurrentModules = $modules['cssModules']['ref'];
            $cssModulesRes = array_merge($cssModulesRes, $cssCurrentModules);

            // JS modules content
            foreach ($modules['jsModules']['inline'] as $curJsModuleContent) {
                $jsQuestions .= $curJsModuleContent;
            }
            // Css modules content
            foreach ($modules['cssModules']['inline'] as $curCssModuleContent) {
                $cssQuestions .= $curCssModuleContent;
            }

            // Javascript grader
            $strGraders .= '<div id="javascript-grader-' . $curKey . '" data-content="' . htmlspecialchars($task->getGrader(), ENT_COMPAT, 'UTF-8') . '"></div>' . "\r\n";

            $questionRelatedJs = Bebras::moveQuestionImagesSrc($questionJs, $curKey, $contestFolder);
            $solutionRelatedJs = Bebras::moveQuestionImagesSrc($solutionJs, $curKey, $contestFolder);
            $cssQuestions .= Bebras::moveQuestionImagesSrc($questionCss, $curKey, $contestFolder);
            $cssSolutions .= Bebras::moveQuestionImagesSrc($solutionCss, $curKey, $contestFolder);

            // Content
            $questionBody = $task->getContent(PEMTaskCompiler::TASK);
            $questionSolution = $task->getContent(PEMTaskCompiler::SOLUTION);

            // Remove absolute images
            $questionBody = preg_replace('#http\://.*\.(png|jpg|gif|mp4|jpeg)#isU', '', $questionBody);

            $strQuestion .= '<div id="question-' . $curKey . '" class="question"><div id="task" class="taskView">' . "\r\n"
            . '<style>' . $cssQuestions . '</style>'
            . Bebras::moveQuestionImagesSrc($questionBody, $curKey, $contestFolder)
                . '</div></div>' . "\r\n";

            $strQuestion .= '<div id="javascript-' . $curKey . '" data-content="' . htmlspecialchars($questionRelatedJs, ENT_COMPAT, 'UTF-8') . '"></div>' . "\r\n";

            foreach ($jsCurrentModules as $name => $content) {
                $strQuestion .= '<div class="js-module-' . $curKey . '" data-content="' . $name . '"></div>' . "\n";
            }
            foreach ($cssCurrentModules as $name => $content) {
                $strQuestion .= '<div class="css-module-' . $curKey . '" data-content="' . $name . '"></div>' . "\n";
            }
            $strSolutions .= '<div id="solution-' . $curKey . '" class="solution">' . "\r\n"
            . '<style>' . $cssSolutions . '</style>'
            . Bebras::moveQuestionImagesSrc($questionSolution, $curKey, $contestFolder)
            . '</div>' . "\r\n"
            . '<div id="javascript-solution-' . $curKey . '" data-content="' . htmlspecialchars($solutionRelatedJs, ENT_COMPAT, 'UTF-8') . '"></div>' . "\r\n";
            $strQuestions .= $strQuestion;
            $this->contestAddContent($strQuestion, $nameParts, $buffer, $numPart, false);
        }
        $this->contestCopyFile(__DIR__ . '/../bebras-tasks/modules/img/castor.png', 'castor.png');
        $this->contestCopyFile(__DIR__ . '/../bebras-tasks/modules/img/laptop_success.png', 'laptop_success.png');
        $this->contestCopyFile(__DIR__ . '/../bebras-tasks/modules/img/laptop_warning.png', 'laptop_warning.png');
        $this->contestCopyFile(__DIR__ . '/../bebras-tasks/modules/img/laptop_error.png', 'laptop_error.png');
        $this->contestCopyFile(__DIR__ . '/../bebras-tasks/modules/img/fleche-bulle.png', 'fleche-bulle.png');
        $images[] = $this->filesystem->joinPaths($this->config->teacherInterface->sAbsoluteStaticPath, 'contests/' . $contestFolder . '/castor.png');
        $images[] = $this->filesystem->joinPaths($this->config->teacherInterface->sAbsoluteStaticPath, 'contests/' . $contestFolder . '/fleche-bulle.png');

        $jsPreload = "\r\n//ImagesLoader is injected by the platform just before the contest is loaded\r\n";

        // preloading fonts results in very strange bug with CORS headers...
        $imagesToPreload = $this->removeFonts($images);
        $imagesToPreloadSols = $this->removeFonts($imagesSols);

        $jsPreload .= "ImagesLoader.setImagesToPreload([\n'" .
        implode("' ,\n'", $imagesToPreload) .
            "']);\r\n";

        $jsPreload .= "function preloadSolImages() { var imagesToLoad = [\n'" .
        implode("' ,\n'", $imagesToPreloadSols) .
            "'];\r\n   ImagesLoader.addImagesToPreload(imagesToLoad);\r\n}\n";

        $htAccessContent =
            '<Files "contest_' . $contestID . '_sols.html">' . "\n"
            . "\t" . 'Deny from all' . "\n"
            . '</Files>' . "\n"
            . '<Files "bebras.js">' . "\n"
            . "\t" . 'Deny from all' . "\n"
            . '</Files>' . "\n";

        if (!$fullFeedback) {
            $htAccessContent .= '<Files "contest_' . $contestID . '_graders.html">' . "\n"
                . "\t" . 'Deny from all' . "\n"
                . '</Files>' . "\n";
        }

        // Compile js modules
        $strQuestions .= "\n";

        foreach ($jsModulesRes as $name => $content) {
            // If the content is too long, split it in parts
            if (strlen($content) < 65000) {
                $strContent = htmlspecialchars($content, ENT_COMPAT, 'UTF-8');
                $strModule = '<div class="js-module" id="js-module-' . $name . '" data-content="' . $strContent . '"></div>' . "\n";
                $strQuestions .= $strModule;
                $this->contestAddContent($strModule, $nameParts, $buffer, $numPart, false);
            } else {
                $strContentPart = 0;
                while (strlen($content) > 0) {
                    $contentExcept = substr($content, 0, 65000);
                    $content = substr($content, 65000);
                    $strContent = htmlspecialchars($contentExcept, ENT_COMPAT, 'UTF-8');
                    $strModule = '<div class="js-module" id="js-module-' . $name . '_' . $strContentPart . '" data-part="' . $strContentPart . '" data-content="' . $strContent . '"></div>' . "\n";
                    $strContentPart += 1;
                    $strQuestions .= $strModule;
                    $this->contestAddContent($strModule, $nameParts, $buffer, $numPart, false);
                }
            }
        }

        // Compile css modules
        $strQuestions .= "\n";
        foreach ($cssModulesRes as $name => $content) {
            $strModule .= '<div class="css-module" id="css-module-' . $name . '" data-content="' . htmlspecialchars($content, ENT_COMPAT, 'UTF-8') . '"></div>' . "\n";
            $strQuestions .= $strModule;
            $this->contestAddContent($strModule, $nameParts, $buffer, $numPart, false);
        }
        $this->contestAddContent("", $nameParts, $buffer, $numPart, true);
        $this->contestPutContents('index.txt', trim($nameParts));
        // Preload
        //$strQuestions .= '<div id="preload-images-js" data-content="'.htmlspecialchars($jsPreload, ENT_COMPAT, 'UTF-8').'"></div>'."\n";

        // Create files
        $this->contestPutContents('contest_' . $contestID . '.html', $strQuestions);
        $this->contestPutContents('contest_' . $contestID . '_sols.html', $strSolutions, true);
        $this->contestPutContents('contest_' . $contestID . '.js', $jsPreload);
        $this->contestPutContents('contest_' . $contestID . '_graders.html', $strGraders, !$fullFeedback);
        $this->contestPutContents('.htaccess', $htAccessContent, true);
    }



    /* Add $content fragment to contest part files. */
    private function contestAddContent($content, &$listParts, &$buffer, &$numPart, $isLast)
    {
        $buffer .= $content;
        if ((strlen($buffer) + strlen($content) > 200000) || ($isLast && (strlen($buffer) != 0))) {
            $part = "part_" . $numPart . ".html";
            $this->contestPutContents($part, "<!doctype html>\n" . $buffer);
            $listParts .= $part . " ";
            $buffer = "";
            $numPart++;
        }
    }

    private function removeFonts($images)
    {
        $imagesToPreload = [];
        foreach ($images as $image) {
            $ext = pathinfo($image, PATHINFO_EXTENSION);
            if ($ext != 'eot' && $ext != 'woff' && $ext != 'ttf') {
                $imagesToPreload[] = $image;
            }
        }
        return $imagesToPreload;
    }
}