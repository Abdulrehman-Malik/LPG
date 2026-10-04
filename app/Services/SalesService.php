<?php

namespace App\Services;

use App\Models\CylinderTypeModel;
use App\Models\CustomerModel;
use App\Models\GasRateModel;
use App\Models\ShopSettingsModel;
use Config\Database;
use RuntimeException;

class SalesService
{
    protected $db;
    protected $types;
    protected $customers;
    protected $rates;
    protected $cash;
    protected $cylinders;
    protected array $inventoryLocks = [];
    protected ?int $currentLocationId = null;

    public function __construct()
    {
        $this->currentLocationId=(int)session()->get('location_id');
        $this->db=Database::connect();
        $this->types=new CylinderTypeModel();
        $this->customers=new CustomerModel();
        $this->rates=new GasRateModel();
        $this->cash=new CashService();
        $this->cylinders=new CylinderUnitService();
    }

    public function post(array $payload,int $userId,int $locationId): array
    {
        $customerId=!empty($payload['customer_id'])?(int)$payload['customer_id']:null;
        $headerType=(string)($payload['transaction_type']??'');
        if(in_array($headerType,['gas_sale','cylinder_sale','security_deposit','cylinder_return'],true)){
            return $this->postHeaderTransaction($payload,$userId,$locationId,$headerType);
        }

        $lines=is_array($payload['lines']??null)?$payload['lines']:[];
        $payments=is_array($payload['payments']??null)?$payload['payments']:[];
        if(!$lines) throw new RuntimeException('At least one sale line is required.');
        if(!$payments) throw new RuntimeException('At least one payment is required.');

        $transactionAt=str_replace('T',' ',trim((string)($payload['transaction_at']??date('Y-m-d H:i:s'))));
        $notes=trim((string)($payload['notes']??''))?:null;
        $customer=$customerId?$this->customers->find($customerId):null;
        if($customerId && !$customer) throw new RuntimeException('Customer not found.');
        if($customerId && !(int)$customer['is_active']) throw new RuntimeException('Customer is inactive.');

        $prepared=[];$subtotal=0;$totalKg=0;$customRate=false;$gasRequirements=[];
        foreach($lines as $i=>$line){
            $mode=(string)($line['sale_mode']??'');
            $allowed=['sell_gas_only','replace_same','sell_filled','replace_different','sell_empty'];
            if(!in_array($mode,$allowed,true)) throw new RuntimeException('Unsupported POS transaction type on line '.($i+1).'.');

            $qty=(float)($line['quantity']??0);
            if(!$customerId && in_array($mode,['replace_same','replace_different'],true)) throw new RuntimeException('A named customer is required when an empty cylinder is returned.');
            $typeId=isset($line['cylinder_type_id'])&&$line['cylinder_type_id']!==''?(int)$line['cylinder_type_id']:null;
            $receivedTypeId=isset($line['received_cylinder_type_id'])&&$line['received_cylinder_type_id']!==''?(int)$line['received_cylinder_type_id']:null;
            $gasRateInput=trim((string)($line['gas_rate']??''))===''?null:(float)$line['gas_rate'];
            $cylinderRateInput=trim((string)($line['cylinder_rate']??''))===''?null:(float)$line['cylinder_rate'];
            if($qty<=0) throw new RuntimeException('Line '.($i+1).' quantity must be greater than zero.');
            if(($gasRateInput!==null && $gasRateInput<0)||($cylinderRateInput!==null && $cylinderRateInput<0)) throw new RuntimeException('Line '.($i+1).' rate cannot be negative.');

            $type=$typeId?(new CylinderTypeModel())->find($typeId):null;
            if(in_array($mode,['sell_gas_only','replace_same','sell_filled','replace_different','sell_empty'],true) && !$typeId) throw new RuntimeException('Cylinder type is required on line '.($i+1).'.');
            if($typeId && (!$type || !(int)$type['is_active'])) throw new RuntimeException('Invalid or inactive cylinder type on line '.($i+1).'.');

            $gasRate=0;$cylinderRate=0;$gasKg=0;$standardGasRate=null;$standardCylinderRate=null;
            $emptyReceived=0;$previewUnits=[];

            if(in_array($mode,['sell_gas_only','replace_same','sell_filled','replace_different'],true)){
                $standardGasRate=$this->rates->currentKgRate($transactionAt);
                if($standardGasRate===null) throw new RuntimeException('No effective gas/kg rate exists.');
                $gasRate=$gasRateInput??$standardGasRate;
            }
            if(in_array($mode,['sell_filled','replace_different','sell_empty'],true)){
                $standardCylinderRate=$this->rates->currentCylinderRate($typeId,$transactionAt);
                if($standardCylinderRate===null) throw new RuntimeException('No effective cylinder price exists for '.$type['name'].'.');
                $cylinderRate=$cylinderRateInput??$standardCylinderRate;
            }

            if($mode==='sell_gas_only'){
                $gasKg=(float)($line['gas_weight_kg']??$qty);
                $sourceUnitId=isset($line['source_cylinder_unit_id'])&&$line['source_cylinder_unit_id']!==''?(int)$line['source_cylinder_unit_id']:0;
                if($gasKg<=0) throw new RuntimeException('Gas KG must be greater than zero on line '.($i+1).'.');
                if($sourceUnitId<=0) throw new RuntimeException('Source filled cylinder is required on line '.($i+1).'.');
                $qty=$gasKg;
                $sourceUnit=$this->db->table('cylinder_units')
                    ->select('id,unit_code,cylinder_type_id,status,gas_weight_kg')
                    ->where(['id'=>$sourceUnitId,'location_id'=>$locationId,'cylinder_type_id'=>$typeId,'status'=>'filled'])
                    ->get()->getRowArray();
                if(!$sourceUnit) throw new RuntimeException('Selected source cylinder is invalid or no longer available on line '.($i+1).'.');
                if($gasKg>(float)$sourceUnit['gas_weight_kg']+0.00001) throw new RuntimeException('Gas quantity exceeds selected source cylinder stock on line '.($i+1).'. Available: '.number_format((float)$sourceUnit['gas_weight_kg'],3).' kg.');
                $gasRequirements[]=['line_no'=>$i+1,'type_id'=>$typeId,'gas_kg'=>$gasKg];
            }elseif(in_array($mode,['replace_same','sell_filled','replace_different'],true)){
                if(floor($qty)!==$qty) throw new RuntimeException('Cylinder quantity on line '.($i+1).' must be a whole number.');
                $previewUnits=$this->cylinders->availableForDisplay($locationId,$typeId,'filled');
                if(count($previewUnits)<(int)$qty) throw new RuntimeException('Insufficient filled cylinders of '.$type['name'].'.');
                $selected=array_slice($previewUnits,0,(int)$qty);
                $gasKg=array_sum(array_map(static fn($u)=>(float)$u['gas_weight_kg'],$selected));
                if($gasKg<=0) throw new RuntimeException('Selected filled cylinders contain no gas.');
                if($mode==='replace_same'){
                    $receivedTypeId=$typeId;
                }elseif($mode==='replace_different'){
                    if(!$receivedTypeId) throw new RuntimeException('Received empty cylinder type is required on line '.($i+1).'.');
                    if($receivedTypeId===$typeId) throw new RuntimeException('Different-capacity replacement must use a different received cylinder type.');
                    $receivedType=$this->types->find($receivedTypeId);
                    if(!$receivedType || !(int)$receivedType['is_active']) throw new RuntimeException('Invalid or inactive received empty-cylinder type.');
                }
                $gasRequirements[]=['line_no'=>$i+1,'type_id'=>$typeId,'gas_kg'=>$gasKg];
                if($receivedTypeId) $emptyReceived=$qty;
            }elseif($mode==='sell_empty'){
                if(floor($qty)!==$qty) throw new RuntimeException('Empty-cylinder quantity on line '.($i+1).' must be a whole number.');
                $availableEmpty=$this->cylinders->availableForDisplay($locationId,$typeId,'empty');
                if(count($availableEmpty)<(int)$qty) throw new RuntimeException('Insufficient empty cylinders of '.$type['name'].'.');
            }

            $customGas=$standardGasRate!==null && abs($gasRate-$standardGasRate)>0.00001;
            $customCylinder=$standardCylinderRate!==null && abs($cylinderRate-$standardCylinderRate)>0.00001;
            $customRate=$customRate||$customGas||$customCylinder;
            $lineTotal=0;
            if(in_array($mode,['sell_gas_only','replace_same'],true)) $lineTotal=$gasKg*$gasRate;
            elseif(in_array($mode,['sell_filled','replace_different'],true)) $lineTotal=$gasKg*$gasRate+$qty*$cylinderRate;
            elseif($mode==='sell_empty') $lineTotal=$qty*$cylinderRate;

            $subtotal+=$lineTotal;$totalKg+=$gasKg;
            $storedLineType=$mode==='sell_empty'?'empty_cylinder':($mode==='sell_gas_only'?'refill_kg':'filled_cylinder');
            $noteParts=[];
            if($mode==='sell_gas_only') $noteParts[]='Gas source: '.$type['code'].' — '.$type['name'];
            if($mode==='replace_different') $noteParts[]='Received empty: '.($receivedType['code']??'').' — '.($receivedType['name']??'');
            $prepared[]=[
                'line_no'=>$i+1,'sale_mode'=>$mode,'line_type'=>$storedLineType,'cylinder_type_id'=>$typeId,
                'source_cylinder_unit_id'=>($mode==='sell_gas_only'?(int)($line['source_cylinder_unit_id']??0):null),
                'received_cylinder_type_id'=>$receivedTypeId,'quantity'=>$qty,'gas_weight_kg'=>$gasKg,
                'gas_rate'=>$gasRate,'cylinder_rate'=>$cylinderRate,'standard_gas_rate'=>$standardGasRate,
                'standard_cylinder_rate'=>$standardCylinderRate,'custom_rate_flag'=>($customGas||$customCylinder)?1:0,
                'empty_cylinder_received'=>$emptyReceived,'line_discount'=>0,'line_total'=>$lineTotal,
                'notes'=>implode(' | ',$noteParts)?:null
            ];
        }

        $discount=max(0,(float)($payload['discount_amount']??0));
        if($discount>$subtotal) throw new RuntimeException('Discount cannot exceed subtotal.');
        $total=$subtotal-$discount;$paymentTotal=0;$credit=0;$cashAmount=0;
        foreach($payments as $p){
            $mode=(string)($p['payment_mode']??'');$amount=(float)($p['amount']??0);
            if(!in_array($mode,['cash','cheque','online','credit'],true)||$amount<=0) throw new RuntimeException('Invalid payment.');
            if(!$customerId && $mode!=='cash') throw new RuntimeException('Walk-in sales are cash only.');
            $paymentTotal+=$amount;if($mode==='credit') $credit+=$amount;if($mode==='cash') $cashAmount+=$amount;
        }
        if(abs($paymentTotal-$total)>0.01) throw new RuntimeException('Payment total must equal sale total.');

        $this->db->transBegin();
        try{
            if($customerId){
                $lockedCustomer=$this->db->query("SELECT * FROM customers WHERE id=? FOR UPDATE",[$customerId])->getRowArray();
                if(!$lockedCustomer || !(int)$lockedCustomer['is_active']) throw new RuntimeException('Customer is unavailable.');
                $customer=$lockedCustomer;
            }

            $lockRows=[];
            foreach($prepared as $row){
                if(in_array($row['sale_mode'],['sell_gas_only','replace_same','sell_filled','replace_different'],true)){
                    $lockRows[]=['type'=>'gas_kg','cylinder_type_id'=>null];
                    $lockRows[]=['type'=>'filled_cylinder','cylinder_type_id'=>(int)$row['cylinder_type_id']];
                }
                if(in_array($row['sale_mode'],['replace_same','replace_different'],true)) $lockRows[]=['type'=>'empty_cylinder','cylinder_type_id'=>(int)$row['received_cylinder_type_id']];
                if($row['sale_mode']==='sell_empty') $lockRows[]=['type'=>'empty_cylinder','cylinder_type_id'=>(int)$row['cylinder_type_id']];
            }
            $this->acquireInventoryLocks($locationId,$lockRows);

            $gasAvailable=(float)($this->db->query("SELECT COALESCE(SUM(gas_weight_kg),0) AS gas_stock FROM cylinder_units WHERE location_id=? AND status='filled'",[$locationId])->getRowArray()['gas_stock']??0);
            $gasGroups=[];
            foreach($prepared as $idx=>$row){
                if(!$row['gas_weight_kg']) continue;
                if(!isset($gasGroups[$idx])) $gasGroups[$idx]=['qty'=>0,'type_id'=>(int)$row['cylinder_type_id']];
                $gasGroups[$idx]['qty']+=(float)$row['gas_weight_kg'];
            }
            $overrideConfirmed=!empty($payload['stock_override_confirmed']);
            $shopSettings=(new ShopSettingsModel())->forLocation($locationId);
            $allowStockOverride=(int)($shopSettings['allow_stock_override']??1)===1;
            $control=new InventoryControlService();
            $virtualGasStock=$gasAvailable;
            foreach($gasGroups as $group){
                $policy=$control->policy($locationId,$group['type_id']);
                $qty=(float)$group['qty'];
                if(!(int)$policy['stock_validation_enabled']){
                    if(!$allowStockOverride) throw new RuntimeException('Gas stock validation is OFF, but stock override is disabled in Shop Settings.');
                    if(!$overrideConfirmed) throw new RuntimeException('Gas stock validation is OFF. Confirm the stock override before posting this sale.');
                }elseif($virtualGasStock+0.00001<$qty){
                    throw new RuntimeException('Insufficient gas stock. Available: '.number_format($virtualGasStock,3).' kg; required: '.number_format($qty,3).' kg.');
                }
                $virtualGasStock-=$qty;
            }

            $inventory=[];
            $reservedFilledIds=[];
            $reservedEmptyIds=[];
            foreach($prepared as $idx=>&$row){
                $mode=$row['sale_mode'];$typeId=(int)$row['cylinder_type_id'];$qty=(float)$row['quantity'];
                if($mode==='sell_gas_only'){
                    $sourceId=(int)($this->db->table('cylinder_units')
                        ->select('id')
                        ->where(['id'=>(int)($row['source_cylinder_unit_id']??0),'location_id'=>$locationId,'cylinder_type_id'=>$typeId,'status'=>'filled'])
                        ->get()->getRowArray()['id']??0);
                    if($sourceId<=0) throw new RuntimeException('Selected source cylinder is no longer available while posting. Please retry the sale.');
                    $unit=$this->db->query("SELECT * FROM cylinder_units WHERE id=? FOR UPDATE",[$sourceId])->getRowArray();
                    if(!$unit || $unit['status']!=='filled' || (int)$unit['cylinder_type_id']!==$typeId) throw new RuntimeException('Selected source cylinder changed while posting. Please retry the sale.');
                    $used=(float)$row['gas_weight_kg'];
                    $before=(float)$unit['gas_weight_kg'];
                    if($used>$before+0.00001) throw new RuntimeException('Selected source cylinder gas changed while posting. Please retry the sale.');
                    $after=$before-$used;
                    if($after<=0.00001){
                        $this->db->table('cylinder_units')->where('id',$sourceId)->update(['status'=>'empty','gas_weight_kg'=>0]);
                        $inventory[]=['line_no'=>$row['line_no'],'type'=>'gas_kg','cylinder_type_id'=>null,'quantity'=>$used,'direction'=>'out','unit_id'=>$sourceId];
                        $inventory[]=['line_no'=>$row['line_no'],'type'=>'filled_cylinder','cylinder_type_id'=>$typeId,'quantity'=>1,'direction'=>'out','unit_id'=>$sourceId,'transition'=>'to_empty'];
                        $inventory[]=['line_no'=>$row['line_no'],'type'=>'empty_cylinder','cylinder_type_id'=>$typeId,'quantity'=>1,'direction'=>'in','unit_id'=>$sourceId,'transition'=>'from_filled'];
                    }else{
                        $this->db->table('cylinder_units')->where('id',$sourceId)->update(['gas_weight_kg'=>$after,'status'=>'filled']);
                        $inventory[]=['line_no'=>$row['line_no'],'type'=>'gas_kg','cylinder_type_id'=>null,'quantity'=>$used,'direction'=>'out','unit_id'=>$sourceId];
                    }
                }elseif(in_array($mode,['replace_same','sell_filled','replace_different'],true)){
                    $units=$this->cylinders->available($locationId,$typeId,'filled');
                    $units=array_values(array_filter($units,static fn(array $unit): bool => !isset($reservedFilledIds[(int)$unit['id']])));
                    if(count($units)<(int)$qty) throw new RuntimeException('Filled-cylinder stock changed while posting. Please retry the sale.');
                    $selected=array_slice($units,0,(int)$qty);$actualGas=0;
                    foreach($selected as $unit){
                        $reservedFilledIds[(int)$unit['id']]=true;
                        $actualGas+=(float)$unit['gas_weight_kg'];
                        $inventory[]=['line_no'=>$row['line_no'],'type'=>'gas_kg','cylinder_type_id'=>null,'quantity'=>(float)$unit['gas_weight_kg'],'direction'=>'out','unit_id'=>(int)$unit['id']];
                        $inventory[]=['line_no'=>$row['line_no'],'type'=>'filled_cylinder','cylinder_type_id'=>$typeId,'quantity'=>1,'direction'=>'out','unit_id'=>(int)$unit['id'],'transition'=>'sold'];
                    }
                    if(abs($actualGas-(float)$row['gas_weight_kg'])>0.00001) throw new RuntimeException('Filled-cylinder gas weight changed while posting. Please retry the sale.');
                    if($row['received_cylinder_type_id']){
                        for($n=0;$n<(int)$qty;$n++) $inventory[]=['line_no'=>$row['line_no'],'type'=>'empty_cylinder','cylinder_type_id'=>(int)$row['received_cylinder_type_id'],'quantity'=>1,'direction'=>'in','unit_id'=>null,'transition'=>'new_empty'];
                    }
                }elseif($mode==='sell_empty'){
                    $units=$this->cylinders->available($locationId,$typeId,'empty');
                    $units=array_values(array_filter($units,static fn(array $unit): bool => !isset($reservedEmptyIds[(int)$unit['id']])));
                    if(count($units)<(int)$qty) throw new RuntimeException('Empty-cylinder stock changed while posting. Please retry the sale.');
                    foreach(array_slice($units,0,(int)$qty) as $unit){
                        $reservedEmptyIds[(int)$unit['id']]=true;
                        $inventory[]=['line_no'=>$row['line_no'],'type'=>'empty_cylinder','cylinder_type_id'=>$typeId,'quantity'=>1,'direction'=>'out','unit_id'=>(int)$unit['id'],'transition'=>'sold'];
                    }
                }
            }
            unset($row);

            $preparedSubtotal=array_sum(array_map(static fn($r)=>(float)$r['line_total'],$prepared));
            $discount=max(0,(float)($payload['discount_amount']??0));if($discount>$preparedSubtotal) throw new RuntimeException('Discount cannot exceed subtotal.');
            $total=$preparedSubtotal-$discount;$paymentTotal=0;$credit=0;$cashAmount=0;
            foreach($payments as $p){
                $mode=(string)$p['payment_mode'];$amount=(float)$p['amount'];$paymentTotal+=$amount;
                if($mode==='credit')$credit+=$amount;if($mode==='cash')$cashAmount+=$amount;
            }
            if(abs($paymentTotal-$total)>0.01) throw new RuntimeException('Payment total must equal the recalculated sale total.');
            if($customerId && $credit>0 && $this->customerBalance($customerId)+$credit>(float)$customer['credit_limit']) throw new RuntimeException('Credit limit exceeded.');

            $saleNo='S'.date('YmdHis').'-'.random_int(100,999);
            $scenarioTypes=array_values(array_unique(array_column($prepared,'sale_mode')));
            $transactionType=count($scenarioTypes)>1?'mixed':match($scenarioTypes[0]){
                'sell_gas_only'=>'refill_service','replace_same'=>'cylinder_exchange','sell_filled'=>'filled_cylinder',
                'replace_different'=>'mixed','sell_empty'=>'empty_sale'
            };
            $this->db->table('sales')->insert(['sale_no'=>$saleNo,'location_id'=>$locationId,'customer_id'=>$customerId,'transaction_type'=>$transactionType,'status'=>'posted','transaction_at'=>$transactionAt,'total_kg'=>array_sum(array_map(static fn($r)=>(float)$r['gas_weight_kg'],$prepared)),'subtotal'=>$preparedSubtotal,'discount_amount'=>$discount,'total_amount'=>$total,'credit_amount'=>$credit,'custom_rate_flag'=>$customRate?1:0,'notes'=>$notes,'created_by'=>$userId]);
            $saleId=(int)$this->db->insertID();

            foreach($prepared as $row){
                $gasKg=(float)$row['gas_weight_kg'];$qty=(float)$row['quantity'];
                if(in_array($row['sale_mode'],['sell_filled','replace_different'],true)) $appliedRate=(($gasKg/max($qty,1))*$row['gas_rate'])+$row['cylinder_rate'];
                elseif(in_array($row['sale_mode'],['sell_gas_only','replace_same'],true)) $appliedRate=$row['gas_rate']; else $appliedRate=$row['cylinder_rate'];
                $standardRate=$row['standard_gas_rate']!==null?(in_array($row['sale_mode'],['sell_filled','replace_different'],true)?((($gasKg/max($qty,1))*$row['standard_gas_rate'])+$row['standard_cylinder_rate']):$row['standard_gas_rate']):$row['standard_cylinder_rate'];
                $this->db->table('sale_items')->insert([
                    'sale_id'=>$saleId,'line_no'=>$row['line_no'],'line_type'=>$row['line_type'],'cylinder_type_id'=>$row['cylinder_type_id'],
                    'quantity'=>$qty,'gas_weight_kg'=>$gasKg,'applied_rate'=>$appliedRate,'standard_rate'=>$standardRate,
                    'custom_rate_flag'=>$row['custom_rate_flag'],'empty_cylinder_received'=>$row['empty_cylinder_received'],
                    'line_discount'=>0,'line_total'=>$row['line_total'],'notes'=>$row['notes']
                ]);
            }
            $lineIds=[];foreach($this->db->table('sale_items')->select('id,line_no')->where('sale_id',$saleId)->get()->getResultArray() as $row)$lineIds[(int)$row['line_no']]=(int)$row['id'];

            foreach($inventory as $idx=>$m){
                $this->db->table('inventory_movements')->insert([
                    'location_id'=>$locationId,'inventory_type'=>$m['type'],'cylinder_type_id'=>$m['cylinder_type_id'],
                    'quantity'=>$m['quantity'],'direction'=>$m['direction'],'movement_at'=>$transactionAt,'source_type'=>'sale',
                    'source_id'=>$saleId,'source_line_id'=>$lineIds[(int)$m['line_no']]??null,'cylinder_unit_id'=>$m['unit_id']??null,
                    'created_by'=>$userId,'notes'=>($m['transition']??null)
                ]);
                $inventory[$idx]['movement_id']=(int)$this->db->insertID();
            }

            foreach($inventory as $m){
                if($m['type']==='empty_cylinder' && $m['direction']==='in' && empty($m['unit_id'])){
                    $ids=$this->cylinders->createUnits($locationId,(int)$m['cylinder_type_id'],1,'empty',0,$userId,'sale',$saleId);
                    $this->db->table('inventory_movements')->where('id',$m['movement_id'])->update(['cylinder_unit_id'=>$ids[0]]);
                }
            }

            foreach($inventory as $m){
                if(($m['unit_id']??null) && $m['type']==='filled_cylinder' && $m['direction']==='out' && ($m['transition']??'')==='sold') $this->cylinders->markSold((int)$m['unit_id']);
                if(($m['unit_id']??null) && $m['type']==='empty_cylinder' && $m['direction']==='out') $this->cylinders->markSold((int)$m['unit_id']);
            }

            foreach($payments as $p) $this->db->table('sale_payments')->insert(['sale_id'=>$saleId,'payment_mode'=>$p['payment_mode'],'amount'=>(float)$p['amount'],'reference_no'=>trim((string)($p['reference_no']??''))?:null,'payment_at'=>$transactionAt,'received_by'=>$userId]);
            if($cashAmount>0){
                $session=$this->cash->openSessionForLocation($locationId);
                if(!$session) throw new RuntimeException('Open the counter cash session before posting a cash sale.');
                $this->cash->postSaleCash((int)$session['id'],$saleId,$cashAmount,$userId,$transactionAt);
            }
            if(!$this->db->transStatus()) throw new RuntimeException('Sale posting failed.');
            $this->db->transCommit();
            $this->releaseInventoryLocks();
            return ['id'=>$saleId,'sale_no'=>$saleNo,'total'=>$total,'customer_id'=>$customerId,'credit_amount'=>$credit];
        }catch(\Throwable $e){
            $this->releaseInventoryLocks();
            $this->db->transRollback();
            throw $e;
        }
    }

    protected function postHeaderTransaction(array $payload,int $userId,int $locationId,string $headerType): array
    {
        $customerId=!empty($payload['customer_id'])?(int)$payload['customer_id']:null;
        $transactionAt=str_replace('T',' ',trim((string)($payload['transaction_at']??date('Y-m-d H:i:s'))));
        $notes=trim((string)($payload['notes']??''))?:null;
        $lines=is_array($payload['lines']??null)?$payload['lines']:[];
        $payments=is_array($payload['payments']??null)?$payload['payments']:[];
        $deposit=max(0,(float)($payload['security_deposit_amount']??0));
        $custodyUnitIds=array_values(array_unique(array_map('intval',is_array($payload['custody_unit_ids']??null)?$payload['custody_unit_ids']:[])));
        $returnUnitIds=array_values(array_unique(array_map('intval',is_array($payload['return_unit_ids']??null)?$payload['return_unit_ids']:[])));

        $customer=$customerId?$this->customers->find($customerId):null;
        if($customerId && (!$customer || !(int)$customer['is_active'])) throw new RuntimeException('Customer is invalid or inactive.');
        if($headerType==='security_deposit' || $headerType==='cylinder_return'){
            if(!$customerId) throw new RuntimeException('A named customer is required for cylinder custody transactions.');
        }
        if($headerType!=='security_deposit' && $deposit>0) throw new RuntimeException('Security Deposit Amount is only allowed on a Security Deposit transaction.');

        if($headerType==='gas_sale') return $this->postGasSaleHeader($payload,$userId,$locationId,$customerId,$transactionAt,$notes,$lines,$payments);
        if($headerType==='cylinder_sale') return $this->postCylinderSaleHeader($payload,$userId,$locationId,$customerId,$transactionAt,$notes,$lines,$payments);
        if($headerType==='security_deposit') return $this->postSecurityDepositHeader($payload,$userId,$locationId,$customerId,$transactionAt,$notes,$payments,$custodyUnitIds,$deposit);
        return $this->postCylinderReturnHeader($payload,$userId,$locationId,$customerId,$transactionAt,$notes,$returnUnitIds);
    }

    protected function postGasSaleHeader(array $payload,int $userId,int $locationId,?int $customerId,string $transactionAt,?string $notes,array $lines,array $payments): array
    {
        if(!$lines) throw new RuntimeException('At least one gas line is required.');
        if(!$payments) throw new RuntimeException('At least one payment is required.');

        $prepared=[];$subtotal=0;$totalKg=0;$customRate=false;
        foreach($lines as $i=>$line){
            $n=$i+1;
            $typeId=isset($line['cylinder_type_id'])&&$line['cylinder_type_id']!==''?(int)$line['cylinder_type_id']:0;
            $sourceId=isset($line['source_cylinder_unit_id'])&&$line['source_cylinder_unit_id']!==''?(int)$line['source_cylinder_unit_id']:0;
            $qty=(float)($line['quantity']??0);
            $gasRateInput=trim((string)($line['gas_rate']??''))===''?null:(float)$line['gas_rate'];
            $targetId=isset($line['customer_cylinder_unit_id'])&&$line['customer_cylinder_unit_id']!==''?(int)$line['customer_cylinder_unit_id']:null;

            if(!$typeId) throw new RuntimeException('Cylinder type is required on gas line '.$n.'.');
            if(!$sourceId) throw new RuntimeException('Source filled cylinder is required on gas line '.$n.'.');
            $type=$this->types->find($typeId);
            if(!$type||!(int)$type['is_active']) throw new RuntimeException('Invalid or inactive cylinder type on gas line '.$n.'.');
            if($qty<=0) throw new RuntimeException('Gas quantity on line '.$n.' must be greater than zero.');
            if($gasRateInput!==null&&$gasRateInput<0) throw new RuntimeException('Gas rate cannot be negative on line '.$n.'.');

            $standardRate=$this->rates->currentKgRate($transactionAt);
            if($standardRate===null) throw new RuntimeException('No effective gas/kg rate exists.');
            $gasRate=$gasRateInput??$standardRate;
            $custom=$gasRateInput!==null&&abs($gasRate-$standardRate)>0.00001;
            $customRate=$customRate||$custom;

            $prepared[]=[
                'line_no'=>$n,'type_id'=>$typeId,'source_id'=>$sourceId,'quantity'=>$qty,'gas_weight_kg'=>$qty,
                'gas_rate'=>$gasRate,'standard_rate'=>$standardRate,'custom_rate_flag'=>$custom?1:0,
                'target_id'=>$targetId,'line_total'=>$qty*$gasRate
            ];
            $subtotal+=$qty*$gasRate;$totalKg+=$qty;
        }

        $discount=max(0,(float)($payload['discount_amount']??0));
        if($discount>$subtotal) throw new RuntimeException('Discount cannot exceed subtotal.');
        $total=$subtotal-$discount;

        $this->db->transBegin();
        try{
            if($customerId){
                $locked=$this->db->query("SELECT * FROM customers WHERE id=? FOR UPDATE",[$customerId])->getRowArray();
                if(!$locked||!(int)$locked['is_active']) throw new RuntimeException('Customer is unavailable.');
            }

            $paymentPlan=$this->prepareSalePaymentPlan($payments,$customerId,$total);

            $lockKeys=array_map(static fn($row)=>['type'=>'filled_cylinder','cylinder_type_id'=>(int)$row['type_id']],$prepared);
            $this->acquireInventoryLocks($locationId,$lockKeys);

            $sources=[];
            $sourceIds=array_values(array_unique(array_map(static fn($row)=>(int)$row['source_id'],$prepared)));
            $placeholders=implode(',',array_fill(0,count($sourceIds),'?'));
            $sourceRows=$this->db->query(
                "SELECT cu.*,ct.code cylinder_code,ct.name cylinder_name,ct.capacity_kg
                 FROM cylinder_units cu
                 JOIN cylinder_types ct ON ct.id=cu.cylinder_type_id
                 WHERE cu.location_id=? AND cu.status='filled' AND cu.id IN ($placeholders)
                 FOR UPDATE",
                array_merge([$locationId],$sourceIds)
            )->getResultArray();
            foreach($sourceRows as $row)$sources[(int)$row['id']]=$row;

            $requestedBySource=[];
            foreach($prepared as $row){
                $source=$sources[$row['source_id']]??null;
                if(!$source) throw new RuntimeException('Selected source cylinder on gas line '.$row['line_no'].' is no longer available.');
                if((int)$source['cylinder_type_id']!==$row['type_id']) throw new RuntimeException('Gas line '.$row['line_no'].' cylinder type does not match the selected source cylinder.');
                $requestedBySource[$row['source_id']]=($requestedBySource[$row['source_id']]??0)+(float)$row['quantity'];
                if($requestedBySource[$row['source_id']]>(float)$source['gas_weight_kg']+0.00001){
                    throw new RuntimeException('Gas line '.$row['line_no'].' exceeds selected cylinder '.$source['unit_code'].' stock. Available: '.number_format((float)$source['gas_weight_kg'],2).' KG.');
                }
            }

            $saleNo='S'.date('YmdHis').'-'.random_int(100,999);
            $this->db->table('sales')->insert([
                'sale_no'=>$saleNo,'location_id'=>$locationId,'customer_id'=>$customerId,'transaction_type'=>'gas_sale',
                'status'=>'posted','transaction_at'=>$transactionAt,'total_kg'=>$totalKg,'subtotal'=>$subtotal,
                'discount_amount'=>$discount,'total_amount'=>$total,'security_deposit_amount'=>0,
                'security_deposit_refund_amount'=>0,'credit_amount'=>$paymentPlan['credit_amount'],
                'custom_rate_flag'=>$customRate?1:0,'notes'=>$notes,'created_by'=>$userId
            ]);
            $saleId=(int)$this->db->insertID();

            foreach($prepared as $row){
                if($row['target_id']){
                    if(!$customerId) throw new RuntimeException('Select a named customer before choosing a customer custody cylinder.');
                    $target=$this->db->query(
                        "SELECT cu.*,ct.capacity_kg
                         FROM cylinder_units cu
                         JOIN cylinder_types ct ON ct.id=cu.cylinder_type_id
                         JOIN cylinder_custody cc ON cc.cylinder_unit_id=cu.id AND cc.status='issued' AND cc.customer_id=?
                         WHERE cu.id=? AND cu.location_id=? AND cu.status='custody'
                         FOR UPDATE",
                        [$customerId,$row['target_id'],$locationId]
                    )->getRowArray();
                    if(!$target) throw new RuntimeException('Selected customer custody cylinder is not available.');
                    if((int)$target['cylinder_type_id']!==$row['type_id']) throw new RuntimeException('Gas refill cylinder type must match the selected customer custody cylinder type.');
                    if((float)$target['gas_weight_kg']+(float)$row['quantity']>(float)$target['capacity_kg']+0.00001) throw new RuntimeException('Gas quantity exceeds the remaining capacity of the selected customer cylinder.');
                }

                $saleItemNotes=($row['target_id']?'Refilled customer custody cylinder':'Gas sale / refill').' | Source cylinder selected by user (ID '.$row['source_id'].')';
                $this->db->table('sale_items')->insert([
                    'sale_id'=>$saleId,'line_no'=>$row['line_no'],'line_type'=>'refill_kg','cylinder_type_id'=>$row['type_id'],
                    'customer_cylinder_unit_id'=>$row['target_id'],'quantity'=>$row['quantity'],'gas_weight_kg'=>$row['gas_weight_kg'],
                    'applied_rate'=>$row['gas_rate'],'standard_rate'=>$row['standard_rate'],'custom_rate_flag'=>$row['custom_rate_flag'],
                    'empty_cylinder_received'=>0,'line_discount'=>0,'line_total'=>$row['line_total'],'notes'=>$saleItemNotes
                ]);
                $saleItemId=(int)$this->db->insertID();

                $source=$sources[$row['source_id']]??null;
                if(!$source) throw new RuntimeException('Selected source cylinder on gas line '.$row['line_no'].' is no longer available.');

                $remaining=(float)$row['quantity'];
                if((float)$source['gas_weight_kg']+0.00001<$remaining){
                    throw new RuntimeException('Selected source cylinder '.$source['unit_code'].' has only '.number_format((float)$source['gas_weight_kg'],2).' KG available.');
                }

                $used=$remaining;
                $before=(float)$source['gas_weight_kg'];
                $after=$before-$used;
                $newStatus=$after<=0.00001?'empty':'filled';

                $this->db->table('cylinder_units')->where('id',(int)$source['id'])->update([
                    'gas_weight_kg'=>max(0,$after),'status'=>$newStatus
                ]);

                $movementRows=[
                    ['type'=>'gas_kg','cylinder_type_id'=>null,'quantity'=>$used,'direction'=>'out','unit_id'=>(int)$source['id'],'notes'=>'Gas sold from selected filled cylinder']
                ];
                if($newStatus==='empty'){
                    $movementRows[]=['type'=>'filled_cylinder','cylinder_type_id'=>$row['type_id'],'quantity'=>1,'direction'=>'out','unit_id'=>(int)$source['id'],'notes'=>'Selected cylinder became empty after gas sale'];
                    $movementRows[]=['type'=>'empty_cylinder','cylinder_type_id'=>$row['type_id'],'quantity'=>1,'direction'=>'in','unit_id'=>(int)$source['id'],'notes'=>'Selected cylinder became empty after gas sale'];
                }
                foreach($movementRows as $m){
                    $this->db->table('inventory_movements')->insert([
                        'location_id'=>$locationId,'inventory_type'=>$m['type'],'cylinder_type_id'=>$m['cylinder_type_id'],
                        'quantity'=>$m['quantity'],'direction'=>$m['direction'],'movement_at'=>$transactionAt,
                        'source_type'=>'sale','source_id'=>$saleId,'source_line_id'=>$saleItemId,
                        'cylinder_unit_id'=>$m['unit_id'],'created_by'=>$userId,'notes'=>$m['notes']
                    ]);
                }

                $sources[(int)$source['id']]=$source;
                $sources[(int)$source['id']]['gas_weight_kg']=max(0,$after);
                $sources[(int)$source['id']]['status']=$newStatus;

                if($row['target_id']) $this->cylinders->addGasToCustody($row['target_id'],$customerId,$row['quantity']);
            }
            if(!$this->db->transStatus()) throw new RuntimeException('Gas sale posting failed. DB=' . json_encode($this->db->error()));
            $this->insertSalePayments($saleId,$paymentPlan['sale_payments'],$transactionAt,$userId);
            $this->postCustomerSettlement($paymentPlan['settlements'],$locationId,$saleId,$customerId,$transactionAt,$userId);
            $cash=$this->paymentCash($paymentPlan['sale_payments']);
            if($cash>0){
                $session=$this->cash->openSessionForLocation($locationId);
                if(!$session) throw new RuntimeException('Open the counter cash session before posting a cash sale.');
                $this->cash->postSaleCash((int)$session['id'],$saleId,$cash,$userId,$transactionAt);
            }

            if(!$this->db->transStatus()) throw new RuntimeException('Gas sale posting failed.');
            $this->db->transCommit();$this->releaseInventoryLocks();
            return ['id'=>$saleId,'sale_no'=>$saleNo,'total'=>$total,'customer_id'=>$customerId,'credit_amount'=>$paymentPlan['credit_amount']];
        }catch(\Throwable $e){
            $this->releaseInventoryLocks();$this->db->transRollback();throw $e;
        }
    }

    protected function postCylinderSaleHeader(array $payload,int $userId,int $locationId,?int $customerId,string $transactionAt,?string $notes,array $lines,array $payments): array
    {
        if(!$lines) throw new RuntimeException('At least one cylinder sale line is required.');
        if(!$payments) throw new RuntimeException('At least one payment is required.');
        $prepared=[];$subtotal=0;$totalKg=0;$customRate=false;$inventory=[];
        $reservedFilledIds=[];$reservedEmptyIds=[];
        foreach($lines as $i=>$line){
            $n=$i+1;$typeId=isset($line['cylinder_type_id'])&&$line['cylinder_type_id']!==''?(int)$line['cylinder_type_id']:0;$status=(string)($line['cylinder_status']??'empty');$qty=(float)($line['quantity']??0);
            $gasRateInput=trim((string)($line['gas_rate']??''))===''?null:(float)$line['gas_rate'];$cylRateInput=trim((string)($line['cylinder_rate']??''))===''?null:(float)$line['cylinder_rate'];
            if(!$typeId||!in_array($status,['filled','empty'],true))throw new RuntimeException('Cylinder type and status are required on line '.$n.'.');
            if($qty<=0||floor($qty)!==$qty)throw new RuntimeException('Cylinder quantity on line '.$n.' must be a whole number.');
            $type=$this->types->find($typeId);if(!$type||!(int)$type['is_active'])throw new RuntimeException('Invalid or inactive cylinder type on line '.$n.'.');
            $cylRate=$this->rates->currentCylinderRate($typeId,$transactionAt);if($cylRate===null)throw new RuntimeException('No effective cylinder price exists for '.$type['name'].'.');$cylRate=$cylRateInput??$cylRate;
            $stdGas=null;$gasKg=0;$gasRate=0;
            if($status==='filled'){
                $stdGas=$this->rates->currentKgRate($transactionAt);if($stdGas===null)throw new RuntimeException('No effective gas/kg rate exists.');
                $gasRate=$gasRateInput??$stdGas;
                $units=$this->cylinders->availableForDisplay($locationId,$typeId,'filled');
                $units=array_values(array_filter($units,static fn(array $u): bool => !isset($reservedFilledIds[(int)$u['id']]) ));
                if(count($units)<$qty)throw new RuntimeException('Insufficient filled cylinders of '.$type['name'].'.');
                $selected=array_slice($units,0,(int)$qty);$gasKg=array_sum(array_map(static fn($u)=>(float)$u['gas_weight_kg'],$selected));if($gasKg<=0)throw new RuntimeException('Selected filled cylinders contain no gas.');
                foreach($selected as $u)$reservedFilledIds[(int)$u['id']]=true;
                foreach($selected as $u)$inventory[]=['line_no'=>$n,'type'=>'gas_kg','cylinder_type_id'=>null,'quantity'=>(float)$u['gas_weight_kg'],'direction'=>'out','unit_id'=>(int)$u['id']];
                foreach($selected as $u)$inventory[]=['line_no'=>$n,'type'=>'filled_cylinder','cylinder_type_id'=>$typeId,'quantity'=>1,'direction'=>'out','unit_id'=>(int)$u['id']];
            }else{
                $units=$this->cylinders->availableForDisplay($locationId,$typeId,'empty');
                $units=array_values(array_filter($units,static fn(array $u): bool => !isset($reservedEmptyIds[(int)$u['id']]) ));
                if(count($units)<$qty)throw new RuntimeException('Insufficient empty cylinders of '.$type['name'].'.');
                foreach(array_slice($units,0,(int)$qty) as $u){
                    $reservedEmptyIds[(int)$u['id']]=true;
                    $inventory[]=['line_no'=>$n,'type'=>'empty_cylinder','cylinder_type_id'=>$typeId,'quantity'=>1,'direction'=>'out','unit_id'=>(int)$u['id']];
                }
            }
            $custom=(($gasRateInput!==null&&$stdGas!==null&&abs($gasRate-$stdGas)>0.00001)||($cylRateInput!==null&&abs($cylRate-((float)$this->rates->currentCylinderRate($typeId,$transactionAt)))>0.00001));$customRate=$customRate||$custom;
            $lineTotal=$gasKg*$gasRate+$qty*$cylRate;$subtotal+=$lineTotal;$totalKg+=$gasKg;
            $prepared[]=['line_no'=>$n,'type_id'=>$typeId,'status'=>$status,'quantity'=>$qty,'gas_kg'=>$gasKg,'gas_rate'=>$gasRate,'cyl_rate'=>$cylRate,'std_gas'=>$stdGas,'line_total'=>$lineTotal];
        }
        $discount=max(0,(float)($payload['discount_amount']??0));if($discount>$subtotal)throw new RuntimeException('Discount cannot exceed subtotal.');
        $total=$subtotal-$discount;
        $this->db->transBegin();
        try{
            if($customerId){$locked=$this->db->query("SELECT * FROM customers WHERE id=? FOR UPDATE",[$customerId])->getRowArray();if(!$locked||!(int)$locked['is_active'])throw new RuntimeException('Customer is unavailable.');$customer=$locked;}
            $paymentPlan=$this->prepareSalePaymentPlan($payments,$customerId,$total);
            $saleNo='S'.date('YmdHis').'-'.random_int(100,999);
            $this->db->table('sales')->insert(['sale_no'=>$saleNo,'location_id'=>$locationId,'customer_id'=>$customerId,'transaction_type'=>'cylinder_sale','status'=>'posted','transaction_at'=>$transactionAt,'total_kg'=>$totalKg,'subtotal'=>$subtotal,'discount_amount'=>$discount,'total_amount'=>$total,'security_deposit_amount'=>0,'security_deposit_refund_amount'=>0,'credit_amount'=>$paymentPlan['credit_amount'],'custom_rate_flag'=>$customRate?1:0,'notes'=>$notes,'created_by'=>$userId]);
            $saleId=(int)$this->db->insertID();$this->acquireInventoryLocks($locationId,array_map(static fn($row)=>['type'=>$row['type'],'cylinder_type_id'=>$row['cylinder_type_id']],$inventory));
            foreach($prepared as $row){
                $this->db->table('sale_items')->insert(['sale_id'=>$saleId,'line_no'=>$row['line_no'],'line_type'=>$row['status']==='filled'?'filled_cylinder':'empty_cylinder','cylinder_type_id'=>$row['type_id'],'customer_cylinder_unit_id'=>null,'quantity'=>$row['quantity'],'gas_weight_kg'=>$row['gas_kg'],'applied_rate'=>$row['status']==='filled'?(($row['gas_kg']/max($row['quantity'],1))*$row['gas_rate']+$row['cyl_rate']):$row['cyl_rate'],'standard_rate'=>$row['status']==='filled'?(($row['gas_kg']/max($row['quantity'],1))*$row['std_gas']+$row['cyl_rate']):$row['cyl_rate'],'custom_rate_flag'=>0,'empty_cylinder_received'=>0,'line_discount'=>0,'line_total'=>$row['line_total'],'notes'=>'Cylinder sale']);
            }
            $lineIds=[];foreach($this->db->table('sale_items')->select('id,line_no')->where('sale_id',$saleId)->get()->getResultArray() as $row)$lineIds[(int)$row['line_no']]=(int)$row['id'];
            foreach($inventory as $m){$this->db->table('inventory_movements')->insert(['location_id'=>$locationId,'inventory_type'=>$m['type'],'cylinder_type_id'=>$m['cylinder_type_id'],'quantity'=>$m['quantity'],'direction'=>'out','movement_at'=>$transactionAt,'source_type'=>'sale','source_id'=>$saleId,'source_line_id'=>$lineIds[(int)$m['line_no']]??null,'cylinder_unit_id'=>$m['unit_id'],'created_by'=>$userId,'notes'=>'Cylinder sale']);if(in_array($m['type'],['filled_cylinder','empty_cylinder'],true))$this->cylinders->markSold((int)$m['unit_id']);}
            $this->insertSalePayments($saleId,$paymentPlan['sale_payments'],$transactionAt,$userId);$this->postCustomerSettlement($paymentPlan['settlements'],$locationId,$saleId,$customerId,$transactionAt,$userId);$cash=$this->paymentCash($paymentPlan['sale_payments']);if($cash>0){$s=$this->cash->openSessionForLocation($locationId);if(!$s)throw new RuntimeException('Open the counter cash session before posting a cash sale.');$this->cash->postSaleCash((int)$s['id'],$saleId,$cash,$userId,$transactionAt);}
            if(!$this->db->transStatus())throw new RuntimeException('Cylinder sale posting failed.');
            $this->db->transCommit();$this->releaseInventoryLocks();return ['id'=>$saleId,'sale_no'=>$saleNo,'total'=>$total,'customer_id'=>$customerId,'credit_amount'=>$this->paymentCredit($payments)];
        }catch(\Throwable $e){$this->releaseInventoryLocks();$this->db->transRollback();throw $e;}
    }

    protected function postSecurityDepositHeader(array $payload,int $userId,int $locationId,int $customerId,string $transactionAt,?string $notes,array $payments,array $unitIds,float $deposit): array
    {
        if(!$unitIds)throw new RuntimeException('Select at least one cylinder to place on customer custody.');
        if($deposit<=0)throw new RuntimeException('Security Deposit Amount must be greater than zero.');
        if(!$payments)throw new RuntimeException('Add a payment for the security deposit.');
        $this->validateHeaderPayments($payments,$customerId,$deposit,false);
        $rows=$this->db->table('cylinder_units cu')->select('cu.*,ct.name cylinder_name')->join('cylinder_types ct','ct.id=cu.cylinder_type_id')->where('cu.location_id',$locationId)->whereIn('cu.id',$unitIds)->whereIn('cu.status',['filled','empty'])->get()->getResultArray();
        if(count($rows)!==count($unitIds))throw new RuntimeException('One or more selected cylinders are no longer available for custody.');
        $this->db->transBegin();
        try{
            $this->acquireInventoryLocks($locationId,array_map(static fn($row)=>['type'=>$row['status']==='filled'?'filled_cylinder':'empty_cylinder','cylinder_type_id'=>(int)$row['cylinder_type_id']],$rows));
            $saleNo='S'.date('YmdHis').'-'.random_int(100,999);
            $this->db->table('sales')->insert(['sale_no'=>$saleNo,'location_id'=>$locationId,'customer_id'=>$customerId,'transaction_type'=>'security_deposit','status'=>'posted','transaction_at'=>$transactionAt,'total_kg'=>0,'subtotal'=>0,'discount_amount'=>0,'total_amount'=>0,'security_deposit_amount'=>$deposit,'security_deposit_refund_amount'=>0,'credit_amount'=>0,'custom_rate_flag'=>0,'notes'=>$notes,'created_by'=>$userId]);
            $saleId=(int)$this->db->insertID();
            $perUnit=round($deposit/count($rows),2);$assigned=0;$lineNo=0;
            foreach($rows as $row){
                $lineNo++;
                $this->cylinders->moveToCustody((int)$row['id'],$customerId,$saleId,$userId);
                $custody=$this->db->table('cylinder_custody')->where(['cylinder_unit_id'=>$row['id'],'status'=>'issued'])->orderBy('id','DESC')->get()->getRowArray();
                if(!$custody)throw new RuntimeException('Custody record could not be created.');
                $share=$lineNo===count($rows)?round($deposit-$assigned,2):$perUnit;$assigned+= $share;
                $this->db->table('cylinder_custody')->where('id',$custody['id'])->update(['deposit_amount'=>$share]);
                $this->db->table('customer_security_deposits')->insert(['location_id'=>$locationId,'customer_id'=>$customerId,'entry_type'=>'hold','amount'=>$share,'sale_id'=>$saleId,'custody_id'=>$custody['id'],'transaction_at'=>$transactionAt,'created_by'=>$userId,'notes'=>'Security deposit held for '.$row['unit_code']]);
                $this->db->table('sale_items')->insert(['sale_id'=>$saleId,'line_no'=>$lineNo,'line_type'=>$row['gas_weight_kg']>0?'filled_cylinder':'empty_cylinder','cylinder_type_id'=>$row['cylinder_type_id'],'customer_cylinder_unit_id'=>$row['id'],'quantity'=>1,'gas_weight_kg'=>$row['gas_weight_kg'],'applied_rate'=>0,'standard_rate'=>null,'custom_rate_flag'=>0,'empty_cylinder_received'=>0,'line_discount'=>0,'line_total'=>0,'notes'=>'Custody issued: '.$row['unit_code']]);
                $this->db->table('inventory_movements')->insert(['location_id'=>$locationId,'inventory_type'=>$row['status']==='filled'?'filled_cylinder':'empty_cylinder','cylinder_type_id'=>$row['cylinder_type_id'],'quantity'=>1,'direction'=>'out','movement_at'=>$transactionAt,'source_type'=>'security_deposit','source_id'=>$saleId,'source_line_id'=>null,'cylinder_unit_id'=>$row['id'],'created_by'=>$userId,'notes'=>'Cylinder placed on customer custody']);
                if($row['status']==='filled' && (float)$row['gas_weight_kg']>0) $this->db->table('inventory_movements')->insert(['location_id'=>$locationId,'inventory_type'=>'gas_kg','cylinder_type_id'=>null,'quantity'=>$row['gas_weight_kg'],'direction'=>'out','movement_at'=>$transactionAt,'source_type'=>'security_deposit','source_id'=>$saleId,'source_line_id'=>null,'cylinder_unit_id'=>$row['id'],'created_by'=>$userId,'notes'=>'Gas carried out with custody cylinder']);
            }
            $this->insertSalePayments($saleId,$payments,$transactionAt,$userId);
            $cash=$this->paymentCash($payments);if($cash>0){$s=$this->cash->openSessionForLocation($locationId);if(!$s)throw new RuntimeException('Open the counter cash session before receiving a security deposit.');$this->cash->postGeneric((int)$s['id'],'security_deposit','in',$cash,'sale',$saleId,$userId,'Security deposit received',$transactionAt);}
            if(!$this->db->transStatus())throw new RuntimeException('Security deposit posting failed.');
            $this->db->transCommit();$this->releaseInventoryLocks();return ['id'=>$saleId,'sale_no'=>$saleNo,'total'=>$deposit,'customer_id'=>$customerId,'credit_amount'=>0];
        }catch(\Throwable $e){$this->releaseInventoryLocks();$this->db->transRollback();throw $e;}
    }

    protected function postCylinderReturnHeader(array $payload,int $userId,int $locationId,int $customerId,string $transactionAt,?string $notes,array $returnUnitIds): array
    {
        if(!$returnUnitIds)throw new RuntimeException('Select at least one customer custody cylinder to return.');
        $rows=$this->db->table('cylinder_custody cc')->select('cc.*,cu.unit_code,cu.gas_weight_kg,cu.cylinder_type_id,ct.name cylinder_name')->join('cylinder_units cu','cu.id=cc.cylinder_unit_id')->join('cylinder_types ct','ct.id=cu.cylinder_type_id')->where(['cc.location_id'=>$locationId,'cc.customer_id'=>$customerId,'cc.status'=>'issued'])->whereIn('cc.cylinder_unit_id',$returnUnitIds)->get()->getResultArray();
        if(count($rows)!==count($returnUnitIds))throw new RuntimeException('One or more selected custody cylinders are no longer active for this customer.');
        $refund=0;foreach($rows as $row){if((float)$row['gas_weight_kg']>0.00001)throw new RuntimeException('Cylinder '.$row['unit_code'].' must be empty before return/refund.');$refund+=(float)$row['deposit_amount'];}
        if($refund<=0)throw new RuntimeException('No refundable security deposit is linked to the selected cylinders.');
        $this->db->transBegin();
        try{
            $saleNo='S'.date('YmdHis').'-'.random_int(100,999);
            $this->db->table('sales')->insert(['sale_no'=>$saleNo,'location_id'=>$locationId,'customer_id'=>$customerId,'transaction_type'=>'cylinder_return','status'=>'posted','transaction_at'=>$transactionAt,'total_kg'=>0,'subtotal'=>0,'discount_amount'=>0,'total_amount'=>0,'security_deposit_amount'=>0,'security_deposit_refund_amount'=>$refund,'credit_amount'=>0,'custom_rate_flag'=>0,'notes'=>$notes,'created_by'=>$userId]);
            $saleId=(int)$this->db->insertID();$lineNo=0;
            foreach($rows as $row){
                $lineNo++;$this->cylinders->returnFromCustody((int)$row['id'],$customerId,$saleId,$userId);
                $this->db->table('customer_security_deposits')->insert(['location_id'=>$locationId,'customer_id'=>$customerId,'entry_type'=>'refund','amount'=>$row['deposit_amount'],'sale_id'=>$saleId,'custody_id'=>$row['id'],'transaction_at'=>$transactionAt,'created_by'=>$userId,'notes'=>'Security deposit refund for '.$row['unit_code']]);
                $this->db->table('sale_items')->insert(['sale_id'=>$saleId,'line_no'=>$lineNo,'line_type'=>'empty_cylinder','cylinder_type_id'=>$row['cylinder_type_id'],'customer_cylinder_unit_id'=>$row['cylinder_unit_id'],'quantity'=>1,'gas_weight_kg'=>0,'applied_rate'=>0,'standard_rate'=>null,'custom_rate_flag'=>0,'empty_cylinder_received'=>1,'line_discount'=>0,'line_total'=>0,'notes'=>'Cylinder returned: '.$row['unit_code'].' | Deposit refund Rs. '.number_format((float)$row['deposit_amount'],2)]);
                $this->db->table('inventory_movements')->insert(['location_id'=>$locationId,'inventory_type'=>'empty_cylinder','cylinder_type_id'=>$row['cylinder_type_id'],'quantity'=>1,'direction'=>'in','movement_at'=>$transactionAt,'source_type'=>'cylinder_return','source_id'=>$saleId,'source_line_id'=>null,'cylinder_unit_id'=>$row['cylinder_unit_id'],'created_by'=>$userId,'notes'=>'Cylinder returned from customer custody']);
            }
            $s=$this->cash->openSessionForLocation($locationId);if(!$s)throw new RuntimeException('Open the counter cash session before refunding a security deposit.');
            $this->cash->postGeneric((int)$s['id'],'security_deposit_refund','out',$refund,'sale',$saleId,$userId,'Security deposit refund',$transactionAt);
            if(!$this->db->transStatus())throw new RuntimeException('Cylinder return failed.');
            $this->db->transCommit();return ['id'=>$saleId,'sale_no'=>$saleNo,'total'=>0,'customer_id'=>$customerId,'credit_amount'=>0];
        }catch(\Throwable $e){$this->db->transRollback();throw $e;}
    }

    protected function prepareSalePaymentPlan(array $payments,?int $customerId,float $saleTotal): array
    {
        if(!$payments)throw new RuntimeException('At least one payment is required.');
        $paymentTotal=0;
        foreach($payments as $p){
            $mode=(string)($p['payment_mode']??'');$amount=(float)($p['amount']??0);
            if(!in_array($mode,['cash','cheque','online','credit'],true)||$amount<=0)throw new RuntimeException('Invalid payment.');
            if(!$customerId&&$mode!=='cash')throw new RuntimeException('Walk-in transactions are cash only.');
            $paymentTotal+=$amount;
        }
        $previousOs=$customerId?max(0,$this->customerBalance($customerId)):0;
        $maxReceivable=$saleTotal+$previousOs;
        if($customerId===null && $paymentTotal>$saleTotal+0.01)throw new RuntimeException('Payment cannot exceed the walk-in sale amount.');
        if($paymentTotal>$maxReceivable+0.01)throw new RuntimeException('Payment cannot exceed the customer net receivable of Rs. '.number_format($maxReceivable,2).'.');

        $salePayments=[];$settlements=[];$remainingSale=$saleTotal;$remainingOs=$previousOs;
        foreach($payments as $p){
            $amount=(float)$p['amount'];$mode=(string)$p['payment_mode'];
            if($remainingOs>0.00001 && $amount>0){
                if($mode==='credit')throw new RuntimeException('Credit cannot be used to settle a previous customer OS balance.');
                $toOs=min($amount,$remainingOs);
                $settlements[]=['payment_mode'=>$mode,'amount'=>$toOs,'reference_no'=>$p['reference_no']??null];
                $remainingOs-=$toOs;$amount-=$toOs;
            }
            if($amount>0){
                $toSale=min($amount,$remainingSale);
                $salePayments[]=['payment_mode'=>$mode,'amount'=>$toSale,'reference_no'=>$p['reference_no']??null];
                $remainingSale-=$toSale;$amount-=$toSale;
            }
            if($amount>0.00001)throw new RuntimeException('Payment exceeds the customer net receivable.');
        }
        $creditAmount=max(0,$remainingSale)+array_sum(array_map(static fn($p)=>(string)$p['payment_mode']==='credit'?(float)$p['amount']:0,$salePayments));
        $newOs=max(0,$remainingOs+$creditAmount);
        $settings=(new ShopSettingsModel())->forLocation($this->currentLocationId ?? 0);
        $validationMode=(string)($settings['credit_limit_validation_mode']??'none');
        if($customerId!==null && $validationMode!=='none'){
            $creditLimit=$validationMode==='shop'?(float)($settings['shop_credit_limit']??0):(float)(($this->customers->find($customerId)['credit_limit']??0));
            if($newOs>$creditLimit+0.01){
                $available=max(0,$creditLimit-$previousOs);
                $label=$validationMode==='shop'?'shop':'customer';
                throw new RuntimeException(ucfirst($label).' credit limit exceeded. Available additional credit is Rs. '.number_format($available,2).'.');
            }
        }
        return ['sale_payments'=>$salePayments,'settlements'=>$settlements,'credit_amount'=>$creditAmount,'previous_os'=>$previousOs,'payment_total'=>$paymentTotal,'net_receivable'=>$maxReceivable,'remaining_os'=>$newOs];
    }

    protected function postCustomerSettlement(array $settlements,int $locationId,int $saleId,?int $customerId,string $at,int $userId): void
    {
        if(!$settlements || $customerId===null)return;
        foreach($settlements as $p){
            $amount=(float)$p['amount'];$mode=(string)$p['payment_mode'];
            $no='R'.date('YmdHis').'-'.random_int(100,999);
            $this->db->table('customer_receipts')->insert([
                'receipt_no'=>$no,'location_id'=>$locationId,'customer_id'=>$customerId,'status'=>'posted',
                'amount'=>$amount,'payment_mode'=>$mode,'receipt_at'=>$at,
                'reference_no'=>trim((string)($p['reference_no']??''))?:null,
                'notes'=>'OS settlement collected with sale '.$saleId,'created_by'=>$userId
            ]);
            if($mode==='cash'){
                $s=$this->cash->openSessionForLocation($locationId);
                if(!$s)throw new RuntimeException('Open the counter cash session before receiving an OS settlement.');
                $this->cash->postGeneric((int)$s['id'],'customer_receipt','in',$amount,'customer_receipt',(int)$this->db->insertID(),$userId,'Customer OS settlement',$at);
            }
        }
    }

    protected function validateHeaderPayments(array $payments,?int $customerId,float $expected,bool $allowCredit=true): void
    {
        if(!$payments)throw new RuntimeException('At least one payment is required.');
        $total=0;
        foreach($payments as $p){$mode=(string)($p['payment_mode']??'');$amount=(float)($p['amount']??0);if(!in_array($mode,['cash','cheque','online','credit'],true)||$amount<=0)throw new RuntimeException('Invalid payment.');if(!$customerId&&$mode!=='cash')throw new RuntimeException('Walk-in transactions are cash only.');if(!$allowCredit&&$mode==='credit')throw new RuntimeException('Credit payment is not allowed for this transaction.');$total+=$amount;}
        if(abs($total-$expected)>0.01)throw new RuntimeException('Payment total must equal the net amount payable.');
    }

    protected function insertSalePayments(int $saleId,array $payments,string $at,int $userId): void
    {
        foreach($payments as $p)$this->db->table('sale_payments')->insert(['sale_id'=>$saleId,'payment_mode'=>$p['payment_mode'],'amount'=>(float)$p['amount'],'reference_no'=>trim((string)($p['reference_no']??''))?:null,'payment_at'=>$at,'received_by'=>$userId]);
    }

    protected function paymentCash(array $payments): float {return array_sum(array_map(static fn($p)=>(string)($p['payment_mode'])==='cash'?(float)$p['amount']:0,$payments));}
    protected function paymentCredit(array $payments): float {return array_sum(array_map(static fn($p)=>(string)($p['payment_mode'])==='credit'?(float)$p['amount']:0,$payments));}
    protected function paymentCustom(array $rows): int {return array_reduce($rows,static fn($c,$r)=>$c||(int)($r['custom_rate_flag']??0)?1:0,0);}

    public function void(int $saleId,int $userId,int $locationId,string $reason): string
    {
        $reason=trim($reason);
        if($reason==='') throw new RuntimeException('Void reason is required.');
        $this->db->transBegin();
        try{
            $sale=$this->db->query("SELECT * FROM sales WHERE id=? AND location_id=? FOR UPDATE",[$saleId,$locationId])->getRowArray();
            if(!$sale) throw new RuntimeException('Sale not found.');
            if($sale['status']!=='posted') throw new RuntimeException('Only posted sales can be voided.');
            if(in_array($sale['transaction_type'],['security_deposit','cylinder_return'],true)) throw new RuntimeException('Security Deposit and Cylinder Return transactions cannot be voided. Use the custody workflow so the liability and physical cylinder remain consistent.');
            if($sale['transaction_type']==='gas_sale'){
                $custodyLine=(int)$this->db->table('sale_items')->where('sale_id',$saleId)->where('customer_cylinder_unit_id IS NOT NULL',null,false)->countAllResults();
                if($custodyLine>0) throw new RuntimeException('Gas sales that refill a customer custody cylinder cannot be voided after posting.');
            }

            $movements=$this->db->table('inventory_movements')->where('source_type','sale')->where('source_id',$saleId)->get()->getResultArray();
            $this->acquireInventoryLocks($locationId,$movements);
            foreach($movements as $m){
                $reverse=$m['direction']==='in'?'out':'in';
                $this->db->table('inventory_movements')->insert(['location_id'=>$m['location_id'],'inventory_type'=>$m['inventory_type'],'cylinder_type_id'=>$m['cylinder_type_id'],'quantity'=>$m['quantity'],'direction'=>$reverse,'movement_at'=>date('Y-m-d H:i:s'),'source_type'=>'sale_void','source_id'=>$saleId,'source_line_id'=>$m['source_line_id']??null,'cylinder_unit_id'=>$m['cylinder_unit_id']??null,'created_by'=>$userId,'notes'=>'Reversal of sale '.$sale['sale_no']]);
                $unitId=(int)($m['cylinder_unit_id']??0);
                if($unitId && $m['inventory_type']==='gas_kg' && $m['direction']==='out'){
                    $this->cylinders->restoreGas($unitId,(float)$m['quantity']);
                }elseif($unitId && $m['inventory_type']==='filled_cylinder' && $m['direction']==='out'){
                    $gasRow=$this->db->table('inventory_movements')->where('source_type','sale')->where('source_id',$saleId)->where('inventory_type','gas_kg')->where('direction','out')->where('cylinder_unit_id',$unitId)->orderBy('id')->get()->getRowArray();
                    $this->cylinders->restoreFilled($unitId,(float)($gasRow['quantity']??0));
                }elseif($unitId && $m['inventory_type']==='empty_cylinder' && $m['direction']==='out') $this->cylinders->restoreEmpty($unitId);
                elseif($unitId && $m['inventory_type']==='empty_cylinder' && $m['direction']==='in') {
                    $converted=(int)$this->db->table('inventory_movements')->where('source_type','sale')->where('source_id',$saleId)->where('inventory_type','filled_cylinder')->where('direction','out')->where('cylinder_unit_id',$unitId)->countAllResults()>0;
                    if(!$converted) $this->cylinders->markSold($unitId);
                }
            }
            $cashRows=$this->db->table('cash_transactions')->where('reference_type','sale')->where('reference_id',$saleId)->where('transaction_type','sale_cash')->where('direction','in')->get()->getResultArray();
            $existingReversal=(int)$this->db->table('cash_transactions')->where('reference_type','sale_void')->where('reference_id',$saleId)->where('transaction_type','sale_cash')->where('direction','out')->countAllResults();
            if($existingReversal>0) throw new RuntimeException('Cash reversal for this sale already exists.');
            foreach($cashRows as $cash){
                $this->db->table('cash_transactions')->insert([
                    'cash_session_id'=>$cash['cash_session_id'],'transaction_type'=>'sale_cash','direction'=>'out',
                    'amount'=>$cash['amount'],'transaction_at'=>date('Y-m-d H:i:s'),'reference_type'=>'sale_void',
                    'reference_id'=>$saleId,'created_by'=>$userId,'notes'=>'Cash reversal of sale '.$sale['sale_no']
                ]);
            }

            $settlementReceipts=$this->db->table('customer_receipts')
                ->where(['location_id'=>$locationId,'status'=>'posted'])
                ->where('customer_id', $sale['customer_id'])
                ->where('notes','OS settlement collected with sale '.$saleId)
                ->get()->getResultArray();
            foreach($settlementReceipts as $receipt){
                $receiptId=(int)$receipt['id'];
                $this->db->table('customer_receipts')->where('id',$receiptId)->update(['status'=>'voided']);

                $receiptCashRows=$this->db->table('cash_transactions')
                    ->where([
                        'reference_type'=>'customer_receipt',
                        'reference_id'=>$receiptId,
                        'transaction_type'=>'customer_receipt',
                        'direction'=>'in'
                    ])->get()->getResultArray();

                foreach($receiptCashRows as $receiptCash){
                    $alreadyReversed=(int)$this->db->table('cash_transactions')
                        ->where([
                            'reference_type'=>'sale_void',
                            'reference_id'=>$saleId,
                            'transaction_type'=>'customer_receipt',
                            'direction'=>'out',
                            'cash_session_id'=>$receiptCash['cash_session_id']
                        ])->where('notes','Reversal of customer receipt '.$receipt['receipt_no'])->countAllResults();
                    if($alreadyReversed===0){
                        $this->db->table('cash_transactions')->insert([
                            'cash_session_id'=>$receiptCash['cash_session_id'],
                            'transaction_type'=>'customer_receipt',
                            'direction'=>'out',
                            'amount'=>$receiptCash['amount'],
                            'transaction_at'=>date('Y-m-d H:i:s'),
                            'reference_type'=>'sale_void',
                            'reference_id'=>$saleId,
                            'created_by'=>$userId,
                            'notes'=>'Reversal of customer receipt '.$receipt['receipt_no'].' from voided sale '.$sale['sale_no']
                        ]);
                    }
                }
            }

            $this->db->table('sales')->where('id',$saleId)->update(['status'=>'voided','voided_by'=>$userId,'voided_at'=>date('Y-m-d H:i:s'),'void_reason'=>$reason]);
            if(!$this->db->transStatus()) throw new RuntimeException('Sale void failed.');
            $this->db->transCommit();
            $this->releaseInventoryLocks();
            return $sale['sale_no'];
        }catch(\Throwable $e){
            $this->releaseInventoryLocks();
            $this->db->transRollback();
            throw $e;
        }
    }

    public function customerBalance(int $customerId): float
    {
        $s=$this->db->table('sales')->selectSum('credit_amount','credit')->where('customer_id',$customerId)->where('status','posted')->where('location_id',$this->currentLocationId)->get()->getRowArray();
        $r=$this->db->table('customer_receipts')->selectSum('amount','paid')->where('customer_id',$customerId)->where('status','posted')->where('location_id',$this->currentLocationId)->get()->getRowArray();
        $c=$this->customers->find($customerId);
        return (float)($c['opening_balance']??0)+(float)($s['credit']??0)-(float)($r['paid']??0);
    }

    protected function acquireInventoryLocks(int $locationId,array $movements): void
    {
        $keys=[];
        foreach($movements as $m){
            $type=(string)($m['type']??$m['inventory_type']??'');
            $typeId=$m['cylinder_type_id']??null;
            if($type==='') throw new RuntimeException('Invalid inventory lock key.');
            $keys[$type.'|'.($typeId===null?'null':(string)$typeId)]=true;
        }
        $keys=array_keys($keys);
        sort($keys,SORT_STRING);
        foreach($keys as $key){
            $lockKey='lpg_inv_'.$locationId.'_'.substr(hash('sha256',$key),0,48);
            $result=$this->db->query("SELECT GET_LOCK(?,5) AS locked",[$lockKey])->getRowArray();
            if((int)($result['locked']??0)!==1) throw new RuntimeException('Inventory is busy; please retry the transaction.');
            $this->inventoryLocks[]=$lockKey;
        }
    }

    protected function releaseInventoryLocks(): void
    {
        if(!$this->inventoryLocks) return;
        foreach(array_reverse($this->inventoryLocks) as $lockKey){
            $this->db->query("SELECT RELEASE_LOCK(?) AS released",[$lockKey]);
        }
        $this->inventoryLocks=[];
    }

    protected function assertStock(int $locationId,string $type,?int $typeId,float $qty,string $direction,string $at): void
 {
  if($direction!=='out') return;

  if($type==='gas_kg'){
   $row=$this->db->table('cylinder_units')
    ->selectSum('gas_weight_kg','qty')
    ->where(['location_id'=>$locationId,'status'=>'filled'])
    ->get()->getRowArray();

   $stock=(float)($row['qty']??0);
   if($stock+0.00001<$qty){
    throw new RuntimeException(
     'Insufficient gas stock. Available gas is derived from filled physical cylinders: ' .
     number_format($stock,3) . ' KG.'
    );
   }
   return;
  }

  $status=$type==='filled_cylinder'?'filled':($type==='empty_cylinder'?'empty':null);
  if($status===null) throw new RuntimeException('Invalid inventory type.');

  $q=$this->db->table('cylinder_units')->where([
   'location_id'=>$locationId,
   'cylinder_type_id'=>$typeId,
   'status'=>$status
  ])->countAllResults();

  if((float)$q+0.00001<$qty){
   throw new RuntimeException('Insufficient '.$type.' stock.');
  }
 }
}
