<?php
/* Smarty version 5.8.2, created on 2026-06-25 15:31:11
  from 'file:ristorante/tavoli.tpl' */

/* @var \Smarty\Template $_smarty_tpl */
if ($_smarty_tpl->getCompiled()->isFresh($_smarty_tpl, array (
  'version' => '5.8.2',
  'unifunc' => 'content_6a3d2d9f01a2f7_23485639',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    'fd63a474dd9da7fb23e72843254e117a8803a30e' => 
    array (
      0 => 'ristorante/tavoli.tpl',
      1 => 1782394102,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
))) {
function content_6a3d2d9f01a2f7_23485639 (\Smarty\Template $_smarty_tpl) {
$_smarty_current_dir = 'C:\\xampp\\htdocs\\OrderApp-main\\presentation\\templates\\ristorante';
?><!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Gestione tavoli - <?php echo htmlspecialchars((string)$_smarty_tpl->getValue('nomeRistorante'), ENT_QUOTES, 'UTF-8', true);?>
</title>
  <link rel="stylesheet" href="presentation/assets/ristorante.css">
</head>
<body>
<div class="r-wrap" data-tavoli='<?php echo $_smarty_tpl->getValue('tavoliIniziali');?>
'>

  <header class="r-top">
    <div class="r-crumbs">
      <a href="index.php?controller=Ristorante&action=mostraHome" class="r-back">&larr; Home</a>
      <span class="r-sep">/</span>
      <span class="r-here">Gestione tavoli</span>
    </div>
    <div class="r-title-side"><?php echo htmlspecialchars((string)$_smarty_tpl->getValue('nomeRistorante'), ENT_QUOTES, 'UTF-8', true);?>
</div>
  </header>

  <main class="r-main">
        <div class="r-card">
      <table class="r-table">
        <thead>
          <tr>
            <th>Numero</th><th>Stato</th><th>Coperti</th><th>Password</th><th>Conto</th><th class="r-right">Azioni</th>
          </tr>
        </thead>
        <tbody id="tavoliBody"></tbody>
      </table>
      <div class="r-empty" id="tavoliEmpty" style="display:none;">Nessun tavolo. Aggiungine uno qui sotto.</div>
    </div>

        <div class="r-add">
      <div class="r-add-title">+ Aggiungi un nuovo tavolo</div>
      <div class="r-add-row">
        <div class="r-field">
          <label>Numero tavolo</label>
          <input type="text" id="nuovoNumero" placeholder="Es. 6">
        </div>
        <div class="r-field">
          <label>Coperti (opzionale)</label>
          <input type="number" id="nuovoCoperti" min="0" placeholder="Es. 4">
        </div>
        <div class="r-field">
          <label>Password (opzionale)</label>
          <input type="text" id="nuovaPassword" placeholder="Lascia vuoto per nessuna">
        </div>
        <button class="r-btn r-btn-primary" id="btnCrea">Crea tavolo</button>
      </div>
      <div class="r-msg" id="creaMsg"></div>
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
