<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h4 class="mb-1">Purchase Entry</h4>
        <div class="text-muted small">Receive stock and settle the supplier payable now or leave the balance on account.</div>
    </div>
</div>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>

<form method="post" action="<?= site_url('purchases/save') ?>" id="purchaseForm">
    <?= csrf_field() ?>
    <input type="hidden" name="lines_json" id="lines_json">
    <input type="hidden" name="payments_json" id="payments_json">

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-lg-7">
                    <label class="form-label fw-semibold">Supplier</label>
                    <select name="supplier_id" id="supplier_id" class="form-select" required>
                        <option value="">Select supplier</option>
                        <?php foreach ($suppliers as $s): ?>
                            <option value="<?= $s['id'] ?>"><?= esc($s['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">The supplier's previous outstanding payable is carried into this purchase.</div>
                </div>
                <div class="col-lg-3">
                    <label class="form-label fw-semibold">Previous Payable</label>
                    <div class="form-control bg-light fw-semibold" id="previousPayable">Rs. 0.00</div>
                </div>
                <div class="col-lg-2">
                    <label class="form-label fw-semibold">Discount</label>
                    <input name="discount_amount" id="discount" class="form-control" type="number" step=".01" min="0" value="0">
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mt-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <div class="fw-semibold">Stock Received</div>
                <div class="small text-muted">Enter the goods received, quantity and agreed purchase rate.</div>
            </div>
            <button type="button" class="btn btn-outline-primary btn-sm" id="add">Add Stock Line</button>
        </div>
        <div class="card-body">
            <div class="row g-2 small text-muted fw-semibold mb-2 d-none d-lg-flex">
                <div class="col-lg-2">Stock Type</div>
                <div class="col-lg-2">Cylinder Type</div>
                <div class="col-lg-1">Qty</div>
                <div class="col-lg-2">Actual Gas KG</div>
                <div class="col-lg-2">Unit Rate</div>
                <div class="col-lg-2">Line Amount</div>
                <div class="col-lg-1"></div>
            </div>
            <div id="lines"></div>
        </div>
    </div>

    <div class="card shadow-sm mt-3">
        <div class="card-header">
            <div class="fw-semibold">Supplier Settlement</div>
            <div class="small text-muted">
                Payment is optional. Any unpaid amount automatically remains payable to this supplier.
                A payment above the current purchase amount first settles previous supplier payable.
            </div>
        </div>
        <div class="card-body">
            <div id="payments"></div>
            <button type="button" class="btn btn-outline-secondary btn-sm mt-1" id="pay">Add Payment</button>

            <div class="row g-3 mt-3">
                <div class="col-lg-8">
                    <label class="form-label fw-semibold">Purchase Notes</label>
                    <textarea name="notes" class="form-control" rows="3" placeholder="Optional purchase or supplier notes"></textarea>
                </div>
                <div class="col-lg-4">
                    <div class="border rounded p-3 bg-light">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Previous Payable</span>
                            <strong>Rs. <span id="summaryPrevious">0.00</span></strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Current Purchase</span>
                            <strong>Rs. <span id="summaryCurrent">0.00</span></strong>
                        </div>
                        <div class="d-flex justify-content-between border-top pt-2 mb-2">
                            <span class="fw-semibold">Total Payable</span>
                            <strong>Rs. <span id="summaryTotalPayable">0.00</span></strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Amount Paid</span>
                            <strong>Rs. <span id="summaryPaid">0.00</span></strong>
                        </div>
                        <div class="d-flex justify-content-between border-top pt-2 fs-5">
                            <span class="fw-bold">Balance Payable</span>
                            <strong>Rs. <span id="summaryBalance">0.00</span></strong>
                        </div>
                    </div>
                </div>
            </div>

            <div class="alert alert-info mt-3 mb-3 py-2">
                <strong>Accounting:</strong> Stock is received immediately. Unpaid purchase value becomes supplier account payable,
                while any amount paid is recorded against the supplier balance.
            </div>

            <button class="btn btn-primary w-100" id="postPurchase" type="submit">
                Post Purchase &amp; Update Supplier Payable
            </button>
        </div>
    </div>
</form>

<script>
const types = <?= json_encode($types) ?>;
const supplierBalances = <?= json_encode($supplierBalances ?? []) ?>;

const money = value => Number(value || 0).toFixed(2);

function getPreviousPayable() {
    const supplierId = document.getElementById('supplier_id').value;
    return Number(supplierBalances[supplierId] || 0);
}

function addLine() {
    const d = document.createElement('div');
    d.className = 'row g-2 mb-2 align-items-end line';
    d.innerHTML = `
        <div class="col-lg-2">
            <label class="form-label d-lg-none">Stock Type</label>
            <select class="form-select type">
                <option value="gas_kg">Gas (KG)</option>
                <option value="filled_cylinder">Filled Cylinder</option>
                <option value="empty_cylinder">Empty Cylinder</option>
            </select>
        </div>
        <div class="col-lg-2">
            <label class="form-label d-lg-none">Cylinder Type</label>
            <select class="form-select cyl">
                <option value="">—</option>
                ${types.map(t => `<option value="${t.id}">${t.code} — ${t.name}</option>`).join('')}
            </select>
        </div>
        <div class="col-lg-1">
            <label class="form-label d-lg-none">Qty</label>
            <input class="form-control qty" type="number" min=".001" step=".001" value="1">
        </div>
        <div class="col-lg-2">
            <label class="form-label d-lg-none">Actual Gas KG</label>
            <input class="form-control actual" type="number" min="0" step=".001" placeholder="Filled only">
        </div>
        <div class="col-lg-2">
            <label class="form-label d-lg-none">Unit Rate</label>
            <input class="form-control rate" type="number" min="0" step=".01" value="0">
        </div>
        <div class="col-lg-2">
            <label class="form-label d-lg-none">Line Amount</label>
            <div class="form-control-plaintext fw-semibold lineTotal">0.00</div>
        </div>
        <div class="col-lg-1">
            <button type="button" class="btn btn-outline-danger w-100 rm" title="Remove line">×</button>
        </div>`;
    document.getElementById('lines').append(d);
    d.querySelector('.rm').onclick = () => { d.remove(); calculate(); };
    d.querySelectorAll('input,select').forEach(e => e.addEventListener('input', calculate));
    d.querySelector('.type').addEventListener('change', calculate);
}

function addPayment() {
    const d = document.createElement('div');
    d.className = 'row g-2 mb-2 align-items-end payment';
    d.innerHTML = `
        <div class="col-md-3">
            <label class="form-label">Payment Method</label>
            <select class="form-select mode">
                <option value="cash">Cash</option>
                <option value="online">Online</option>
                <option value="cheque">Cheque</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Amount Paid</label>
            <input class="form-control amount" type="number" min=".01" step=".01" placeholder="0.00">
        </div>
        <div class="col-md-4">
            <label class="form-label">Reference / Cheque No.</label>
            <input class="form-control ref" placeholder="Optional">
        </div>
        <div class="col-md-2">
            <button type="button" class="btn btn-outline-danger w-100 removePayment">Remove</button>
        </div>`;
    document.getElementById('payments').append(d);
    d.querySelector('.removePayment').onclick = () => { d.remove(); calculate(); };
    d.querySelector('.amount').addEventListener('input', calculate);
}

function calculate() {
    let current = 0;
    document.querySelectorAll('.line').forEach(d => {
        const amount = Number(d.querySelector('.qty').value || 0) * Number(d.querySelector('.rate').value || 0);
        d.querySelector('.lineTotal').textContent = money(amount);
        current += amount;
    });

    current = Math.max(0, current - Number(document.getElementById('discount').value || 0));
    const previous = Math.max(0, getPreviousPayable());
    const totalPayable = previous + current;

    let paid = 0;
    document.querySelectorAll('.payment').forEach(d => {
        paid += Number(d.querySelector('.amount').value || 0);
    });

    const balance = Math.max(0, totalPayable - paid);

    document.getElementById('previousPayable').textContent = 'Rs. ' + money(previous);
    document.getElementById('summaryPrevious').textContent = money(previous);
    document.getElementById('summaryCurrent').textContent = money(current);
    document.getElementById('summaryTotalPayable').textContent = money(totalPayable);
    document.getElementById('summaryPaid').textContent = money(paid);
    document.getElementById('summaryBalance').textContent = money(balance);

    const over = paid > totalPayable + 0.01;
    document.getElementById('summaryPaid').classList.toggle('text-danger', over);
    document.getElementById('postPurchase').disabled = over;
}

document.getElementById('add').onclick = addLine;
document.getElementById('pay').onclick = addPayment;
document.getElementById('discount').addEventListener('input', calculate);
document.getElementById('supplier_id').addEventListener('change', calculate);

document.getElementById('purchaseForm').onsubmit = function () {
    const lines = [...document.querySelectorAll('.line')].map(d => ({
        line_type: d.querySelector('.type').value,
        cylinder_type_id: d.querySelector('.cyl').value,
        quantity: d.querySelector('.qty').value,
        unit_rate: d.querySelector('.rate').value,
        actual_gas_weight_kg: d.querySelector('.actual').value
    }));

    const payments = [...document.querySelectorAll('.payment')]
        .map(d => ({
            payment_mode: d.querySelector('.mode').value,
            amount: d.querySelector('.amount').value,
            reference_no: d.querySelector('.ref').value
        }))
        .filter(p => Number(p.amount || 0) > 0);

    if (!lines.length) {
        alert('Add at least one stock line.');
        return false;
    }

    const totalPayable = getPreviousPayable() + Number(document.getElementById('summaryCurrent').textContent || 0);
    const paid = payments.reduce((sum, p) => sum + Number(p.amount || 0), 0);
    if (paid > totalPayable + 0.01) {
        alert('Amount paid cannot exceed the total supplier payable.');
        return false;
    }

    document.getElementById('lines_json').value = JSON.stringify(lines);
    document.getElementById('payments_json').value = JSON.stringify(payments);
    return true;
};

addLine();
calculate();
</script>

<?= $this->endSection() ?>
