<?php

namespace App\Controllers;

use App\Models\ShopSettingsModel;
use App\Services\InventoryControlService;
use App\Services\PermissionService;
use CodeIgniter\Controller;
use Config\Database;

class ShopSettings extends Controller
{
    private function guard(): ?\CodeIgniter\HTTP\ResponseInterface
    {
        $allowed = PermissionService::allows('USER_MANAGE') || PermissionService::allows('INVENTORY_MANAGE');
        return $allowed ? null : $this->response->setStatusCode(403)->setBody('Forbidden');
    }

    public function index()
    {
        if ($r = $this->guard()) return $r;

        $db = Database::connect();
        $locationId = (int) session()->get('location_id');
        $settings = (new ShopSettingsModel())->forLocation($locationId);
        $location = $db->table('locations')->where('id', $locationId)->get()->getRowArray() ?: [];

        return view('shop-settings/index', [
            'title' => 'Shop Settings',
            'settings' => $settings,
            'location' => $location,
        ]);
    }

    public function save()
    {
        if ($r = $this->guard()) return $r;

        $locationId = (int) session()->get('location_id');
        $saleMode = trim((string) $this->request->getPost('default_sale_mode'));
        $paymentMode = trim((string) $this->request->getPost('default_payment_mode'));
        $posFontSize = (float) $this->request->getPost('pos_font_size_px');
        if ($posFontSize < 10 || $posFontSize > 24) {
            return redirect()->back()->withInput()->with('error', 'POS font size must be between 10 and 24 pixels.');
        }

        if (!in_array($saleMode, ShopSettingsModel::SALE_MODES, true)) {
            return redirect()->back()->withInput()->with('error', 'Invalid default POS transaction type.');
        }
        if (!in_array($paymentMode, ShopSettingsModel::PAYMENT_MODES, true)) {
            return redirect()->back()->withInput()->with('error', 'Invalid default payment mode.');
        }

        $db = Database::connect();
        $db->transBegin();

        try {
            $db->table('locations')->where('id', $locationId)->update([
                'name' => trim((string) $this->request->getPost('shop_name')),
                'address' => trim((string) $this->request->getPost('shop_address')) ?: null,
                'city' => trim((string) $this->request->getPost('shop_city')) ?: null,
                'phone' => trim((string) $this->request->getPost('shop_phone')) ?: null,
            ]);

            $model = new ShopSettingsModel();
            $settings = $model->forLocation($locationId);
            $data = [
                'location_id' => $locationId,
                'default_sale_mode' => $saleMode,
                'default_payment_mode' => $paymentMode,
                'pos_font_size_px' => $posFontSize,
                'stock_validation_enabled' => $this->request->getPost('stock_validation_enabled') ? 1 : 0,
                'allow_stock_override' => $this->request->getPost('allow_stock_override') ? 1 : 0,
                'backup_enabled' => $this->request->getPost('backup_enabled') ? 1 : 0,
                'db_backup_url' => trim((string) $this->request->getPost('db_backup_url')) ?: null,
                'backup_notes' => trim((string) $this->request->getPost('backup_notes')) ?: null,
                'receipt_title' => trim((string) $this->request->getPost('receipt_title')) ?: 'SALE RECEIPT',
                'receipt_footer' => trim((string) $this->request->getPost('receipt_footer')) ?: 'Thank you',
                'show_address_on_receipt' => $this->request->getPost('show_address_on_receipt') ? 1 : 0,
                'settings_note' => trim((string) $this->request->getPost('settings_note')) ?: null,
            ];

            if ((int) ($settings['id'] ?? 0) > 0) {
                $model->update((int) $settings['id'], $data);
            } else {
                $model->insert($data);
            }

            $inventory = new InventoryControlService();
            $defaultPolicy = $inventory->policy($locationId);
            $inventory->savePolicy(
                $locationId,
                null,
                (bool) $data['stock_validation_enabled'],
                (string) $defaultPolicy['wastage_mode'],
                (float) $defaultPolicy['wastage_percent'],
                (float) $defaultPolicy['wastage_fixed_kg'],
                (int) session()->get('user_id')
            );

            $db->table('audit_logs')->insert([
                'user_id'=>(int)session()->get('user_id'),
                'location_id'=>$locationId,
                'action'=>'settings_update',
                'entity_type'=>'shop_settings',
                'entity_id'=>(int)($settings['id'] ?? 0),
                'old_values'=>json_encode($settings, JSON_UNESCAPED_UNICODE),
                'new_values'=>json_encode($data, JSON_UNESCAPED_UNICODE),
                'ip_address'=>$this->request->getIPAddress(),
                'user_agent'=>substr((string)$this->request->getUserAgent(),0,500)
            ]);
            if (!$db->transStatus()) {
                throw new \RuntimeException('Shop settings could not be saved.');
            }

            $db->transCommit();
            return redirect()->to('/shop-settings')->with('success', 'Shop settings saved successfully.');
        } catch (\Throwable $e) {
            $db->transRollback();
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }
}