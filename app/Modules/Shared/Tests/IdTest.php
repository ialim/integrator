<?php

use App\Modules\Shared\Support\HasUuidV7;
use App\Modules\Shared\Support\Id;
use Illuminate\Database\Eloquent\Model;

it('generates UUIDv7 ids', function () {
    $id = Id::generate();

    expect($id)->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/')
        ->and(Id::isValid($id))->toBeTrue();
});

it('generates ids that sort in creation order', function () {
    $ids = [];

    for ($i = 0; $i < 50; $i++) {
        $ids[] = Id::generate();
        usleep(1000);
    }

    $sorted = $ids;
    sort($sorted);

    expect($sorted)->toBe($ids)->and(array_unique($ids))->toHaveCount(50);
});

it('accepts an uppercase UUIDv7', function () {
    expect(Id::isValid(strtoupper(Id::generate())))->toBeTrue();
});

it('rejects other UUID versions', function () {
    expect(Id::isValid('8f14e45f-ceea-467f-a8f5-44f1f2b3c4d5'))->toBeFalse()
        ->and(Id::isValid('not-an-id'))->toBeFalse();
});

it('gives a model using HasUuidV7 a UUIDv7 primary key', function () {
    $model = new class extends Model
    {
        use HasUuidV7;
    };

    expect(Id::isValid($model->newUniqueId()))->toBeTrue()
        ->and($model->getIncrementing())->toBeFalse()
        ->and($model->getKeyType())->toBe('string');
});
