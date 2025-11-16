# Data Transfer Objects (DTOs)

This directory contains typed DTOs for API contracts between backend and frontend.

## Structure

```
DTO/
├── Shop/
│   ├── ShopData.php           # Shop entity with purchase channel and country
│   └── ShopAddressData.php    # Physical shop location (maps to old "Address" type)
├── Product/
│   ├── CategoryData.php       # Product category (hierarchical)
│   ├── BrandData.php          # Product brand/manufacturer
│   └── ProductData.php        # Catalog product entry
└── Purchase/
    ├── PurchaseData.php       # Complete purchase (maps to old "BuyInfo" type)
    └── PurchaseItemData.php   # Line item (maps to old "Product" type)
```

## Design Principles

1. **Readonly classes**: All DTOs use PHP 8.1+ readonly properties for immutability
2. **Type safety**: Every property is strictly typed using PHP enums where applicable
3. **Backward compatibility**: DTOs provide `toLegacyFormat()` methods to support old frontend types
4. **JSON serialization**: All DTOs have `toArray()` methods for API responses

## Mapping to Old Frontend Types

### Old `Address` → `ShopAddressData`
```typescript
// Old frontend
interface Address {
    country: Country,
    index: string,      // postal code
    city: City,
    street: Street,
    houseNumber: HouseNumber
}

// New DTO adds: id, shopId, isPrimary, displayOrder, isActive
```

### Old `Product` → `PurchaseItemData`
```typescript
// Old frontend
interface Product {
    name: string,
    price: number,           // gross price
    weightAmount: number,    // quantity
    measure: Measure,
    description?: string,
    discount?: number | string
}

// New DTO adds: catalog references (productId, categoryId, brandId),
// VAT tracking (net/gross split, vatRate, vatAmount),
// precise discount tracking (amount + percent)
```

### Old `BuyInfo` → `PurchaseData`
```typescript
// Old frontend
interface BuyInfo {
    date: string,
    time: string,
    currency: Currency,
    address: Address,
    payMethod: PayMethod,
    shopName: ShopName,
    products: Product[]
}

// New DTO adds: normalized shop/address references,
// net/gross/VAT totals, receiptNumber, notes
```

## Usage Example

```php
use App\DTO\Purchase\PurchaseData;
use App\DTO\Purchase\PurchaseItemData;

// Create from database models
$purchase = new PurchaseData(
    id: $model->id,
    purchaseDate: $model->purchase_date,
    // ... other fields
    items: $model->items->map(fn($item) => 
        new PurchaseItemData(/* ... */)
    )->all(),
);

// Return modern API response
return response()->json($purchase->toArray());

// Return legacy-compatible response for old frontend
return response()->json($purchase->toLegacyFormat());
```

## Benefits

1. **Type safety**: Compile-time checks prevent invalid data
2. **Consistency**: Single source of truth for API contracts
3. **Versioning**: Can support multiple API versions (modern + legacy)
4. **Documentation**: DTOs self-document the API structure
5. **Refactoring**: Changes to DTOs surface immediately in IDE
