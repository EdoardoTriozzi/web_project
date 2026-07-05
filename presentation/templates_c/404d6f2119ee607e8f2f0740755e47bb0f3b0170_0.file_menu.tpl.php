<?php
/* Smarty version 5.8.2, created on 2026-06-26 15:04:28
  from 'file:menu/menu.tpl' */

/* @var \Smarty\Template $_smarty_tpl */
if ($_smarty_tpl->getCompiled()->isFresh($_smarty_tpl, array (
  'version' => '5.8.2',
  'unifunc' => 'content_6a3e78dca4baa7_27168584',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '404d6f2119ee607e8f2f0740755e47bb0f3b0170' => 
    array (
      0 => 'menu/menu.tpl',
      1 => 1782478971,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
))) {
function content_6a3e78dca4baa7_27168584 (\Smarty\Template $_smarty_tpl) {
$_smarty_current_dir = 'C:\\xampp\\htdocs\\OrderApp-main\\presentation\\templates\\menu';
?><!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Menu - <?php echo htmlspecialchars((string)$_smarty_tpl->getValue('numeroTavolo'), ENT_QUOTES, 'UTF-8', true);?>
</title>
  <link rel="stylesheet" href="presentation/assets/tavolo.css">
</head>
<body>
<div class="app">

  <header class="header">
    <div class="topbar">
      <div class="brand"><span class="dot"></span> <?php echo htmlspecialchars((string)$_smarty_tpl->getValue('nomeRistorante'), ENT_QUOTES, 'UTF-8', true);?>
</div>
      <span class="table-label"><?php echo htmlspecialchars((string)$_smarty_tpl->getValue('numeroTavolo'), ENT_QUOTES, 'UTF-8', true);?>
</span>
    </div>
    <div class="filters">
      <button class="drop-btn" id="btnCat"><span id="catLabel">Tutte le categorie</span><span class="chev"></span></button>
      <div class="panel" id="panelCat">
        <button class="chip active" data-cat="">Tutte</button>
        <?php
$_from = $_smarty_tpl->getSmarty()->getRuntime('Foreach')->init($_smarty_tpl, $_smarty_tpl->getValue('gruppi'), 'g');
$foreach0DoElse = true;
foreach ($_from ?? [] as $_smarty_tpl->getVariable('g')->value) {
$foreach0DoElse = false;
?>
          <?php if ($_smarty_tpl->getValue('g')['catId'] != 'cat-0') {?>
            <button class="chip" data-cat="<?php echo $_smarty_tpl->getValue('g')['catId'];?>
"><?php echo htmlspecialchars((string)$_smarty_tpl->getValue('g')['catNome'], ENT_QUOTES, 'UTF-8', true);?>
</button>
          <?php }?>
        <?php
}
$_smarty_tpl->getSmarty()->getRuntime('Foreach')->restore($_smarty_tpl, 1);?>
      </div>

      <button class="drop-btn" id="btnAll"><span>Filtra allergeni <span class="badge-mini" id="allCount" style="display:none;"></span></span><span class="chev"></span></button>
      <div class="panel" id="panelAll">
        <?php
$_from = $_smarty_tpl->getSmarty()->getRuntime('Foreach')->init($_smarty_tpl, $_smarty_tpl->getValue('catalogoAllergeni'), 'nomeAll');
$foreach1DoElse = true;
foreach ($_from ?? [] as $_smarty_tpl->getVariable('nomeAll')->value) {
$foreach1DoElse = false;
?>
          <label class="all-opt"><input type="checkbox" class="all-chk" value="<?php echo htmlspecialchars((string)$_smarty_tpl->getValue('nomeAll'), ENT_QUOTES, 'UTF-8', true);?>
"> <?php echo htmlspecialchars((string)$_smarty_tpl->getValue('nomeAll'), ENT_QUOTES, 'UTF-8', true);?>
</label>
        <?php
}
$_smarty_tpl->getSmarty()->getRuntime('Foreach')->restore($_smarty_tpl, 1);?>
        <button class="btn" id="resetAll" style="width:100%; margin-top:4px;">Rimuovi filtro</button>
      </div>
    </div>
  </header>

  <main class="menu" id="menuList">
    <?php
$_from = $_smarty_tpl->getSmarty()->getRuntime('Foreach')->init($_smarty_tpl, $_smarty_tpl->getValue('gruppi'), 'g');
$foreach2DoElse = true;
foreach ($_from ?? [] as $_smarty_tpl->getVariable('g')->value) {
$foreach2DoElse = false;
?>
      <div class="cat-title" data-cat="<?php echo $_smarty_tpl->getValue('g')['catId'];?>
"><?php echo htmlspecialchars((string)$_smarty_tpl->getValue('g')['catNome'], ENT_QUOTES, 'UTF-8', true);?>
</div>

      <?php
$_from = $_smarty_tpl->getSmarty()->getRuntime('Foreach')->init($_smarty_tpl, $_smarty_tpl->getValue('g')['piatti'], 'piatto');
$foreach3DoElse = true;
foreach ($_from ?? [] as $_smarty_tpl->getVariable('piatto')->value) {
$foreach3DoElse = false;
?>
        <div class="card" data-cat="<?php echo $_smarty_tpl->getValue('g')['catId'];?>
" data-all="<?php echo htmlspecialchars((string)$_smarty_tpl->getValue('piatto')['dataAll'], ENT_QUOTES, 'UTF-8', true);?>
">
          <div class="photo">
            <?php if ($_smarty_tpl->getValue('piatto')['immagine']) {?>
              <img src="<?php echo htmlspecialchars((string)$_smarty_tpl->getValue('piatto')['immagine'], ENT_QUOTES, 'UTF-8', true);?>
" alt="<?php echo htmlspecialchars((string)$_smarty_tpl->getValue('piatto')['nome'], ENT_QUOTES, 'UTF-8', true);?>
" class="foto-zoom" data-img="<?php echo htmlspecialchars((string)$_smarty_tpl->getValue('piatto')['immagine'], ENT_QUOTES, 'UTF-8', true);?>
" data-nome="<?php echo htmlspecialchars((string)$_smarty_tpl->getValue('piatto')['nome'], ENT_QUOTES, 'UTF-8', true);?>
" style="width:100%; height:100%; object-fit:cover; cursor:zoom-in;">
            <?php } else { ?>
              FOTO PIATTO
            <?php }?>
            <?php if ($_smarty_tpl->getValue('piatto')['etichetta']) {?>
              <span class="label"><?php echo htmlspecialchars((string)$_smarty_tpl->getValue('piatto')['etichetta'], ENT_QUOTES, 'UTF-8', true);?>
</span>
            <?php }?>
          </div>
          <div class="body">
            <div class="name"><?php echo htmlspecialchars((string)$_smarty_tpl->getValue('piatto')['nome'], ENT_QUOTES, 'UTF-8', true);?>
</div>
            <?php if ($_smarty_tpl->getValue('piatto')['descrizione']) {?>
              <div class="desc"><?php echo htmlspecialchars((string)$_smarty_tpl->getValue('piatto')['descrizione'], ENT_QUOTES, 'UTF-8', true);?>
</div>
            <?php }?>

            <div class="allergens">
              <?php if ($_smarty_tpl->getSmarty()->getModifierCallback('count')($_smarty_tpl->getValue('piatto')['allergeni']) > 0) {?>
                <span class="lbl">Allergeni:</span>
                <?php
$_from = $_smarty_tpl->getSmarty()->getRuntime('Foreach')->init($_smarty_tpl, $_smarty_tpl->getValue('piatto')['allergeni'], 'nomeAll');
$foreach4DoElse = true;
foreach ($_from ?? [] as $_smarty_tpl->getVariable('nomeAll')->value) {
$foreach4DoElse = false;
?>
                  <span class="atag"><?php echo htmlspecialchars((string)$_smarty_tpl->getValue('nomeAll'), ENT_QUOTES, 'UTF-8', true);?>
</span>
                <?php
}
$_smarty_tpl->getSmarty()->getRuntime('Foreach')->restore($_smarty_tpl, 1);?>
              <?php } else { ?>
                <span class="lbl">Nessun allergene</span>
              <?php }?>
            </div>

            <div class="prow">
              <span class="price"><?php echo $_smarty_tpl->getValue('piatto')['prezzo'];?>
 &euro;</span>
              <div class="stepper">
                <button class="step" data-id="<?php echo $_smarty_tpl->getValue('piatto')['id'];?>
" data-d="-1">-</button>
                <span class="val" id="q<?php echo $_smarty_tpl->getValue('piatto')['id'];?>
">0</span>
                <button class="step" data-id="<?php echo $_smarty_tpl->getValue('piatto')['id'];?>
" data-d="1">+</button>
              </div>
            </div>
          </div>
        </div>
      <?php
}
$_smarty_tpl->getSmarty()->getRuntime('Foreach')->restore($_smarty_tpl, 1);?>
    <?php
}
$_smarty_tpl->getSmarty()->getRuntime('Foreach')->restore($_smarty_tpl, 1);?>
  </main>

  <nav class="actions">
    <button class="act" id="btnStorico"><span class="ic ic-hist"></span> Storico</button>
    <button class="act" id="btnConto"><span class="ic ic-receipt"></span> Richiedi conto</button>
    <button class="act cart" id="btnCart"><span class="ic ic-cart"></span><span>Carrello - <span id="cartTotal">0,00 &euro;</span></span><span class="cart-badge" id="cartBadge">0</span></button>
  </nav>

    <div class="overlay overlay-img" id="overlayImg">
    <button class="img-close close-ov" data-t="overlayImg" aria-label="Chiudi">&times;</button>
    <img id="imgGrande" src="" alt="">
    <div class="img-caption" id="imgCaption"></div>
  </div>

    <div class="overlay" id="overlayCart"><div class="sheet">
    <div class="sheet-head"><span class="sheet-title">Carrello</span><button class="icon-btn close-ov" data-t="overlayCart" aria-label="Chiudi"></button></div>
    <div id="cartRows" style="flex:1;"></div>
    <div class="empty-cart" id="cartEmpty">Il carrello &egrave; vuoto.</div>
    <div class="total-row"><span style="font-weight:600;">Totale</span><span class="big"><span id="cartTotal2">0,00</span> &euro;</span></div>
    <div class="sheet-actions"><button class="btn" id="svuotaBtn">Svuota</button><button class="btn primary" id="inviaBtn" style="flex:2;">Invia in cucina</button></div>
  </div></div>

    <div class="overlay" id="overlayStorico"><div class="sheet">
    <div class="sheet-head"><span class="sheet-title">Ordini effettuati</span><button class="icon-btn close-ov" data-t="overlayStorico" aria-label="Chiudi"></button></div>
    <div id="storicoRows" style="flex:1;"></div>
    <div class="empty-cart" id="storicoEmpty">Nessun ordine inviato.</div>
    <div class="total-row"><span style="font-weight:600;">Totale ordinato</span><span class="big"><span id="storicoTot">0,00</span> &euro;</span></div>
  </div></div>

    <div class="overlay center" id="overlayConto"><div class="dialog">
    <div class="d-title">Richiesta del conto</div>
    <div class="d-text">Desidera richiedere il conto? Una volta confermata la richiesta non sar&agrave; pi&ugrave; possibile aggiungere altri piatti all'ordine.</div>
    <div class="d-actions"><button class="btn close-ov" data-t="overlayConto" style="flex:1;">Annulla</button><button class="btn primary" id="confermaConto" style="flex:1;">Conferma</button></div>
  </div></div>

    <div class="overlay center" id="overlayCassa"><div class="dialog">
    <div class="d-title" style="color:#5fd38a;">Conto richiesto</div>
    <div class="d-text">La preghiamo di recarsi alla cassa per saldare il conto. Grazie.</div>
    <button class="btn primary close-ov" data-t="overlayCassa" style="width:100%;">Ho capito</button>
  </div></div>

    <div class="overlay center" id="overlayInviato"><div class="dialog">
    <div class="d-title" style="color:#5fd38a;">Ordine inviato</div>
    <div class="d-text">Il tuo ordine &egrave; stato inviato in cucina. Lo trovi nello storico.</div>
    <button class="btn primary close-ov" data-t="overlayInviato" style="width:100%;">Ho capito</button>
  </div></div>

</div>

<?php echo '<script'; ?>
 src="presentation/assets/tavolo.js"><?php echo '</script'; ?>
>
</body>
</html>
<?php }
}
