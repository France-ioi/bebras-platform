<?php
    require_once("../shared/common.php");
    require_once("commonAdmin.php");
    $script = basename(__FILE__);

    // fetch contests
    $q = "
        SELECT
            ID,
            name,
            folder,
            thumbnail
        FROM
            contest
        ORDER BY
            name";
    $stmt = $db->prepare($q);
    $stmt->execute();
    $contests = $stmt->fetchAll(PDO::FETCH_ASSOC);


    // update thumbnails
    if(isset($_POST['action']) && $_POST['action'] == 'upload') {
        //echo '<pre>';var_dump($_FILES);die();

        $q = "
            UPDATE
                contest
            SET
                thumbnail = :thumbnail
            WHERE
                ID = :ID
            LIMIT 1";
        $stmt = $db->prepare($q);

        foreach($contests as $contest) {
            $id = $contest['ID'];
            if(isset($_FILES['new']['name'][$id]) && $contest['folder']) {
                $pinfo = pathinfo($_FILES['new']['name'][$id]);
                $thumbnail = uniqid('thumbnail_').'.'.$pinfo['extension'];
                $dst = __DIR__.$config->teacherInterface->sContestGenerationPath.$contest['folder'].'/'.$thumbnail;
                if(move_uploaded_file($_FILES['new']['tmp_name'][$id], $dst)) {
                    if($contest['thumbnail']) {
                        @unlink(__DIR__.$config->teacherInterface->sContestGenerationPath.$contest['folder'].'/'.$contest['thumbnail']);
                    }
                    $stmt->execute(array(
                        'ID' => $id,
                        'thumbnail' => $thumbnail
                    ));
                }
            }
        }
        header('HTTP/1.1 200 OK');
        header('Location: '.$script);
        dir();
    }
?>
<html>
<meta charset='utf-8'>
<body>
    <style>
        .thumbnail {
            width: 320px;
            height: 180px;
            background-repeat: no-repeat;
            background-position: center;
            background-size: cover;
        }
        th, td {
            border-bottom: 1px solid #999;
            padding: 5px 20px;
        }
        td.error {
            text-align: center;
            color: red;
        }
    </style>
    <form method="POST" enctype="multipart/form-data" action="<?=$script?>">
        <input type="hidden" name="action" value="upload"/>
        <table>
            <tr>
                <th>Contest</th>
                <th>Old thumbnail</th>
                <th>New thumbnail</th>
            </tr>
            <?php
                foreach($contests as $contest) {
                    if($contest['thumbnail']) {
                        $thumb_file = 'contests/'.$contest['folder'].'/'.$contest['thumbnail'];
                        $thumb_src = $config->contestInterface->baseUrl.$thumb_file;
                    } else {
                        $thumb_file = '';
                        $thumb_src = $config->contestInterface->baseUrl.'images/img-placeholder.png';
                    }
            ?>
                <tr>
                    <td><?=$contest['name']?></td>
                    <?php if($contest['folder']) { ?>
                        <td><div class="thumbnail" style="background-image: URL(<?=$thumb_src?>)"/></div>
                        <td>
                            <input type="hidden" name="old[<?=$contest['ID']?>]" values="<?=$thumb_file?>"/>
                            <input type="file" name="new[<?=$contest['ID']?>]" accept=".jpg,.jpeg,.gif,.png"/>
                        </td>
                    <?php } else { ?>
                        <td colspan="2" class="error">Contest regeneration required!</td>
                    <?php } ?>
                </tr>
            <?php } ?>
        </table>
        <input type="submit"/>
    </form>
</body>
</html>