<?php
declare(strict_types=1);

/**
 * index.php — punto di avvio unico dell'applicazione (il "main").
 *
 * Tutte le richieste autodescrittive passano da qui (slide 13-22).
 * Responsabilita' di questo file: il minimo indispensabile.
 *   1. caricare l'autoloader (cosi' non servono include manuali delle classi)
 *   2. avviare la sessione PRIMA di qualsiasi output (vedi nota nel riepilogo)
 *   3. delegare tutto al FrontController
 */

require_once __DIR__ . '/config/autoload.php';

use Control\FrontController;
use Foundation\Session;

// La sessione va avviata prima di stampare alcunche'.
// NOTA: 'avvia' e' il nome ipotizzato nel riepilogo; se nella tua classe
// Session il metodo si chiama diversamente (o se usi direttamente
// session_start()), allinea questa riga.
Session::avvia();

$frontController = new FrontController();
$frontController->run();
