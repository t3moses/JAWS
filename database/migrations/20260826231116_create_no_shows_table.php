<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Create No Shows Table Migration
 *
 * Records a no-show for a crew at an event, one row per (crew, event) pair.
 * Written when a boat owner flags a crew member for a no-show (see
 * FlagAssignedCrewUseCase); re-flagging the same crew for the same event is a
 * no-op thanks to the composite unique index.
 */
final class CreateNoShowsTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('no_shows', ['id' => 'id']);
        $table->addColumn('crew_id', 'integer', ['null' => false])
              ->addColumn('event_id', 'string', ['limit' => 255, 'null' => false])
              ->addForeignKey('crew_id', 'crews', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
              ->addForeignKey('event_id', 'events', 'event_id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
              ->addIndex(['crew_id', 'event_id'], ['unique' => true])
              ->create();
    }
}
