<?php declare(strict_types=1);

namespace BiiiiiigMonster\Throttle\Annotation\Parser;

use BiiiiiigMonster\Throttle\Annotation\Mapping\Throttle;
use BiiiiiigMonster\Throttle\Annotation\Mapping\Throttles;
use BiiiiiigMonster\Throttle\ThrottleRegister;
use Swoft\Annotation\Annotation\Mapping\AnnotationParser;
use Swoft\Annotation\Annotation\Parser\Parser;

/**
 * Class ThrottleParser
 * @package BiiiiiigMonster\Throttle\Annotation\Parser
 * @AnnotationParser(Throttles::class)
 */
class ThrottlesParser extends Parser
{
    /**
     * @param int $type
     * @param Throttles $annotationObject
     * @return array
     */
    public function parse(int $type, $annotationObject): array
    {
        $throttles = $annotationObject->getThrottles();

        if($type != self::TYPE_METHOD) {
            return [];
        }

        foreach ($throttles as $throttle) {
            if (!$throttle instanceof Throttle) {
                continue;
            }

            ThrottleRegister::register($this->className,$this->methodName,$throttle);
        }

        return [];
    }
}
