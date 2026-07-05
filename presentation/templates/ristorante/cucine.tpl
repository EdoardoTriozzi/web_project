{*
  Template Smarty — Gestione cucine (ristorante/cucine.tpl)

  Tabella delle cucine (riempita dal JS) + form per aggiungerne.
  A differenza dei tavoli, le cucine hanno credenziali proprie
  (username/password) usate per il login della cucina.

  Variabili attese dal control (CRistorante::mostraCucine):
    $nomeRistorante  (stringa)
    $cucineIniziali  (stringa JSON gia' pronta e "escaped")

  Il JavaScript (ristorante.js) gestisce tabella e azioni.
*}
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Gestione cucine - {$nomeRistorante|escape}</title>
  <link rel="stylesheet" href="presentation/assets/ristorante.css">
</head>
<body>
<div class="r-wrap" data-cucine='{$cucineIniziali}'>

  <header class="r-top">
    <div class="r-crumbs">
      <a href="index.php?controller=Ristorante&action=mostraHome" class="r-back">&larr; Home</a>
      <span class="r-sep">/</span>
      <span class="r-here">Gestione cucine</span>
    </div>
    <div class="r-title-side">{$nomeRistorante|escape}</div>
  </header>

  <main class="r-main">
    {* Tabella cucine (riempita dal JS) *}
    <div class="r-card">
      <table class="r-table">
        <thead>
          <tr>
            <th>Nome cucina</th><th>Username</th><th class="r-right">Azioni</th>
          </tr>
        </thead>
        <tbody id="cucineBody"></tbody>
      </table>
      <div class="r-empty" id="cucineEmpty" style="display:none;">Nessuna cucina. Aggiungine una qui sotto.</div>
    </div>

    {* Form aggiungi cucina *}
    <div class="r-add">
      <div class="r-add-title">+ Aggiungi una nuova cucina</div>
      <div class="r-add-row">
        <div class="r-field">
          <label>Nome cucina</label>
          <input type="text" id="nuovoNome" placeholder="Es. Cucina calda">
        </div>
        <div class="r-field">
          <label>Username</label>
          <input type="text" id="nuovoUsername" placeholder="Es. cucina_calda">
        </div>
        <div class="r-field">
          <label>Password</label>
          <input type="text" id="nuovaPasswordCucina" placeholder="Password di accesso">
        </div>
        <button class="r-btn r-btn-primary" id="btnCreaCucina">Crea cucina</button>
      </div>
      <div class="r-msg" id="creaCucinaMsg"></div>
    </div>
  </main>

</div>
<script src="presentation/assets/ristorante.js"></script>
</body>
</html>
