<?php

use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

it('backfills legacy records including soft deleted media without moving their files', function (): void {
    $migration = require __DIR__.'/../../database/migrations/2026_10_12_000000_add_uuid_to_media_files_table.php';
    $migration->down();

    foreach ([false, true] as $deleted) {
        DB::table('media_files')->insert([
            'disk' => 'public',
            'path' => $deleted ? 'old/deleted.txt' : 'old/active.txt',
            'original_name' => 'original.txt',
            'file_name' => $deleted ? 'deleted.txt' : 'active.txt',
            'mime_type' => 'text/plain',
            'deleted_at' => $deleted ? now() : null,
        ]);
    }

    $migration->up();
    $records = DB::table('media_files')->orderBy('id')->get();
    $uuids = $records->pluck('uuid')->all();

    expect($uuids)->toHaveCount(2)
        ->and(Str::isUuid($uuids[0], 7))->toBeTrue()
        ->and(Str::isUuid($uuids[1], 7))->toBeTrue()
        ->and($uuids[0] < $uuids[1])->toBeTrue()
        ->and($records->pluck('path')->all())->toBe(['old/active.txt', 'old/deleted.txt'])
        ->and($records->pluck('file_name')->all())->toBe(['active.txt', 'deleted.txt']);

    $migration->up();
    expect(DB::table('media_files')->orderBy('id')->pluck('uuid')->all())->toBe($uuids);

    expect(fn () => DB::table('media_files')->where('id', $records[1]->id)->update(['uuid' => $uuids[0]]))
        ->toThrow(QueryException::class);
    expect(fn () => DB::table('media_files')->where('id', $records[0]->id)->update(['uuid' => null]))
        ->toThrow(QueryException::class);
});

it('adds and rolls back uuids using the configured media table', function (): void {
    Schema::create('custom_media_files', function (Blueprint $table): void {
        $table->id();
        $table->string('path');
    });
    DB::table('custom_media_files')->insert(['path' => 'legacy.txt']);
    config()->set('filament-media.tables.media_files', 'custom_media_files');

    $migration = require __DIR__.'/../../database/migrations/2026_10_12_000000_add_uuid_to_media_files_table.php';
    $migration->up();

    expect(Str::isUuid(DB::table('custom_media_files')->value('uuid'), 7))->toBeTrue();

    $migration->down();
    expect(Schema::hasColumn('custom_media_files', 'uuid'))->toBeFalse()
        ->and(DB::table('custom_media_files')->value('path'))->toBe('legacy.txt');
});
