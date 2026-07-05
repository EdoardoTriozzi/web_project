<?php
declare(strict_types=1);

namespace Foundation;

use Entity\Amministratore;

/**
 * Classe Foundation per l'entity Amministratore. Conosce la tabella
 * "amministratori". Stessa struttura delle altre classi F.
 */
class FAmministratore
{
    private const TABELLA = 'amministratori';

    public static function exist(int $id): bool
    {
        $pdo  = FConnectionDB::getConnection();
        $stmt = $pdo->prepare('SELECT id FROM ' . self::TABELLA . ' WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() !== false;
    }

    public static function load(int $id): ?Amministratore
    {
        $pdo  = FConnectionDB::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM ' . self::TABELLA . ' WHERE id = ?');
        $stmt->execute([$id]);
        $riga = $stmt->fetch();
        return $riga === false ? null : self::creaDaRiga($riga);
    }

    /** Metodo specifico per il login dell'amministratore. */
    public static function loadByUsername(string $username): ?Amministratore
    {
        $pdo  = FConnectionDB::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM ' . self::TABELLA . ' WHERE username = ?');
        $stmt->execute([$username]);
        $riga = $stmt->fetch();
        return $riga === false ? null : self::creaDaRiga($riga);
    }

    public static function store(Amministratore $a): bool
    {
        $pdo  = FConnectionDB::getConnection();
        $stmt = $pdo->prepare(
            'INSERT INTO ' . self::TABELLA . ' (username, password_hash) VALUES (?, ?)'
        );
        $esito = $stmt->execute([$a->getUsername(), $a->getPasswordHash()]);
        if ($esito) {
            $a->setId((int) $pdo->lastInsertId());
        }
        return $esito;
    }

    public static function update(Amministratore $a): bool
    {
        $pdo  = FConnectionDB::getConnection();
        $stmt = $pdo->prepare(
            'UPDATE ' . self::TABELLA . ' SET username = ?, password_hash = ? WHERE id = ?'
        );
        return $stmt->execute([$a->getUsername(), $a->getPasswordHash(), $a->getId()]);
    }

    public static function delete(int $id): bool
    {
        $pdo  = FConnectionDB::getConnection();
        $stmt = $pdo->prepare('DELETE FROM ' . self::TABELLA . ' WHERE id = ?');
        return $stmt->execute([$id]);
    }

    private static function creaDaRiga(array $riga): Amministratore
    {
        return new Amministratore(
            $riga['username'],
            $riga['password_hash'],
            (int) $riga['id']
        );
    }
}
