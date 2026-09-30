<?php

namespace App\Models;

use CodeIgniter\Model;

class ShopSettingsModel extends Model
{
    protected $table = 'shop_settings';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'location_id','default_sale_mode','default_payment_mode','pos_font_size_px',
        'stock_validation_enabled','allow_stock_override',
        'backup_enabled','db_backup_url','backup_notes',
        'receipt_title','receipt_footer','show_address_on_receipt',
        'settings_note'
    ];

    public const SALE_MODES = [
        'sell_gas_only',
        'replace_same',
        'sell_filled',
        'replace_different',
        'sell_empty',
    ];

    public const PAYMENT_MODES = ['cash','cheque','online','credit'];

    public function forLocation(int $locationId): array
    {
        $defaults = [
            'id' => 0,
            'location_id' => $locationId,
            'default_sale_mode' => 'sell_gas_only',
            'default_payment_mode' => 'cash',
            'pos_font_size_px' => 14,
            'stock_validation_enabled' => 1,
            'allow_stock_override' => 1,
            'backup_enabled' => 0,
            'db_backup_url' => '',
            'backup_notes' => '',
            'receipt_title' => 'SALE RECEIPT',
            'receipt_footer' => 'Thank you',
            'show_address_on_receipt' => 1,
            'settings_note' => '',
        ];

        try {
            $row = $this->where('location_id', $locationId)->first();
            if (!$row) {
                $this->insert($defaults);
                $row = $this->where('location_id', $locationId)->first();
            }
            return array_merge($defaults, (array) $row);
        } catch (\Throwable $e) {
            return $defaults;
        }
    }
}