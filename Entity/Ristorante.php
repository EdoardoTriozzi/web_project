<?php
declare(strict_types=1);

namespace Entity;

/**
 * Ristorante: entita' centrale del sistema. E' un Utente.
 *
 * Possiede (composizione) cucine, tavoli, categorie, allergeni e il
 * proprio menu. Qui l'entity tiene questi insiemi IN MEMORIA e offre
 * i metodi per manipolare le collezioni (aggiungere/rimuovere un
 * oggetto gia' costruito dalla lista).
 *
 * Distinzione importante rispetto al class diagram, dovuta agli strati:
 * metodi come "aggiungiTavolo", "piattiPiuVenduti", "statistiche",
 * "generaPasswordTavolo" implicano persistenza o calcoli sul database.
 * La loro logica applicativa vive nel CONTROLLO (che usa la FOUNDATION
 * per leggere/scrivere). Questa classe espone percio' operazioni di
 * dominio "pure" sulle collezioni gia' caricate, senza toccare il DB.
 */
class Ristorante extends Utente
{
    private string $nome;
    private bool $attivo;

    /** @var Cucina[] */
    private array $cucine;
    /** @var Tavolo[] */
    private array $tavoli;
    /** @var Categoria[] */
    private array $categorie;
    /** @var Allergene[] */
    private array $allergeni;
    /** @var Piatto[] */
    private array $piatti;

    public function __construct(
        string $nome,
        string $username,
        string $passwordHash,
        bool $attivo = true,
        ?int $id = null
    ) {
        parent::__construct($username, $passwordHash, $id);
        $this->nome      = $nome;
        $this->attivo    = $attivo;
        $this->cucine    = [];
        $this->tavoli    = [];
        $this->categorie = [];
        $this->allergeni = [];
        $this->piatti    = [];
    }

    public function getNome(): string
    {
        return $this->nome;
    }

    public function setNome(string $nome): void
    {
        $this->nome = $nome;
    }

    public function isAttivo(): bool
    {
        return $this->attivo;
    }

    public function setAttivo(bool $attivo): void
    {
        $this->attivo = $attivo;
    }

    // --- Cucine ---------------------------------------------------

    /** @return Cucina[] */
    public function getCucine(): array
    {
        return $this->cucine;
    }

    public function aggiungiCucina(Cucina $cucina): void
    {
        $this->cucine[] = $cucina;
    }

    public function rimuoviCucina(Cucina $cucina): void
    {
        $this->cucine = array_values(array_filter(
            $this->cucine,
            fn(Cucina $c) => $c->getId() !== $cucina->getId()
        ));
    }

    // --- Tavoli ---------------------------------------------------

    /** @return Tavolo[] */
    public function getTavoli(): array
    {
        return $this->tavoli;
    }

    public function aggiungiTavolo(Tavolo $tavolo): void
    {
        $this->tavoli[] = $tavolo;
    }

    public function rimuoviTavolo(Tavolo $tavolo): void
    {
        $this->tavoli = array_values(array_filter(
            $this->tavoli,
            fn(Tavolo $t) => $t->getId() !== $tavolo->getId()
        ));
    }

    // --- Categorie ------------------------------------------------

    /** @return Categoria[] */
    public function getCategorie(): array
    {
        return $this->categorie;
    }

    public function aggiungiCategoria(Categoria $categoria): void
    {
        $this->categorie[] = $categoria;
    }

    public function rimuoviCategoria(Categoria $categoria): void
    {
        $this->categorie = array_values(array_filter(
            $this->categorie,
            fn(Categoria $c) => $c->getId() !== $categoria->getId()
        ));
    }

    // --- Allergeni (catalogo) -------------------------------------

    /** @return Allergene[] */
    public function getAllergeni(): array
    {
        return $this->allergeni;
    }

    public function aggiungiAllergene(Allergene $allergene): void
    {
        $this->allergeni[] = $allergene;
    }

    public function rimuoviAllergene(Allergene $allergene): void
    {
        $this->allergeni = array_values(array_filter(
            $this->allergeni,
            fn(Allergene $a) => $a->getId() !== $allergene->getId()
        ));
    }


    // --- Piatti (catalogo/menu del ristorante) -------------------

    /** @return Piatto[] */
    public function getPiatti(): array
    {
        return $this->piatti;
    }

    public function aggiungiPiatto(Piatto $piatto): void
    {
        foreach ($this->piatti as $p) {
            if ($piatto->getId() !== null && $p->getId() === $piatto->getId()) {
                return; // gia' presente
            }

            if ($piatto->getId() === null && $p === $piatto) {
                return; // stesso oggetto gia' presente
            }
        }

        $this->piatti[] = $piatto;
    }

    public function rimuoviPiatto(Piatto $piatto): void
    {
        $this->piatti = array_values(array_filter(
            $this->piatti,
            function (Piatto $p) use ($piatto): bool {
                if ($piatto->getId() !== null) {
                    return $p->getId() !== $piatto->getId();
                }

                return $p !== $piatto;
            }
        ));
    }

}
