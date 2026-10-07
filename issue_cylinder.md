# Issue Cylinder / Security Deposit & Cylinder Return Requirements

## Purpose
Authoritative implementation source for POS Security Deposit / Issue Cylinder and Cylinder Return / Refund Deposit.

## Security Deposit / Issue Cylinder
- Select an existing customer and load normal customer context.
- Select cylinder type and Filled/Empty condition; Filled includes filled and partially-filled cylinders.
- Popup shows individual available physical cylinder IDs with Cylinder Type and Filled/Empty filters.
- Multiple line items are allowed, but the same Cylinder Type + Filled/Empty combination cannot be duplicated in one issue transaction.
- Filled and empty cylinders may be issued together. At least one cylinder is required.
- Filled cylinder issue gas = its full current gas quantity/capacity; partially-filled = exact current gas quantity; empty = 0 kg.
- Gas rate follows existing POS rules and total follows current cylinder-sale pricing logic.
- At issue time, filled/partially-filled gas stock is reduced by the exact issued gas quantity; the physical cylinder moves to customer custody/pending status and its ID remains tracked.
- Empty cylinders move to customer custody with 0 kg gas.
- Custody is temporary; the shop remains owner.
- One transaction-level Security Deposit Amount field on the right panel, not per cylinder. It may be 0 or positive.
- Deposit uses existing POS payment methods and is tracked separately from party OS and pending-cylinder balance.
- Shop Settings controls whether deposit affects party OS. Disabled: deposit does not affect OS. Enabled: deposit affects OS and the resulting OS including deposit is shown. A later refund reverses that OS impact when enabled.
- OPEN DECISION: whether gas/cylinder issue amount itself always affects party OS was not yet answered by the user; do not invent this rule.

## Cylinder Return / Refund Deposit
- Select customer first; show only that customer's currently pending/issued cylinders.
- Popup shows individual cylinder cards with Cylinder ID, type/capacity, current status, and gas quantity; filters are Cylinder Type and Filled/Empty.
- User selects specific IDs; each selected cylinder becomes its own return line.
- Filled/partially-filled and empty cylinders can be returned together. At least one cylinder is required.
- Return gas defaults to 0 and is entered separately for each cylinder; no Apply-to-All for quantity.
- Return gas 0 => cylinder becomes Empty Stock. Return gas >0 => cylinder becomes Partially Filled Stock with that exact gas quantity and same physical ID; returned gas is added to shop gas stock.
- Issued gas was already deducted at issue, so return never deducts issued gas again.
- Consumed gas = Issued Gas - Returned Gas and must be retained in history. Show per line: Issued X kg | Return Y kg | Consumed Z kg.
- Original issue gas rate is shown by default. User can change it.
- Support one overall return-gas rate plus individual per-cylinder rates. Provide Apply to All for the overall rate; individual changes override the overall rate and are used for party-OS calculation when return-gas ledger impact is enabled.

## Return Gas Settings
Two independent options, both disabled by default:
1. Allow/Show Return Gas Quantity.
2. Return Gas Affects Party OS.
If quantity is disabled, users cannot enter it. If quantity is enabled but OS impact is disabled, stock is still increased but party OS is unchanged.

## Invalid Return Gas Conditions
- Empty-issued cylinder returned with gas >0: allowed only if a Shop Settings option permits it; when allowed, show confirmation/warning. When disabled, reject and prevent the entire transaction from saving.
- Return gas greater than originally issued: allowed only if a Shop Settings option permits it; when allowed, show confirmation/warning. When disabled, reject and prevent the entire transaction from saving.
- Never silently change a disallowed value to 0.

## Deposit Refund
- One transaction-level Security Deposit Refund Amount field on the right panel; it may be 0.
- Refund cannot exceed customer's currently refundable security-deposit balance.
- If no refund is made, remaining deposit stays refundable for future transactions.
- Deposit remains separate from party OS and pending-cylinder balance.
- Refund uses existing POS payment methods.
- Return transaction requires at least one cylinder.

## Return Stock
- Returned cylinder becomes immediately available in shop stock.
- Return gas >0 => available partially-filled physical cylinder with exact returned gas quantity.
- Return gas 0 => available empty physical cylinder.
- Preserve physical cylinder identity and full custody history.

## Balances / Audit
Maintain separately and reconcile: party OS, security deposit balance, pending cylinder balance, issued gas, returned gas, consumed gas, physical cylinder custody history, deposit received/refunded history.

## Atomicity / Validation
- Financial, stock, cylinder-status, custody, deposit and ledger effects must post atomically.
- Any validation failure prevents the entire transaction from saving.
- Server-side validation is mandatory.
- Lock/revalidate physical cylinder IDs during posting to prevent double issue/return.
- Reject duplicate selections and returns for cylinders not pending for the selected customer.

## Shop Settings
Add/configure:
- POS visibility of Security Deposit / Issue Cylinder and Cylinder Return / Refund Deposit.
- Include Security Deposit in Party OS (default disabled).
- Allow/Show Return Gas Quantity (default disabled).
- Return Gas Affects Party OS (default disabled).
- Allow Empty-Issued Cylinder to be Returned With Gas (default disabled).
- Allow Return Gas Greater Than Originally Issued (default disabled).

## Implementation
Reuse existing POS payment/cash and physical-cylinder infrastructure where possible, but extend custody/history to preserve issue gas, return gas, consumed gas, original/return rates, and proper deposit allocation. Custody is not a cylinder sale. Never deduct issued gas again on return. Update documentation/test cases with code changes.
