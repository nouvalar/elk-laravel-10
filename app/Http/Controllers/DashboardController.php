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
            'index' => 'metricbeat-*',
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
                                    'field' => 'system.memory.actual.used.pct'
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
                    '@timestamp',
                    'message',
                    'loglevel',
                    'http_method',
                    'client_ip',
                    'request_url'
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
                'index' => 'metricbeat-*',
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
                        'system.cpu.total.pct',
                        'system.memory.actual.used.bytes',
                        'system.memory.actual.used.pct',
                        'system.memory.total',
                        'system.memory.free'
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
            $memoryFree = $memoryMetrics['free'] ?? 0;

            // Calculate memory percentage if not available or incorrect
            if ($memoryTotal > 0) {
                $memoryPercent = ($memoryUsed / $memoryTotal);
            }

            // Ensure memory percentage is between 0 and 1
            $memoryPercent = max(0, min(1, $memoryPercent));

            // Get CPU percentage
            $cpuTotal = $system['cpu']['total']['pct'] ?? 0;

            // Ensure CPU percentage is between 0 and 1
            $cpuTotal = max(0, min(1, $cpuTotal));

            // Format memory values for human readability
            $memoryUsedFormatted = $this->formatBytes($memoryUsed);
            $memoryTotalFormatted = $this->formatBytes($memoryTotal);
            $memoryFreeFormatted = $this->formatBytes($memoryFree);

            // Log extracted values for debugging
            Log::info('Extracted metrics:', [
                'memory' => [
                    'total' => $memoryTotalFormatted,
                    'used' => $memoryUsedFormatted,
                    'percent' => $memoryPercent * 100,
                    'free' => $memoryFreeFormatted
                ],
                'cpu' => [
                    'total' => $cpuTotal * 100
                ]
            ]);

            $response = [
                'success' => true,
                'timestamp' => $latest['@timestamp'],
                'data' => [
                    'cpu' => $cpuTotal * 100,
                    'memory' => $memoryPercent * 100,
                    'memory_bytes' => $memoryUsed,
                    'memory_total' => $memoryTotal,
                    'memory_free' => $memoryFree,
                    'memory_formatted' => [
                        'used' => $memoryUsedFormatted,
                        'total' => $memoryTotalFormatted,
                        'free' => $memoryFreeFormatted
                    ]
                ]
            ];

            return response()->json($response);

        } catch (\Exception $e) {
            Log::error('Metrics Error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch metrics data: ' . $e->getMessage(),
                'data' => [
                    'cpu' => 0,
                    'memory' => 0,
                    'memory_bytes' => 0,
                    'memory_total' => 0,
                    'memory_free' => 0,
                    'memory_formatted' => [
                        'used' => '0 B',
                        'total' => '0 B',
                        'free' => '0 B'
                    ]
                ]
            ], 500);
        }
    }

    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, $precision) . ' ' . $units[$pow];
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
                    ],
                    '_source' => [
                        '@timestamp',
                        'message',
                        'loglevel',
                        'http_method',
                        'client_ip',
                        'request_url'
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