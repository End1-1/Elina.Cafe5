<?php
declare(strict_types=1);
session_start();

/**
 * ВАЖНО:
 * - Храни код НЕ в файле, а в env (Apache/Nginx/FPM) или хотя бы вынеси в отдельный конфиг вне webroot.
 * - Ниже для примера берём код из переменной окружения POWEROFF_CODE.
 */
$EXPECTED_CODE = getenv('POWEROFF_CODE') ?: 'CHANGE_ME_NOW';

// Мини-защита от брутфорса
$_SESSION['fails'] = $_SESSION['fails'] ?? 0;
$_SESSION['locked_until'] = $_SESSION['locked_until'] ?? 0;

$now = time();
if ($now < (int)$_SESSION['locked_until']) {
    http_response_code(429);
    echo "Слишком много попыток. Попробуй позже.\n";
    exit;
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = (string)($_POST['code'] ?? '');

    // сравнение без утечек по времени
    if (hash_equals($EXPECTED_CODE, $code)) {
        $message = "Код верный. Выключаю машину...";

        // В лог (по желанию)
        error_log("POWEROFF requested from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));

        // Запуск выключения (не используем ввод пользователя в команде)
        // Можно: poweroff / shutdown -h now / systemctl poweroff
        @exec('sudo /usr/sbin/poweroff > /dev/null 2>&1 &');

        echo $message;
        exit;
    } else {
        $_SESSION['fails']++;
        if ($_SESSION['fails'] >= 5) {
            $_SESSION['locked_until'] = $now + 60; // 60 сек блок
            $_SESSION['fails'] = 0;
        }
        $message = "Неверный код.";
    }
}
?>
<!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Power Off</title>
  <style>
    body{font-family:system-ui,Segoe UI,Arial;max-width:520px;margin:40px auto;padding:0 16px}
    .box{border:1px solid #ddd;border-radius:12px;padding:18px}
    input{width:100%;padding:12px;font-size:18px;border:1px solid #ccc;border-radius:10px}
    button{margin-top:12px;width:100%;padding:12px;font-size:18px;border:0;border-radius:10px;cursor:pointer}
    .msg{margin-top:12px}
  </style>
</head>
<body>
  <div class="box">
    <h2>Սպիտակեցում</h2>
    <form method="post" autocomplete="off">
      <label>Код:</label>
      <input type="password" name="code" autofocus required>
      <button type="submit">Սպիտակեցնել</button>
    </form>
    <?php if ($message): ?>
      <div class="msg"><?= htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></div>
    <?php endif; ?>
  </div>
</body>
</html>
