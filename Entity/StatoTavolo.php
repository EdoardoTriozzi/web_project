<?php
declare(strict_types=1);

namespace Entity;

/**
 * Stato di un tavolo.
 * Traduce l'enumeration <<StatoTavolo>> del class diagram.
 *
 * E' un "backed enum": ogni caso ha un valore stringa che coincide
 * con quello salvato nella colonna ENUM del database, cosi la
 * conversione tra oggetto e dato persistito (compito della foundation)
 * e' immediata con StatoTavolo::from('libero') e $stato->value.
 */
enum StatoTavolo: string
{
    case LIBERO   = 'libero';
    case OCCUPATO = 'occupato';
}
