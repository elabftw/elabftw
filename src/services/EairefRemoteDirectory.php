<?php declare(strict_types=1);
/**
 * @package   Elabftw\Elabftw
 * @author    Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2023 Nicolas CARPi
 * @license   https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @see       https://www.elabftw.net Official website
 */

namespace Elabftw\Services;

use Elabftw\Models\Config;

/**
 * Implements requests to EAIREF directory
 */
class EairefRemoteDirectory extends AbstractRemoteDirectory
{
    public function search(string $term): array
    {
        $results = array();
        // two URL need to be queried, so we do the request on both and merge results
        foreach ($this->config as $endpoint) {
            $results = array_merge($results, $this->makeRequest($endpoint, $term));
        }
        return $results;
        ;
    }

    private function makeRequest(array $endpoint, string $term): array
    {
        if ($endpoint['preg_quote']) {
            $term = preg_quote($term);
        }
        $url = str_replace('%q%', $term, $endpoint['url']);
        $reqOptions = array('auth' => $endpoint['auth']);
        $Config = Config::getConfig();
        if (!empty($Config->configArr['proxy'])) {
            $reqOptions['proxy'] = $Config->configArr['proxy'];
        }
        $res = $this->client->request(self::METHOD, $url, $reqOptions);
        // terminate early if we get a 204 (nothing found)
        if ($res->getStatusCode() === 204) {
            return array();
        }
        $decoded = json_decode((string) $res->getBody(), true, 512, JSON_THROW_ON_ERROR);
        foreach ($decoded as &$user) {
            $user['firstname'] = $user[$endpoint['firstname']];
            $user['lastname'] = $user[$endpoint['lastname']];
            $user['email'] = $user[$endpoint['email']];
            $user['orgid'] = $user[$endpoint['orgid']];
            $user['disabled'] = false;
            foreach ($endpoint['disabled'] as $disabler) {
                if (isset($user[$disabler['property']]) && $user[$disabler['property']] === $disabler['value']) {
                    $user['disabled'] = true;
                }
            }
        }
        return $decoded;
    }
}
