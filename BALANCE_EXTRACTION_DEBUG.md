# Balance Extraction Debug Log

## Issue Summary
User reported: "sales ma store nhii ho ra ha agency_pricing wala balance fix it"
(Balance not storing in sales table from agency_pricing)

## Fix Applied

### 1. Enhanced StoreSaleRepo.php
**File**: `app/Repository/V2/Shared/Schedule/Sale/StoreSaleRepo.php`

**Changes**:
- Added detailed logging to `extractBalanceFromOffer()` method
- Added debug logging in `run()` method to track extraction process
- Logs show: offer_id, extracted_balance, and reasoning

**Debug Logs to Watch For**:
```
Extract Balance: Offer not found - {offer_id}
Extract Balance: agency_pricing found - [...]
Extract Balance: user ville={ville}, detected userAgency={userAgency}
Extract Balance: Matched criel agency, balance={balance}
Extract Balance: Matched exact agency {userAgency}, balance={balance}
Extract Balance: No exact match, using first agency, balance={balance}
Extract Balance: Using root offer.balance={balance}
Extract Balance: Exception parsing agency_pricing - [error]
Extract Balance: Exception - [error]
Sale Balance Extracted: offer_id={offer_id}, extracted_balance={extracted_balance}
Sale Balance NOT extracted: offer_id={offer_id}
Sale: No offer_id found in attributes or cart
```

### 2. Enhanced StoreOrEditWalletAction.php
**File**: `app/Repository/V2/Student/Schedule/Training/Wallet/StoreOrEditWalletAction.php`

**Changes**:
- Added detailed logging to `extractBalanceFromOffer()` method
- Added debug logging in `run()` method with userCity context
- Same extraction logic as StoreSaleRepo

**Debug Logs to Watch For**:
```
Wallet Extract Balance: Offer not found - {offer_id}
Wallet Extract Balance: agency_pricing found - [...]
Wallet Extract Balance: userCity={userCity}, ville={ville}, detected userAgency={userAgency}
Wallet Extract Balance: Matched criel agency, balance={balance}
Wallet Extract Balance: Matched exact agency {userAgency}, balance={balance}
Wallet Extract Balance: No exact match, using first agency, balance={balance}
Wallet Extract Balance: Using root offer.balance={balance}
Wallet Extract Balance: Exception parsing agency_pricing - [error]
Wallet Extract Balance: Exception - [error]
Wallet Balance Extracted: offer_id={offer_id}, extracted_balance={extracted_balance}, userCity={userCity}
Wallet Balance NOT extracted: offer_id={offer_id}, userCity={userCity}
Wallet: No offer_id in attributes
```

## Verification Checklist

✅ **Database Schema**: Both `sales.balance` and `wallets.balance` columns exist
✅ **Model Protection**: Both `Sale` and `Wallet` models have `$unguarded = true`
✅ **Balance Extraction**: Code correctly extracts from agency_pricing, falls back to root balance
✅ **Data Persistence**: Balance is set in correct arrays before create()

## How to Test

### Manual Test:
1. Run this script to check existing data:
   ```bash
   php test_balance_check.php
   ```

2. Make a test purchase and check logs:
   ```bash
   tail -f storage/logs/laravel.log | grep -E "Balance|Extract"
   ```

### Expected Behavior:
1. When creating a sale: Should see "Sale Balance Extracted: ..."
2. When creating a wallet: Should see "Wallet Balance Extracted: ..."
3. Database query should show balance populated in sales and wallets tables

## Diagnostic Steps (if logs show failures)

### If "Extract Balance: Offer not found":
- Verify offer_id is correctly retrieved
- Check if offer exists in database

### If "Extract Balance: agency_pricing found - []":
- Check if agency_pricing is valid JSON
- Verify agency_pricing has data in the database

### If "Extract Balance: user ville=..., detected userAgency=...":
- But no match found, verify agency names match expected values
- Common values: 'criel', 'toulouse'

### If "Extract Balance: Using root offer.balance=...":
- agency_pricing extraction failed or was empty
- Using fallback root balance (expected behavior)

### If "Sale Balance NOT extracted" or "Wallet Balance NOT extracted":
- Check logs above to see which step failed
- May indicate malformed data or database issue

## Code Flow Diagram

```
Purchase Triggered
    ↓
StoreSaleRepo.run() / StoreOrEditWalletAction.run()
    ↓
Get offer_id from attributes or cart.cartDetails
    ↓
Call extractBalanceFromOffer(offer_id)
    ↓
    ├─ Load Offer from DB
    │  ├─ If not found: Return null
    │  └─ If found:
    │      ├─ Check agency_pricing JSON
    │      │  ├─ Parse JSON
    │      │  ├─ Get user agency from ville (creil→criel, toulouse→toulouse)
    │      │  ├─ Try to find matching agency
    │      │  │  ├─ Exact match found: Return balance
    │      │  │  └─ No match: Use first agency balance
    │      │  └─ Exception: Fall through
    │      └─ Use root offer.balance
    ↓
Set balance in attributes (for wallet) or saleData (for sale)
    ↓
Create model with balance included
    ↓
Database stores balance value
```

## Files Modified

1. `app/Repository/V2/Shared/Schedule/Sale/StoreSaleRepo.php`
   - Enhanced `extractBalanceFromOffer()` with logging
   - Enhanced `run()` with extraction call and logging

2. `app/Repository/V2/Student/Schedule/Training/Wallet/StoreOrEditWalletAction.php`
   - Enhanced `extractBalanceFromOffer()` with logging
   - Enhanced `run()` with extraction call and logging

## Test Script Created

- `test_balance_check.php` - Checks recent sales/wallets and offers to verify balance storage

## Next Steps

1. Execute a test purchase
2. Check Laravel logs for extraction messages
3. Query database to confirm balance was stored
4. If still not working, investigate:
   - Is offer_id being passed correctly?
   - Is agency_pricing JSON valid?
   - Are user agencies (ville) matching expected values?

