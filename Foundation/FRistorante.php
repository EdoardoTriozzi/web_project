<?php
declare(strict_types=1);

namespace Foundation;

use Entity\Ristorante;
use PDO;

/**
 * ⚠️ Classe Foundation per l'entity Ristorante.
 *
 * Secondo la strategia base delle slide: una classe Foundation per ogni
 * Entity, con la responsabilita' di inviare al DB le query relative agli
 * oggetti Ristorante. Conosce la tabella "ristoranti".
 *
 * Metodi CRUD standard (comuni a tutte le classi F):
 *   exist, load, store, update, delete
 * Metodo specifico di questa classe:
 *   loadByUsername  (serve al login: trova un ristorante dal suo username)
 *
 * Le classi Foundation NON hanno attributi: ricevono dati, eseguono
 * query tramite la connessione di FConnectionDB, restituiscono risultati.
 */
class FRistorante
{
    /** Nome della tabella, centralizzato per non ripeterlo nelle query. */
    private const TABELLA = 'ristoranti';

    /**
     * Vero se esiste un ristorante con quell'id.
     * (Pseudo-algoritmo "exist" delle slide.)
     */
    public static function exist(int $id): bool
    {
        $pdo  = FConnectionDB::getConnection();
        $stmt = $pdo->prepare('SELECT id FROM ' . self::TABELLA . ' WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() !== false;
    }

    /**
     * Carica un ristorante dato l'id e ne costruisce l'oggetto entity.
     * Restituisce null se non esiste.
     */
    public static function load(int $id): ?Ristorante
    {
        $pdo  = FConnectionDB::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM ' . self::TABELLA . ' WHERE id = ?');
        $stmt->execute([$id]);
        $riga = $stmt->fetch();

        return $riga === false ? null : self::creaDaRiga($riga);
    }

    /**
     * Metodo specifico: carica un ristorante dal suo username.
     * E' il mattone del login (poi il control verifichera' la password
     * sull'oggetto restituito). Restituisce null se l'username non esiste.
     */
    public static function loadByUsername(string $username): ?Ristorante
    {
        $pdo  = FConnectionDB::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM ' . self::TABELLA . ' WHERE username = ?');
        $stmt->execute([$username]);
        $riga = $stmt->fetch();

        return $riga === false ? null : self::creaDaRiga($riga);
    }

    /**
     * Inserisce un NUOVO ristorante (INSERT) e gli assegna l'id generato
     * dal database. Restituisce true se l'inserimento riesce.
     */
    public static function store(Ristorante $r): bool
    {
        $pdo  = FConnectionDB::getConnection();
        $stmt = $pdo->prepare(
            'INSERT INTO ' . self::TABELLA . ' (nome, username, password_hash, attivo)
             VALUES (?, ?, ?, ?)'
        );
        $esito = $stmt->execute([
            $r->getNome(),
            $r->getUsername(),
            $r->getPasswordHash(),
            $r->isAttivo() ? 1 : 0,
        ]);

        if ($esito) {
            // Allinea l'oggetto in memoria con la riga appena creata.
            $r->setId((int) $pdo->lastInsertId());
        }
        return $esito;
    }

    /**
     * Aggiorna un ristorante ESISTENTE (UPDATE), identificato dal suo id.
     * Restituisce true se l'aggiornamento riesce.
     */
    public static function update(Ristorante $r): bool
    {
        $pdo  = FConnectionDB::getConnection();
        $stmt = $pdo->prepare(
            'UPDATE ' . self::TABELLA . '
             SET nome = ?, username = ?, password_hash = ?, attivo = ?
             WHERE id = ?'
        );
        return $stmt->execute([
            $r->getNome(),
            $r->getUsername(),
            $r->getPasswordHash(),
            $r->isAttivo() ? 1 : 0,
            $r->getId(),
        ]);
    }

    /**
     * Elimina un ristorante dato l'id. Le tabelle figlie (cucine, tavoli,
     * categorie, piatti, allergeni) spariscono in cascata grazie ai
     * vincoli ON DELETE CASCADE definiti nello schema.
     */
    public static function delete(int $id): bool
    {
        $pdo  = FConnectionDB::getConnection();
        $stmt = $pdo->prepare('DELETE FROM ' . self::TABELLA . ' WHERE id = ?');
        return $stmt->execute([$id]);
    }

    /**
     * Metodo specifico utile all'amministratore: elenca tutti i ristoranti.
     * @return Ristorante[]
     */
    public static function loadAll(): array
    {
        $pdo  = FConnectionDB::getConnection();
        $stmt = $pdo->query('SELECT * FROM ' . self::TABELLA . ' ORDER BY nome');

        $risultato = [];
        foreach ($stmt->fetchAll() as $riga) {
            $risultato[] = self::creaDaRiga($riga);
        }
        return $risultato;
    }

    // -----------------------------------------------------------------
    //  Costruzione dell'oggetto entity da una riga del database.
    //  Isolata qui perche' usata da load, loadByUsername e loadAll.
    //  E' il punto in cui il mondo "tabella" diventa mondo "oggetto".
    // -----------------------------------------------------------------
    private static function creaDaRiga(array $riga): Ristorante
    {
        return new Ristorante(
            $riga['nome'],
            $riga['username'],
            $riga['password_hash'],
            (bool) $riga['attivo'],
            (int) $riga['id']
        );
    }
}
