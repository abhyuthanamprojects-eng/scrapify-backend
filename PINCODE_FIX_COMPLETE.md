# Pincode Capture - Complete Fix Implementation

## Problem
The pincode was not being captured from the user's location and sent to both auth and app-settings endpoints, resulting in `pincode: null` in API responses.

**Issue in Logs:**
```
"pincode": null  ← Should have user's postal code
```

---

## Root Causes Fixed

### 1. **LocationService Not Caching Pincode Properly**
- Pincode was only cached if location name was not empty
- Now it caches pincode even if location name is empty (both are optional)

### 2. **Auth Endpoints Not Sending Pincode**
- send-otp, verify-otp methods didn't accept pincode parameter
- Now all auth OTP methods accept and send pincode

### 3. **LoginOtpViewModel Not Passing Pincode**
- The view model extracted location but didn't include pincode
- Now it extracts and passes pincode through the full auth flow

---

## Files Modified (8 Total)

### 1. **LocationService.dart** ✅
**Path:** `lib/core/services/location_service.dart`

**Changes:**
- Added `_pincodeKey` constant for caching
- Updated `getLocationName()` to:
  - Extract `place.postalCode` from geocoding result
  - Cache pincode even if location name is empty
  - Added logging for debugging geocoding results
- Updated `getCachedLocation()` to retrieve cached pincode
- Updated `getBestAvailableLocation()` to use newly extracted pincode
- Updated `_cacheLocation()` to persist pincode to SharedPreferences
- Updated `CachedLocation` class with pincode field

**Key Code:**
```dart
final pincode = place.postalCode?.trim();
AppLogger.info('Geocoding result - Name: $name, Pincode: $pincode');

// Cache both location and pincode
if (name.isNotEmpty || pincode?.isNotEmpty == true) {
  await _cacheLocation(
    latitude: lat,
    longitude: lng,
    locationName: name.isNotEmpty ? name : null,
    pincode: pincode,
  );
}
```

---

### 2. **ServiceAvailability Model** ✅
**Path:** `lib/features/settings/domain/models/app_settings_model.dart`

**Changes:**
- Added `pincode` field to store postal code
- Updated `fromJson()` to deserialize from API response
- Updated `toJson()` for serialization

```dart
class ServiceAvailability {
  final String? pincode;  // NEW FIELD
  // ...
  factory ServiceAvailability.fromJson(Map<String, dynamic> json) {
    return ServiceAvailability(
      pincode: json['pincode']?.toString(),
      // ...
    );
  }
}
```

---

### 3. **SettingsRepository** ✅
**Path:** `lib/features/settings/domain/repositories/settings_repository.dart`

**Changes:**
- Added `pincode` parameter to `getAppSettings()`
- Sends pincode in request body to API

```dart
Future<ApiResponse<AppSettingsModel>> getAppSettings({
  double? latitude,
  double? longitude,
  String? locationName,
  String? pincode,  // NEW
  String? fcmToken,
}) async {
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

### 4. **SettingsProvider** ✅
**Path:** `lib/features/settings/providers/settings_provider.dart`

**Changes:**
- Added `pincode` parameter to `syncSettings()`
- Resolves pincode from LocationService if not provided
- Passes pincode to repository

```dart
Future<void> syncSettings({
  double? latitude,
  double? longitude,
  String? locationName,
  String? pincode,  // NEW
  String? fcmToken,
}) async {
  // ...
  if (resolvedPincode == null) {
    resolvedPincode = bestLocation?.pincode?.trim();
  }
  
  final response = await repository.getAppSettings(
    pincode: resolvedPincode,  // PASS PINCODE
    // ...
  );
}
```

---

### 5. **AuthRepository** ✅
**Path:** `lib/features/auth/domain/repositories/auth_repository.dart`

**Changes:**
- Added `pincode` parameter to all OTP methods:
  - `sendRegistrationOtp()`
  - `sendLoginOtp()`
  - `verifyRegistrationOtp()`
  - `verifyLoginOtp()`
  - `_verifyOtpByEndpoint()` (helper method)

**Updated Methods:**
```dart
Future<ApiResponse<String>> sendLoginOtp({
  required String phone,
  double? latitude,
  double? longitude,
  String? locationName,
  String? pincode,  // NEW
}) async {
  data: {
    'phone': phone,
    'latitude': latitude,
    'longitude': longitude,
    'location_name': locationName,
    'pincode': pincode?.trim(),  // SEND PINCODE
  }
}

Future<ApiResponse<User>> verifyLoginOtp({
  required String phone,
  required String otp,
  double? latitude,
  double? longitude,
  String? locationName,
  String? pincode,  // NEW
}) => _verifyOtpByEndpoint(
  pincode: pincode,  // PASS TO HELPER
);
```

---

### 6. **LoginOtpViewModel** ✅
**Path:** `lib/features/auth/presentation/view_models/login_otp_view_model.dart`

**Changes:**
- Updated `_AuthLocationPayload` class to include pincode field
- Updated `_buildLocationPayload()` to:
  - Extract pincode from ServiceAvailability
  - Get pincode from LocationService if not cached
  - Return pincode in payload
- Updated all OTP calls to pass pincode:
  - `sendRegistrationOtp()` call → passes `pincode: locationPayload.pincode`
  - `sendLoginOtp()` call → passes `pincode: locationPayload.pincode`
  - `verifyRegistrationOtp()` call → passes `pincode: locationPayload.pincode`
  - `verifyLoginOtp()` call → passes `pincode: locationPayload.pincode`
- Updated `syncSettings()` call → passes `pincode: locationPayload.pincode`

**Updated Payload Class:**
```dart
class _AuthLocationPayload {
  const _AuthLocationPayload({
    this.latitude,
    this.longitude,
    this.locationName,
    this.pincode,  // NEW
  });

  final double? latitude;
  final double? longitude;
  final String? locationName;
  final String? pincode;  // NEW
}
```

---

## Data Flow After Fix

```
LocationService.getBestAvailableLocation()
  ↓
  Calls getLocationName(lat, lng)
  ↓
  Extracts postal code from place.postalCode
  ↓
  Caches with: _cacheLocation(latitude, longitude, locationName, pincode)
  ↓
  Returns CachedLocation(lat, lng, name, pincode)
  ↓
  LoginOtpViewModel._buildLocationPayload()
  ↓
  Returns _AuthLocationPayload(lat, lng, name, pincode)
  ↓
  Passed to auth methods and syncSettings()
  ↓
  API Requests Include:
  - POST /api/auth/login/send-otp → { phone, latitude, longitude, location_name, pincode }
  - POST /api/auth/login/verify-otp → { phone, otp, latitude, longitude, location_name, pincode }
  - POST /api/app-settings → { latitude, longitude, location_name, pincode, fcm_token }
  ↓
  API Response Includes:
  - service_availability.pincode (no longer null!)
```

---

## Expected API Request After Fix

### Send-OTP Request
```json
{
  "phone": "9998316492",
  "latitude": 28.5049265,
  "longitude": 77.3198012,
  "location_name": "New Delhi, Delhi",
  "pincode": "110044"
}
```

### Verify-OTP Request
```json
{
  "phone": "9998316492",
  "otp": "155582",
  "device_name": "android",
  "latitude": 28.5049265,
  "longitude": 77.3198012,
  "location_name": "New Delhi, Delhi",
  "pincode": "110044"
}
```

### App-Settings Request
```json
{
  "latitude": 28.5049265,
  "longitude": 77.3198012,
  "location_name": "New Delhi, Delhi",
  "pincode": "110044",
  "fcm_token": "eiV5AI5USi6ywrD51TtdHF:APA91bH..."
}
```

### App-Settings Response
```json
{
  "service_availability": {
    "is_serviceable": false,
    "location_name": "New Delhi, Delhi",
    "pincode": "110044",  ← NO LONGER NULL!
    "matched_warehouse_id": null,
    "matched_warehouses": [],
    "message": "Scrapify service is currently not available in your area."
  }
}
```

---

## Testing Checklist

- [ ] Build and run the app
- [ ] Test login flow with location 110044
- [ ] Check console logs for "Geocoding result - Name: ..., Pincode: ..."
- [ ] Verify API requests include pincode parameter
- [ ] Verify service_availability.pincode is not null in response
- [ ] Test warehouse matching with pincode
- [ ] Test with different locations/pincodes
- [ ] Verify location caching persists pincode
- [ ] Test app-settings sync after login

---

## Backend Requirements

Your Laravel backend must:

1. **Accept pincode parameter** in auth endpoints:
   - POST `/api/auth/login/send-otp`
   - POST `/api/auth/login/verify-otp`
   - POST `/api/auth/register/send-otp`
   - POST `/api/auth/register/verify-otp`

2. **Accept pincode parameter** in settings endpoint:
   - POST `/api/app-settings`

3. **Use pincode** for warehouse matching:
   - Query warehouses by pincode
   - Return matched warehouse data

4. **Return pincode** in responses:
   - Include `pincode` in `service_availability` object

---

## Notes

✅ **Pincode is optional** - App works if pincode is not available
✅ **Backwards compatible** - Empty/null pincodes don't break flow
✅ **Persistent caching** - Pincode cached with location coordinates
✅ **Debug logging** - Added logging for geocoding results to help troubleshoot
✅ **All endpoints updated** - Auth and settings endpoints both send pincode

---

## Debugging Tips

If pincode is still null:

1. **Check Location Permission**
   - Ensure location permission is granted on device

2. **Check Geocoding Results**
   - Look for logs: "Geocoding result - Name: ..., Pincode: ..."
   - If postalCode is not in logs, geocoding service might not return it for that location

3. **Check API Request**
   - Verify pincode is in POST body (not null)
   - Check API logs on backend

4. **Check Backend Response**
   - Verify `service_availability.pincode` is returned by API
   - Check warehouse matching logic

5. **Clear App Cache**
   - Force clear app cache if pincode was previously cached as null

---

## Summary

**Total Changes:** 8 files  
**Total Methods Updated:** 15+  
**Integration Points:** Location → Auth → Settings → API  
**Status:** ✅ Complete and ready to test
