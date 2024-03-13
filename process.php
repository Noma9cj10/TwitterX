<!DOCTYPE html>
<html lang="ja">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>登録成否判定</title>
    <link rel="icon" type="image/png" href="soreppoitori32.png">
</head>

<body>
    <?php
    // process.php
    // データベースへの接続
    $dsn = 'mysql:host=localhost;dbname=TwitterX;charset=utf8';
    $username = 'root';
    $password = '';
    try {
        $db = new PDO($dsn, $username, $password);
    } catch (PDOException $e) {
        die('データベースに接続できませんでした: ' . $e->getMessage());
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // ユーザー選択フォームが送信された場合
        if (isset($_POST['user_select'])) {
            $selectedUserId = $_POST['user_select'];
            // データベースからユーザー情報を取得
            $stmt = $db->prepare('SELECT id, name, image_path FROM users WHERE id = ?');
            $stmt->execute([$selectedUserId]);
            $selectedUser = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($selectedUser) {
                echo '<div>';
                echo '<p>選択されたユーザー: ' . $selectedUser['name'] . '</p>';
                echo '<img src="' . $selectedUser['image_path'] . '" alt="' . $selectedUser['name'] . '" style="width: 100px; height: 100px;">';
                echo '</div>';
            } else {
                echo 'ユーザーが存在しません';
            }
        }
        // 新規ユーザー登録フォームが送信された場合
        if (isset($_POST['new_user_name'])) {
            $newUserName = $_POST['new_user_name'];
            // 画像のアップロード処理
            $uploadDir = 'uploads/'; // アップロード先のディレクトリ
            $uploadedFile = $uploadDir . basename($_FILES['new_user_image']['name']);
            // 必須項目が未記入の場合
            if (empty($newUserName) || empty($_FILES['new_user_image']['name'])) {
    ?>
                <div>
                    <p style="color: red;">未記入の項目があります。</p>
                    <p>3秒後にリダイレクトします。</p>
                </div>
                <script>
                    setTimeout(function() {
                        window.location.href = "userselect.php";
                    }, 3000);
                </script>
    <?php
                exit; // プログラムの実行を終了
            }
            move_uploaded_file($_FILES['new_user_image']['tmp_name'], $uploadedFile);
            // データベースに新しいユーザーを登録
            $stmt = $db->prepare('INSERT INTO users (name, image_path) VALUES (?, ?)');
            $stmt->execute([$newUserName, $uploadedFile]);
            // 登録されたユーザーの情報を表示
            echo '<div>';
            echo '<p>新しいユーザーが登録されました: ' . $newUserName . '</p>';
            echo '<p>3秒後にリダイレクトします。</p>';
            echo '<img src="' . $uploadedFile . '" alt="' . $newUserName . '" style="width: 100px; height: 100px;">';
            echo '</div>';
        }
    }
    echo '<script>';
    echo 'setTimeout(function() { window.location.href = "userselect.php"; }, 3000);'; // 3秒後にuserselect.phpにリダイレクト
    echo '</script>';
    ?>
</body>

</html>