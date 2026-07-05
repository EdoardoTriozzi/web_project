<?php
/* Smarty version 5.8.2, created on 2026-06-25 15:22:24
  from 'file:cucina/ordini.tpl' */

/* @var \Smarty\Template $_smarty_tpl */
if ($_smarty_tpl->getCompiled()->isFresh($_smarty_tpl, array (
  'version' => '5.8.2',
  'unifunc' => 'content_6a3d2b900b47d8_78164523',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    'edfeb94b96c315716a301c7f0f349a7b9fd2b0d9' => 
    array (
      0 => 'cucina/ordini.tpl',
      1 => 1782393368,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
))) {
function content_6a3d2b900b47d8_78164523 (\Smarty\Template $_smarty_tpl) {
$_smarty_current_dir = 'C:\\xampp\\htdocs\\OrderApp-main\\presentation\\templates\\cucina';
?><!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cucina - Ordini</title>
  <link rel="stylesheet" href="presentation/assets/cucina.css">
</head>
<body>
<div class="kitchen" data-iniziali='<?php echo $_smarty_tpl->getValue('datiIniziali');?>
'>

  <header class="k-top">
    <div class="k-brand">
      <span class="k-dot"></span>
      <div>
        <div class="k-title"><?php echo htmlspecialchars((string)$_smarty_tpl->getValue('nomeCucina'), ENT_QUOTES, 'UTF-8', true);?>
</div>
        <div class="k-sub">Ordini in tempo reale</div>
      </div>
    </div>
    <div class="k-actions">
      <span class="k-updated" id="kUpdated">&nbsp;</span>
      <div class="k-filtro-wrap">
        <button class="k-btn" id="btnFiltroTavoli">Filtra per tavolo</button>
        <div class="k-filtro-panel" id="filtroTavoliPanel">
          <div class="k-filtro-head">
            <span>Mostra solo i tavoli:</span>
            <button class="k-filtro-reset" id="btnResetFiltro">Tutti</button>
          </div>
          <div class="k-filtro-lista" id="filtroTavoliLista"></div>
        </div>
      </div>
      <button class="k-btn" id="btnPulisci">Pulisci consegnati</button>
      <a class="k-btn" href="index.php?controller=Cucina&action=mostraPiatti">Disponibilità piatti</a>
      <a class="k-btn k-btn-ghost" href="index.php?controller=Login&action=logout">Esci</a>
    </div>
  </header>

  <main class="k-board">
    <section class="k-col">
      <div class="k-col-head">
        <span class="k-col-name"><span class="k-bullet k-bullet-new"></span>Nuovi</span>
        <span class="k-count k-count-new" id="countNuovi">0</span>
      </div>
      <div class="k-col-body" id="colNuovi"></div>
    </section>

    <section class="k-col">
      <div class="k-col-head">
        <span class="k-col-name"><span class="k-bullet k-bullet-prep"></span>In preparazione</span>
        <span class="k-count k-count-prep" id="countPrep">0</span>
      </div>
      <div class="k-col-body" id="colPrep"></div>
    </section>

    <section class="k-col">
      <div class="k-col-head">
        <span class="k-col-name"><span class="k-bullet k-bullet-done"></span>Consegnati</span>
        <span class="k-count k-count-done" id="countDone">0</span>
      </div>
      <div class="k-col-body" id="colDone"></div>
    </section>
  </main>

</div>
<?php echo '<script'; ?>
 src="presentation/assets/cucina.js"><?php echo '</script'; ?>
>
</body>
</html>
<?php }
}
