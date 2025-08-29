<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use App\Repositories\FileEntryRepository;
use App\Http\Requests\StoreEntryRequest;
use App\Http\Requests\UpdateEntryRequest;
use Illuminate\Support\Carbon;

class ProductEntryController extends Controller
{
    public function index(Request $request): View
    {
        return view('entries.index');
    }

    public function list(Request $request, FileEntryRepository $repo): JsonResponse
    {
        $entries = $repo->all();
        foreach ($entries as &$entry) {
            $entry['total_value'] = (float) ($entry['quantity_in_stock'] ?? 0) * (float) ($entry['price_per_item'] ?? 0);
        }
        unset($entry);
        usort($entries, function ($a, $b) {
            return strcmp($b['submitted_at'] ?? '', $a['submitted_at'] ?? '');
        });
        $totalSum = array_reduce($entries, function ($carry, $item) {
            return $carry + ($item['total_value'] ?? 0);
        }, 0.0);
        return response()->json(['data' => $entries, 'meta' => ['total_sum' => $totalSum]]);
    }

    public function store(StoreEntryRequest $request, FileEntryRepository $repo): JsonResponse
    {
        $data = $request->validated();
        $entry = [
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'product_name' => $data['product_name'],
            'quantity_in_stock' => (int) $data['quantity_in_stock'],
            'price_per_item' => (float) $data['price_per_item'],
            'submitted_at' => Carbon::now('UTC')->toIso8601String(),
        ];
        $repo->append($entry);
        $entry['total_value'] = (float) $entry['quantity_in_stock'] * (float) $entry['price_per_item'];
        return response()->json($entry, 201);
    }

    public function update(UpdateEntryRequest $request, FileEntryRepository $repo, string $id): JsonResponse
    {
        $data = $request->validated();
        $patch = [
            'product_name' => $data['product_name'],
            'quantity_in_stock' => (int) $data['quantity_in_stock'],
            'price_per_item' => (float) $data['price_per_item'],
        ];
        $updated = $repo->update($id, $patch);
        $updated['total_value'] = (float) ($updated['quantity_in_stock'] ?? 0) * (float) ($updated['price_per_item'] ?? 0);
        return response()->json($updated);
    }
}


