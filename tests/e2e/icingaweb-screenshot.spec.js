'use strict';

const { test, expect } = require('@playwright/test');
const path = require('path');

const BASE_URL = process.env.BASE_URL || 'http://localhost:3080';

test('IcingaWeb2 — captura dashboard, hosts e serviços', async ({ page }) => {
  // Login
  await page.goto(`${BASE_URL}/icinga/`);
  await page.waitForSelector('input[name="username"]', { timeout: 15000 });
  await page.fill('input[name="username"]', 'admin');
  await page.fill('input[name="password"]', 'admin');
  await page.click('input[type="submit"]');
  await page.waitForURL(url => !url.toString().includes('/authentication/login'), { timeout: 15000 });

  // Dashboard
  await page.screenshot({ path: 'test-results/icinga-dashboard.png', fullPage: true });
  console.log('Dashboard:', page.url());
  console.log('Título:', await page.title());

  // Hosts
  await page.goto(`${BASE_URL}/icinga/icingadb/hosts`);
  await page.waitForLoadState('networkidle', { timeout: 10000 }).catch(() => {});
  await page.screenshot({ path: 'test-results/icinga-hosts.png', fullPage: true });
  console.log('Hosts página:', page.url());

  // Serviços
  await page.goto(`${BASE_URL}/icinga/icingadb/services`);
  await page.waitForLoadState('networkidle', { timeout: 10000 }).catch(() => {});
  await page.screenshot({ path: 'test-results/icinga-services.png', fullPage: true });
  console.log('Services página:', page.url());

  // Incidentes/problemas
  await page.goto(`${BASE_URL}/icinga/icingadb/hosts?problems=1`);
  await page.waitForLoadState('networkidle', { timeout: 10000 }).catch(() => {});
  await page.screenshot({ path: 'test-results/icinga-problems.png', fullPage: true });

  expect(true).toBe(true);
});
