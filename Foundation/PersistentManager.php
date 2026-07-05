<?php
declare(strict_types=1);

namespace Foundation;

use Entity\Amministratore;
use Entity\Ristorante;
use Entity\Cucina;
use Entity\Tavolo;
use Entity\Categoria;
use Entity\Allergene;
use Entity\Piatto;
use Entity\Ordine;
use Entity\Pagamento;
use InvalidArgumentException;

/**
 * PersistentManager: facciata unica dello strato Foundation.
 *
 * Motivazione (slide pag. 29-31): con la sola strategia base, gli strati
 * superiori dovrebbero conoscere TUTTE le classi F (FPiatto, FOrdine, ...).
 * Il PersistentManager risolve questo limite: e' l'unica classe pubblica
 * che il control usa, e internamente smista la richiesta verso la classe
 * F corretta. Cosi' il control parla con un solo interlocutore.
 *
 * Come decide quale F chiamare:
 *  - per store/update/delete riceve l'OGGETTO e ne guarda il tipo
 *    (instanceof): un Piatto -> FPiatto, un Ordine -> FOrdine, ecc.;
 *  - per load riceve il NOME dell'entity come stringa piu' l'id
 *    (come nelle slide: pm.load("Studente", id)).
 *
 * Nota sul secondo parametro di store/update: alcune classi F richiedono
 * l'id del "genitore" (es. FPiatto::store($p, $ristoranteId)) perche' le
 * relative entity non lo contengono. Il manager accetta percio' un
 * parametro opzionale $idGenitore che gira alla classe F quando serve.
 *
 * Metodi statici, coerentemente con le altre classi della foundation.
 */
class PersistentManager
{
    /**
     * Salva (INSERT) un oggetto entity, smistando verso la classe F giusta.
     * $idGenitore serve per le entity che lo richiedono (piatto, cucina,
     * tavolo, categoria, allergene -> id del ristorante; ordine -> id del tavolo).
     */
    public static function store(object $oggetto, ?int $idGenitore = null): bool
    {
        if ($oggetto instanceof Amministratore) {
            return FAmministratore::store($oggetto);
        }
        if ($oggetto instanceof Ristorante) {
            return FRistorante::store($oggetto);
        }
        if ($oggetto instanceof Cucina) {
            return FCucina::store($oggetto, self::richiediGenitore($idGenitore, 'Cucina'));
        }
        if ($oggetto instanceof Tavolo) {
            return FTavolo::store($oggetto, self::richiediGenitore($idGenitore, 'Tavolo'));
        }
        if ($oggetto instanceof Categoria) {
            return FCategoria::store($oggetto, self::richiediGenitore($idGenitore, 'Categoria'));
        }
        if ($oggetto instanceof Allergene) {
            return FAllergene::store($oggetto, self::richiediGenitore($idGenitore, 'Allergene'));
        }
        if ($oggetto instanceof Piatto) {
            return FPiatto::store($oggetto, self::richiediGenitore($idGenitore, 'Piatto'));
        }
        if ($oggetto instanceof Ordine) {
            return FOrdine::store($oggetto, self::richiediGenitore($idGenitore, 'Ordine'));
        }
        if ($oggetto instanceof Pagamento) {
            return FPagamento::store($oggetto, self::richiediGenitore($idGenitore, 'Pagamento'));
        }
        throw new InvalidArgumentException('store: tipo di oggetto non gestito.');
    }

    /**
     * Aggiorna (UPDATE) un oggetto entity, smistando verso la classe F giusta.
     * (update non richiede l'id del genitore: l'oggetto ha gia' il proprio id.)
     */
    public static function update(object $oggetto): bool
    {
        if ($oggetto instanceof Amministratore) {
            return FAmministratore::update($oggetto);
        }
        if ($oggetto instanceof Ristorante) {
            return FRistorante::update($oggetto);
        }
        if ($oggetto instanceof Cucina) {
            return FCucina::update($oggetto);
        }
        if ($oggetto instanceof Tavolo) {
            return FTavolo::update($oggetto);
        }
        if ($oggetto instanceof Categoria) {
            return FCategoria::update($oggetto);
        }
        if ($oggetto instanceof Allergene) {
            return FAllergene::update($oggetto);
        }
        if ($oggetto instanceof Piatto) {
            return FPiatto::update($oggetto);
        }
        if ($oggetto instanceof Ordine) {
            return FOrdine::update($oggetto);
        }
        throw new InvalidArgumentException('update: tipo di oggetto non gestito.');
    }

    /**
     * Carica un'entity dato il suo NOME e l'id (slide: pm.load("Studente", id)).
     * $tipo accetta il nome dell'entity (es. 'Ristorante', 'Piatto').
     * Restituisce l'oggetto o null se non trovato.
     */
    public static function load(string $tipo, int $id): ?object
    {
        return match ($tipo) {
            'Amministratore' => FAmministratore::load($id),
            'Ristorante'     => FRistorante::load($id),
            'Cucina'         => FCucina::load($id),
            'Tavolo'         => FTavolo::load($id),
            'Categoria'      => FCategoria::load($id),
            'Allergene'      => FAllergene::load($id),
            'Piatto'         => FPiatto::load($id),
            'Ordine'         => FOrdine::load($id),
            default          => throw new InvalidArgumentException("load: tipo '$tipo' non gestito."),
        };
    }

    /**
     * Elimina un'entity dato il NOME e l'id.
     * Coerente con load: per cancellare basta sapere cosa e quale id.
     */
    public static function delete(string $tipo, int $id): bool
    {
        return match ($tipo) {
            'Amministratore' => FAmministratore::delete($id),
            'Ristorante'     => FRistorante::delete($id),
            'Cucina'         => FCucina::delete($id),
            'Tavolo'         => FTavolo::delete($id),
            'Categoria'      => FCategoria::delete($id),
            'Allergene'      => FAllergene::delete($id),
            'Piatto'         => FPiatto::delete($id),
            'Ordine'         => FOrdine::delete($id),
            default          => throw new InvalidArgumentException("delete: tipo '$tipo' non gestito."),
        };
    }

    /**
     * Verifica l'esistenza di un'entity dato il NOME e l'id.
     */
    public static function exist(string $tipo, int $id): bool
    {
        return match ($tipo) {
            'Amministratore' => FAmministratore::exist($id),
            'Ristorante'     => FRistorante::exist($id),
            'Cucina'         => FCucina::exist($id),
            'Tavolo'         => FTavolo::exist($id),
            'Categoria'      => FCategoria::exist($id),
            'Allergene'      => FAllergene::exist($id),
            'Piatto'         => FPiatto::exist($id),
            'Ordine'         => FOrdine::exist($id),
            default          => throw new InvalidArgumentException("exist: tipo '$tipo' non gestito."),
        };
    }

    // -----------------------------------------------------------------
    //  Helper: garantisce che l'id del genitore sia stato fornito per le
    //  entity che lo richiedono, con un messaggio d'errore chiaro.
    // -----------------------------------------------------------------
    private static function richiediGenitore(?int $idGenitore, string $tipo): int
    {
        if ($idGenitore === null) {
            throw new InvalidArgumentException(
                "store di $tipo richiede l'id del genitore come secondo argomento."
            );
        }
        return $idGenitore;
    }
}
