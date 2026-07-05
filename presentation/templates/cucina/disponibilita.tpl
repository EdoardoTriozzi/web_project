{*
  Template Smarty — Disponibilita piatti cucina (cucina/disponibilita.tpl)

  Lista dei piatti della cucina con interruttore acceso/spento per ciascuno.
  Gli interruttori sono gestiti da cucina.js (invariato).

  Variabili attese dal control (CCucina::mostraPiatti):
    $nomeCucina  (stringa)
    $piatti      (array)  ognuno: { id, nome, disponibile (bool) }
*}
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cucina - Disponibilità piatti</title>
  <link rel="stylesheet" href="presentation/assets/cucina.css">
</head>
<body>
<div class="kitchen">

  <header class="k-top">
    <div class="k-brand">
      <span class="k-dot"></span>
      <div>
        <div class="k-title">Disponibilità piatti</div>
        <div class="k-sub">{$nomeCucina|escape} &middot; spegni i piatti che non puoi preparare</div>
      </div>
    </div>
    <div class="k-actions">
      <a class="k-btn" href="index.php?controller=Cucina&action=mostraOrdini">Torna agli ordini</a>
      <a class="k-btn k-btn-ghost" href="index.php?controller=Login&action=logout">Esci</a>
    </div>
  </header>

  <main class="k-dishes">
    {if $piatti|@count == 0}
      <div class="k-empty">Nessun piatto assegnato a questa cucina.</div>
    {else}
      {foreach $piatti as $piatto}
        <div class="k-dish {if !$piatto.disponibile}k-dish-off{/if}" data-id="{$piatto.id}">
          <div class="k-dish-info">
            <div class="k-dish-name">{$piatto.nome|escape}</div>
            <div class="k-dish-state">
              <span class="k-state-dot"></span>
              <span class="k-state-text">{if $piatto.disponibile}Disponibile{else}Non disponibile{/if}</span>
            </div>
          </div>
          <button class="k-switch {if $piatto.disponibile}on{else}off{/if}" role="switch"
                  aria-checked="{if $piatto.disponibile}true{else}false{/if}"
                  aria-label="Disponibilità {$piatto.nome|escape}">
            <span class="k-switch-knob"></span>
          </button>
        </div>
      {/foreach}
    {/if}
  </main>

</div>
<script src="presentation/assets/cucina.js"></script>
</body>
</html>
