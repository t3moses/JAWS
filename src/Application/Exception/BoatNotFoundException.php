<?php

declare(strict_types=1);

namespace App\Application\Exception;

use App\Domain\ValueObject\BoatKey;

/**
 * Boat Not Found Exception
 *
 * Thrown when a boat cannot be found in the repository.
 */
class BoatNotFoundException extends \RuntimeException
{
    public function __construct(BoatKey $key)
    {
        parent::__construct("Boat not found: {$key->toString()}");
    }
}
