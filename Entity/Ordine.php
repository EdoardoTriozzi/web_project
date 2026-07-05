<?php
declare(strict_types=1);

namespace Entity;

use DateTimeImmutable;

/**
 * Ordine emesso da un tavolo.
 *
 * Cuore della comunicazione tavolo-cucina. Nasce in stato BOZZA
 * (il carrello condiviso) e, una volta inviato, passa a INVIATO e
 * viene visto dalla cucina.
 *
 * Contiene le sue righe (composizione): le RigaOrdine non hanno vita
 * propria fuori dall'ordine. La logica di totale e gestione righe vive
 * qui, secondo il principio dell'information expert (l'ordine conosce
 * le sue righe, quindi e' lui a saperle sommare).
 *
 * Coerenza con gli strati: i metodi qui operano sugli oggetti in
 * memoria. Persistere l'ordine e le righe nel database e' compito
 * della foundation; questa classe non contiene query.
 */
class Ordine
{
    private ?int $id;
    private StatoOrdine $stato;
    private DateTimeImmutable $creatoIl;
    /** Tavolo a cui l'ordine appartiene. Nullable per retrocompatibilita':
     *  una bozza appena creata in memoria puo' non averlo ancora. La foundation
     *  lo valorizza quando carica l'ordine dal database. */
    private ?int $tavoloId;

    /** @var RigaOrdine[] */
    private array $righe;

    public function __construct(
        StatoOrdine $stato = StatoOrdine::BOZZA,
        ?DateTimeImmutable $creatoIl = null,
        ?int $id = null,
        ?int $tavoloId = null
    ) {
        $this->stato    = $stato;
        $this->creatoIl = $creatoIl ?? new DateTimeImmutable();
        $this->id       = $id;
        $this->tavoloId = $tavoloId;
        $this->righe    = [];
    }

    public function getTavoloId(): ?int
    {
        return $this->tavoloId;
    }

    public function setTavoloId(?int $tavoloId): void
    {
        $this->tavoloId = $tavoloId;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getStato(): StatoOrdine
    {
        return $this->stato;
    }

    public function setStato(StatoOrdine $stato): void
    {
        $this->stato = $stato;
    }

    public function getCreatoIl(): DateTimeImmutable
    {
        return $this->creatoIl;
    }

    // --- Gestione delle righe -------------------------------------

    /** @return RigaOrdine[] */
    public function getRighe(): array
    {
        return $this->righe;
    }

    /**
     * Aggiunge un piatto all'ordine. Se il piatto e' gia' presente,
     * incrementa la quantita' della riga esistente invece di duplicarla.
     */
    public function aggiungiPiatto(Piatto $piatto, int $quantita = 1): void
    {
        foreach ($this->righe as $riga) {
            if ($riga->getPiatto()->getId() === $piatto->getId()) {
                $riga->setQuantita($riga->getQuantita() + $quantita);
                return;
            }
        }
        $this->righe[] = new RigaOrdine($piatto, $quantita);
    }

    /**
     * Aggiunge una riga gia' costruita all'ordine.
     * A differenza di aggiungiPiatto(), non ricalcola il prezzo: la riga
     * conserva il proprio prezzo unitario. Utile quando la riga viene
     * ricostruita dalla persistenza preservando il prezzo storico (snapshot).
     */
    public function aggiungiRiga(RigaOrdine $riga): void
    {
        $this->righe[] = $riga;
    }

    /** Rimuove una riga dall'ordine (confronto per identita' dell'oggetto). */
    public function rimuoviRiga(RigaOrdine $riga): void
    {
        $this->righe = array_values(array_filter(
            $this->righe,
            fn(RigaOrdine $r) => $r !== $riga
        ));
    }

    // --- Operazioni di dominio ------------------------------------

    /**
     * Invia l'ordine in cucina: da BOZZA passa a INVIATO.
     * Non fa nulla se l'ordine non e' una bozza o se e' vuoto.
     */
    public function invia(): void
    {
        if ($this->stato === StatoOrdine::BOZZA && count($this->righe) > 0) {
            $this->stato = StatoOrdine::INVIATO;
        }
    }

    /** Totale dell'ordine: somma dei subtotali delle righe. */
    public function totale(): float
    {
        $totale = 0.0;
        foreach ($this->righe as $riga) {
            $totale += $riga->subtotale();
        }
        return $totale;
    }
}
