import { useEffect, useRef, useState } from 'react';

let scriptPromise = null;
function loadGoogleMaps(apiKey) {
    if (window.google?.maps?.places) return Promise.resolve(window.google);
    if (scriptPromise) return scriptPromise;

    scriptPromise = new Promise((resolve, reject) => {
        const s = document.createElement('script');
        s.src = `https://maps.googleapis.com/maps/api/js?key=${apiKey}&libraries=places`;
        s.async = true;
        s.defer = true;
        s.onload = () => resolve(window.google);
        s.onerror = reject;
        document.head.appendChild(s);
    });
    return scriptPromise;
}

export default function GoogleMapPicker({
    apiKey,
    initialLat,
    initialLng,
    onChange,
    height = 320,
}) {
    const mapRef = useRef(null);
    const inputRef = useRef(null);
    const markerRef = useRef(null);
    const mapInstanceRef = useRef(null);
    const [ready, setReady] = useState(false);

    const defaultCenter = {
        lat: initialLat ? Number(initialLat) : 19.076,
        lng: initialLng ? Number(initialLng) : 72.8777,
    };

    const handleCurrentLocation = () => {
        if (!navigator.geolocation) {
            window.alert('Geolocation is not supported in this browser.');
            return;
        }

        navigator.geolocation.getCurrentPosition(
            ({ coords }) => {
                const lat = Number(coords.latitude);
                const lng = Number(coords.longitude);
                const loc = { lat, lng };

                if (mapInstanceRef.current) {
                    mapInstanceRef.current.panTo(loc);
                    mapInstanceRef.current.setZoom(16);
                }
                if (markerRef.current) {
                    markerRef.current.setPosition(loc);
                }

                // Premium reverse-geocoding feature to automatically populate address
                if (window.google?.maps?.Geocoder) {
                    const geocoder = new window.google.maps.Geocoder();
                    geocoder.geocode({ location: loc }, (results, status) => {
                        let address = null;
                        if (status === 'OK' && results && results[0]) {
                            address = results[0].formatted_address;
                            if (inputRef.current) {
                                inputRef.current.value = address;
                            }
                        }
                        onChange?.({ lat, lng, address });
                    });
                } else {
                    onChange?.({ lat, lng, address: null });
                }
            },
            (error) => {
                console.error('Error getting location:', error);
                window.alert('Location permission denied or unavailable. Please enable location services in your browser.');
            },
            { enableHighAccuracy: true, timeout: 10000 },
        );
    };

    useEffect(() => {
        if (!apiKey) return;
        loadGoogleMaps(apiKey).then((google) => {
            const map = new google.maps.Map(mapRef.current, {
                center: defaultCenter,
                zoom: initialLat ? 16 : 11,
                mapTypeControl: false,
                streetViewControl: false,
            });
            mapInstanceRef.current = map;

            const marker = new google.maps.Marker({
                map,
                position: defaultCenter,
                draggable: true,
            });
            markerRef.current = marker;

            const emit = (lat, lng, address = null) => {
                onChange?.({ lat, lng, address });
            };

            marker.addListener('dragend', () => {
                const p = marker.getPosition();
                emit(p.lat(), p.lng());
            });

            map.addListener('click', (e) => {
                const lat = e.latLng.lat();
                const lng = e.latLng.lng();
                marker.setPosition({ lat, lng });
                emit(lat, lng);
            });

            const ac = new google.maps.places.Autocomplete(inputRef.current, {
                fields: ['geometry', 'formatted_address'],
                componentRestrictions: { country: 'in' },
            });
            ac.addListener('place_changed', () => {
                const place = ac.getPlace();
                if (!place.geometry) return;
                const loc = place.geometry.location;
                map.panTo(loc);
                map.setZoom(16);
                marker.setPosition(loc);
                emit(loc.lat(), loc.lng(), place.formatted_address);
            });

            setReady(true);
        });
    }, [apiKey]);

    if (!apiKey) {
        return (
            <div className="p-4 text-sm text-red-600 border rounded">
                Google Maps key missing. Set GOOGLE_MAPS_API_KEY in .env.
            </div>
        );
    }

    return (
        <div>
            <div className="flex gap-2 mb-2">
                <div className="relative flex-1">
                    <input
                        ref={inputRef}
                        type="text"
                        placeholder="Search location..."
                        className="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm pr-8"
                    />
                    <div className="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                        <svg className="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fillRule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clipRule="evenodd" />
                        </svg>
                    </div>
                </div>
                <button
                    type="button"
                    onClick={handleCurrentLocation}
                    disabled={!ready}
                    className="inline-flex items-center gap-1.5 px-3.5 py-2 bg-indigo-50 border border-transparent rounded-md text-sm font-semibold text-indigo-700 hover:bg-indigo-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 transition-colors shadow-sm cursor-pointer"
                    title="Use current location"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor" className="w-4 h-4 text-indigo-600">
                        <path strokeLinecap="round" strokeLinejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                        <path strokeLinecap="round" strokeLinejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                    </svg>
                    Locate Me
                </button>
            </div>
            <div ref={mapRef} style={{ width: '100%', height }} className="rounded-lg border border-gray-200 shadow-inner" />
            {!ready && <div className="text-xs text-indigo-500 font-medium animate-pulse mt-1.5">Initializing map services…</div>}
        </div>
    );
}
