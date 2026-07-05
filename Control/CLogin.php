<?php
declare(strict_types=1);

namespace Control;

use Foundation\FRistorante;
use Foundation\FCucina;
use Foundation\FAmministratore;
use Foundation\FTavolo;
use Foundation\Session;
use Entity\Utente;

/**
 * CLogin — classe control del caso d'uso "Autenticazione".
 *
 * Strategia delle slide 9-10: una classe control per caso d'uso, un metodo
 * pubblico per ogni operazione di sistema. Ogni metodo riceve dal
 * FrontController l'array $params (dati della richiesta) ed e' invocabile
 * via reflection grazie alla firma uniforme (array $params).
 *
 * Particolarita' emersa leggendo la foundation: ci sono DUE modi di
 * autenticarsi, perche' i quattro ruoli non sono simmetrici.
 *
 *   - STAFF (ristorante, cucina, amministratore): si identifica con uno
 *     USERNAME. I mapper offrono loadByUsername. La facciata
 *     PersistentManager NON ha loadByUsername (fa solo CRUD per tipo),
 *     quindi qui si chiamano direttamente i tre mapper.
 *
 *   - TAVOLO: non ha username. Si accede indicando il RISTORANTE, il
 *     NUMERO del tavolo e la PASSWORD condivisa. Il mapper offre
 *     loadByNumero($ristoranteId, $numero); la password si verifica poi
 *     sull'oggetto (eredita verificaPassword da Utente).
 *
 * Operazioni di sistema di questo UC:
 *   - mostraLogin()        -> presenta la form di login staff
 *   - login()              -> autentica lo staff (username + password)
 *   - mostraLoginTavolo()  -> presenta la form di accesso tavolo
 *   - loginTavolo()        -> autentica un tavolo (ristorante + numero + password)
 *   - logout()             -> chiude la sessione (vale per tutti i ruoli)
 */
final class CLogin
{
    /**
     * Mapper da interrogare per il login dello staff, nell'ordine di prova.
     * Ogni voce associa il NOME DI RUOLO (quello che finira' in sessione)
     * alla classe mapper che espone loadByUsername per quel ruolo.
     *
     * SCELTA DI PROGETTO: si provano i mapper in sequenza finche' uno trova
     * lo username. E' trasparente per l'utente (non deve dichiarare il ruolo)
     * al costo di un massimo di tre query. Se preferisci che l'utente scelga
     * il ruolo nella form, basta leggere $params['ruolo'] e interrogare il
     * solo mapper corrispondente: cambia solo questo punto.
     *
     * @var array<string, class-string>
     */
    private const MAPPER_STAFF = [
        'ristorante'     => FRistorante::class,
        'cucina'         => FCucina::class,
        'amministratore' => FAmministratore::class,
    ];

    // ------------------------------------------------------------------
    //  Login dello staff (username + password)
    // ------------------------------------------------------------------

    /**
     * Operazione di sistema: mostra la form di login per lo staff.
     *
     * @param array<string,mixed> $params
     */
    public function mostraLogin(array $params): void
    {
        $this->mostraFormStaff(null);
    }

    /**
     * Operazione di sistema: autentica un utente dello staff.
     *
     * Flusso:
     *   1. legge username/password dai dati della richiesta;
     *   2. cerca lo username tra i mapper dello staff, nell'ordine;
     *   3. verifica la password con la logica di dominio (entity);
     *   4. se ok, salva ruolo + id in sessione e instrada per ruolo;
     *      altrimenti ripresenta il login con un errore generico.
     *
     * @param array<string,mixed> $params  attesi: 'username', 'password'
     */
    public function login(array $params): void
    {
        $username = trim((string)($params['username'] ?? ''));
        $password = (string)($params['password'] ?? '');

        if ($username === '' || $password === '') {
            $this->mostraFormStaff('Inserisci username e password.');
            return;
        }

        // Cerca lo username tra i ruoli dello staff.
        foreach (self::MAPPER_STAFF as $ruolo => $mapper) {
            /** @var Utente|null $utente */
            $utente = $mapper::loadByUsername($username);

            if ($utente instanceof Utente && $utente->verificaPassword($password)) {
                // In sessione si salvano solo ruolo + id (Session reale).
                Session::login($ruolo, (int) $utente->getId());
                $this->redirectPerRuolo($ruolo);
                return;
            }
        }

        // Nessun mapper ha trovato una corrispondenza valida.
        // Messaggio volutamente generico: non rivela se lo username esista.
        $this->mostraFormStaff('Credenziali non valide.');
    }

    // ------------------------------------------------------------------
    //  Login del tavolo (ristorante + numero + password condivisa)
    // ------------------------------------------------------------------

    /**
     * Operazione di sistema: mostra la form di accesso per un tavolo.
     *
     * @param array<string,mixed> $params
     */
    public function mostraLoginTavolo(array $params): void
    {
        $this->mostraFormTavolo(null);
    }

    /**
     * Operazione di sistema: autentica un tavolo.
     *
     * Il tavolo non ha username: lo si individua per (ristorante, numero) e
     * poi se ne verifica la password condivisa. Piu' dispositivi che
     * inseriscono gli stessi dati condividono lo stesso tavolo (e percio'
     * la stessa bozza d'ordine).
     *
     * @param array<string,mixed> $params  attesi: 'ristoranteId', 'numero', 'password'
     */
    public function loginTavolo(array $params): void
    {
        $ristoranteId = (int)($params['ristoranteId'] ?? 0);
        $numero       = trim((string)($params['numero'] ?? ''));
        $password     = (string)($params['password'] ?? '');

        if ($ristoranteId <= 0 || $numero === '' || $password === '') {
            $this->mostraFormTavolo('Inserisci ristorante, numero del tavolo e password.');
            return;
        }

        $tavolo = FTavolo::loadByNumero($ristoranteId, $numero);

        // Il tavolo deve esistere, avere una password attiva e che combaci.
        if ($tavolo === null || !$tavolo->haPassword() || !$tavolo->verificaPassword($password)) {
            $this->mostraFormTavolo('Accesso al tavolo non riuscito.');
            return;
        }

        Session::login('tavolo', (int) $tavolo->getId());
        $this->redirectPerRuolo('tavolo');
    }

    // ------------------------------------------------------------------
    //  Logout (comune a tutti i ruoli)
    // ------------------------------------------------------------------

    /**
     * Operazione di sistema: logout.
     *
     * @param array<string,mixed> $params
     */
    public function logout(array $params): void
    {
        Session::logout();
        $this->redirect('index.php?controller=Login&action=mostraLogin');
    }

    // ------------------------------------------------------------------
    //  Helper privati (non sono operazioni di sistema: il FrontController
    //  invoca solo i metodi il cui nome arriva nella richiesta).
    // ------------------------------------------------------------------

    /**
     * Manda l'utente alla home del proprio ruolo.
     * I controller di destinazione sono quelli degli altri casi d'uso,
     * che implementeremo nei prossimi passi.
     */
    private function redirectPerRuolo(string $ruolo): void
    {
        $destinazioni = [
            'amministratore' => 'index.php?controller=Amministratore&action=mostraRistoranti',
            'ristorante'     => 'index.php?controller=Ristorante&action=mostraHome',
            'cucina'         => 'index.php?controller=Cucina&action=mostraOrdini',
            'tavolo'         => 'index.php?controller=Tavolo&action=mostraMenu',
        ];
        $this->redirect($destinazioni[$ruolo] ?? 'index.php?controller=Login&action=mostraLogin');
    }

    /**
     * Mostra il form di login dello staff, eventualmente con un messaggio
     * d'errore (quando un tentativo precedente e' fallito). Prepara la
     * variabile $errore e include la view.
     */
    private function mostraFormStaff(?string $errore): void
    {
        $view = \Foundation\Presentazione::crea();
        $view->assign('errore', $errore);
        $view->display('login/staff.tpl');
    }

    /**
     * Mostra il form di accesso del tavolo, eventualmente con un messaggio
     * d'errore. Prepara la variabile $errore e include la view.
     */
    private function mostraFormTavolo(?string $errore): void
    {
        $view = \Foundation\Presentazione::crea();
        $view->assign('errore', $errore);
        $view->display('login/tavolo.tpl');
    }

    /**
     * Redirect HTTP. Va chiamato prima di qualsiasi output.
     * Quando arrivera' la presentation, i metodi di login termineranno con
     * redirect e non stamperanno nulla (gli echo qui sono temporanei).
     */
    private function redirect(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }
}
