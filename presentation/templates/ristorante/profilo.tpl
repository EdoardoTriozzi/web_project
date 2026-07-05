{*
  Template Smarty — Profilo del ristorante (ristorante/profilo.tpl)

  Due riquadri: cambio nome e cambio password.
  Lo username non e' modificabile (e' l'identificativo di accesso), viene
  solo mostrato.

  Variabili attese dal control (CRistorante::mostraProfilo):
    $nomeRistorante (stringa)
    $username       (stringa)
*}
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Profilo - {$nomeRistorante|escape}</title>
  <link rel="stylesheet" href="presentation/assets/ristorante.css">
</head>
<body>
<div class="r-wrap" id="profiloWrap">

  <header class="r-top">
    <div class="r-crumbs">
      <a href="index.php?controller=Ristorante&action=mostraHome" class="r-back">&larr; Home</a>
      <span class="r-sep">/</span>
      <span class="r-here">Profilo</span>
    </div>
    <div class="r-title-side">{$nomeRistorante|escape}</div>
  </header>

  <main class="r-main" style="max-width:560px;">

    {* --- Nome del ristorante --- *}
    <div class="r-sec-label">Nome del ristorante</div>
    <div class="r-card" style="padding:20px; margin-bottom:22px;">
      <div class="r-field" style="margin-bottom:6px;">
        <label>Username di accesso (non modificabile)</label>
        <input type="text" value="{$username|escape}" disabled class="r-input-disabled">
      </div>
      <div class="r-field">
        <label>Nome del ristorante</label>
        <input type="text" id="profNome" value="{$nomeRistorante|escape}">
      </div>
      <div style="margin-top:14px;">
        <button class="r-btn r-btn-primary" id="btnSalvaNome">Salva nome</button>
      </div>
      <div class="r-msg" id="nomeMsg"></div>
    </div>

    {* --- Password --- *}
    <div class="r-sec-label">Cambia password</div>
    <div class="r-card" style="padding:20px;">
      <div class="r-field" style="margin-bottom:10px;">
        <label>Password attuale</label>
        <input type="password" id="profAttuale" placeholder="La password che usi ora">
      </div>
      <div class="r-field" style="margin-bottom:10px;">
        <label>Nuova password</label>
        <input type="password" id="profNuova" placeholder="La nuova password">
      </div>
      <div class="r-field">
        <label>Ripeti nuova password</label>
        <input type="password" id="profRipeti" placeholder="Scrivi di nuovo la nuova password">
      </div>
      <div style="margin-top:14px;">
        <button class="r-btn r-btn-primary" id="btnCambiaPwd">Cambia password</button>
      </div>
      <div class="r-msg" id="pwdMsg"></div>
    </div>

  </main>

</div>
<script src="presentation/assets/ristorante.js"></script>
</body>
</html>
