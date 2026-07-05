<?php
/* Smarty version 5.8.2, created on 2026-06-25 15:24:11
  from 'file:cucina/disponibilita.tpl' */

/* @var \Smarty\Template $_smarty_tpl */
if ($_smarty_tpl->getCompiled()->isFresh($_smarty_tpl, array (
  'version' => '5.8.2',
  'unifunc' => 'content_6a3d2bfb75fb69_87444642',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '2dd53637e86b32efccf83152ab90aeff4e5dc46d' => 
    array (
      0 => 'cucina/disponibilita.tpl',
      1 => 1782393371,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
))) {
function content_6a3d2bfb75fb69_87444642 (\Smarty\Template $_smarty_tpl) {
$_smarty_current_dir = 'C:\\xampp\\htdocs\\OrderApp-main\\presentation\\templates\\cucina';
?><!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cucina - Disponibilità piatti</title>
  <link rel="stylesheet" href="presentation/assets/cucina.css">
</head>
<body>
<div class="kitchen">

  <header class="k-top">
    <div class="k-brand">
      <span class="k-dot"></span>
      <div>
        <div class="k-title">Disponibilità piatti</div>
        <div class="k-sub"><?php echo htmlspecialchars((string)$_smarty_tpl->getValue('nomeCucina'), ENT_QUOTES, 'UTF-8', true);?>
 &middot; spegni i piatti che non puoi preparare</div>
      </div>
    </div>
    <div class="k-actions">
      <a class="k-btn" href="index.php?controller=Cucina&action=mostraOrdini">Torna agli ordini</a>
      <a class="k-btn k-btn-ghost" href="index.php?controller=Login&action=logout">Esci</a>
    </div>
  </header>

  <main class="k-dishes">
    <?php if ($_smarty_tpl->getSmarty()->getModifierCallback('count')($_smarty_tpl->getValue('piatti')) == 0) {?>
      <div class="k-empty">Nessun piatto assegnato a questa cucina.</div>
    <?php } else { ?>
      <?php
$_from = $_smarty_tpl->getSmarty()->getRuntime('Foreach')->init($_smarty_tpl, $_smarty_tpl->getValue('piatti'), 'piatto');
$foreach0DoElse = true;
foreach ($_from ?? [] as $_smarty_tpl->getVariable('piatto')->value) {
$foreach0DoElse = false;
?>
        <div class="k-dish <?php if (!$_smarty_tpl->getValue('piatto')['disponibile']) {?>k-dish-off<?php }?>" data-id="<?php echo $_smarty_tpl->getValue('piatto')['id'];?>
">
          <div class="k-dish-info">
            <div class="k-dish-name"><?php echo htmlspecialchars((string)$_smarty_tpl->getValue('piatto')['nome'], ENT_QUOTES, 'UTF-8', true);?>
</div>
            <div class="k-dish-state">
              <span class="k-state-dot"></span>
              <span class="k-state-text"><?php if ($_smarty_tpl->getValue('piatto')['disponibile']) {?>Disponibile<?php } else { ?>Non disponibile<?php }?></span>
            </div>
          </div>
          <button class="k-switch <?php if ($_smarty_tpl->getValue('piatto')['disponibile']) {?>on<?php } else { ?>off<?php }?>" role="switch"
                  aria-checked="<?php if ($_smarty_tpl->getValue('piatto')['disponibile']) {?>true<?php } else { ?>false<?php }?>"
                  aria-label="Disponibilità <?php echo htmlspecialchars((string)$_smarty_tpl->getValue('piatto')['nome'], ENT_QUOTES, 'UTF-8', true);?>
">
            <span class="k-switch-knob"></span>
          </button>
        </div>
      <?php
}
$_smarty_tpl->getSmarty()->getRuntime('Foreach')->restore($_smarty_tpl, 1);?>
    <?php }?>
  </main>

</div>
<?php echo '<script'; ?>
 src="presentation/assets/cucina.js"><?php echo '</script'; ?>
>
</body>
</html>
<?php }
}
