<?php
declare(strict_types=1);

namespace Foundation;

use Entity\Categoria;

/**
 * Classe Foundation per l'entity Categoria. Conosce la tabella "categorie".
 * La categoria appartiene a un ristorante e ha una posizione di ordinamento.
 */
class FCategoria
{
    private const TABELLA = 'categorie';

    public static function exist(int $id): bool
    {
        $pdo  = FConnectionDB::getConnection();
        $stmt = $pdo->prepare('SELECT id FROM ' . self::TABELLA . ' WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() !== false;
    }

    public static function load(int $id): ?Categoria
    {
        $pdo  = FConnectionDB::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM ' . self::TABELLA . ' WHERE id = ?');
        $stmt->execute([$id]);
        $riga = $stmt->fetch();
        return $riga === false ? null : self::creaDaRiga($riga);
    }

    /**
     * Metodo specifico: le categorie di un ristorante, gia' ordinate per
     * posizione (utile a costruire il menu nell'ordine deciso dal ristorante).
     * @return Categoria[]
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

    public static function store(Categoria $c, int $ristoranteId): bool
    {
        $pdo  = FConnectionDB::getConnection();
        $stmt = $pdo->prepare(
            'INSERT INTO ' . self::TABELLA . ' (ristorante_id, nome, posizione) VALUES (?, ?, ?)'
        );
        $esito = $stmt->execute([$ristoranteId, $c->getNome(), $c->getPosizione()]);
        if ($esito) {
            $c->setId((int) $pdo->lastInsertId());
        }
        return $esito;
    }

    public static function update(Categoria $c): bool
    {
        $pdo  = FConnectionDB::getConnection();
        $stmt = $pdo->prepare(
            'UPDATE ' . self::TABELLA . ' SET nome = ?, posizione = ? WHERE id = ?'
        );
        return $stmt->execute([$c->getNome(), $c->getPosizione(), $c->getId()]);
    }

    public static function delete(int $id): bool
    {
        $pdo  = FConnectionDB::getConnection();
        $stmt = $pdo->prepare('DELETE FROM ' . self::TABELLA . ' WHERE id = ?');
        return $stmt->execute([$id]);
    }

    private static function creaDaRiga(array $riga): Categoria
    {
        return new Categoria(
            $riga['nome'],
            (int) $riga['posizione'],
            (int) $riga['id']
        );
    }
}
