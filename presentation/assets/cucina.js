/*
 * cucina.js — comportamento delle pagine della cucina.
 *
 * Due pagine usano questo stesso file:
 *  - ORDINI: tabellone a colonne. Disegna le schede dai dati, fa polling
 *    ogni 10 secondi per aggiornarsi da solo quando arrivano ordini nuovi,
 *    gestisce "prendi in carico" / "segna consegnato" / "pulisci consegnati".
 *  - DISPONIBILITA: interruttori acceso/spento per ogni piatto.
 *
 * Il file capisce da solo su quale pagina si trova guardando quali elementi
 * esistono nel documento.
 */
(function () {
  'use strict';

  // chiamaServer: manda una richiesta a CCucina e restituisce il JSON.
  async function chiamaServer(action, dati) {
    var corpo = new URLSearchParams();
    corpo.set('controller', 'Cucina');
    corpo.set('action', action);
    for (var k in dati) { corpo.set(k, dati[k]); }
    var risposta = await fetch('index.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: corpo.toString()
    });
    return await risposta.json();
  }

  // =================================================================
  //  PAGINA ORDINI (esiste l'elemento .kitchen con le colonne)
  // =================================================================
  var board = document.getElementById('colNuovi');
  if (board) {
    // ordini consegnati che l'utente ha scelto di nascondere (solo lato browser:
    // restano nel database, spariscono solo dalla vista finche' non si ricarica).
    var consegnatiNascosti = {};
    // righe evidenziate in rosso dall'utente: chiave "ordineId:piattoId".
    // Vive solo nel browser (sparisce a ricarica pagina), ma sopravvive ai
    // ridisegni del polling, cosi' il rosso non si cancella ogni 10 secondi.
    var righeRosse = {};
    // tavoli attualmente selezionati nel filtro (vuoto = mostra tutti i tavoli)
    var tavoliSelezionati = {};
    var ultimiDati = { attivi: [], consegnati: [] };

    // costruisce/aggiorna l'elenco di caselle dei tavoli che hanno ordini ora
    function aggiornaElencoTavoliFiltro(dati) {
      var box = document.getElementById('filtroTavoliLista');
      if (!box) return;
      // raccoglie i numeri di tavolo presenti negli ordini (attivi + consegnati visibili)
      var tavoli = {};
      (dati.attivi || []).forEach(function (o) { tavoli[String(o.tavolo)] = true; });
      (dati.consegnati || []).forEach(function (o) { if (!consegnatiNascosti[o.id]) tavoli[String(o.tavolo)] = true; });
      var numeri = Object.keys(tavoli).sort(function (a, b) { return a.localeCompare(b, undefined, {numeric:true}); });

      box.innerHTML = '';
      if (numeri.length === 0) { box.innerHTML = '<div class="k-filtro-empty">Nessun tavolo con ordini.</div>'; return; }
      numeri.forEach(function (n) {
        var checked = tavoliSelezionati[n] ? 'checked' : '';
        var lab = document.createElement('label');
        lab.className = 'k-filtro-opt';
        lab.innerHTML = '<input type="checkbox" value="' + n + '" ' + checked + '> Tavolo ' + n;
        lab.querySelector('input').addEventListener('change', function (e) {
          if (e.target.checked) tavoliSelezionati[n] = true; else delete tavoliSelezionati[n];
          disegnaOrdini(ultimiDati);   // ridisegna applicando il nuovo filtro
        });
        box.appendChild(lab);
      });
    }

    // true se un ordine va mostrato in base al filtro tavoli
    function passaFiltroTavolo(o) {
      // se nessun tavolo e' spuntato, mostra tutto
      var almenoUno = Object.keys(tavoliSelezionati).some(function (k) { return tavoliSelezionati[k]; });
      if (!almenoUno) return true;
      return tavoliSelezionati[String(o.tavolo)] === true;
    }

    function disegnaOrdini(dati) {
      ultimiDati = dati;  // ricordo gli ultimi dati per ridisegnare quando cambia il filtro
      var colNuovi = document.getElementById('colNuovi');
      var colPrep  = document.getElementById('colPrep');
      var colDone  = document.getElementById('colDone');
      colNuovi.innerHTML = ''; colPrep.innerHTML = ''; colDone.innerHTML = '';

      var nNuovi = 0, nPrep = 0, nDone = 0;

      // attivi = INVIATO (nuovi) + IN_PREPARAZIONE (in preparazione)
      (dati.attivi || []).forEach(function (o) {
        if (!passaFiltroTavolo(o)) return;
        if (o.stato === 'inviato') { colNuovi.appendChild(schedaOrdine(o, 'nuovo')); nNuovi++; }
        else if (o.stato === 'in_preparazione') { colPrep.appendChild(schedaOrdine(o, 'prep')); nPrep++; }
      });

      // consegnati (esclusi quelli nascosti e quelli fuori dal filtro)
      (dati.consegnati || []).forEach(function (o) {
        if (consegnatiNascosti[o.id]) return;
        if (!passaFiltroTavolo(o)) return;
        colDone.appendChild(schedaOrdine(o, 'done')); nDone++;
      });

      if (nNuovi === 0) colNuovi.innerHTML = '<div class="k-empty-col">Nessun ordine nuovo.</div>';
      if (nPrep === 0)  colPrep.innerHTML  = '<div class="k-empty-col">Niente in preparazione.</div>';
      if (nDone === 0)  colDone.innerHTML  = '<div class="k-empty-col">Nessun ordine consegnato.</div>';

      document.getElementById('countNuovi').textContent = nNuovi;
      document.getElementById('countPrep').textContent = nPrep;
      document.getElementById('countDone').textContent = nDone;

      aggiornaElencoTavoliFiltro(dati);

      var now = new Date();
      document.getElementById('kUpdated').textContent =
        'Aggiornato ' + ('0'+now.getHours()).slice(-2) + ':' + ('0'+now.getMinutes()).slice(-2) + ':' + ('0'+now.getSeconds()).slice(-2);
    }

    function schedaOrdine(o, tipo) {
      var card = document.createElement('div');
      card.className = 'k-card' + (tipo === 'done' ? ' k-card-done' : '');

      var righe = o.righe.map(function (r) {
        var base = r.mia ? 'k-line k-line-mia' : 'k-line k-line-altrui';
        var chiave = o.id + ':' + r.piattoId;
        // se questa riga era stata segnata in rosso, nasce gia' rossa
        var classe = base + (righeRosse[chiave] ? ' k-line-rosso' : '');
        return '<div class="' + classe + '" data-ordine="' + o.id + '" data-piatto="' + r.piattoId + '">' +
                 '<span class="k-line-nome">' + r.nome + '</span>' +
                 '<span class="k-line-right">' +
                   '<span class="q">x' + r.quantita + '</span>' +
                   '<button class="k-line-flag" title="Evidenzia in rosso" aria-label="Evidenzia in rosso"></button>' +
                 '</span>' +
               '</div>';
      }).join('');

      var pulsante = '';
      if (tipo === 'nuovo') {
        pulsante = '<button class="k-card-btn k-card-btn-take" data-azione="prendi" data-id="' + o.id + '">Prendi in carico</button>';
      } else if (tipo === 'prep') {
        pulsante = '<button class="k-card-btn k-card-btn-done" data-azione="consegna" data-id="' + o.id + '">Segna consegnato</button>';
      }

      card.innerHTML =
        '<div class="k-card-head"><span class="k-card-table">Tavolo ' + o.tavolo + '</span>' +
        (o.ora ? '<span class="k-card-time">arrivato alle ' + o.ora + '</span>' : '') +
        '</div>' +
        '<div class="k-lines">' + righe + '</div>' +
        pulsante;

      // pulsantini "rosso": al clic la riga si colora di rosso (e si toglie ricliccando).
      // Aggiorniamo anche la memoria righeRosse, cosi' il polling non cancella il rosso.
      card.querySelectorAll('.k-line-flag').forEach(function (f) {
        f.addEventListener('click', function (ev) {
          ev.stopPropagation();
          var riga = f.closest('.k-line');
          var attiva = riga.classList.toggle('k-line-rosso');
          var chiave = riga.getAttribute('data-ordine') + ':' + riga.getAttribute('data-piatto');
          if (attiva) righeRosse[chiave] = true; else delete righeRosse[chiave];
        });
      });

      // collega il pulsante d'azione (se c'e')
      var btn = card.querySelector('.k-card-btn');
      if (btn) {
        btn.addEventListener('click', function () {
          var azione = btn.getAttribute('data-azione');
          var id = btn.getAttribute('data-id');
          if (azione === 'prendi') eseguiAzione('prendiInCarico', id);
          else if (azione === 'consegna') eseguiAzione('segnaConsegnato', id);
        });
      }
      return card;
    }

    async function eseguiAzione(action, ordineId) {
      var res = await chiamaServer(action, { ordineId: ordineId });
      if (res.ok) { disegnaOrdini(res); }
      else { alert(res.errore || 'Operazione non riuscita.'); aggiorna(); }
    }

    // polling: chiede gli ordini aggiornati al server
    async function aggiorna() {
      try {
        var res = await chiamaServer('mostraOrdini', { formato: 'json' });
        if (res.ok) disegnaOrdini(res);
      } catch (e) { /* se il server non risponde, riprova al giro dopo */ }
    }

    // "pulisci consegnati": nasconde gli ordini consegnati attualmente a schermo
    document.getElementById('btnPulisci').addEventListener('click', function () {
      document.querySelectorAll('#colDone .k-card').forEach(function () {});
      // segna come nascosti tutti i consegnati presenti ora
      // (li rileggiamo dai dati correnti tramite un refresh mirato)
      chiamaServer('mostraOrdini', { formato: 'json' }).then(function (res) {
        if (res.ok) {
          (res.consegnati || []).forEach(function (o) { consegnatiNascosti[o.id] = true; });
          disegnaOrdini(res);
        }
      });
    });

    // apri/chiudi il pannello del filtro tavoli
    var btnFiltro = document.getElementById('btnFiltroTavoli');
    if (btnFiltro) {
      btnFiltro.addEventListener('click', function () {
        var p = document.getElementById('filtroTavoliPanel');
        p.classList.toggle('show');
      });
    }
    var btnReset = document.getElementById('btnResetFiltro');
    if (btnReset) {
      btnReset.addEventListener('click', function () {
        tavoliSelezionati = {};
        document.querySelectorAll('#filtroTavoliLista input').forEach(function (c) { c.checked = false; });
        disegnaOrdini(ultimiDati);
      });
    }

    // dati iniziali (passati dalla view), poi avvio del polling ogni 10 secondi
    var grezzi = document.querySelector('.kitchen').getAttribute('data-iniziali');
    try { disegnaOrdini(JSON.parse(grezzi)); } catch (e) { aggiorna(); }
    setInterval(aggiorna, 10000);  // 10 secondi
  }

  // =================================================================
  //  PAGINA DISPONIBILITA (esistono gli interruttori .k-switch)
  // =================================================================
  var switches = document.querySelectorAll('.k-switch');
  if (switches.length > 0) {
    switches.forEach(function (sw) {
      sw.addEventListener('click', async function () {
        var card = sw.closest('.k-dish');
        var id = card.getAttribute('data-id');
        var eraOn = sw.classList.contains('on');
        var action = eraOn ? 'disabilitaPiatto' : 'abilitaPiatto';

        var res = await chiamaServer(action, { piattoId: id });
        if (!res.ok) { alert(res.errore || 'Operazione non riuscita.'); return; }

        // aggiorna l'aspetto dell'interruttore e dello stato in base alla risposta
        var nuovo = (res.piatti || []).find(function (p) { return String(p.id) === String(id); });
        var disp = nuovo ? nuovo.disponibile : !eraOn;
        sw.classList.toggle('on', disp);
        sw.classList.toggle('off', !disp);
        sw.setAttribute('aria-checked', disp ? 'true' : 'false');
        card.classList.toggle('k-dish-off', !disp);
        card.querySelector('.k-state-text').textContent = disp ? 'Disponibile' : 'Non disponibile';
      });
    });
  }

})();
