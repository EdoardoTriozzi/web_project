<?php
declare(strict_types=1);

namespace Foundation;

use Entity\Piatto;

/**
 * Classe Foundation per l'entity Piatto. Conosce la tabella "piatti" e la
 * tabella ponte "piatto_allergeni".
 *
 * E' la classe F piu' ricca, perche' il Piatto ha relazioni:
 *  - categoria_id e cucina_id (riferimenti, molteplicita' 0..1): in lettura
 *    ricostruiamo gli oggetti Categoria e Cucina tramite le loro classi F;
 *  - allergeni (multivalore, molti-a-molti): in lettura li carichiamo con
 *    una seconda query sulla tabella ponte, come il load di FContact nelle
 *    slide caricava i telefoni; in scrittura sincronizziamo la tabella ponte.
 *
 * Questo e' l'esempio concreto del mapping Object->Relational per classi
 * con multivalori e associazioni discusso nelle slide.
 */
class FPiatto
{
    private const TABELLA = 'piatti';

    public static function exist(int $id): bool
    {
        $pdo  = FConnectionDB::getConnection();
        $stmt = $pdo->prepare('SELECT id FROM ' . self::TABELLA . ' WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() !== false;
    }

    public static function load(int $id): ?Piatto
    {
        $pdo  = FConnectionDB::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM ' . self::TABELLA . ' WHERE id = ?');
        $stmt->execute([$id]);
        $riga = $stmt->fetch();
        return $riga === false ? null : self::creaDaRiga($riga);
    }

    /**
     * Metodo specifico: tutti i piatti di un ristorante, ordinati per
     * posizione. Serve sia alla gestione (ristorante) sia a costruire il menu.
     * @return Piatto[]
     */
    public static function loadByRistorante(int $ristoranteId): array
    {
        $pdo  = FConnectionDB::getConnection();
        $stmt = $pdo->prepare(
            'SELECT * FROM ' . self::TABELLA . ' WHERE ristorante_id = ? ORDER BY posizione, nome'
        );
        $stmt->execute([$ristoranteId]);
        $risultato = [];
        foreach ($stmt->fetchAll() as $riga) {
            $risultato[] = self::creaDaRiga($riga);
        }
        return $risultato;
    }

    /**
     * Metodo specifico: i piatti assegnati a una certa cucina. E' cio' che
     * permette alla cucina di vedere solo i piatti di sua competenza.
     * @return Piatto[]
     */
    public static function loadByCucina(int $cucinaId): array
    {
        $pdo  = FConnectionDB::getConnection();
        $stmt = $pdo->prepare(
            'SELECT * FROM ' . self::TABELLA . ' WHERE cucina_id = ? ORDER BY posizione, nome'
        );
        $stmt->execute([$cucinaId]);
        $risultato = [];
        foreach ($stmt->fetchAll() as $riga) {
            $risultato[] = self::creaDaRiga($riga);
        }
        return $risultato;
    }

    public static function store(Piatto $p, int $ristoranteId): bool
    {
        $pdo  = FConnectionDB::getConnection();
        $stmt = $pdo->prepare(
            'INSERT INTO ' . self::TABELLA . '
                (ristorante_id, categoria_id, cucina_id, nome, descrizione,
                 prezzo, immagine, etichetta, posizione, disponibile)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $esito = $stmt->execute([
            $ristoranteId,
            $p->getCategoria()?->getId(),   // null se nessuna categoria
            $p->getCucina()?->getId(),      // null se nessuna cucina
            $p->getNome(),
            $p->getDescrizione(),
            $p->getPrezzo(),
            $p->getImmagine(),
            $p->getEtichetta(),
            $p->getPosizione(),
            $p->isDisponibile() ? 1 : 0,
        ]);
        if ($esito) {
            $p->setId((int) $pdo->lastInsertId());
            self::sincronizzaAllergeni($p);   // salva le associazioni nella tabella ponte
        }
        return $esito;
    }

    public static function update(Piatto $p): bool
    {
        $pdo  = FConnectionDB::getConnection();
        $stmt = $pdo->prepare(
            'UPDATE ' . self::TABELLA . '
             SET categoria_id = ?, cucina_id = ?, nome = ?, descrizione = ?,
                 prezzo = ?, immagine = ?, etichetta = ?, posizione = ?, disponibile = ?
             WHERE id = ?'
        );
        $esito = $stmt->execute([
            $p->getCategoria()?->getId(),
            $p->getCucina()?->getId(),
            $p->getNome(),
            $p->getDescrizione(),
            $p->getPrezzo(),
            $p->getImmagine(),
            $p->getEtichetta(),
            $p->getPosizione(),
            $p->isDisponibile() ? 1 : 0,
            $p->getId(),
        ]);
        if ($esito) {
            self::sincronizzaAllergeni($p);
        }
        return $esito;
    }

    public static function delete(int $id): bool
    {
        $pdo  = FConnectionDB::getConnection();
        // Le righe in piatto_allergeni spariscono in cascata (ON DELETE CASCADE).
        $stmt = $pdo->prepare('DELETE FROM ' . self::TABELLA . ' WHERE id = ?');
        return $stmt->execute([$id]);
    }

    // -----------------------------------------------------------------
    //  Sincronizza la tabella ponte piatto_allergeni con gli allergeni
    //  attualmente associati all'oggetto: cancella i vecchi legami e
    //  reinserisce quelli correnti. Approccio semplice e sempre coerente.
    // -----------------------------------------------------------------
    private static function sincronizzaAllergeni(Piatto $p): void
    {
        $pdo = FConnectionDB::getConnection();

        $del = $pdo->prepare('DELETE FROM piatto_allergeni WHERE piatto_id = ?');
        $del->execute([$p->getId()]);

        $ins = $pdo->prepare(
            'INSERT INTO piatto_allergeni (piatto_id, allergene_id) VALUES (?, ?)'
        );
        foreach ($p->getAllergeni() as $allergene) {
            if ($allergene->getId() !== null) {
                $ins->execute([$p->getId(), $allergene->getId()]);
            }
        }
    }

    // -----------------------------------------------------------------
    //  Costruzione dell'oggetto Piatto da una riga, completo di:
    //  - oggetto Categoria (se categoria_id presente),
    //  - oggetto Cucina (se cucina_id presente),
    //  - collezione di Allergeni (seconda query sulla tabella ponte).
    // -----------------------------------------------------------------
    private static function creaDaRiga(array $riga): Piatto
    {
        // Ricostruisce gli oggetti collegati appoggiandosi alle altre classi F.
        $categoria = $riga['categoria_id'] !== null
            ? FCategoria::load((int) $riga['categoria_id'])
            : null;

        $cucina = $riga['cucina_id'] !== null
            ? FCucina::load((int) $riga['cucina_id'])
            : null;

        $piatto = new Piatto(
            $riga['nome'],
            (float) $riga['prezzo'],
            $riga['descrizione'],
            $riga['immagine'],
            $riga['etichetta'],
            (int) $riga['posizione'],
            (bool) $riga['disponibile'],
            $categoria,
            $cucina,
            (int) $riga['id']
        );

        // Carica gli allergeni del piatto dalla tabella ponte e li associa.
        $pdo  = FConnectionDB::getConnection();
        $stmt = $pdo->prepare(
            'SELECT a.id, a.nome
               FROM allergeni a
               JOIN piatto_allergeni pa ON pa.allergene_id = a.id
              WHERE pa.piatto_id = ?'
        );
        $stmt->execute([(int) $riga['id']]);
        foreach ($stmt->fetchAll() as $rigaAll) {
            $piatto->associaAllergene(
                new \Entity\Allergene($rigaAll['nome'], (int) $rigaAll['id'])
            );
        }

        return $piatto;
    }
}
