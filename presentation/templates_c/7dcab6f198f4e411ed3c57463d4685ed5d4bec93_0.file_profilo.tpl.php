<?php
/* Smarty version 5.8.2, created on 2026-06-26 12:28:08
  from 'file:ristorante/profilo.tpl' */

/* @var \Smarty\Template $_smarty_tpl */
if ($_smarty_tpl->getCompiled()->isFresh($_smarty_tpl, array (
  'version' => '5.8.2',
  'unifunc' => 'content_6a3e54385dc9a8_70714639',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '7dcab6f198f4e411ed3c57463d4685ed5d4bec93' => 
    array (
      0 => 'ristorante/profilo.tpl',
      1 => 1782469612,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
))) {
function content_6a3e54385dc9a8_70714639 (\Smarty\Template $_smarty_tpl) {
$_smarty_current_dir = 'C:\\xampp\\htdocs\\OrderApp-main\\presentation\\templates\\ristorante';
?><!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Profilo - <?php echo htmlspecialchars((string)$_smarty_tpl->getValue('nomeRistorante'), ENT_QUOTES, 'UTF-8', true);?>
</title>
  <link rel="stylesheet" href="presentation/assets/ristorante.css">
</head>
<body>
<div class="r-wrap" id="profiloWrap">

  <header class="r-top">
    <div class="r-crumbs">
      <a href="index.php?controller=Ristorante&action=mostraHome" class="r-back">&larr; Home</a>
      <span class="r-sep">/</span>
      <span class="r-here">Profilo</span>
    </div>
    <div class="r-title-side"><?php echo htmlspecialchars((string)$_smarty_tpl->getValue('nomeRistorante'), ENT_QUOTES, 'UTF-8', true);?>
</div>
  </header>

  <main class="r-main" style="max-width:560px;">

        <div class="r-sec-label">Nome del ristorante</div>
    <div class="r-card" style="padding:20px; margin-bottom:22px;">
      <div class="r-field" style="margin-bottom:6px;">
        <label>Username di accesso (non modificabile)</label>
        <input type="text" value="<?php echo htmlspecialchars((string)$_smarty_tpl->getValue('username'), ENT_QUOTES, 'UTF-8', true);?>
" disabled class="r-input-disabled">
      </div>
      <div class="r-field">
        <label>Nome del ristorante</label>
        <input type="text" id="profNome" value="<?php echo htmlspecialchars((string)$_smarty_tpl->getValue('nomeRistorante'), ENT_QUOTES, 'UTF-8', true);?>
">
      </div>
      <div style="margin-top:14px;">
        <button class="r-btn r-btn-primary" id="btnSalvaNome">Salva nome</button>
      </div>
      <div class="r-msg" id="nomeMsg"></div>
    </div>

        <div class="r-sec-label">Cambia password</div>
    <div class="r-card" style="padding:20px;">
      <div class="r-field" style="margin-bottom:10px;">
        <label>Password attuale</label>
        <input type="password" id="profAttuale" placeholder="La password che usi ora">
      </div>
      <div class="r-field" style="margin-bottom:10px;">
        <label>Nuova password</label>
        <input type="password" id="profNuova" placeholder="La nuova password">
      </div>
      <div class="r-field">
        <label>Ripeti nuova password</label>
        <input type="password" id="profRipeti" placeholder="Scrivi di nuovo la nuova password">
      </div>
      <div style="margin-top:14px;">
        <button class="r-btn r-btn-primary" id="btnCambiaPwd">Cambia password</button>
      </div>
      <div class="r-msg" id="pwdMsg"></div>
    </div>

  </main>

</div>
<?php echo '<script'; ?>
 src="presentation/assets/ristorante.js"><?php echo '</script'; ?>
>
</body>
</html>
<?php }
}
