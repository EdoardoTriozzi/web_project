<?php
/* Smarty version 5.8.2, created on 2026-06-26 01:15:39
  from 'file:ristorante/conti.tpl' */

/* @var \Smarty\Template $_smarty_tpl */
if ($_smarty_tpl->getCompiled()->isFresh($_smarty_tpl, array (
  'version' => '5.8.2',
  'unifunc' => 'content_6a3db69b1d95f8_78975926',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '6c68eebbe578568147caca971a67ac7b95f2dcc6' => 
    array (
      0 => 'ristorante/conti.tpl',
      1 => 1782428737,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
))) {
function content_6a3db69b1d95f8_78975926 (\Smarty\Template $_smarty_tpl) {
$_smarty_current_dir = 'C:\\xampp\\htdocs\\OrderApp-main\\presentation\\templates\\ristorante';
?><!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Conti - <?php echo htmlspecialchars((string)$_smarty_tpl->getValue('nomeRistorante'), ENT_QUOTES, 'UTF-8', true);?>
</title>
  <link rel="stylesheet" href="presentation/assets/ristorante.css">
</head>
<body>
<div class="r-wrap" id="contiWrap">

  <header class="r-top">
    <div class="r-crumbs">
      <a href="index.php?controller=Ristorante&action=mostraHome" class="r-back">&larr; Home</a>
      <span class="r-sep">/</span>
      <span class="r-here">Conti dei tavoli</span>
    </div>
    <div class="r-title-side"><?php echo htmlspecialchars((string)$_smarty_tpl->getValue('nomeRistorante'), ENT_QUOTES, 'UTF-8', true);?>
</div>
  </header>

  <main class="r-main">

        <?php $_smarty_tpl->assign('inAttesa', 0, false, NULL);?>
    <?php
$_from = $_smarty_tpl->getSmarty()->getRuntime('Foreach')->init($_smarty_tpl, $_smarty_tpl->getValue('tavoli'), 't');
$foreach0DoElse = true;
foreach ($_from ?? [] as $_smarty_tpl->getVariable('t')->value) {
$foreach0DoElse = false;
if ($_smarty_tpl->getValue('t')['contoRichiesto']) {
$_smarty_tpl->assign('inAttesa', $_smarty_tpl->getValue('inAttesa')+1, false, NULL);
}
}
$_smarty_tpl->getSmarty()->getRuntime('Foreach')->restore($_smarty_tpl, 1);?>

    <?php if ($_smarty_tpl->getValue('inAttesa') > 0) {?>
      <div class="r-sec-label">Richieste di conto in attesa</div>
      <div class="r-card r-card-alert" style="margin-bottom:22px;">
        <table class="r-table">
          <tbody>
            <?php
$_from = $_smarty_tpl->getSmarty()->getRuntime('Foreach')->init($_smarty_tpl, $_smarty_tpl->getValue('tavoli'), 't');
$foreach1DoElse = true;
foreach ($_from ?? [] as $_smarty_tpl->getVariable('t')->value) {
$foreach1DoElse = false;
?>
              <?php if ($_smarty_tpl->getValue('t')['contoRichiesto']) {?>
                <tr>
                  <td class="r-num">Tavolo <?php echo htmlspecialchars((string)$_smarty_tpl->getValue('t')['numero'], ENT_QUOTES, 'UTF-8', true);?>

                    <span class="r-badge r-badge-conto" style="margin-left:8px;">Conto richiesto</span>
                  </td>
                  <td><?php if ($_smarty_tpl->getValue('t')['coperti'] > 0) {
echo $_smarty_tpl->getValue('t')['coperti'];?>
 coperti<?php } else { ?>&mdash;<?php }?></td>
                  <td style="font-weight:600; font-size:15px;"><?php echo $_smarty_tpl->getValue('t')['speso'];?>
 &euro;</td>
                  <td class="r-actions-cell">
                    <button class="r-btn r-btn-sm" data-az="modifica" data-id="<?php echo $_smarty_tpl->getValue('t')['id'];?>
">Modifica</button>
                    <button class="r-btn r-btn-sm r-btn-pay" data-az="paga" data-id="<?php echo $_smarty_tpl->getValue('t')['id'];?>
">&#10003; Segna pagato</button>
                  </td>
                </tr>
              <?php }?>
            <?php
}
$_smarty_tpl->getSmarty()->getRuntime('Foreach')->restore($_smarty_tpl, 1);?>
          </tbody>
        </table>
      </div>
    <?php }?>

        <div class="r-sec-label">Tutti i tavoli</div>
    <div class="r-card">
      <table class="r-table">
        <thead>
          <tr>
            <th>Tavolo</th><th>Stato</th><th>Speso finora</th><th>Conto</th><th class="r-right">Azioni</th>
          </tr>
        </thead>
        <tbody>
          <?php
$_from = $_smarty_tpl->getSmarty()->getRuntime('Foreach')->init($_smarty_tpl, $_smarty_tpl->getValue('tavoli'), 't');
$foreach2DoElse = true;
foreach ($_from ?? [] as $_smarty_tpl->getVariable('t')->value) {
$foreach2DoElse = false;
?>
            <tr <?php if ($_smarty_tpl->getValue('t')['contoRichiesto']) {?>class="r-row-alert"<?php }?>>
              <td class="r-num">Tavolo <?php echo htmlspecialchars((string)$_smarty_tpl->getValue('t')['numero'], ENT_QUOTES, 'UTF-8', true);?>
</td>
              <td>
                <?php if ($_smarty_tpl->getValue('t')['stato'] == 'libero') {?>
                  <span class="r-badge r-badge-libero">Libero</span>
                <?php } else { ?>
                  <span class="r-badge r-badge-occupato">Occupato</span>
                <?php }?>
              </td>
              <td><?php if ($_smarty_tpl->getValue('t')['spesoNum'] > 0) {?><span style="font-weight:500;"><?php echo $_smarty_tpl->getValue('t')['speso'];?>
 &euro;</span><?php } else { ?><span class="r-muted">&mdash;</span><?php }?></td>
              <td><?php if ($_smarty_tpl->getValue('t')['contoRichiesto']) {?><span style="color:var(--danger); font-weight:500;">Richiesto</span><?php } else { ?><span class="r-muted">&mdash;</span><?php }?></td>
              <td class="r-actions-cell">
                <?php if ($_smarty_tpl->getValue('t')['spesoNum'] > 0 || $_smarty_tpl->getValue('t')['contoRichiesto']) {?>
                  <button class="r-btn r-btn-sm" data-az="modifica" data-id="<?php echo $_smarty_tpl->getValue('t')['id'];?>
">Modifica / Paga</button>
                <?php } else { ?>
                  <span class="r-muted">&mdash;</span>
                <?php }?>
              </td>
            </tr>
          <?php
}
$_smarty_tpl->getSmarty()->getRuntime('Foreach')->restore($_smarty_tpl, 1);?>
        </tbody>
      </table>
    </div>

  </main>

    <div class="r-overlay" id="contoOverlay">
    <div class="r-dialog">
      <div class="r-dialog-head">
        <span class="r-dialog-title" id="contoTitolo">Conto tavolo</span>
        <button class="r-x" id="contoClose" aria-label="Chiudi">&times;</button>
      </div>

      <div class="r-dialog-body">
        <div class="r-voci-head">
          <span>Voce</span><span>Prezzo</span><span>Q.tà</span><span></span>
        </div>
        <div id="contoVoci"><!-- righe dal JS --></div>

        <div class="r-add-voce">
          <select id="contoPiattoSel" class="r-select"><!-- opzioni dal JS --></select>
          <button class="r-btn r-btn-sm" id="contoAddBtn">+ Aggiungi</button>
        </div>
      </div>

      <div class="r-dialog-foot">
        <div class="r-tot-line">Totale da pagare <span class="r-tot-val" id="contoTotale">0,00 &euro;</span></div>
        <div class="r-dialog-actions">
          <button class="r-btn" id="contoAnnulla">Chiudi</button>
          <button class="r-btn r-btn-pay" id="contoPaga">&#10003; Conferma pagamento</button>
        </div>
      </div>
    </div>
  </div>

</div>
<?php echo '<script'; ?>
 src="presentation/assets/ristorante.js"><?php echo '</script'; ?>
>
</body>
</html>
<?php }
}
