{*
  Template Smarty — Menu del ristorante (ristorante/menu.tpl)

  Una pagina con TRE SCHEDE: Piatti, Categorie, Allergeni.
  Le schede si cambiano con i pulsanti in alto (gestiti dal JS).
  Ogni tabella e' riempita dal JS leggendo i dati iniziali da data-menu.

  Variabili attese dal control (CRistorante::mostraMenu):
    $nomeRistorante (stringa)
    $datiMenu (stringa JSON: { piatti:[], categorie:[], allergeni:[] })
*}
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Menu - {$nomeRistorante|escape}</title>
  <link rel="stylesheet" href="presentation/assets/ristorante.css">
</head>
<body>
<div class="r-wrap" id="menuWrap" data-menu='{$datiMenu}'>

  <header class="r-top">
    <div class="r-crumbs">
      <a href="index.php?controller=Ristorante&action=mostraHome" class="r-back">&larr; Home</a>
      <span class="r-sep">/</span>
      <span class="r-here">Menu</span>
    </div>
    <div class="r-title-side">{$nomeRistorante|escape}</div>
  </header>

  {* Schede *}
  <div class="r-tabs">
    <button class="r-tab active" data-tab="piatti">Piatti</button>
    <button class="r-tab" data-tab="categorie">Categorie</button>
    <button class="r-tab" data-tab="allergeni">Allergeni</button>
  </div>

  <main class="r-main">

    {* ===== SCHEDA PIATTI ===== *}
    <section class="r-pane" id="pane-piatti">
      <div class="r-card">
        <table class="r-table">
          <thead>
            <tr><th>Nome</th><th>Prezzo</th><th>Categoria</th><th>Cucina</th><th>Allergeni</th><th class="r-right">Azioni</th></tr>
          </thead>
          <tbody id="piattiBody"></tbody>
        </table>
        <div class="r-empty" id="piattiEmpty" style="display:none;">Nessun piatto. Aggiungine uno qui sotto.</div>
      </div>

      <div class="r-add">
        <div class="r-add-title">+ Aggiungi un piatto</div>
        <div class="r-add-row">
          <div class="r-field"><label>Nome</label><input type="text" id="pNome" placeholder="Es. Sake nigiri"></div>
          <div class="r-field"><label>Prezzo (&euro;)</label><input type="number" step="0.10" id="pPrezzo" placeholder="Es. 6.50"></div>
          <div class="r-field"><label>Categoria</label><select id="pCategoria" class="r-select"></select></div>
          <div class="r-field"><label>Cucina</label><select id="pCucina" class="r-select"></select></div>
        </div>
        <div class="r-add-row" style="margin-top:10px;">
          <div class="r-field" style="flex:2;"><label>Descrizione (opzionale)</label><input type="text" id="pDescrizione" placeholder="Breve descrizione"></div>
          <div class="r-field"><label>Etichetta (opzionale)</label><input type="text" id="pEtichetta" placeholder="Es. Consigliato"></div>
        </div>
        <div class="r-field" style="margin-top:10px;">
          <label>Allergeni</label>
          <div id="pAllergeni" class="r-chips"></div>
        </div>
        <div style="margin-top:12px;"><button class="r-btn r-btn-primary" id="pCrea">Crea piatto</button></div>
        <div class="r-msg" id="pMsg"></div>
      </div>
    </section>

    {* ===== SCHEDA CATEGORIE ===== *}
    <section class="r-pane" id="pane-categorie" style="display:none;">
      <div class="r-card">
        <table class="r-table">
          <thead><tr><th>Nome</th><th>Posizione</th><th class="r-right">Azioni</th></tr></thead>
          <tbody id="categorieBody"></tbody>
        </table>
        <div class="r-empty" id="categorieEmpty" style="display:none;">Nessuna categoria.</div>
      </div>
      <div class="r-add">
        <div class="r-add-title">+ Aggiungi una categoria</div>
        <div class="r-add-row">
          <div class="r-field"><label>Nome</label><input type="text" id="cNome" placeholder="Es. Antipasti"></div>
          <div class="r-field"><label>Posizione</label><input type="number" id="cPosizione" placeholder="Es. 1" min="0"></div>
          <button class="r-btn r-btn-primary" id="cCrea">Crea categoria</button>
        </div>
        <div class="r-msg" id="cMsg"></div>
      </div>
    </section>

    {* ===== SCHEDA ALLERGENI ===== *}
    <section class="r-pane" id="pane-allergeni" style="display:none;">
      <div class="r-card">
        <table class="r-table">
          <thead><tr><th>Nome</th><th class="r-right">Azioni</th></tr></thead>
          <tbody id="allergeniBody"></tbody>
        </table>
        <div class="r-empty" id="allergeniEmpty" style="display:none;">Nessun allergene.</div>
      </div>
      <div class="r-add">
        <div class="r-add-title">+ Aggiungi un allergene</div>
        <div class="r-add-row">
          <div class="r-field"><label>Nome</label><input type="text" id="aNome" placeholder="Es. Glutine"></div>
          <button class="r-btn r-btn-primary" id="aCrea">Crea allergene</button>
        </div>
        <div class="r-msg" id="aMsg"></div>
      </div>
    </section>

  </main>

  {* --- Overlay modifica piatto --- *}
  <div class="r-overlay" id="piattoEditOverlay">
    <div class="r-dialog">
      <div class="r-dialog-head">
        <span class="r-dialog-title">Modifica piatto</span>
        <button class="r-x" id="eClose" aria-label="Chiudi">&times;</button>
      </div>
      <div class="r-dialog-body">
        <input type="hidden" id="ePiattoId">
        <div class="r-add-row">
          <div class="r-field"><label>Nome</label><input type="text" id="eNome"></div>
          <div class="r-field"><label>Prezzo (&euro;)</label><input type="number" step="0.10" id="ePrezzo"></div>
        </div>
        <div class="r-add-row" style="margin-top:10px;">
          <div class="r-field"><label>Categoria</label><select id="eCategoria" class="r-select"></select></div>
          <div class="r-field"><label>Cucina</label><select id="eCucina" class="r-select"></select></div>
        </div>
        <div class="r-add-row" style="margin-top:10px;">
          <div class="r-field" style="flex:2;"><label>Descrizione</label><input type="text" id="eDescrizione"></div>
          <div class="r-field"><label>Etichetta</label><input type="text" id="eEtichetta"></div>
        </div>
        <div class="r-field" style="margin-top:10px;">
          <label>Allergeni</label>
          <div id="eAllergeni" class="r-chips"></div>
        </div>

        <div class="r-field" style="margin-top:14px;">
          <label>Immagine del piatto</label>
          <div class="r-img-area">
            <div class="r-img-preview" id="eImgPreview">
              <span class="r-img-vuota">Nessuna immagine</span>
            </div>
            <div class="r-img-controls">
              <input type="file" id="eImgFile" accept="image/*" style="display:none;">
              <button type="button" class="r-btn r-btn-sm" id="eImgScegli">Scegli immagine…</button>
              <button type="button" class="r-btn r-btn-sm r-btn-danger" id="eImgRimuovi" style="display:none;">Rimuovi</button>
              <div class="r-img-hint">JPG, PNG, GIF o WEBP · max 2 MB</div>
            </div>
          </div>
          <div class="r-msg" id="eImgMsg"></div>
        </div>
      </div>
      <div class="r-dialog-foot">
        <div class="r-dialog-actions">
          <button class="r-btn" id="eAnnulla">Annulla</button>
          <button class="r-btn r-btn-primary" id="eSalva">Salva modifiche</button>
        </div>
      </div>
    </div>
  </div>

</div>
<script src="presentation/assets/ristorante.js"></script>
</body>
</html>
