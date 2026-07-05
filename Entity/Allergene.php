<?php
declare(strict_types=1);

namespace Entity;

/**
 * Allergene del catalogo di un ristorante.
 *
 * Entity pura: rappresenta l'oggetto in memoria e non sa nulla
 * di come viene salvato (quello e' compito dello strato foundation).
 *
 * L'id e' nullable perche' un oggetto appena creato in memoria non ha
 * ancora un id: lo riceve dal database solo dopo essere stato salvato.
 */
class Allergene
{
    private ?int $id;
    private string $nome;

    public function __construct(string $nome, ?int $id = null)
    {
        $this->nome = $nome;
        $this->id   = $id;
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
}
