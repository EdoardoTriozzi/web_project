<?php
declare(strict_types=1);

namespace Control;

use Foundation\Session;
use Foundation\FOrdine;
use Foundation\FPiatto;
use Foundation\FCucina;
use Foundation\FTavolo;
use Foundation\PersistentManager;
use Entity\StatoOrdine;
use Entity\Ordine;
use Entity\Piatto;

/**
 * CCucina — classe control del caso d'uso "Cucina".
 *
 * E' l'altra meta' della comunicazione tavolo-cucina: il tavolo invia gli
 * ordini, la cucina li riceve e li lavora. La cucina vede solo cio' che le
 * compete (gli ordini con piatti a lei assegnati, i piatti a lei assegnati).
 *
 * Operazioni di sistema (firma uniforme (array $params) per la reflection):
 *   - mostraOrdini()      -> gli ordini di competenza (INVIATO o IN_PREPARAZIONE)
 *   - prendiInCarico()    -> un ordine passa da INVIATO a IN_PREPARAZIONE
 *   - segnaConsegnato()   -> un ordine passa da IN_PREPARAZIONE a CONSEGNATO
 *   - mostraPiatti()      -> i piatti di competenza (per gestirne la disponibilita')
 *   - abilitaPiatto()     -> rende un piatto ordinabile
 *   - disabilitaPiatto()  -> rende un piatto non ordinabile (ingrediente finito)
 *
 * Sicurezza a due livelli:
 *   1. richiediCucina() pretende una cucina loggata e ne restituisce l'id
 *      (dalla SESSIONE, mai dalla richiesta).
 *   2. prima di modificare un ordine o un piatto, si verifica che sia DI
 *      COMPETENZA di questa cucina, confrontandolo con cio' che le query
 *      "byCucina" restituiscono. Cosi' una cucina non puo' toccare ordini o
 *      piatti di un'altra passando un id altrui.
 *
 * Transizioni di stato: per scelta di progetto la regola di quali passaggi
 * sono leciti vive QUI nel control (l'entity Ordine espone setStato() ma non
 * metodi di transizione dedicati oltre a invia()). Quindi ogni transizione
 * controlla esplicitamente lo stato di partenza prima di applicare il nuovo.
 */
final class CCucina
{
    /** Ruolo atteso in sessione per accedere a queste operazioni. */
    private const RUOLO = 'cucina';

    // ------------------------------------------------------------------
    //  Ordini
    // ------------------------------------------------------------------

    /**
     * Mostra gli ordini di competenza della cucina: quelli inviati e quelli
     * gia' presi in carico (la foundation filtra per stato INVIATO o
     * IN_PREPARAZIONE e per cucina assegnata ai piatti).
     *
     * @param array<string,mixed> $params
     */
    public function mostraOrdini(array $params): void
    {
        $cucinaId = $this->richiediCucina();

        // Due modi di chiamare questo metodo:
        //  - apertura pagina (browser): vogliamo la view HTML completa
        //  - polling JS (ogni 10s): vuole solo i dati aggiornati in JSON
        // Distinzione tramite il parametro 'formato'.
        if (($params['formato'] ?? '') === 'json') {
            $this->rispondiOrdini($cucinaId);
            return;
        }

        // Apertura pagina: prepara i dati e mostra il template Smarty.
        $datiOrdini   = $this->raccogliOrdini($cucinaId);
        $nomeCucina   = $this->nomeCucina($cucinaId);

        // Dati iniziali per il JavaScript: JSON gia' pronto e reso sicuro per
        // l'attributo HTML (le virgolette diventano entita', cosi' non rompono
        // l'attributo e Smarty non interpreta le graffe del JSON).
        $datiIniziali = htmlspecialchars(
            json_encode(['attivi' => $datiOrdini['attivi'], 'consegnati' => $datiOrdini['consegnati']]),
            ENT_QUOTES, 'UTF-8'
        );

        $view = \Foundation\Presentazione::crea();
        $view->assign('nomeCucina', $nomeCucina);
        $view->assign('datiIniziali', $datiIniziali);
        $view->display('cucina/ordini.tpl');
    }

    /**
     * Raccoglie gli ordini della cucina divisi in "attivi" (inviati e in
     * preparazione) e "consegnati". Ogni ordine e' gia' trasformato in array
     * semplice, pronto sia per la view sia per il JSON.
     *
     * @return array{attivi: array[], consegnati: array[]}
     */
    private function raccogliOrdini(int $cucinaId): array
    {
        // Insieme degli id dei piatti di QUESTA cucina, per marcare nelle righe
        // di ogni ordine quali piatti sono di sua competenza (vanno evidenziati).
        $idMiei = [];
        foreach (FPiatto::loadByCucina($cucinaId) as $p) {
            $idMiei[(int) $p->getId()] = true;
        }

        $attivi = [];
        foreach (FOrdine::loadInviatiByCucina($cucinaId) as $o) {
            $attivi[] = $this->ordineInArray($o, $idMiei);
        }
        $consegnati = [];
        foreach (FOrdine::loadConsegnatiByCucina($cucinaId) as $o) {
            $consegnati[] = $this->ordineInArray($o, $idMiei);
        }
        return ['attivi' => $attivi, 'consegnati' => $consegnati];
    }

    /** Risposta JSON con gli ordini correnti (usata dal polling ogni 10s). */
    private function rispondiOrdini(int $cucinaId): void
    {
        $dati = $this->raccogliOrdini($cucinaId);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => true] + $dati);
    }

    /**
     * Trasforma un Ordine in array semplice per view/JSON: numero tavolo,
     * stato, righe (piatto + quantita'), e quando e' stato creato.
     */
    private function ordineInArray(Ordine $o, array $idMiei = []): array
    {
        $righe = [];
        foreach ($o->getRighe() as $r) {
            $pid = (int) $r->getPiatto()->getId();
            $righe[] = [
                'piattoId' => $pid,
                'nome'     => $r->getPiatto()->getNome(),
                'quantita' => $r->getQuantita(),
                'mia'      => isset($idMiei[$pid]),  // true se il piatto e' di questa cucina
            ];
        }
        return [
            'id'     => (int) $o->getId(),
            'stato'  => $o->getStato()->value,
            'tavolo' => $this->numeroTavoloDiOrdine($o),
            'righe'  => $righe,
            'ora'    => $o->getCreatoIl()->format('H:i'),  // orario di arrivo, es. "12:34"
        ];
    }

    /**
     * Prende in carico un ordine: da INVIATO passa a IN_PREPARAZIONE.
     *
     * @param array<string,mixed> $params  atteso: 'ordineId'
     */
    public function prendiInCarico(array $params): void
    {
        $cucinaId = $this->richiediCucina();
        $ordineId = (int)($params['ordineId'] ?? 0);

        $ordine = $this->ordineDiCompetenza($cucinaId, $ordineId);
        if ($ordine === null) {
            $this->rispondiErrore('Ordine non trovato o non di tua competenza.');
            return;
        }

        // Transizione lecita solo da INVIATO.
        if ($ordine->getStato() !== StatoOrdine::INVIATO) {
            $this->rispondiErrore('L\'ordine non e\' in stato inviato.');
            return;
        }

        $ordine->setStato(StatoOrdine::IN_PREPARAZIONE);
        PersistentManager::update($ordine);

        // Risponde al JavaScript con gli ordini aggiornati.
        $this->rispondiOrdini($cucinaId);
    }

    /**
     * Segna un ordine come consegnato: da IN_PREPARAZIONE passa a CONSEGNATO.
     * Dopo questo passaggio l'ordine esce dalla lista della cucina (CONSEGNATO
     * non rientra nel filtro di loadInviatiByCucina).
     *
     * @param array<string,mixed> $params  atteso: 'ordineId'
     */
    public function segnaConsegnato(array $params): void
    {
        $cucinaId = $this->richiediCucina();
        $ordineId = (int)($params['ordineId'] ?? 0);

        $ordine = $this->ordineDiCompetenza($cucinaId, $ordineId);
        if ($ordine === null) {
            $this->rispondiErrore('Ordine non trovato o non di tua competenza.');
            return;
        }

        // Transizione lecita solo da IN_PREPARAZIONE.
        if ($ordine->getStato() !== StatoOrdine::IN_PREPARAZIONE) {
            $this->rispondiErrore('L\'ordine non e\' in preparazione.');
            return;
        }

        $ordine->setStato(StatoOrdine::CONSEGNATO);
        PersistentManager::update($ordine);

        $this->rispondiOrdini($cucinaId);
    }

    // ------------------------------------------------------------------
    //  Piatti (gestione disponibilita')
    // ------------------------------------------------------------------

    /**
     * Mostra i piatti di competenza della cucina, su cui puo' agire per
     * abilitarli o disabilitarli.
     *
     * @param array<string,mixed> $params
     */
    public function mostraPiatti(array $params): void
    {
        $cucinaId = $this->richiediCucina();
        $piatti   = FPiatto::loadByCucina($cucinaId);

        // Polling/refresh JSON oppure pagina HTML completa.
        if (($params['formato'] ?? '') === 'json') {
            $out = [];
            foreach ($piatti as $p) {
                $out[] = [
                    'id'          => (int) $p->getId(),
                    'nome'        => $p->getNome(),
                    'disponibile' => $p->isDisponibile(),
                ];
            }
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => true, 'piatti' => $out]);
            return;
        }

        $nomeCucina = $this->nomeCucina($cucinaId);

        // Piatti come array semplici per il template.
        $piattiView = [];
        foreach ($piatti as $p) {
            $piattiView[] = [
                'id'          => (int) $p->getId(),
                'nome'        => $p->getNome(),
                'disponibile' => $p->isDisponibile(),
            ];
        }

        $view = \Foundation\Presentazione::crea();
        $view->assign('nomeCucina', $nomeCucina);
        $view->assign('piatti', $piattiView);
        $view->display('cucina/disponibilita.tpl');
    }

    /**
     * Rende un piatto ordinabile.
     *
     * @param array<string,mixed> $params  atteso: 'piattoId'
     */
    public function abilitaPiatto(array $params): void
    {
        $this->cambiaDisponibilita($params, true);
    }

    /**
     * Rende un piatto non ordinabile (es. ingrediente esaurito).
     *
     * @param array<string,mixed> $params  atteso: 'piattoId'
     */
    public function disabilitaPiatto(array $params): void
    {
        $this->cambiaDisponibilita($params, false);
    }

    // ------------------------------------------------------------------
    //  Guardia di accesso e helper privati
    // ------------------------------------------------------------------

    /**
     * Pretende una cucina loggata. Restituisce l'id della cucina, preso
     * DALLA SESSIONE. Se non c'e' una cucina loggata, reindirizza al login.
     */
    private function richiediCucina(): int
    {
        if (!Session::isLoggato() || Session::getRuolo() !== self::RUOLO) {
            $this->redirect('index.php?controller=Login&action=mostraLogin');
        }
        return (int) Session::getIdUtente();
    }

    /** Numero del tavolo a cui appartiene un ordine (per l'intestazione scheda). */
    private function numeroTavoloDiOrdine(Ordine $ordine): string
    {
        $tavolo = FTavolo::load($ordine->getTavoloId());
        return $tavolo !== null ? $tavolo->getNumero() : '?';
    }

    /** Nome della cucina dato il suo id (per l'intestazione della pagina). */
    private function nomeCucina(int $cucinaId): string
    {
        $c = FCucina::load($cucinaId);
        return $c !== null ? $c->getNome() : 'Cucina';
    }

    /**
     * Restituisce l'ordine indicato SOLO se e' tra quelli di competenza della
     * cucina; altrimenti null. E' la verifica che impedisce a una cucina di
     * agire su ordini di un'altra: invece di fidarsi dell'id ricevuto, lo
     * cerca nell'elenco che la foundation riconosce come suo.
     */
    private function ordineDiCompetenza(int $cucinaId, int $ordineId): ?\Entity\Ordine
    {
        if ($ordineId <= 0) {
            return null;
        }
        foreach (FOrdine::loadInviatiByCucina($cucinaId) as $ordine) {
            if ($ordine->getId() === $ordineId) {
                return $ordine;
            }
        }
        return null;
    }

    /**
     * Logica condivisa di abilita/disabilita: verifica che il piatto sia di
     * competenza della cucina, ne cambia la disponibilita' con il metodo di
     * dominio (abilita/disabilita) e salva.
     *
     * @param array<string,mixed> $params  atteso: 'piattoId'
     */
    private function cambiaDisponibilita(array $params, bool $abilita): void
    {
        $cucinaId = $this->richiediCucina();
        $piattoId = (int)($params['piattoId'] ?? 0);

        $piatto = $this->piattoDiCompetenza($cucinaId, $piattoId);
        if ($piatto === null) {
            $this->rispondiErrore('Piatto non trovato o non di tua competenza.');
            return;
        }

        // Metodi di dominio dell'entity Piatto.
        if ($abilita) {
            $piatto->abilita();
        } else {
            $piatto->disabilita();
        }
        PersistentManager::update($piatto);

        // Risponde al JavaScript con i piatti aggiornati.
        $out = [];
        foreach (FPiatto::loadByCucina($cucinaId) as $p) {
            $out[] = ['id' => (int) $p->getId(), 'nome' => $p->getNome(), 'disponibile' => $p->isDisponibile()];
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => true, 'piatti' => $out]);
    }

    /**
     * Restituisce il piatto indicato SOLO se e' di competenza della cucina;
     * altrimenti null. Stessa difesa di ordineDiCompetenza, applicata ai piatti.
     */
    private function piattoDiCompetenza(int $cucinaId, int $piattoId): ?\Entity\Piatto
    {
        if ($piattoId <= 0) {
            return null;
        }
        foreach (FPiatto::loadByCucina($cucinaId) as $piatto) {
            if ($piatto->getId() === $piattoId) {
                return $piatto;
            }
        }
        return null;
    }

    /** Risposta JSON di errore per le azioni chiamate via fetch dal JavaScript. */
    private function rispondiErrore(string $messaggio): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'errore' => $messaggio]);
    }

    private function errore(string $messaggio): void
    {
        // TODO(presentation): mostrare l'errore nella UI della cucina.
        echo 'Errore: ' . htmlspecialchars($messaggio, ENT_QUOTES, 'UTF-8');
    }

    private function redirect(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }
}
