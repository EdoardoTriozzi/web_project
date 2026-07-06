<?php
declare(strict_types=1);

namespace Control;

use Foundation\Session;
use Foundation\Presentazione;
use Foundation\FRistorante;
use Foundation\PersistentManager;
use Entity\Ristorante;

/**
 * CAmministratore — classe control del caso d'uso "Amministratore".
 *
 * E' il caso d'uso piu' snello: l'amministratore sta in cima alla gerarchia
 * e gestisce i ristoranti (li genera, li rimuove, li visualizza). Non possiede
 * "genitori", quindi le store dei ristoranti non richiedono un id genitore.
 *
 * Operazioni di sistema (firma uniforme (array $params) per la reflection):
 *   - mostraRistoranti()   -> elenco di tutti i ristoranti
 *   - creaRistorante()     -> crea un nuovo account ristorante (password hashata)
 *   - attivaRistorante()   -> riattiva un ristorante (flag attivo = true)
 *   - disattivaRistorante()-> sospende un ristorante (flag attivo = false)
 *   - eliminaRistorante()  -> rimuove un ristorante (cancellazione a cascata
 *                             di cucine, tavoli, ecc. via vincoli del DB)
 *
 * Tutte passano da richiediAmministratore(), che pretende un amministratore
 * loggato. Qui non serve un controllo di "competenza" come negli altri
 * control: l'amministratore ha visibilita' su TUTTI i ristoranti per
 * definizione, quindi opera su qualunque id valido.
 */
final class CAmministratore
{
    /** Ruolo atteso in sessione per accedere a queste operazioni. */
    private const RUOLO = 'amministratore';

    // ------------------------------------------------------------------
    //  Operazioni di sistema
    // ------------------------------------------------------------------

    /**
     * Elenca tutti i ristoranti della piattaforma.
     *
     * @param array<string,mixed> $params
     */
    public function mostraRistoranti(array $params): void
    {
        $this->richiediAmministratore();

        $datiRistoranti = htmlspecialchars(
            json_encode($this->ristorantiInArray()),
            ENT_QUOTES, 'UTF-8'
        );

        $view = Presentazione::crea();
        $view->assign('ristoranti', $datiRistoranti);
        $view->display('admin/ristoranti.tpl');
    }

    /** Tutti i ristoranti come array semplici per il JSON. */
    private function ristorantiInArray(): array
    {
        $out = [];
        foreach (FRistorante::loadAll() as $r) {
            $out[] = [
                'id'       => (int) $r->getId(),
                'nome'     => $r->getNome(),
                'username' => $r->getUsername(),
                'attivo'   => $r->isAttivo(),
            ];
        }
        usort($out, function ($a, $b) {
            return strnatcasecmp($a['nome'], $b['nome']);
        });
        return $out;
    }

    /** Risposta JSON con la lista aggiornata dei ristoranti. */
    private function rispondiRistoranti(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => true, 'ristoranti' => $this->ristorantiInArray()]);
    }

    /** Risposta JSON di errore. */
    private function rispondiErrore(string $messaggio): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'errore' => $messaggio]);
    }

    /**
     * Crea un nuovo account ristorante. La password arriva in chiaro e viene
     * hashata (setPassword) prima del salvataggio.
     *
     * NOTA (versione minima): non si controlla qui se l'username esista gia'.
     * Si assume username unico. Se in futuro servisse un messaggio pulito in
     * caso di collisione, basta anteporre un FRistorante::loadByUsername e,
     * se trova qualcosa, restituire un errore invece di procedere.
     *
     * @param array<string,mixed> $params  attesi: 'nome', 'username', 'password'
     */
    public function creaRistorante(array $params): void
    {
        $this->richiediAmministratore();

        $nome     = trim((string)($params['nome'] ?? ''));
        $username = trim((string)($params['username'] ?? ''));
        $password = (string)($params['password'] ?? '');

        if ($nome === '' || $username === '' || $password === '') {
            $this->rispondiErrore('Nome, username e password del ristorante sono obbligatori.');
            return;
        }

        // Si costruisce con hash vuoto e poi si imposta la password (che la hasha).
        // attivo = true di default: un ristorante appena creato e' subito operativo.
        $ristorante = new Ristorante($nome, $username, '');
        $ristorante->setPassword($password);

        // I ristoranti non hanno genitore: store senza secondo argomento.
        PersistentManager::store($ristorante);

        $this->rispondiRistoranti();
    }

    /**
     * Riattiva un ristorante sospeso.
     *
     * @param array<string,mixed> $params  atteso: 'ristoranteId'
     */
    public function attivaRistorante(array $params): void
    {
        $this->cambiaAttivazione($params, true);
    }

    /**
     * Sospende un ristorante (resta nel sistema ma non operativo).
     *
     * @param array<string,mixed> $params  atteso: 'ristoranteId'
     */
    public function disattivaRistorante(array $params): void
    {
        $this->cambiaAttivazione($params, false);
    }

    /**
     * Elimina un ristorante. Le entita' figlie (cucine, tavoli, categorie,
     * piatti, allergeni) spariscono in cascata grazie ai vincoli ON DELETE
     * CASCADE definiti nello schema del database.
     *
     * @param array<string,mixed> $params  atteso: 'ristoranteId'
     */
    public function eliminaRistorante(array $params): void
    {
        $this->richiediAmministratore();
        $ristoranteId = (int)($params['ristoranteId'] ?? 0);

        if ($ristoranteId <= 0 || !FRistorante::exist($ristoranteId)) {
            $this->rispondiErrore('Ristorante non trovato.');
            return;
        }

        PersistentManager::delete('Ristorante', $ristoranteId);

        $this->rispondiRistoranti();
    }

    // ------------------------------------------------------------------
    //  Guardia di accesso e helper privati
    // ------------------------------------------------------------------

    /**
     * Pretende un amministratore loggato. Non restituisce l'id (non serve:
     * l'amministratore non e' "genitore" di nulla e opera su tutti i
     * ristoranti). Se non c'e', reindirizza al login.
     */
    private function richiediAmministratore(): void
    {
        if (!Session::isLoggato() || Session::getRuolo() !== self::RUOLO) {
            $this->redirect('index.php?controller=Login&action=mostraLogin');
        }
    }

    /**
     * Logica condivisa di attiva/disattiva: carica il ristorante, ne cambia
     * il flag attivo e salva.
     *
     * @param array<string,mixed> $params  atteso: 'ristoranteId'
     */
    private function cambiaAttivazione(array $params, bool $attivo): void
    {
        $this->richiediAmministratore();
        $ristoranteId = (int)($params['ristoranteId'] ?? 0);

        $ristorante = $ristoranteId > 0 ? FRistorante::load($ristoranteId) : null;
        if ($ristorante === null) {
            $this->rispondiErrore('Ristorante non trovato.');
            return;
        }

        $ristorante->setAttivo($attivo);
        PersistentManager::update($ristorante);

        $this->rispondiRistoranti();
    }

    private function errore(string $messaggio): void
    {
        echo 'Errore: ' . htmlspecialchars($messaggio, ENT_QUOTES, 'UTF-8');
    }

    private function redirect(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }
}
