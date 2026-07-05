{*
  Template Smarty — Conti dei tavoli (ristorante/conti.tpl)

  Due sezioni:
    1. Richieste di conto in attesa (i tavoli che hanno chiesto il conto)
    2. Tutti i tavoli (panoramica con speso finora)
  Piu' un overlay per il dettaglio/correzione del conto di un tavolo.

  Variabili attese dal control (CRistorante::mostraConti):
    $nomeRistorante (stringa)
    $tavoli (array)  ognuno: { id, numero, stato, coperti, contoRichiesto, speso, spesoNum }
*}
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Conti - {$nomeRistorante|escape}</title>
  <link rel="stylesheet" href="presentation/assets/ristorante.css">
</head>
<body>
<div class="r-wrap" id="contiWrap">

  <header class="r-top">
    <div class="r-crumbs">
      <a href="index.php?controller=Ristorante&action=mostraHome" class="r-back">&larr; Home</a>
      <span class="r-sep">/</span>
      <span class="r-here">Conti dei tavoli</span>
    </div>
    <div class="r-title-side">{$nomeRistorante|escape}</div>
  </header>

  <main class="r-main">

    {* --- Sezione 1: richieste di conto in attesa --- *}
    {assign var=inAttesa value=0}
    {foreach $tavoli as $t}{if $t.contoRichiesto}{assign var=inAttesa value=$inAttesa+1}{/if}{/foreach}

    {if $inAttesa > 0}
      <div class="r-sec-label">Richieste di conto in attesa</div>
      <div class="r-card r-card-alert" style="margin-bottom:22px;">
        <table class="r-table">
          <tbody>
            {foreach $tavoli as $t}
              {if $t.contoRichiesto}
                <tr>
                  <td class="r-num">Tavolo {$t.numero|escape}
                    <span class="r-badge r-badge-conto" style="margin-left:8px;">Conto richiesto</span>
                  </td>
                  <td>{if $t.coperti > 0}{$t.coperti} coperti{else}&mdash;{/if}</td>
                  <td style="font-weight:600; font-size:15px;">{$t.speso} &euro;</td>
                  <td class="r-actions-cell">
                    <button class="r-btn r-btn-sm" data-az="modifica" data-id="{$t.id}">Modifica</button>
                    <button class="r-btn r-btn-sm r-btn-pay" data-az="paga" data-id="{$t.id}">&#10003; Segna pagato</button>
                  </td>
                </tr>
              {/if}
            {/foreach}
          </tbody>
        </table>
      </div>
    {/if}

    {* --- Sezione 2: tutti i tavoli --- *}
    <div class="r-sec-label">Tutti i tavoli</div>
    <div class="r-card">
      <table class="r-table">
        <thead>
          <tr>
            <th>Tavolo</th><th>Stato</th><th>Speso finora</th><th>Conto</th><th class="r-right">Azioni</th>
          </tr>
        </thead>
        <tbody>
          {foreach $tavoli as $t}
            <tr {if $t.contoRichiesto}class="r-row-alert"{/if}>
              <td class="r-num">Tavolo {$t.numero|escape}</td>
              <td>
                {if $t.stato == 'libero'}
                  <span class="r-badge r-badge-libero">Libero</span>
                {else}
                  <span class="r-badge r-badge-occupato">Occupato</span>
                {/if}
              </td>
              <td>{if $t.spesoNum > 0}<span style="font-weight:500;">{$t.speso} &euro;</span>{else}<span class="r-muted">&mdash;</span>{/if}</td>
              <td>{if $t.contoRichiesto}<span style="color:var(--danger); font-weight:500;">Richiesto</span>{else}<span class="r-muted">&mdash;</span>{/if}</td>
              <td class="r-actions-cell">
                {if $t.spesoNum > 0 || $t.contoRichiesto}
                  <button class="r-btn r-btn-sm" data-az="modifica" data-id="{$t.id}">Modifica / Paga</button>
                {else}
                  <span class="r-muted">&mdash;</span>
                {/if}
              </td>
            </tr>
          {/foreach}
        </tbody>
      </table>
    </div>

  </main>

  {* --- Overlay dettaglio/correzione conto --- *}
  <div class="r-overlay" id="contoOverlay">
    <div class="r-dialog">
      <div class="r-dialog-head">
        <span class="r-dialog-title" id="contoTitolo">Conto tavolo</span>
        <button class="r-x" id="contoClose" aria-label="Chiudi">&times;</button>
      </div>

      <div class="r-dialog-body">
        <div class="r-voci-head">
          <span>Voce</span><span>Prezzo</span><span>Q.tà</span><span></span>
        </div>
        <div id="contoVoci"><!-- righe dal JS --></div>

        <div class="r-add-voce">
          <select id="contoPiattoSel" class="r-select"><!-- opzioni dal JS --></select>
          <button class="r-btn r-btn-sm" id="contoAddBtn">+ Aggiungi</button>
        </div>
      </div>

      <div class="r-dialog-foot">
        <div class="r-tot-line">Totale da pagare <span class="r-tot-val" id="contoTotale">0,00 &euro;</span></div>
        <div class="r-dialog-actions">
          <button class="r-btn" id="contoAnnulla">Chiudi</button>
          <button class="r-btn r-btn-pay" id="contoPaga">&#10003; Conferma pagamento</button>
        </div>
      </div>
    </div>
  </div>

</div>
<script src="presentation/assets/ristorante.js"></script>
</body>
</html>
