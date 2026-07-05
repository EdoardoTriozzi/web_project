<?php
declare(strict_types=1);

namespace Entity;

/**
 * Menu di un ristorante.
 *
 * Nel modello finale e' una VISTA di sola lettura per il tavolo: le
 * operazioni di gestione (aggiungere/rimuovere categorie e piatti,
 * riordinare) stanno sul Ristorante, non qui. Il Menu si limita a
 * presentare i piatti e a filtrarli.
 *
 * Tiene in memoria la collezione di piatti su cui opera; come questa
 * venga popolata dal database e' compito della foundation.
 */
class Menu
{
    /** @var Piatto[] */
    private array $piatti;

    /** @param Piatto[] $piatti */
    public function __construct(array $piatti = [])
    {
        $this->piatti = $piatti;
    }

    /** @return Piatto[] tutti i piatti del menu */
    public function getPiatti(): array
    {
        return $this->piatti;
    }

    /** @return Piatto[] solo i piatti attualmente disponibili */
    public function piattiDisponibili(): array
    {
        return array_values(array_filter(
            $this->piatti,
            fn(Piatto $p) => $p->isDisponibile()
        ));
    }

    /**
     * Filtro di ESCLUSIONE per allergeni.
     * Dato un insieme di allergeni da evitare (scelti dal commensale),
     * restituisce i soli piatti disponibili che NON contengono nessuno
     * di quegli allergeni.
     *
     * @param Allergene[] $allergeniDaEscludere
     * @return Piatto[]
     */
    public function filtra(array $allergeniDaEscludere): array
    {
        return array_values(array_filter(
            $this->piattiDisponibili(),
            function (Piatto $piatto) use ($allergeniDaEscludere) {
                foreach ($allergeniDaEscludere as $allergene) {
                    if ($piatto->contieneAllergene($allergene)) {
                        return false; // il piatto cade: contiene un allergene da evitare
                    }
                }
                return true; // nessun allergene vietato presente: il piatto resta
            }
        ));
    }
}
