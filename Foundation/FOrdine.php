<?php
declare(strict_types=1);

namespace Foundation;

use Entity\Ordine;
use Entity\StatoOrdine;
use DateTimeImmutable;

/**
 * Classe Foundation per l'entity Ordine. Conosce la tabella "ordini" e,
 * tramite le righe, la tabella "ordine_piatti".
 *
 * L'ordine contiene RigaOrdine (composizione): in lettura ricostruiamo le
 * righe con una seconda query; in scrittura le sincronizziamo. Ogni riga
 * riferisce un Piatto, che ricarichiamo tramite FPiatto.
 *
 * Lo stato e' un enum StatoOrdine (bozza/inviato/...): in scrittura si salva
 * $stato->value, in lettura si ricostruisce con StatoOrdine::from(...).
 *
 * Metodi specifici:
 *  - loadByTavolo: tutti gli ordini di un tavolo;
 *  - loadBozzaByTavolo: l'ordine in BOZZA di un tavolo (il carrello condiviso);
 *  - loadInviatiByCucina: gli ordini con righe di competenza di una cucina.
 */
class FOrdine
{
    private const TABELLA = 'ordini';

    public static function exist(int $id): bool
    {
        $pdo  = FConnectionDB::getConnection();
        $stmt = $pdo->prepare('SELECT id FROM ' . self::TABELLA . ' WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() !== false;
    }

    public static function load(int $id): ?Ordine
    {
        $pdo  = FConnectionDB::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM ' . self::TABELLA . ' WHERE id = ?');
        $stmt->execute([$id]);
        $riga = $stmt->fetch();
        return $riga === false ? null : self::creaDaRiga($riga);
    }

    /**
     * Metodo specifico: tutti gli ordini di un tavolo (storico della tavolata).
     * @return Ordine[]
     */
    public static function loadByTavolo(int $tavoloId): array
    {
        $pdo  = FConnectionDB::getConnection();
        $stmt = $pdo->prepare(
            'SELECT * FROM ' . self::TABELLA . ' WHERE tavolo_id = ? ORDER BY creato_il'
        );
        $stmt->execute([$tavoloId]);
        return self::costruisciLista($stmt->fetchAll());
    }

    /**
     * Metodo specifico: l'ordine in BOZZA di un tavolo, cioe' il carrello
     * condiviso ancora aperto. Restituisce null se non c'e' una bozza.
     * (Convenzione: un tavolo ha al piu' una bozza aperta alla volta.)
     */
    public static function loadBozzaByTavolo(int $tavoloId): ?Ordine
    {
        $pdo  = FConnectionDB::getConnection();
        $stmt = $pdo->prepare(
            'SELECT * FROM ' . self::TABELLA . '
             WHERE tavolo_id = ? AND stato = ?
             ORDER BY creato_il DESC LIMIT 1'
        );
        $stmt->execute([$tavoloId, StatoOrdine::BOZZA->value]);
        $riga = $stmt->fetch();
        return $riga === false ? null : self::creaDaRiga($riga);
    }

    /**
     * Metodo specifico: gli ordini che contengono almeno una riga il cui
     * piatto e' assegnato alla cucina indicata, e che sono gia' stati inviati
     * (stato inviato o in_preparazione). E' cio' che la cucina vede.
     * @return Ordine[]
     */
    public static function loadInviatiByCucina(int $cucinaId): array
    {
        $pdo  = FConnectionDB::getConnection();
        $stmt = $pdo->prepare(
            'SELECT DISTINCT o.*
               FROM ordini o
               JOIN ordine_piatti op ON op.ordine_id = o.id
               JOIN piatti p          ON p.id = op.piatto_id
              WHERE p.cucina_id = ?
                AND o.stato IN (?, ?)
              ORDER BY o.creato_il'
        );
        $stmt->execute([
            $cucinaId,
            StatoOrdine::INVIATO->value,
            StatoOrdine::IN_PREPARAZIONE->value,
        ]);
        return self::costruisciLista($stmt->fetchAll());
    }

    /**
     * Ordini CONSEGNATI di competenza di una cucina (contengono almeno un
     * piatto assegnato a quella cucina). Servono a popolare la colonna
     * "Consegnati" del tabellone: ordini gia' pronti ma non ancora chiusi.
     * Gli ordini CHIUSI sono esclusi (sono stati "puliti" e non si rivedono).
     *
     * @return Ordine[]
     */
    public static function loadConsegnatiByCucina(int $cucinaId): array
    {
        $pdo  = FConnectionDB::getConnection();
        $stmt = $pdo->prepare(
            'SELECT DISTINCT o.*
               FROM ordini o
               JOIN ordine_piatti op ON op.ordine_id = o.id
               JOIN piatti p          ON p.id = op.piatto_id
              WHERE p.cucina_id = ?
                AND o.stato = ?
              ORDER BY o.creato_il'
        );
        $stmt->execute([$cucinaId, StatoOrdine::CONSEGNATO->value]);
        return self::costruisciLista($stmt->fetchAll());
    }

    /**
     * Metodo specifico per le statistiche del ristorante: conta quante unita'
     * di ciascun piatto sono state vendute, considerando solo gli ordini gia'
     * usciti dalla bozza (tutto tranne lo stato BOZZA: i carrelli aperti non
     * sono vendite). Risultato ordinato dal piu' venduto.
     *
     * NOTA: se volessi contare solo cio' che e' stato davvero consegnato,
     * basta cambiare la condizione "stato <> bozza" in "stato = consegnato".
     *
     * Restituisce un array di righe associative, una per piatto venduto:
     *   ['piatto_id' => int, 'nome' => string, 'quantita_totale' => int]
     * (Non costruisce oggetti Piatto: e' un dato di reportistica aggregato,
     * non il caricamento di un'entity.)
     *
     * @return array<int, array{piatto_id:int, nome:string, quantita_totale:int}>
     */
    public static function conteggioVenditeByRistorante(int $ristoranteId): array
    {
        $pdo  = FConnectionDB::getConnection();
        $stmt = $pdo->prepare(
            'SELECT p.id AS piatto_id, p.nome AS nome, SUM(op.quantita) AS quantita_totale
               FROM ordine_piatti op
               JOIN piatti p ON p.id = op.piatto_id
               JOIN ordini o ON o.id = op.ordine_id
              WHERE p.ristorante_id = ?
                AND o.stato <> ?
              GROUP BY p.id, p.nome
              ORDER BY quantita_totale DESC, p.nome'
        );
        $stmt->execute([$ristoranteId, StatoOrdine::BOZZA->value]);

        $risultato = [];
        foreach ($stmt->fetchAll() as $riga) {
            $risultato[] = [
                'piatto_id'       => (int) $riga['piatto_id'],
                'nome'            => (string) $riga['nome'],
                'quantita_totale' => (int) $riga['quantita_totale'],
            ];
        }
        return $risultato;
    }

    public static function store(Ordine $o, int $tavoloId): bool
    {
        $pdo  = FConnectionDB::getConnection();
        $stmt = $pdo->prepare(
            'INSERT INTO ' . self::TABELLA . ' (tavolo_id, stato, creato_il) VALUES (?, ?, ?)'
        );
        $esito = $stmt->execute([
            $tavoloId,
            $o->getStato()->value,
            $o->getCreatoIl()->format('Y-m-d H:i:s'),
        ]);
        if ($esito) {
            $o->setId((int) $pdo->lastInsertId());
            self::sincronizzaRighe($o);
        }
        return $esito;
    }

    public static function update(Ordine $o): bool
    {
        $pdo  = FConnectionDB::getConnection();
        $stmt = $pdo->prepare('UPDATE ' . self::TABELLA . ' SET stato = ? WHERE id = ?');
        $esito = $stmt->execute([$o->getStato()->value, $o->getId()]);
        if ($esito) {
            self::sincronizzaRighe($o);
        }
        return $esito;
    }

    public static function delete(int $id): bool
    {
        $pdo  = FConnectionDB::getConnection();
        // Le righe in ordine_piatti spariscono in cascata.
        $stmt = $pdo->prepare('DELETE FROM ' . self::TABELLA . ' WHERE id = ?');
        return $stmt->execute([$id]);
    }

    // --- helper privati ----------------------------------------------

    /** @param array[] $righeDb @return Ordine[] */
    private static function costruisciLista(array $righeDb): array
    {
        $risultato = [];
        foreach ($righeDb as $riga) {
            $risultato[] = self::creaDaRiga($riga);
        }
        return $risultato;
    }

    /**
     * Riallinea la tabella ordine_piatti con le righe presenti nell'oggetto:
     * cancella le vecchie e reinserisce quelle correnti (con prezzo snapshot).
     */
    private static function sincronizzaRighe(Ordine $o): void
    {
        $pdo = FConnectionDB::getConnection();

        $del = $pdo->prepare('DELETE FROM ordine_piatti WHERE ordine_id = ?');
        $del->execute([$o->getId()]);

        $ins = $pdo->prepare(
            'INSERT INTO ordine_piatti (ordine_id, piatto_id, quantita, prezzo_unitario)
             VALUES (?, ?, ?, ?)'
        );
        foreach ($o->getRighe() as $r) {
            $ins->execute([
                $o->getId(),
                $r->getPiatto()->getId(),
                $r->getQuantita(),
                $r->getPrezzoUnitario(),
            ]);
        }
    }

    /** Costruisce l'oggetto Ordine da una riga, comprese le sue RigaOrdine. */
    private static function creaDaRiga(array $riga): Ordine
    {
        $ordine = new Ordine(
            StatoOrdine::from($riga['stato']),
            new DateTimeImmutable($riga['creato_il']),
            (int) $riga['id'],
            isset($riga['tavolo_id']) ? (int) $riga['tavolo_id'] : null
        );

        // Carica le righe dell'ordine; ogni riga riferisce un Piatto (via FPiatto).
        $pdo  = FConnectionDB::getConnection();
        $stmt = $pdo->prepare(
            'SELECT piatto_id, quantita, prezzo_unitario FROM ordine_piatti WHERE ordine_id = ?'
        );
        $stmt->execute([(int) $riga['id']]);

        foreach ($stmt->fetchAll() as $rigaOp) {
            $piatto = FPiatto::load((int) $rigaOp['piatto_id']);
            if ($piatto !== null) {
                // Ricostruiamo la riga preservando il prezzo storico (snapshot)
                // e la aggiungiamo con aggiungiRiga(), che non ricalcola il prezzo.
                $ordine->aggiungiRiga(new \Entity\RigaOrdine(
                    $piatto,
                    (int) $rigaOp['quantita'],
                    (float) $rigaOp['prezzo_unitario']
                ));
            }
        }

        return $ordine;
    }
}
