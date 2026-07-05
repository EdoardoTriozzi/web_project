<?php
/**
 * VIEW LOGIN STAFF (presentation/login/vistaLoginStaff.php)
 *
 * Form di accesso per lo staff (ristorante, cucina, amministratore). Un solo
 * form con username e password: il control li cerca tra i tre ruoli e capisce
 * da solo chi sei (la cascata di mapper in CLogin::login).
 *
 * Invia in POST a login i due dati: username, password (nomi che combaciano
 * con CLogin::login).
 *
 * Variabile opzionale dal control:
 *   - ?string $errore
 */
$errore = $errore ?? null;

if (!function_exists('h')) {
    function h(?string $s): string { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Accesso staff</title>
  <link rel="stylesheet" href="presentation/assets/tavolo.css">
</head>
<body>
<div class="app">
  <div class="login-wrap">
    <div class="login-card">
      <div class="login-logo"><span class="dot"></span></div>
      <h1 class="login-title">Accesso staff</h1>
      <p class="login-sub">Ristorante, cucina o amministratore.</p>

      <?php if ($errore !== null): ?>
        <div class="login-error"><?= h($errore) ?></div>
      <?php endif; ?>

      <form method="post" action="index.php" class="login-form">
        <input type="hidden" name="controller" value="Login">
        <input type="hidden" name="action" value="login">

        <label class="login-label">
          Username
          <input type="text" name="username" class="login-input" placeholder="Il tuo username" required>
        </label>

        <label class="login-label">
          Password
          <input type="password" name="password" class="login-input" placeholder="La tua password" required>
        </label>

        <button type="submit" class="btn primary login-btn">Entra</button>
      </form>

      <a class="login-altlink" href="index.php?controller=Login&action=mostraLoginTavolo">Sei a un tavolo?</a>
    </div>
  </div>
</div>
</body>
</html>
