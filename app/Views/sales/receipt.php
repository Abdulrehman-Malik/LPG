<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Receipt <?= esc($sale['sale_no']) ?></title>
<style>
*{box-sizing:border-box}
html,body{margin:0;padding:0}
body{
  width:80mm;
  max-width:80mm;
  margin:0 auto;
  padding:4mm 3mm;
  font-family:Arial,Helvetica,sans-serif;
  font-size:10px;
  line-height:1.25;
  color:#000;
  background:#fff;
}
.header{text-align:center}
.shop-name{font-size:15px;font-weight:700;margin-bottom:2px}
.receipt-title{font-size:11px;font-weight:700;margin-top:2px}
.small{font-size:9px}
.rule{border-top:1px dashed #000;margin:5px 0}
.row{display:flex;justify-content:space-between;gap:6px}
.row span:first-child{flex:1}
.row span:last-child{text-align:right}
table{width:100%;border-collapse:collapse;table-layout:fixed}
th,td{padding:2px 0;vertical-align:top;border-bottom:1px dotted #888;word-wrap:break-word}
th{font-weight:700}
.item{width:34%}
.qty{width:11%;text-align:right}
.gas{width:15%;text-align:right}
.rate{width:17%;text-align:right}
.amount{width:23%;text-align:right}
.total{font-size:13px;font-weight:700;margin-top:5px}
.footer{text-align:center;margin-top:6px;white-space:pre-line}
.actions{text-align:center;margin-top:10px}
button{font-size:12px;padding:6px 12px;margin:0 3px}
@media print{
  @page{size:80mm auto;margin:0}
  html,body{width:80mm;max-width:80mm}
  body{padding:3mm 2.5mm}
  .actions{display:none}
}
</style>
</head>
<body>
<div class="header">
  <div class="shop-name"><?= esc($location['name']??'Perfect LPG') ?></div>
  <?php if(!empty($shopSettings['show_address_on_receipt']) && !empty($location['address'])): ?>
    <div class="small"><?= esc($location['address']) ?><?= !empty($location['city']) ? ', '.esc($location['city']) : '' ?></div>
  <?php endif; ?>
  <div class="receipt-title"><?= esc($shopSettings['receipt_title']??'SALE RECEIPT') ?></div>
</div>

<div class="rule"></div>
<div class="row"><span>No.</span><span><?= esc($sale['sale_no']) ?></span></div>
<div class="row"><span>Date</span><span><?= esc($sale['transaction_at']) ?></span></div>
<div class="row"><span>Customer</span><span><?= esc($sale['customer_name']??'Walk-in / Cash') ?></span></div>
<div class="rule"></div>

<table>
  <thead>
    <tr>
      <th class="item">Item</th>
      <th class="qty">Qty</th>
      <th class="gas">Gas</th>
      <th class="rate">Rate</th>
      <th class="amount">Amount</th>
    </tr>
  </thead>
  <tbody>
  <?php foreach($items as $i): ?>
    <tr>
      <td class="item">
        <?= esc(match($i['line_type']){
          'refill_kg'=>'Gas Only / Refill',
          'filled_cylinder'=>'Filled Cylinder',
          'empty_cylinder'=>'Empty Cylinder',
          default=>$i['line_type']
        }) ?>
        <?php if(!empty($i['notes'])): ?><br><span class="small"><?= esc($i['notes']) ?></span><?php endif; ?>
      </td>
      <td class="qty"><?= number_format((float)$i['quantity'],2) ?></td>
      <td class="gas"><?= number_format((float)$i['gas_weight_kg'],2) ?></td>
      <td class="rate"><?= number_format((float)$i['applied_rate'],2) ?></td>
      <td class="amount"><?= number_format((float)$i['line_total'],2) ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>

<div class="rule"></div>
<div class="row"><span>Subtotal</span><span><?= number_format((float)$sale['subtotal'],2) ?></span></div>
<div class="row"><span>Discount</span><span><?= number_format((float)$sale['discount_amount'],2) ?></span></div>
<div class="row total"><span>Total</span><span>Rs. <?= number_format((float)$sale['total_amount'],2) ?></span></div>

<?php foreach($payments as $p): ?>
  <div class="row"><span><?= esc(ucfirst($p['payment_mode'])) ?></span><span>Rs. <?= number_format((float)$p['amount'],2) ?></span></div>
<?php endforeach; ?>

<div class="rule"></div>
<div class="footer"><?= nl2br(esc($shopSettings['receipt_footer']??'Thank you')) ?></div>

<div class="actions">
  <button type="button" onclick="window.print()">Print</button>
  <button type="button" onclick="window.close()">Close</button>
</div>

<script>
window.addEventListener('load', function () {
  setTimeout(function () { window.print(); }, 150);
});
window.addEventListener('afterprint', function () {
  setTimeout(function () { window.close(); }, 150);
});
</script>
</body>
</html>