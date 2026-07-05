<?php
/* Smarty version 5.8.2, created on 2026-06-25 16:03:18
  from 'file:ristorante/cucine.tpl' */

/* @var \Smarty\Template $_smarty_tpl */
if ($_smarty_tpl->getCompiled()->isFresh($_smarty_tpl, array (
  'version' => '5.8.2',
  'unifunc' => 'content_6a3d3526ab14d9_37926620',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '7d2e083fd6915b0f881eb9759b90ca73240a2850' => 
    array (
      0 => 'ristorante/cucine.tpl',
      1 => 1782396090,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
))) {
function content_6a3d3526ab14d9_37926620 (\Smarty\Template $_smarty_tpl) {
$_smarty_current_dir = 'C:\\xampp\\htdocs\\OrderApp-main\\presentation\\templates\\ristorante';
?><!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Gestione cucine - <?php echo htmlspecialchars((string)$_smarty_tpl->getValue('nomeRistorante'), ENT_QUOTES, 'UTF-8', true);?>
</title>
  <link rel="stylesheet" href="presentation/assets/ristorante.css">
</head>
<body>
<div class="r-wrap" data-cucine='<?php echo $_smarty_tpl->getValue('cucineIniziali');?>
'>

  <header class="r-top">
    <div class="r-crumbs">
      <a href="index.php?controller=Ristorante&action=mostraHome" class="r-back">&larr; Home</a>
      <span class="r-sep">/</span>
      <span class="r-here">Gestione cucine</span>
    </div>
    <div class="r-title-side"><?php echo htmlspecialchars((string)$_smarty_tpl->getValue('nomeRistorante'), ENT_QUOTES, 'UTF-8', true);?>
</div>
  </header>

  <main class="r-main">
        <div class="r-card">
      <table class="r-table">
        <thead>
          <tr>
            <th>Nome cucina</th><th>Username</th><th class="r-right">Azioni</th>
          </tr>
        </thead>
        <tbody id="cucineBody"></tbody>
      </table>
      <div class="r-empty" id="cucineEmpty" style="display:none;">Nessuna cucina. Aggiungine una qui sotto.</div>
    </div>

        <div class="r-add">
      <div class="r-add-title">+ Aggiungi una nuova cucina</div>
      <div class="r-add-row">
        <div class="r-field">
          <label>Nome cucina</label>
          <input type="text" id="nuovoNome" placeholder="Es. Cucina calda">
        </div>
        <div class="r-field">
          <label>Username</label>
          <input type="text" id="nuovoUsername" placeholder="Es. cucina_calda">
        </div>
        <div class="r-field">
          <label>Password</label>
          <input type="text" id="nuovaPasswordCucina" placeholder="Password di accesso">
        </div>
        <button class="r-btn r-btn-primary" id="btnCreaCucina">Crea cucina</button>
      </div>
      <div class="r-msg" id="creaCucinaMsg"></div>
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
