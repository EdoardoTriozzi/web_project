{*
  Template Smarty — Home ristorante (ristorante/home.tpl)
  Sei riquadri che portano alle sezioni di gestione.
  Variabile attesa: $nomeRistorante (stringa)
*}
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{$nomeRistorante|escape} - Pannello</title>
  <link rel="stylesheet" href="presentation/assets/ristorante.css">
</head>
<body>
<div class="r-wrap">

  <header class="r-top">
    <div class="r-brand">
      <span class="r-logo"></span>
      <div>
        <div class="r-shop-name">{$nomeRistorante|escape}</div>
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
