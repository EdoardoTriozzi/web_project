<?php
declare(strict_types=1);

namespace Entity;

/**
 * Tavolo del ristorante. E' un Utente, ma con una particolarita':
 * la sua "password" e' una credenziale di sessione CONDIVISA, con cui
 * piu' dispositivi si collegano allo stesso tavolo. Per questo la
 * password puo' essere rigenerata dal ristorante e qui e' nullable
 * (un tavolo libero puo' non averne una attiva).
 *
 * Stato e conto:
 *  - $stato: libero / occupato (enum StatoTavolo).
 *  - $contoRichiesto: flag alzato da richiediConto(); sostituisce
 *    l'entita' RichiestaConto, come deciso nell'OOA. Il ristorante
 *    legge questo flag per sapere quali tavoli hanno chiamato.
 *
 * La password e' nullable, quindi il costruttore di Utente (che vuole
 * una stringa) viene chiamato passando '' quando non c'e' password,
 * e qui sotto gestiamo esplicitamente il caso "nessuna password".
 */
class Tavolo extends Utente
{
    private string $numero;
    private StatoTavolo $stato;
    private int $coperti;
    private bool $contoRichiesto;
    /**
     * Id del ristorante a cui il tavolo appartiene.
     * Nullable per coerenza con $id: un tavolo appena creato in memoria, non
     * ancora salvato, puo' non avere ancora il ristorante associato. Dopo il
     * caricamento dal database (FTavolo) e' sempre valorizzato.
     */
    private ?int $ristoranteId;

    public function __construct(
        string $numero,
        ?string $passwordHash = null,
        StatoTavolo $stato = StatoTavolo::LIBERO,
        int $coperti = 0,
        bool $contoRichiesto = false,
        ?int $id = null,
        ?int $ristoranteId = null
    ) {
        // Il "username" del tavolo non e' usato per il login come per
        // gli altri utenti: identifichiamo il tavolo per numero. Passiamo
        // il numero come username a Utente per coerenza della superclasse.
        parent::__construct($numero, $passwordHash ?? '', $id);
        $this->numero         = $numero;
        $this->stato          = $stato;
        $this->coperti        = $coperti;
        $this->contoRichiesto = $contoRichiesto;
        $this->ristoranteId   = $ristoranteId;
    }

    public function getNumero(): string
    {
        return $this->numero;
    }

    public function setNumero(string $numero): void
    {
        $this->numero = $numero;
    }
    /**
     * Id del ristorante a cui appartiene il tavolo (o null se non ancora
     * associato). Serve al controllo per caricare il menu e il catalogo
     * allergeni del ristorante giusto.
     */
    public function getRistoranteId(): ?int
    {
        return $this->ristoranteId;
    }

    // --- Stato del tavolo -----------------------------------------

    public function getStato(): StatoTavolo
    {
        return $this->stato;
    }

    public function setStato(StatoTavolo $stato): void
    {
        $this->stato = $stato;
    }

    public function isOccupato(): bool
    {
        return $this->stato === StatoTavolo::OCCUPATO;
    }

    // --- Coperti --------------------------------------------------

    public function getCoperti(): int
    {
        return $this->coperti;
    }

    public function setCoperti(int $coperti): void
    {
        $this->coperti = $coperti;
    }

    // --- Richiesta conto (flag) -----------------------------------

    public function isContoRichiesto(): bool
    {
        return $this->contoRichiesto;
    }

    /** Il tavolo chiede il conto: alza il flag. */
    public function richiediConto(): void
    {
        $this->contoRichiesto = true;
    }

    /** Il ristorante ha evaso la richiesta: abbassa il flag. */
    public function azzeraRichiestaConto(): void
    {
        $this->contoRichiesto = false;
    }

    // --- Password condivisa ---------------------------------------

    /** Vero se il tavolo ha attualmente una password di accesso attiva. */
    public function haPassword(): bool
    {
        return $this->passwordHash !== '';
    }
}
