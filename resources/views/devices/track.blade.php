<!DOCTYPE html>
<html>

<head>
    <title>Vehicle Tracking</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <style>
        #map {
            height: 600px;
        }
        #loading {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 1000;
        }
    </style>
    <script>
        const isProduction = window.location.hostname !== 'localhost';
        const apiBaseUrl = isProduction ? 'https://gps.pistatapp.ir' : 'http://localhost:8000';

        let polyline = null;
        let markers = [];
        let map;

        document.addEventListener('DOMContentLoaded', async () => {
            await loadDevices();

            map = L.map('map').setView([35.6892, 51.3890], 13);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

            // Set default date to today
            document.getElementById('date').valueAsDate = new Date();

            // Load data for the first device and today's date
            loadData();

            // Fetch latest data every 5 seconds
            setInterval(fetchLatestData, 5000);
        });

        async function loadDevices() {
            try {
                const response = await fetch(`${apiBaseUrl}/api/devices`);
                const devices = await response.json();
                const deviceSelect = document.getElementById('device-select');

                devices.forEach(device => {
                    const option = document.createElement('option');
                    option.value = device.imei;
                    textContent = device.name + ' (' + device.imei + ')';
                    option.textContent = textContent;
                    deviceSelect.appendChild(option);
                });

                deviceSelect.addEventListener('change', loadData);
                document.getElementById('date').addEventListener('change', loadData);
            } catch (error) {
                console.error('Error loading devices:', error);
            }
        }

        async function loadData() {
            const imei = document.getElementById('device-select').value;
            const date = document.getElementById('date').value;

            if (!imei) {
                console.warn('No device selected');
                return;
            }

            document.getElementById('loading').style.display = 'block';

            try {
                const response = await fetch(`${apiBaseUrl}/api/devices/${imei}/track?date=${date}`);
                const data = await response.json();

                displayPath(data.path);
                displayStatistics(data.statistics);
            } catch (error) {
                console.error('Error loading data:', error);
                displayStatistics(null); // Set statistics to 0 in case of error
            } finally {
                document.getElementById('loading').style.display = 'none';
            }
        }

        async function fetchLatestData() {
            const imei = document.getElementById('device-select').value;
            if (!imei) {
                return;
            }

            try {
                const response = await fetch(`${apiBaseUrl}/api/devices/${imei}/latest`);
                const data = await response.json();

                appendLatestPoint(data.latest_point);
                displayStatistics(data.statistics);
            } catch (error) {
                console.error('Error fetching latest data:', error);
            }
        }

        function displayPath(path) {
            // Clear existing path and markers
            if (polyline) map.removeLayer(polyline);
            markers.forEach(marker => map.removeLayer(marker));
            markers = [];

            // Draw new path
            const coordinates = path.map(point => point.coordinates);
            polyline = L.polyline(coordinates, {
                color: 'blue'
            }).addTo(map);

            // Add markers for stops
            path.forEach(point => {
                if (point.status === 0 || point.speed === 0) {
                    const marker = L.marker(point.coordinates)
                        .bindPopup(`Time: ${point.time}<br>Speed: ${point.speed} km/h`)
                        .addTo(map);
                    markers.push(marker);
                }
            });

            // Fit map to show all points
            if (coordinates.length > 0) {
                map.fitBounds(polyline.getBounds());
            }
        }

        function appendLatestPoint(point) {
            if (!polyline) {
                polyline = L.polyline([], { color: 'blue' }).addTo(map);
            }

            const coordinates = point.coordinates;
            polyline.addLatLng(coordinates);

            if (point.status === 0 || point.speed === 0) {
                const marker = L.marker(coordinates)
                    .bindPopup(`Time: ${point.time}<br>Speed: ${point.speed} km/h`)
                    .addTo(map);
                markers.push(marker);
            }

            map.fitBounds(polyline.getBounds());
        }

        function displayStatistics(stats) {
            if (!stats) {
                stats = {
                    total_distance: 0,
                    stoppage_count: 0,
                    stoppage_duration: 0,
                    moving_duration: 0,
                    max_speed: 0
                };
            }

            const html = `
                <div class="row">
                    <div class="col-lg-4 mb-2">
                        <div class="card text-white bg-primary">
                            <div class="card-body">
                                <p class="card-text">Total Distance: ${(stats.total_distance/1000).toFixed(2)} km</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 mb-2">
                        <div class="card text-white bg-success">
                            <div class="card-body">
                                <p class="card-text">Stoppage Count: ${stats.stoppage_count}</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 mb-2">
                        <div class="card text-white bg-danger">
                            <div class="card-body">
                                <p class="card-text">Stoppage Duration: ${Math.floor(stats.stoppage_duration/60)} minutes</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 mb-2">
                        <div class="card text-white bg-warning">
                            <div class="card-body">
                                <p class="card-text">Moving Duration: ${Math.floor(stats.moving_duration/60)} minutes</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 mb-2">
                        <div class="card text-white bg-info">
                            <div class="card-body">
                                <p class="card-text">Max Speed: ${stats.max_speed} km/h</p>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            document.getElementById('statistics').innerHTML = html;
        }
    </script>
</head>

<body>
    <div class="container mt-4">
        <div class="row mb-3">
            <div class="col-md-6">
                <select id="device-select" class="form-control">
                    <option value="">Select Device</option>
                </select>
            </div>
            <div class="col-md-6">
                <input type="date" id="date" class="form-control">
            </div>
        </div>

        <div id="map" class="mb-4"></div>

        <div class="stats">
            <h3 class="mb-3">Statistics</h3>
            <div id="statistics"></div>
        </div>

        <div id="loading">
            <div class="spinner-border text-primary" role="status">
                <span class="sr-only">Loading...</span>
            </div>
        </div>
    </div>
</body>

</html>
