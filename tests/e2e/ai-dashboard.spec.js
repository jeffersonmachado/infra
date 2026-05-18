'use strict';
const { test } = require('@playwright/test');

const TOKEN = process.env.OBSERVE_TOKEN || 'f17ff319c89c90976a40f51cba29fee9721d4c51cea983b11084e4b06e539ddb';

test('AI Dashboard screenshot', async ({ page }) => {
  await page.goto('http://localhost:3080/observe/ai');
  await page.fill('#token', TOKEN);
  await page.click('button:has-text("↺")');
  await page.waitForTimeout(2500);
  await page.screenshot({ path: 'test-results/ai-dashboard.png', fullPage: true });

  await page.click('.tab:has-text("Catálogo")');
  await page.waitForTimeout(500);
  await page.screenshot({ path: 'test-results/ai-catalog.png', fullPage: true });

  await page.click('.tab:has-text("Análises")');
  await page.waitForTimeout(500);
  await page.screenshot({ path: 'test-results/ai-analyses.png', fullPage: true });
});
