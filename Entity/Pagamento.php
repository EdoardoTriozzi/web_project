<?php
declare(strict_types=1);

namespace Entity;

use DateTimeImmutable;

/**
 * Pagamento — un conto saldato da un tavolo.
 *
 * E' lo "scontrino" storico: registra quale ristorante, quale numero di
 * tavolo (congelato come testo), il totale incassato, il momento del
 * pagamento, e il dettaglio delle voci pagate (PagamentoVoce).
 *
 * Una volta creato, e' un dato immutabile: rappresenta un fatto avvenuto.
 * Da questi oggetti si calcolano le statistiche delle vendite.
 */
class Pagamento
{
    private ?int $id;
    private ?int $ristoranteId;
    private string $numeroTavolo;
    private DateTimeImmutable $pagatoIl;
    /** @var PagamentoVoce[] */
    private array $voci;

    public function __construct(
        string $numeroTavolo,
        ?int $ristoranteId = null,
        ?DateTimeImmutable $pagatoIl = null,
        ?int $id = null
    ) {
        $this->numeroTavolo = $numeroTavolo;
        $this->ristoranteId = $ristoranteId;
        $this->pagatoIl     = $pagatoIl ?? new DateTimeImmutable();
        $this->id           = $id;
        $this->voci         = [];
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getRistoranteId(): ?int
    {
        return $this->ristoranteId;
    }

    public function getNumeroTavolo(): string
    {
        return $this->numeroTavolo;
    }

    public function getPagatoIl(): DateTimeImmutable
    {
        return $this->pagatoIl;
    }

    /** @return PagamentoVoce[] */
    public function getVoci(): array
    {
        return $this->voci;
    }

    public function aggiungiVoce(PagamentoVoce $voce): void
    {
        $this->voci[] = $voce;
    }

    /** Totale del pagamento: somma dei subtotali delle voci. */
    public function totale(): float
    {
        $somma = 0.0;
        foreach ($this->voci as $voce) {
            $somma += $voce->subtotale();
        }
        return $somma;
    }
}
