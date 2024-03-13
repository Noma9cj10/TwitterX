<!DOCTYPE html>
<?php
// データベースへの接続
$dsn = 'mysql:host=localhost;dbname=TwitterX;charset=utf8';
$username = 'root';
$password = '';
try {
    $db = new PDO($dsn, $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // エラーが発生した場合に例外をスローするように設定
} catch (PDOException $e) {
    die('データベースに接続できませんでした: ' . $e->getMessage());
}
?>
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>ツイッターっぽい感じのやつ</title>
    <link rel="stylesheet" type="text/css" href="reset.css">
    <link rel="stylesheet" type="text/css" href="style.css"> <!-- CSSファイルを読み込む -->
    <link rel="icon" type="image/png" href="soreppoitori32.png">
</head>

<body>
    <?php
    // ユーザーの選択フォームが送信されたときの処理
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_select'])) {
        $selectedUserId = $_POST['user_select'];
        // データベースから選択されたユーザーの情報を取得
        $stmt = $db->prepare('SELECT id, name, image_path FROM users WHERE id = :userId');
        $stmt->bindParam(':userId', $selectedUserId, PDO::PARAM_INT);
        $stmt->execute();
        $selectedUser = $stmt->fetch(PDO::FETCH_ASSOC);
        // 選択されたユーザーの情報を表示
        if ($selectedUser) {
    ?>
            <div class="tweet-ish">
                <h2>ツイートする</h2>
                <div class="tweet-box">
                    <div class="user-info">
                        <img src="<?php echo $selectedUser['image_path']; ?>" alt="プロフィール画像">
                        <p><strong><?php echo htmlspecialchars($selectedUser['name']); ?></strong></p>
                    </div>
                    <form class="tweet-form" method="post" action="tweets.php" enctype="multipart/form-data">
                        <label for="tweet_content"></label>
                        <textarea name="tweet_content" id="tweet_content" placeholder="いまどうしています？ 暇ですよね？ 何か呟いてもらっていいですか？"></textarea><br>
                        <label for="tweet_image"></label>
                        <input type="file" name="tweet_image" id="tweet_image"><br>
                        <input type="hidden" name="user_id" value="<?php echo $selectedUser['id']; ?>">
                        <input type="submit" value="ツイート">
                        <input type="button" value="ユーザー選択に戻る" onclick="location.href='userselect.php';">
                    </form>
                </div>
            </div>
        <?php
        } else {
            echo 'ユーザーが見つかりませんでした。';
        }
    } else {
        // ユーザー情報がない場合、ユーザー選択に戻るボタンを表示
        if (!isset($_POST['tweet_content']) && !isset($_POST['tweet_id'])) {
        ?>
            <div class="tweet-ish">
                <div class="tweet-box">
                    <form class="tweet-form" method="post" action="userselect.php">
                        <input type="submit" value="ユーザー選択に戻る">
                    </form>
                </div>
            </div>
        <?php
        }
    }
    // ツイート内容が送信されたときの処理
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tweet_content']) && isset($_POST['user_id'])) {
        $tweetContent = $_POST['tweet_content'];
        $userId = $_POST['user_id'];
        // 画像のアップロード処理
        $uploadDir = 'tweet_images/';
        $uploadedFile = '';
        if (!empty($_FILES['tweet_image']['name'])) {
            $uploadedFile = $uploadDir . basename($_FILES['tweet_image']['name']);
            move_uploaded_file($_FILES['tweet_image']['tmp_name'], $uploadedFile);
        }
        // ツイートが空の場合は中止し、アラートを表示
        if (empty($tweetContent) && empty($uploadedFile)) {
            echo '<script>alert("未入力です");</script>';
            // 再度ユーザー情報を取得して表示
            $stmt = $db->prepare('SELECT id, name, image_path FROM users WHERE id = :userId');
            $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
            $stmt->execute();
            $selectedUser = $stmt->fetch(PDO::FETCH_ASSOC);
        ?>
            <!-- ツイートフォームを表示 -->
            <div class="tweet-ish">
                <div class="tweet-box">
                    <div class="user-info">
                        <img src="<?php echo $selectedUser['image_path']; ?>" alt="プロフィール画像">
                        <p><strong><?php echo htmlspecialchars($selectedUser['name']); ?></strong></p>
                    </div>
                    <form class="tweet-form" method="post" action="tweets.php" enctype="multipart/form-data">
                        <label for="tweet_content"></label>
                        <textarea name="tweet_content" id="tweet_content" placeholder="いまどうしています？ 暇ですよね？ 何か呟いてもらっていいですか？"></textarea><br>
                        <label for="tweet_image"></label>
                        <input type="file" name="tweet_image" id="tweet_image"><br>
                        <input type="hidden" name="user_id" value="<?php echo $selectedUser['id']; ?>">
                        <input type="submit" value="ツイート">
                        <input type="button" value="ユーザー選択に戻る" onclick="location.href='userselect.php';">
                    </form>
                </div>
            </div>
            <?php
        } else {
            try {
                // ツイートをデータベースに格納
                $stmt = $db->prepare('INSERT INTO tweets (user_id, text, picture_path, created_at) VALUES (:userId, :text, :picturePath, NOW())');
                $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
                $stmt->bindParam(':text', $tweetContent, PDO::PARAM_STR);
                // 画像が選択された場合はアップロード処理を行い、パスを保存。選択されなかった場合はNULLを設定。
                if (!empty($uploadedFile)) {
                    $stmt->bindParam(':picturePath', $uploadedFile, PDO::PARAM_STR);
                } else {
                    $stmt->bindValue(':picturePath', NULL, PDO::PARAM_NULL);
                }
                $stmt->execute();
                // ツイートが成功したら再度ユーザー情報を取得して表示
                $selectedUser = $db->query('SELECT id, name, image_path FROM users WHERE id = ' . (int)$userId)->fetch(PDO::FETCH_ASSOC);
                // 選択されたユーザーの情報を表示
                if ($selectedUser) {
            ?>
                    <div class="tweet-ish">
                        <div class="tweet-box">
                            <div class="user-info">
                                <img src="<?php echo $selectedUser['image_path']; ?>" alt="プロフィール画像">
                                <p><strong><?php echo htmlspecialchars($selectedUser['name']); ?></strong></p>
                            </div>
                            <form class="tweet-form" method="post" action="tweets.php" enctype="multipart/form-data">
                                <label for="tweet_content"></label>
                                <textarea name="tweet_content" id="tweet_content" placeholder="いまどうしています？ 暇ですよね？ 何か呟いてもらっていいですか？"></textarea><br>
                                <label for="tweet_image"></label>
                                <input type="file" name="tweet_image" id="tweet_image"><br>
                                <input type="hidden" name="user_id" value="<?php echo $selectedUser['id']; ?>">
                                <input type="submit" value="ツイート">
                                <input type="button" value="ユーザー選択に戻る" onclick="location.href='userselect.php';">
                            </form>
                        </div>
                    </div>
            <?php
                } else {
                    echo 'ユーザーが見つかりませんでした。';
                }
            } catch (PDOException $e) {
                die('ツイートの挿入中にエラーが発生しました: ' . $e->getTraceAsString());
            }
        }
    }
    // ツイート削除がリクエストされたときの処理
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tweet_id'])) {
        $tweetId = $_POST['tweet_id'];
        // ツイートを削除前に該当のツイートのuser_idを取得
        $stmtUserId = $db->prepare('SELECT user_id FROM tweets WHERE id = :tweetId');
        $stmtUserId->bindParam(':tweetId', $tweetId, PDO::PARAM_INT);
        $stmtUserId->execute();
        $userIdResult = $stmtUserId->fetch(PDO::FETCH_ASSOC);
        if ($userIdResult) {
            $userId = $userIdResult['user_id'];
            // ツイートを削除
            try {
                $stmt = $db->prepare('DELETE FROM tweets WHERE id = :tweetId');
                $stmt->bindParam(':tweetId', $tweetId, PDO::PARAM_INT);
                $stmt->execute();
            } catch (PDOException $e) {
                die('ツイートの削除中にエラーが発生しました: ' . $e->getMessage());
            }
            // 再度ユーザー情報を取得して表示
            $stmt = $db->prepare('SELECT id, name, image_path FROM users WHERE id = :userId');
            $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
            $stmt->execute();
            $selectedUser = $stmt->fetch(PDO::FETCH_ASSOC);
            ?>
            <!-- ツイートフォームを表示 -->
            <div class="tweet-ish">
                <div class="tweet-box">
                    <div class="user-info">
                        <img src="<?php echo $selectedUser['image_path']; ?>" alt="プロフィール画像">
                        <p><strong><?php echo htmlspecialchars($selectedUser['name']); ?></strong></p>
                    </div>
                    <form class="tweet-form" method="post" action="tweets.php" enctype="multipart/form-data">
                        <label for="tweet_content"></label>
                        <textarea name="tweet_content" id="tweet_content"></textarea><br>
                        <label for="tweet_image"></label>
                        <input type="file" name="tweet_image" id="tweet_image"><br>
                        <input type="hidden" name="user_id" value="<?php echo $selectedUser['id']; ?>">
                        <input type="submit" value="ツイート">
                        <input type="button" value="ユーザー選択に戻る" onclick="location.href='userselect.php';">
                    </form>
                </div>
            </div>
        <?php
            // ツイート削除後に再度最新ツイートを取得して表示
            $stmt = $db->prepare('SELECT tweets.id as tweet_id, users.id as user_id, users.name, users.image_path AS profile_image, tweets.text, tweets.picture_path, tweets.created_at FROM tweets INNER JOIN users ON tweets.user_id = users.id ORDER BY tweets.created_at DESC LIMIT 15');
            $stmt->execute();
            $allTweets = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
    // 全ユーザーの最新ツイートを取得
    $stmt = $db->prepare('SELECT tweets.id as tweet_id, users.id as user_id, users.name, users.image_path AS profile_image, tweets.text, tweets.picture_path, tweets.created_at FROM tweets INNER JOIN users ON tweets.user_id = users.id ORDER BY tweets.created_at DESC LIMIT 15');
    $stmt->execute();
    $allTweets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    // 最新ツイートを表示
    if (!empty($allTweets)) {
        ?>
        <div class=TimeLine>
            <h2>最新のツイート</h2>
        <?php
        foreach ($allTweets as $tweet) {
            echo '<div class="tweet-container">';
            // プロフィールと名前
            echo '<div class="user-info">';
            echo '<img src="' . $tweet['profile_image'] . '" alt="プロフィール画像">';
            echo '<p><strong>' . htmlspecialchars($tweet['name']) . '</strong></p>';
            echo '</div>';
            // ツイートと画像
            echo '<div class="tweet-content">';
            $tweetText = nl2br(htmlspecialchars($tweet['text']));
            // 50行ごとに追加の改行を挿入
            $tweetText = preg_replace('/(<br\s*\/?>\s*){50}/', '$0<br>', $tweetText);
            echo '<p>' . $tweetText . '</p>';
            // 画像があれば表示
            if (!empty($tweet['picture_path'])) {
                echo '<img src="' . $tweet['picture_path'] . '" alt="ツイート画像" class="tweet-image">';
            }
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($selectedUser)) {
                // ログインユーザーが自分のツイートの場合に削除ボタンを表示
                if ($tweet['user_id'] == $selectedUser['id']) {
                    echo '<form method="post" class="delete-form">';
                    echo '<input type="hidden" name="tweet_id" value="' . $tweet['tweet_id'] . '">';
                    echo '<button type="button" onclick="confirmDelete(' . $tweet['tweet_id'] . ')">削除</button>';
                    echo '</form>';
                }
            }
            echo '<small class="created-at">' . $tweet['created_at'] . '</small>';
            echo '</div>';
            echo '</div>'; // .tweet-container
        }
        echo '</div>';
    } else {
        echo 'ツイートはまだありません。';
    }
        ?>
        <script>
            function confirmDelete(tweetId) {
                if (confirm('本当に削除しますか？')) {
                    // "はい" が選択された場合、削除処理を行う
                    document.querySelector('input[name="tweet_id"][value="' + tweetId + '"]').form.submit();
                    alert('削除しました');
                }
            }
        </script>
</body>

</html>