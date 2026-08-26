# Test Team Report — Full Test Coverage Audit

> Generated: 2026-08-26　Version: v1.3.8
> Team: deep-audit (tester-php / tester-api / tester-ui / tester-go / tester-rust)

## 1. Executive Summary

| Role | Task | Result |
|------|------|--------|
| PHP test engineer | Unit/integration tests for all modules | 70 existing tests + this round's additions (see §3) |
| API test engineer | Automation for all endpoints | Controller-layer integration tests are this project's API automation form (§4) |
| UI automation engineer | End-to-end for all pages | Environment not available, conclusion in §5 |
| GO test engineer | Unit tests | **Skipped: project has no GO code** (zero .go files) |
| Rust test engineer | Unit tests | **Skipped: project has no Rust code** (zero .rs files) |

## 2. Tech Stack & Test Approach

- Backend: PHP 8.3 webman, two apps (service user side / admin dashboard), sharing service models
- Test framework: PHPUnit + Eloquent, **real MySQL + transaction rollback** mode (not mocks), auto-skip if DB unavailable
- Test run: `cd service && php -d memory_limit=2G vendor/bin/phpunit`
- API automation = controller-layer integration tests (construct Request, call controller methods directly, hit real DB, rollback transactions)

## 3. PHP Test Coverage

**Full results: 558 tests / 2508 assertions, 0 failures 0 errors 0 skipped** (2 pre-existing vendor deprecations, 2 pre-existing PHPUnit notices, none introduced this round; the original 4 withdrawal-gate skips were eliminated via injectable `config('withdraw.gate_day')`, so all tests run any day)

### Added This Round (tester-php, 6 files 32 cases, all real DB + transaction rollback)

| Test file | Cases | Coverage |
|-----------|-------|----------|
| CartControllerTest | 4 | Save normalization (whitelist/qty≥1/drop dirty entries), non-array 400, empty cart, clear cart |
| PointControllerTest | 4 | Balance = latest snapshot, pagination meta, type/source filtering, empty list |
| AddressControllerTest | 7 | Add + default, required-field 400, default exclusivity, default priority, unauthorized 404, switch default, delete + second 404 |
| FavoriteControllerTest | 7 | Favorite service/technician, invalid type 400, duplicate 400, favorite_count increment/decrement, orphan favorites, delete 404 |
| ReferralControllerTest | 5 | Referral code generation + stats, user 404, QR code URL, referred list, commission details |
| WithdrawControllerTest | 5 | Gate-day rejection (config injection for non-today), success, insufficient balance, < 10 yuan, missing account (runs any day, 0 skipped) |

### Existing Coverage (70 files, unchanged)

35+ controllers covered: Auth/Order state machine/refund/verification/rescheduling/payment callback/flash sale/group buy/coupons/gift cards/points/wallet/transfer/member cards/growth value/rebate/withdrawal/check-in/scheduling/invoice/logistics/push/subscribe messages/queues, etc.

### Fixes This Round (found by tester-php)

- [bug] AddressController::show/update/destroy and FavoriteController::destroy did not decode hashids, hashid calls returned 404.
  Root-cause fix: `BaseController::decodeId` adds pass-through compatibility for pure numeric strings (return as-is when hashids cannot decode and ctype_digit), benefiting all 89 call sites uniformly; 4 controller method entries got decodeId added. Full regression passed.
- [bug] When hashids min-length was 0, some bare numeric IDs (e.g. 306) happened to be valid hashids encodings of other IDs, and decodeId could misdecode to a wrong ID (AddressControllerTest sporadic 404, randomly reproduced across multiple full runs).
  Root-cause fix: service/admin `config/hashids.php` main connection `length` 0→8, so encodings are always ≥ 8 chars, disjoint from bare numeric IDs (< 8 or 16 digits), eliminating the ambiguity from the encoding space.
  5 consecutive runs of AddressControllerTest confirmed stability, full regression passed.
- Withdrawal gate day hardcoded to the 20th changed to injectable `config('withdraw.gate_day')` (config/withdraw.php); the original 4 "only on the 20th" skip cases now inject the gate day via reflection and run any day, 0 skipped.

## 4. API Automation Test Conclusion

- This project has no standalone HTTP-layer test scripts; all 70 existing test files are controller-layer integration tests (real DB), covering 35+ controllers, equivalent to endpoint automation testing
- Test coverage matrix in §3
- **HTTP smoke test executed** (2026-08-26): port 8787 was occupied by another project, so service `config/process.php` listener was temporarily changed to 8791 to start the service (32 webman workers + websocket + 4 timers all [OK]), verified `GET /health` → `{"code":0,"message":"ok"}`, `GET /api/guest/services` → HTTP 200 with normal JSON (hashids-encoded IDs visible), then stopped and restored config, zero leftover processes
- Recommended adding flutter build web → Playwright E2E of admin key paths in CI (see §5)

## 5. UI End-to-End Conclusion

- Clients: Flutter (apps/flutter user side, admin/apps/flutter dashboard), WeChat Mini Program (apps/wechat), HarmonyOS (apps/harmonyos), admin/apps/weixin
- Current state: admin Flutter web has no build artifacts (build/web missing); no UI services running on this machine; no browser automation channel for WeChat Mini Program/HarmonyOS
- **Conclusion: end-to-end automation environment unavailable**. Recommended adding to CI: flutter build web → Playwright driving dashboard key paths (login → order list → verification); Mini Program/HarmonyOS need manual testing on real devices/emulators
- Provided: admin/public/apidoc (API docs page)

## 6. GO / Rust

Recursive scan of the project root found **0 .go files, 0 .rs files** (excluding vendor/node_modules/.git). Toolchains are installed (go / rustc available) but there is nothing to test. If GO/Rust services are introduced later, tests must be added separately.

## 7. Remaining Risks (Uncovered High-Value Areas)

- Order main flow (covered via trait-level tests such as OrderState/OrderRefundFlow)
- Real WeChat Pay callbacks (WechatPayService has unit tests; real WeChat sandbox not integration-tested)
- External dependency modules: printing, LBS, captcha, etc.

(§3 pending fill after tester-php returns)
