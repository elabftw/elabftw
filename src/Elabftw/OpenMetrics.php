<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2025 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Elabftw;

use Elabftw\Models\Info;
use Symfony\Component\HttpFoundation\Response;

class OpenMetrics
{
    private const METRICS_TTL_SECONDS = 60;

    private array $lines = array();

    public function __construct()
    {
        setlocale(LC_ALL, 'C');
        ini_set('serialize_precision', '-1');
        ini_set('precision', '17');
    }

    public function getResponse(): Response
    {
        $Response = new Response();
        $Response->headers->set('Content-Type', 'application/openmetrics-text; version=1.0.0; charset=utf-8');
        $Response->headers->set('Cache-Control', 'no-store');
        $Response->setContent($this->getContent());
        return $Response;
    }

    private function collect(): array
    {
        return apcu_entry('elab_metrics_payload', function () {
            return new Info()->readAll();
        }, self::METRICS_TTL_SECONDS);
    }

    private function n(int|float|string|null $v): string
    {
        if (is_int($v)) {
            return (string) $v;
        }
        if (is_float($v) || is_numeric($v)) {
            $s = rtrim(rtrim(sprintf('%.15F', (float) $v), '0'), '.');
            return $s === '' ? '0' : $s;
        }
        return '0';
    }

    private function sample(string $name, array $labels, int|float|string|null $value): void
    {
        $lab = '';
        if ($labels) {
            $pairs = array();
            foreach ($labels as $k => $v) {
                $pairs[] = sprintf('%s="%s"', $k, $v);
            }
            $lab = '{' . implode(',', $pairs) . '}';
        }
        $this->lines[] = $name . $lab . ' ' . $this->n($value);
    }

    private function getContent(): string
    {
        $d = $this->collect();
        $now = time();

        // elab_app_info
        $this->lines[] = '# HELP elab_app_info Static labels about this eLabFTW instance';
        $this->lines[] = '# TYPE elab_app_info gauge';
        $this->sample('elab_app_info', array(
            'version' => (string) $d['elabftw_version'],
            'version_int' => (string) $d['elabftw_version_int'],
        ), 1);

        // Timestamping balance/limit
        $this->lines[] = '# HELP elab_ts_balance Current timestamping balance';
        $this->lines[] = '# TYPE elab_ts_balance gauge';
        $this->sample('elab_ts_balance', array(), $d['ts_balance']);

        $this->lines[] = '# HELP elab_ts_limit Timestamping limit';
        $this->lines[] = '# TYPE elab_ts_limit gauge';
        $this->sample('elab_ts_limit', array(), $d['ts_limit']);

        // Uploads size (bytes)
        $this->lines[] = '# HELP elab_uploads_filesize_bytes Total size of uploaded files';
        $this->lines[] = '# TYPE elab_uploads_filesize_bytes gauge';
        $this->lines[] = '# UNIT elab_uploads_filesize_bytes bytes';
        $this->sample('elab_uploads_filesize_bytes', array(), $d['uploads_filesize_sum']);

        // Users
        $this->lines[] = '# HELP elab_users_total Total users';
        $this->lines[] = '# TYPE elab_users_total gauge';
        $this->sample('elab_users_total', array(), $d['all_users_count']);

        $this->lines[] = '# HELP elab_users_active Active users';
        $this->lines[] = '# TYPE elab_users_active gauge';
        $this->sample('elab_users_active', array(), $d['active_users_count']);

        // Content counts
        $this->lines[] = '# HELP elab_items_total Total items';
        $this->lines[] = '# TYPE elab_items_total gauge';
        $this->sample('elab_items_total', array(), $d['items_count']);

        $this->lines[] = '# HELP elab_teams_total Total teams';
        $this->lines[] = '# TYPE elab_teams_total gauge';
        $this->sample('elab_teams_total', array(), $d['teams_count']);

        $this->lines[] = '# HELP elab_compounds_total Total compounds';
        $this->lines[] = '# TYPE elab_compounds_total gauge';
        $this->sample('elab_compounds_total', array(), $d['compounds_count']);

        $this->lines[] = '# HELP elab_experiments_total Total experiments';
        $this->lines[] = '# TYPE elab_experiments_total gauge';
        $this->sample('elab_experiments_total', array(), $d['experiments_count']);

        $this->lines[] = '# HELP elab_experiments_timestamped_total Experiments that are timestamped';
        $this->lines[] = '# TYPE elab_experiments_timestamped_total gauge';
        $this->sample('elab_experiments_timestamped_total', array(), $d['experiments_timestamped_count']);

        // Scrape timestamp
        $this->lines[] = '# HELP elab_scrape_timestamp_seconds Unix time when this was served';
        $this->lines[] = '# TYPE elab_scrape_timestamp_seconds gauge';
        $this->sample('elab_scrape_timestamp_seconds', array(), $now);

        // Trailer required by OpenMetrics v1
        $this->lines[] = '# EOF';

        return implode("\n", $this->lines) . "\n";
    }
}
