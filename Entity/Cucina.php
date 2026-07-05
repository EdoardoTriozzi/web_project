<?php
declare(strict_types=1);

namespace Entity;

/**
 * Cucina di un ristorante. E' un Utente (ha credenziali proprie).
 *
 * Nel modello la cucina prepara un insieme di piatti (associazione
 * "prepara") e da questo discende che riceve solo le righe d'ordine
 * dei piatti a lei assegnati. Qui l'entity tiene il nome e l'identita';
 * il recupero effettivo degli ordini ricevuti e' un'operazione che
 * coinvolge la persistenza, quindi sara' orchestrata dal controllo
 * appoggiandosi alla foundation, non risolta dentro questa classe.
 */
class Cucina extends Utente
{
    private string $nome;

    public function __construct(
        string $nome,
        string $username,
        string $passwordHash,
        ?int $id = null
    ) {
        parent::__construct($username, $passwordHash, $id);
        $this->nome = $nome;
    }

    public function getNome(): string
    {
        return $this->nome;
    }

    public function setNome(string $nome): void
    {
        $this->nome = $nome;
    }
}
