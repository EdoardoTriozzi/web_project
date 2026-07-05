<?php
declare(strict_types=1);

namespace Entity;

/**
 * Stato di un ordine lungo il suo ciclo di vita.
 * Traduce l'enumeration <<StatoOrdine>> del class diagram.
 *
 * Flusso previsto:
 *   BOZZA -> INVIATO -> IN_PREPARAZIONE -> CONSEGNATO -> CHIUSO
 *
 * BOZZA e' il carrello condiviso che i dispositivi del tavolo
 * riempiono; passa a INVIATO quando l'ordine viene mandato in cucina.
 *
 * I valori stringa coincidono con quelli della colonna ENUM nel database.
 */
enum StatoOrdine: string
{
    case BOZZA           = 'bozza';
    case INVIATO         = 'inviato';
    case IN_PREPARAZIONE = 'in_preparazione';
    case CONSEGNATO      = 'consegnato';
    case CHIUSO          = 'chiuso';
}
