<?php
/* Smarty version 5.8.2, created on 2026-06-25 15:09:03
  from 'file:login/tavolo.tpl' */

/* @var \Smarty\Template $_smarty_tpl */
if ($_smarty_tpl->getCompiled()->isFresh($_smarty_tpl, array (
  'version' => '5.8.2',
  'unifunc' => 'content_6a3d286fb15af5_34017651',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '8a9fa9976ba829a5a4ccd1763fafe8ba39b4afa3' => 
    array (
      0 => 'login/tavolo.tpl',
      1 => 1782391546,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
))) {
function content_6a3d286fb15af5_34017651 (\Smarty\Template $_smarty_tpl) {
$_smarty_current_dir = 'C:\\xampp\\htdocs\\OrderApp-main\\presentation\\templates\\login';
?><!DOCTYPE html>
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

      <?php if ($_smarty_tpl->getValue('errore')) {?>
        <div class="login-error"><?php echo htmlspecialchars((string)$_smarty_tpl->getValue('errore'), ENT_QUOTES, 'UTF-8', true);?>
</div>
      <?php }?>

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
<?php }
}
