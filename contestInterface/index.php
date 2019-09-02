<?php include('templates/bootstrap.php'); ?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset='utf-8'>
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <link rel="shortcut icon" href="<?= $config->faviconfile ?>" />
        <title data-i18n="general_page_title"></title>
        <?php stylesheet_tag('/style.css'); ?>
    </head>

    <body>
        <?php include('templates/header.php'); ?>
        <form id="mainContent" autocomplete="off">
            <?php include('templates/browserVerified.php'); ?>
            <?php include('templates/homePage.php'); ?>
            <?php include('templates/checkGroup.php'); ?>
            <?php include('templates/accessContest/index.php'); ?>
            <?php include('templates/startContest.php'); ?>
            <?php include('templates/allContestsDone.php'); ?>
            <?php include('templates/personalPage.php'); ?>
            <?php include('templates/contests.php'); ?>
            <?php include('templates/password.php'); ?>
        </form>
        <?php include('templates/loadingPage.php'); ?>
        <?php include('templates/questions.php'); ?>
        <?php include('templates/personalDataEditor.php'); ?>
        <?php include('templates/guest.php'); ?>
        <?php include('templates/questionIframe.php'); ?>
        <?php include('templates/footer.php'); ?>
        <?php include('templates/closed.php'); ?>
        <?php include('templates/groupConfirmation.php'); ?>
        <?php include('templates/error.php'); ?>
        <?php include('templates/scripts.php'); ?>
    </body>
</html>