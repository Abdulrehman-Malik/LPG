import { test, expect } from '@playwright/test';

test.use({ baseURL: process.env.LPG_QA_BASE_URL || 'http://127.0.0.1:8080' });
async function login(page) {
  await page.goto('/login', { waitUntil: 'networkidle' });
  await page.locator('input[name="login"], #login').first().fill('admin');
  await page.locator('input[name="password"], #password').first().fill('admin123');
  await page.locator('button[type="submit"], input[type="submit"]').first().click();
  await page.waitForLoadState('networkidle');
  await expect(page).toHaveURL(/\/dashboard/);
}
test.describe.configure({ mode: 'serial' });

test('E2E-001 Login succeeds', async ({ page }) => {
  await login(page); await expect(page.locator('body')).toContainText('Dashboard');
});
test('E2E-002 Dashboard business summary loads', async ({ page }) => {
  await login(page);
  const body = await page.locator('body').innerText();
  for (const label of ['Total Sale','Cash Sale','Credit Sale','Payments','Stock Purchased']) expect(body.toLowerCase()).toContain(label.toLowerCase());
});
test('E2E-003 POS screen exposes configured gas-sale transaction', async ({ page }) => {
  await login(page); await page.goto('/sales',{waitUntil:'networkidle'});
  await expect(page.locator('#transactionType')).toHaveValue('gas_sale');
  await expect(page.locator('#lines tbody .cyl')).toHaveCount(1);
  await expect(page.locator('#saveBtn')).toContainText('Post Transaction');
});
test('E2E-004 Actual gas sale posts successfully', async ({ page }) => {
  await login(page); await page.goto('/sales',{waitUntil:'networkidle'});
  await page.locator('#transactionType').selectOption('gas_sale');
  await page.locator('#customer_id').selectOption({label:'QA-E2E-CUST — QA E2E Customer'});
  const line=page.locator('#lines tbody tr').first();
  await line.locator('.cyl').selectOption({label:'C6 — 6 kg'});
  await expect(line.locator('.sourceCyl')).toBeEnabled();
  await line.locator('.sourceCyl').selectOption({label:/QA-E2E-C6-001/});
  await line.locator('.entryValue').fill('2');
  const total=await page.locator('#saleTotal').innerText();
  expect(Number(total)).toBeGreaterThan(0);
  await page.locator('.payment .amount').first().fill(total);
  await page.locator('#saveBtn').click();
  await page.waitForLoadState('networkidle');
  await expect(page.locator('body')).toContainText(/posted successfully/i);
});
test('E2E-005 Posted gas sale appears in Sale History with inventory movement', async ({ page }) => {
  await login(page); await page.goto('/sales',{waitUntil:'networkidle'});
  await page.locator('#saleHistoryTab').click();
  await page.locator('#historyType').selectOption('gas_sale');
  await page.locator('#historyCustomer').fill('QA-E2E-CUST');
  await page.locator('#historySearch').click(); await page.waitForTimeout(500);
  const row=page.locator('#saleHistoryBody tr.sale-history-row').first();
  await expect(row).toBeVisible(); await expect(row).toContainText('QA-E2E-CUST');
  await expect(row).toContainText(/Gas Sale/i); await expect(row).toContainText(/POSTED/i);
  await row.click();
  await expect(page.locator('#saleDetailsBody')).toContainText(/Inventory \/ Reversal Movements/i);
  await expect(page.locator('#saleDetailsBody')).toContainText(/gas_kg/i);
  await expect(page.locator('#saleDetailsBody')).toContainText(/out/i);
});
test('E2E-006 Gas stock is reduced after the actual sale', async ({ page }) => {
  await login(page); await page.goto('/inventory',{waitUntil:'networkidle'});
  const row=page.locator('table tbody tr').filter({hasText:'C6'}).first();
  await expect(row).toBeVisible(); await expect(row).toContainText('4.000 KG');
});
test('E2E-007 POS rejects an invalid zero-quantity gas sale without posting', async ({ page }) => {
  await login(page); await page.goto('/sales',{waitUntil:'networkidle'});
  const line=page.locator('#lines tbody tr').first();
  await line.locator('.cyl').selectOption({label:'C6 — 6 kg'}); await line.locator('.entryValue').fill('0');
  await page.locator('.payment .amount').first().fill('0'); await page.locator('#saveBtn').click();
  await expect(page.locator('#posValidationAlert')).toBeVisible();
  await expect(page.locator('#posValidationAlert')).toContainText(/valid quantity|invalid/i);
  await expect(page).toHaveURL(/\/sales$/);
});
test('E2E-008 Configuration navigation remains available after POS regression', async ({ page }) => {
  await login(page); await page.getByText('Configuration',{exact:false}).first().click();
  await page.waitForTimeout(300); await expect(page.locator('body')).toContainText(/configuration/i);
});
