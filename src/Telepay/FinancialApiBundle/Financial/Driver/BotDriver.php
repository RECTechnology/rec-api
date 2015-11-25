<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 11/21/15
 * Time: 5:48 PM
 */

namespace Telepay\FinancialApiBundle\Financial\Driver;

use TelepayApi\Core\ApiRequest;
use TelepayApi\Core\JsonRequester;

class BotDriver {

    private $botId;

    /**
     * BotDriver constructor.
     * @param $botId
     */
    public function __construct($botId) {
        $this->botId = $botId;
    }

    public function __call($name, $userArgs) {
        $args['execute'] = "now";
        $args['args'] = "\"" . implode("\" \"", $userArgs) . "\"";
        $request = new ApiRequest(
            "https://bots.robotunion.org/jobs/" . $this->botId,
            $name,
            array(),
            "PUT",
            $args,
            array()
        );

        $requester = new JsonRequester();
        return $requester->send($request);
    }

}
