<?php
declare(strict_types=1);

namespace Control;

/**
 * FrontController
 *
 * Punto di smistamento dell'applicazione (slide 19-22).
 * index.php crea un'istanza e ne invoca run().
 *
 * Strategia di mapping (scelta: "caso d'uso", non REST):
 *   - dalla richiesta legge   controller  (es. "Login")  e  action (es. "login")
 *   - compone il nome di classe   Control\C<Controller>   ->  Control\CLogin
 *   - usa  action  come nome del metodo da invocare
 *   - risolve classe e metodo via REFLECTION (class_exists / method_exists),
 *     senza if/switch sui singoli casi d'uso (slide 21)
 *
 * Le classi control vengono ISTANZIATE (new), come da scelta di progetto.
 */
final class FrontController
{
    /** Prefisso delle classi control, secondo le convenzioni del progetto (E/F/C). */
    private const PREFIX = 'C';

    /** Namespace in cui vivono le classi control. */
    private const NS = __NAMESPACE__ . '\\';

    /** Controller usato quando la richiesta non ne specifica uno. */
    private const DEFAULT_CONTROLLER = 'Login';

    /** Metodo usato quando la richiesta non specifica un'azione. */
    private const DEFAULT_ACTION = 'mostraLogin';

    /**
     * Avvia la gestione della richiesta corrente.
     * Equivalente del "main": ricostruisce (oggetto control, metodo) e lo esegue.
     */
    public function run(): void
    {
        $request = $this->parseRequest();

        $class  = self::NS . self::PREFIX . $request['controller']; // Control\CLogin
        $action = $request['action'];                               // login
        $params = $request['params'];                               // dati per il metodo

        // --- Risoluzione via reflection (slide 21) ---
        if (!class_exists($class)) {
            $this->fail(404, "Controller non trovato: {$request['controller']}");
            return;
        }
        if (!method_exists($class, $action)) {
            $this->fail(405, "Azione non consentita: {$action}");
            return;
        }

        $controller = new $class();   // istanza, come da scelta di progetto
        $controller->$action($params);
    }

    /**
     * Estrae controller, action e parametri dalla richiesta HTTP.
     *
     * Per ora la richiesta arriva come query string esplicita:
     *   index.php?controller=Login&action=login
     * (in locale su XAMPP funziona senza mod_rewrite). I restanti parametri
     * di $_GET e l'intero $_POST finiscono in 'params', a disposizione del metodo.
     *
     * @return array{controller:string, action:string, params:array<string,mixed>}
     */
    private function parseRequest(): array
    {
        // controller e action possono arrivare sia dall'URL (GET) sia da una
        // submit di form (POST). I form di login, per esempio, li inviano in
        // POST. Quindi li cerchiamo in entrambi: prima POST, poi GET come ripiego.
        $controllerRaw = $_POST['controller'] ?? $_GET['controller'] ?? self::DEFAULT_CONTROLLER;
        $actionRaw     = $_POST['action']     ?? $_GET['action']     ?? self::DEFAULT_ACTION;

        $controller = $this->sanitizeName($controllerRaw);
        $action     = $this->sanitizeName($actionRaw);

        if ($controller === '') {
            $controller = self::DEFAULT_CONTROLLER;
        }
        if ($action === '') {
            $action = self::DEFAULT_ACTION;
        }

        // controller e action sono "coordinate di routing": non sono dati del metodo
        $params = $_GET;
        unset($params['controller'], $params['action']);

        // i dati di una submit (POST) hanno priorita': sovrascrivono eventuali omonimi in GET
        $params = array_merge($params, $_POST);
        // anche dai params rimuoviamo controller/action: sono routing, non dati
        unset($params['controller'], $params['action']);

        return [
            'controller' => ucfirst($controller),
            'action'     => $action,
            'params'     => $params,
        ];
    }

    /**
     * Ripulisce un nome proveniente dall'esterno cosi' che possa diventare
     * un identificatore PHP (nome di classe o di metodo) in sicurezza:
     * tiene solo lettere, cifre e underscore.
     */
    private function sanitizeName(string $raw): string
    {
        return preg_replace('/[^A-Za-z0-9_]/', '', $raw) ?? '';
    }

    /**
     * Invia uno status HTTP di errore e termina (slide 21: 404 / 405).
     */
    private function fail(int $status, string $message): void
    {
        $map = [
            404 => '404 Not Found',
            405 => '405 Method Not Allowed',
        ];
        $line = $map[$status] ?? '500 Internal Server Error';

        header("HTTP/1.1 {$line}");
        header('Content-Type: text/plain; charset=utf-8');
        echo $message;
    }
}
