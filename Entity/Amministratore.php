<?php
declare(strict_types=1);

namespace Entity;

/**
 * Amministratore della piattaforma. E' un Utente.
 *
 * Sta in cima alla gerarchia: genera, rimuove e visualizza i ristoranti.
 * Queste operazioni creano/eliminano record e quindi, nell'architettura
 * a strati, sono orchestrate dal controllo appoggiandosi alla foundation:
 * l'entity Amministratore rappresenta qui solo l'identita' e le credenziali.
 */
class Amministratore extends Utente
{
    public function __construct(
        string $username,
        string $passwordHash,
        ?int $id = null
    ) {
        parent::__construct($username, $passwordHash, $id);
    }
}
