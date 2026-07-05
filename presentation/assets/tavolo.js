/*
 * tavolo.js — comportamento della pagina del tavolo.
 *
 * Due tipi di lavoro:
 *  1) INTERAZIONI LOCALI (solo nel browser): aprire tendine, scorrere alle
 *     categorie, filtrare per allergeni, aprire/chiudere overlay.
 *  2) AZIONI SUL SERVER (fetch): aggiungere/rimuovere dalla bozza, inviare
 *     l'ordine, richiedere il conto, leggere lo storico. Qui il browser
 *     chiama il control CTavolo e aggiorna la pagina con la risposta.
 *
 * Il "ponte" col server e' la funzione chiamaServer() qui sotto: manda una
 * richiesta a index.php?controller=Tavolo&action=... e restituisce il JSON.
 */
(function () {
  'use strict';

  // Formatta un numero come prezzo italiano: 6.5 -> "6,50"
  function euro(n) { return Number(n).toFixed(2).replace('.', ','); }

  /*
   * chiamaServer(action, dati)
   * Manda una richiesta al control CTavolo e restituisce la risposta JSON.
   * - action: il nome del metodo del control (es. 'aggiungiAllaBozza')
   * - dati:   oggetto con i parametri (es. {piattoId: 230, quantita: 1})
   *
   * Usa fetch con metodo POST: i dati viaggiano nel corpo della richiesta.
   * "await" aspetta la risposta del server prima di proseguire: e' la parte
   * "non istantanea" di cui parlavamo. La funzione e' percio' "async".
   */
  async function chiamaServer(action, dati) {
    var corpo = new URLSearchParams();
    corpo.set('controller', 'Tavolo');
    corpo.set('action', action);
    for (var k in dati) { corpo.set(k, dati[k]); }

    var risposta = await fetch('index.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: corpo.toString()
    });
    return await risposta.json(); // converte la risposta JSON in oggetto JS
  }

  // ------------------------------------------------------------------
  //  Stato locale della bozza, per disegnare il carrello e il contatore.
  //  Lo teniamo allineato a cio' che il server ci risponde.
  // ------------------------------------------------------------------
  var bozza = { righe: [], totale: 0 };

  // aggiorna il contatore in basso (totale + numero pezzi) e i numerini
  // "quantita'" sulle schede del menu, in base alla bozza corrente.
  function aggiornaVistaBozza() {
    var pezzi = 0;
    // azzera tutti i numerini del menu, poi rimette quelli presenti in bozza
    document.querySelectorAll('.val').forEach(function (v) { if (v.id) v.textContent = '0'; });
    bozza.righe.forEach(function (r) {
      pezzi += r.quantita;
      var el = document.getElementById('q' + r.piattoId);
      if (el) el.textContent = r.quantita;
    });
    document.getElementById('cartTotal').innerHTML = euro(bozza.totale) + ' &euro;';
    document.getElementById('cartTotal2').textContent = euro(bozza.totale);
    var badge = document.getElementById('cartBadge');
    if (pezzi > 0) { badge.style.display = 'block'; badge.textContent = pezzi; }
    else { badge.style.display = 'none'; }
  }

  // disegna le righe dentro l'overlay del carrello
  function renderCarrello() {
    var rows = document.getElementById('cartRows');
    var empty = document.getElementById('cartEmpty');
    rows.innerHTML = '';
    if (bozza.righe.length === 0) { empty.style.display = 'block'; return; }
    empty.style.display = 'none';
    bozza.righe.forEach(function (r) {
      var row = document.createElement('div');
      row.className = 'cart-row';
      row.innerHTML =
        '<button class="rm-btn" data-id="' + r.piattoId + '" aria-label="Rimuovi"></button>' +
        '<div class="thumb">FOTO</div>' +
        '<div class="cart-info"><div class="n">' + r.nome + '</div><div class="p">' + euro(r.prezzo) + ' &euro;</div></div>' +
        '<div class="stepper sm">' +
          '<button class="cstep" data-id="' + r.piattoId + '" data-d="-1">-</button>' +
          '<span class="val">' + r.quantita + '</span>' +
          '<button class="cstep" data-id="' + r.piattoId + '" data-d="1">+</button>' +
        '</div>';
      rows.appendChild(row);
    });
    // pulsanti +/- dentro il carrello
    rows.querySelectorAll('.cstep').forEach(function (b) {
      b.addEventListener('click', function () {
        var id = b.getAttribute('data-id');
        var d = parseInt(b.getAttribute('data-d'), 10);
        if (d > 0) aggiungi(id, 1);
        else rimuoviUno(id);
      });
    });
    // pulsante rimuovi riga (X)
    rows.querySelectorAll('.rm-btn').forEach(function (b) {
      b.addEventListener('click', function () { rimuoviTutto(b.getAttribute('data-id')); });
    });
  }

  // ------------------------------------------------------------------
  //  AZIONI sul server: ognuna chiama il control e poi aggiorna la vista
  //  con la bozza che il server restituisce.
  // ------------------------------------------------------------------

  async function aggiungi(piattoId, quantita) {
    var res = await chiamaServer('aggiungiAllaBozza', { piattoId: piattoId, quantita: quantita });
    if (res.ok) { bozza = res.bozza; aggiornaVistaBozza(); renderCarrello(); }
    else alert(res.errore || 'Errore.');
  }

  // rimuove UNA unita': se la quantita' scende a 0, la riga sparisce.
  // Lo facciamo aggiungendo quantita' negativa non e' previsto dal server,
  // quindi: leggiamo la quantita' attuale, e se >1 rimuoviamo tutto e
  // riaggiungiamo (qta-1); se ==1 rimuoviamo del tutto. Per semplicita' e
  // robustezza, qui rimuoviamo tutto e riaggiungiamo la quantita' ridotta.
  async function rimuoviUno(piattoId) {
    var riga = bozza.righe.find(function (r) { return String(r.piattoId) === String(piattoId); });
    if (!riga) return;
    if (riga.quantita <= 1) {
      await rimuoviTutto(piattoId);
    } else {
      await rimuoviTutto(piattoId);
      await aggiungi(piattoId, riga.quantita - 1);
    }
  }

  async function rimuoviTutto(piattoId) {
    var res = await chiamaServer('rimuoviDallaBozza', { piattoId: piattoId });
    if (res.ok) { bozza = res.bozza; aggiornaVistaBozza(); renderCarrello(); }
    else alert(res.errore || 'Errore.');
  }

  async function svuota() {
    // svuota = rimuove ogni riga presente
    var ids = bozza.righe.map(function (r) { return r.piattoId; });
    for (var i = 0; i < ids.length; i++) {
      await chiamaServer('rimuoviDallaBozza', { piattoId: ids[i] });
    }
    var res = await chiamaServer('mostraBozza', {});
    if (res.ok) { bozza = res.bozza; aggiornaVistaBozza(); renderCarrello(); }
  }

  async function invia() {
    var res = await chiamaServer('inviaOrdine', {});
    if (res.ok) {
      bozza = res.bozza;            // ora vuota
      aggiornaVistaBozza();
      renderCarrello();
      chiudi('overlayCart');
      mostra('overlayInviato');     // conferma
    } else {
      alert(res.errore || 'Niente da inviare.');
    }
  }

  async function richiediConto() {
    var res = await chiamaServer('richiediConto', {});
    if (res.ok) { chiudi('overlayConto'); mostra('overlayCassa'); }
    else alert(res.errore || 'Errore.');
  }

  async function caricaStorico() {
    var res = await chiamaServer('storico', {});
    var rows = document.getElementById('storicoRows');
    var empty = document.getElementById('storicoEmpty');
    rows.innerHTML = '';
    if (!res.ok || res.ordini.length === 0) {
      empty.style.display = 'block';
      document.getElementById('storicoTot').textContent = '0,00';
      return;
    }
    empty.style.display = 'none';
    res.ordini.forEach(function (o) {
      o.righe.forEach(function (r) {
        var row = document.createElement('div');
        row.style.cssText = 'display:flex; justify-content:space-between; padding:8px 0; border-bottom:1px solid var(--border); font-size:14px;';
        row.innerHTML = '<span>' + r.nome + ' x' + r.quantita + '</span>' +
                        '<span style="color:var(--soft);">' + euro(r.subtotale) + ' &euro;</span>';
        rows.appendChild(row);
      });
    });
    document.getElementById('storicoTot').textContent = euro(res.totaleGenerale);
  }

  // ------------------------------------------------------------------
  //  INTERAZIONI LOCALI (solo browser)
  // ------------------------------------------------------------------

  // pulsanti +/- sulle schede del menu
  document.querySelectorAll('.step').forEach(function (b) {
    b.addEventListener('click', function () {
      var id = b.getAttribute('data-id');
      var d = parseInt(b.getAttribute('data-d'), 10);
      if (d > 0) aggiungi(id, 1);
      else rimuoviUno(id);
    });
  });

  // tendine (categorie / allergeni)
  function drop(btn, panel) {
    btn.addEventListener('click', function () {
      var open = panel.classList.toggle('show');
      btn.classList.toggle('open', open);
    });
  }
  drop(document.getElementById('btnCat'), document.getElementById('panelCat'));
  drop(document.getElementById('btnAll'), document.getElementById('panelAll'));

  // categorie come INDICE: clic -> scorre fino al titolo, senza nascondere nulla
  document.querySelectorAll('.chip').forEach(function (c) {
    c.addEventListener('click', function () {
      var cat = c.getAttribute('data-cat');
      document.querySelectorAll('.chip').forEach(function (x) { x.classList.remove('active'); });
      c.classList.add('active');
      document.getElementById('catLabel').textContent = cat ? c.textContent : 'Tutte le categorie';
      document.getElementById('panelCat').classList.remove('show');
      document.getElementById('btnCat').classList.remove('open');
      if (cat) {
        var titolo = document.querySelector('.cat-title[data-cat="' + cat + '"]');
        if (titolo) titolo.scrollIntoView({ behavior: 'smooth', block: 'start' });
      } else {
        document.getElementById('menuList').scrollTo({ top: 0, behavior: 'smooth' });
      }
    });
  });

  // filtro allergeni: nasconde i piatti che contengono gli allergeni spuntati
  var filtroAll = [];
  function applicaFiltro() {
    document.querySelectorAll('.card').forEach(function (card) {
      var all = (card.getAttribute('data-all') || '').split(',').filter(Boolean);
      var ok = true;
      for (var i = 0; i < filtroAll.length; i++) { if (all.indexOf(filtroAll[i]) >= 0) ok = false; }
      card.style.display = ok ? '' : 'none';
    });
    document.querySelectorAll('.cat-title').forEach(function (t) {
      var c = t.getAttribute('data-cat');
      var visibili = Array.prototype.slice.call(document.querySelectorAll('.card[data-cat="' + c + '"]'))
        .filter(function (card) { return card.style.display !== 'none'; });
      t.style.display = visibili.length ? '' : 'none';
    });
  }
  function aggiornaFiltro() {
    filtroAll = Array.prototype.slice.call(document.querySelectorAll('.all-chk:checked')).map(function (x) { return x.value; });
    var b = document.getElementById('allCount');
    if (filtroAll.length) { b.style.display = 'inline-block'; b.textContent = filtroAll.length; }
    else { b.style.display = 'none'; }
    applicaFiltro();
  }
  document.querySelectorAll('.all-chk').forEach(function (chk) { chk.addEventListener('change', aggiornaFiltro); });
  document.getElementById('resetAll').addEventListener('click', function () {
    document.querySelectorAll('.all-chk').forEach(function (chk) { chk.checked = false; });
    aggiornaFiltro();
  });

  // overlay: apri/chiudi
  function mostra(id) { document.getElementById(id).classList.add('show'); }
  function chiudi(id) { document.getElementById(id).classList.remove('show'); }

  // Clic su un'immagine di piatto: la mostra ingrandita nell'overlay.
  // Le immagini sono dentro #menuList, che viene generato dal server: uso la
  // delega sull'evento, cosi' funziona per tutte le card senza un listener
  // per ognuna.
  var menuList = document.getElementById('menuList');
  if (menuList) {
    menuList.addEventListener('click', function (e) {
      var img = e.target.closest('.foto-zoom');
      if (!img) return;
      document.getElementById('imgGrande').src = img.getAttribute('data-img');
      document.getElementById('imgGrande').alt = img.getAttribute('data-nome') || '';
      document.getElementById('imgCaption').textContent = img.getAttribute('data-nome') || '';
      mostra('overlayImg');
    });
  }
  // chiudere anche cliccando sullo sfondo scuro (non solo sulla X)
  var ovImg = document.getElementById('overlayImg');
  if (ovImg) {
    ovImg.addEventListener('click', function (e) {
      if (e.target === ovImg) chiudi('overlayImg');
    });
  }
  document.getElementById('btnCart').addEventListener('click', function () { renderCarrello(); mostra('overlayCart'); });
  document.getElementById('btnStorico').addEventListener('click', function () { caricaStorico(); mostra('overlayStorico'); });
  document.getElementById('btnConto').addEventListener('click', function () { mostra('overlayConto'); });
  document.getElementById('svuotaBtn').addEventListener('click', svuota);
  document.getElementById('inviaBtn').addEventListener('click', invia);
  document.getElementById('confermaConto').addEventListener('click', richiediConto);
  document.querySelectorAll('.close-ov').forEach(function (b) {
    b.addEventListener('click', function () { chiudi(b.getAttribute('data-t')); });
  });

  // ------------------------------------------------------------------
  //  All'avvio: chiede al server la bozza corrente (il carrello condiviso
  //  potrebbe gia' contenere piatti messi da un altro dispositivo del tavolo).
  // ------------------------------------------------------------------
  (async function init() {
    try {
      var res = await chiamaServer('mostraBozza', {});
      if (res.ok) { bozza = res.bozza; aggiornaVistaBozza(); }
    } catch (e) { /* se il server non risponde, la pagina resta usabile */ }
  })();

})();
