/*
 * ristorante.js — comportamento del pannello di gestione del ristorante.
 *
 * Per ora gestisce la pagina TAVOLI: disegna la tabella dei tavoli dai dati,
 * e gestisce le azioni (crea, elimina, cambia stato, rigenera password)
 * chiamando il control CRistorante via fetch e ridisegnando la tabella.
 *
 * Quando aggiungeremo le altre sezioni (cucine, menu, ecc.), questo file
 * capira' su quale pagina si trova guardando quali elementi esistono.
 */
(function () {
  'use strict';

  function h(s) {
    return String(s).replace(/[&<>"]/g, function (c) {
      return { '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;' }[c];
    });
  }

  // chiamaServer: invia un'azione a CRistorante e restituisce il JSON.
  async function chiamaServer(action, dati) {
    var corpo = new URLSearchParams();
    corpo.set('controller', 'Ristorante');
    corpo.set('action', action);
    for (var k in dati) { corpo.set(k, dati[k]); }
    var risposta = await fetch('index.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: corpo.toString()
    });
    return await risposta.json();
  }

  // genera una password breve e leggibile per un tavolo
  function generaPassword() {
    var car = 'abcdefghijkmnpqrstuvwxyz23456789';
    var s = '';
    for (var i = 0; i < 6; i++) s += car.charAt(Math.floor(Math.random() * car.length));
    return s;
  }

  // =================================================================
  //  PAGINA TAVOLI (esiste il corpo tabella #tavoliBody)
  // =================================================================
  var body = document.getElementById('tavoliBody');
  if (body) {

    function disegnaTavoli(tavoli) {
      body.innerHTML = '';
      var empty = document.getElementById('tavoliEmpty');
      if (!tavoli || tavoli.length === 0) { empty.style.display = 'block'; return; }
      empty.style.display = 'none';

      tavoli.forEach(function (t) {
        var tr = document.createElement('tr');

        var badgeStato = t.stato === 'libero'
          ? '<span class="r-badge r-badge-libero">Libero</span>'
          : '<span class="r-badge r-badge-occupato">Occupato</span>';

        var coperti = t.coperti > 0 ? t.coperti : '<span class="r-muted">&mdash;</span>';
        var pwd = t.haPassword ? '&#10003;' : '<span class="r-muted">&mdash;</span>';
        var conto = t.contoRichiesto
          ? '<span class="r-badge r-badge-conto">Richiesto</span>'
          : '<span class="r-muted">&mdash;</span>';

        tr.innerHTML =
          '<td class="r-num">Tavolo ' + h(t.numero) + '</td>' +
          '<td>' + badgeStato + '</td>' +
          '<td>' + coperti + '</td>' +
          '<td>' + pwd + '</td>' +
          '<td>' + conto + '</td>' +
          '<td class="r-actions-cell">' +
            '<button class="r-btn r-btn-sm" data-az="stato" data-id="' + t.id + '" data-stato="' + t.stato + '">Stato</button>' +
            '<button class="r-btn r-btn-sm" data-az="pwd-imposta" data-id="' + t.id + '">Imposta pwd</button>' +
            '<button class="r-btn r-btn-sm" data-az="pwd-genera" data-id="' + t.id + '">Genera pwd</button>' +
            '<button class="r-btn r-btn-sm r-btn-danger" data-az="elimina" data-id="' + t.id + '" data-num="' + h(t.numero) + '">&#128465;</button>' +
          '</td>';

        // collega le azioni della riga
        tr.querySelector('[data-az="stato"]').addEventListener('click', function () {
          var nuovo = t.stato === 'libero' ? 'occupato' : 'libero';
          azione('impostaStatoTavolo', { tavoloId: t.id, stato: nuovo });
        });
        tr.querySelector('[data-az="pwd-genera"]').addEventListener('click', function () {
          var nuova = generaPassword();
          azione('generaPasswordTavolo', { tavoloId: t.id, password: nuova });
        });
        tr.querySelector('[data-az="pwd-imposta"]').addEventListener('click', function () {
          var scelta = prompt('Imposta la password per il tavolo ' + t.numero + ':');
          if (scelta === null) return;            // annullato
          scelta = scelta.trim();
          if (scelta === '') { alert('La password non puo\' essere vuota.'); return; }
          azione('generaPasswordTavolo', { tavoloId: t.id, password: scelta });
        });
        tr.querySelector('[data-az="elimina"]').addEventListener('click', function () {
          if (confirm('Eliminare il tavolo ' + t.numero + '? L\'operazione non si puo\' annullare.')) {
            azione('eliminaTavolo', { tavoloId: t.id });
          }
        });

        body.appendChild(tr);
      });
    }

    // esegue un'azione e ridisegna; se torna una password, la mostra
    async function azione(action, dati) {
      var res = await chiamaServer(action, dati);
      if (!res.ok) { alert(res.errore || 'Operazione non riuscita.'); return; }
      disegnaTavoli(res.tavoli);
      if (res.passwordImpostata) {
        mostraMessaggio('Password del tavolo aggiornata: ' + res.passwordImpostata, true);
      }
    }

    function mostraMessaggio(testo, ok) {
      var m = document.getElementById('creaMsg');
      m.textContent = testo;
      m.className = 'r-msg ' + (ok ? 'r-msg-ok' : 'r-msg-err');
    }

    // crea un nuovo tavolo
    document.getElementById('btnCrea').addEventListener('click', async function () {
      var numero = document.getElementById('nuovoNumero').value.trim();
      var coperti = document.getElementById('nuovoCoperti').value.trim();
      var password = document.getElementById('nuovaPassword').value.trim();
      if (numero === '') { mostraMessaggio('Inserisci il numero del tavolo.', false); return; }

      var res = await chiamaServer('creaTavolo', { numero: numero, coperti: coperti || 0, password: password });
      if (!res.ok) { mostraMessaggio(res.errore || 'Errore nella creazione.', false); return; }
      disegnaTavoli(res.tavoli);
      document.getElementById('nuovoNumero').value = '';
      document.getElementById('nuovoCoperti').value = '';
      document.getElementById('nuovaPassword').value = '';
      mostraMessaggio('Tavolo ' + numero + ' creato.', true);
    });

    // dati iniziali passati dalla view
    try {
      var grezzi = document.querySelector('.r-wrap').getAttribute('data-tavoli');
      disegnaTavoli(JSON.parse(grezzi));
    } catch (e) { disegnaTavoli([]); }
  }

  // =================================================================
  //  PAGINA CUCINE (esiste il corpo tabella #cucineBody)
  // =================================================================
  var bodyCucine = document.getElementById('cucineBody');
  if (bodyCucine) {

    function disegnaCucine(cucine) {
      bodyCucine.innerHTML = '';
      var empty = document.getElementById('cucineEmpty');
      if (!cucine || cucine.length === 0) { empty.style.display = 'block'; return; }
      empty.style.display = 'none';

      cucine.forEach(function (c) {
        var tr = document.createElement('tr');
        tr.innerHTML =
          '<td class="r-num">' + h(c.nome) + '</td>' +
          '<td class="r-muted">' + h(c.username || '') + '</td>' +
          '<td class="r-actions-cell">' +
            '<button class="r-btn r-btn-sm" data-az="cuc-pwd-imposta" data-id="' + c.id + '" data-nome="' + h(c.nome) + '">Imposta pwd</button>' +
            '<button class="r-btn r-btn-sm" data-az="cuc-pwd-genera" data-id="' + c.id + '">Genera pwd</button>' +
            '<button class="r-btn r-btn-sm r-btn-danger" data-id="' + c.id + '" data-nome="' + h(c.nome) + '" data-az="cuc-elimina">&#128465;</button>' +
          '</td>';
        // azioni della riga cucina
        tr.querySelector('[data-az="cuc-elimina"]').addEventListener('click', function () {
          if (confirm('Eliminare la cucina "' + c.nome + '"? L\'operazione non si puo\' annullare.')) {
            azioneCucina('eliminaCucina', { cucinaId: c.id });
          }
        });
        tr.querySelector('[data-az="cuc-pwd-genera"]').addEventListener('click', function () {
          var nuova = generaPassword();
          azioneCucinaPwd('impostaPasswordCucina', { cucinaId: c.id, password: nuova });
        });
        tr.querySelector('[data-az="cuc-pwd-imposta"]').addEventListener('click', function () {
          var scelta = prompt('Imposta la password per la cucina "' + c.nome + '":');
          if (scelta === null) return;
          scelta = scelta.trim();
          if (scelta === '') { alert('La password non puo\' essere vuota.'); return; }
          azioneCucinaPwd('impostaPasswordCucina', { cucinaId: c.id, password: scelta });
        });
        bodyCucine.appendChild(tr);
      });
    }

    async function azioneCucina(action, dati) {
      var res = await chiamaServer(action, dati);
      if (!res.ok) { alert(res.errore || 'Operazione non riuscita.'); return; }
      disegnaCucine(res.cucine);
    }

    // come azioneCucina, ma mostra la password impostata/generata
    async function azioneCucinaPwd(action, dati) {
      var res = await chiamaServer(action, dati);
      if (!res.ok) { alert(res.errore || 'Operazione non riuscita.'); return; }
      disegnaCucine(res.cucine);
      if (res.passwordImpostata) {
        msgCucina('Password della cucina aggiornata: ' + res.passwordImpostata, true);
      }
    }

    function msgCucina(testo, ok) {
      var m = document.getElementById('creaCucinaMsg');
      m.textContent = testo;
      m.className = 'r-msg r-msg-box ' + (ok ? 'r-msg-ok' : 'r-msg-err');
      // il messaggio di successo sparisce da solo dopo qualche secondo
      if (ok) {
        clearTimeout(m._t);
        m._t = setTimeout(function () { m.textContent = ''; m.className = 'r-msg'; }, 4000);
      }
    }

    document.getElementById('btnCreaCucina').addEventListener('click', async function () {
      var nome = document.getElementById('nuovoNome').value.trim();
      var username = document.getElementById('nuovoUsername').value.trim();
      var password = document.getElementById('nuovaPasswordCucina').value.trim();
      if (nome === '' || username === '' || password === '') {
        msgCucina('Nome, username e password sono obbligatori.', false); return;
      }
      var res = await chiamaServer('creaCucina', { nome: nome, username: username, password: password });
      if (!res.ok) { msgCucina(res.errore || 'Errore nella creazione.', false); return; }
      disegnaCucine(res.cucine);
      document.getElementById('nuovoNome').value = '';
      document.getElementById('nuovoUsername').value = '';
      document.getElementById('nuovaPasswordCucina').value = '';
      msgCucina('Cucina "' + nome + '" creata.', true);
    });

    try {
      var grezziC = document.querySelector('.r-wrap').getAttribute('data-cucine');
      disegnaCucine(JSON.parse(grezziC));
    } catch (e) { disegnaCucine([]); }
  }

  // =================================================================
  //  PAGINA CONTI (esiste #contiWrap)
  // =================================================================
  var contiWrap = document.getElementById('contiWrap');
  if (contiWrap) {
    var vociCorrenti = [];      // voci del conto in correzione
    var catalogoPiatti = [];    // piatti del menu per aggiungere
    var tavoloCorrente = null;

    var overlay = document.getElementById('contoOverlay');

    function apriConto(tavoloId) {
      chiamaServer('dettaglioConto', { tavoloId: tavoloId }).then(function (res) {
        if (!res.ok) { alert(res.errore || 'Errore.'); return; }
        tavoloCorrente = tavoloId;
        vociCorrenti = res.voci.map(function (v) {
          return { piattoId: v.piattoId, nome: v.nome, quantita: v.quantita, prezzo: v.prezzo };
        });
        catalogoPiatti = res.catalogo;
        document.getElementById('contoTitolo').textContent = 'Conto tavolo ' + res.numero;
        riempiSelect();
        disegnaVoci();
        overlay.classList.add('open');
      });
    }

    function riempiSelect() {
      var sel = document.getElementById('contoPiattoSel');
      sel.innerHTML = '';
      catalogoPiatti.forEach(function (p) {
        var o = document.createElement('option');
        o.value = p.id;
        o.textContent = p.nome + ' (' + euro(p.prezzo) + ')';
        sel.appendChild(o);
      });
    }

    function disegnaVoci() {
      var c = document.getElementById('contoVoci');
      c.innerHTML = '';
      vociCorrenti.forEach(function (v, idx) {
        var riga = document.createElement('div');
        riga.className = 'r-voce-row';
        riga.innerHTML =
          '<span class="r-voce-nome">' + h(v.nome) + '</span>' +
          '<span class="r-voce-prezzo">' + euro(v.prezzo) + '</span>' +
          '<span class="r-voce-qta">' +
            '<button class="r-qta-btn" data-d="-1">-</button>' +
            '<span class="r-qta-val">' + v.quantita + '</span>' +
            '<button class="r-qta-btn" data-d="1">+</button>' +
          '</span>' +
          '<button class="r-voce-del" aria-label="Rimuovi">&#128465;</button>';
        var btns = riga.querySelectorAll('.r-qta-btn');
        btns[0].addEventListener('click', function () { cambiaQta(idx, -1); });
        btns[1].addEventListener('click', function () { cambiaQta(idx, 1); });
        riga.querySelector('.r-voce-del').addEventListener('click', function () { rimuoviVoce(idx); });
        c.appendChild(riga);
      });
      aggiornaTotale();
    }

    function cambiaQta(idx, d) {
      vociCorrenti[idx].quantita += d;
      if (vociCorrenti[idx].quantita <= 0) { vociCorrenti.splice(idx, 1); }
      disegnaVoci();
    }
    function rimuoviVoce(idx) { vociCorrenti.splice(idx, 1); disegnaVoci(); }

    document.getElementById('contoAddBtn').addEventListener('click', function () {
      var sel = document.getElementById('contoPiattoSel');
      var id = parseInt(sel.value, 10);
      var p = catalogoPiatti.find(function (x) { return x.id === id; });
      if (!p) return;
      var esistente = vociCorrenti.find(function (v) { return v.piattoId === id; });
      if (esistente) { esistente.quantita += 1; }
      else { vociCorrenti.push({ piattoId: p.id, nome: p.nome, quantita: 1, prezzo: p.prezzo }); }
      disegnaVoci();
    });

    function aggiornaTotale() {
      var t = vociCorrenti.reduce(function (s, v) { return s + v.prezzo * v.quantita; }, 0);
      document.getElementById('contoTotale').textContent = euro(t);
    }

    function euro(n) { return n.toFixed(2).replace('.', ',') + ' \u20AC'; }

    function chiudiOverlay() { overlay.classList.remove('open'); }
    document.getElementById('contoClose').addEventListener('click', chiudiOverlay);
    document.getElementById('contoAnnulla').addEventListener('click', chiudiOverlay);

    // conferma pagamento
    document.getElementById('contoPaga').addEventListener('click', function () {
      if (vociCorrenti.length === 0) { alert('Non ci sono voci da pagare.'); return; }
      if (!confirm('Confermare il pagamento? Gli ordini del tavolo verranno archiviati e il tavolo tornera\' libero.')) return;
      chiamaServer('confermaPagamento', {
        tavoloId: tavoloCorrente,
        voci: JSON.stringify(vociCorrenti)
      }).then(function (res) {
        if (!res.ok) { alert(res.errore || 'Errore nel pagamento.'); return; }
        chiudiOverlay();
        alert('Pagamento registrato: ' + res.totale + ' \u20AC. Il tavolo ' + res.numero + ' e\' ora libero.');
        location.reload();
      });
    });

    // pulsanti nelle tabelle (modifica / paga diretto)
    contiWrap.addEventListener('click', function (e) {
      var btn = e.target.closest('[data-az]');
      if (!btn) return;
      var az = btn.getAttribute('data-az');
      var id = parseInt(btn.getAttribute('data-id'), 10);
      if (az === 'modifica' || az === 'paga') { apriConto(id); }
    });
  }

  // =================================================================
  //  PAGINA MENU (esiste #menuWrap) — 3 schede: piatti, categorie, allergeni
  // =================================================================
  var menuWrap = document.getElementById('menuWrap');
  if (menuWrap) {
    var dati = { piatti: [], categorie: [], allergeni: [] };
    try { dati = JSON.parse(menuWrap.getAttribute('data-menu')); } catch (e) {}

    var cucineMenu = (dati.cucine || []);

    // popola select cucine nel form piatti
    (function () {
      var sel = document.getElementById('pCucina');
      if (sel) {
        sel.innerHTML = '<option value="0">— nessuna —</option>';
        cucineMenu.forEach(function (c) { sel.innerHTML += '<option value="' + c.id + '">' + h(c.nome) + '</option>'; });
      }
    })();

    // --- cambio scheda ---
    menuWrap.querySelectorAll('.r-tab').forEach(function (tab) {
      tab.addEventListener('click', function () {
        menuWrap.querySelectorAll('.r-tab').forEach(function (t) { t.classList.remove('active'); });
        tab.classList.add('active');
        var nome = tab.getAttribute('data-tab');
        ['piatti', 'categorie', 'allergeni'].forEach(function (p) {
          document.getElementById('pane-' + p).style.display = (p === nome) ? '' : 'none';
        });
      });
    });

    function euroM(n) { return Number(n).toFixed(2).replace('.', ',') + ' \u20AC'; }

    // ---------- CATEGORIE ----------
    function disegnaCategorie() {
      var body = document.getElementById('categorieBody');
      body.innerHTML = '';
      document.getElementById('categorieEmpty').style.display = dati.categorie.length ? 'none' : 'block';
      dati.categorie.forEach(function (c) {
        var tr = document.createElement('tr');
        tr.innerHTML =
          '<td class="r-num">' + h(c.nome) + '</td>' +
          '<td>' + c.posizione + '</td>' +
          '<td class="r-actions-cell">' +
            '<button class="r-btn r-btn-sm" data-az="cat-rinomina" data-id="' + c.id + '">Rinomina</button>' +
            '<button class="r-btn r-btn-sm" data-az="cat-posizione" data-id="' + c.id + '">Posizione</button>' +
            '<button class="r-btn r-btn-sm r-btn-danger" data-az="cat-elimina" data-id="' + c.id + '" data-nome="' + h(c.nome) + '">&#128465;</button>' +
          '</td>';
        body.appendChild(tr);
      });
      // aggiorna anche la select categorie nel form piatti
      var sel = document.getElementById('pCategoria');
      if (sel) {
        var val = sel.value;
        sel.innerHTML = '<option value="0">— nessuna —</option>';
        dati.categorie.forEach(function (c) { sel.innerHTML += '<option value="' + c.id + '">' + h(c.nome) + '</option>'; });
        sel.value = val;
      }
    }

    document.getElementById('cCrea').addEventListener('click', function () {
      var nome = document.getElementById('cNome').value.trim();
      var pos = document.getElementById('cPosizione').value.trim() || 0;
      if (nome === '') { setMsg('cMsg', 'Inserisci il nome.', false); return; }
      chiamaServer('creaCategoria', { nome: nome, posizione: pos }).then(function (res) {
        if (!res.ok) { setMsg('cMsg', res.errore || 'Errore.', false); return; }
        dati.categorie = res.categorie; disegnaCategorie();
        document.getElementById('cNome').value = ''; document.getElementById('cPosizione').value = '';
        setMsg('cMsg', 'Categoria creata.', true);
      });
    });

    // ---------- ALLERGENI ----------
    function disegnaAllergeni() {
      var body = document.getElementById('allergeniBody');
      body.innerHTML = '';
      document.getElementById('allergeniEmpty').style.display = dati.allergeni.length ? 'none' : 'block';
      dati.allergeni.forEach(function (a) {
        var tr = document.createElement('tr');
        tr.innerHTML =
          '<td class="r-num">' + h(a.nome) + '</td>' +
          '<td class="r-actions-cell">' +
            '<button class="r-btn r-btn-sm r-btn-danger" data-az="all-elimina" data-id="' + a.id + '" data-nome="' + h(a.nome) + '">&#128465;</button>' +
          '</td>';
        body.appendChild(tr);
      });
      // aggiorna le checkbox allergeni nel form piatti
      var box = document.getElementById('pAllergeni');
      if (box) {
        box.innerHTML = '';
        dati.allergeni.forEach(function (a) {
          var lab = document.createElement('label');
          lab.className = 'r-chip-check';
          lab.innerHTML = '<input type="checkbox" value="' + a.id + '"> ' + h(a.nome);
          box.appendChild(lab);
        });
      }
    }

    document.getElementById('aCrea').addEventListener('click', function () {
      var nome = document.getElementById('aNome').value.trim();
      if (nome === '') { setMsg('aMsg', 'Inserisci il nome.', false); return; }
      chiamaServer('creaAllergene', { nome: nome }).then(function (res) {
        if (!res.ok) { setMsg('aMsg', res.errore || 'Errore.', false); return; }
        dati.allergeni = res.allergeni; disegnaAllergeni();
        document.getElementById('aNome').value = '';
        setMsg('aMsg', 'Allergene creato.', true);
      });
    });

    // ---------- PIATTI ----------
    function nomeCategoria(id) { var c = dati.categorie.find(function (x) { return x.id === id; }); return c ? c.nome : null; }

    function disegnaPiatti() {
      var body = document.getElementById('piattiBody');
      body.innerHTML = '';
      document.getElementById('piattiEmpty').style.display = dati.piatti.length ? 'none' : 'block';
      var catCorrente = '__nessuna__';
      dati.piatti.forEach(function (p) {
        // intestazione di gruppo categoria (i piatti sono gia' ordinati per categoria)
        var etichettaCat = p.categoria || 'Senza categoria';
        if (etichettaCat !== catCorrente) {
          catCorrente = etichettaCat;
          var trh = document.createElement('tr');
          trh.className = 'r-group-row';
          trh.innerHTML = '<td colspan="6">' + h(catCorrente) + '</td>';
          body.appendChild(trh);
        }

        var allNomi = (p.allergeniIds || []).map(function (id) {
          var a = dati.allergeni.find(function (x) { return x.id === id; }); return a ? a.nome : null;
        }).filter(Boolean).join(', ');

        var tr = document.createElement('tr');
        if (!p.disponibile) tr.className = 'r-row-off';
        tr.innerHTML =
          '<td class="r-num">' + h(p.nome) +
            (p.disponibile ? '' : ' <span class="r-badge r-badge-off">non disp.</span>') + '</td>' +
          '<td>' + h(p.prezzoTxt) + ' \u20AC</td>' +
          '<td>' + (p.categoria ? h(p.categoria) : '<span class="r-muted">&mdash;</span>') + '</td>' +
          '<td>' + (p.cucina ? h(p.cucina) : '<span class="r-muted">&mdash;</span>') + '</td>' +
          '<td>' + (allNomi ? '<span class="r-muted" style="font-size:12px;">' + h(allNomi) + '</span>' : '<span class="r-muted">&mdash;</span>') + '</td>' +
          '<td class="r-actions-cell">' +
            '<button class="r-switch-sm ' + (p.disponibile ? 'on' : 'off') + '" data-az="piatto-disp" data-id="' + p.id + '" data-val="' + (p.disponibile ? '0' : '1') + '" title="' + (p.disponibile ? 'Disattiva' : 'Attiva') + '"><span></span></button>' +
            '<button class="r-btn r-btn-sm" data-az="piatto-modifica" data-id="' + p.id + '">Modifica</button>' +
            '<button class="r-btn r-btn-sm r-btn-danger" data-az="piatto-elimina" data-id="' + p.id + '" data-nome="' + h(p.nome) + '">&#128465;</button>' +
          '</td>';
        body.appendChild(tr);
      });
    }

    document.getElementById('pCrea').addEventListener('click', function () {
      var nome = document.getElementById('pNome').value.trim();
      var prezzo = document.getElementById('pPrezzo').value.trim();
      if (nome === '') { setMsg('pMsg', 'Inserisci il nome del piatto.', false); return; }
      var allSelez = [];
      document.querySelectorAll('#pAllergeni input:checked').forEach(function (c) { allSelez.push(parseInt(c.value, 10)); });
      var payload = {
        nome: nome,
        prezzo: prezzo || 0,
        categoriaId: document.getElementById('pCategoria').value || 0,
        cucinaId: document.getElementById('pCucina').value || 0,
        descrizione: document.getElementById('pDescrizione').value.trim(),
        etichetta: document.getElementById('pEtichetta').value.trim(),
        allergeni: JSON.stringify(allSelez)
      };
      chiamaServer('creaPiatto', payload).then(function (res) {
        if (!res.ok) { setMsg('pMsg', res.errore || 'Errore.', false); return; }
        dati.piatti = res.piatti; disegnaPiatti();
        ['pNome', 'pPrezzo', 'pDescrizione', 'pEtichetta'].forEach(function (id) { document.getElementById(id).value = ''; });
        document.querySelectorAll('#pAllergeni input:checked').forEach(function (c) { c.checked = false; });
        setMsg('pMsg', 'Piatto "' + nome + '" creato.', true);
      });
    });

    // azioni delegate (elimina/rinomina nelle tabelle)
    menuWrap.addEventListener('click', function (e) {
      var btn = e.target.closest('[data-az]');
      if (!btn) return;
      var az = btn.getAttribute('data-az');
      var id = parseInt(btn.getAttribute('data-id'), 10);
      var nome = btn.getAttribute('data-nome') || '';

      if (az === 'cat-elimina') {
        if (!confirm('Eliminare la categoria "' + nome + '"? I piatti resteranno senza categoria.')) return;
        chiamaServer('eliminaCategoria', { categoriaId: id }).then(function (res) {
          if (!res.ok) { alert(res.errore); return; } dati.categorie = res.categorie; disegnaCategorie(); disegnaPiatti();
        });
      } else if (az === 'cat-rinomina') {
        var nuovo = prompt('Nuovo nome della categoria:');
        if (nuovo === null || nuovo.trim() === '') return;
        chiamaServer('aggiornaCategoria', { categoriaId: id, nome: nuovo.trim() }).then(function (res) {
          if (!res.ok) { alert(res.errore); return; } dati.categorie = res.categorie; disegnaCategorie(); disegnaPiatti();
        });
      } else if (az === 'all-elimina') {
        if (!confirm('Eliminare l\'allergene "' + nome + '"?')) return;
        chiamaServer('eliminaAllergene', { allergeneId: id }).then(function (res) {
          if (!res.ok) { alert(res.errore); return; } dati.allergeni = res.allergeni; disegnaAllergeni(); disegnaPiatti();
        });
      } else if (az === 'piatto-elimina') {
        if (!confirm('Eliminare il piatto "' + nome + '"?')) return;
        chiamaServer('eliminaPiatto', { piattoId: id }).then(function (res) {
          if (!res.ok) { alert(res.errore); return; } dati.piatti = res.piatti; disegnaPiatti();
        });
      } else if (az === 'piatto-disp') {
        var val = btn.getAttribute('data-val');
        chiamaServer('impostaDisponibilitaPiatto', { piattoId: id, disponibile: val }).then(function (res) {
          if (!res.ok) { alert(res.errore); return; } dati.piatti = res.piatti; disegnaPiatti();
        });
      } else if (az === 'piatto-modifica') {
        apriModificaPiatto(id);
      } else if (az === 'cat-posizione') {
        var nuovaPos = prompt('Nuova posizione della categoria (1 = in alto):');
        if (nuovaPos === null || nuovaPos.trim() === '') return;
        chiamaServer('aggiornaCategoria', { categoriaId: id, posizione: parseInt(nuovaPos, 10) || 0 }).then(function (res) {
          if (!res.ok) { alert(res.errore); return; } dati.categorie = res.categorie; disegnaCategorie(); disegnaPiatti();
        });
      }
    });

    function setMsg(elId, testo, ok) {
      var m = document.getElementById(elId);
      m.textContent = testo;
      m.className = 'r-msg r-msg-box ' + (ok ? 'r-msg-ok' : 'r-msg-err');
      if (ok) { clearTimeout(m._t); m._t = setTimeout(function () { m.textContent = ''; m.className = 'r-msg'; }, 4000); }
    }

    // --- modifica piatto esistente (overlay) ---
    function apriModificaPiatto(id) {
      var p = dati.piatti.find(function (x) { return x.id === id; });
      if (!p) return;
      var ov = document.getElementById('piattoEditOverlay');
      document.getElementById('ePiattoId').value = p.id;
      document.getElementById('eNome').value = p.nome;
      document.getElementById('ePrezzo').value = String(p.prezzo).replace(',', '.');
      document.getElementById('eDescrizione').value = p.descrizione || '';
      document.getElementById('eEtichetta').value = p.etichetta || '';

      // select categoria
      var selC = document.getElementById('eCategoria');
      selC.innerHTML = '<option value="0">— nessuna —</option>';
      dati.categorie.forEach(function (c) { selC.innerHTML += '<option value="' + c.id + '">' + h(c.nome) + '</option>'; });
      selC.value = p.categoriaId || 0;

      // select cucina
      var selK = document.getElementById('eCucina');
      selK.innerHTML = '<option value="0">— nessuna —</option>';
      cucineMenu.forEach(function (c) { selK.innerHTML += '<option value="' + c.id + '">' + h(c.nome) + '</option>'; });
      selK.value = p.cucinaId || 0;

      // checkbox allergeni
      var box = document.getElementById('eAllergeni');
      box.innerHTML = '';
      dati.allergeni.forEach(function (a) {
        var checked = (p.allergeniIds || []).indexOf(a.id) >= 0 ? 'checked' : '';
        var lab = document.createElement('label');
        lab.className = 'r-chip-check';
        lab.innerHTML = '<input type="checkbox" value="' + a.id + '" ' + checked + '> ' + h(a.nome);
        box.appendChild(lab);
      });

      // immagine corrente
      mostraAnteprimaImg(p.immagine);
      document.getElementById('eImgMsg').textContent = '';

      ov.classList.add('open');
    }

    // mostra l'anteprima dell'immagine (o "nessuna")
    function mostraAnteprimaImg(percorso) {
      var prev = document.getElementById('eImgPreview');
      var btnRim = document.getElementById('eImgRimuovi');
      if (percorso) {
        prev.innerHTML = '<img src="' + h(percorso) + '?t=' + Date.now() + '" alt="anteprima">';
        btnRim.style.display = '';
      } else {
        prev.innerHTML = '<span class="r-img-vuota">Nessuna immagine</span>';
        btnRim.style.display = 'none';
      }
    }

    // "Scegli immagine" apre il selettore file
    document.getElementById('eImgScegli').addEventListener('click', function () {
      document.getElementById('eImgFile').click();
    });

    // quando si sceglie un file, lo si carica subito
    document.getElementById('eImgFile').addEventListener('change', function () {
      var file = this.files[0];
      if (!file) return;
      var id = parseInt(document.getElementById('ePiattoId').value, 10);
      var msg = document.getElementById('eImgMsg');
      msg.textContent = 'Caricamento…';
      msg.className = 'r-msg';

      // i file si inviano con FormData (multipart), non in JSON
      var fd = new FormData();
      fd.set('controller', 'Ristorante');
      fd.set('action', 'caricaImmaginePiatto');
      fd.set('piattoId', id);
      fd.set('immagine', file);

      fetch('index.php', { method: 'POST', body: fd })
        .then(function (r) { return r.json(); })
        .then(function (res) {
          if (!res.ok) { msg.textContent = res.errore || 'Errore nel caricamento.'; msg.className = 'r-msg r-msg-box r-msg-err'; return; }
          dati.piatti = res.piatti;
          var aggiornato = dati.piatti.find(function (x) { return x.id === id; });
          mostraAnteprimaImg(aggiornato ? aggiornato.immagine : null);
          disegnaPiatti();
          msg.textContent = 'Immagine caricata.'; msg.className = 'r-msg r-msg-box r-msg-ok';
        })
        .catch(function () { msg.textContent = 'Errore di rete.'; msg.className = 'r-msg r-msg-box r-msg-err'; });
      this.value = '';  // permette di ricaricare lo stesso file se serve
    });

    // rimuovi immagine
    document.getElementById('eImgRimuovi').addEventListener('click', function () {
      var id = parseInt(document.getElementById('ePiattoId').value, 10);
      if (!confirm('Rimuovere l\'immagine di questo piatto?')) return;
      chiamaServer('rimuoviImmaginePiatto', { piattoId: id }).then(function (res) {
        if (!res.ok) { alert(res.errore); return; }
        dati.piatti = res.piatti;
        mostraAnteprimaImg(null);
        disegnaPiatti();
      });
    });

    function chiudiModifica() { document.getElementById('piattoEditOverlay').classList.remove('open'); }
    document.getElementById('eClose').addEventListener('click', chiudiModifica);
    document.getElementById('eAnnulla').addEventListener('click', chiudiModifica);

    document.getElementById('eSalva').addEventListener('click', function () {
      var id = parseInt(document.getElementById('ePiattoId').value, 10);
      var nome = document.getElementById('eNome').value.trim();
      if (nome === '') { alert('Il nome non puo\' essere vuoto.'); return; }
      var allSelez = [];
      document.querySelectorAll('#eAllergeni input:checked').forEach(function (c) { allSelez.push(parseInt(c.value, 10)); });
      chiamaServer('aggiornaPiatto', {
        piattoId: id,
        nome: nome,
        prezzo: document.getElementById('ePrezzo').value.trim() || 0,
        categoriaId: document.getElementById('eCategoria').value || 0,
        cucinaId: document.getElementById('eCucina').value || 0,
        descrizione: document.getElementById('eDescrizione').value.trim(),
        etichetta: document.getElementById('eEtichetta').value.trim(),
        allergeni: JSON.stringify(allSelez)
      }).then(function (res) {
        if (!res.ok) { alert(res.errore || 'Errore.'); return; }
        dati.piatti = res.piatti; disegnaPiatti(); chiudiModifica();
      });
    });

    // disegno iniziale
    disegnaCategorie();
    disegnaAllergeni();
    disegnaPiatti();
  }

  // =================================================================
  //  PAGINA PROFILO (esiste #profiloWrap)
  // =================================================================
  var profiloWrap = document.getElementById('profiloWrap');
  if (profiloWrap) {

    function msgProfilo(elId, testo, ok) {
      var m = document.getElementById(elId);
      m.textContent = testo;
      m.className = 'r-msg r-msg-box ' + (ok ? 'r-msg-ok' : 'r-msg-err');
      if (ok) { clearTimeout(m._t); m._t = setTimeout(function () { m.textContent = ''; m.className = 'r-msg'; }, 4000); }
    }

    document.getElementById('btnSalvaNome').addEventListener('click', function () {
      var nome = document.getElementById('profNome').value.trim();
      if (nome === '') { msgProfilo('nomeMsg', 'Il nome non puo\' essere vuoto.', false); return; }
      chiamaServer('aggiornaNomeRistorante', { nome: nome }).then(function (res) {
        if (!res.ok) { msgProfilo('nomeMsg', res.errore || 'Errore.', false); return; }
        msgProfilo('nomeMsg', 'Nome aggiornato.', true);
        var side = document.querySelector('.r-title-side');
        if (side) side.textContent = nome;
      });
    });

    document.getElementById('btnCambiaPwd').addEventListener('click', function () {
      var attuale = document.getElementById('profAttuale').value;
      var nuova = document.getElementById('profNuova').value;
      var ripeti = document.getElementById('profRipeti').value;
      if (attuale === '' || nuova === '') { msgProfilo('pwdMsg', 'Compila tutti i campi.', false); return; }
      if (nuova !== ripeti) { msgProfilo('pwdMsg', 'Le due nuove password non coincidono.', false); return; }
      chiamaServer('cambiaPasswordRistorante', { attuale: attuale, nuova: nuova }).then(function (res) {
        if (!res.ok) { msgProfilo('pwdMsg', res.errore || 'Errore.', false); return; }
        msgProfilo('pwdMsg', 'Password cambiata con successo.', true);
        document.getElementById('profAttuale').value = '';
        document.getElementById('profNuova').value = '';
        document.getElementById('profRipeti').value = '';
      });
    });
  }

})();
