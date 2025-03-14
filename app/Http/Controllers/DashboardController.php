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
            // Mengambil data metricbeat
            $metricbeat = Elasticsearch::search([
                'index' => 'metricbeat-*',
                'body' => [
                    'size' => 0,
                    'aggs' => [
                        'cpu_usage' => [
                            'date_histogram' => [
                                'field' => '@timestamp',
                                'fixed_interval' => '1h'
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
                                'fixed_interval' => '1h'
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

            // Mengambil data logs
            $logs = Elasticsearch::search([
                'index' => 'flight5-2025.03.14*',  // Menggunakan pattern yang sesuai dengan logstash
                'body' => [
                    'size' => 100,
                    'sort' => [
                        '@timestamp' => [
                            'order' => 'desc'
                        ]
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

            $httpMethodStats = Elasticsearch::search([
                'index' => 'flight5-2025.03.14*',
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

            // Log data untuk debugging
            Log::info('Elasticsearch Response - Logs:', ['count' => count($logs['hits']['hits'] ?? [])]);
            Log::info('Elasticsearch Response - HTTP Methods:', ['buckets' => count($httpMethodStats['aggregations']['http_methods']['buckets'] ?? [])]);

            return view('dashboard', [
                'metricbeat' => $metricbeat,
                'logs' => $logs,
                'httpMethodStats' => $httpMethodStats
            ]);

        } catch (\Exception $e) {
            Log::error('Elasticsearch Error: ' . $e->getMessage());
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

    public function getMetrics()
    {
        try {
            $metrics = Elasticsearch::search([
                'index' => 'metricbeat-*',
                'body' => [
                    'size' => 0,
                    'aggs' => [
                        'cpu_usage' => [
                            'avg' => [
                                'field' => 'system.cpu.total.pct'
                            ]
                        ],
                        'memory_usage' => [
                            'avg' => [
                                'field' => 'system.memory.actual.used.pct'
                            ]
                        ],
                        'disk_usage' => [
                            'avg' => [
                                'field' => 'system.filesystem.used.pct'
                            ]
                        ]
                    ]
                ]
            ]);

            return response()->json($metrics);
        } catch (\Exception $e) {
            Log::error('Metrics Error: ' . $e->getMessage());
            return response()->json([
                'aggregations' => [
                    'cpu_usage' => ['value' => 0],
                    'memory_usage' => ['value' => 0],
                    'disk_usage' => ['value' => 0]
                ]
            ]);
        }
    }

    public function getLogStats()
    {
        try {
            $stats = Elasticsearch::search([
                'index' => 'flight5-2025.03.14*',
                'body' => [
                    'size' => 0,
                    'aggs' => [
                        'clients' => [
                            'terms' => [
                                'field' => 'client_ip.keyword',
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
            ]);

            return response()->json($stats);
        } catch (\Exception $e) {
            return response()->json([
                'aggregations' => [
                    'clients' => ['buckets' => []],
                    'log_levels' => ['buckets' => []]
                ]
            ]);
        }
    }

    public function getLogs(Request $request)
    {
        try {
            $query = [
                'index' => 'flight5-2025.03.14*',  // Menggunakan pattern yang sesuai
                'body' => [
                    'size' => 100,
                    'sort' => [
                        '@timestamp' => [
                            'order' => 'desc'
                        ]
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

            // Jika ada parameter 'after', tambahkan filter timestamp
            if ($request->has('after')) {
                $afterTimestamp = $request->after;
                Log::info('Filtering logs after timestamp:', ['timestamp' => $afterTimestamp]);
                
                $query['body']['query'] = [
                    'bool' => [
                        'must' => [
                            [
                                'range' => [
                                    '@timestamp' => [
                                        'gt' => $afterTimestamp
                                    ]
                                ]
                            ]
                        ]
                    ]
                ];
            }

            Log::info('Elasticsearch query:', ['query' => json_encode($query)]);
            $logs = Elasticsearch::search($query)->asArray();
            Log::info('Elasticsearch response:', ['response' => json_encode($logs)]);

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
}
