<?php

use Illuminate\Support\Facades\File;

beforeEach(function () {
    // Ensure a fresh storage file for each test
    $path = storage_path('app/entries.json');
    if (file_exists($path)) {
        unlink($path);
    }
});

it('creates and lists entries with totals and sum', function () {
    $resp = $this->postJson('/api/entries', [
        'product_name' => 'A',
        'quantity_in_stock' => 2,
        'price_per_item' => 5.5,
    ]);
    $resp->assertCreated();
    $entry = $resp->json();
    expect((float)$entry['total_value'])->toBe(11.0);

    $resp2 = $this->getJson('/api/entries');
    $resp2->assertOk();
    $data = $resp2->json();
    expect((float)$data['meta']['total_sum'])->toBe(11.0)
        ->and(count($data['data']))->toBe(1);
});

it('updates an entry and recomputes totals', function () {
    $created = $this->postJson('/api/entries', [
        'product_name' => 'B',
        'quantity_in_stock' => 1,
        'price_per_item' => 10,
    ])->json();

    $resp = $this->putJson('/api/entries/' . $created['id'], [
        'product_name' => 'B2',
        'quantity_in_stock' => 3,
        'price_per_item' => 4,
    ]);
    $resp->assertOk();
    $upd = $resp->json();
    expect($upd['product_name'])->toBe('B2')
        ->and((float)$upd['total_value'])->toBe(12.0);
});

it('validates inputs', function () {
    $resp = $this->postJson('/api/entries', [
        'product_name' => '',
        'quantity_in_stock' => -1,
        'price_per_item' => -3,
    ]);
    $resp->assertStatus(422);
});


