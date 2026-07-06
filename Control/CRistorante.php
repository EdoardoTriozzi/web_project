<?php
declare(strict_types=1);

namespace Control;

use Foundation\Session;
use Foundation\FRistorante;
use Foundation\FPiatto;
use Foundation\FCategoria;
use Foundation\FAllergene;
use Foundation\FCucina;
use Foundation\FTavolo;
use Foundation\FOrdine;
use Foundation\FPagamento;
use Foundation\PersistentManager;
use Entity\Piatto;
use Entity\Categoria;
use Entity\Allergene;
use Entity\Cucina;
use Entity\Tavolo;
use Entity\StatoTavolo;
use Entity\StatoOrdine;
use Entity\Pagamento;
use Entity\PagamentoVoce;

/**
 * CRistorante — classe control del caso d'uso "Ristorante".
 *
 * E' il caso d'uso piu' ampio: il ristorante gestisce il proprio catalogo
 * (piatti, categorie, allergeni), le proprie risorse (cucine, tavoli) e
 * consulta le statistiche. La maggior parte sono operazioni CRUD; alcune
 * sono specifiche (generare la password di un tavolo, gestirne lo stato,
 * evadere la richiesta conto, i piatti piu' venduti).
 *
 * Tutte le operazioni passano da richiediRistorante(), che pretende un
 * ristorante loggato e ne restituisce l'id (dalla SESSIONE). L'id del
 * ristorante e' il "genitore" che le store della foundation richiedono e
 * il filtro con cui si verifica che un'entita' sia di competenza di questo
 * ristorante (un ristorante non puo' toccare roba di un altro).
 *
 * Le operazioni di sistema hanno la solita firma uniforme (array $params)
 * per l'invocazione via reflection dal FrontController.
 */
final class CRistorante
{
    /** Ruolo atteso in sessione per accedere a queste operazioni. */
    private const RUOLO = 'ristorante';

    // ==================================================================
    //  PIATTI
    // ==================================================================

    /**
     * Elenca i piatti del ristorante.
     *
     * @param array<string,mixed> $params
     */
    public function mostraPiatti(array $params): void
    {
        $ristoranteId = $this->richiediRistorante();
        $piatti       = FPiatto::loadByRistorante($ristoranteId);

        // TODO(presentation): passare $piatti (+ categorie, cucine, allergeni
        // per i menu a tendina del form) alla view di gestione.
        echo 'Piatti: ' . count($piatti) . '.';
    }

    /**
     * Crea un nuovo piatto. I riferimenti opzionali (categoria, cucina) e gli
     * allergeni si collegano dopo, tramite le operazioni dedicate, oppure qui
     * se gli id arrivano nei parametri (versione minima: solo i dati propri).
     *
     * @param array<string,mixed> $params  attesi: 'nome'; opzionali: 'prezzo',
     *                                      'descrizione', 'etichetta', 'categoriaId', 'cucinaId'
     */
    public function creaPiatto(array $params): void
    {
        $ristoranteId = $this->richiediRistorante();

        $nome = trim((string)($params['nome'] ?? ''));
        if ($nome === '') {
            $this->rispondiErrore('Il nome del piatto e\' obbligatorio.');
            return;
        }

        $piatto = new Piatto(
            $nome,
            (float)($params['prezzo'] ?? 0.0),
            $this->stringaOpzionale($params['descrizione'] ?? null),
            null,                                              // immagine: gestita a parte
            $this->stringaOpzionale($params['etichetta'] ?? null)
        );

        // Riferimenti opzionali, solo se appartengono a questo ristorante.
        $this->collegaCategoriaSeValida($piatto, $ristoranteId, $params);
        $this->collegaCucinaSeValida($piatto, $ristoranteId, $params);
        $this->collegaAllergeni($piatto, $ristoranteId, $params);

        PersistentManager::store($piatto, $ristoranteId);

        $this->rispondiPiatti($ristoranteId);
    }

    /**
     * Aggiorna i dati di un piatto esistente del ristorante.
     *
     * @param array<string,mixed> $params  atteso: 'piattoId'; piu' i campi da aggiornare
     */
    public function aggiornaPiatto(array $params): void
    {
        $ristoranteId = $this->richiediRistorante();
        $piattoId     = (int)($params['piattoId'] ?? 0);

        $piatto = $this->piattoDiCompetenza($ristoranteId, $piattoId);
        if ($piatto === null) {
            $this->rispondiErrore('Piatto non trovato o non di tua competenza.');
            return;
        }

        if (isset($params['nome'])) {
            $nome = trim((string) $params['nome']);
            if ($nome !== '') {
                $piatto->setNome($nome);
            }
        }
        if (isset($params['prezzo'])) {
            $piatto->setPrezzo((float) $params['prezzo']);
        }
        if (array_key_exists('descrizione', $params)) {
            $piatto->setDescrizione($this->stringaOpzionale($params['descrizione']));
        }
        if (array_key_exists('etichetta', $params)) {
            $piatto->setEtichetta($this->stringaOpzionale($params['etichetta']));
        }
        $this->collegaCategoriaSeValida($piatto, $ristoranteId, $params);
        $this->collegaCucinaSeValida($piatto, $ristoranteId, $params);
        $this->collegaAllergeni($piatto, $ristoranteId, $params);

        PersistentManager::update($piatto);

        $this->rispondiPiatti($ristoranteId);
    }

    /**
     * Elimina un piatto del ristorante.
     *
     * @param array<string,mixed> $params  atteso: 'piattoId'
     */
    public function eliminaPiatto(array $params): void
    {
        $ristoranteId = $this->richiediRistorante();
        $piattoId     = (int)($params['piattoId'] ?? 0);

        if ($this->piattoDiCompetenza($ristoranteId, $piattoId) === null) {
            $this->rispondiErrore('Piatto non trovato o non di tua competenza.');
            return;
        }

        PersistentManager::delete('Piatto', $piattoId);

        $this->rispondiPiatti($ristoranteId);
    }

    /**
     * Attiva o disattiva un piatto dal menu del ristorante. Usa gli stessi
     * metodi di dominio (abilita/disabilita) della cucina. JSON.
     *
     * @param array<string,mixed> $params  atteso: 'piattoId', 'disponibile' (1/0)
     */
    public function impostaDisponibilitaPiatto(array $params): void
    {
        $ristoranteId = $this->richiediRistorante();
        $piattoId     = (int) ($params['piattoId'] ?? 0);

        $piatto = $this->piattoDiCompetenza($ristoranteId, $piattoId);
        if ($piatto === null) {
            $this->rispondiErrore('Piatto non trovato o non di tua competenza.');
            return;
        }

        $abilita = (string) ($params['disponibile'] ?? '1') === '1';
        if ($abilita) {
            $piatto->abilita();
        } else {
            $piatto->disabilita();
        }
        PersistentManager::update($piatto);

        $this->rispondiPiatti($ristoranteId);
    }

    /**
     * Carica l'immagine di un piatto. A differenza delle altre azioni, qui
     * arriva un FILE (in $_FILES), non parametri JSON: i file si inviano con
     * un form multipart, non si possono mettere in JSON.
     *
     * Salva il file su disco (cartella img_piatti) e mette il PERCORSO nel
     * piatto. Esegue controlli di sicurezza: tipo immagine reale, dimensione
     * massima, nome del file generato (non si usa quello dell'utente).
     *
     * Risponde JSON (la pagina poi si ricarica o aggiorna la lista).
     *
     * @param array<string,mixed> $params  atteso: 'piattoId'; file in $_FILES['immagine']
     */
    public function caricaImmaginePiatto(array $params): void
    {
        $ristoranteId = $this->richiediRistorante();
        $piattoId     = (int) ($params['piattoId'] ?? 0);

        $piatto = $this->piattoDiCompetenza($ristoranteId, $piattoId);
        if ($piatto === null) {
            $this->rispondiErrore('Piatto non trovato o non di tua competenza.');
            return;
        }

        // Il file deve essere arrivato correttamente.
        if (!isset($_FILES['immagine']) || $_FILES['immagine']['error'] !== UPLOAD_ERR_OK) {
            $this->rispondiErrore('Nessun file caricato o errore nel caricamento.');
            return;
        }

        $file = $_FILES['immagine'];

        // 1) Limite di dimensione: 2 MB. Evita di riempire il disco.
        $maxByte = 2 * 1024 * 1024;
        if ($file['size'] > $maxByte) {
            $this->rispondiErrore('L\'immagine e\' troppo grande (massimo 2 MB).');
            return;
        }

        // 2) Verifica che sia DAVVERO un'immagine (non ci si fida del nome).
        //    getimagesize legge il contenuto: se non e' un'immagine, fallisce.
        $info = @getimagesize($file['tmp_name']);
        if ($info === false) {
            $this->rispondiErrore('Il file non e\' un\'immagine valida.');
            return;
        }

        // 3) Solo formati immagine ammessi, con la loro estensione "vera".
        $estensioni = [
            IMAGETYPE_JPEG => 'jpg',
            IMAGETYPE_PNG  => 'png',
            IMAGETYPE_GIF  => 'gif',
            IMAGETYPE_WEBP => 'webp',
        ];
        $tipo = $info[2];
        if (!isset($estensioni[$tipo])) {
            $this->rispondiErrore('Formato non supportato. Usa JPG, PNG, GIF o WEBP.');
            return;
        }
        $est = $estensioni[$tipo];

        // 4) Nome del file generato da noi (mai quello dell'utente): id del
        //    piatto + un pezzo casuale, cosi' caricando una nuova immagine il
        //    nome cambia ed evitiamo problemi di cache del browser.
        $nomeFile = 'piatto_' . $piattoId . '_' . bin2hex(random_bytes(4)) . '.' . $est;

        $cartellaRel = 'presentation/assets/img_piatti/';
        $cartellaAbs = __DIR__ . '/../' . $cartellaRel;
        $percorsoAbs = $cartellaAbs . $nomeFile;
        $percorsoRel = $cartellaRel . $nomeFile;   // questo va nel database

        // 5) Sposta il file caricato nella cartella delle immagini.
        if (!move_uploaded_file($file['tmp_name'], $percorsoAbs)) {
            $this->rispondiErrore('Impossibile salvare l\'immagine. Riprova.');
            return;
        }

        // 6) Cancella la vecchia immagine, se c'era, per non accumulare file.
        $vecchia = $piatto->getImmagine();
        if ($vecchia !== null && $vecchia !== '' && is_file(__DIR__ . '/../' . $vecchia)) {
            @unlink(__DIR__ . '/../' . $vecchia);
        }

        // 7) Salva il PERCORSO nel piatto (non il file: il file e' su disco).
        $piatto->setImmagine($percorsoRel);
        PersistentManager::update($piatto);

        $this->rispondiPiatti($ristoranteId);
    }

    /**
     * Rimuove l'immagine di un piatto: cancella il file e azzera il percorso.
     * JSON.
     *
     * @param array<string,mixed> $params  atteso: 'piattoId'
     */
    public function rimuoviImmaginePiatto(array $params): void
    {
        $ristoranteId = $this->richiediRistorante();
        $piattoId     = (int) ($params['piattoId'] ?? 0);

        $piatto = $this->piattoDiCompetenza($ristoranteId, $piattoId);
        if ($piatto === null) {
            $this->rispondiErrore('Piatto non trovato o non di tua competenza.');
            return;
        }

        $vecchia = $piatto->getImmagine();
        if ($vecchia !== null && $vecchia !== '' && is_file(__DIR__ . '/../' . $vecchia)) {
            @unlink(__DIR__ . '/../' . $vecchia);
        }

        $piatto->setImmagine(null);
        PersistentManager::update($piatto);

        $this->rispondiPiatti($ristoranteId);
    }

    // ==================================================================
    //  CATEGORIE
    // ==================================================================

    /**
     * @param array<string,mixed> $params
     */
    public function mostraCategorie(array $params): void
    {
        $ristoranteId = $this->richiediRistorante();
        $categorie    = FCategoria::loadByRistorante($ristoranteId);

        echo 'Categorie: ' . count($categorie) . '.';
    }

    /**
     * @param array<string,mixed> $params  atteso: 'nome'; opzionale: 'posizione'
     */
    public function creaCategoria(array $params): void
    {
        $ristoranteId = $this->richiediRistorante();

        $nome = trim((string)($params['nome'] ?? ''));
        if ($nome === '') {
            $this->rispondiErrore('Il nome della categoria e\' obbligatorio.');
            return;
        }

        $categoria = new Categoria($nome, (int)($params['posizione'] ?? 0));
        PersistentManager::store($categoria, $ristoranteId);

        $this->rispondiCategorie($ristoranteId);
    }

    /**
     * @param array<string,mixed> $params  atteso: 'categoriaId'; opzionali: 'nome', 'posizione'
     */
    public function aggiornaCategoria(array $params): void
    {
        $ristoranteId = $this->richiediRistorante();
        $categoriaId  = (int)($params['categoriaId'] ?? 0);

        $categoria = $this->categoriaDiCompetenza($ristoranteId, $categoriaId);
        if ($categoria === null) {
            $this->rispondiErrore('Categoria non trovata o non di tua competenza.');
            return;
        }

        if (isset($params['nome'])) {
            $nome = trim((string) $params['nome']);
            if ($nome !== '') {
                $categoria->setNome($nome);
            }
        }
        if (isset($params['posizione'])) {
            $categoria->setPosizione((int) $params['posizione']);
        }

        PersistentManager::update($categoria);

        $this->rispondiCategorie($ristoranteId);
    }

    /**
     * @param array<string,mixed> $params  atteso: 'categoriaId'
     */
    public function eliminaCategoria(array $params): void
    {
        $ristoranteId = $this->richiediRistorante();
        $categoriaId  = (int)($params['categoriaId'] ?? 0);

        if ($this->categoriaDiCompetenza($ristoranteId, $categoriaId) === null) {
            $this->rispondiErrore('Categoria non trovata o non di tua competenza.');
            return;
        }

        PersistentManager::delete('Categoria', $categoriaId);

        $this->rispondiCategorie($ristoranteId);
    }

    // ==================================================================
    //  ALLERGENI (catalogo del ristorante)
    // ==================================================================

    /**
     * @param array<string,mixed> $params
     */
    public function mostraAllergeni(array $params): void
    {
        $ristoranteId = $this->richiediRistorante();
        $allergeni    = FAllergene::loadByRistorante($ristoranteId);

        echo 'Allergeni: ' . count($allergeni) . '.';
    }

    /**
     * @param array<string,mixed> $params  atteso: 'nome'
     */
    public function creaAllergene(array $params): void
    {
        $ristoranteId = $this->richiediRistorante();

        $nome = trim((string)($params['nome'] ?? ''));
        if ($nome === '') {
            $this->rispondiErrore('Il nome dell\'allergene e\' obbligatorio.');
            return;
        }

        $allergene = new Allergene($nome);
        PersistentManager::store($allergene, $ristoranteId);

        $this->rispondiAllergeni($ristoranteId);
    }

    /**
     * @param array<string,mixed> $params  atteso: 'allergeneId'
     */
    public function eliminaAllergene(array $params): void
    {
        $ristoranteId = $this->richiediRistorante();
        $allergeneId  = (int)($params['allergeneId'] ?? 0);

        if ($this->allergeneDiCompetenza($ristoranteId, $allergeneId) === null) {
            $this->rispondiErrore('Allergene non trovato o non di tua competenza.');
            return;
        }

        PersistentManager::delete('Allergene', $allergeneId);

        $this->rispondiAllergeni($ristoranteId);
    }

    // ==================================================================
    //  CUCINE
    // ==================================================================

    /**
     * @param array<string,mixed> $params
     */
    public function mostraCucine(array $params): void
    {
        $ristoranteId   = $this->richiediRistorante();
        $nomeRistorante = $this->nomeRistorante($ristoranteId);

        $cucineIniziali = htmlspecialchars(
            json_encode($this->cucineInArray($ristoranteId)),
            ENT_QUOTES, 'UTF-8'
        );

        $view = \Foundation\Presentazione::crea();
        $view->assign('nomeRistorante', $nomeRistorante);
        $view->assign('cucineIniziali', $cucineIniziali);
        $view->display('ristorante/cucine.tpl');
    }

    /**
     * Crea una cucina con credenziali proprie. La password arriva in chiaro
     * dal form e viene hashata (setPassword) prima del salvataggio.
     *
     * @param array<string,mixed> $params  attesi: 'nome', 'username', 'password'
     */
    public function creaCucina(array $params): void
    {
        $ristoranteId = $this->richiediRistorante();

        $nome     = trim((string)($params['nome'] ?? ''));
        $username = trim((string)($params['username'] ?? ''));
        $password = (string)($params['password'] ?? '');

        if ($nome === '' || $username === '' || $password === '') {
            $this->rispondiErrore('Nome, username e password della cucina sono obbligatori.');
            return;
        }

        // Si costruisce con hash vuoto e poi si imposta la password (che la hasha).
        $cucina = new Cucina($nome, $username, '');
        $cucina->setPassword($password);

        PersistentManager::store($cucina, $ristoranteId);

        $this->rispondiCucine($ristoranteId);
    }

    /**
     * @param array<string,mixed> $params  atteso: 'cucinaId'
     */
    public function eliminaCucina(array $params): void
    {
        $ristoranteId = $this->richiediRistorante();
        $cucinaId     = (int)($params['cucinaId'] ?? 0);

        if ($this->cucinaDiCompetenza($ristoranteId, $cucinaId) === null) {
            $this->rispondiErrore('Cucina non trovata o non di tua competenza.');
            return;
        }

        PersistentManager::delete('Cucina', $cucinaId);

        $this->rispondiCucine($ristoranteId);
    }

    /**
     * Imposta la password di una cucina esistente. La password (in chiaro)
     * arriva nel parametro 'password': puo' essere scelta dal ristorante o
     * generata casualmente lato client. Risponde JSON con la password
     * impostata, cosi' la UI puo' mostrarla.
     *
     * @param array<string,mixed> $params  atteso: 'cucinaId', 'password'
     */
    public function impostaPasswordCucina(array $params): void
    {
        $ristoranteId = $this->richiediRistorante();
        $cucinaId     = (int) ($params['cucinaId'] ?? 0);
        $password     = (string) ($params['password'] ?? '');

        $cucina = $this->cucinaDiCompetenza($ristoranteId, $cucinaId);
        if ($cucina === null) {
            $this->rispondiErrore('Cucina non trovata o non di tua competenza.');
            return;
        }
        if ($password === '') {
            $this->rispondiErrore('La password della cucina non puo\' essere vuota.');
            return;
        }

        $cucina->setPassword($password);   // hash
        PersistentManager::update($cucina);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'ok'                => true,
            'cucine'            => $this->cucineInArray($ristoranteId),
            'passwordImpostata' => $password,
            'cucinaId'          => $cucinaId,
        ]);
    }

    // ==================================================================
    //  TAVOLI
    // ==================================================================

    /**
     * @param array<string,mixed> $params
     */
    /**
     * Operazione di sistema: home del pannello ristorante.
     * Mostra le sei sezioni (tavoli, cucine, conti, menu, statistiche, profilo).
     *
     * @param array<string,mixed> $params
     */
    public function mostraHome(array $params): void
    {
        $ristoranteId   = $this->richiediRistorante();
        $nomeRistorante = $this->nomeRistorante($ristoranteId);

        $view = \Foundation\Presentazione::crea();
        $view->assign('nomeRistorante', $nomeRistorante);
        $view->display('ristorante/home.tpl');
    }

    // ------------------------------------------------------------------
    //  Segnaposto delle sezioni non ancora costruite: mostrano una pagina
    //  "in costruzione" invece di un errore. Verranno sostituiti man mano.
    // ------------------------------------------------------------------
    public function mostraMenu(array $params): void
    {
        $ristoranteId   = $this->richiediRistorante();
        $nomeRistorante = $this->nomeRistorante($ristoranteId);

        $datiMenu = htmlspecialchars(json_encode([
            'piatti'    => $this->piattiInArray($ristoranteId),
            'categorie' => $this->categorieInArray($ristoranteId),
            'allergeni' => $this->allergeniInArray($ristoranteId),
            'cucine'    => $this->cucineInArray($ristoranteId),
        ]), ENT_QUOTES, 'UTF-8');

        $view = \Foundation\Presentazione::crea();
        $view->assign('nomeRistorante', $nomeRistorante);
        $view->assign('datiMenu', $datiMenu);
        $view->display('ristorante/menu.tpl');
    }
    public function mostraStatistiche(array $params): void
    {
        $ristoranteId   = $this->richiediRistorante();
        $nomeRistorante = $this->nomeRistorante($ristoranteId);

        // Periodo selezionato (default: tutto). Determina la data minima da cui
        // contare i pagamenti. I periodi disponibili sono mostrati come link.
        $periodo = (string) ($params['periodo'] ?? 'tutto');
        $dataMin = $this->dataMinPerPeriodo($periodo);

        // Le statistiche leggono dai PAGAMENTI (archivio storico), non dagli
        // ordini, che dopo il pagamento vengono cancellati. Con filtro periodo.
        $vendite    = FPagamento::conteggioVenditeByRistorante($ristoranteId, $dataMin);
        $incasso    = FPagamento::incassoByRistorante($ristoranteId, $dataMin);
        $nPagamenti = FPagamento::numeroPagamentiByRistorante($ristoranteId, $dataMin);

        // Prepara la classifica piatti come array semplice per il grafico.
        // Calcola anche la larghezza percentuale di ogni barra rispetto al
        // piatto piu' venduto (il primo, visto che sono gia' ordinati).
        $maxQta = 0;
        foreach ($vendite as $v) {
            if ($v['quantita_totale'] > $maxQta) {
                $maxQta = $v['quantita_totale'];
            }
        }
        $classifica = [];
        foreach ($vendite as $v) {
            $classifica[] = [
                'nome'       => $v['nome'],
                'quantita'   => $v['quantita_totale'],
                // percentuale rispetto al massimo (per la larghezza della barra)
                'percentuale'=> $maxQta > 0 ? round($v['quantita_totale'] / $maxQta * 100) : 0,
            ];
        }

        // Scontrino medio (incasso diviso numero pagamenti).
        $medio = $nPagamenti > 0 ? $incasso / $nPagamenti : 0.0;

        $view = \Foundation\Presentazione::crea();
        $view->assign('nomeRistorante', $nomeRistorante);
        $view->assign('classifica', $classifica);
        $view->assign('incasso', number_format($incasso, 2, ',', '.'));
        $view->assign('nPagamenti', $nPagamenti);
        $view->assign('scontrinoMedio', number_format($medio, 2, ',', '.'));
        $view->assign('haDati', count($classifica) > 0);
        $view->assign('periodo', $periodo);
        $view->assign('periodi', $this->elencoPeriodi());
        $view->display('ristorante/statistiche.tpl');
    }

    /** Elenco dei periodi selezionabili: chiave => etichetta. */
    private function elencoPeriodi(): array
    {
        return [
            'tutto'     => 'Sempre',
            'anno'      => 'Ultimo anno',
            'sei_mesi'  => 'Ultimi 6 mesi',
            'tre_mesi'  => 'Ultimi 3 mesi',
            'mese'      => 'Ultimo mese',
            'settimana' => 'Ultima settimana',
            'giorno'    => 'Ultimo giorno',
        ];
    }

    /**
     * Data minima (stringa 'Y-m-d H:i:s') da cui contare, per un dato periodo.
     * Restituisce null per 'tutto' (nessun filtro).
     */
    private function dataMinPerPeriodo(string $periodo): ?string
    {
        $offset = match ($periodo) {
            'anno'      => '-1 year',
            'sei_mesi'  => '-6 months',
            'tre_mesi'  => '-3 months',
            'mese'      => '-1 month',
            'settimana' => '-1 week',
            'giorno'    => '-1 day',
            default     => null,   // 'tutto'
        };
        if ($offset === null) {
            return null;
        }
        return (new \DateTimeImmutable())->modify($offset)->format('Y-m-d H:i:s');
    }
    public function mostraProfilo(array $params): void
    {
        $ristoranteId   = $this->richiediRistorante();
        $ristorante     = FRistorante::load($ristoranteId);
        $nomeRistorante = $ristorante !== null ? $ristorante->getNome() : 'Ristorante';
        $username       = $ristorante !== null ? $ristorante->getUsername() : '';

        $view = \Foundation\Presentazione::crea();
        $view->assign('nomeRistorante', $nomeRistorante);
        $view->assign('username', $username);
        $view->assign('idRistorante', $ristoranteId);
        $view->display('ristorante/profilo.tpl');
    }

    /**
     * Aggiorna il nome del ristorante. JSON.
     *
     * @param array<string,mixed> $params  atteso: 'nome'
     */
    public function aggiornaNomeRistorante(array $params): void
    {
        $ristoranteId = $this->richiediRistorante();
        $nome         = trim((string) ($params['nome'] ?? ''));

        if ($nome === '') {
            $this->rispondiErrore('Il nome del ristorante non puo\' essere vuoto.');
            return;
        }

        $ristorante = FRistorante::load($ristoranteId);
        if ($ristorante === null) {
            $this->rispondiErrore('Ristorante non trovato.');
            return;
        }

        $ristorante->setNome($nome);
        FRistorante::update($ristorante);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => true, 'nome' => $nome]);
    }

    /**
     * Cambia la password del ristorante. Per sicurezza richiede la password
     * attuale: cosi' non si puo' cambiare se non si conosce quella in uso.
     * JSON.
     *
     * @param array<string,mixed> $params  atteso: 'attuale', 'nuova'
     */
    public function cambiaPasswordRistorante(array $params): void
    {
        $ristoranteId = $this->richiediRistorante();
        $attuale      = (string) ($params['attuale'] ?? '');
        $nuova        = (string) ($params['nuova'] ?? '');

        if ($nuova === '') {
            $this->rispondiErrore('La nuova password non puo\' essere vuota.');
            return;
        }

        $ristorante = FRistorante::load($ristoranteId);
        if ($ristorante === null) {
            $this->rispondiErrore('Ristorante non trovato.');
            return;
        }

        // Verifica la password attuale prima di consentire il cambio.
        if (!$ristorante->verificaPassword($attuale)) {
            $this->rispondiErrore('La password attuale non e\' corretta.');
            return;
        }

        $ristorante->setPassword($nuova);   // hash
        FRistorante::update($ristorante);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => true]);
    }

    // ==================================================================
    //  CONTI E PAGAMENTI
    // ==================================================================

    /**
     * Pagina Conti: due sezioni. In alto i tavoli che hanno richiesto il
     * conto (da gestire), sotto la panoramica di tutti i tavoli con quanto
     * hanno speso finora.
     *
     * @param array<string,mixed> $params
     */
    public function mostraConti(array $params): void
    {
        $ristoranteId   = $this->richiediRistorante();
        $nomeRistorante = $this->nomeRistorante($ristoranteId);
        $tavoli         = FTavolo::loadByRistorante($ristoranteId);

        $righe = [];
        foreach ($tavoli as $t) {
            $speso = $this->spesoFinora((int) $t->getId());
            $righe[] = [
                'id'             => (int) $t->getId(),
                'numero'         => $t->getNumero(),
                'stato'          => $t->getStato()->value,
                'coperti'        => $t->getCoperti(),
                'contoRichiesto' => $t->isContoRichiesto(),
                'speso'          => number_format($speso, 2, ',', '.'),
                'spesoNum'       => $speso,
            ];
        }

        // Ordinamento naturale dei tavoli (1, 2, 11) come nella gestione tavoli.
        usort($righe, function ($a, $b) {
            return strnatcasecmp($a['numero'], $b['numero']);
        });

        $view = \Foundation\Presentazione::crea();
        $view->assign('nomeRistorante', $nomeRistorante);
        $view->assign('tavoli', $righe);
        $view->display('ristorante/conti.tpl');
    }

    /**
     * Dettaglio del conto di un tavolo (JSON): l'elenco di tutto cio' che ha
     * ordinato, voce per voce, piu' l'elenco dei piatti del menu (per poter
     * aggiungere). Serve alla schermata di correzione del conto.
     *
     * @param array<string,mixed> $params
     */
    public function dettaglioConto(array $params): void
    {
        $ristoranteId = $this->richiediRistorante();
        $tavoloId     = (int) ($params['tavoloId'] ?? 0);

        $tavolo = $this->tavoloDiCompetenza($ristoranteId, $tavoloId);
        if ($tavolo === null) {
            $this->rispondiErrore('Tavolo non trovato o non di tua competenza.');
            return;
        }

        // Voci ordinate finora dal tavolo (aggregate per piatto), escluse le bozze.
        $voci = $this->vociOrdinate($tavoloId);

        // Catalogo dei piatti del ristorante, per poterne aggiungere.
        $catalogo = [];
        foreach (FPiatto::loadByRistorante($ristoranteId) as $p) {
            $catalogo[] = [
                'id'     => (int) $p->getId(),
                'nome'   => $p->getNome(),
                'prezzo' => $p->getPrezzo(),
            ];
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'ok'       => true,
            'tavoloId' => $tavoloId,
            'numero'   => $tavolo->getNumero(),
            'voci'     => $voci,
            'catalogo' => $catalogo,
        ]);
    }

    /**
     * Conferma il pagamento di un tavolo. Riceve le voci finali (gia'
     * corrette dal ristorante) e, in una sola transazione logica:
     *   1. salva il pagamento con le sue voci (archivio storico);
     *   2. cancella gli ordini del tavolo dal database;
     *   3. azzera la richiesta conto e rimette il tavolo libero.
     *
     * L'ordine e' garantito: prima si salva il pagamento (cosi' i dati
     * storici sono al sicuro), poi si cancellano gli ordini.
     *
     * Le voci arrivano come JSON nel parametro 'voci':
     *   [ { "piattoId": int|null, "nome": string, "quantita": int, "prezzo": float }, ... ]
     *
     * @param array<string,mixed> $params
     */
    public function confermaPagamento(array $params): void
    {
        $ristoranteId = $this->richiediRistorante();
        $tavoloId     = (int) ($params['tavoloId'] ?? 0);

        $tavolo = $this->tavoloDiCompetenza($ristoranteId, $tavoloId);
        if ($tavolo === null) {
            $this->rispondiErrore('Tavolo non trovato o non di tua competenza.');
            return;
        }

        // Decodifica le voci finali inviate dalla schermata.
        $vociRaw = json_decode((string) ($params['voci'] ?? '[]'), true);
        if (!is_array($vociRaw) || count($vociRaw) === 0) {
            $this->rispondiErrore('Non ci sono voci da pagare.');
            return;
        }

        // Costruisce il Pagamento con le voci.
        $pagamento = new Pagamento($tavolo->getNumero(), $ristoranteId);
        foreach ($vociRaw as $v) {
            $nome     = trim((string) ($v['nome'] ?? ''));
            $quantita = (int) ($v['quantita'] ?? 0);
            $prezzo   = (float) ($v['prezzo'] ?? 0);
            $piattoId = isset($v['piattoId']) && $v['piattoId'] !== null ? (int) $v['piattoId'] : null;
            if ($nome === '' || $quantita <= 0) {
                continue; // salta voci non valide
            }
            $pagamento->aggiungiVoce(new PagamentoVoce($nome, $quantita, $prezzo, $piattoId));
        }

        if (count($pagamento->getVoci()) === 0) {
            $this->rispondiErrore('Nessuna voce valida da pagare.');
            return;
        }

        // 1) salva il pagamento (con transazione interna su testata + voci)
        $salvato = PersistentManager::store($pagamento, $ristoranteId);
        if (!$salvato) {
            $this->rispondiErrore('Errore nel salvataggio del pagamento. Riprova.');
            return;
        }

        // 2) cancella gli ordini del tavolo (i dati storici sono gia' salvi)
        foreach (FOrdine::loadByTavolo($tavoloId) as $ordine) {
            PersistentManager::delete('Ordine', (int) $ordine->getId());
        }

        // 3) azzera richiesta conto e libera il tavolo (chiude la sessione)
        $tavolo->azzeraRichiestaConto();
        $tavolo->setStato(StatoTavolo::LIBERO);
        PersistentManager::update($tavolo);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'ok'      => true,
            'totale'  => number_format($pagamento->totale(), 2, ',', '.'),
            'numero'  => $tavolo->getNumero(),
        ]);
    }

    /**
     * Evade la richiesta di conto di un tavolo senza registrare un pagamento
     * (es. richiesta annullata): azzera solo la richiesta. JSON.
     *
     * @param array<string,mixed> $params
     */
    public function annullaRichiestaConto(array $params): void
    {
        $ristoranteId = $this->richiediRistorante();
        $tavoloId     = (int) ($params['tavoloId'] ?? 0);

        $tavolo = $this->tavoloDiCompetenza($ristoranteId, $tavoloId);
        if ($tavolo === null) {
            $this->rispondiErrore('Tavolo non trovato o non di tua competenza.');
            return;
        }

        $tavolo->azzeraRichiestaConto();
        PersistentManager::update($tavolo);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => true]);
    }

    // --- helper per i conti -------------------------------------------

    /** Somma di quanto un tavolo ha speso finora (ordini non in bozza). */
    private function spesoFinora(int $tavoloId): float
    {
        $somma = 0.0;
        foreach (FOrdine::loadByTavolo($tavoloId) as $ordine) {
            if ($ordine->getStato() !== StatoOrdine::BOZZA) {
                $somma += $ordine->totale();
            }
        }
        return $somma;
    }

    /**
     * Voci ordinate da un tavolo, aggregate per piatto (quantita' sommate),
     * escluse le bozze. Ogni voce: piattoId, nome, quantita, prezzo.
     *
     * @return array<int, array{piattoId:int, nome:string, quantita:int, prezzo:float}>
     */
    private function vociOrdinate(int $tavoloId): array
    {
        $perPiatto = [];
        foreach (FOrdine::loadByTavolo($tavoloId) as $ordine) {
            if ($ordine->getStato() === StatoOrdine::BOZZA) {
                continue;
            }
            foreach ($ordine->getRighe() as $riga) {
                $piatto = $riga->getPiatto();
                $pid    = (int) $piatto->getId();
                if (!isset($perPiatto[$pid])) {
                    $perPiatto[$pid] = [
                        'piattoId' => $pid,
                        'nome'     => $piatto->getNome(),
                        'quantita' => 0,
                        'prezzo'   => $riga->getPrezzoUnitario(),
                    ];
                }
                $perPiatto[$pid]['quantita'] += $riga->getQuantita();
            }
        }
        return array_values($perPiatto);
    }

    /** Paginetta temporanea per le sezioni ancora da costruire. */
    private function sezioneInCostruzione(string $nome): void
    {
        $this->richiediRistorante();
        $titolo = htmlspecialchars($nome, ENT_QUOTES, 'UTF-8');
        echo '<!DOCTYPE html><html lang="it"><head><meta charset="UTF-8">'
           . '<meta name="viewport" content="width=device-width, initial-scale=1.0">'
           . '<title>' . $titolo . '</title>'
           . '<link rel="stylesheet" href="presentation/assets/ristorante.css"></head><body>'
           . '<div class="r-wrap"><header class="r-top"><div class="r-crumbs">'
           . '<a href="index.php?controller=Ristorante&action=mostraHome" class="r-back">&larr; Home</a>'
           . '<span class="r-sep">/</span><span class="r-here">' . $titolo . '</span></div></header>'
           . '<main class="r-main"><div class="r-card"><div class="r-empty">'
           . 'La sezione &ldquo;' . $titolo . '&rdquo; &egrave; in costruzione.</div></div></main></div></body></html>';
    }

    public function mostraTavoli(array $params): void
    {
        $ristoranteId   = $this->richiediRistorante();
        $nomeRistorante = $this->nomeRistorante($ristoranteId);

        // Dati iniziali per il JavaScript: la lista dei tavoli come JSON,
        // reso sicuro per l'attributo HTML (riusa tavoliInArray).
        $tavoliIniziali = htmlspecialchars(
            json_encode($this->tavoliInArray($ristoranteId)),
            ENT_QUOTES, 'UTF-8'
        );

        $view = \Foundation\Presentazione::crea();
        $view->assign('nomeRistorante', $nomeRistorante);
        $view->assign('tavoliIniziali', $tavoliIniziali);
        $view->display('ristorante/tavoli.tpl');
    }

    /**
     * Crea un tavolo. Il numero e i coperti sono dati propri; la password
     * condivisa e' opzionale alla creazione (puo' essere impostata dopo con
     * generaPasswordTavolo).
     *
     * @param array<string,mixed> $params  atteso: 'numero'; opzionali: 'coperti', 'password'
     */
    public function creaTavolo(array $params): void
    {
        $ristoranteId = $this->richiediRistorante();

        $numero = trim((string)($params['numero'] ?? ''));
        if ($numero === '') {
            $this->rispondiErrore('Il numero del tavolo e\' obbligatorio.');
            return;
        }

        $tavolo = new Tavolo($numero, null, StatoTavolo::LIBERO, (int)($params['coperti'] ?? 0));

        // Password condivisa opzionale gia' in creazione.
        $password = (string)($params['password'] ?? '');
        if ($password !== '') {
            $tavolo->setPassword($password);
        }

        PersistentManager::store($tavolo, $ristoranteId);

        $this->rispondiTavoli($ristoranteId);
    }

    /**
     * Imposta/rigenera la password condivisa di un tavolo. La password arriva
     * in chiaro dal ristorante (che la digita) e viene hashata: il ristorante
     * la comunica ai commensali, il database ne conserva solo l'hash.
     *
     * @param array<string,mixed> $params  attesi: 'tavoloId', 'password'
     */
    public function generaPasswordTavolo(array $params): void
    {
        $ristoranteId = $this->richiediRistorante();
        $tavoloId     = (int)($params['tavoloId'] ?? 0);
        $password     = (string)($params['password'] ?? '');

        $tavolo = $this->tavoloDiCompetenza($ristoranteId, $tavoloId);
        if ($tavolo === null) {
            $this->rispondiErrore('Tavolo non trovato o non di tua competenza.');
            return;
        }
        if ($password === '') {
            $this->rispondiErrore('La password del tavolo non puo\' essere vuota.');
            return;
        }

        $tavolo->setPassword($password);   // hash
        PersistentManager::update($tavolo);

        // Restituisce anche la password impostata, cosi' la UI puo' mostrarla.
        $this->rispondiTavoli($ristoranteId, ['passwordImpostata' => $password, 'tavoloId' => $tavoloId]);
    }

    /**
     * Cambia lo stato di un tavolo (libero/occupato).
     *
     * @param array<string,mixed> $params  attesi: 'tavoloId', 'stato' ('libero'|'occupato')
     */
    public function impostaStatoTavolo(array $params): void
    {
        $ristoranteId = $this->richiediRistorante();
        $tavoloId     = (int)($params['tavoloId'] ?? 0);
        $statoRaw     = (string)($params['stato'] ?? '');

        $tavolo = $this->tavoloDiCompetenza($ristoranteId, $tavoloId);
        if ($tavolo === null) {
            $this->rispondiErrore('Tavolo non trovato o non di tua competenza.');
            return;
        }

        // tryFrom: null se la stringa non e' un valore valido dell'enum.
        $stato = StatoTavolo::tryFrom($statoRaw);
        if ($stato === null) {
            $this->rispondiErrore('Stato del tavolo non valido.');
            return;
        }

        $tavolo->setStato($stato);
        PersistentManager::update($tavolo);

        $this->rispondiTavoli($ristoranteId);
    }

    /**
     * Evade la richiesta conto di un tavolo: abbassa il flag contoRichiesto.
     *
     * @param array<string,mixed> $params  atteso: 'tavoloId'
     */
    public function evadiRichiestaConto(array $params): void
    {
        $ristoranteId = $this->richiediRistorante();
        $tavoloId     = (int)($params['tavoloId'] ?? 0);

        $tavolo = $this->tavoloDiCompetenza($ristoranteId, $tavoloId);
        if ($tavolo === null) {
            $this->errore('Tavolo non trovato o non di tua competenza.');
            return;
        }

        $tavolo->azzeraRichiestaConto();
        PersistentManager::update($tavolo);

        $this->redirect('index.php?controller=Ristorante&action=mostraTavoli');
    }

    /**
     * Elimina un tavolo del ristorante.
     *
     * @param array<string,mixed> $params  atteso: 'tavoloId'
     */
    public function eliminaTavolo(array $params): void
    {
        $ristoranteId = $this->richiediRistorante();
        $tavoloId     = (int)($params['tavoloId'] ?? 0);

        if ($this->tavoloDiCompetenza($ristoranteId, $tavoloId) === null) {
            $this->rispondiErrore('Tavolo non trovato o non di tua competenza.');
            return;
        }

        PersistentManager::delete('Tavolo', $tavoloId);

        $this->rispondiTavoli($ristoranteId);
    }

    // ==================================================================
    //  STATISTICHE
    // ==================================================================

    /**
     * Mostra i piatti piu' venduti del ristorante, usando il conteggio
     * aggregato fornito dalla foundation.
     *
     * @param array<string,mixed> $params
     */
    public function piattiPiuVenduti(array $params): void
    {
        $ristoranteId = $this->richiediRistorante();
        $vendite      = FOrdine::conteggioVenditeByRistorante($ristoranteId);

        // TODO(presentation): passare $vendite alla view delle statistiche
        // (e' gia' ordinato dal piu' venduto).
        if ($vendite === []) {
            echo 'Nessuna vendita registrata.';
            return;
        }
        $top = $vendite[0];
        echo 'Piatti venduti: ' . count($vendite)
            . '. Piu\' venduto: ' . $top['nome'] . ' (' . $top['quantita_totale'] . ').';
    }

    // ==================================================================
    //  Guardia e helper (parte 1). Le altre sezioni e gli helper di
    //  competenza specifici sono aggiunti nei blocchi successivi.
    // ==================================================================

    /**
     * Pretende un ristorante loggato. Restituisce l'id del ristorante, preso
     * DALLA SESSIONE. Se non c'e', reindirizza al login.
     */
    private function richiediRistorante(): int
    {
        if (!Session::isLoggato() || Session::getRuolo() !== self::RUOLO) {
            $this->redirect('index.php?controller=Login&action=mostraLogin');
        }
        return (int) Session::getIdUtente();
    }

    /** Normalizza una stringa opzionale: '' o null diventano null. */
    private function stringaOpzionale(mixed $v): ?string
    {
        if ($v === null) {
            return null;
        }
        $s = trim((string) $v);
        return $s === '' ? null : $s;
    }

    // --- Verifiche di competenza ---------------------------------------
    // Ognuna restituisce l'entita' SOLO se appartiene a questo ristorante;
    // altrimenti null. Invece di fidarsi dell'id ricevuto dalla richiesta, lo
    // si cerca tra le entita' che la foundation riconosce come del ristorante.

    private function piattoDiCompetenza(int $ristoranteId, int $piattoId): ?Piatto
    {
        if ($piattoId <= 0) {
            return null;
        }
        foreach (FPiatto::loadByRistorante($ristoranteId) as $p) {
            if ($p->getId() === $piattoId) {
                return $p;
            }
        }
        return null;
    }

    private function categoriaDiCompetenza(int $ristoranteId, int $categoriaId): ?Categoria
    {
        if ($categoriaId <= 0) {
            return null;
        }
        foreach (FCategoria::loadByRistorante($ristoranteId) as $c) {
            if ($c->getId() === $categoriaId) {
                return $c;
            }
        }
        return null;
    }

    private function allergeneDiCompetenza(int $ristoranteId, int $allergeneId): ?Allergene
    {
        if ($allergeneId <= 0) {
            return null;
        }
        foreach (FAllergene::loadByRistorante($ristoranteId) as $a) {
            if ($a->getId() === $allergeneId) {
                return $a;
            }
        }
        return null;
    }

    private function cucinaDiCompetenza(int $ristoranteId, int $cucinaId): ?Cucina
    {
        if ($cucinaId <= 0) {
            return null;
        }
        foreach (FCucina::loadByRistorante($ristoranteId) as $c) {
            if ($c->getId() === $cucinaId) {
                return $c;
            }
        }
        return null;
    }

    private function tavoloDiCompetenza(int $ristoranteId, int $tavoloId): ?Tavolo
    {
        if ($tavoloId <= 0) {
            return null;
        }
        foreach (FTavolo::loadByRistorante($ristoranteId) as $t) {
            if ($t->getId() === $tavoloId) {
                return $t;
            }
        }
        return null;
    }

    // --- Collegamento dei riferimenti opzionali del piatto -------------
    // Categoria e cucina di un piatto si impostano solo se l'id fornito
    // appartiene a questo ristorante; un id estraneo viene ignorato.

    /** @param array<string,mixed> $params */
    private function collegaCategoriaSeValida(Piatto $piatto, int $ristoranteId, array $params): void
    {
        if (!array_key_exists('categoriaId', $params)) {
            return;
        }
        $id = (int) $params['categoriaId'];
        if ($id <= 0) {
            $piatto->setCategoria(null);   // esplicito "nessuna categoria"
            return;
        }
        $categoria = $this->categoriaDiCompetenza($ristoranteId, $id);
        if ($categoria !== null) {
            $piatto->setCategoria($categoria);
        }
    }

    /** @param array<string,mixed> $params */
    private function collegaCucinaSeValida(Piatto $piatto, int $ristoranteId, array $params): void
    {
        if (!array_key_exists('cucinaId', $params)) {
            return;
        }
        $id = (int) $params['cucinaId'];
        if ($id <= 0) {
            $piatto->assegnaCucina(null);
            return;
        }
        $cucina = $this->cucinaDiCompetenza($ristoranteId, $id);
        if ($cucina !== null) {
            $piatto->assegnaCucina($cucina);
        }
    }

    /**
     * Imposta gli allergeni di un piatto dalla lista di id ricevuta (parametro
     * 'allergeni' = JSON di id, es. "[1,3,5]"). Sostituisce gli allergeni
     * correnti con quelli indicati, validando che siano del ristorante.
     */
    private function collegaAllergeni(Piatto $piatto, int $ristoranteId, array $params): void
    {
        if (!array_key_exists('allergeni', $params)) {
            return;
        }
        $ids = json_decode((string) $params['allergeni'], true);
        if (!is_array($ids)) {
            $ids = [];
        }

        // svuota gli allergeni attuali
        foreach ($piatto->getAllergeni() as $a) {
            $piatto->rimuoviAllergene($a);
        }
        // aggiunge quelli validi (del ristorante)
        foreach ($ids as $idAll) {
            $allergene = $this->allergeneDiCompetenza($ristoranteId, (int) $idAll);
            if ($allergene !== null) {
                $piatto->associaAllergene($allergene);
            }
        }
    }

    // ------------------------------------------------------------------
    //  Helper per la presentation (nome ristorante e risposte JSON).
    // ------------------------------------------------------------------

    /** Nome del ristorante, per le intestazioni delle pagine. */
    private function nomeRistorante(int $ristoranteId): string
    {
        $r = FRistorante::load($ristoranteId);
        return $r !== null ? $r->getNome() : 'Ristorante';
    }

    /** Trasforma i tavoli del ristorante in array semplici per il JSON. */
    private function tavoliInArray(int $ristoranteId): array
    {
        $out = [];
        foreach (FTavolo::loadByRistorante($ristoranteId) as $t) {
            $out[] = [
                'id'             => (int) $t->getId(),
                'numero'         => $t->getNumero(),
                'stato'          => $t->getStato()->value,
                'coperti'        => $t->getCoperti(),
                'haPassword'     => $t->haPassword(),
                'contoRichiesto' => $t->isContoRichiesto(),
            ];
        }
        // Ordinamento "naturale": 1, 2, 11 (non 1, 11, 2). Gestisce anche
        // numeri con lettere (es. A1, A2). I numeri sono testo, quindi senza
        // questo si ordinerebbero alfabeticamente.
        usort($out, function ($a, $b) {
            return strnatcasecmp($a['numero'], $b['numero']);
        });
        return $out;
    }

    /** Lista cucine del ristorante come array semplici per il JSON. */
    private function cucineInArray(int $ristoranteId): array
    {
        $out = [];
        foreach (FCucina::loadByRistorante($ristoranteId) as $c) {
            $out[] = [
                'id'       => (int) $c->getId(),
                'nome'     => $c->getNome(),
                'username' => $c->getUsername(),
            ];
        }
        return $out;
    }

    /** Categorie del ristorante come array, ordinate per posizione. */
    private function categorieInArray(int $ristoranteId): array
    {
        $out = [];
        foreach (FCategoria::loadByRistorante($ristoranteId) as $c) {
            $out[] = [
                'id'        => (int) $c->getId(),
                'nome'      => $c->getNome(),
                'posizione' => $c->getPosizione(),
            ];
        }
        // ordina per posizione crescente, poi per nome
        usort($out, function ($a, $b) {
            return [$a['posizione'], $a['nome']] <=> [$b['posizione'], $b['nome']];
        });
        return $out;
    }

    /** Allergeni del ristorante come array. */
    private function allergeniInArray(int $ristoranteId): array
    {
        $out = [];
        foreach (FAllergene::loadByRistorante($ristoranteId) as $a) {
            $out[] = [
                'id'   => (int) $a->getId(),
                'nome' => $a->getNome(),
            ];
        }
        return $out;
    }

    /** Piatti del ristorante come array (con categoria, cucina, allergeni). */
    private function piattiInArray(int $ristoranteId): array
    {
        $out = [];
        foreach (FPiatto::loadByRistorante($ristoranteId) as $p) {
            $idsAll = [];
            foreach ($p->getAllergeni() as $a) {
                $idsAll[] = (int) $a->getId();
            }
            $cat = $p->getCategoria();
            $cuc = $p->getCucina();
            $out[] = [
                'id'          => (int) $p->getId(),
                'nome'        => $p->getNome(),
                'prezzo'      => $p->getPrezzo(),
                'prezzoTxt'   => number_format($p->getPrezzo(), 2, ',', '.'),
                'descrizione' => $p->getDescrizione(),
                'etichetta'   => $p->getEtichetta(),
                'immagine'    => $p->getImmagine(),
                'disponibile' => $p->isDisponibile(),
                'categoriaId' => $cat !== null ? (int) $cat->getId() : null,
                'categoria'   => $cat !== null ? $cat->getNome() : null,
                'catPos'      => $cat !== null ? $cat->getPosizione() : 9999,
                'cucinaId'    => $cuc !== null ? (int) $cuc->getId() : null,
                'cucina'      => $cuc !== null ? $cuc->getNome() : null,
                'allergeniIds'=> $idsAll,
            ];
        }
        // ordina per posizione della categoria, poi nome categoria, poi nome piatto
        usort($out, function ($a, $b) {
            return [$a['catPos'], (string) $a['categoria'], $a['nome']]
               <=> [$b['catPos'], (string) $b['categoria'], $b['nome']];
        });
        return $out;
    }

    /** Risposta JSON con categorie aggiornate. */
    private function rispondiCategorie(int $ristoranteId): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => true, 'categorie' => $this->categorieInArray($ristoranteId)]);
    }

    /** Risposta JSON con allergeni aggiornati. */
    private function rispondiAllergeni(int $ristoranteId): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => true, 'allergeni' => $this->allergeniInArray($ristoranteId)]);
    }

    /** Risposta JSON con piatti aggiornati. */
    private function rispondiPiatti(int $ristoranteId): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => true, 'piatti' => $this->piattiInArray($ristoranteId)]);
    }

    /** Risposta JSON di successo con la lista aggiornata delle cucine. */
    private function rispondiCucine(int $ristoranteId): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => true, 'cucine' => $this->cucineInArray($ristoranteId)]);
    }

    /** Risposta JSON di successo con la lista aggiornata dei tavoli. */
    private function rispondiTavoli(int $ristoranteId, array $extra = []): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(array_merge(['ok' => true, 'tavoli' => $this->tavoliInArray($ristoranteId)], $extra));
    }

    /** Risposta JSON di errore per le azioni chiamate via fetch. */
    private function rispondiErrore(string $messaggio): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'errore' => $messaggio]);
    }

    private function errore(string $messaggio): void
    {
        echo 'Errore: ' . htmlspecialchars($messaggio, ENT_QUOTES, 'UTF-8');
    }

    private function redirect(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }
}
