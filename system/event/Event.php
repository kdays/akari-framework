<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 15/1/22
 * Time: 下午10:17
 */

namespace Akari\system\event;


Class Event {

    public $params = NULL;

    public $eventName;

    public $eventId;

    public $chainPos = 0;

    protected $isPropagationStopped = FALSE;

    public function stop() {
        $this->isPropagationStopped = TRUE;
    }

    public function isPropagationStopped() {
        return $this->isPropagationStopped;
    }

}