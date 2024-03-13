<!DOCTYPE html>
<html lang="ja">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>ユーザー選択</title>
    <link rel="icon" type="image/png" href="soreppoitori32.png">
</head>

<body>
    <?php
    // データベースへの接続
    $dsn = 'mysql:host=localhost;dbname=TwitterX;charset=utf8';
    $username = 'root';
    $password = '';

    try {
        $db = new PDO($dsn, $username, $password);
    } catch (PDOException $e) {
        die('データベースに接続できませんでした: ' . $e->getMessage());
    }
    ?>
    <!-- ユーザーの選択フォーム -->
    <form method="post" action="tweets.php">
        <label for="user_select">ユーザー選択:</label>
        <select name="user_select" id="user_select" onchange="updateSize()">
            <!-- 最初に表示するオプション -->
            <option value="" disabled selected>ユーザーを選択してください</option>
            <?php
            // データベースからユーザー一覧を取得
            $stmt = $db->prepare('SELECT id, name FROM users');
            $stmt->execute();
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($users as $user) {
                echo '<option value="' . $user['id'] . '">' . $user['name'] . '</option>';
            }
            ?>
        </select>
        <input type="submit" value="選択">
    </form>

    <!-- 新規ユーザー登録フォーム -->
    <form method="post" action="process.php" enctype="multipart/form-data">
        <label for="new_user_name">新規ユーザー名 (必須) : </label>
        <input type="text" name="new_user_name" id="new_user_name" placeholder="※10文字制限です" maxlength="10"><br>
        <label for="new_user_image">プロフィール画像選択 (必須) : </label>
        <input type="file" name="new_user_image" id="new_user_image"><br>
        <input type="submit" value="登録">
    </form>

    <!-- JavaScriptで選択されたら選択肢を閉じる -->
    <script>
        function updateSize() {
            document.getElementById("user_select").size = 1;
        }
    </script>
</body>

</html>