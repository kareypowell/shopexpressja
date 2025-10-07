# Air Rate Calculator Service

The `AirRateCalculator` service provides consistent and accurate freight price calculations for air shipments based on package weight.

## Overview

Similar to the `SeaRateCalculator` for sea shipments, the `AirRateCalculator` handles:
- Weight-based rate calculations for air packages
- Automatic weight rounding (standard air freight practice)
- Exchange rate application
- Fallback rate matching
- Detailed rate breakdowns for transparency

## Usage

### Basic Calculation

```php
use App\Services\AirRateCalculator;

$calculator = new AirRateCalculator();
$freightPrice = $calculator->calculateFreightPrice($package);
```

### Rate Breakdown

```php
$breakdown = $calculator->getRateBreakdown($package);

// Returns:
[
    'weight' => 14.7,           // Original package weight
    'rounded_weight' => 15.0,   // Rounded up weight used for calculation
    'rate_weight' => 15.0,      // Rate tier weight
    'base_price' => 30.00,      // Base rate price
    'processing_fee' => 10.00,  // Processing fee
    'subtotal' => 40.00,        // Base price + processing fee
    'exchange_rate' => 1.25,    // Manifest exchange rate
    'total' => 50.00,           // Final calculated price
]
```

## Rate Matching Logic

The calculator uses the following priority order to find appropriate rates:

1. **Exact Weight Match**: Uses `Rate::forAirShipment($weight)` scope
2. **Legacy Fallback**: Searches for any air rate with matching weight
3. **Higher Weight Rate**: Finds the closest rate with higher weight capacity
4. **Exception**: Throws `AirRateNotFoundException` if no suitable rate found

## Weight Handling

- Package weights are automatically rounded **up** to the nearest whole number
- This follows standard air freight industry practices
- Example: 14.1 lbs → 15 lbs, 10.0 lbs → 10 lbs

## Exchange Rate Application

- Uses the manifest's `exchange_rate` field
- Falls back to 1.0 if exchange rate is null or zero
- Applied to the final calculation: `(base_price + processing_fee) * exchange_rate`

## Error Handling

### AirRateNotFoundException

Thrown when no suitable air rate is found for the package weight.

```php
try {
    $price = $calculator->calculateFreightPrice($package);
} catch (AirRateNotFoundException $e) {
    // Handle missing rate scenario
    $weight = $e->getWeight();
    $userMessage = $e->getUserMessage();
}
```

### InvalidArgumentException

Thrown for invalid input:
- Package doesn't belong to an air manifest
- Package weight is zero or negative

## Integration

### ManifestPackage Component

The `ManifestPackage` Livewire component automatically uses the `AirRateCalculator` for air packages:

```php
// In ManifestPackage.php
private function calculateAirFreightPrice(?Package $package = null): float
{
    if (!$package) {
        return $this->calculateAirFreightPriceLegacy();
    }

    try {
        $airRateCalculator = new AirRateCalculator();
        return $airRateCalculator->calculateFreightPrice($package);
    } catch (AirRateNotFoundException $e) {
        throw $e; // Re-throw for UI handling
    } catch (\Exception $e) {
        Log::error('Air freight price calculation failed: ' . $e->getMessage());
        return $this->calculateAirFreightPriceLegacy();
    }
}
```

### Package Model Methods

New method added to identify air packages:

```php
// Check if package belongs to air manifest
$package->isAirPackage(); // Returns boolean
```

## Rate Configuration

Air rates should be configured in the `rates` table with:

```php
[
    'type' => 'air',
    'weight' => 10,              // Weight tier in lbs
    'price' => 25.00,            // Base price per weight tier
    'processing_fee' => 5.00,    // Additional processing fee
    'min_cubic_feet' => null,    // Not used for air rates
    'max_cubic_feet' => null,    // Not used for air rates
]
```

## Testing

The service includes comprehensive unit tests covering:
- Basic freight price calculations
- Weight rounding behavior
- Exchange rate handling
- Rate matching fallbacks
- Error scenarios
- Rate breakdown accuracy

Run tests with:
```bash
php artisan test tests/Unit/AirRateCalculatorTest.php
php artisan test tests/Feature/AirRateCalculationTest.php
```

## Comparison with SeaRateCalculator

| Feature | AirRateCalculator | SeaRateCalculator |
|---------|-------------------|-------------------|
| **Measurement** | Weight (lbs) | Volume (cubic feet) |
| **Rounding** | Round up to whole number | Use exact decimal |
| **Rate Matching** | Exact weight or higher | Cubic feet range |
| **Calculation** | `(price + fee) * exchange_rate` | `((price + fee) * cubic_feet) * exchange_rate` |
| **Exception** | `AirRateNotFoundException` | `SeaRateNotFoundException` |

## PHP 7.4 Compatibility

The service is fully compatible with PHP 7.4:
- Uses traditional array syntax
- Compatible type hints
- No PHP 8+ specific features
- Follows Laravel 8.x conventions