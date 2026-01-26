<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\SQLite;

use App\Application\Port\Repository\BoatRepositoryInterface;
use App\Domain\Entity\Boat;
use App\Domain\ValueObject\BoatKey;
use App\Domain\ValueObject\EventId;
use App\Domain\ValueObject\Rank;
use App\Domain\Enum\RankDimension;
use PDO;

/**
 * SQLite Boat Repository
 *
 * Implements boat persistence using SQLite database.
 */
class BoatRepository implements BoatRepositoryInterface
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Connection::getInstance();
    }

    public function findByKey(BoatKey $key): ?Boat
    {
        $stmt = $this->pdo->prepare('
            SELECT * FROM boats WHERE key = :key LIMIT 1
        ');
        $stmt->execute(['key' => $key->toString()]);
        $row = $stmt->fetch();

        if ($row === false) {
            return null;
        }

        return $this->hydrate($row);
    }

    public function findByOwnerName(string $firstName, string $lastName): ?Boat
    {
        $stmt = $this->pdo->prepare('
            SELECT * FROM boats
            WHERE owner_first_name = :first_name
            AND owner_last_name = :last_name
            LIMIT 1
        ');
        $stmt->execute([
            'first_name' => $firstName,
            'last_name' => $lastName,
        ]);
        $row = $stmt->fetch();

        if ($row === false) {
            return null;
        }

        return $this->hydrate($row);
    }

    public function findAll(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM boats ORDER BY display_name');
        $rows = $stmt->fetchAll();

        return array_map(fn($row) => $this->hydrate($row), $rows);
    }

    public function findAvailableForEvent(EventId $eventId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT b.* FROM boats b
            INNER JOIN boat_availability ba ON b.id = ba.boat_id
            WHERE ba.event_id = :event_id AND ba.berths > 0
            ORDER BY b.display_name
        ');
        $stmt->execute(['event_id' => $eventId->toString()]);
        $rows = $stmt->fetchAll();

        return array_map(fn($row) => $this->hydrate($row), $rows);
    }

    public function save(Boat $boat): void
    {
        if ($boat->getId() === null) {
            $this->insert($boat);
        } else {
            $this->update($boat);
        }
    }

    public function delete(BoatKey $key): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM boats WHERE key = :key');
        $stmt->execute(['key' => $key->toString()]);
    }

    public function exists(BoatKey $key): bool
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM boats WHERE key = :key');
        $stmt->execute(['key' => $key->toString()]);
        return (int)$stmt->fetchColumn() > 0;
    }

    public function updateAvailability(BoatKey $key, EventId $eventId, int $berths): void
    {
        $boat = $this->findByKey($key);
        if ($boat === null) {
            throw new \RuntimeException("Boat not found: {$key->toString()}");
        }

        $stmt = $this->pdo->prepare('
            INSERT INTO boat_availability (boat_id, event_id, berths)
            VALUES (:boat_id, :event_id, :berths)
            ON CONFLICT(boat_id, event_id) DO UPDATE SET berths = :berths
        ');
        $stmt->execute([
            'boat_id' => $boat->getId(),
            'event_id' => $eventId->toString(),
            'berths' => $berths,
        ]);
    }

    public function updateHistory(BoatKey $key, EventId $eventId, string $participated): void
    {
        $boat = $this->findByKey($key);
        if ($boat === null) {
            throw new \RuntimeException("Boat not found: {$key->toString()}");
        }

        $stmt = $this->pdo->prepare('
            INSERT INTO boat_history (boat_id, event_id, participated)
            VALUES (:boat_id, :event_id, :participated)
            ON CONFLICT(boat_id, event_id) DO UPDATE SET participated = :participated
        ');
        $stmt->execute([
            'boat_id' => $boat->getId(),
            'event_id' => $eventId->toString(),
            'participated' => $participated,
        ]);
    }

    public function count(): int
    {
        $stmt = $this->pdo->query('SELECT COUNT(*) FROM boats');
        return (int)$stmt->fetchColumn();
    }

    /**
     * Insert new boat
     */
    private function insert(Boat $boat): void
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO boats (
                key, display_name, owner_first_name, owner_last_name,
                owner_email, owner_mobile, min_berths, max_berths,
                assistance_required, social_preference,
                rank_flexibility, rank_absence
            ) VALUES (
                :key, :display_name, :owner_first_name, :owner_last_name,
                :owner_email, :owner_mobile, :min_berths, :max_berths,
                :assistance_required, :social_preference,
                :rank_flexibility, :rank_absence
            )
        ');

        $rank = $boat->getRank();
        $stmt->execute([
            'key' => $boat->getKey()->toString(),
            'display_name' => $boat->getDisplayName(),
            'owner_first_name' => $boat->getOwnerFirstName(),
            'owner_last_name' => $boat->getOwnerLastName(),
            'owner_email' => $boat->getOwnerEmail(),
            'owner_mobile' => $boat->getOwnerMobile(),
            'min_berths' => $boat->getMinBerths(),
            'max_berths' => $boat->getMaxBerths(),
            'assistance_required' => $boat->requiresAssistance() ? 'Yes' : 'No',
            'social_preference' => $boat->hasSocialPreference() ? 'Yes' : 'No',
            'rank_flexibility' => $rank->getDimension(RankDimension::BOAT_FLEXIBILITY),
            'rank_absence' => $rank->getDimension(RankDimension::BOAT_ABSENCE),
        ]);

        $boat->setId((int)$this->pdo->lastInsertId());

        // Insert availability and history
        $this->saveAvailabilityAndHistory($boat);
    }

    /**
     * Update existing boat
     */
    private function update(Boat $boat): void
    {
        $stmt = $this->pdo->prepare('
            UPDATE boats SET
                display_name = :display_name,
                owner_first_name = :owner_first_name,
                owner_last_name = :owner_last_name,
                owner_email = :owner_email,
                owner_mobile = :owner_mobile,
                min_berths = :min_berths,
                max_berths = :max_berths,
                assistance_required = :assistance_required,
                social_preference = :social_preference,
                rank_flexibility = :rank_flexibility,
                rank_absence = :rank_absence
            WHERE id = :id
        ');

        $rank = $boat->getRank();
        $stmt->execute([
            'id' => $boat->getId(),
            'display_name' => $boat->getDisplayName(),
            'owner_first_name' => $boat->getOwnerFirstName(),
            'owner_last_name' => $boat->getOwnerLastName(),
            'owner_email' => $boat->getOwnerEmail(),
            'owner_mobile' => $boat->getOwnerMobile(),
            'min_berths' => $boat->getMinBerths(),
            'max_berths' => $boat->getMaxBerths(),
            'assistance_required' => $boat->requiresAssistance() ? 'Yes' : 'No',
            'social_preference' => $boat->hasSocialPreference() ? 'Yes' : 'No',
            'rank_flexibility' => $rank->getDimension(RankDimension::BOAT_FLEXIBILITY),
            'rank_absence' => $rank->getDimension(RankDimension::BOAT_ABSENCE),
        ]);

        // Update availability and history
        $this->saveAvailabilityAndHistory($boat);
    }

    /**
     * Save boat availability and history
     */
    private function saveAvailabilityAndHistory(Boat $boat): void
    {
        // Save availability (berths)
        foreach ($boat->getAllBerths() as $eventIdString => $berths) {
            $this->updateAvailability($boat->getKey(), EventId::fromString($eventIdString), $berths);
        }

        // Save history
        foreach ($boat->getAllHistory() as $eventIdString => $participated) {
            $this->updateHistory($boat->getKey(), EventId::fromString($eventIdString), $participated);
        }
    }

    /**
     * Hydrate boat entity from database row
     */
    private function hydrate(array $row): Boat
    {
        $boat = new Boat(
            key: BoatKey::fromString($row['key']),
            displayName: $row['display_name'],
            ownerFirstName: $row['owner_first_name'],
            ownerLastName: $row['owner_last_name'],
            ownerEmail: $row['owner_email'],
            ownerMobile: $row['owner_mobile'] ?? '',
            minBerths: (int)$row['min_berths'],
            maxBerths: (int)$row['max_berths'],
            assistanceRequired: $row['assistance_required'] === 'Yes',
            socialPreference: $row['social_preference'] === 'Yes',
        );

        $boat->setId((int)$row['id']);

        // Set rank
        $rank = Rank::forBoat(
            flexibility: (int)$row['rank_flexibility'],
            absence: (int)$row['rank_absence']
        );
        $boat->setRank($rank);

        // Load availability
        $this->loadAvailability($boat);

        // Load history
        $this->loadHistory($boat);

        return $boat;
    }

    /**
     * Load boat availability from database
     */
    private function loadAvailability(Boat $boat): void
    {
        $stmt = $this->pdo->prepare('
            SELECT event_id, berths FROM boat_availability WHERE boat_id = :boat_id
        ');
        $stmt->execute(['boat_id' => $boat->getId()]);

        while ($row = $stmt->fetch()) {
            $boat->setBerths(
                EventId::fromString($row['event_id']),
                (int)$row['berths']
            );
        }
    }

    /**
     * Load boat history from database
     */
    private function loadHistory(Boat $boat): void
    {
        $stmt = $this->pdo->prepare('
            SELECT event_id, participated FROM boat_history WHERE boat_id = :boat_id
        ');
        $stmt->execute(['boat_id' => $boat->getId()]);

        while ($row = $stmt->fetch()) {
            $boat->setHistory(
                EventId::fromString($row['event_id']),
                $row['participated']
            );
        }
    }
}
