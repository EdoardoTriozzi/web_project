{*
  Template Smarty — Gestione ristoranti (admin/ristoranti.tpl)

  La schermata dell'amministratore: elenco di tutti i ristoranti della
  piattaforma, con stato attivo/disattivo e azioni (attiva/disattiva,
  elimina), piu' un form per crearne di nuovi.

  La tabella viene riempita dal JavaScript (admin.js) leggendo i dati
  iniziali dall'attributo data-ristoranti. Riusa lo stile del ristorante
  (ristorante.css) per coerenza visiva.

  Variabili attese dal control (CAmministratore::mostraRistoranti):
    $ristoranti (stringa JSON: [ {id, nome, username, attivo}, ... ])
*}
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Gestione ristoranti - Amministratore</title>
  <link rel="stylesheet" href="presentation/assets/ristorante.css">
</head>
<body>
<div class="r-wrap" id="adminWrap" data-ristoranti='{$ristoranti}'>

  <header class="r-top">
    <div class="r-crumbs">
      <span class="r-here">Amministratore &middot; Ristoranti</span>
    </div>
    <div class="r-title-side">
      <a href="index.php?controller=Login&action=logout" class="r-back">Esci</a>
    </div>
  </header>

  <main class="r-main">

    {* --- Elenco ristoranti --- *}
    <div class="r-sec-label">Ristoranti registrati</div>
    <div class="r-card">
      <table class="r-table">
        <thead>
          <tr><th>Nome</th><th>Username</th><th>Stato</th><th class="r-right">Azioni</th></tr>
        </thead>
        <tbody id="ristorantiBody"></tbody>
      </table>
      <div class="r-empty" id="ristorantiEmpty" style="display:none;">Nessun ristorante registrato. Creane uno qui sotto.</div>
    </div>

    {* --- Crea nuovo ristorante --- *}
    <div class="r-add">
      <div class="r-add-title">+ Aggiungi un ristorante</div>
      <div class="r-add-row">
        <div class="r-field"><label>Nome del ristorante</label><input type="text" id="nNome" placeholder="Es. Sushi Glicine"></div>
        <div class="r-field"><label>Username di accesso</label><input type="text" id="nUsername" placeholder="Es. sushiglicine"></div>
        <div class="r-field"><label>Password</label><input type="password" id="nPassword" placeholder="Password iniziale"></div>
      </div>
      <div style="margin-top:12px;">
        <button class="r-btn r-btn-primary" id="btnCrea">Crea ristorante</button>
      </div>
      <div class="r-msg" id="creaMsg"></div>
    </div>

  </main>

</div>
<script src="presentation/assets/admin.js"></script>
</body>
</html>
