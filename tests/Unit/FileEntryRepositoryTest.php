<?php

use App\Repositories\FileEntryRepository;

it('reads empty when file missing', function () {
    $tmp = sys_get_temp_dir() . '/entries-' . uniqid('', true) . '.json';
    @unlink($tmp);
    $repo = new FileEntryRepository($tmp);
    expect($repo->all())->toBeArray()->toBeEmpty();
});

it('appends and updates entries', function () {
    $tmp = sys_get_temp_dir() . '/entries-' . uniqid('', true) . '.json';
    @unlink($tmp);
    $repo = new FileEntryRepository($tmp);

    $entry = [
        'id' => 'id-1',
        'product_name' => 'Item',
        'quantity_in_stock' => 2,
        'price_per_item' => 3.5,
        'submitted_at' => '2025-01-01T00:00:00Z',
    ];
    $repo->append($entry);
    $all = $repo->all();
    expect(count($all))->toBe(1);

    $updated = $repo->update('id-1', [
        'product_name' => 'ItemX',
        'quantity_in_stock' => 4,
        'price_per_item' => 2.25,
    ]);

    expect($updated['product_name'])->toBe('ItemX')
        ->and($repo->all()[0]['quantity_in_stock'])->toBe(4)
        ->and($repo->all()[0]['price_per_item'])->toBe(2.25);
});


