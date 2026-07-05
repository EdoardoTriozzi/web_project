{*
  Template Smarty — Menu del tavolo (menu/menu.tpl)

  Riceve dal control (CTavolo::mostraMenu) dati gia' pronti come array:
    $nomeRistorante  (stringa)   nome del locale
    $numeroTavolo    (stringa)   numero del tavolo
    $gruppi          (array)     piatti raggruppati per categoria. Ogni elemento:
                                 { catId, catNome, piatti:[ {id,nome,descrizione,
                                   prezzo,immagine,etichetta,allergeni:[],
                                   dataAll}, ... ] }
    $catalogoAllergeni (array di stringhe)  per le checkbox del filtro

  Il JavaScript (tavolo.js) NON cambia: gestisce carrello, overlay, filtro.
*}
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Menu - {$numeroTavolo|escape}</title>
  <link rel="stylesheet" href="presentation/assets/tavolo.css">
</head>
<body>
<div class="app">

  <header class="header">
    <div class="topbar">
      <div class="brand"><span class="dot"></span> {$nomeRistorante|escape}</div>
      <span class="table-label">{$numeroTavolo|escape}</span>
    </div>
    <div class="filters">
      <button class="drop-btn" id="btnCat"><span id="catLabel">Tutte le categorie</span><span class="chev"></span></button>
      <div class="panel" id="panelCat">
        <button class="chip active" data-cat="">Tutte</button>
        {foreach $gruppi as $g}
          {if $g.catId != 'cat-0'}
            <button class="chip" data-cat="{$g.catId}">{$g.catNome|escape}</button>
          {/if}
        {/foreach}
      </div>

      <button class="drop-btn" id="btnAll"><span>Filtra allergeni <span class="badge-mini" id="allCount" style="display:none;"></span></span><span class="chev"></span></button>
      <div class="panel" id="panelAll">
        {foreach $catalogoAllergeni as $nomeAll}
          <label class="all-opt"><input type="checkbox" class="all-chk" value="{$nomeAll|escape}"> {$nomeAll|escape}</label>
        {/foreach}
        <button class="btn" id="resetAll" style="width:100%; margin-top:4px;">Rimuovi filtro</button>
      </div>
    </div>
  </header>

  <main class="menu" id="menuList">
    {foreach $gruppi as $g}
      <div class="cat-title" data-cat="{$g.catId}">{$g.catNome|escape}</div>

      {foreach $g.piatti as $piatto}
        <div class="card" data-cat="{$g.catId}" data-all="{$piatto.dataAll|escape}">
          <div class="photo">
            {if $piatto.immagine}
              <img src="{$piatto.immagine|escape}" alt="{$piatto.nome|escape}" class="foto-zoom" data-img="{$piatto.immagine|escape}" data-nome="{$piatto.nome|escape}" style="width:100%; height:100%; object-fit:cover; cursor:zoom-in;">
            {else}
              FOTO PIATTO
            {/if}
            {if $piatto.etichetta}
              <span class="label">{$piatto.etichetta|escape}</span>
            {/if}
          </div>
          <div class="body">
            <div class="name">{$piatto.nome|escape}</div>
            {if $piatto.descrizione}
              <div class="desc">{$piatto.descrizione|escape}</div>
            {/if}

            <div class="allergens">
              {if $piatto.allergeni|@count > 0}
                <span class="lbl">Allergeni:</span>
                {foreach $piatto.allergeni as $nomeAll}
                  <span class="atag">{$nomeAll|escape}</span>
                {/foreach}
              {else}
                <span class="lbl">Nessun allergene</span>
              {/if}
            </div>

            <div class="prow">
              <span class="price">{$piatto.prezzo} &euro;</span>
              <div class="stepper">
                <button class="step" data-id="{$piatto.id}" data-d="-1">-</button>
                <span class="val" id="q{$piatto.id}">0</span>
                <button class="step" data-id="{$piatto.id}" data-d="1">+</button>
              </div>
            </div>
          </div>
        </div>
      {/foreach}
    {/foreach}
  </main>

  <nav class="actions">
    <button class="act" id="btnStorico"><span class="ic ic-hist"></span> Storico</button>
    <button class="act" id="btnConto"><span class="ic ic-receipt"></span> Richiedi conto</button>
    <button class="act cart" id="btnCart"><span class="ic ic-cart"></span><span>Carrello - <span id="cartTotal">0,00 &euro;</span></span><span class="cart-badge" id="cartBadge">0</span></button>
  </nav>

  {* Overlay immagine ingrandita *}
  <div class="overlay overlay-img" id="overlayImg">
    <button class="img-close close-ov" data-t="overlayImg" aria-label="Chiudi">&times;</button>
    <img id="imgGrande" src="" alt="">
    <div class="img-caption" id="imgCaption"></div>
  </div>

  {* Overlay carrello *}
  <div class="overlay" id="overlayCart"><div class="sheet">
    <div class="sheet-head"><span class="sheet-title">Carrello</span><button class="icon-btn close-ov" data-t="overlayCart" aria-label="Chiudi"></button></div>
    <div id="cartRows" style="flex:1;"></div>
    <div class="empty-cart" id="cartEmpty">Il carrello &egrave; vuoto.</div>
    <div class="total-row"><span style="font-weight:600;">Totale</span><span class="big"><span id="cartTotal2">0,00</span> &euro;</span></div>
    <div class="sheet-actions"><button class="btn" id="svuotaBtn">Svuota</button><button class="btn primary" id="inviaBtn" style="flex:2;">Invia in cucina</button></div>
  </div></div>

  {* Overlay storico *}
  <div class="overlay" id="overlayStorico"><div class="sheet">
    <div class="sheet-head"><span class="sheet-title">Ordini effettuati</span><button class="icon-btn close-ov" data-t="overlayStorico" aria-label="Chiudi"></button></div>
    <div id="storicoRows" style="flex:1;"></div>
    <div class="empty-cart" id="storicoEmpty">Nessun ordine inviato.</div>
    <div class="total-row"><span style="font-weight:600;">Totale ordinato</span><span class="big"><span id="storicoTot">0,00</span> &euro;</span></div>
  </div></div>

  {* Overlay conferma conto *}
  <div class="overlay center" id="overlayConto"><div class="dialog">
    <div class="d-title">Richiesta del conto</div>
    <div class="d-text">Desidera richiedere il conto? Una volta confermata la richiesta non sar&agrave; pi&ugrave; possibile aggiungere altri piatti all'ordine.</div>
    <div class="d-actions"><button class="btn close-ov" data-t="overlayConto" style="flex:1;">Annulla</button><button class="btn primary" id="confermaConto" style="flex:1;">Conferma</button></div>
  </div></div>

  {* Overlay cassa *}
  <div class="overlay center" id="overlayCassa"><div class="dialog">
    <div class="d-title" style="color:#5fd38a;">Conto richiesto</div>
    <div class="d-text">La preghiamo di recarsi alla cassa per saldare il conto. Grazie.</div>
    <button class="btn primary close-ov" data-t="overlayCassa" style="width:100%;">Ho capito</button>
  </div></div>

  {* Overlay ordine inviato *}
  <div class="overlay center" id="overlayInviato"><div class="dialog">
    <div class="d-title" style="color:#5fd38a;">Ordine inviato</div>
    <div class="d-text">Il tuo ordine &egrave; stato inviato in cucina. Lo trovi nello storico.</div>
    <button class="btn primary close-ov" data-t="overlayInviato" style="width:100%;">Ho capito</button>
  </div></div>

</div>

<script src="presentation/assets/tavolo.js"></script>
</body>
</html>
