<?php

declare(strict_types=1);

namespace App\Domain\Tournament\Exception;

use RuntimeException;

/**
 * Classe de base des erreurs du domaine tournoi.
 *
 * Le domaine est volontairement indépendant de Laravel : on n'étend que des
 * exceptions de la SPL.
 */
abstract class TournamentDomainException extends RuntimeException {}
