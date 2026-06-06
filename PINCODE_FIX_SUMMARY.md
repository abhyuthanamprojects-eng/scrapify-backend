# Pincode Capture Fix - Scrapify App

## Problem Identified
The warehouse availability API was not receiving the user's pincode, causing `service_availability.pincode` to be `null` in the response. This prevented proper warehouse matching for your location (110044 - New Delhi).

**Root Cause:** The pincode was never being extracted from the device's geolocation data and wasn't being sent to the backend API.

---

## Files Modified

### 1. **LocationService** (`lib/core/services/location_service.dart`)
**Changes:**
- Added pincode cache key constant: `_pincodeKey = 'cached_pincode'`
- Updated `getLocationName()` to extract postal code from `Placemark.postalCode`
- Updated `getCachedLocation()` to retrieve and return cached pincode
- Updated `getBestAvailableLocation()` to include pincode in resolved location
- Updated `_cacheLocation()` to accept and persist pincode to SharedPreferences
- Updated `CachedLocation` class to include `pincode` field

**Key Addition:**
```dart
final pincode = place.postalCode?.trim();  // Extract from geocoding result
```

---

### 2. **ServiceAvailability Model** (`lib/features/settings/domain/models/app_settings_model.dart`)
**Changes:**
- Added `pincode` field to `ServiceAvailability` class
- Updated `fromJson()` to deserialize pincode from API response
- Updated `toJson()` to serialize pincode when sending data

**Updated Class:**
```dart
class ServiceAvailability {
  final bool isServiceable;
  final String message;
  final String locationName;
  final String? pincode;  // NEW FIELD
  
  // ... constructor, fromJson, toJson updated
}
```

---

### 3. **SettingsRepository** (`lib/features/settings/domain/repositories/settings_repository.dart`)
**Changes:**
- Added `pincode` parameter to `getAppSettings()` method
- Updated request body to include pincode in API call
- Pincode is sent as `null` if empty to prevent null/empty string issues

**Updated Method:**
```dart
Future<ApiResponse<AppSettingsModel>> getAppSettings({
  double? latitude,
  double? longitude,
  String? locationName,
  String? pincode,  // NEW PARAMETER
  String? fcmToken,
}) async {
  // ...
  data: {
    'latitude': latitude,
    'longitude': longitude,
    'location_name': normalizedLocationName,
    'pincode': normalizedPincode.isNotEmpty ? normalizedPincode : null,
    'fcm_token': (fcmToken ?? '').trim(),
  }
}
```

---

### 4. **SettingsProvider** (`lib/features/settings/providers/settings_provider.dart`)
**Changes:**
- Added `pincode` parameter to `syncSettings()` method
- Updated logic to resolve pincode from LocationService if not provided
- Passes pincode to repository's `getAppSettings()` method

**Updated Logic:**
```dart
String? resolvedPincode = (pincode ?? '').trim().isEmpty ? null : pincode?.trim();

if (resolvedLatitude == null || resolvedLongitude == null) {
  final locationService = LocationService();
  final bestLocation = await locationService.getBestAvailableLocation();
  // ...
  if (resolvedPincode == null) {
    resolvedPincode = bestLocation?.pincode?.trim();  // NEW
  }
}

final response = await repository.getAppSettings(
  latitude: resolvedLatitude,
  longitude: resolvedLongitude,
  locationName: resolvedLocationName,
  pincode: resolvedPincode,  // NEW PARAMETER
  fcmToken: fcmToken,
);
```

---

## How It Works Now

1. **Location Capture Flow:**
   - User's device location is obtained (latitude, longitude)
   - Geocoding library converts coordinates to a `Placemark` object
   - `LocationService` now extracts the postal code from `Placemark.postalCode`
   - Both location name and pincode are cached in SharedPreferences

2. **API Request:**
   - When syncing app settings, the cached pincode is retrieved
   - Pincode is included in the API request body to `/api/app-settings`
   - API can now match the user's pincode with warehouse service areas

3. **Response Handling:**
   - Backend returns `service_availability.pincode` (no longer null)
   - App displays correct warehouse availability based on pincode matching

---

## Testing Checklist

After deploying these changes:

- [ ] Test with location 110044 (New Delhi)
- [ ] Verify pincode is extracted from geocoding
- [ ] Check API logs to confirm pincode is being sent
- [ ] Verify `service_availability.pincode` is no longer `null`
- [ ] Test warehouse matching with the pincode
- [ ] Verify location caching persists pincode correctly
- [ ] Test with different locations/pincodes

---

## API Request Example

After fix, the API request will look like:

```json
{
  "latitude": 28.5049193,
  "longitude": 77.3198232,
  "location_name": "New Delhi, Delhi",
  "pincode": "110044",
  "fcm_token": "fnDNJhl_RAGYgaki1pOGYh:APA91bF..."
}
```

Previous request was missing the `pincode` field entirely.

---

## Backend Requirement

Ensure your Laravel backend's `app-settings` endpoint:
- Accepts the `pincode` parameter
- Uses pincode for warehouse matching/filtering
- Returns `pincode` in the `service_availability` response object

---

## Backwards Compatibility

✅ These changes are backwards compatible:
- Pincode is optional (sent as `null` if not available)
- Existing code that doesn't pass pincode will still work
- Empty/missing pincodes don't break the flow

---

## Notes

- Pincode is cached with coordinates for faster future requests
- If geocoding fails to get pincode, the user can still proceed with location-based service
- The fix handles both fresh location requests and cached location scenarios
