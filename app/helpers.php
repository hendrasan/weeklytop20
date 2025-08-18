<?php

if (!function_exists('assets_url')) {
    /**
     * Get the URL to an asset
     *
     * @param  string  $path
     * @return string
     */
    function assets_url($asset)
    {
        return asset('assets/' . ltrim($asset, '/'));
    }
}

if (!function_exists('uploads_url')) {
    /**
     * Get the URL to uploads folder
     *
     * @param  string  $path
     * @return string
     */
    function uploads_url($upload)
    {
        return asset('uploads/' . ltrim($upload, '/'));
    }
}

if (!function_exists('uploads_path')) {
    /**
     * Get the URL to uploads_path folder
     *
     * @param  string  $path
     * @return string
     */
    function uploads_path($upload)
    {
        if (!file_exists(public_path('uploads/' . $upload))) {
            return 'file not found';
        }

        return public_path() . '/uploads/' . ltrim($upload, '/');
    }
}


if (!function_exists('currentRouteName')) {
    /**
     * Get the URL to currentRouteName folder
     *
     * @param  string  $path
     * @return string
     */
    function currentRouteName()
    {
        return Route::currentRouteName();
    }
}


if (!function_exists('getArrowIcon')) {
    /**
     * Get the CSS class for the arrow icon based on current and previous values.
     * TODO: change this to TailwindCSS and other icon sets (FontAwesome is huge)
     *
     * @param int $curr
     * @param int|null $prev
     * @return string
     */
    function getArrowIcon(int $curr, ?int $prev): string
    {
        return match(true) {
            empty($prev) => 'has-text-warning fa-star',
            $curr < $prev => 'has-text-success fa-arrow-up',
            $curr > $prev => 'has-text-danger fa-arrow-down',
            $curr == $prev => 'has-text-info fa-arrows-alt-h',
        };
    }

}

if (!function_exists('annotateRuns')) {
    /**
     * Annotate chart runs to prepare the data for rendering.
     *
     * @param array|null $runs Array of objects with period and position
     * @param int $currentPeriod The current period being viewed
     * @return array
     */
    function annotateRuns(?array $runs, int $currentPeriod): array
    {
        if (empty($runs) || !is_array($runs)) {
            return [];
        }

        // Extract positions and find peak
        $positions = [];
        foreach ($runs as $run) {
            if (isset($run['position']) && $run['position'] > 0) {
                $positions[] = $run['position'];
            }
        }

        $peak = !empty($positions) ? min($positions) : null;
        $result = [];

        foreach ($runs as $i => $run) {
            $position = $run['position'] ?? null;
            $period = $run['period'] ?? null;

            // Check for gaps and add gap indicator if needed
            if ($i > 0) {
                $prevPeriod = $runs[$i - 1]['period'] ?? null;

                if (!is_null($prevPeriod) && !is_null($period) && $period - $prevPeriod > 1) {
                    $result[] = [
                        'display' => '...',
                        'is_gap' => true,
                        'trend' => null,
                        'is_current' => false,
                        'is_peak' => false,
                        'value' => null,
                        'period' => null,
                    ];
                }
            }

            $isCurrent = ($period === $currentPeriod);
            $display = (is_null($position) || $position == 0) ? '—' : (string) $position;

            // Calculate trend - only if no gap before this item
            $trend = null;
            if ($i > 0) {
                $prevPeriod = $runs[$i - 1]['period'] ?? null;
                $prev = $runs[$i - 1]['position'] ?? null;

                // Only calculate trend if periods are consecutive
                if (!is_null($prevPeriod) && !is_null($period) &&
                    $period - $prevPeriod === 1 &&
                    !is_null($prev) && !is_null($position) &&
                    $prev != 0 && $position != 0) {

                    if ($position < $prev) {
                        $trend = 'up';
                    } elseif ($position > $prev) {
                        $trend = 'down';
                    } else {
                        $trend = 'flat';
                    }
                }
            }

            $isPeak = (!is_null($peak) && !is_null($position) && $position != 0 && $position === $peak);

            $result[] = [
                'value' => $position,
                'period' => $period,
                'display' => $display,
                'trend' => $trend,
                'is_current' => $isCurrent,
                'is_peak' => $isPeak,
            ];
        }

        return $result;
    }
}