{*
  Template Smarty — Login tavolo (login/tavolo.tpl)
  Form di accesso del tavolo: ristorante + numero + password.
  Variabile attesa: $errore (stringa o vuota).
*}
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Accesso tavolo</title>
  <link rel="stylesheet" href="presentation/assets/tavolo.css">
</head>
<body>
<div class="app">
  <div class="login-wrap">
    <div class="login-card">
      <div class="login-logo"><span class="dot"></span></div>
      <h1 class="login-title">Accesso tavolo</h1>
      <p class="login-sub">Inserisci i dati che trovi sul tavolo.</p>

      {if $errore}
        <div class="login-error">{$errore|escape}</div>
      {/if}

      {* POST: i dati viaggiano nel corpo, non nell'URL (password protetta). *}
      <form method="post" action="index.php" class="login-form">
        <input type="hidden" name="controller" value="Login">
        <input type="hidden" name="action" value="loginTavolo">

        <label class="login-label">
          Ristorante
          <input type="number" name="ristoranteId" class="login-input" placeholder="Es. 1" min="1" required>
        </label>

        <label class="login-label">
          Numero tavolo
          <input type="text" name="numero" class="login-input" placeholder="Es. 1" required>
        </label>

        <label class="login-label">
          Password
          <input type="password" name="password" class="login-input" placeholder="Password del tavolo" required>
        </label>

        <button type="submit" class="btn primary login-btn">Entra</button>
      </form>

      <a class="login-altlink" href="index.php?controller=Login&action=mostraLogin">Sei un membro dello staff?</a>
    </div>
  </div>
</div>
</body>
</html>
