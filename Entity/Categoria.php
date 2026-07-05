<?php
declare(strict_types=1);

namespace Entity;

/**
 * Categoria del menu (es. Antipasti, Primi).
 *
 * Possiede l'ordinamento delle proprie voci tramite "posizione".
 * setPosizione() e' il metodo che il riordino (trascinamento)
 * invoca; nell'architettura e' chiamato solo dal Ristorante, ma il
 * controllo di chi puo' farlo vive nello strato di controllo/UI,
 * non dentro l'entity (che resta un semplice contenitore di dati).
 */
class Categoria
{
    private ?int $id;
    private string $nome;
    private int $posizione;

    public function __construct(string $nome, int $posizione = 0, ?int $id = null)
    {
        $this->nome      = $nome;
        $this->posizione = $posizione;
        $this->id        = $id;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getNome(): string
    {
        return $this->nome;
    }

    public function setNome(string $nome): void
    {
        $this->nome = $nome;
    }

    public function getPosizione(): int
    {
        return $this->posizione;
    }

    /** Cambia la posizione della categoria nell'ordinamento del menu. */
    public function setPosizione(int $posizione): void
    {
        $this->posizione = $posizione;
    }
}
