{*
  Template Smarty — Login staff (login/staff.tpl)
  Form unico per ristorante/cucina/amministratore: username + password.
  Variabile attesa: $errore (stringa o vuota).
*}
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

      {if $errore}
        <div class="login-error">{$errore|escape}</div>
      {/if}

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
