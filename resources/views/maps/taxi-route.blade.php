@extends('layouts.app')

@section('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css" />
<style>
    #map {
        height: 500px;
        width: 100%;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }
    .route-info {
        margin-top: 20px;
        padding: 15px;
        background-color: #f8f9fa;
        border-radius: 5px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    }
    .search-box {
        margin-bottom: 15px;
    }
    .location-input {
        margin-bottom: 10px;
    }
</style>
@endsection

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header">Taxi Route Planner</div>

                <div class="card-body">
                    <div class="search-box">
                        <div class="location-input">
                            <label for="start-location" class="form-label">Starting Point</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="start-location" placeholder="Enter starting location">
                                <button class="btn btn-outline-secondary" type="button" id="use-current-location-start">
                                    <i class="fas fa-map-marker-alt"></i> Current Location
                                </button>
                            </div>
                        </div>

                        <div class="location-input">
                            <label for="end-location" class="form-label">Destination</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="end-location" placeholder="Enter destination">
                                <button class="btn btn-outline-secondary" type="button" id="search-destination">
                                    <i class="fas fa-search"></i> Search
                                </button>
                            </div>
                        </div>

                        <div class="d-grid gap-2 mt-3">
                            <button class="btn btn-primary" type="button" id="calculate-route">Calculate Route</button>
                        </div>
                    </div>

                    <div id="map"></div>

                    <div class="route-info mt-3" id="route-info" style="display: none;">
                        <h5>Route Information</h5>
                        <div class="row">
                            <div class="col-md-4">
                                <p><strong>Distance:</strong> <span id="route-distance">0</span> km</p>
                            </div>
                            <div class="col-md-4">
                                <p><strong>Duration:</strong> <span id="route-duration">0</span> min</p>
                            </div>
                            <div class="col-md-4">
                                <p><strong>Estimated Fare:</strong> $<span id="route-fare">0</span></p>
                            </div>
                        </div>
                        <div id="route-steps" class="mt-3">
                            <h6>Directions:</h6>
                            <ol id="directions-list"></ol>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://unpkg.com/leaflet@1.9.3/dist/leaflet.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const mapTilesKey = "{{ $mapTilesKey }}";
        const routingKey = "{{ $routingKey }}";
        const geocodingKey = "{{ $geocodingKey }}";

        // Initialize map
        const map = L.map('map').setView([0, 0], 2);

        // Add tile layer
        L.tileLayer('https://maps.geoapify.com/v1/tile/{style}/{z}/{x}/{y}.png?apiKey={apiKey}', {
            attribution: 'Powered by <a href="https://www.geoapify.com/" target="_blank">Geoapify</a> | © OpenStreetMap <a href="https://www.openstreetmap.org/copyright" target="_blank">contributors</a>',
            apiKey: mapTilesKey,
            style: 'osm-bright',
            maxZoom: 20
        }).addTo(map);

        // Variables to store markers and route
        let startMarker = null;
        let endMarker = null;
        let routeLine = null;

        // Get current location
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(position) {
                const lat = position.coords.latitude;
                const lon = position.coords.longitude;

                map.setView([lat, lon], 13);

                // Add marker for current location
                startMarker = L.marker([lat, lon]).addTo(map);
                startMarker.bindPopup('Your Location').openPopup();

                // Reverse geocode to get address
                reverseGeocode(lat, lon, function(address) {
                    document.getElementById('start-location').value = address;
                });
            });
        }

        // Event listeners
        document.getElementById('use-current-location-start').addEventListener('click', function() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(position) {
                    const lat = position.coords.latitude;
                    const lon = position.coords.longitude;

                    map.setView([lat, lon], 13);

                    if (startMarker) {
                        map.removeLayer(startMarker);
                    }

                    startMarker = L.marker([lat, lon]).addTo(map);
                    startMarker.bindPopup('Starting Point').openPopup();

                    reverseGeocode(lat, lon, function(address) {
                        document.getElementById('start-location').value = address;
                    });
                });
            }
        });

        document.getElementById('search-destination').addEventListener('click', function() {
            const destination = document.getElementById('end-location').value;

            if (destination.trim() === '') {
                alert('Please enter a destination');
                return;
            }

            geocodeAddress(destination, function(result) {
                if (result) {
                    const lat = result.lat;
                    const lon = result.lon;

                    if (endMarker) {
                        map.removeLayer(endMarker);
                    }

                    endMarker = L.marker([lat, lon]).addTo(map);
                    endMarker.bindPopup('Destination').openPopup();

                    // Adjust map to show both markers
                    if (startMarker) {
                        const bounds = L.latLngBounds(startMarker.getLatLng(), endMarker.getLatLng());
                        map.fitBounds(bounds, { padding: [50, 50] });
                    } else {
                        map.setView([lat, lon], 13);
                    }
                }
            });
        });

        document.getElementById('calculate-route').addEventListener('click', function() {
            if (!startMarker || !endMarker) {
                alert('Please select both starting point and destination');
                return;
            }

            const startLat = startMarker.getLatLng().lat;
            const startLon = startMarker.getLatLng().lng;
            const endLat = endMarker.getLatLng().lat;
            const endLon = endMarker.getLatLng().lng;

            calculateRoute(startLat, startLon, endLat, endLon);
        });

        // Helper functions
        function reverseGeocode(lat, lon, callback) {
            fetch('/api/map/reverse-geocode', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ lat, lon })
            })
            .then(response => response.json())
            .then(data => {
                if (data.features && data.features.length > 0) {
                    const address = data.features[0].properties.formatted;
                    callback(address);
                }
            })
            .catch(error => console.error('Error:', error));
        }

        function geocodeAddress(address, callback) {
            fetch('/api/map/geocode', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ address })
            })
            .then(response => response.json())
            .then(data => {
                if (data.features && data.features.length > 0) {
                    const result = {
                        lat: data.features[0].properties.lat,
                        lon: data.features[0].properties.lon,
                        address: data.features[0].properties.formatted
                    };
                    callback(result);
                } else {
                    alert('Location not found');
                    callback(null);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                callback(null);
            });
        }

        function calculateRoute(fromLat, fromLon, toLat, toLon) {
            fetch('/api/map/route', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    from_lat: fromLat,
                    from_lon: fromLon,
                    to_lat: toLat,
                    to_lon: toLon,
                    mode: 'drive'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.features && data.features.length > 0) {
                    displayRoute(data);
                } else {
                    alert('Could not calculate route');
                }
            })
            .catch(error => console.error('Error:', error));
        }

        function displayRoute(routeData) {
            // Clear previous route if exists
            if (routeLine) {
                map.removeLayer(routeLine);
            }

            // Get route geometry
            const routeGeometry = routeData.features[0].geometry.coordinates;

            // Convert coordinates from [lon, lat] to [lat, lon] for Leaflet
            const routePoints = routeGeometry.map(coord => [coord[1], coord[0]]);

            // Create and add route line
            routeLine = L.polyline(routePoints, {
                color: '#3388ff',
                weight: 6,
                opacity: 0.7
            }).addTo(map);

            // Fit map to show the entire route
            map.fitBounds(routeLine.getBounds(), { padding: [50, 50] });

            // Display route information
            const properties = routeData.features[0].properties;
            const distance = (properties.distance / 1000).toFixed(2); // Convert to km
            const duration = Math.round(properties.time / 60); // Convert to minutes

            // Calculate estimated fare (example: $2 base + $1.5 per km)
            const fare = (2 + (distance * 1.5)).toFixed(2);

            document.getElementById('route-distance').textContent = distance;
            document.getElementById('route-duration').textContent = duration;
            document.getElementById('route-fare').textContent = fare;

            // Display route steps/directions if available
            const directionsList = document.getElementById('directions-list');
            directionsList.innerHTML = '';

            if (properties.legs && properties.legs[0].steps) {
                properties.legs[0].steps.forEach(step => {
                    const li = document.createElement('li');
                    li.textContent = step.instruction;
                    directionsList.appendChild(li);
                });
            }

            // Show route info section
            document.getElementById('route-info').style.display = 'block';
        }
    });
</script>
@endsection
