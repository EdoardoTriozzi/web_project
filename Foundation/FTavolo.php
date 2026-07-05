<?php
declare(strict_types=1);

namespace Foundation;

use Entity\Tavolo;
use Entity\StatoTavolo;

/**
 * Classe Foundation per l'entity Tavolo. Conosce la tabella "tavoli".
 *
 * Particolarita' nella traduzione oggetto<->riga:
 *  - lo stato e' un enum StatoTavolo: in scrittura si salva $stato->value,
 *    in lettura si ricostruisce con StatoTavolo::from(...);
 *  - la password e' nullable (un tavolo libero puo' non averne);
 *  - conto_richiesto e' un flag booleano.
 *
 * Metodi specifici:
 *  - loadByRistorante: i tavoli di un ristorante;
 *  - loadByNumero: trova un tavolo per ristorante + numero (per l'accesso
 *    del tavolo, che avviene indicando numero e password).
 */
class FTavolo
{
    private const TABELLA = 'tavoli';

    public static function exist(int $id): bool
    {
        $pdo  = FConnectionDB::getConnection();
        $stmt = $pdo->prepare('SELECT id FROM ' . self::TABELLA . ' WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() !== false;
    }

    public static function load(int $id): ?Tavolo
    {
        $pdo  = FConnectionDB::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM ' . self::TABELLA . ' WHERE id = ?');
        $stmt->execute([$id]);
        $riga = $stmt->fetch();
        return $riga === false ? null : self::creaDaRiga($riga);
    }

    /**
     * Metodo specifico: i tavoli di un ristorante, ordinati per numero.
     * @return Tavolo[]
     */
    public static function loadByRistorante(int $ristoranteId): array
    {
        $pdo  = FConnectionDB::getConnection();
        $stmt = $pdo->prepare(
            'SELECT * FROM ' . self::TABELLA . ' WHERE ristorante_id = ? ORDER BY numero'
        );
        $stmt->execute([$ristoranteId]);
        $risultato = [];
        foreach ($stmt->fetchAll() as $riga) {
            $risultato[] = self::creaDaRiga($riga);
        }
        return $risultato;
    }

    /**
     * Metodo specifico: trova un tavolo dato il ristorante e il numero.
     * Serve all'accesso del tavolo (numero + password). Restituisce null
     * se non esiste. La verifica della password avviene poi sull'oggetto.
     */
    public static function loadByNumero(int $ristoranteId, string $numero): ?Tavolo
    {
        $pdo  = FConnectionDB::getConnection();
        $stmt = $pdo->prepare(
            'SELECT * FROM ' . self::TABELLA . ' WHERE ristorante_id = ? AND numero = ?'
        );
        $stmt->execute([$ristoranteId, $numero]);
        $riga = $stmt->fetch();
        return $riga === false ? null : self::creaDaRiga($riga);
    }

    public static function store(Tavolo $t, int $ristoranteId): bool
    {
        $pdo  = FConnectionDB::getConnection();
        $stmt = $pdo->prepare(
            'INSERT INTO ' . self::TABELLA . '
                (ristorante_id, numero, password_hash, stato, coperti, conto_richiesto)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        // La password e' '' nell'oggetto quando assente: salviamo NULL in quel caso.
        $passwordDb = $t->haPassword() ? $t->getPasswordHash() : null;
        $esito = $stmt->execute([
            $ristoranteId,
            $t->getNumero(),
            $passwordDb,
            $t->getStato()->value,
            $t->getCoperti(),
            $t->isContoRichiesto() ? 1 : 0,
        ]);
        if ($esito) {
            $t->setId((int) $pdo->lastInsertId());
        }
        return $esito;
    }

    public static function update(Tavolo $t): bool
    {
        $pdo  = FConnectionDB::getConnection();
        $stmt = $pdo->prepare(
            'UPDATE ' . self::TABELLA . '
             SET numero = ?, password_hash = ?, stato = ?, coperti = ?, conto_richiesto = ?
             WHERE id = ?'
        );
        $passwordDb = $t->haPassword() ? $t->getPasswordHash() : null;
        return $stmt->execute([
            $t->getNumero(),
            $passwordDb,
            $t->getStato()->value,
            $t->getCoperti(),
            $t->isContoRichiesto() ? 1 : 0,
            $t->getId(),
        ]);
    }

    public static function delete(int $id): bool
    {
        $pdo  = FConnectionDB::getConnection();
        $stmt = $pdo->prepare('DELETE FROM ' . self::TABELLA . ' WHERE id = ?');
        return $stmt->execute([$id]);
    }

    private static function creaDaRiga(array $riga): Tavolo
    {
        return new Tavolo(
            $riga['numero'],
            $riga['password_hash'],                       // puo' essere null
            StatoTavolo::from($riga['stato']),            // stringa -> enum
            (int) $riga['coperti'],
            (bool) $riga['conto_richiesto'],
            (int) $riga['id'],
            (int) $riga['ristorante_id']                  // ristorante di appartenenza
        );
    }
}
