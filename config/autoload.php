<?php
declare(strict_types=1);

/**
 * Autoloader del progetto (stile PSR-4 semplificato).
 *
 * A cosa serve: evita di dover scrivere "require_once" per ogni classe.
 * PHP chiama automaticamente la funzione qui registrata ogni volta che
 * incontra una classe non ancora caricata; la funzione traduce il nome
 * completo della classe (namespace + nome) nel percorso del file e lo include.
 *
 * Convenzione usata:
 *   namespace  ->  cartella
 *   classe     ->  file con lo stesso nome + ".php"
 *
 * Esempi:
 *   Entity\Piatto          ->  <base>/entity/Piatto.php
 *   Foundation\PiattoMapper -> <base>/foundation/PiattoMapper.php
 *   Control\OrdineControl   -> <base>/control/OrdineControl.php
 *
 * Per includerlo basta, una volta sola all'avvio dell'app:
 *   require_once __DIR__ . '/config/autoload.php';
 * e da quel momento tutte le classi si caricano da sole.
 */

spl_autoload_register(function (string $classeCompleta): void {

    // Mappa: primo segmento del namespace -> cartella sul filesystem.
    // La chiave e' il namespace (come scritto nei file), il valore e'
    // il percorso della cartella corrispondente.
    $mappaNamespace = [
        'Entity'     => __DIR__ . '/../entity/',
        'Foundation' => __DIR__ . '/../foundation/',
        'Control'    => __DIR__ . '/../control/',
    ];

    // Divide "Entity\Piatto" in ["Entity", "Piatto"].
    $parti = explode('\\', $classeCompleta);

    // Il primo pezzo e' il namespace radice, l'ultimo e' il nome della classe.
    $namespaceRadice = $parti[0];
    $nomeClasse      = end($parti);

    // Se il namespace non e' tra quelli che gestiamo, lasciamo perdere:
    // potrebbe essere una classe di PHP o di un'altra libreria.
    if (!isset($mappaNamespace[$namespaceRadice])) {
        return;
    }

    // Costruisce il percorso del file e lo include se esiste.
    $percorso = $mappaNamespace[$namespaceRadice] . $nomeClasse . '.php';

    if (is_file($percorso)) {
        require_once $percorso;
    }
});

// ---------------------------------------------------------------------------
//  Caricamento di Smarty (libreria esterna per i template della UI).
//  Smarty 5 si puo' caricare senza Composer includendo Smarty.class.php, che
//  registra da se' il proprio autoloader. La libreria va scaricata e messa in
//  libs/smarty/ (vedi istruzioni di installazione).
// ---------------------------------------------------------------------------
$bootSmarty = __DIR__ . '/../libs/smarty/libs/Smarty.class.php';
if (is_file($bootSmarty)) {
    require_once $bootSmarty;
}
