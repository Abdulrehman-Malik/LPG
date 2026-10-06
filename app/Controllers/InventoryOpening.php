<?php

namespace App\Controllers;

use App\Models\CylinderTypeModel;
use App\Models\InventoryOpeningBalanceModel;
use App\Services\AuditService;
use App\Services\PermissionService;
use App\Services\CylinderUnitService;
use Config\Database;
use CodeIgniter\Controller;

class InventoryOpening extends Controller
{
    protected InventoryOpeningBalanceModel $model;
    protected CylinderTypeModel $types;
    protected CylinderUnitService $cylinders;

    public function __construct()
    {
        $this->model = new InventoryOpeningBalanceModel();
        $this->types = new CylinderTypeModel();
        $this->cylinders = new CylinderUnitService();
    }

    private function guard(): ?\CodeIgniter\HTTP\ResponseInterface
    {
        if (!PermissionService::allows('INVENTORY_MANAGE')) {
            return $this->response->setStatusCode(403)->setBody('Forbidden');
        }
        return null;
    }

    public function index()
    {
        if ($r = $this->guard()) {
            return $r;
        }

        $locationId = (int) session()->get('location_id');
        $rows = $this->model
            ->select('inventory_opening_balances.*, cylinder_types.code AS cylinder_code, cylinder_types.name AS cylinder_name')
            ->join('cylinder_types', 'cylinder_types.id=inventory_opening_balances.cylinder_type_id', 'left')
            ->where('inventory_opening_balances.location_id', $locationId)
            ->orderBy('inventory_date', 'DESC')
            ->orderBy('inventory_type')
            ->findAll();

        $gasByOpening = $this->model->gasStockByOpeningIds(
            $locationId,
            array_map(static fn(array $row): int => (int) $row['id'], $rows)
        );

        foreach ($rows as &$row) {
            $row['gas_stock'] = $gasByOpening[(int) $row['id']] ?? 0;
        }
        unset($row);

        return view('inventory/opening', [
            'title' => 'Opening Inventory',
            'types' => $this->types->where('is_active', 1)->orderBy('sort_order')->findAll(),
            'rows' => $rows,
        ]);
    }

    public function save()
    {
        if ($r = $this->guard()) {
            return $r;
        }

        $locationId = (int) session()->get('location_id');
        $userId = (int) session()->get('user_id');
        $openingIdRaw = $this->request->getPost('opening_id');
        $openingId = $openingIdRaw === null || $openingIdRaw === '' ? null : (int) $openingIdRaw;

        $date = (string) $this->request->getPost('inventory_date');
        $kind = (string) $this->request->getPost('inventory_type');
        $typeId = $this->request->getPost('cylinder_type_id') === '' ? null : (int) $this->request->getPost('cylinder_type_id');
        $qty = (float) $this->request->getPost('quantity');
        $actualRaw = $this->request->getPost('actual_gas_weight_kg');
        $actual = $actualRaw !== null && $actualRaw !== '' ? (float) $actualRaw : 0;
        $comments = trim((string) ($this->request->getPost('comments') ?? ''));

        $partialFilled = $kind === 'partially_filled_cylinder';
        if ($partialFilled) {
            $kind = 'filled_cylinder';
        }

        if (!$date || !in_array($kind, ['filled_cylinder', 'empty_cylinder'], true) || $qty < 0) {
            return redirect()->back()->withInput()->with('error', 'Valid date, inventory type and non-negative quantity are required.');
        }
        if (!$typeId) {
            return redirect()->back()->withInput()->with('error', 'Select a cylinder type.');
        }
        if (floor($qty) !== $qty) {
            return redirect()->back()->withInput()->with('error', 'Cylinder quantity must be a whole number.');
        }
        if (mb_strlen($comments) > 500) {
            return redirect()->back()->withInput()->with('error', 'Comments cannot exceed 500 characters.');
        }

        $ct = $this->types->find($typeId);
        if (!$ct) {
            return redirect()->back()->withInput()->with('error', 'Cylinder type not found.');
        }

        $actualProvided = $actualRaw !== null && $actualRaw !== '';

        if ($kind === 'filled_cylinder') {
            if ($actualProvided) {
                $actual = (float) $actualRaw;
                if ($actual <= 0 || $actual > (float) $ct['capacity_kg']) {
                    return redirect()->back()->withInput()->with('error', 'Actual gas weight must be greater than zero and cannot exceed cylinder capacity.');
                }
                if ($partialFilled && $actual >= (float) $ct['capacity_kg']) {
                    return redirect()->back()->withInput()->with('error', 'For a partially filled cylinder, actual gas weight must be less than the cylinder capacity.');
                }
            } else {
                if ($partialFilled) {
                    return redirect()->back()->withInput()->with('error', 'Actual gas weight is required for a partially filled cylinder.');
                }
                $actual = (float) $ct['capacity_kg'];
            }
        } else {
            $actual = 0;
        }

        $existing = null;
        if ($openingId !== null) {
            $existing = $this->model
                ->where(['id' => $openingId, 'location_id' => $locationId])
                ->first();
            if (!$existing) {
                return redirect()->back()->withInput()->with('error', 'Opening inventory record not found.');
            }

            if ((int) $existing['cylinder_type_id'] !== $typeId || $existing['inventory_type'] !== $kind) {
                return redirect()->back()->withInput()->with('error', 'Inventory type and cylinder type cannot be changed after an opening record is created.');
            }
        } else {
            $existing = $this->model
                ->where([
                    'location_id' => $locationId,
                    'inventory_date' => $date,
                    'inventory_type' => $kind,
                    'cylinder_type_id' => $typeId,
                ])
                ->first();
        }

        $db = Database::connect();

        if ($existing) {
            $existingUnits = $db->table('cylinder_units')
                ->select('id')
                ->where([
                    'location_id' => $locationId,
                    'source_type' => 'opening',
                    'source_id' => (int) $existing['id'],
                ])
                ->get()->getResultArray();

            $existingUnitIds = array_values(array_map(
                static fn(array $unit): int => (int) $unit['id'],
                $existingUnits
            ));

            if ($existingUnitIds) {
                $downstreamCount = (int) $db->table('inventory_movements')
                    ->whereIn('cylinder_unit_id', $existingUnitIds)
                    ->where('source_type !=', 'opening_cylinder')
                    ->countAllResults();

                if ($downstreamCount > 0) {
                    return redirect()->back()->withInput()->with(
                        'error',
                        'This opening entry cannot be edited because one or more of its physical cylinders already have downstream inventory history. Use stock adjustment or reversal instead.'
                    );
                }
            }
        }

        $db->transBegin();

        try {
            $oldValues = $existing ? [
                'id' => (int) $existing['id'],
                'inventory_date' => $existing['inventory_date'],
                'inventory_type' => $existing['inventory_type'],
                'cylinder_type_id' => (int) $existing['cylinder_type_id'],
                'quantity' => (float) $existing['quantity'],
                'comments' => $existing['comments'] ?? null,
            ] : null;

            $data = [
                'location_id' => $locationId,
                'inventory_date' => $date,
                'inventory_type' => $kind,
                'cylinder_type_id' => $typeId,
                'quantity' => $qty,
                'comments' => $comments !== '' ? $comments : null,
                'created_by' => $existing['created_by'] ?? $userId,
            ];

            if ($existing) {
                $this->model->update($existing['id'], $data);
                $openingId = (int) $existing['id'];
            } else {
                $this->model->insert($data);
                $openingId = (int) $this->model->getInsertID();
            }

            $units = $db->table('cylinder_units')
                ->where([
                    'location_id' => $locationId,
                    'source_type' => 'opening',
                    'source_id' => $openingId,
                ])
                ->whereIn('status', ['filled', 'empty'])
                ->get()->getResultArray();

            if ($kind === 'filled_cylinder' && !$actualProvided && $existing) {
                $existingFilledWeights = array_values(array_filter(
                    array_map(static fn(array $unit): float => (float) $unit['gas_weight_kg'], $units),
                    static fn(float $weight): bool => $weight > 0
                ));
                if ($existingFilledWeights) {
                    $actual = $existingFilledWeights[0];
                }
            }

            $activeCount = count($units);
            $newUnitGasWeight = $actual;
            if ($activeCount > $qty) {
                throw new \RuntimeException('Opening quantity cannot be reduced below cylinders already in active opening stock. Use stock transactions instead.');
            }

            if ($activeCount < $qty) {
                $this->cylinders->createUnits(
                    $locationId,
                    $typeId,
                    (int) $qty - $activeCount,
                    $kind === 'filled_cylinder' ? 'filled' : 'empty',
                    $newUnitGasWeight,
                    $userId,
                    'opening',
                    $openingId
                );
            }

            if ($kind === 'filled_cylinder') {
                $db->table('inventory_movements')
                    ->where(['source_type' => 'opening_cylinder', 'source_id' => $openingId])
                    ->delete();

                $current = $db->table('cylinder_units')
                    ->where([
                        'location_id' => $locationId,
                        'source_type' => 'opening',
                        'source_id' => $openingId,
                        'status' => 'filled',
                    ])
                    ->get()->getResultArray();

                foreach ($current as $unit) {
                    $unitGasWeight = (float) $unit['gas_weight_kg'];
                    if ($actualProvided) {
                        $unitGasWeight = $actual;
                        $db->table('cylinder_units')
                            ->where('id', $unit['id'])
                            ->update(['gas_weight_kg' => $unitGasWeight]);
                    }

                    $db->table('inventory_movements')->insert([
                        'location_id' => $locationId,
                        'inventory_type' => 'gas_kg',
                        'cylinder_type_id' => null,
                        'quantity' => $unitGasWeight,
                        'direction' => 'in',
                        'movement_at' => $date . ' 00:00:00',
                        'source_type' => 'opening_cylinder',
                        'source_id' => $openingId,
                        'cylinder_unit_id' => $unit['id'],
                        'created_by' => $userId,
                        'notes' => 'Gas contained in opening filled cylinder',
                    ]);
                }
            }

            $newValues = [
                'id' => $openingId,
                'inventory_date' => $date,
                'inventory_type' => $kind,
                'cylinder_type_id' => $typeId,
                'quantity' => $qty,
                'comments' => $comments !== '' ? $comments : null,
                'gas_stock_kg' => $kind === 'filled_cylinder'
                    ? (float) $db->table('cylinder_units')
                        ->selectSum('gas_weight_kg')
                        ->where([
                            'location_id' => $locationId,
                            'source_type' => 'opening',
                            'source_id' => $openingId,
                            'status' => 'filled',
                        ])
                        ->get()->getRow('gas_weight_kg')
                    : 0,
            ];

            AuditService::log(
                $oldValues === null ? 'CREATE' : 'UPDATE',
                'inventory_opening',
                $openingId,
                $oldValues,
                $newValues,
                $userId,
                $locationId
            );

            if (!$db->transStatus()) {
                throw new \RuntimeException('Opening inventory could not be saved.');
            }

            $db->transCommit();

            return redirect()->to(site_url('inventory/opening'))
                ->with('success', $oldValues === null ? 'Opening inventory created successfully.' : 'Opening inventory updated successfully.');
        } catch (\Throwable $e) {
            $db->transRollback();
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function delete(int $id)
    {
        if ($r = $this->guard()) {
            return $r;
        }

        $locationId = (int) session()->get('location_id');
        $userId = (int) session()->get('user_id');

        $existing = $this->model
            ->where(['id' => $id, 'location_id' => $locationId])
            ->first();

        if (!$existing) {
            return redirect()->back()->with('error', 'Opening inventory record not found.');
        }

        $db = Database::connect();
        $units = $db->table('cylinder_units')
            ->where([
                'location_id' => $locationId,
                'source_type' => 'opening',
                'source_id' => $id,
            ])
            ->get()->getResultArray();

        $unitIds = array_values(array_map(
            static fn(array $unit): int => (int) $unit['id'],
            $units
        ));

        if ($unitIds) {
            $downstreamCount = (int) $db->table('inventory_movements')
                ->whereIn('cylinder_unit_id', $unitIds)
                ->where('source_type !=', 'opening_cylinder')
                ->countAllResults();

            if ($downstreamCount > 0) {
                return redirect()->back()->with(
                    'error',
                    'This opening entry cannot be deleted because one or more of its physical cylinders already have downstream inventory history. Use stock adjustment or reversal instead.'
                );
            }
        }

        $oldValues = [
            'id' => (int) $existing['id'],
            'inventory_date' => $existing['inventory_date'],
            'inventory_type' => $existing['inventory_type'],
            'cylinder_type_id' => $existing['cylinder_type_id'] !== null ? (int) $existing['cylinder_type_id'] : null,
            'quantity' => (float) $existing['quantity'],
            'comments' => $existing['comments'] ?? null,
            'gas_stock_kg' => $existing['inventory_type'] === 'filled_cylinder'
                ? array_sum(array_map(static fn(array $unit): float => (float) $unit['gas_weight_kg'], $units))
                : 0,
        ];

        $db->transBegin();

        try {
            $db->table('inventory_movements')
                ->where(['source_type' => 'opening_cylinder', 'source_id' => $id])
                ->delete();
            $db->table('cylinder_units')
                ->where(['location_id' => $locationId, 'source_type' => 'opening', 'source_id' => $id])
                ->delete();
            $this->model->delete($id);

            AuditService::log(
                'DELETE',
                'inventory_opening',
                $id,
                $oldValues,
                null,
                $userId,
                $locationId
            );

            if (!$db->transStatus()) {
                throw new \RuntimeException('Opening inventory could not be deleted.');
            }

            $db->transCommit();
            return redirect()->to(site_url('inventory/opening'))
                ->with('success', 'Opening inventory deleted successfully.');
        } catch (\Throwable $e) {
            $db->transRollback();
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
