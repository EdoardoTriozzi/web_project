<?php
/* Smarty version 5.8.2, created on 2026-06-26 12:03:50
  from 'file:ristorante/statistiche.tpl' */

/* @var \Smarty\Template $_smarty_tpl */
if ($_smarty_tpl->getCompiled()->isFresh($_smarty_tpl, array (
  'version' => '5.8.2',
  'unifunc' => 'content_6a3e4e8605c9e3_32232000',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '0d9c0a4bba8afc7025b85dbff9d8e8be218aa700' => 
    array (
      0 => 'ristorante/statistiche.tpl',
      1 => 1782468074,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
))) {
function content_6a3e4e8605c9e3_32232000 (\Smarty\Template $_smarty_tpl) {
$_smarty_current_dir = 'C:\\xampp\\htdocs\\OrderApp-main\\presentation\\templates\\ristorante';
?><!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Statistiche - <?php echo htmlspecialchars((string)$_smarty_tpl->getValue('nomeRistorante'), ENT_QUOTES, 'UTF-8', true);?>
</title>
  <link rel="stylesheet" href="presentation/assets/ristorante.css">
</head>
<body>
<div class="r-wrap">

  <header class="r-top">
    <div class="r-crumbs">
      <a href="index.php?controller=Ristorante&action=mostraHome" class="r-back">&larr; Home</a>
      <span class="r-sep">/</span>
      <span class="r-here">Statistiche</span>
    </div>
    <div class="r-title-side"><?php echo htmlspecialchars((string)$_smarty_tpl->getValue('nomeRistorante'), ENT_QUOTES, 'UTF-8', true);?>
</div>
  </header>

  <main class="r-main">

        <div class="r-periodi">
      <?php
$_from = $_smarty_tpl->getSmarty()->getRuntime('Foreach')->init($_smarty_tpl, $_smarty_tpl->getValue('periodi'), 'etichetta', false, 'chiave');
$foreach0DoElse = true;
foreach ($_from ?? [] as $_smarty_tpl->getVariable('chiave')->value => $_smarty_tpl->getVariable('etichetta')->value) {
$foreach0DoElse = false;
?>
        <a href="index.php?controller=Ristorante&action=mostraStatistiche&periodo=<?php echo $_smarty_tpl->getValue('chiave');?>
"
           class="r-periodo <?php if ($_smarty_tpl->getValue('periodo') == $_smarty_tpl->getValue('chiave')) {?>attivo<?php }?>"><?php echo $_smarty_tpl->getValue('etichetta');?>
</a>
      <?php
}
$_smarty_tpl->getSmarty()->getRuntime('Foreach')->restore($_smarty_tpl, 1);?>
    </div>

        <div class="r-sec-label">Riepilogo incassi</div>
    <div class="r-stats-grid">
      <div class="r-stat-box">
        <div class="r-stat-val"><?php echo $_smarty_tpl->getValue('incasso');?>
 &euro;</div>
        <div class="r-stat-lbl">Incasso totale</div>
      </div>
      <div class="r-stat-box">
        <div class="r-stat-val"><?php echo $_smarty_tpl->getValue('nPagamenti');?>
</div>
        <div class="r-stat-lbl">Pagamenti registrati</div>
      </div>
      <div class="r-stat-box">
        <div class="r-stat-val"><?php echo $_smarty_tpl->getValue('scontrinoMedio');?>
 &euro;</div>
        <div class="r-stat-lbl">Scontrino medio</div>
      </div>
    </div>

        <div class="r-sec-label" style="margin-top:24px;">Piatti più venduti</div>
    <div class="r-card" style="padding:20px;">
      <?php if ($_smarty_tpl->getValue('haDati')) {?>
        <div class="r-chart">
          <?php
$_from = $_smarty_tpl->getSmarty()->getRuntime('Foreach')->init($_smarty_tpl, $_smarty_tpl->getValue('classifica'), 'piatto');
$foreach1DoElse = true;
foreach ($_from ?? [] as $_smarty_tpl->getVariable('piatto')->value) {
$foreach1DoElse = false;
?>
            <div class="r-bar-row">
              <div class="r-bar-name"><?php echo htmlspecialchars((string)$_smarty_tpl->getValue('piatto')['nome'], ENT_QUOTES, 'UTF-8', true);?>
</div>
              <div class="r-bar-track">
                <div class="r-bar-fill" style="width:<?php echo $_smarty_tpl->getValue('piatto')['percentuale'];?>
%;"></div>
              </div>
              <div class="r-bar-val"><?php echo $_smarty_tpl->getValue('piatto')['quantita'];?>
</div>
            </div>
          <?php
}
$_smarty_tpl->getSmarty()->getRuntime('Foreach')->restore($_smarty_tpl, 1);?>
        </div>
      <?php } else { ?>
        <div class="r-empty">Ancora nessun dato. Le statistiche compaiono dopo i primi pagamenti.</div>
      <?php }?>
    </div>

  </main>

</div>
</body>
</html>
<?php }
}
