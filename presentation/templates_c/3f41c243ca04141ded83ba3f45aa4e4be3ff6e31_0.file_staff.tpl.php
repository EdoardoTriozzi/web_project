<?php
/* Smarty version 5.8.2, created on 2026-06-25 14:52:57
  from 'file:login/staff.tpl' */

/* @var \Smarty\Template $_smarty_tpl */
if ($_smarty_tpl->getCompiled()->isFresh($_smarty_tpl, array (
  'version' => '5.8.2',
  'unifunc' => 'content_6a3d24a9d3e220_46680528',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '3f41c243ca04141ded83ba3f45aa4e4be3ff6e31' => 
    array (
      0 => 'login/staff.tpl',
      1 => 1782391543,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
))) {
function content_6a3d24a9d3e220_46680528 (\Smarty\Template $_smarty_tpl) {
$_smarty_current_dir = 'C:\\xampp\\htdocs\\OrderApp-main\\presentation\\templates\\login';
?><!DOCTYPE html>
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

      <?php if ($_smarty_tpl->getValue('errore')) {?>
        <div class="login-error"><?php echo htmlspecialchars((string)$_smarty_tpl->getValue('errore'), ENT_QUOTES, 'UTF-8', true);?>
</div>
      <?php }?>

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
<?php }
}
