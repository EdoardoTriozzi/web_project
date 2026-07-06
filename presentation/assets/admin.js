/*
 * admin.js — comportamento della schermata dell'amministratore.
 *
 * Un'unica pagina: l'elenco dei ristoranti. Disegna la tabella dai dati,
 * gestisce la creazione di un ristorante e le azioni su ognuno
 * (attiva/disattiva, elimina). Stesso schema degli altri file: una funzione
 * "chiamaServer" per parlare col control, e funzioni che ridisegnano la
 * tabella con i dati aggiornati che il server restituisce.
 */
(function () {
  'use strict';

  // manda una richiesta a CAmministratore e restituisce il JSON
  async function chiamaServer(action, dati) {
    var corpo = new URLSearchParams();
    corpo.set('controller', 'Amministratore');
    corpo.set('action', action);
    for (var k in dati) { corpo.set(k, dati[k]); }
    var risposta = await fetch('index.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: corpo.toString()
    });
    return await risposta.json();
  }

  // piccola utilità: mette al sicuro il testo prima di inserirlo nell'HTML
  // (evita che un nome con caratteri speciali rompa la pagina)
  function h(s) {
    return String(s == null ? '' : s)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
  }

  var wrap = document.getElementById('adminWrap');
  if (!wrap) return;   // sicurezza: se non siamo sulla pagina admin, non fare nulla

  // dati iniziali dei ristoranti, letti dall'attributo data-ristoranti
  var ristoranti = [];
  try { ristoranti = JSON.parse(wrap.getAttribute('data-ristoranti')); } catch (e) {}

  // disegna la tabella dei ristoranti
  function disegna() {
    var body = document.getElementById('ristorantiBody');
    body.innerHTML = '';
    document.getElementById('ristorantiEmpty').style.display = ristoranti.length ? 'none' : 'block';

    ristoranti.forEach(function (r) {
      var tr = document.createElement('tr');
      if (!r.attivo) tr.className = 'r-row-off';

      // pulsante attiva/disattiva a seconda dello stato corrente
      var azioneStato = r.attivo
        ? '<button class="r-btn r-btn-sm" data-az="disattiva" data-id="' + r.id + '">Disattiva</button>'
        : '<button class="r-btn r-btn-sm r-btn-pay" data-az="attiva" data-id="' + r.id + '">Attiva</button>';

      tr.innerHTML =
        '<td class="r-num">' + h(r.nome) + '</td>' +
        '<td class="r-muted">' + h(r.username) + '</td>' +
        '<td>' + (r.attivo
          ? '<span class="r-badge r-badge-libero">Attivo</span>'
          : '<span class="r-badge r-badge-off">Disattivato</span>') + '</td>' +
        '<td class="r-actions-cell">' +
          azioneStato +
          '<button class="r-btn r-btn-sm r-btn-danger" data-az="elimina" data-id="' + r.id + '" data-nome="' + h(r.nome) + '">&#128465;</button>' +
        '</td>';
      body.appendChild(tr);
    });
  }

  // creazione di un nuovo ristorante
  document.getElementById('btnCrea').addEventListener('click', function () {
    var nome = document.getElementById('nNome').value.trim();
    var username = document.getElementById('nUsername').value.trim();
    var password = document.getElementById('nPassword').value;
    if (nome === '' || username === '' || password === '') {
      setMsg('creaMsg', 'Compila nome, username e password.', false);
      return;
    }
    chiamaServer('creaRistorante', { nome: nome, username: username, password: password }).then(function (res) {
      if (!res.ok) { setMsg('creaMsg', res.errore || 'Errore.', false); return; }
      ristoranti = res.ristoranti;
      disegna();
      document.getElementById('nNome').value = '';
      document.getElementById('nUsername').value = '';
      document.getElementById('nPassword').value = '';
      setMsg('creaMsg', 'Ristorante "' + nome + '" creato.', true);
    });
  });

  // azioni sulle righe (attiva/disattiva, elimina), delegate sul contenitore
  wrap.addEventListener('click', function (e) {
    var btn = e.target.closest('[data-az]');
    if (!btn) return;
    var az = btn.getAttribute('data-az');
    var id = parseInt(btn.getAttribute('data-id'), 10);
    var nome = btn.getAttribute('data-nome') || '';

    if (az === 'attiva') {
      chiamaServer('attivaRistorante', { ristoranteId: id }).then(applica);
    } else if (az === 'disattiva') {
      chiamaServer('disattivaRistorante', { ristoranteId: id }).then(applica);
    } else if (az === 'elimina') {
      if (!confirm('Eliminare il ristorante "' + nome + '"? L\'operazione non si puo\' annullare.')) return;
      chiamaServer('eliminaRistorante', { ristoranteId: id }).then(applica);
    }
  });

  // applica la risposta di un'azione: aggiorna la lista o mostra l'errore
  function applica(res) {
    if (!res.ok) { alert(res.errore || 'Operazione non riuscita.'); return; }
    ristoranti = res.ristoranti;
    disegna();
  }

  // messaggio di conferma/errore che sparisce dopo qualche secondo
  function setMsg(elId, testo, ok) {
    var m = document.getElementById(elId);
    m.textContent = testo;
    m.className = 'r-msg r-msg-box ' + (ok ? 'r-msg-ok' : 'r-msg-err');
    if (ok) { clearTimeout(m._t); m._t = setTimeout(function () { m.textContent = ''; m.className = 'r-msg'; }, 4000); }
  }

  // disegno iniziale
  disegna();

})();
