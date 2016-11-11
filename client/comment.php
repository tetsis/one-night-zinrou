<!DOCTYPE html>

<html lang="ja">

<head>
    <meta charaset="UTF-8">
    <title>ワンナイト人狼：コメント</title>
    <link rel="stylesheet" href="css/ripples.min.css">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/bootstrap-material-design.css">
    <link rel="stylesheet" href="main.css">
    <script type="text/javascript" src="jquery.js"></script>
    <script type="text/javascript" src="js/ripples.min.js"></script>
    <script type="text/javascript" src="js/bootstrap.js"></script>
    <script type="text/javascript" src="js/material.js"></script>
    <script type="text/javascript" src="md5.js"></script>
    <script type="text/javascript" src="zinrou.js"></script>
</head>

<body>
<script>
    $.material.init();
</script>
<div id="comment" class="state">
<?php
    if ($_POST['btn_commentSubmit']) {
        error_log("COMMENT: ". $_POST[txa_comment]. "\n", 3, '/var/log/zinrou.log');
        $slackApiKey = 'SLACK_API_KEY';
        $name = $_POST['txt_name'];
        $text = $_POST['txa_comment'];
        $text = urlencode($name. ": ". $text);
        $url = "https://slack.com/api/chat.postMessage?token=${slackApiKey}&channel=%23one-night-zinrou&text=${text}&as_user=true";
        file_get_contents($url);
        print "<div class='header'>";
        print "コメントが送信されました<br/>";
        print "ご協力ありがとうございました<br/><br/>";
        print "</div>";
    }
?>
<div class="header">
</div>
<form action = 'comment.php' method = 'post'>
    <div class="form-group">
        <div class = "header">あなたの名前（任意）</div>
    <input type="text" maxlength="20" name="txt_name" class="form-control txt">
    </div>
    <div class="form-group">
        <div class = "header">開発者へのコメントを書いてください</div>
        <textarea name="txa_comment" class='form-control txt'></textarea>
    </div>
    <input type='submit' name='btn_commentSubmit' value='送信' class='btn btn-raised btn-default btn_main'/>
</form>

<form action = './' method = 'post'>
    <button type='submit' name='btn_back' class='btn btn-raised btn-default btn_main'>戻る</button>
</form>

</div>
</body>

</html>

