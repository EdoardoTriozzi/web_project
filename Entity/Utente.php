<?php
declare(strict_types=1);

namespace Entity;

/**
 * Classe astratta da cui derivano i quattro attori del sistema:
 * Amministratore, Ristorante, Cucina, Tavolo.
 *
 * Fattorizza cio' che hanno in comune: identita' e credenziali.
 * Gli attributi sono protected (#) come nel class diagram, cosi'
 * sono accessibili alle sottoclassi ma non dall'esterno.
 *
 * NOTA sulla separazione a strati: qui c'e' solo la logica di dominio
 * della verifica password (confronto dell'hash). Il recupero dell'utente
 * dal database e il salvataggio dell'hash sono compito della foundation;
 * la gestione della sessione (chi e' loggato) e' compito del controllo.
 */
abstract class Utente
{
    protected ?int $id;
    protected string $username;
    protected string $passwordHash;

    public function __construct(string $username, string $passwordHash, ?int $id = null)
    {
        $this->username     = $username;
        $this->passwordHash = $passwordHash;
        $this->id           = $id;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $username): void
    {
        $this->username = $username;
    }

    public function getPasswordHash(): string
    {
        return $this->passwordHash;
    }

    /**
     * Imposta la password salvandone l'hash (mai in chiaro).
     * Usa l'algoritmo di default di PHP (attualmente bcrypt).
     */
    public function setPassword(string $passwordInChiaro): void
    {
        $this->passwordHash = password_hash($passwordInChiaro, PASSWORD_DEFAULT);
    }

    /**
     * Verifica una password in chiaro contro l'hash memorizzato.
     * Traduce il metodo login() del diagramma a livello di dominio:
     * dice solo se le credenziali combaciano, non apre la sessione.
     */
    public function verificaPassword(string $passwordInChiaro): bool
    {
        return password_verify($passwordInChiaro, $this->passwordHash);
    }
}
