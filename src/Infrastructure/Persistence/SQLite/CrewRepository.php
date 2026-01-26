<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\SQLite;

use App\Application\Port\Repository\CrewRepositoryInterface;
use App\Domain\Entity\Crew;
use App\Domain\ValueObject\CrewKey;
use App\Domain\ValueObject\BoatKey;
use App\Domain\ValueObject\EventId;
use App\Domain\ValueObject\Rank;
use App\Domain\Enum\RankDimension;
use App\Domain\Enum\AvailabilityStatus;
use App\Domain\Enum\SkillLevel;
use PDO;

/**
 * SQLite Crew Repository
 *
 * Implements crew persistence using SQLite database.
 */
class CrewRepository implements CrewRepositoryInterface
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Connection::getInstance();
    }

    public function findByKey(CrewKey $key): ?Crew
    {
        $stmt = $this->pdo->prepare('
            SELECT * FROM crews WHERE key = :key LIMIT 1
        ');
        $stmt->execute(['key' => $key->toString()]);
        $row = $stmt->fetch();

        if ($row === false) {
            return null;
        }

        return $this->hydrate($row);
    }

    public function findByName(string $firstName, string $lastName): ?Crew
    {
        $stmt = $this->pdo->prepare('
            SELECT * FROM crews
            WHERE first_name = :first_name
            AND last_name = :last_name
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
        $stmt = $this->pdo->query('SELECT * FROM crews ORDER BY display_name');
        $rows = $stmt->fetchAll();

        return array_map(fn($row) => $this->hydrate($row), $rows);
    }

    public function findAvailableForEvent(EventId $eventId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT c.* FROM crews c
            INNER JOIN crew_availability ca ON c.id = ca.crew_id
            WHERE ca.event_id = :event_id AND ca.status IN (1, 2)
            ORDER BY c.display_name
        ');
        $stmt->execute(['event_id' => $eventId->toString()]);
        $rows = $stmt->fetchAll();

        return array_map(fn($row) => $this->hydrate($row), $rows);
    }

    public function findAssignedToEvent(EventId $eventId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT c.* FROM crews c
            INNER JOIN crew_availability ca ON c.id = ca.crew_id
            WHERE ca.event_id = :event_id AND ca.status = 2
            ORDER BY c.display_name
        ');
        $stmt->execute(['event_id' => $eventId->toString()]);
        $rows = $stmt->fetchAll();

        return array_map(fn($row) => $this->hydrate($row), $rows);
    }

    public function save(Crew $crew): void
    {
        if ($crew->getId() === null) {
            $this->insert($crew);
        } else {
            $this->update($crew);
        }
    }

    public function delete(CrewKey $key): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM crews WHERE key = :key');
        $stmt->execute(['key' => $key->toString()]);
    }

    public function exists(CrewKey $key): bool
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM crews WHERE key = :key');
        $stmt->execute(['key' => $key->toString()]);
        return (int)$stmt->fetchColumn() > 0;
    }

    public function updateAvailability(CrewKey $key, EventId $eventId, AvailabilityStatus $status): void
    {
        $crew = $this->findByKey($key);
        if ($crew === null) {
            throw new \RuntimeException("Crew not found: {$key->toString()}");
        }

        $stmt = $this->pdo->prepare('
            INSERT INTO crew_availability (crew_id, event_id, status)
            VALUES (:crew_id, :event_id, :status)
            ON CONFLICT(crew_id, event_id) DO UPDATE SET status = :status
        ');
        $stmt->execute([
            'crew_id' => $crew->getId(),
            'event_id' => $eventId->toString(),
            'status' => $status->value,
        ]);
    }

    public function updateHistory(CrewKey $key, EventId $eventId, string $boatKey): void
    {
        $crew = $this->findByKey($key);
        if ($crew === null) {
            throw new \RuntimeException("Crew not found: {$key->toString()}");
        }

        $stmt = $this->pdo->prepare('
            INSERT INTO crew_history (crew_id, event_id, boat_key)
            VALUES (:crew_id, :event_id, :boat_key)
            ON CONFLICT(crew_id, event_id) DO UPDATE SET boat_key = :boat_key
        ');
        $stmt->execute([
            'crew_id' => $crew->getId(),
            'event_id' => $eventId->toString(),
            'boat_key' => $boatKey,
        ]);
    }

    public function addToWhitelist(CrewKey $crewKey, BoatKey $boatKey): void
    {
        $crew = $this->findByKey($crewKey);
        if ($crew === null) {
            throw new \RuntimeException("Crew not found: {$crewKey->toString()}");
        }

        $stmt = $this->pdo->prepare('
            INSERT OR IGNORE INTO crew_whitelist (crew_id, boat_key)
            VALUES (:crew_id, :boat_key)
        ');
        $stmt->execute([
            'crew_id' => $crew->getId(),
            'boat_key' => $boatKey->toString(),
        ]);
    }

    public function removeFromWhitelist(CrewKey $crewKey, BoatKey $boatKey): void
    {
        $crew = $this->findByKey($crewKey);
        if ($crew === null) {
            throw new \RuntimeException("Crew not found: {$crewKey->toString()}");
        }

        $stmt = $this->pdo->prepare('
            DELETE FROM crew_whitelist
            WHERE crew_id = :crew_id AND boat_key = :boat_key
        ');
        $stmt->execute([
            'crew_id' => $crew->getId(),
            'boat_key' => $boatKey->toString(),
        ]);
    }

    public function count(): int
    {
        $stmt = $this->pdo->query('SELECT COUNT(*) FROM crews');
        return (int)$stmt->fetchColumn();
    }

    /**
     * Insert new crew
     */
    private function insert(Crew $crew): void
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO crews (
                key, display_name, first_name, last_name, partner_key,
                email, mobile, social_preference, membership_number,
                skill, experience,
                rank_commitment, rank_flexibility, rank_membership, rank_absence
            ) VALUES (
                :key, :display_name, :first_name, :last_name, :partner_key,
                :email, :mobile, :social_preference, :membership_number,
                :skill, :experience,
                :rank_commitment, :rank_flexibility, :rank_membership, :rank_absence
            )
        ');

        $rank = $crew->getRank();
        $stmt->execute([
            'key' => $crew->getKey()->toString(),
            'display_name' => $crew->getDisplayName(),
            'first_name' => $crew->getFirstName(),
            'last_name' => $crew->getLastName(),
            'partner_key' => $crew->getPartnerKey()?->toString(),
            'email' => $crew->getEmail(),
            'mobile' => $crew->getMobile(),
            'social_preference' => $crew->hasSocialPreference() ? 'Yes' : 'No',
            'membership_number' => $crew->getMembershipNumber(),
            'skill' => $crew->getSkill()->value,
            'experience' => $crew->getExperience(),
            'rank_commitment' => $rank->getDimension(RankDimension::CREW_COMMITMENT),
            'rank_flexibility' => $rank->getDimension(RankDimension::CREW_FLEXIBILITY),
            'rank_membership' => $rank->getDimension(RankDimension::CREW_MEMBERSHIP),
            'rank_absence' => $rank->getDimension(RankDimension::CREW_ABSENCE),
        ]);

        $crew->setId((int)$this->pdo->lastInsertId());

        // Insert availability, history, and whitelist
        $this->saveAvailabilityHistoryAndWhitelist($crew);
    }

    /**
     * Update existing crew
     */
    private function update(Crew $crew): void
    {
        $stmt = $this->pdo->prepare('
            UPDATE crews SET
                display_name = :display_name,
                first_name = :first_name,
                last_name = :last_name,
                partner_key = :partner_key,
                email = :email,
                mobile = :mobile,
                social_preference = :social_preference,
                membership_number = :membership_number,
                skill = :skill,
                experience = :experience,
                rank_commitment = :rank_commitment,
                rank_flexibility = :rank_flexibility,
                rank_membership = :rank_membership,
                rank_absence = :rank_absence
            WHERE id = :id
        ');

        $rank = $crew->getRank();
        $stmt->execute([
            'id' => $crew->getId(),
            'display_name' => $crew->getDisplayName(),
            'first_name' => $crew->getFirstName(),
            'last_name' => $crew->getLastName(),
            'partner_key' => $crew->getPartnerKey()?->toString(),
            'email' => $crew->getEmail(),
            'mobile' => $crew->getMobile(),
            'social_preference' => $crew->hasSocialPreference() ? 'Yes' : 'No',
            'membership_number' => $crew->getMembershipNumber(),
            'skill' => $crew->getSkill()->value,
            'experience' => $crew->getExperience(),
            'rank_commitment' => $rank->getDimension(RankDimension::CREW_COMMITMENT),
            'rank_flexibility' => $rank->getDimension(RankDimension::CREW_FLEXIBILITY),
            'rank_membership' => $rank->getDimension(RankDimension::CREW_MEMBERSHIP),
            'rank_absence' => $rank->getDimension(RankDimension::CREW_ABSENCE),
        ]);

        // Update availability, history, and whitelist
        $this->saveAvailabilityHistoryAndWhitelist($crew);
    }

    /**
     * Save crew availability, history, and whitelist
     */
    private function saveAvailabilityHistoryAndWhitelist(Crew $crew): void
    {
        // Save availability
        foreach ($crew->getAllAvailability() as $eventIdString => $status) {
            $this->updateAvailability($crew->getKey(), EventId::fromString($eventIdString), $status);
        }

        // Save history
        foreach ($crew->getAllHistory() as $eventIdString => $boatKey) {
            $this->updateHistory($crew->getKey(), EventId::fromString($eventIdString), $boatKey);
        }

        // Save whitelist (delete all and re-insert)
        $stmt = $this->pdo->prepare('DELETE FROM crew_whitelist WHERE crew_id = :crew_id');
        $stmt->execute(['crew_id' => $crew->getId()]);

        foreach ($crew->getWhitelist() as $boatKeyString) {
            $this->addToWhitelist($crew->getKey(), BoatKey::fromString($boatKeyString));
        }
    }

    /**
     * Hydrate crew entity from database row
     */
    private function hydrate(array $row): Crew
    {
        $crew = new Crew(
            key: CrewKey::fromString($row['key']),
            displayName: $row['display_name'],
            firstName: $row['first_name'],
            lastName: $row['last_name'],
            partnerKey: $row['partner_key'] ? CrewKey::fromString($row['partner_key']) : null,
            email: $row['email'],
            mobile: $row['mobile'] ?? '',
            socialPreference: $row['social_preference'] === 'Yes',
            membershipNumber: $row['membership_number'] ?? '',
            skill: SkillLevel::fromInt((int)$row['skill']),
            experience: $row['experience'] ?? '',
        );

        $crew->setId((int)$row['id']);

        // Set rank
        $rank = Rank::forCrew(
            commitment: (int)$row['rank_commitment'],
            flexibility: (int)$row['rank_flexibility'],
            membership: (int)$row['rank_membership'],
            absence: (int)$row['rank_absence']
        );
        $crew->setRank($rank);

        // Load availability
        $this->loadAvailability($crew);

        // Load history
        $this->loadHistory($crew);

        // Load whitelist
        $this->loadWhitelist($crew);

        return $crew;
    }

    /**
     * Load crew availability from database
     */
    private function loadAvailability(Crew $crew): void
    {
        $stmt = $this->pdo->prepare('
            SELECT event_id, status FROM crew_availability WHERE crew_id = :crew_id
        ');
        $stmt->execute(['crew_id' => $crew->getId()]);

        while ($row = $stmt->fetch()) {
            $crew->setAvailability(
                EventId::fromString($row['event_id']),
                AvailabilityStatus::from((int)$row['status'])
            );
        }
    }

    /**
     * Load crew history from database
     */
    private function loadHistory(Crew $crew): void
    {
        $stmt = $this->pdo->prepare('
            SELECT event_id, boat_key FROM crew_history WHERE crew_id = :crew_id
        ');
        $stmt->execute(['crew_id' => $crew->getId()]);

        while ($row = $stmt->fetch()) {
            $crew->setHistory(
                EventId::fromString($row['event_id']),
                $row['boat_key']
            );
        }
    }

    /**
     * Load crew whitelist from database
     */
    private function loadWhitelist(Crew $crew): void
    {
        $stmt = $this->pdo->prepare('
            SELECT boat_key FROM crew_whitelist WHERE crew_id = :crew_id
        ');
        $stmt->execute(['crew_id' => $crew->getId()]);

        $whitelist = [];
        while ($row = $stmt->fetch()) {
            $whitelist[] = $row['boat_key'];
        }

        $crew->setWhitelist($whitelist);
    }
}
