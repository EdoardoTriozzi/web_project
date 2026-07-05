<?php
declare(strict_types=1);

namespace Foundation;

use Entity\Allergene;

/**
 * Classe Foundation per l'entity Allergene. Conosce la tabella "allergeni".
 * L'allergene appartiene al catalogo di un ristorante (ristorante_id).
 */
class FAllergene
{
    private const TABELLA = 'allergeni';

    public static function exist(int $id): bool
    {
        $pdo  = FConnectionDB::getConnection();
        $stmt = $pdo->prepare('SELECT id FROM ' . self::TABELLA . ' WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() !== false;
    }

    public static function load(int $id): ?Allergene
    {
        $pdo  = FConnectionDB::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM ' . self::TABELLA . ' WHERE id = ?');
        $stmt->execute([$id]);
        $riga = $stmt->fetch();
        return $riga === false ? null : self::creaDaRiga($riga);
    }

    /**
     * Metodo specifico: tutti gli allergeni del catalogo di un ristorante.
     * @return Allergene[]
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

    public static function store(Allergene $a, int $ristoranteId): bool
    {
        $pdo  = FConnectionDB::getConnection();
        $stmt = $pdo->prepare(
            'INSERT INTO ' . self::TABELLA . ' (ristorante_id, nome) VALUES (?, ?)'
        );
        $esito = $stmt->execute([$ristoranteId, $a->getNome()]);
        if ($esito) {
            $a->setId((int) $pdo->lastInsertId());
        }
        return $esito;
    }

    public static function update(Allergene $a): bool
    {
        $pdo  = FConnectionDB::getConnection();
        $stmt = $pdo->prepare('UPDATE ' . self::TABELLA . ' SET nome = ? WHERE id = ?');
        return $stmt->execute([$a->getNome(), $a->getId()]);
    }

    public static function delete(int $id): bool
    {
        $pdo  = FConnectionDB::getConnection();
        $stmt = $pdo->prepare('DELETE FROM ' . self::TABELLA . ' WHERE id = ?');
        return $stmt->execute([$id]);
    }

    private static function creaDaRiga(array $riga): Allergene
    {
        return new Allergene($riga['nome'], (int) $riga['id']);
    }
}
