<?php

use function Pest\Laravel\artisan;
use Spatie\Health\Commands\RunHealthChecksCommand;
use Spatie\Health\Enums\Status;
use Spatie\Health\Facades\Health;
use Spatie\Health\ResultStores\InMemoryHealthResultStore;
use Spatie\Health\ResultStores\ResultStore;
use Spatie\Health\ResultStores\StoredCheckResults\StoredCheckResults;
use Spatie\Health\Tests\TestClasses\FakeUsedDiskSpaceCheck;
use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze('2021-01-01 00:00:00');

    config()->set('health.result_stores', [
        InMemoryHealthResultStore::class,
    ]);

    $this->fakeDiskSpaceCheck = FakeUsedDiskSpaceCheck::new();

    Health::checks([
        $this->fakeDiskSpaceCheck,
    ]);
});

it('can keep results in memory', function () {
    artisan(RunHealthChecksCommand::class)->assertSuccessful();

    $report = app(ResultStore::class)->latestResults();

    expect($report)->toBeInstanceOf(StoredCheckResults::class);
    expect($report->storedCheckResults)->toHaveCount(1);
});

it('can store skipped results in memory', function () {
    $this
        ->fakeDiskSpaceCheck
        ->everyFiveMinutes();

    artisan(RunHealthChecksCommand::class)->assertSuccessful();

    testTime()->addMinutes(4);

    artisan(RunHealthChecksCommand::class)->assertSuccessful();

    $report = app(ResultStore::class)->latestResults();

    expect($report->containsCheckWithStatus(Status::skipped()))->toBeTrue();
});
