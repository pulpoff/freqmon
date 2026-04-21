<?php

namespace FreqtradeDashboard;

/**
 * Runs a batch of HTTP requests concurrently using curl_multi.
 * Caps the number of in-flight handles to the given concurrency.
 */
class ParallelHttp
{
    /**
     * Execute $requests in parallel. Each request is an associative array:
     *   - id              (string|int)  unique key used to correlate the response
     *   - url             (string)
     *   - method          (string)      'GET' | 'POST' (default GET)
     *   - headers         (string[])    extra HTTP headers
     *   - userpwd         (string|null) "user:pass" for basic auth
     *   - body            (string|null) POST body
     *   - timeout         (int)         total timeout seconds (default 10)
     *   - connect_timeout (int)         connect timeout seconds (default 5)
     *
     * Returns: [id => ['status' => int, 'body' => string, 'error' => ?string]]
     */
    public static function run(array $requests, int $concurrency = 20): array
    {
        if (empty($requests)) {
            return [];
        }
        $concurrency = max(1, $concurrency);

        $mh = curl_multi_init();
        $inflight = [];   // spl_object_id(ch) => ['ch' => CurlHandle, 'id' => mixed]
        $results = [];
        $queue = array_values($requests);

        $addNext = function () use (&$queue, &$inflight, $mh): bool {
            if (empty($queue)) {
                return false;
            }
            $req = array_shift($queue);
            $ch = self::buildHandle($req);
            $inflight[spl_object_id($ch)] = ['ch' => $ch, 'id' => $req['id']];
            curl_multi_add_handle($mh, $ch);
            return true;
        };

        for ($i = 0; $i < $concurrency; $i++) {
            if (!$addNext()) {
                break;
            }
        }

        try {
            do {
                do {
                    $status = curl_multi_exec($mh, $running);
                } while ($status === CURLM_CALL_MULTI_PERFORM);

                while (($info = curl_multi_info_read($mh)) !== false) {
                    $ch = $info['handle'];
                    $key = spl_object_id($ch);
                    $entry = $inflight[$key] ?? null;
                    if ($entry !== null) {
                        $body = curl_multi_getcontent($ch);
                        $results[$entry['id']] = [
                            'status' => (int) curl_getinfo($ch, CURLINFO_HTTP_CODE),
                            'body'   => $body === null ? '' : $body,
                            'error'  => $info['result'] !== CURLE_OK ? (curl_error($ch) ?: null) : null,
                        ];
                        curl_multi_remove_handle($mh, $ch);
                        curl_close($ch);
                        unset($inflight[$key]);
                    }
                    $addNext();
                }

                if ($running > 0 && empty($queue)) {
                    curl_multi_select($mh, 0.5);
                }
            } while ($running > 0 || !empty($queue) || !empty($inflight));
        } finally {
            foreach ($inflight as $entry) {
                curl_multi_remove_handle($mh, $entry['ch']);
                curl_close($entry['ch']);
            }
            curl_multi_close($mh);
        }

        return $results;
    }

    private static function buildHandle(array $req): \CurlHandle
    {
        $ch = curl_init();
        $opts = [
            CURLOPT_URL            => $req['url'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $req['timeout'] ?? 10,
            CURLOPT_CONNECTTIMEOUT => $req['connect_timeout'] ?? 5,
        ];

        $method = strtoupper($req['method'] ?? 'GET');
        if ($method === 'POST') {
            $opts[CURLOPT_POST] = true;
            if (isset($req['body'])) {
                $opts[CURLOPT_POSTFIELDS] = $req['body'];
            }
        }

        if (!empty($req['userpwd'])) {
            $opts[CURLOPT_USERPWD] = $req['userpwd'];
        }

        if (!empty($req['headers'])) {
            $opts[CURLOPT_HTTPHEADER] = $req['headers'];
        }

        curl_setopt_array($ch, $opts);
        return $ch;
    }
}
