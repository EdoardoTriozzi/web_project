<?php
/* Smarty version 5.8.2, created on 2026-06-25 15:30:57
  from 'file:ristorante/home.tpl' */

/* @var \Smarty\Template $_smarty_tpl */
if ($_smarty_tpl->getCompiled()->isFresh($_smarty_tpl, array (
  'version' => '5.8.2',
  'unifunc' => 'content_6a3d2d91b5b9e3_79981796',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '934701e2242161898ade23467417ff4e24c03e6e' => 
    array (
      0 => 'ristorante/home.tpl',
      1 => 1782394098,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
))) {
function content_6a3d2d91b5b9e3_79981796 (\Smarty\Template $_smarty_tpl) {
$_smarty_current_dir = 'C:\\xampp\\htdocs\\OrderApp-main\\presentation\\templates\\ristorante';
?><!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars((string)$_smarty_tpl->getValue('nomeRistorante'), ENT_QUOTES, 'UTF-8', true);?>
 - Pannello</title>
  <link rel="stylesheet" href="presentation/assets/ristorante.css">
</head>
<body>
<div class="r-wrap">

  <header class="r-top">
    <div class="r-brand">
      <span class="r-logo"></span>
      <div>
        <div class="r-shop-name"><?php echo htmlspecialchars((string)$_smarty_tpl->getValue('nomeRistorante'), ENT_QUOTES, 'UTF-8', true);?>
</div>
        <div class="r-shop-sub">Pannello di gestione</div>
      </div>
    </div>
    <a class="r-btn" href="index.php?controller=Login&action=logout">Esci</a>
  </header>

  <main class="r-home-grid">

    <a class="r-tile" href="index.php?controller=Ristorante&action=mostraTavoli">
      <span class="r-tile-ic r-ic-tavoli"></span>
      <span class="r-tile-name">Tavoli</span>
      <span class="r-tile-desc">Aggiungi, rimuovi, password e stato dei tavoli</span>
    </a>

    <a class="r-tile" href="index.php?controller=Ristorante&action=mostraCucine">
      <span class="r-tile-ic r-ic-cucine"></span>
      <span class="r-tile-name">Cucine</span>
      <span class="r-tile-desc">Gestisci le cucine e le loro credenziali</span>
    </a>

    <a class="r-tile" href="index.php?controller=Ristorante&action=mostraConti">
      <span class="r-tile-ic r-ic-conti"></span>
      <span class="r-tile-name">Conti</span>
      <span class="r-tile-desc">Richieste di conto e totali per tavolo</span>
    </a>

    <a class="r-tile" href="index.php?controller=Ristorante&action=mostraMenu">
      <span class="r-tile-ic r-ic-menu"></span>
      <span class="r-tile-name">Menu</span>
      <span class="r-tile-desc">Piatti, categorie e allergeni</span>
    </a>

    <a class="r-tile" href="index.php?controller=Ristorante&action=mostraStatistiche">
      <span class="r-tile-ic r-ic-stat"></span>
      <span class="r-tile-name">Statistiche</span>
      <span class="r-tile-desc">Piatti più venduti e andamento</span>
    </a>

    <a class="r-tile" href="index.php?controller=Ristorante&action=mostraProfilo">
      <span class="r-tile-ic r-ic-profilo"></span>
      <span class="r-tile-name">Profilo</span>
      <span class="r-tile-desc">Nome del ristorante e password</span>
    </a>

  </main>

</div>
</body>
</html>
<?php }
}
