<?php
declare(strict_types=1);

namespace Foundation;

/**
 *⚠️⚠️⚠️⚠️⚠️⚠️⚠️⚠️⚠️⚠️⚠️⚠️⚠️⚠️⚠️⚠️⚠️⚠️⚠️⚠️⚠️⚠️⚠️⚠️⚠️⚠️⚠️⚠️⚠️⚠️⚠️⚠️⚠️⚠️⚠️⚠️⚠️⚠️⚠️⚠️⚠️*
 *Classe Foundation per la gestione della sessione utente.
 *
 * Corrisponde alla USession / Session elencata tra i servizi pubblici
 * della foundation nelle slide del professore.
 *
 * Il web e' senza memoria: ogni richiesta HTTP e' isolata. La sessione
 * e' lo spazio di memoria sul server, legato a un singolo visitatore,
 * che permette di ricordare informazioni (es. chi e' loggato) tra una
 * pagina e l'altra. PHP la espone con la superglobale $_SESSION; questa
 * classe la incapsula in un punto unico, cosi' il resto dell'app non
 * manipola $_SESSION direttamente.
 *
 * Metodi statici (come le altre classi F): non serve creare oggetti.
 *
 * Distinzione dei dati salvati, coerente con la nostra architettura a
 * ruoli: in sessione teniamo CHI e' loggato (ruolo + id dell'utente),
 * non l'intero oggetto entity. Sara' il control, dato il ruolo e l'id,
 * a ricaricare l'oggetto dalla foundation quando serve.
 */
class Session
{
    /**
     * Avvia la sessione se non e' gia' attiva.
     * Chiamare session_start() due volte produce un warning: questo
     * controllo rende sicuro invocare avvia() sempre, senza rischi.
     */
    public static function avvia(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    // -----------------------------------------------------------------
    //  API generica: imposta / leggi / cancella un valore qualsiasi.
    //  (corrisponde a imposta_valore / leggi_valore / cancella_valore)
    // -----------------------------------------------------------------

    /** Salva un valore in sessione sotto una data chiave. */
    public static function set(string $chiave, mixed $valore): void
    {
        self::avvia();
        $_SESSION[$chiave] = $valore;
    }

    /**
     * Legge un valore dalla sessione. Restituisce $default (null per
     * impostazione predefinita) se la chiave non esiste.
     */
    public static function get(string $chiave, mixed $default = null): mixed
    {
        self::avvia();
        return $_SESSION[$chiave] ?? $default;
    }

    /** Vero se in sessione esiste un valore per quella chiave. */
    public static function has(string $chiave): bool
    {
        self::avvia();
        return isset($_SESSION[$chiave]);
    }

    /** Rimuove un singolo valore dalla sessione. */
    public static function remove(string $chiave): void
    {
        self::avvia();
        unset($_SESSION[$chiave]);
    }

    // -----------------------------------------------------------------
    //  API specifica per l'utente loggato: costruita sopra a quella
    //  generica, rende leggibile il codice del control.
    // -----------------------------------------------------------------

    /**
     * Registra l'utente loggato salvando il suo ruolo e il suo id.
     * $ruolo: 'amministratore' | 'ristorante' | 'cucina' | 'tavolo'.
     */
    public static function login(string $ruolo, int $idUtente): void
    {
        self::set('ruolo', $ruolo);
        self::set('id_utente', $idUtente);
    }

    /** Vero se c'e' un utente loggato (cioe' se sono presenti ruolo e id). */
    public static function isLoggato(): bool
    {
        return self::has('ruolo') && self::has('id_utente');
    }

    /** Il ruolo dell'utente loggato, o null se nessuno e' loggato. */
    public static function getRuolo(): ?string
    {
        return self::get('ruolo');
    }

    /** L'id dell'utente loggato, o null se nessuno e' loggato. */
    public static function getIdUtente(): ?int
    {
        $id = self::get('id_utente');
        return $id === null ? null : (int) $id;
    }

    /**
     * Esegue il logout: cancella tutti i dati e distrugge la sessione.
     * Da chiamare quando l'utente esce.
     */
    public static function logout(): void
    {
        self::avvia();
        $_SESSION = [];          // svuota i dati
        session_destroy();        // distrugge la sessione lato server
    }
}
