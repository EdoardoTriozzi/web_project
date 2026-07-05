<?php
declare(strict_types=1);

namespace Entity;

/**
 * PagamentoVoce — una riga di un pagamento: un piatto pagato in una certa
 * quantita' a un certo prezzo.
 *
 * A differenza di RigaOrdine, questa e' una voce STORICA: "congela" anche
 * il NOME del piatto (oltre al prezzo), cosi' lo scontrino resta leggibile
 * anche se il piatto venisse poi rinominato o eliminato dal menu.
 *
 * piatto_id e' opzionale: serve a raggruppare le statistiche, ma puo'
 * diventare null se il piatto sparisce dall'anagrafica.
 */
class PagamentoVoce
{
    private ?int $id;
    private ?int $piattoId;
    private string $nomePiatto;
    private int $quantita;
    private float $prezzoUnitario;

    public function __construct(
        string $nomePiatto,
        int $quantita,
        float $prezzoUnitario,
        ?int $piattoId = null,
        ?int $id = null
    ) {
        $this->nomePiatto     = $nomePiatto;
        $this->quantita       = $quantita;
        $this->prezzoUnitario = $prezzoUnitario;
        $this->piattoId       = $piattoId;
        $this->id             = $id;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getPiattoId(): ?int
    {
        return $this->piattoId;
    }

    public function getNomePiatto(): string
    {
        return $this->nomePiatto;
    }

    public function getQuantita(): int
    {
        return $this->quantita;
    }

    public function getPrezzoUnitario(): float
    {
        return $this->prezzoUnitario;
    }

    /** Subtotale della voce: prezzo congelato per quantita'. */
    public function subtotale(): float
    {
        return $this->prezzoUnitario * $this->quantita;
    }
}
