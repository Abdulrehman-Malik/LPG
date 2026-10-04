<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<style>
.purchase-page .card-header { background:#fff; }
.purchase-page .stock-table th,
.purchase-page .stock-table td { vertical-align:middle; }
.purchase-page .stock-table th { white-space:nowrap; font-size:.86rem; font-weight:600; color:#5f6b76; }
.purchase-page .stock-table .form-control,
.purchase-page .stock-table .form-select { min-width:0; }
.purchase-page .stock-table .lineTotal {
    display:flex;
    align-items:center;
    justify-content:flex-end;
    min-height:38px;
    font-weight:600;
    white-space:nowrap;
}
.purchase-page .summary-card { border:1px solid #dee2e6; border-radius:.5rem; }
.purchase-page .summary-row { display:flex; justify-content:space-between; gap:1rem; padding:.6rem 0; }
.purchase-page .summary-row + .summary-row { border-top:1px solid #e9ecef; }
.purchase-page .summary-total { font-size:1.05rem; }
.purchase-page .summary-balance {
    margin-top:.35rem;
    padding-top:.75rem;
    border-top:2px solid #adb5bd;
    font-size:1.15rem;
}
.purchase-page .payment-row {
    border:1px solid #e9ecef;
    border-radius:.5rem;
    padding:.75rem;
    background:#fafbfc;
}
@media (max-width: 991.98px) {
    .purchase-page .stock-table { min-width:920px; }
}
</style>

<div class="purchase-page">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-4">
        <div>
            <h4 class="mb-1">Purchase Entry</h4>
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

        <div class="card shadow-sm mb-3">
            <div class="card-header py-3">
                <div class="fw-semibold">Supplier &amp; Purchase Details</div>
            </div>
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-lg-8">
                        <label class="form-label fw-semibold">Supplier</label>
                        <select name="supplier_id" id="supplier_id" class="form-select" required>
                            <option value="">Select supplier</option>
                            <?php foreach ($suppliers as $s): ?>
                                <option value="<?= $s['id'] ?>"><?= esc($s['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Previous outstanding balance is shown below and carried into this transaction.</div>
                    </div>
                    <div class="col-lg-2">
                        <label class="form-label fw-semibold">Previous Payable</label>
                        <div class="form-control bg-light fw-semibold text-end" id="previousPayable">Rs. 0.00</div>
                    </div>
                    <div class="col-lg-2">
                        <label class="form-label fw-semibold">Discount</label>
                        <input name="discount_amount" id="discount" class="form-control text-end" type="number" step=".01" min="0" value="0">
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mb-3">
            <div class="card-header d-flex justify-content-between align-items-center py-3">
                <div>
                    <div class="fw-semibold">Stock Received</div>
                    <div class="small text-muted">Add each stock item received from the supplier.</div>
                </div>
                <button type="button" class="btn btn-outline-primary btn-sm" id="add">
                    <i class="bi bi-plus-lg me-1"></i>Add Stock Line
                </button>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-borderless mb-0 stock-table">
                        <thead class="border-bottom">
                            <tr>
                                <th class="ps-3" style="width:18%">Stock Type</th>
                                <th style="width:18%">Cylinder Type</th>
                                <th class="text-end" style="width:10%">Quantity</th>
                                <th class="text-end" style="width:16%">Actual Gas / Cylinder (KG)</th>
                                <th class="text-end" style="width:15%">Unit Rate</th>
                                <th class="text-end" style="width:18%">Line Amount</th>
                                <th class="text-center pe-3" style="width:5%"></th>
                            </tr>
                        </thead>
                        <tbody id="lines"></tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header py-3">
                <div class="fw-semibold">Supplier Settlement</div>
                <div class="small text-muted mt-1">
                    Payment is optional. Leave it as “No Payment” for a full credit purchase, or enter the amount paid now.
                </div>
            </div>

            <div class="card-body">
                <div class="row g-4 mt-2">
                    <div class="col-lg-7">
                        <label class="form-label fw-semibold">Purchase Notes</label>
                        <textarea name="notes" class="form-control" rows="5" placeholder="Optional purchase or supplier notes"></textarea>

                        <div class="alert alert-info mt-3 mb-0">
                            <div class="fw-semibold mb-1">Accounting treatment</div>
                            <div class="small">
                                Stock is received immediately. The unpaid portion becomes supplier account payable.
                                Any payment entered here reduces the supplier balance.
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-5">
                        <div class="summary-card p-3 bg-light">
                            <div class="small text-muted mb-2">Supplier Account Summary</div>

                            <div class="summary-row">
                                <span>Previous Payable</span>
                                <strong>Rs. <span id="summaryPrevious">0.00</span></strong>
                            </div>

                            <div class="summary-row">
                                <span>Current Purchase</span>
                                <strong>Rs. <span id="summaryCurrent">0.00</span></strong>
                            </div>

                            <div class="summary-row summary-total">
                                <span class="fw-semibold">Total Payable</span>
                                <strong>Rs. <span id="summaryTotalPayable">0.00</span></strong>
                            </div>

                            <div class="summary-row align-items-center">
                                <span class="fw-semibold">Payment Type</span>
                                <select id="paymentType" class="form-select form-select-sm" style="max-width:180px">
                                    <option value="">No Payment</option>
                                    <option value="cash">Cash</option>
                                    <option value="online">Online</option>
                                    <option value="cheque">Cheque</option>
                                </select>
                            </div>

                            <div class="summary-row align-items-center">
                                <span class="fw-semibold">Amount Paid</span>
                                <div class="input-group input-group-sm" style="max-width:180px">
                                    <span class="input-group-text">Rs.</span>
                                    <input id="amountPaid" class="form-control text-end" type="number" min="0" step=".01" value="0" placeholder="0.00">
                                </div>
                            </div>

                            <div class="summary-balance d-flex justify-content-between align-items-center">
                                <span class="fw-bold">Balance Payable</span>
                                <strong>Rs. <span id="summaryBalance">0.00</span></strong>
                            </div>
                        </div>
                    </div>
                </div>

                <button class="btn btn-primary w-100 mt-4" id="postPurchase" type="submit">
                    <i class="bi bi-check2-circle me-1"></i>Post Purchase
                </button>
            </div>
        </div>
    </form>
</div>

<script>
const types = <?= json_encode($types) ?>;
const supplierBalances = <?= json_encode($supplierBalances ?? []) ?>;

const money = value => Number(value || 0).toFixed(2);

function getPreviousPayable() {
    const supplierId = document.getElementById('supplier_id').value;
    return Math.max(0, Number(supplierBalances[supplierId] || 0));
}

function addLine() {
    const row = document.createElement('tr');
    row.className = 'line border-bottom';
    row.innerHTML = `
        <td class="ps-3">
            <select class="form-select type">
                <option value="filled_cylinder">Filled Cylinder</option>
                <option value="empty_cylinder">Empty Cylinder</option>
            </select>
        </td>
        <td>
            <select class="form-select cyl">
                <option value="">—</option>
                ${types.map(t => `<option value="${t.id}">${t.code} — ${t.name}</option>`).join('')}
            </select>
        </td>
        <td>
            <input class="form-control qty text-end" type="number" min=".001" step=".001" value="1">
        </td>
        <td>
            <input class="form-control actual text-end" type="number" min="0" step=".001" placeholder="Filled cylinders only">
        </td>
        <td>
            <input class="form-control rate text-end" type="number" min="0" step=".01" value="0">
        </td>
        <td>
            <div class="lineTotal">Rs. 0.00</div>
        </td>
        <td class="text-center pe-3">
            <button type="button" class="btn btn-outline-danger btn-sm rm" title="Remove line">
                <i class="bi bi-trash"></i>
            </button>
        </td>`;

    document.getElementById('lines').append(row);
    row.querySelector('.rm').onclick = () => { row.remove(); calculate(); };
    row.querySelectorAll('input,select').forEach(element => element.addEventListener('input', calculate));
    row.querySelectorAll('select').forEach(element => element.addEventListener('change', calculate));
}

function calculate() {
    let current = 0;

    document.querySelectorAll('.line').forEach(row => {
        const amount =
            Number(row.querySelector('.qty').value || 0) *
            Number(row.querySelector('.rate').value || 0);

        row.querySelector('.lineTotal').textContent = 'Rs. ' + money(amount);
        current += amount;
    });

    current = Math.max(0, current - Number(document.getElementById('discount').value || 0));

    const previous = getPreviousPayable();
    const totalPayable = previous + current;

    const paid = Math.max(0, Number(document.getElementById('amountPaid').value || 0));

    const balance = Math.max(0, totalPayable - paid);

    document.getElementById('previousPayable').textContent = 'Rs. ' + money(previous);
    document.getElementById('summaryPrevious').textContent = money(previous);
    document.getElementById('summaryCurrent').textContent = money(current);
    document.getElementById('summaryTotalPayable').textContent = money(totalPayable);
    document.getElementById('summaryBalance').textContent = money(balance);
    document.getElementById('amountPaid').max = money(totalPayable);

    const over = paid > totalPayable + 0.01;
    document.getElementById('amountPaid').classList.toggle('is-invalid', over);
    document.getElementById('postPurchase').disabled = over;
}

document.getElementById('add').onclick = addLine;
document.getElementById('discount').addEventListener('input', calculate);
document.getElementById('supplier_id').addEventListener('change', calculate);
document.getElementById('paymentType').addEventListener('change', function () {
    if (!this.value) document.getElementById('amountPaid').value = '0.00';
    calculate();
});
document.getElementById('amountPaid').addEventListener('input', calculate);

document.getElementById('purchaseForm').onsubmit = function () {
    const lines = [...document.querySelectorAll('.line')].map(row => ({
        line_type: row.querySelector('.type').value,
        cylinder_type_id: row.querySelector('.cyl').value,
        quantity: row.querySelector('.qty').value,
        unit_rate: row.querySelector('.rate').value,
        actual_gas_weight_kg: row.querySelector('.actual').value
    }));

    const paymentType = document.getElementById('paymentType').value;
    const paidAmount = Number(document.getElementById('amountPaid').value || 0);

    if (paidAmount > 0 && !paymentType) {
        alert('Select a payment type for the amount paid.');
        return false;
    }

    const payments = paidAmount > 0 ? [{
        payment_mode: paymentType,
        amount: paidAmount,
        reference_no: ''
    }] : [];

    if (!lines.length) {
        alert('Add at least one stock line.');
        return false;
    }

    const totalPayable = getPreviousPayable() +
        Number(document.getElementById('summaryCurrent').textContent || 0);

    const paid = payments.reduce((sum, payment) => sum + Number(payment.amount || 0), 0);

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
