<?php
declare(strict_types=1);

namespace Entity;

/**
 * Piatto del menu.
 *
 * Entity pura, ma "ricca": oltre ai dati propri tiene i riferimenti
 * agli oggetti collegati (Categoria, Cucina) e la collezione dei
 * propri Allergeni, in modo fedele alle associazioni del class diagram.
 *
 * Scelte di modellazione:
 *  - $categoria e $cucina sono oggetti nullable (molteplicita 0..1):
 *    un piatto puo' non avere ancora categoria o cucina assegnata.
 *  - $allergeni e' una collezione di oggetti Allergene (relazione
 *    molti-a-molti "contiene").
 *  - $etichetta e' un badge libero (es. "Consigliato") impostato dal
 *    ristorante; sostituisce i vecchi flag booleani e il campo consigliato.
 *  - $immagine e' il percorso del file immagine (o null): l'entity non
 *    sa nulla di upload, conosce solo dove sta la sua immagine.
 */
class Piatto
{
    private ?int $id;
    private string $nome;
    private ?string $descrizione;
    private float $prezzo;
    private ?string $immagine;
    private ?string $etichetta;
    private int $posizione;
    private bool $disponibile;

    private ?Categoria $categoria;
    private ?Cucina $cucina;

    /** @var Allergene[] */
    private array $allergeni;

    public function __construct(
        string $nome,
        float $prezzo = 0.0,
        ?string $descrizione = null,
        ?string $immagine = null,
        ?string $etichetta = null,
        int $posizione = 0,
        bool $disponibile = true,
        ?Categoria $categoria = null,
        ?Cucina $cucina = null,
        ?int $id = null
    ) {
        $this->nome        = $nome;
        $this->prezzo      = $prezzo;
        $this->descrizione = $descrizione;
        $this->immagine    = $immagine;
        $this->etichetta   = $etichetta;
        $this->posizione   = $posizione;
        $this->disponibile = $disponibile;
        $this->categoria   = $categoria;
        $this->cucina      = $cucina;
        $this->id          = $id;
        $this->allergeni   = [];
    }

    // --- Identita' e dati di base ---------------------------------

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

    public function getDescrizione(): ?string
    {
        return $this->descrizione;
    }

    public function setDescrizione(?string $descrizione): void
    {
        $this->descrizione = $descrizione;
    }

    public function getPrezzo(): float
    {
        return $this->prezzo;
    }

    public function setPrezzo(float $prezzo): void
    {
        $this->prezzo = $prezzo;
    }

    public function getImmagine(): ?string
    {
        return $this->immagine;
    }

    public function setImmagine(?string $immagine): void
    {
        $this->immagine = $immagine;
    }

    public function getPosizione(): int
    {
        return $this->posizione;
    }

    /** Cambia la posizione del piatto dentro la sua categoria. */
    public function setPosizione(int $posizione): void
    {
        $this->posizione = $posizione;
    }

    // --- Etichetta ------------------------------------------------

    public function getEtichetta(): ?string
    {
        return $this->etichetta;
    }

    /** Imposta il badge del piatto (es. "Consigliato", "Novita'"). */
    public function setEtichetta(?string $etichetta): void
    {
        $this->etichetta = $etichetta;
    }

    // --- Disponibilita' -------------------------------------------

    public function isDisponibile(): bool
    {
        return $this->disponibile;
    }

    /** Rende il piatto ordinabile. */
    public function abilita(): void
    {
        $this->disponibile = true;
    }

    /** Rende il piatto non ordinabile (es. ingrediente finito). */
    public function disabilita(): void
    {
        $this->disponibile = false;
    }

    // --- Categoria e Cucina (riferimenti a oggetti) ---------------

    public function getCategoria(): ?Categoria
    {
        return $this->categoria;
    }

    public function setCategoria(?Categoria $categoria): void
    {
        $this->categoria = $categoria;
    }

    public function getCucina(): ?Cucina
    {
        return $this->cucina;
    }

    /** Assegna la cucina che prepara questo piatto. */
    public function assegnaCucina(?Cucina $cucina): void
    {
        $this->cucina = $cucina;
    }

    // --- Allergeni (collezione molti-a-molti) ---------------------

    /** @return Allergene[] */
    public function getAllergeni(): array
    {
        return $this->allergeni;
    }

    /** Associa un allergene al piatto, evitando duplicati. */
    public function associaAllergene(Allergene $allergene): void
    {
        foreach ($this->allergeni as $a) {
            if ($a->getId() !== null && $a->getId() === $allergene->getId()) {
                return; // gia' presente
            }
        }
        $this->allergeni[] = $allergene;
    }

    /** Rimuove un allergene dal piatto (confronto per id). */
    public function rimuoviAllergene(Allergene $allergene): void
    {
        $this->allergeni = array_values(array_filter(
            $this->allergeni,
            fn(Allergene $a) => $a->getId() !== $allergene->getId()
        ));
    }

    /**
     * Vero se il piatto contiene l'allergene indicato.
     * E' il mattone su cui si appoggia il filtro di esclusione del menu.
     */
    public function contieneAllergene(Allergene $allergene): bool
    {
        foreach ($this->allergeni as $a) {
            if ($a->getId() === $allergene->getId()) {
                return true;
            }
        }
        return false;
    }
}
