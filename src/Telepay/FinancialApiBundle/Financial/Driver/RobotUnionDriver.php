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

class RobotUnionDriver {

    private $botName;

    /**
     * RobotUnionDriver constructor.
     * @param $botName
     */
    public function __construct($botName) {
        $this->botName = $botName;
    }

    public function __call($name, $userArgs) {
        if($name != "execute") throw new \LogicException("Method '" . $name . "' is not defined");
        $args['execute'] = "now";
        $args['args'] = "\"" . implode("\" \"", $userArgs) . "\"";
        $request = new ApiRequest(
            "https://bots.robotunion.org/jobs",
            $this->botName,
            array(),
            "PUT",
            $args,
            array()
        );

        $requester = new JsonRequester();
        return $requester->send($request);
    }

}
