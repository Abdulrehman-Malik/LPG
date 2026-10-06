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
            ->select('inventory_opening_balances.*, cylinder_types.code AS cylinder_code, cylinder_types.name AS cylinder_name, cylinder_types.capacity_kg')
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
        $typeId = $this->request->getPost('cylinder_type_id') === '' ? null : (int) $this->request->getPost('cylinder_type_id');
        $qtyRaw = $this->request->getPost('quantity');
        $qty = is_numeric($qtyRaw) ? (float) $qtyRaw : 0;
        $actualRaw = $this->request->getPost('actual_gas_weight_kg');
        $comments = trim((string) ($this->request->getPost('comments') ?? ''));

        if (!$date || !preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', $date) || $qty < 1) {
            return redirect()->back()->withInput()->with('error', 'Valid date and a cylinder quantity of at least 1 are required.');
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
        if (!$actualProvided || !is_numeric($actualRaw)) {
            return redirect()->back()->withInput()->with('error', 'Enter the actual gas quantity per cylinder. Enter 0 for an empty cylinder.');
        }

        $actual = (float) $actualRaw;
        $capacity = (float) $ct['capacity_kg'];

        if ($capacity <= 0) {
            return redirect()->back()->withInput()->with('error', 'Selected cylinder type has an invalid capacity.');
        }
        if ($actual < 0) {
            return redirect()->back()->withInput()->with('error', 'Gas quantity cannot be negative.');
        }
        if ($actual > $capacity + 0.00001) {
            return redirect()->back()->withInput()->with('error', 'Gas quantity cannot exceed the selected cylinder capacity of ' . number_format($capacity, 3) . ' KG.');
        }

        // Full / partially filled / empty is a calculated cylinder condition,
        // not a separate cylinder type.
        $kind = $actual <= 0.00001 ? 'empty_cylinder' : 'filled_cylinder';

        $existing = null;
        if ($openingId !== null) {
            $existing = $this->model
                ->where(['id' => $openingId, 'location_id' => $locationId])
                ->first();
            if (!$existing) {
                return redirect()->back()->withInput()->with('error', 'Opening inventory record not found.');
            }
        }

        $db = Database::connect();

        // Cylinder type is part of the physical identity. It may not be changed
        // while editing an opening entry.
        if ($existing) {
            $typeId = (int) $existing['cylinder_type_id'];
            $ct = $this->types->find($typeId);
            if (!$ct) {
                return redirect()->back()->withInput()->with('error', 'The cylinder type used by this opening record no longer exists.');
            }
            $capacity = (float) $ct['capacity_kg'];
            if ($actual > $capacity + 0.00001) {
                return redirect()->back()->withInput()->with('error', 'Gas quantity cannot exceed the selected cylinder capacity of ' . number_format($capacity, 3) . ' KG.');
            }

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
            // The opening table has a unique key on location + date + type + cylinder type.
            // Check it explicitly so users get a useful validation message instead of
            // the generic "could not be saved" transaction error.
            $duplicateQuery = $db->table('inventory_opening_balances')
                ->where('location_id', $locationId)
                ->where('inventory_date', $date)
                ->where('inventory_type', $kind)
                ->where('cylinder_type_id', $typeId);

            if ($existing) {
                $duplicateQuery->where('id !=', (int) $existing['id']);
            }

            if ($duplicateQuery->countAllResults() > 0) {
                throw new \RuntimeException('An opening inventory entry already exists for this date, cylinder type, and cylinder status. Edit the existing entry instead.');
            }

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
                $ok = $db->table('inventory_opening_balances')
                    ->where(['id' => (int) $existing['id'], 'location_id' => $locationId])
                    ->update($data);

                if ($ok === false) {
                    $error = $db->error();
                    throw new \RuntimeException('Opening inventory update failed: ' . ($error['message'] ?? 'Database error.'));
                }

                $openingId = (int) $existing['id'];
            } else {
                $ok = $db->table('inventory_opening_balances')->insert($data);

                if ($ok === false) {
                    $error = $db->error();
                    throw new \RuntimeException('Opening inventory insert failed: ' . ($error['message'] ?? 'Database error.'));
                }

                $openingId = (int) $db->insertID();
                if ($openingId <= 0) {
                    throw new \RuntimeException('Opening inventory insert failed: no record ID was generated.');
                }
            }

            // Every new opening save creates the requested number of new
            // physical cylinder units. On edit, the existing opening is rebuilt
            // only after the downstream-history guard above confirms it is safe.
            if ($existing) {
                $db->table('inventory_movements')
                    ->where(['source_type' => 'opening_cylinder', 'source_id' => $openingId])
                    ->delete();

                $db->table('cylinder_units')
                    ->where([
                        'location_id' => $locationId,
                        'source_type' => 'opening',
                        'source_id' => $openingId,
                    ])
                    ->delete();
            }

            $unitStatus = $actual <= 0.00001 ? 'empty' : 'filled';

            $this->cylinders->createUnits(
                $locationId,
                $typeId,
                (int) $qty,
                $unitStatus,
                $actual,
                $userId,
                'opening',
                $openingId
            );

            if ($unitStatus === 'filled') {
                $current = $db->table('cylinder_units')
                    ->where([
                        'location_id' => $locationId,
                        'source_type' => 'opening',
                        'source_id' => $openingId,
                        'status' => 'filled',
                    ])
                    ->get()->getResultArray();

                foreach ($current as $unit) {
                    $db->table('inventory_movements')->insert([
                        'location_id' => $locationId,
                        'inventory_type' => 'gas_kg',
                        'cylinder_type_id' => null,
                        'quantity' => $actual,
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
                'cylinder_status' => $actual <= 0.00001
                    ? 'Empty'
                    : ($actual < $capacity - 0.00001 ? 'Partially Filled' : 'Full'),
                'gas_per_cylinder_kg' => $actual,
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
                $error = $db->error();
                throw new \RuntimeException('Opening inventory could not be saved: ' . ($error['message'] ?? 'database transaction failed.'));
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
