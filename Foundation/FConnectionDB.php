<?php
declare(strict_types=1);

namespace Foundation;

use PDO;
use PDOException;

/**
 * Classe Foundation responsabile della connessione al database.
 *
 * Corrisponde alla FConnectionDB / Fdb delle slide: sa connettersi al
 * DBMS e restituisce l'oggetto connessione. Tutte le altre classi
 * Foundation (FRistorante, FPiatto, ...) le chiedono la connessione.
 *
 * Realizzata con il pattern SINGLETON (una delle due opzioni indicate
 * dal professore, l'altra essendo l'approccio tutto statico): la
 * connessione PDO viene aperta una sola volta e riusata da tutti.
 *
 * Valori di default pensati per XAMPP (utente "root", password vuota).
 */
class FConnectionDB
{
    // Parametri di connessione. Default per XAMPP.
    private static string $host    = 'localhost';
    private static string $dbName  = 'menu_digitale';
    private static string $user    = 'root';
    private static string $pass    = '';
    private static string $charset = 'utf8mb4';

    // Unica istanza della connessione.
    private static ?PDO $istanza = null;

    /** Costruttore privato: la connessione si ottiene solo da getConnection(). */
    private function __construct()
    {
    }

    /**
     * Permette di cambiare i parametri prima del primo getConnection()
     * (utile per l'ambiente online). Senza effetto su una connessione gia' aperta.
     */
    public static function configure(
        string $host,
        string $dbName,
        string $user,
        string $pass,
        string $charset = 'utf8mb4'
    ): void {
        self::$host    = $host;
        self::$dbName  = $dbName;
        self::$user    = $user;
        self::$pass    = $pass;
        self::$charset = $charset;
    }

    /**
     * Restituisce la connessione PDO, creandola al primo accesso.
     * Le chiamate successive ricevono sempre la stessa istanza.
     */
    public static function getConnection(): PDO
    {
        if (self::$istanza === null) {
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=%s',
                self::$host,
                self::$dbName,
                self::$charset
            );

            //⚠️DA DECIDERE SE TENERE⚠️ 
            $opzioni = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            try {
                self::$istanza = new PDO($dsn, self::$user, self::$pass, $opzioni);
            } catch (PDOException $e) {
                throw new PDOException(
                    'Connessione al database fallita: ' . $e->getMessage(),
                    (int) $e->getCode()
                );
            }
        }

        return self::$istanza;
    }

    /** Azzera l'istanza (utile nei test per ripartire puliti). */
    public static function reset(): void
    {
        self::$istanza = null;
    }
}
