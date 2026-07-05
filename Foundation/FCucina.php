<?php
declare(strict_types=1);

namespace Foundation;

use Entity\Cucina;

/**
 * Classe Foundation per l'entity Cucina. Conosce la tabella "cucine".
 *
 * Oltre al CRUD ha due metodi specifici:
 *  - loadByUsername: per il login della cucina;
 *  - loadByRistorante: per elencare le cucine di un ristorante.
 *
 * Nota: la cucina appartiene a un ristorante (ristorante_id). Qui la
 * Foundation gestisce la colonna ristorante_id come dato; il collegamento
 * all'oggetto Ristorante, se servisse, e' orchestrato dal control.
 */
class FCucina
{
    private const TABELLA = 'cucine';

    public static function exist(int $id): bool
    {
        $pdo  = FConnectionDB::getConnection();
        $stmt = $pdo->prepare('SELECT id FROM ' . self::TABELLA . ' WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() !== false;
    }

    public static function load(int $id): ?Cucina
    {
        $pdo  = FConnectionDB::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM ' . self::TABELLA . ' WHERE id = ?');
        $stmt->execute([$id]);
        $riga = $stmt->fetch();
        return $riga === false ? null : self::creaDaRiga($riga);
    }

    /** Metodo specifico per il login della cucina. */
    public static function loadByUsername(string $username): ?Cucina
    {
        $pdo  = FConnectionDB::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM ' . self::TABELLA . ' WHERE username = ?');
        $stmt->execute([$username]);
        $riga = $stmt->fetch();
        return $riga === false ? null : self::creaDaRiga($riga);
    }

    /**
     * Metodo specifico: tutte le cucine di un dato ristorante.
     * @return Cucina[]
     */
    public static function loadByRistorante(int $ristoranteId): array
    {
        $pdo  = FConnectionDB::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM ' . self::TABELLA . ' WHERE ristorante_id = ? ORDER BY nome');
        $stmt->execute([$ristoranteId]);
        $risultato = [];
        foreach ($stmt->fetchAll() as $riga) {
            $risultato[] = self::creaDaRiga($riga);
        }
        return $risultato;
    }

    public static function store(Cucina $c, int $ristoranteId): bool
    {
        $pdo  = FConnectionDB::getConnection();
        $stmt = $pdo->prepare(
            'INSERT INTO ' . self::TABELLA . ' (ristorante_id, nome, username, password_hash)
             VALUES (?, ?, ?, ?)'
        );
        $esito = $stmt->execute([
            $ristoranteId,
            $c->getNome(),
            $c->getUsername(),
            $c->getPasswordHash(),
        ]);
        if ($esito) {
            $c->setId((int) $pdo->lastInsertId());
        }
        return $esito;
    }

    public static function update(Cucina $c): bool
    {
        $pdo  = FConnectionDB::getConnection();
        $stmt = $pdo->prepare(
            'UPDATE ' . self::TABELLA . ' SET nome = ?, username = ?, password_hash = ? WHERE id = ?'
        );
        return $stmt->execute([
            $c->getNome(),
            $c->getUsername(),
            $c->getPasswordHash(),
            $c->getId(),
        ]);
    }

    public static function delete(int $id): bool
    {
        $pdo  = FConnectionDB::getConnection();
        $stmt = $pdo->prepare('DELETE FROM ' . self::TABELLA . ' WHERE id = ?');
        return $stmt->execute([$id]);
    }

    private static function creaDaRiga(array $riga): Cucina
    {
        return new Cucina(
            $riga['nome'],
            $riga['username'],
            $riga['password_hash'],
            (int) $riga['id']
        );
    }
}
