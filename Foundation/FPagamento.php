<?php
declare(strict_types=1);

namespace Foundation;

use Entity\Pagamento;
use Entity\PagamentoVoce;
use DateTimeImmutable;

/**
 * Classe Foundation per l'entity Pagamento. Conosce le tabelle "pagamenti"
 * e "pagamento_voci".
 *
 * Il metodo store() salva testata + voci dentro una TRANSAZIONE: o si
 * scrive tutto, o niente. Cosi' non puo' mai restare un pagamento senza
 * le sue voci (integrita').
 *
 * Da qui si leggono anche le statistiche delle vendite, calcolate sui dati
 * "congelati" dei pagamenti (non piu' sugli ordini, che dopo il pagamento
 * vengono cancellati).
 */
class FPagamento
{
    private const TABELLA      = 'pagamenti';
    private const TABELLA_VOCI = 'pagamento_voci';

    /**
     * Salva un pagamento con tutte le sue voci, in transazione.
     *
     * @param Pagamento $p           il pagamento da salvare (con le voci dentro)
     * @param int       $ristoranteId ristorante a cui appartiene
     * @return bool true se salvato, false altrimenti
     */
    public static function store(Pagamento $p, int $ristoranteId): bool
    {
        $pdo = FConnectionDB::getConnection();

        try {
            $pdo->beginTransaction();

            // 1) testata del pagamento
            $stmt = $pdo->prepare(
                'INSERT INTO ' . self::TABELLA . ' (ristorante_id, numero_tavolo, totale, pagato_il)
                 VALUES (?, ?, ?, ?)'
            );
            $stmt->execute([
                $ristoranteId,
                $p->getNumeroTavolo(),
                $p->totale(),
                $p->getPagatoIl()->format('Y-m-d H:i:s'),
            ]);
            $pagamentoId = (int) $pdo->lastInsertId();
            $p->setId($pagamentoId);

            // 2) le voci
            $stmtV = $pdo->prepare(
                'INSERT INTO ' . self::TABELLA_VOCI . '
                 (pagamento_id, piatto_id, nome_piatto, quantita, prezzo_unitario)
                 VALUES (?, ?, ?, ?, ?)'
            );
            foreach ($p->getVoci() as $voce) {
                $stmtV->execute([
                    $pagamentoId,
                    $voce->getPiattoId(),
                    $voce->getNomePiatto(),
                    $voce->getQuantita(),
                    $voce->getPrezzoUnitario(),
                ]);
            }

            $pdo->commit();
            return true;
        } catch (\Throwable $e) {
            // Qualcosa e' andato storto: annulla tutto.
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return false;
        }
    }

    /**
     * Statistiche: quantita' totale venduta per piatto, da tutti i pagamenti
     * di un ristorante. Raggruppa per nome del piatto (cosi' include anche le
     * voci di piatti poi eliminati, che hanno piatto_id NULL ma nome valido).
     *
     * @return array<int, array{piatto_id:?int, nome:string, quantita_totale:int}>
     */
    public static function conteggioVenditeByRistorante(int $ristoranteId, ?string $dataMin = null): array
    {
        $pdo  = FConnectionDB::getConnection();
        // Filtro opzionale per data: se $dataMin e' indicato, conta solo i
        // pagamenti da quella data in poi (per le statistiche per periodo).
        $sql = 'SELECT MAX(pv.piatto_id) AS piatto_id, pv.nome_piatto AS nome,
                       SUM(pv.quantita) AS quantita_totale
                  FROM ' . self::TABELLA_VOCI . ' pv
                  JOIN ' . self::TABELLA . ' pg ON pg.id = pv.pagamento_id
                 WHERE pg.ristorante_id = ?';
        $args = [$ristoranteId];
        if ($dataMin !== null) {
            $sql .= ' AND pg.pagato_il >= ?';
            $args[] = $dataMin;
        }
        $sql .= ' GROUP BY pv.nome_piatto ORDER BY quantita_totale DESC, pv.nome_piatto';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($args);

        $risultato = [];
        foreach ($stmt->fetchAll() as $riga) {
            $risultato[] = [
                'piatto_id'       => $riga['piatto_id'] !== null ? (int) $riga['piatto_id'] : null,
                'nome'            => (string) $riga['nome'],
                'quantita_totale' => (int) $riga['quantita_totale'],
            ];
        }
        return $risultato;
    }

    /**
     * Incasso totale di un ristorante (somma di tutti i pagamenti).
     */
    public static function incassoByRistorante(int $ristoranteId, ?string $dataMin = null): float
    {
        $pdo  = FConnectionDB::getConnection();
        $sql  = 'SELECT COALESCE(SUM(totale), 0) AS incasso FROM ' . self::TABELLA . ' WHERE ristorante_id = ?';
        $args = [$ristoranteId];
        if ($dataMin !== null) {
            $sql .= ' AND pagato_il >= ?';
            $args[] = $dataMin;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($args);
        return (float) $stmt->fetchColumn();
    }

    /**
     * Numero di pagamenti (scontrini) registrati per un ristorante.
     */
    public static function numeroPagamentiByRistorante(int $ristoranteId, ?string $dataMin = null): int
    {
        $pdo  = FConnectionDB::getConnection();
        $sql  = 'SELECT COUNT(*) FROM ' . self::TABELLA . ' WHERE ristorante_id = ?';
        $args = [$ristoranteId];
        if ($dataMin !== null) {
            $sql .= ' AND pagato_il >= ?';
            $args[] = $dataMin;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($args);
        return (int) $stmt->fetchColumn();
    }
}
