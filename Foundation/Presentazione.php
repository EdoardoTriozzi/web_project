<?php
declare(strict_types=1);

namespace Foundation;

use Smarty\Smarty;

/**
 * Presentazione — punto unico di configurazione di Smarty.
 *
 * A cosa serve: invece di configurare Smarty in ogni control (ripetendo le
 * cartelle dei template, della compilazione, ecc.), lo facciamo qui una volta
 * sola. I control chiedono a questa classe un'istanza gia' pronta di Smarty,
 * le assegnano i dati e mostrano il template.
 *
 * Uso tipico in un control:
 *   $view = Presentazione::crea();
 *   $view->assign('tavoli', $tavoli);
 *   $view->display('ristorante/tavoli.tpl');
 *
 * NOTA: Smarty "compila" i template in PHP nella cartella templates_c e puo'
 * tenerne una cache. Quelle cartelle devono essere scrivibili dal server web.
 */
final class Presentazione
{
    /** Istanza unica di Smarty (creata alla prima richiesta). */
    private static ?Smarty $smarty = null;

    /**
     * Restituisce un'istanza di Smarty gia' configurata e pronta all'uso.
     * La crea la prima volta, poi riusa sempre la stessa.
     */
    public static function crea(): Smarty
    {
        if (self::$smarty === null) {
            $smarty = new Smarty();

            // Cartelle di Smarty, calcolate rispetto alla radice del progetto.
            // __DIR__ qui e' .../foundation, quindi saliamo di un livello.
            $base = __DIR__ . '/..';

            // Dove stanno i file .tpl (i template scritti da noi).
            $smarty->setTemplateDir($base . '/presentation/templates/');
            // Dove Smarty mette i template "compilati" in PHP (cartella di lavoro).
            $smarty->setCompileDir($base . '/presentation/templates_c/');
            // Dove Smarty puo' tenere la cache (la lasciamo disattivata sotto).
            $smarty->setCacheDir($base . '/presentation/cache/');

            // Per un'app dinamica come questa teniamo la cache disattivata:
            // ogni pagina viene rigenerata, cosi' i dati sono sempre aggiornati.
            $smarty->setCaching(Smarty::CACHING_OFF);

            self::$smarty = $smarty;
        }

        return self::$smarty;
    }
}
