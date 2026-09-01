<?php

namespace FreqtradeDashboard;

/**
 * Tracks the running maximum drawdown / underwater per server.
 *
 * The freqtrade API only exposes current snapshots, and its /profit
 * max_drawdown is computed from CLOSED trades only, so it stays at 0 for a
 * strategy whose closed trades are all winners even while open positions sit
 * deep underwater. To surface the true worst point of the run we persist a
 * tiny running summary per server and update it on every poll using the
 * mark-to-market equity (balance.total, which already includes open-trade
 * unrealized P/L).
 *
 * Only O(1) state is stored (peak + running maxima), not a full series, so the
 * file stays small regardless of how long a bot runs.
 */
class EquityTracker
{
    private string $file;

    public function __construct(?string $file = null)
    {
        $this->file = $file ?? (__DIR__ . '/../cache/equity_stats.json');
    }

    /**
     * Update the running stats for one server and return its current summary.
     *
     * @param int        $serverNum   Server identifier
     * @param float      $markToMarket Current total equity (balance.total)
     * @param float      $unrealized  Sum of open-trade unrealized P/L (0 if none)
     * @param int|null   $ts          Unix timestamp of the observation
     * @return array{max_dd_abs: float, max_dd_pct: float, max_uw_pct: float, peak: float, since: int}
     */
    public function update(int $serverNum, float $markToMarket, float $unrealized = 0.0, ?int $ts = null): array
    {
        $ts = $ts ?? time();
        $all = $this->readAll();
        $key = (string) $serverNum;

        // Realized wallet (high-water reference): equity minus current unrealized.
        // Using it as a peak candidate means a currently-underwater account shows a
        // drawdown from the very first observation, without needing to have watched
        // the exact moment the peak was set.
        $realizedWallet = $markToMarket - $unrealized;
        $peakCandidate = max($markToMarket, $realizedWallet);

        if (!isset($all[$key]) || !is_array($all[$key])) {
            $all[$key] = [
                'peak' => $peakCandidate,
                'max_dd_abs' => 0.0,
                'max_dd_pct' => 0.0,
                'max_uw_pct' => 0.0,
                'since' => $ts,
                'last_ts' => $ts,
            ];
        }

        $s = $all[$key];
        $peak = max((float) ($s['peak'] ?? 0), $peakCandidate);

        $dd = $peak - $markToMarket;          // absolute drop below peak
        if ($dd < 0) $dd = 0.0;
        $ddPct = $peak > 0 ? ($dd / $peak) : 0.0;

        $maxDdAbs = (float) ($s['max_dd_abs'] ?? 0);
        $maxDdPct = (float) ($s['max_dd_pct'] ?? 0);
        $maxUwPct = (float) ($s['max_uw_pct'] ?? 0);

        if ($dd > $maxDdAbs) {
            $maxDdAbs = $dd;
            $maxDdPct = $ddPct; // relative drop at the moment of the deepest absolute drop
        }
        if ($ddPct > $maxUwPct) {
            $maxUwPct = $ddPct;
        }

        $all[$key] = [
            'peak' => $peak,
            'max_dd_abs' => $maxDdAbs,
            'max_dd_pct' => $maxDdPct,
            'max_uw_pct' => $maxUwPct,
            'since' => (int) ($s['since'] ?? $ts),
            'last_ts' => $ts,
        ];

        $this->writeAll($all);

        return [
            'max_dd_abs' => $maxDdAbs,
            'max_dd_pct' => $maxDdPct,
            'max_uw_pct' => $maxUwPct,
            'peak' => $peak,
            'since' => (int) $all[$key]['since'],
        ];
    }

    private function readAll(): array
    {
        if (!is_file($this->file)) {
            return [];
        }
        $raw = @file_get_contents($this->file);
        if ($raw === false || $raw === '') {
            return [];
        }
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }

    private function writeAll(array $all): void
    {
        $dir = dirname($this->file);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        // Atomic write with an exclusive lock to survive concurrent refreshes.
        @file_put_contents($this->file, json_encode($all), LOCK_EX);
    }
}
