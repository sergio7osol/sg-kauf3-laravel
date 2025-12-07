---
description: Manual receipt ingestion into purchases DB
---
1. **Prepare shop + address**
   - Ensure shop exists in `shops` table (create if missing).
   - Ensure address exists in `shop_addresses` linked to the shop (street, house number, postal code, city, country).

2. **Confirm payment method**
   - Verify the user's payment method already exists (`user_payment_methods`).
   - Note the method name (e.g., "DKB Card").

3. **Collect receipt asset**
   - Place the PDF/PNG under `resources/PSD/data/` (any filename).
   - For PNG/JPG, keep it high-resolution for OCR readability.

4. **Extract raw text**
   - Prefer `pdftotext` for PDFs; fallback to Tesseract OCR for images.
   - Save/copy the text output for reference and validation.

5. **Transcribe to JSON**
   - Create `resources/PSD/data/<YYYY-MM-DD>_<shop>.json` using the legacy schema:
     ```json
     [
       {
         "date": "DD.MM.YYYY",
         "time": "HH:MM",
         "currency": "EUR",
         "shopName": "...",
         "payMethod": "...",
         "address": {"country": "Germany", "index": "...", "city": "...", "street": "...", "houseNumber": "..."},
         "products": [
           {"name": "...", "price": 1.23, "weightAmount": 2, "measure": "piece", "description": "...", "discount": 0}
         ]
       }
     ]
     ```
   - Represent discounts/coupons/returns as line items with negative `price`.
   - Keep quantities (`weightAmount`) true to receipt data (kg, pieces, etc.).

6. **Dry run import**
   - Run `sail artisan import:legacy-data --user=<id> --file=resources/PSD/data/<file>.json --dry-run`.
   - Verify summary (shops/addresses/payment methods should already exist; check purchase + line counts).

7. **Execute import**
   - Run the same command without `--dry-run`.
   - Confirm the success summary (1 purchase, N lines, no skipped duplicates).

8. **Post-import checks**
   - Optional: `sail artisan tinker --execute="App\\Models\\Purchase::latest()->with('lines')->first()"` to inspect the inserted purchase.
   - Archive receipt JSON if needed or keep it in `resources/PSD/data/` for future reruns.
