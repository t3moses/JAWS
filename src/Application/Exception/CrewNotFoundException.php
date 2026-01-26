<?php

declare(strict_types=1);

namespace App\Application\Exception;

use App\Domain\ValueObject\CrewKey;

/**
 * Crew Not Found Exception
 *
 * Thrown when a crew member cannot be found in the repository.
 */
class CrewNotFoundException extends \RuntimeException
{
    public function __construct(CrewKey $key)
    {
        parent::__construct("Crew member not found: {$key->toString()}");
    }
}
