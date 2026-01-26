<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\CSV;

use App\Infrastructure\Persistence\SQLite\Connection;
use App\Infrastructure\Persistence\SQLite\BoatRepository;
use App\Infrastructure\Persistence\SQLite\CrewRepository;
use App\Infrastructure\Persistence\SQLite\EventRepository;
use App\Infrastructure\Persistence\SQLite\SeasonRepository;
use App\Domain\Entity\Boat;
use App\Domain\Entity\Crew;
use App\Domain\ValueObject\BoatKey;
use App\Domain\ValueObject\CrewKey;
use App\Domain\ValueObject\EventId;
use App\Domain\ValueObject\Rank;
use App\Domain\Enum\SkillLevel;
use App\Domain\Enum\AvailabilityStatus;

/**
 * CSV Migration
 *
 * Migrates legacy CSV data to SQLite database.
 *
 * Usage:
 *   $migration = new CsvMigration();
 *   $migration->migrate();
 */
class CsvMigration
{
    private string $legacyPath;
    private BoatRepository $boatRepo;
    private CrewRepository $crewRepo;
    private EventRepository $eventRepo;
    private SeasonRepository $seasonRepo;

    private array $migrationLog = [];

    public function __construct(?string $legacyPath = null)
    {
        if ($legacyPath === null) {
            // Default to legacy directory
            $projectRoot = dirname(__DIR__, 4);
            $this->legacyPath = $projectRoot . '/legacy';
        } else {
            $this->legacyPath = $legacyPath;
        }

        $this->boatRepo = new BoatRepository();
        $this->crewRepo = new CrewRepository();
        $this->eventRepo = new EventRepository();
        $this->seasonRepo = new SeasonRepository();
    }

    /**
     * Run full migration
     *
     * @param bool $backup Create CSV backups before migration
     * @return array Migration results
     */
    public function migrate(bool $backup = true): array
    {
        $this->log('=== JAWS CSV to SQLite Migration ===');
        $this->log('Legacy path: ' . $this->legacyPath);
        $this->log('');

        try {
            if ($backup) {
                $this->log('Creating CSV backups...');
                $this->backupCsvFiles();
                $this->log('Backups created successfully.');
                $this->log('');
            }

            // Load season configuration first (for event IDs)
            $this->log('Loading season configuration...');
            $config = $this->loadSeasonConfig();
            $this->log('Loaded season config for year: ' . $config['year']);
            $this->log('Event IDs: ' . implode(', ', $config['event_ids']));
            $this->log('');

            // Create events
            $this->log('Creating events...');
            $eventCount = $this->createEvents($config['event_ids'], $config);
            $this->log("Created {$eventCount} events.");
            $this->log('');

            // Migrate boats
            $this->log('Migrating boats from fleet_data.csv...');
            $boatCount = $this->migrateBoats($config['event_ids']);
            $this->log("Migrated {$boatCount} boats.");
            $this->log('');

            // Migrate crews
            $this->log('Migrating crews from squad_data.csv...');
            $crewCount = $this->migrateCrews($config['event_ids']);
            $this->log("Migrated {$crewCount} crews.");
            $this->log('');

            $this->log('=== Migration Complete ===');
            $this->log('Summary:');
            $this->log("  - Events: {$eventCount}");
            $this->log("  - Boats: {$boatCount}");
            $this->log("  - Crews: {$crewCount}");

            return [
                'success' => true,
                'events' => $eventCount,
                'boats' => $boatCount,
                'crews' => $crewCount,
                'log' => $this->migrationLog,
            ];

        } catch (\Exception $e) {
            $this->log('ERROR: ' . $e->getMessage());
            $this->log('File: ' . $e->getFile());
            $this->log('Line: ' . $e->getLine());

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'log' => $this->migrationLog,
            ];
        }
    }

    /**
     * Backup CSV files
     */
    private function backupCsvFiles(): void
    {
        $timestamp = date('YmdHis');

        $fleetCsv = $this->legacyPath . '/Libraries/Fleet/data/fleet_data.csv';
        $squadCsv = $this->legacyPath . '/Libraries/Squad/data/squad_data.csv';

        if (file_exists($fleetCsv)) {
            copy($fleetCsv, $fleetCsv . '.backup.' . $timestamp);
        }

        if (file_exists($squadCsv)) {
            copy($squadCsv, $squadCsv . '.backup.' . $timestamp);
        }
    }

    /**
     * Load season configuration from config.json
     */
    private function loadSeasonConfig(): array
    {
        $configFile = $this->legacyPath . '/Libraries/Season/data/config.json';

        if (!file_exists($configFile)) {
            throw new \RuntimeException("Config file not found: {$configFile}");
        }

        $json = file_get_contents($configFile);
        $data = json_decode($json, true);

        return $data['config'] ?? [];
    }

    /**
     * Create events in database
     */
    private function createEvents(array $eventIds, array $config): int
    {
        $count = 0;
        $year = (int)$config['year'];
        $startTime = $config['start_time'] ?? '12:45:00';
        $finishTime = $config['finish_time'] ?? '17:00:00';

        foreach ($eventIds as $eventIdString) {
            // Parse event ID format: "Fri May 29"
            $date = \DateTimeImmutable::createFromFormat('D M d', $eventIdString);
            if ($date === false) {
                $this->log("WARNING: Could not parse event ID: {$eventIdString}");
                continue;
            }

            // Set year
            $date = $date->setDate($year, (int)$date->format('m'), (int)$date->format('d'));

            $eventId = EventId::fromString($eventIdString);

            if (!$this->eventRepo->exists($eventId)) {
                $this->eventRepo->create($eventId, $date, $startTime, $finishTime);
                $count++;
            }
        }

        return $count;
    }

    /**
     * Migrate boats from fleet_data.csv
     */
    private function migrateBoats(array $eventIds): int
    {
        $csvFile = $this->legacyPath . '/Libraries/Fleet/data/fleet_data.csv';

        if (!file_exists($csvFile)) {
            throw new \RuntimeException("Fleet CSV not found: {$csvFile}");
        }

        $handle = fopen($csvFile, 'r');
        $header = fgetcsv($handle, 0, ',', '"', '\\');

        $count = 0;
        while (($row = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
            $boat = $this->parseBoatRow($header, $row, $eventIds);
            $this->boatRepo->save($boat);
            $count++;
        }

        fclose($handle);
        return $count;
    }

    /**
     * Parse boat CSV row
     */
    private function parseBoatRow(array $header, array $row, array $eventIds): Boat
    {
        $data = array_combine($header, $row);

        $boat = new Boat(
            key: BoatKey::fromString($data['key']),
            displayName: $data['display_name'],
            ownerFirstName: $data['owner_first_name'],
            ownerLastName: $data['owner_last_name'],
            ownerEmail: $data['owner_email'] ?? '',
            ownerMobile: $data['owner_mobile'] ?? '',
            minBerths: (int)($data['min_berths'] ?? 1),
            maxBerths: (int)($data['max_berths'] ?? 1),
            assistanceRequired: ($data['assistance_required'] ?? 'No') === 'Yes',
            socialPreference: ($data['social_preference'] ?? 'No') === 'Yes'
        );

        // Parse rank (semicolon-delimited: flexibility;absence)
        if (!empty($data['rank'])) {
            $rankParts = explode(';', $data['rank']);
            $rank = Rank::forBoat(
                flexibility: (int)($rankParts[0] ?? 1),
                absence: (int)($rankParts[1] ?? 0)
            );
            $boat->setRank($rank);
        }

        // Parse berths (semicolon-delimited, mapped to event IDs)
        if (!empty($data['berths'])) {
            $berthsParts = explode(';', $data['berths']);
            foreach ($eventIds as $i => $eventIdString) {
                $berths = (int)($berthsParts[$i] ?? 0);
                $boat->setBerths(EventId::fromString($eventIdString), $berths);
            }
        }

        // Parse history (semicolon-delimited, mapped to event IDs)
        if (!empty($data['history'])) {
            $historyParts = explode(';', $data['history']);
            foreach ($eventIds as $i => $eventIdString) {
                $participated = $historyParts[$i] ?? '';
                $boat->setHistory(EventId::fromString($eventIdString), $participated);
            }
        }

        return $boat;
    }

    /**
     * Migrate crews from squad_data.csv
     */
    private function migrateCrews(array $eventIds): int
    {
        $csvFile = $this->legacyPath . '/Libraries/Squad/data/squad_data.csv';

        if (!file_exists($csvFile)) {
            throw new \RuntimeException("Squad CSV not found: {$csvFile}");
        }

        $handle = fopen($csvFile, 'r');
        $header = fgetcsv($handle, 0, ',', '"', '\\');

        $count = 0;
        while (($row = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
            $crew = $this->parseCrewRow($header, $row, $eventIds);
            $this->crewRepo->save($crew);
            $count++;
        }

        fclose($handle);
        return $count;
    }

    /**
     * Parse crew CSV row
     */
    private function parseCrewRow(array $header, array $row, array $eventIds): Crew
    {
        $data = array_combine($header, $row);

        $crew = new Crew(
            key: CrewKey::fromString($data['key']),
            displayName: $data['display_name'],
            firstName: $data['first_name'],
            lastName: $data['last_name'],
            partnerKey: !empty($data['partner_key']) ? CrewKey::fromString($data['partner_key']) : null,
            email: $data['email'] ?? '',
            mobile: $data['mobile'] ?? '',
            socialPreference: ($data['social_preference'] ?? 'No') === 'Yes',
            membershipNumber: $data['membership_number'] ?? '',
            skill: SkillLevel::fromInt((int)($data['skill'] ?? 0)),
            experience: $data['experience'] ?? ''
        );

        // Parse rank (semicolon-delimited: commitment;flexibility;membership;absence)
        if (!empty($data['rank'])) {
            $rankParts = explode(';', $data['rank']);
            $rank = Rank::forCrew(
                commitment: (int)($rankParts[0] ?? 0),
                flexibility: (int)($rankParts[1] ?? 1),
                membership: (int)($rankParts[2] ?? 0),
                absence: (int)($rankParts[3] ?? 0)
            );
            $crew->setRank($rank);
        }

        // Parse availability (semicolon-delimited, mapped to event IDs)
        if (!empty($data['available'])) {
            $availParts = explode(';', $data['available']);
            foreach ($eventIds as $i => $eventIdString) {
                $statusValue = (int)($availParts[$i] ?? 0);
                $status = AvailabilityStatus::from($statusValue);
                $crew->setAvailability(EventId::fromString($eventIdString), $status);
            }
        }

        // Parse history (semicolon-delimited boat keys, mapped to event IDs)
        if (!empty($data['history'])) {
            $historyParts = explode(';', $data['history']);
            foreach ($eventIds as $i => $eventIdString) {
                $boatKey = $historyParts[$i] ?? '';
                $crew->setHistory(EventId::fromString($eventIdString), $boatKey);
            }
        }

        // Parse whitelist (semicolon-delimited boat keys)
        if (!empty($data['whitelist'])) {
            $whitelistParts = explode(';', $data['whitelist']);
            $crew->setWhitelist(array_filter($whitelistParts)); // Remove empty strings
        }

        return $crew;
    }

    /**
     * Log a message
     */
    private function log(string $message): void
    {
        $this->migrationLog[] = $message;
        echo $message . "\n";
    }

    /**
     * Get migration log
     */
    public function getLog(): array
    {
        return $this->migrationLog;
    }
}
