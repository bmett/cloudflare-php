<?php
/**
 * Created by PhpStorm.
 * User: junade
 * Date: 09/06/2017
 * Time: 16:17
 */

namespace Cloudflare\API\Endpoints;

use Cloudflare\API\Adapter\Adapter;
use Cloudflare\API\Configurations\PageRulesActions;
use Cloudflare\API\Configurations\PageRulesTargets;
use Cloudflare\API\Traits\BodyAccessorTrait;

class PageRules implements API
{
    use BodyAccessorTrait;

    private $adapter;

    public function __construct(Adapter $adapter)
    {
        $this->adapter = $adapter;
    }

    public function listRulesets(
        string $zoneID
    ): array {
 
        $user = $this->adapter->get('zones/' . $zoneID . '/rulesets');
        $this->body = json_decode($user->getBody());

        return $this->body->result;
    }

    public function listRuleset(
        string $zoneID,
        string $rulesetID
    ): object {
 
        $user = $this->adapter->get('zones/' . $zoneID . '/rulesets/'.$rulesetID);
        $this->body = json_decode($user->getBody());

        return $this->body->result;
    }

    public function deleteRuleset(
        string $zoneID,
        string $rulesetID
    ): bool {
 
        $user = $this->adapter->delete('zones/' . $zoneID . '/rulesets/'.$rulesetID);
        $this->body = json_decode($user->getBody());

        return true;
    }


    public function createRuleset(
        string $zoneID,
        string $domain
    ): bool {
 
        $action_parameters = [
            'from_value' => [
                'target_url' => [
                    'value' => "https://suspended.mywork.net.au"
                ],
                'status_code'           => 302,
                'preserve_query_string' => false
            ]
        ];

        $rules = array();
        $rules[] = array(
            'action' => 'redirect',
            'action_parameters' => $action_parameters,
            'expression'    => '(http.host contains "'.$domain.'")',
            'description'   => 'MyWork suspension redirect',
        );

        $options = [
            'description'   => 'MyWork suspension redirect',
            'kind'          => 'zone',
            'name'          => 'MyWork suspension redirect',
            'phase'         => 'http_request_dynamic_redirect',
            'rules'         => $rules,            
        ];

        $query = $this->adapter->post('zones/' . $zoneID . '/rulesets', $options);

        $this->body = json_decode($query->getBody());

        if (isset($this->body->result->id)) {
            return true;
        }

        return false;
    }


    /**
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     *
     * @param string $zoneID
     * @param PageRulesTargets $target
     * @param PageRulesActions $actions
     * @param bool $active
     * @param int|null $priority
     * @return bool
     */
    public function createPageRule(
        string $zoneID,
        PageRulesTargets $target,
        PageRulesActions $actions,
        bool $active = true,
        int $priority = null
    ): bool {
        $options = [
            'targets' => $target->getArray(),
            'actions' => $actions->getArray()
        ];

        if ($active !== null) {
            $options['status'] = $active == true ? 'active' : 'disabled';
        }

        if ($priority !== null) {
            $options['priority'] = $priority;
        }


        $query = $this->adapter->post('zones/' . $zoneID . '/pagerules', $options);

        $this->body = json_decode($query->getBody());

        if (isset($this->body->result->id)) {
            return true;
        }

        return false;
    }

    public function listPageRules(
        string $zoneID,
        string $status = null,
        string $order = null,
        string $direction = null,
        string $match = null
    ): array {
        if ($status != null && !in_array($status, ['active', 'disabled'])) {
            throw new EndpointException('Page Rules can only be listed by status of active or disabled.');
        }

        if ($order != null && !in_array($order, ['status', 'priority'])) {
            throw new EndpointException('Page Rules can only be ordered by status or priority.');
        }

        if ($direction != null && !in_array($direction, ['asc', 'desc'])) {
            throw new EndpointException('Direction of Page Rule ordering can only be asc or desc.');
        }

        if ($match != null && !in_array($match, ['all', 'any'])) {
            throw new EndpointException('Match can only be any or all.');
        }

        $query = [
            'status' => $status,
            'order' => $order,
            'direction' => $direction,
            'match' => $match
        ];

        $user = $this->adapter->get('zones/' . $zoneID . '/pagerules', $query);
        $this->body = json_decode($user->getBody());

        return $this->body->result;
    }

    public function getPageRuleDetails(string $zoneID, string $ruleID): \stdClass
    {
        $user = $this->adapter->get('zones/' . $zoneID . '/pagerules/' . $ruleID);
        $this->body = json_decode($user->getBody());
        return $this->body->result;
    }

    public function editPageRule(
        string $zoneID,
        string $ruleID,
        PageRulesTargets $target,
        PageRulesActions $actions,
        bool $active = null,
        int $priority = null
    ): bool {
        $options = [];
        $options['targets'] = $target->getArray();
        $options['actions'] = $actions->getArray();

        if ($active !== null) {
            $options['status'] = $active == true ? 'active' : 'disabled';
        }

        if ($priority !== null) {
            $options['priority'] = $priority;
        }

        $query = $this->adapter->put('zones/' . $zoneID . '/pagerules/' . $ruleID, $options);

        $this->body = json_decode($query->getBody());

        if (isset($this->body->result->id)) {
            return true;
        }

        return false;
    }

    public function updatePageRule(
        string $zoneID,
        string $ruleID,
        PageRulesTargets $target = null,
        PageRulesActions $actions = null,
        bool $active = null,
        int $priority = null
    ): bool {
        $options = [];

        if ($target !== null) {
            $options['targets'] = $target->getArray();
        }

        if ($actions !== null) {
            $options['actions'] = $actions->getArray();
        }

        if ($active !== null) {
            $options['status'] = $active == true ? 'active' : 'disabled';
        }

        if ($priority !== null) {
            $options['priority'] = $priority;
        }

        $query = $this->adapter->patch('zones/' . $zoneID . '/pagerules/' . $ruleID, $options);

        $this->body = json_decode($query->getBody());

        if (isset($this->body->result->id)) {
            return true;
        }

        return false;
    }

    public function deletePageRule(string $zoneID, string $ruleID): bool
    {
        $user = $this->adapter->delete('zones/' . $zoneID . '/pagerules/' . $ruleID);

        $this->body = json_decode($user->getBody());

        if (isset($this->body->result->id)) {
            return true;
        }

        return false;
    }
}
