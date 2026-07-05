{*
  Template Smarty — Ordini cucina (cucina/ordini.tpl)

  Tabellone a 3 colonne per stato. Le schede degli ordini le disegna il
  JavaScript (cucina.js) leggendo i dati iniziali dall'attributo data-iniziali
  e poi aggiornandosi col polling. Qui c'e' solo l'intelaiatura.

  Variabili attese dal control (CCucina::mostraOrdini):
    $nomeCucina    (stringa)
    $datiIniziali  (stringa JSON gia' pronta e gia' "escaped" per attributo HTML)

  Il JavaScript (cucina.js) NON cambia.
*}
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cucina - Ordini</title>
  <link rel="stylesheet" href="presentation/assets/cucina.css">
</head>
<body>
<div class="kitchen" data-iniziali='{$datiIniziali}'>

  <header class="k-top">
    <div class="k-brand">
      <span class="k-dot"></span>
      <div>
        <div class="k-title">{$nomeCucina|escape}</div>
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
<script src="presentation/assets/cucina.js"></script>
</body>
</html>
