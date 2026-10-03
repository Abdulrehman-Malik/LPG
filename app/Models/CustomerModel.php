<?php
namespace App\Models;
use CodeIgniter\Model;
class CustomerModel extends Model
{
    protected $table='customers'; protected $primaryKey='id'; protected $returnType='array';
    protected $useTimestamps=true; protected $createdField='created_at'; protected $updatedField='updated_at';
    protected $allowedFields=['code','name','phone','city','address','vehicle_no','credit_limit','opening_balance','is_active'];
    public function withLedgerTotals(int $customerId): ?array
    {
        $customer=$this->find($customerId); if(!$customer) return null;
        $s=$this->db->table('sales')->selectSum('total_amount','total_purchased')->selectSum('credit_amount','total_credit')->selectSum('total_kg','total_kg')->where('customer_id',$customerId)->where('status','posted')->get()->getRowArray();
        $p=$this->db->table('customer_receipts')->selectSum('amount','total_paid')->where('customer_id',$customerId)->where('status','posted')->get()->getRowArray();
        $customer['total_purchased']=(float)($s['total_purchased']??0); $customer['total_gas_kg']=(float)($s['total_kg']??0);
        $customer['credit_due']=(float)$customer['opening_balance']+(float)($s['total_credit']??0)-(float)($p['total_paid']??0);
        $d=$this->db->table('customer_security_deposits')->select("SUM(CASE WHEN entry_type='hold' THEN amount ELSE 0 END) deposit_held,SUM(CASE WHEN entry_type='refund' THEN amount ELSE 0 END) deposit_refunded")->where(['customer_id'=>$customerId])->get()->getRowArray();
        $customer['security_deposit_held']=(float)($d['deposit_held']??0)-(float)($d['deposit_refunded']??0);
        return $customer;
    }
    public function activeDirectory(): array { return $this->where('is_active',1)->orderBy('name')->findAll(); }
}