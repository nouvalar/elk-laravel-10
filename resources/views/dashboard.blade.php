@extends('layout.main')

@section('content')

    <div class="page">
        <header class="navbar navbar-expand-sm navbar-light d-print-none">
            <div class="container-xl">
                <h1 class="navbar-brand navbar-brand-autodark d-none-navbar-horizontal pe-0 pe-md-3">
                    <a href="#">
                        <img src="{{ asset('assets/img/asyst.png') }}" width="110" height="32" alt="Logo"
                            class="navbar-brand-image" />
                    </a>
                </h1>
                <div class="navbar-nav flex-row order-md-last">
                    <div class="nav-item dropdown">
                        <a href="#" class="nav-link dropdown-toggle d-flex align-items-center lh-1 text-reset p-0"
                            id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="avatar avatar-sm" style="background-image: url(...)"></span>
                            <div class="d-none d-xl-block ps-2">
                                <div>{{ Auth::user()->name }}</div>
                            </div>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                            <li><a href="/" class="dropdown-item">Setting</a></li>
                            <li>
                                <form action="{{ route('logout') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="dropdown-item">Logout</button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </header>

        <div class="page-wrapper">
            <div class="page-body">
                <div class="container-fluid">
                    <div class="row">
                        <!-- CPU Usage Chart -->
                        <div class="col-xl-6 col-lg-6">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="m-0 font-weight-bold text-primary">CPU Usage</h6>
                                </div>
                                <div class="card-body">
                                    <div id="cpuChart"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Memory Usage Chart -->
                        <div class="col-xl-6 col-lg-6">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="m-0 font-weight-bold text-primary">Memory Usage</h6>
                                </div>
                                <div class="card-body">
                                    <div id="memoryChart"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- HTTP Methods Chart -->
                        <div class="col-xl-4 col-lg-4">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="m-0 font-weight-bold text-primary">HTTP Methods Distribution</h6>
                                </div>
                                <div class="card-body">
                                    <div id="httpMethodChart"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Log Levels Chart -->
                        <div class="col-xl-4 col-lg-4">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="m-0 font-weight-bold text-primary">Log Levels Distribution</h6>
                                </div>
                                <div class="card-body">
                                    <div id="logLevelChart"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Disk Usage Chart -->
                        <div class="col-xl-4 col-lg-4">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="m-0 font-weight-bold text-primary">Disk Usage</h6>
                                </div>
                                <div class="card-body">
                                    <div id="diskChart"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Logs Section -->
                    <div class="row">
                        <!-- Message Trends Chart -->
                        <div class="col-12 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="m-0 font-weight-bold text-primary">Message Trends</h6>
                                </div>
                                <div class="card-body">
                                    <div id="messageTrendsChart"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Recent Logs Tabs -->
                        <div class="col-12">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <ul class="nav nav-tabs card-header-tabs">
                                        <li class="nav-item">
                                            <a class="nav-link active" id="detailed-tab" data-bs-toggle="tab" href="#detailed">Detailed View</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" id="simple-tab" data-bs-toggle="tab" href="#simple">Simple View</a>
                                        </li>
                                    </ul>
                                    <div class="float-end mt-2">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="realTimeSwitch">
                                            <label class="form-check-label" for="realTimeSwitch">Real-time Updates</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="tab-content">
                                        <!-- Detailed View -->
                                        <div class="tab-pane fade show active" id="detailed">
                                            <div class="table-responsive">
                                                <table class="table">
                                                    <thead>
                                                        <tr>
                                                            <th>Timestamp</th>
                                                            <th>Message</th>
                                                            <th>Log Level</th>
                                                            <th>HTTP Method</th>
                                                            <th>Client IP</th>
                                                            <th>Request URL</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="detailedLogsTableBody">
                                                        @forelse($logs['hits']['hits'] as $log)
                                                        <tr>
                                                            <td>{{ isset($log['_source']['@timestamp']) ? \Carbon\Carbon::parse($log['_source']['@timestamp'])->format('Y-m-d H:i:s') : 'N/A' }}</td>
                                                            <td>{{ Str::limit($log['_source']['message'] ?? 'N/A', 100) }}</td>
                                                            <td>
                                                                <span class="badge bg-{{ 
                                                                    isset($log['_source']['loglevel']) ? 
                                                                        (strtoupper($log['_source']['loglevel']) == 'ERROR' ? 'danger' : 
                                                                        (strtoupper($log['_source']['loglevel']) == 'WARNING' ? 'warning' : 
                                                                        (strtoupper($log['_source']['loglevel']) == 'INFO' ? 'info' : 'secondary'))) 
                                                                    : 'secondary' 
                                                                }}">
                                                                    {{ $log['_source']['loglevel'] ?? 'N/A' }}
                                                                </span>
                                                            </td>
                                                            <td>{{ $log['_source']['http_method'] ?? 'N/A' }}</td>
                                                            <td>{{ $log['_source']['client_ip'] ?? 'N/A' }}</td>
                                                            <td>{{ $log['_source']['request_url'] ?? 'N/A' }}</td>
                                                        </tr>
                                                        @empty
                                                        <tr>
                                                            <td colspan="6" class="text-center">No logs available</td>
                                                        </tr>
                                                        @endforelse
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>

                                        <!-- Simple View with Chart -->
                                        <div class="tab-pane fade" id="simple">
                                            <div class="row">
                                                <!-- Timeline Chart -->
                                                <div class="col-12 mb-4">
                                                    <div id="timelineChart"></div>
                                                </div>
                                                <!-- Simple Table -->
                                                <div class="col-12">
                                                    <div class="table-responsive">
                                                        <table class="table">
                                                            <thead>
                                                                <tr>
                                                                    <th>Timestamp</th>
                                                                    <th>Message</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody id="simpleLogsTableBody">
                                                                @forelse($logs['hits']['hits'] as $log)
                                                                <tr>
                                                                    <td>{{ isset($log['_source']['@timestamp']) ? \Carbon\Carbon::parse($log['_source']['@timestamp'])->format('Y-m-d H:i:s') : 'N/A' }}</td>
                                                                    <td>{{ Str::limit($log['_source']['message'] ?? 'N/A', 100) }}</td>
                                                                </tr>
                                                                @empty
                                                                <tr>
                                                                    <td colspan="2" class="text-center">No messages available</td>
                                                                </tr>
                                                                @endforelse
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            var dropdownElement = document.getElementById('navbarDropdown');
            if (dropdownElement) {
                dropdownElement.addEventListener('click', function (event) {
                    event.preventDefault(); // Mencegah navigasi default
                    var dropdown = new bootstrap.Dropdown(dropdownElement);
                    dropdown.toggle();
                });
            }
        });
    </script>    

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Deklarasi variabel chart global
        let httpMethodChart;
        let logLevelChart;
        let timelineChart;
        let cpuChart;
        let memoryChart;
        let diskChart;

        // CPU Usage Chart
        const cpuData = @json($metricbeat['aggregations']['cpu_usage']['buckets'] ?? []);
        const cpuOptions = {
            series: [{
                name: 'CPU Usage',
                data: cpuData.map(item => ({
                    x: new Date(item.key),
                    y: ((item.cpu?.value ?? 0) * 100).toFixed(2)
                }))
            }],
            chart: {
                type: 'line',
                height: 350
            },
            xaxis: {
                type: 'datetime'
            },
            yaxis: {
                labels: {
                    formatter: function(val) {
                        return val.toFixed(2) + '%';
                    }
                }
            },
            noData: {
                text: 'No CPU Usage Data Available'
            }
        };
        cpuChart = new ApexCharts(document.querySelector("#cpuChart"), cpuOptions);
        cpuChart.render();

        // Memory Usage Chart
        const memoryData = @json($metricbeat['aggregations']['memory_usage']['buckets'] ?? []);
        const memoryOptions = {
            series: [{
                name: 'Memory Usage',
                data: memoryData.map(item => ({
                    x: new Date(item.key),
                    y: ((item.memory?.value ?? 0) * 100).toFixed(2)
                }))
            }],
            chart: {
                type: 'line',
                height: 350
            },
            xaxis: {
                type: 'datetime'
            },
            yaxis: {
                labels: {
                    formatter: function(val) {
                        return val.toFixed(2) + '%';
                    }
                }
            },
            noData: {
                text: 'No Memory Usage Data Available'
            }
        };
        memoryChart = new ApexCharts(document.querySelector("#memoryChart"), memoryOptions);
        memoryChart.render();

        // HTTP Methods Chart
        const httpMethodData = @json($httpMethodStats['aggregations']['http_methods']['buckets'] ?? []);
        const httpMethodOptions = {
            series: httpMethodData.map(item => item.doc_count ?? 0),
            labels: httpMethodData.map(item => item.key ?? 'Unknown'),
            chart: {
                type: 'donut',
                height: 350
            },
            plotOptions: {
                pie: {
                    donut: {
                        size: '70%'
                    }
                }
            },
            noData: {
                text: 'No HTTP Methods Data Available'
            }
        };
        httpMethodChart = new ApexCharts(document.querySelector("#httpMethodChart"), httpMethodOptions);
        httpMethodChart.render();

        // Log Levels Chart
        const logLevelData = @json($httpMethodStats['aggregations']['log_levels']['buckets'] ?? []);
        const logLevelOptions = {
            series: logLevelData.map(item => item.doc_count ?? 0),
            labels: logLevelData.map(item => item.key ?? 'Unknown'),
            chart: {
                type: 'donut',
                height: 350
            },
            plotOptions: {
                pie: {
                    donut: {
                        size: '70%'
                    }
                }
            },
            colors: ['#28a745', '#ffc107', '#dc3545', '#6c757d'],
            noData: {
                text: 'No Log Levels Data Available'
            }
        };
        logLevelChart = new ApexCharts(document.querySelector("#logLevelChart"), logLevelOptions);
        logLevelChart.render();

        // Disk Usage Chart
        fetch('/metrics')
            .then(response => response.json())
            .then(data => {
                const diskOptions = {
                    series: [(data.aggregations?.disk_usage?.value ?? 0) * 100],
                    chart: {
                        type: 'radialBar',
                        height: 350
                    },
                    plotOptions: {
                        radialBar: {
                            hollow: {
                                size: '70%',
                            },
                            dataLabels: {
                                show: true,
                                name: {
                                    show: true,
                                    fontSize: '16px',
                                    fontFamily: undefined,
                                    color: undefined,
                                    offsetY: -10
                                },
                                value: {
                                    show: true,
                                    fontSize: '30px',
                                    fontFamily: undefined,
                                    color: undefined,
                                    offsetY: 5,
                                    formatter: function (val) {
                                        return val.toFixed(2) + '%';
                                    }
                                }
                            }
                        }
                    },
                    labels: ['Disk Usage'],
                    noData: {
                        text: 'No Disk Usage Data Available'
                    }
                };
                diskChart = new ApexCharts(document.querySelector("#diskChart"), diskOptions);
                diskChart.render();
            })
            .catch(error => {
                console.error('Error fetching metrics:', error);
                const diskOptions = {
                    series: [0],
                    chart: {
                        type: 'radialBar',
                        height: 350
                    },
                    plotOptions: {
                        radialBar: {
                            hollow: {
                                size: '70%',
                            }
                        }
                    },
                    labels: ['Disk Usage'],
                    noData: {
                        text: 'Error Loading Disk Usage Data'
                    }
                };
                diskChart = new ApexCharts(document.querySelector("#diskChart"), diskOptions);
                diskChart.render();
            });

        // Timeline Chart
        const timelineData = @json($logs['hits']['hits'] ?? []);
        const timelineOptions = {
            series: [{
                name: 'Messages',
                data: timelineData.map(item => ({
                    x: new Date(item._source['@timestamp']),
                    y: 1,
                    message: item._source.message
                }))
            }],
            chart: {
                type: 'scatter',
                height: 350,
                zoom: {
                    type: 'xy'
                }
            },
            xaxis: {
                type: 'datetime'
            },
            yaxis: {
                show: false
            },
            tooltip: {
                custom: function({series, seriesIndex, dataPointIndex, w}) {
                    const data = w.config.series[0].data[dataPointIndex];
                    return '<div class="arrow_box">' +
                        '<span>' + moment(data.x).format('YYYY-MM-DD HH:mm:ss') + '</span><br>' +
                        '<span>' + data.message + '</span>' +
                        '</div>';
                }
            }
        };
        timelineChart = new ApexCharts(document.querySelector("#timelineChart"), timelineOptions);
        timelineChart.render();

        // Real-time updates
        let updateInterval;
        const realTimeSwitch = document.getElementById('realTimeSwitch');
        let lastTimestamp = null;

        function updateHttpMethodsChart(data) {
            if (!httpMethodChart) return;
            
            const methodCounts = {};
            data.hits.hits.forEach(log => {
                const method = log._source.http_method || 'Unknown';
                methodCounts[method] = (methodCounts[method] || 0) + 1;
            });

            const series = Object.values(methodCounts);
            const labels = Object.keys(methodCounts);

            httpMethodChart.updateSeries(series);
            httpMethodChart.updateOptions({
                labels: labels
            });
        }

        function updateLogLevelsChart(data) {
            if (!logLevelChart) return;
            
            const levelCounts = {};
            data.hits.hits.forEach(log => {
                const level = log._source.loglevel || 'Unknown';
                levelCounts[level] = (levelCounts[level] || 0) + 1;
            });

            const series = Object.values(levelCounts);
            const labels = Object.keys(levelCounts);

            logLevelChart.updateSeries(series);
            logLevelChart.updateOptions({
                labels: labels
            });
        }

        realTimeSwitch.addEventListener('change', function() {
            if (this.checked) {
                console.log('Real-time updates activated');
                updateDashboard(); // Panggil sekali saat diaktifkan
                updateInterval = setInterval(updateDashboard, 1000); // Update setiap 1 detik
            } else {
                console.log('Real-time updates deactivated');
                clearInterval(updateInterval);
            }
        });

        function updateTables(logs) {
            if (!logs || !Array.isArray(logs)) {
                console.warn('No logs data received or invalid format');
                return;
            }

            // Update Detailed Table
            const detailedTableBody = document.getElementById('detailedLogsTableBody');
            if (detailedTableBody) {
                logs.forEach(log => {
                    if (!log._source) return;
                    
                    const newRow = document.createElement('tr');
                    newRow.style.opacity = '0';
                    newRow.innerHTML = `
                        <td>${moment(log._source['@timestamp']).format('YYYY-MM-DD HH:mm:ss')}</td>
                        <td>${log._source.message || 'N/A'}</td>
                        <td><span class="badge bg-${getLogLevelClass(log._source.loglevel)}">${log._source.loglevel || 'N/A'}</span></td>
                        <td>${log._source.http_method || 'N/A'}</td>
                        <td>${log._source.client_ip || 'N/A'}</td>
                        <td>${log._source.request_url || 'N/A'}</td>
                    `;
                    detailedTableBody.insertBefore(newRow, detailedTableBody.firstChild);
                    
                    requestAnimationFrame(() => {
                        newRow.style.opacity = '1';
                    });
                });

                // Limit table rows
                while (detailedTableBody.children.length > 100) {
                    detailedTableBody.removeChild(detailedTableBody.lastChild);
                }
            }

            // Update Simple Table
            const simpleTableBody = document.getElementById('simpleLogsTableBody');
            if (simpleTableBody) {
                logs.forEach(log => {
                    if (!log._source) return;
                    
                    const newRow = document.createElement('tr');
                    newRow.style.opacity = '0';
                    newRow.innerHTML = `
                        <td>${moment(log._source['@timestamp']).format('YYYY-MM-DD HH:mm:ss')}</td>
                        <td>${log._source.message || 'N/A'}</td>
                    `;
                    simpleTableBody.insertBefore(newRow, simpleTableBody.firstChild);
                    
                    requestAnimationFrame(() => {
                        newRow.style.opacity = '1';
                    });
                });

                // Limit table rows
                while (simpleTableBody.children.length > 100) {
                    simpleTableBody.removeChild(simpleTableBody.lastChild);
                }
            }
        }

        function getLogLevelClass(level) {
            if (!level) return 'secondary';
            switch(level.toUpperCase()) {
                case 'ERROR': return 'danger';
                case 'WARNING': return 'warning';
                case 'INFO': return 'info';
                default: return 'secondary';
            }
        }

        function updateDashboard() {
            console.log('Fetching new logs, lastTimestamp:', lastTimestamp);
            
            const url = '/api/logs' + (lastTimestamp ? `?after=${encodeURIComponent(lastTimestamp)}` : '');
            console.log('Fetching URL:', url);

            fetch(url, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                credentials: 'same-origin'
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Received data:', data);
                
                if (data.hits && data.hits.hits && data.hits.hits.length > 0) {
                    console.log('New logs found:', data.hits.hits.length);
                    
                    // Update lastTimestamp dengan timestamp terbaru
                    const latestLog = data.hits.hits[0]._source;
                    if (latestLog && latestLog['@timestamp']) {
                        lastTimestamp = latestLog['@timestamp'];
                        console.log('Updated lastTimestamp:', lastTimestamp);
                    }

                    // Update tables
                    updateTables(data.hits.hits);
                    
                    // Update charts jika fungsinya ada
                    if (typeof updateHttpMethodsChart === 'function') {
                        updateHttpMethodsChart(data);
                    }
                    if (typeof updateLogLevelsChart === 'function') {
                        updateLogLevelsChart(data);
                    }
                }
            })
            .catch(error => {
                console.error('Error updating dashboard:', error);
                
                // Tampilkan pesan error yang lebih informatif
                const errorMessage = error.message.includes('404') 
                    ? 'Connection lost. Retrying in 5 seconds...'
                    : 'Error updating dashboard. Please refresh the page.';
                
                const alertDiv = document.createElement('div');
                alertDiv.className = 'alert alert-warning alert-dismissible fade show';
                alertDiv.role = 'alert';
                alertDiv.innerHTML = `
                    ${errorMessage}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                `;
                
                const container = document.querySelector('.container-fluid');
                if (container) {
                    container.insertBefore(alertDiv, container.firstChild);
                }

                if (error.message.includes('404')) {
                    setTimeout(() => {
                        updateDashboard();
                    }, 5000);
                } else {
                    clearInterval(updateInterval);
                    if (realTimeSwitch) {
                        realTimeSwitch.checked = false;
                    }
                }
            });
        }
    });
    </script>

    <style>
    .custom-tooltip {
        background: rgba(0,0,0,0.8);
        color: white;
        padding: 5px 10px;
        border-radius: 4px;
        font-size: 12px;
        max-width: 300px;
        z-index: 1000;
    }

    /* Tambahkan smooth scroll */
    #detailedLogsTableBody tr,
    #simpleLogsTableBody tr {
        transition: opacity 0.5s ease-in-out;
    }
    </style>
    @endpush
