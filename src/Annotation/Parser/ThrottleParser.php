<?php


namespace BiiiiiigMonster\Throttle\Annotation\Parser;


use BiiiiiigMonster\Throttle\Annotation\Mapping\Throttle;
use BiiiiiigMonster\Throttle\ThrottleRegister;
use Swoft\Annotation\Annotation\Parser\Parser;

class ThrottleParser extends Parser
{
    /**
     * @param int $type
     * @param Throttle $annotationObject
     * @return array
     */
    public function parse(int $type, $annotationObject): array
    {
        if ($type != self::TYPE_METHOD) {
            return [];
        }

        ThrottleRegister::register($this->className,$this->methodName,$annotationObject);
        return [];
    }
}
