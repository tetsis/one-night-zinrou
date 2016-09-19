<!DOCTYPE html>

<html lang="ja">

<head>
    <meta charaset="UTF-8">
    <title>ワンナイト人狼：コメント</title>
    <link rel="stylesheet" href="normalize.css">
    <link rel="stylesheet" href="main.css">
</head>

<body>
<div id="comment" class="state">
<?php
    if ($_POST[btn_commentSubmit]) {
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
    <div class = "header">あなたの名前（必須ではありませんが、開発者があなたのコメントを反映したリリース情報をお伝えするのに役立ちます。）</div>
    <input type="text" maxlength="20" name="txt_name" class="txt">
    <div class = "header">開発者へのコメントを書いてください</div>
    <textarea name="txa_comment" class='txt'></textarea>
    <input type = 'submit' name = 'btn_commentSubmit' value = '送信' class='btn_main'/>
</form>

<form action = './' method = 'post'>
    <input type = 'submit' name = 'btn_back' value = '戻る' class='btn_main'/>
</form>

</div>
</body>

</html>

