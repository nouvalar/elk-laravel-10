<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Facades\Elasticsearch;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        try {
            $metricbeat = $this->fetchMetricbeatData();
            $logs = $this->fetchLogs();
            $httpMethodStats = $this->fetchLogStats();

            Log::info('Dashboard data loaded successfully', [
                'metricbeat_buckets' => count($metricbeat['aggregations']['cpu_usage']['buckets'] ?? []),
                'logs_count' => count($logs['hits']['hits'] ?? []),
                'http_methods' => count($httpMethodStats['aggregations']['http_methods']['buckets'] ?? [])
            ]);

            return view('dashboard', [
                'metricbeat' => $metricbeat,
                'logs' => $logs,
                'httpMethodStats' => $httpMethodStats
            ]);

        } catch (\Exception $e) {
            Log::error('Dashboard Error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return view('dashboard', [
                'metricbeat' => [
                    'aggregations' => [
                        'cpu_usage' => ['buckets' => []],
                        'memory_usage' => ['buckets' => []]
                    ]
                ],
                'logs' => ['hits' => ['hits' => []]],
                'httpMethodStats' => [
                    'aggregations' => [
                        'http_methods' => ['buckets' => []],
                        'log_levels' => ['buckets' => []]
                    ]
                ]
            ]);
        }
    }

    private function fetchMetricbeatData()
    {
        return Elasticsearch::search([
            'index' => '.ds-metricbeat-8.17.4-*',
            'body' => [
                'size' => 0,
                'query' => [
                    'range' => [
                        '@timestamp' => [
                            'gte' => 'now-5m'
                        ]
                    ]
                ],
                'aggs' => [
                    'cpu_usage' => [
                        'date_histogram' => [
                            'field' => '@timestamp',
                            'fixed_interval' => '10s'
                        ],
                        'aggs' => [
                            'cpu' => [
                                'avg' => [
                                    'field' => 'system.cpu.total.pct'
                                ]
                            ]
                        ]
                    ],
                    'memory_usage' => [
                        'date_histogram' => [
                            'field' => '@timestamp',
                            'fixed_interval' => '10s'
                        ],
                        'aggs' => [
                            'memory' => [
                                'avg' => [
                                    'field' => 'docker.container.memory.usage.pct'
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ])->asArray();
    }

    private function fetchLogs()
    {
        return Elasticsearch::search([
            'index' => 'flight5',
            'body' => [
                'size' => 100,
                'sort' => [
                    '@timestamp' => ['order' => 'desc']
                ],
                '_source' => [
                    '@timestamp', 'message', 'loglevel', 'http_method', 'client_ip', 'request_url'
                ]
            ]
        ])->asArray();
    }

    private function fetchLogStats()
    {
        return Elasticsearch::search([
            'index' => 'flight5',
            'body' => [
                'size' => 0,
                'aggs' => [
                    'http_methods' => [
                        'terms' => [
                            'field' => 'http_method.keyword',
                            'size' => 10
                        ]
                    ],
                    'log_levels' => [
                        'terms' => [
                            'field' => 'loglevel.keyword',
                            'size' => 10
                        ]
                    ]
                ]
            ]
        ])->asArray();
    }

    public function getMetrics()
    {
        try {
            Log::info('Fetching metrics from Elasticsearch');

            $metrics = Elasticsearch::search([
                'index' => 'metricbeat-8.17.4',
                'body' => [
                    'size' => 1,
                    'sort' => [
                        '@timestamp' => 'desc'
                    ],
                    'query' => [
                        'bool' => [
                            'must' => [
                                [
                                    'exists' => [
                                        'field' => 'system.memory'
                                    ]
                                ]
                            ]
                        ]
                    ],
                    '_source' => [
                        '@timestamp',
                        'system.cpu.user.norm.pct',
                        'system.cpu.system.norm.pct',
                        'system.memory.actual.used.bytes',
                        'system.memory.actual.used.pct',
                        'system.memory.actual.free',
                        'system.memory.total',
                        'system.memory.used.bytes',
                        'system.memory.used.pct',
                        'system.fsstat.total_size.total',
                        'system.fsstat.total_size.used'
                    ]
                ]
            ])->asArray();

            if (empty($metrics['hits']['hits'])) {
                throw new \Exception('No metrics data available');
            }

            $latest = $metrics['hits']['hits'][0]['_source'];
            
            // Log the complete raw response for debugging
            Log::info('Latest source data:', $latest);

            // Extract system data
            $system = $latest['system'] ?? [];
            
            // Get memory metrics
            $memoryMetrics = $system['memory'] ?? [];
            $memoryTotal = $memoryMetrics['total'] ?? 0;
            $memoryUsed = $memoryMetrics['actual']['used']['bytes'] ?? 0;
            $memoryPercent = $memoryMetrics['actual']['used']['pct'] ?? 0;
            $memoryFree = $memoryMetrics['actual']['free'] ?? 0;

            // Get filesystem metrics from fsstat
            $fsstat = $system['fsstat'] ?? [];
            $diskTotal = $fsstat['total_size']['total'] ?? 0;
            $diskUsed = $fsstat['total_size']['used'] ?? 0;
            $diskPercent = $diskTotal > 0 ? ($diskUsed / $diskTotal) : 0;

            // Get CPU percentage (user + system)
            $cpuUser = $system['cpu']['user']['norm']['pct'] ?? 0;
            $cpuSystem = $system['cpu']['system']['norm']['pct'] ?? 0;
            $cpuTotal = $cpuUser + $cpuSystem;

            // Log extracted values for debugging
            Log::info('Extracted metrics:', [
                'memory' => [
                    'total' => $memoryTotal,
                    'used' => $memoryUsed,
                    'percent' => $memoryPercent,
                    'free' => $memoryFree
                ],
                'disk' => [
                    'total' => $diskTotal,
                    'used' => $diskUsed,
                    'percent' => $diskPercent
                ],
                'cpu' => [
                    'user' => $cpuUser,
                    'system' => $cpuSystem,
                    'total' => $cpuTotal
                ]
            ]);

            $response = [
                'success' => true,
                'timestamp' => $latest['@timestamp'],
                'data' => [
                    'cpu' => $cpuTotal,
                    'memory' => $memoryPercent,
                    'memory_bytes' => $memoryUsed,
                    'memory_total' => $memoryTotal,
                    'memory_free' => $memoryFree,
                    'disk' => [
                        'used' => $diskUsed,
                        'total' => $diskTotal,
                        'percent' => $diskPercent
                    ]
                ]
            ];

            return response()->json($response);

        } catch (\Exception $e) {
            Log::error('Metrics Error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch metrics data: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getLogs(Request $request)
    {
        try {
            $query = [
                'index' => env('ELASTICSEARCH_INDEX_LOGS', 'flight5'),
                'body' => [
                    'size' => 100,
                    'sort' => [
                        '@timestamp' => ['order' => 'desc']
                    ],
                    '_source' => [
                        '@timestamp', 'message', 'loglevel', 'http_method', 'client_ip', 'request_url'
                    ]
                ]
            ];

            if ($request->has('after')) {
                $query['body']['query'] = [
                    'bool' => [
                        'must' => [
                            ['range' => ['@timestamp' => ['gt' => $request->after]]]
                        ]
                    ]
                ];
            }

            $logs = Elasticsearch::search($query)->asArray();

            return response()->json($logs);

        } catch (\Exception $e) {
            Log::error('Logs API Error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return response()->json([
                'error' => $e->getMessage(),
                'hits' => [
                    'hits' => []
                ]
            ], 500);
        }
    }

    public function getRecentLogs(Request $request)
    {
        try {
            $params = [
                'index' => 'flight5',
                'body' => [
                    'size' => 100,
                    'sort' => [
                        '@timestamp' => ['order' => 'desc']
                    ]
                ]
            ];

            if ($request->has('after')) {
                $params['body']['query'] = [
                    'bool' => [
                        'must' => [
                            ['range' => ['@timestamp' => ['gt' => $request->after]]]
                        ]
                    ]
                ];
            }

            $response = Elasticsearch::search($params)->asArray();

            $hits = collect($response['hits']['hits'] ?? [])->map(function ($hit) {
                $source = $hit['_source'] ?? [];
                return [
                    '_source' => [
                        '@timestamp' => $source['@timestamp'] ?? null,
                        'message' => $source['message'] ?? 'N/A',
                        'loglevel' => $source['loglevel'] ?? 'N/A',
                        'http_method' => $source['http_method'] ?? 'N/A',
                        'client_ip' => $source['client_ip'] ?? 'N/A',
                        'request_url' => $source['request_url'] ?? 'N/A'
                    ]
                ];
            })->toArray();

            return response()->json([
                'success' => true,
                'hits' => ['hits' => $hits]
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching recent logs: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch logs: ' . $e->getMessage()
            ], 500);
        }
    }
}