{*
  Template Smarty — Gestione tavoli (ristorante/tavoli.tpl)

  La tabella dei tavoli viene riempita e aggiornata dal JavaScript
  (ristorante.js), che legge i dati iniziali dall'attributo data-tavoli.

  Variabili attese dal control (CRistorante::mostraTavoli):
    $nomeRistorante  (stringa)
    $tavoliIniziali  (stringa JSON gia' pronta e "escaped" per attributo HTML)

  Il JavaScript (ristorante.js) NON cambia.
*}
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Gestione tavoli - {$nomeRistorante|escape}</title>
  <link rel="stylesheet" href="presentation/assets/ristorante.css">
</head>
<body>
<div class="r-wrap" data-tavoli='{$tavoliIniziali}'>

  <header class="r-top">
    <div class="r-crumbs">
      <a href="index.php?controller=Ristorante&action=mostraHome" class="r-back">&larr; Home</a>
      <span class="r-sep">/</span>
      <span class="r-here">Gestione tavoli</span>
    </div>
    <div class="r-title-side">{$nomeRistorante|escape}</div>
  </header>

  <main class="r-main">
    {* Tabella tavoli (riempita dal JS) *}
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

    {* Form aggiungi tavolo *}
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
<script src="presentation/assets/ristorante.js"></script>
</body>
</html>
