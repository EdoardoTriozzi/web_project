{*
  Template Smarty — Statistiche del ristorante (ristorante/statistiche.tpl)

  Due statistiche:
   1. Riepilogo incassi (incasso totale, numero pagamenti, scontrino medio)
   2. Classifica piatti piu' venduti (grafico a barre in HTML/CSS puro)

  Il grafico e' fatto con semplici barre CSS: niente librerie, niente
  JavaScript, niente da scaricare. La larghezza di ogni barra (percentuale
  rispetto al piatto piu' venduto) e' gia' calcolata dal control.

  Variabili attese dal control (CRistorante::mostraStatistiche):
    $nomeRistorante  (stringa)
    $incasso         (stringa, gia' formattata)
    $nPagamenti      (intero)
    $scontrinoMedio  (stringa, gia' formattata)
    $haDati          (bool)
    $classifica      (array)  ognuno: { nome, quantita, percentuale }
*}
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Statistiche - {$nomeRistorante|escape}</title>
  <link rel="stylesheet" href="presentation/assets/ristorante.css">
</head>
<body>
<div class="r-wrap">

  <header class="r-top">
    <div class="r-crumbs">
      <a href="index.php?controller=Ristorante&action=mostraHome" class="r-back">&larr; Home</a>
      <span class="r-sep">/</span>
      <span class="r-here">Statistiche</span>
    </div>
    <div class="r-title-side">{$nomeRistorante|escape}</div>
  </header>

  <main class="r-main">

    {* --- Selettore periodo: link che ricaricano la pagina (niente JS) --- *}
    <div class="r-periodi">
      {foreach $periodi as $chiave => $etichetta}
        <a href="index.php?controller=Ristorante&action=mostraStatistiche&periodo={$chiave}"
           class="r-periodo {if $periodo == $chiave}attivo{/if}">{$etichetta}</a>
      {/foreach}
    </div>

    {* --- Riepilogo incassi: tre riquadri --- *}
    <div class="r-sec-label">Riepilogo incassi</div>
    <div class="r-stats-grid">
      <div class="r-stat-box">
        <div class="r-stat-val">{$incasso} &euro;</div>
        <div class="r-stat-lbl">Incasso totale</div>
      </div>
      <div class="r-stat-box">
        <div class="r-stat-val">{$nPagamenti}</div>
        <div class="r-stat-lbl">Pagamenti registrati</div>
      </div>
      <div class="r-stat-box">
        <div class="r-stat-val">{$scontrinoMedio} &euro;</div>
        <div class="r-stat-lbl">Scontrino medio</div>
      </div>
    </div>

    {* --- Classifica piatti piu' venduti: barre CSS --- *}
    <div class="r-sec-label" style="margin-top:24px;">Piatti più venduti</div>
    <div class="r-card" style="padding:20px;">
      {if $haDati}
        <div class="r-chart">
          {foreach $classifica as $piatto}
            <div class="r-bar-row">
              <div class="r-bar-name">{$piatto.nome|escape}</div>
              <div class="r-bar-track">
                <div class="r-bar-fill" style="width:{$piatto.percentuale}%;"></div>
              </div>
              <div class="r-bar-val">{$piatto.quantita}</div>
            </div>
          {/foreach}
        </div>
      {else}
        <div class="r-empty">Ancora nessun dato. Le statistiche compaiono dopo i primi pagamenti.</div>
      {/if}
    </div>

  </main>

</div>
</body>
</html>
