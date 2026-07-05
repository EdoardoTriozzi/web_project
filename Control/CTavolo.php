<?php
declare(strict_types=1);

namespace Control;

use Foundation\Session;
use Foundation\FPiatto;
use Foundation\FAllergene;
use Foundation\FOrdine;
use Foundation\FTavolo;
use Foundation\FRistorante;
use Foundation\PersistentManager;
use Entity\Menu;
use Entity\Ordine;
use Entity\Allergene;
use Entity\Categoria;
use Entity\Piatto;
use Entity\StatoOrdine;

/**
 * CTavolo — classe control del caso d'uso "Tavolo".
 *
 * E' il caso d'uso piu' ricco: copre la comunicazione tavolo-cucina.
 * Operazioni di sistema (un metodo pubblico ciascuna, firma uniforme
 * (array $params) per l'invocazione via reflection dal FrontController):
 *
 *   - mostraMenu()         -> mostra il menu (eventualmente filtrato per allergeni)
 *   - aggiungiAllaBozza()  -> aggiunge un piatto al carrello condiviso del tavolo
 *   - rimuoviDallaBozza()  -> toglie un piatto dal carrello condiviso
 *   - mostraBozza()        -> mostra il carrello corrente (per il polling dei dispositivi)
 *   - inviaOrdine()        -> manda la bozza in cucina (BOZZA -> INVIATO)
 *   - richiediConto()      -> alza il flag conto del tavolo
 *
 * Sicurezza: ogni operazione comincia con richiediTavolo(), che pretende
 * un tavolo loggato e restituisce il suo id (preso dalla SESSIONE, mai
 * dalla richiesta: cosi' un dispositivo non puo' spacciarsi per un altro
 * tavolo manipolando l'URL).
 *
 * Carrello condiviso: la "bozza" e' un Ordine in stato BOZZA che vive nel
 * database, uno per tavolo. Tutti i dispositivi del tavolo leggono e
 * scrivono la stessa bozza. Il primo che aggiunge un piatto la crea; gli
 * altri la aggiornano. Inviata in cucina, diventa INVIATO e smette di
 * essere un carrello aperto.
 */
final class CTavolo
{
    /** Ruolo atteso in sessione per accedere a queste operazioni. */
    private const RUOLO = 'tavolo';

    // ------------------------------------------------------------------
    //  Operazioni di sistema
    // ------------------------------------------------------------------

    /**
     * Mostra il menu del ristorante del tavolo. Se la richiesta porta degli
     * allergeni da escludere, applica il filtro di esclusione: nasconde i
     * piatti che contengono almeno uno di quegli allergeni.
     *
     * @param array<string,mixed> $params  opzionale: 'allergeni' => array di id
     */
    public function mostraMenu(array $params): void
    {
        $tavoloId     = $this->richiediTavolo();
        $ristoranteId = $this->ristoranteIdDelTavolo($tavoloId);

        // Il Menu e' una vista di sola lettura: lo costruiamo al volo dai
        // piatti del ristorante (non si persiste). Mostriamo i soli disponibili.
        $menu   = new Menu(FPiatto::loadByRistorante($ristoranteId));
        $piatti = $menu->piattiDisponibili();

        // Raggruppa i piatti per categoria, preservando l'ordine (i piatti
        // arrivano gia' ordinati per posizione da loadByRistorante). La view
        // riceve i gruppi gia' pronti: e' il control a fare la preparazione,
        // la view si limita a disegnare.
        $gruppi = $this->raggruppaPerCategoria($piatti);

        // Catalogo allergeni del ristorante, per le checkbox del filtro.
        // (Il filtro vero e' applicato lato browser dal JavaScript, come da
        // interfaccia approvata; qui forniamo solo l'elenco selezionabile.)
        $catalogoAllergeni = FAllergene::loadByRistorante($ristoranteId);

        // Dati per l'intestazione.
        $tavolo          = FTavolo::load($tavoloId);
        $numeroTavolo    = $tavolo !== null ? 'Tavolo ' . $tavolo->getNumero() : 'Tavolo';
        $nomeRistorante  = $this->nomeRistorante($ristoranteId);

        // Prepara i gruppi come array semplici per il template Smarty.
        $gruppiView = [];
        foreach ($gruppi as $g) {
            $cat   = $g['categoria'];
            $catId = $cat !== null ? 'cat-' . (int) $cat->getId() : 'cat-0';
            $piattiView = [];
            foreach ($g['piatti'] as $p) {
                $nomiAllergeni = [];
                foreach ($p->getAllergeni() as $a) {
                    $nomiAllergeni[] = $a->getNome();
                }
                $piattiView[] = [
                    'id'          => (int) $p->getId(),
                    'nome'        => $p->getNome(),
                    'descrizione' => $p->getDescrizione(),
                    'prezzo'      => number_format($p->getPrezzo(), 2, ',', '.'),
                    'immagine'    => $p->getImmagine(),
                    'etichetta'   => $p->getEtichetta(),
                    'allergeni'   => $nomiAllergeni,
                    'dataAll'     => implode(',', $nomiAllergeni),
                ];
            }
            $gruppiView[] = [
                'catId'   => $catId,
                'catNome' => $cat !== null ? $cat->getNome() : 'Senza categoria',
                'piatti'  => $piattiView,
            ];
        }

        // Catalogo allergeni come semplice lista di nomi.
        $allergeniView = [];
        foreach ($catalogoAllergeni as $a) {
            $allergeniView[] = $a->getNome();
        }

        // Passa i dati al template Smarty e lo mostra.
        $view = \Foundation\Presentazione::crea();
        $view->assign('nomeRistorante', $nomeRistorante);
        $view->assign('numeroTavolo', $numeroTavolo);
        $view->assign('gruppi', $gruppiView);
        $view->assign('catalogoAllergeni', $allergeniView);
        $view->display('menu/menu.tpl');
    }

    /**
     * Aggiunge un piatto al carrello condiviso del tavolo.
     * Se la bozza non esiste ancora, la crea; altrimenti la aggiorna.
     *
     * @param array<string,mixed> $params  attesi: 'piattoId'; opzionale 'quantita'
     */
    public function aggiungiAllaBozza(array $params): void
    {
        $tavoloId = $this->richiediTavolo();

        $piattoId = (int)($params['piattoId'] ?? 0);
        $quantita = max(1, (int)($params['quantita'] ?? 1));

        if ($piattoId <= 0) {
            $this->rispondiErrore('Piatto non valido.');
            return;
        }

        $piatto = FPiatto::load($piattoId);
        if ($piatto === null || !$piatto->isDisponibile()) {
            $this->rispondiErrore('Piatto non disponibile.');
            return;
        }

        // Recupera la bozza condivisa o creane una nuova (primo piatto del tavolo).
        $bozza     = FOrdine::loadBozzaByTavolo($tavoloId);
        $daSalvare = $bozza ?? new Ordine();   // nuova bozza in stato BOZZA

        // Logica di dominio: se il piatto c'e' gia', incrementa la quantita'.
        $daSalvare->aggiungiPiatto($piatto, $quantita);

        // Persistenza: store se nuova (serve l'id del tavolo), update se esisteva.
        if ($bozza === null) {
            PersistentManager::store($daSalvare, $tavoloId);
        } else {
            PersistentManager::update($daSalvare);
        }

        // Rilegge la bozza salvata e la restituisce al JavaScript.
        $this->rispondiBozza(FOrdine::loadBozzaByTavolo($tavoloId));
    }

    /**
     * Rimuove dal carrello condiviso tutte le righe di un piatto.
     *
     * @param array<string,mixed> $params  atteso: 'piattoId'
     */
    public function rimuoviDallaBozza(array $params): void
    {
        $tavoloId = $this->richiediTavolo();
        $piattoId = (int)($params['piattoId'] ?? 0);

        $bozza = FOrdine::loadBozzaByTavolo($tavoloId);
        if ($bozza === null || $piattoId <= 0) {
            // Niente da rimuovere: restituisce comunque lo stato corrente.
            $this->rispondiBozza($bozza);
            return;
        }

        // Cerca la riga di quel piatto e rimuovila (l'entity confronta per oggetto).
        foreach ($bozza->getRighe() as $riga) {
            if ($riga->getPiatto()->getId() === $piattoId) {
                $bozza->rimuoviRiga($riga);
            }
        }
        PersistentManager::update($bozza);

        $this->rispondiBozza(FOrdine::loadBozzaByTavolo($tavoloId));
    }

    /**
     * Mostra il carrello condiviso corrente. E' l'operazione che i
     * dispositivi del tavolo richiamano periodicamente (polling) per
     * vedere le aggiunte fatte dagli altri.
     *
     * @param array<string,mixed> $params
     */
    public function mostraBozza(array $params): void
    {
        $tavoloId = $this->richiediTavolo();
        $bozza    = FOrdine::loadBozzaByTavolo($tavoloId);
        $this->rispondiBozza($bozza);
    }

    /**
     * Invia la bozza in cucina: da BOZZA passa a INVIATO.
     * L'entity Ordine::invia() non fa nulla se la bozza e' vuota.
     *
     * @param array<string,mixed> $params
     */
    public function inviaOrdine(array $params): void
    {
        $tavoloId = $this->richiediTavolo();
        $bozza    = FOrdine::loadBozzaByTavolo($tavoloId);

        if ($bozza === null || $bozza->getRighe() === []) {
            $this->rispondiErrore('Non c\'e\' nulla da inviare.');
            return;
        }

        $bozza->invia();                 // BOZZA -> INVIATO (se non vuota)
        PersistentManager::update($bozza);

        // Dopo l'invio non c'e' piu' una bozza aperta: il carrello e' vuoto.
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'ok'      => true,
            'inviato' => true,
            'bozza'   => $this->bozzaInArray(FOrdine::loadBozzaByTavolo($tavoloId)),
        ]);
    }

    /**
     * Il tavolo chiede il conto: alza il flag contoRichiesto e lo salva,
     * cosi' il ristorante lo vede tra i tavoli che hanno chiamato.
     *
     * @param array<string,mixed> $params
     */
    public function richiediConto(array $params): void
    {
        $tavoloId = $this->richiediTavolo();

        $tavolo = FTavolo::load($tavoloId);
        if ($tavolo === null) {
            $this->rispondiErrore('Tavolo non trovato.');
            return;
        }

        $tavolo->richiediConto();        // alza il flag (logica di dominio)
        PersistentManager::update($tavolo);

        // Conto richiesto: confermiamo al JavaScript.
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => true, 'contoRichiesto' => true]);
    }

    /**
     * Operazione di sistema: storico degli ordini gia' inviati del tavolo.
     * Sono tutti gli ordini del tavolo TRANNE la bozza in corso (stato BOZZA).
     * Risponde in JSON: per ogni ordine, le sue righe; piu' il totale generale.
     *
     * @param array<string,mixed> $params
     */
    public function storico(array $params): void
    {
        $tavoloId = $this->richiediTavolo();

        $ordini = FOrdine::loadByTavolo($tavoloId);
        $totaleGenerale = 0.0;
        $out = [];

        foreach ($ordini as $ordine) {
            if ($ordine->getStato() === StatoOrdine::BOZZA) {
                continue; // la bozza non e' "ordinato": e' il carrello aperto
            }
            $righe = [];
            foreach ($ordine->getRighe() as $r) {
                $righe[] = [
                    'nome'      => $r->getPiatto()->getNome(),
                    'quantita'  => $r->getQuantita(),
                    'subtotale' => $r->subtotale(),
                ];
            }
            $totaleOrdine = $ordine->totale();
            $totaleGenerale += $totaleOrdine;
            $out[] = [
                'id'     => (int) $ordine->getId(),
                'stato'  => $ordine->getStato()->value,
                'righe'  => $righe,
                'totale' => $totaleOrdine,
            ];
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'ok'             => true,
            'ordini'         => $out,
            'totaleGenerale' => $totaleGenerale,
        ]);
    }

    // ------------------------------------------------------------------
    //  Guardia di accesso e helper privati
    // ------------------------------------------------------------------

    /**
     * Pretende che ci sia un tavolo loggato. Restituisce l'id del tavolo,
     * preso DALLA SESSIONE (non dalla richiesta). Se non c'e' un tavolo
     * loggato, reindirizza al login del tavolo e termina.
     */
    private function richiediTavolo(): int
    {
        if (!Session::isLoggato() || Session::getRuolo() !== self::RUOLO) {
            $this->redirect('index.php?controller=Login&action=mostraLoginTavolo');
        }
        // A questo punto siamo certi che c'e' un id valido in sessione.
        return (int) Session::getIdUtente();
    }

    /**
     * Risale all'id del ristorante a cui appartiene il tavolo, ricaricando
     * il tavolo dalla foundation (ora l'entity Tavolo porta il ristoranteId).
     */
    private function ristoranteIdDelTavolo(int $tavoloId): int
    {
        $tavolo = FTavolo::load($tavoloId);
        $rid    = $tavolo?->getRistoranteId();

        if ($rid === null) {
            // Difesa: tavolo sparito o senza ristorante. Sessione non piu' valida.
            $this->redirect('index.php?controller=Login&action=mostraLoginTavolo');
        }
        return (int) $rid;
    }

    /**
     * Estrae dai parametri gli id degli allergeni selezionati dal commensale.
     * Accetta sia un array (checkbox multiple) sia valori sporchi: tiene solo
     * gli interi positivi.
     *
     * @param array<string,mixed> $params
     * @return int[]
     */
    private function idAllergeniDaParams(array $params): array
    {
        $grezzi = $params['allergeni'] ?? [];
        if (!is_array($grezzi)) {
            $grezzi = [$grezzi];
        }
        $id = [];
        foreach ($grezzi as $v) {
            $n = (int) $v;
            if ($n > 0) {
                $id[] = $n;
            }
        }
        return $id;
    }

    /**
     * Trasforma una lista di id-allergene negli oggetti Allergene
     * corrispondenti, prendendoli dal catalogo del ristorante (cosi' un id
     * estraneo al ristorante viene semplicemente ignorato).
     *
     * @param int[] $idSelezionati
     * @return Allergene[]
     */
    private function allergeniDaId(int $ristoranteId, array $idSelezionati): array
    {
        $catalogo = FAllergene::loadByRistorante($ristoranteId);
        $insieme  = array_flip($idSelezionati); // per ricerca O(1)

        return array_values(array_filter(
            $catalogo,
            fn(Allergene $a) => $a->getId() !== null && isset($insieme[$a->getId()])
        ));
    }

    /**
     * Raggruppa una lista piatta di piatti per categoria, preservando l'ordine
     * di arrivo (i piatti sono gia' ordinati per posizione). Restituisce una
     * lista di gruppi, ciascuno con la sua Categoria (o null) e i suoi piatti:
     *   [ ['categoria' => Categoria|null, 'piatti' => Piatto[]], ... ]
     * I piatti senza categoria finiscono in un gruppo finale con categoria null.
     *
     * @param Piatto[] $piatti
     * @return array<int, array{categoria: Categoria|null, piatti: Piatto[]}>
     */
    private function raggruppaPerCategoria(array $piatti): array
    {
        $indice    = [];   // id categoria -> posizione nel risultato
        $risultato = [];
        $senzaCat  = [];   // piatti senza categoria, raccolti a parte

        foreach ($piatti as $piatto) {
            $cat = $piatto->getCategoria();
            if ($cat === null) {
                $senzaCat[] = $piatto;
                continue;
            }
            $chiave = (int) $cat->getId();
            if (!isset($indice[$chiave])) {
                $indice[$chiave] = count($risultato);
                $risultato[] = ['categoria' => $cat, 'piatti' => []];
            }
            $risultato[$indice[$chiave]]['piatti'][] = $piatto;
        }

        if ($senzaCat !== []) {
            $risultato[] = ['categoria' => null, 'piatti' => $senzaCat];
        }

        // Ordina i gruppi per posizione della categoria (la posizione 1 per
        // prima). I piatti senza categoria (categoria null) vanno in fondo.
        usort($risultato, function ($a, $b) {
            $pa = $a['categoria'] !== null ? $a['categoria']->getPosizione() : 9999;
            $pb = $b['categoria'] !== null ? $b['categoria']->getPosizione() : 9999;
            $na = $a['categoria'] !== null ? $a['categoria']->getNome() : '';
            $nb = $b['categoria'] !== null ? $b['categoria']->getNome() : '';
            return [$pa, $na] <=> [$pb, $nb];
        });

        return $risultato;
    }

    /** Nome del ristorante dato il suo id (per l'intestazione della pagina). */
    private function nomeRistorante(int $ristoranteId): string
    {
        $r = FRistorante::load($ristoranteId);
        return $r !== null ? $r->getNome() : 'Ristorante';
    }

    // ------------------------------------------------------------------
    //  Risposte JSON per le azioni chiamate dal JavaScript (fetch).
    //  I metodi-azione non reindirizzano piu': rispondono dati che il
    //  JavaScript usa per aggiornare la pagina senza ricaricarla.
    // ------------------------------------------------------------------

    /**
     * Trasforma un Ordine (la bozza) in un array semplice, pronto per il JSON:
     * righe con id piatto, nome, prezzo unitario, quantita' e subtotale, piu'
     * il totale complessivo. Se la bozza e' null, restituisce un carrello vuoto.
     */
    private function bozzaInArray(?Ordine $bozza): array
    {
        $righe = [];
        if ($bozza !== null) {
            foreach ($bozza->getRighe() as $r) {
                $righe[] = [
                    'piattoId'  => (int) $r->getPiatto()->getId(),
                    'nome'      => $r->getPiatto()->getNome(),
                    'prezzo'    => $r->getPrezzoUnitario(),
                    'quantita'  => $r->getQuantita(),
                    'subtotale' => $r->subtotale(),
                ];
            }
        }
        return [
            'righe'  => $righe,
            'totale' => $bozza !== null ? $bozza->totale() : 0.0,
        ];
    }

    /** Invia una risposta JSON di successo con lo stato corrente della bozza. */
    private function rispondiBozza(?Ordine $bozza): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'ok'    => true,
            'bozza' => $this->bozzaInArray($bozza),
        ]);
    }

    /** Invia una risposta JSON di errore con un messaggio per il JavaScript. */
    private function rispondiErrore(string $messaggio): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'errore' => $messaggio]);
    }

    private function errore(string $messaggio): void
    {
        // TODO(presentation): mostrare l'errore nella UI del tavolo.
        echo 'Errore: ' . htmlspecialchars($messaggio, ENT_QUOTES, 'UTF-8');
    }

    private function redirect(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }
}
