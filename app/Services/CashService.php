<?php
namespace App\Services;
use Config\Database;
use RuntimeException;
class CashService {
 protected $db;
 public function __construct(){ $this->db=Database::connect(); }
 public function openSession(int $userId,int $locationId,float $openingCash,string $notes=''): int {
  if($openingCash<0) throw new RuntimeException('Opening cash cannot be negative.');
  $register=$this->db->table('cash_registers')->where('location_id',$locationId)->where('is_active',1)->orderBy('id')->get()->getRowArray();
  if(!$register) throw new RuntimeException('No active cash register exists for this location.');
  if($this->db->table('cash_sessions')->where('register_id',$register['id'])->where('status','open')->get()->getRowArray()) throw new RuntimeException('An open cash session already exists for this register.');
  $this->db->transBegin();
  try {
   $this->db->table('cash_sessions')->insert(['register_id'=>$register['id'],'opened_by'=>$userId,'opening_cash'=>$openingCash,'notes'=>trim($notes)?:null]);
   $id=(int)$this->db->insertID();
   if($openingCash>0) $this->db->table('cash_transactions')->insert(['cash_session_id'=>$id,'transaction_type'=>'opening_float','direction'=>'in','amount'=>$openingCash,'created_by'=>$userId,'notes'=>'Opening float']);
   if(!$this->db->transStatus()) throw new RuntimeException('Cash session could not be opened.');
   $this->db->transCommit(); return $id;
  } catch(\Throwable $e){$this->db->transRollback();throw $e;}
 }
 public function openSessionForLocation(int $locationId): ?array {
  return $this->db->table('cash_sessions cs')->select('cs.*,cr.code register_code,cr.name register_name')->join('cash_registers cr','cr.id=cs.register_id')->where('cr.location_id',$locationId)->where('cs.status','open')->orderBy('cs.id','DESC')->get()->getRowArray() ?: null;
 }
 public function postSaleCash(int $sessionId,int $saleId,float $amount,int $userId,string $at): void {
  if($amount<=0) return;
  if(!$this->db->table('cash_sessions')->where('id',$sessionId)->where('status','open')->get()->getRowArray()) throw new RuntimeException('No open cash session is available for this cash sale.');
  $this->db->table('cash_transactions')->insert(['cash_session_id'=>$sessionId,'transaction_type'=>'sale_cash','direction'=>'in','amount'=>$amount,'transaction_at'=>$at,'reference_type'=>'sale','reference_id'=>$saleId,'created_by'=>$userId]);
  if(!$this->db->transStatus()) throw new RuntimeException('Cash transaction could not be posted.');
 }
 public function summary(int $sessionId): array {
  $session=$this->db->table('cash_sessions')->where('id',$sessionId)->get()->getRowArray();
  if(!$session) throw new RuntimeException('Cash session not found.');
  $row=$this->db->table('cash_transactions')->select("SUM(CASE WHEN direction='in' THEN amount ELSE 0 END) cash_in,SUM(CASE WHEN direction='out' THEN amount ELSE 0 END) cash_out")->where('cash_session_id',$sessionId)->get()->getRowArray();
  return ['session'=>$session,'cash_in'=>(float)($row['cash_in']??0),'cash_out'=>(float)($row['cash_out']??0),'expected'=>(float)$session['opening_cash']+(float)($row['cash_in']??0)-(float)($row['cash_out']??0)];
 }
 public function closeSession(int $sessionId,int $userId,float $countedCash,string $notes=''): array {
  if($countedCash<0) throw new RuntimeException('Counted cash cannot be negative.');
  $summary=$this->summary($sessionId);
  if($summary['session']['status']!=='open') throw new RuntimeException('Cash session is already closed.');
  $this->db->transBegin();
  try {
   $this->db->table('cash_sessions')->where('id',$sessionId)->update(['closed_by'=>$userId,'closed_at'=>date('Y-m-d H:i:s'),'counted_cash'=>$countedCash,'status'=>'closed','notes'=>trim($notes)?:$summary['session']['notes']]);
   if(!$this->db->transStatus()) throw new RuntimeException('Cash session could not be closed.');
   $this->db->transCommit(); return ['expected'=>$summary['expected'],'counted'=>$countedCash,'difference'=>$countedCash-$summary['expected']];
  } catch(\Throwable $e){$this->db->transRollback();throw $e;}
 }
}