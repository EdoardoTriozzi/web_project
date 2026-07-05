<?php
declare(strict_types=1);

namespace Entity;

/**
 * Riga di un ordine: un piatto in una certa quantita'.
 *
 * E' una vera entity (non semplice collegamento) perche' porta dati
 * propri: la quantita' e il prezzo unitario congelato al momento
 * dell'ordine (snapshot). Memorizzare il prezzo qui fa si' che un
 * eventuale cambio di listino futuro non alteri gli ordini gia' fatti.
 *
 * Riferisce il Piatto come oggetto, ma non lo possiede: il piatto
 * continua a esistere anche se questa riga viene rimossa.
 *
 * NOTA: niente stato della singola riga (in_attesa/in_preparazione/
 * consegnato), come da semplificazione decisa: lo stato vive a livello
 * di Ordine intero.
 */
class RigaOrdine
{
    private ?int $id;
    private Piatto $piatto;
    private int $quantita;
    private float $prezzoUnitario;

    public function __construct(
        Piatto $piatto,
        int $quantita = 1,
        ?float $prezzoUnitario = null,
        ?int $id = null
    ) {
        $this->piatto   = $piatto;
        $this->quantita = $quantita;
        // Se non viene passato un prezzo, si fotografa quello attuale del piatto.
        $this->prezzoUnitario = $prezzoUnitario ?? $piatto->getPrezzo();
        $this->id = $id;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getPiatto(): Piatto
    {
        return $this->piatto;
    }

    public function getQuantita(): int
    {
        return $this->quantita;
    }

    public function setQuantita(int $quantita): void
    {
        $this->quantita = $quantita;
    }

    public function getPrezzoUnitario(): float
    {
        return $this->prezzoUnitario;
    }

    /** Subtotale della riga: prezzo congelato per quantita'. */
    public function subtotale(): float
    {
        return $this->prezzoUnitario * $this->quantita;
    }
}
