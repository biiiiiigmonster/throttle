<?php declare(strict_types=1);


namespace BiiiiiigMonster\Throttle;

use BiiiiiigMonster\Throttle\Annotation\Mapping\Throttle;
use Carbon\CarbonInterval;
use Swoft\Stdlib\Helper\ArrayHelper;
use Swoft\Stdlib\Helper\StringHelper;

class ThrottleRegister
{
    /**
     * @var array
     */
    private static $throttle = [];

    /**
     * @param string $className
     * @param string $method
     * @param Throttle $throttle
     */
    public static function register(string $className,string $method,Throttle $throttle): void
    {
        [$maxAccepts,$intervalDefinition] = StringHelper::explode($throttle->getRate(),'/',2);
        $ttl = CarbonInterval::fromString($intervalDefinition);

        self::$throttle[$className][$method][$throttle->getKey()] = [$throttle->getPrefix(),$throttle->getRate()];
    }

    /**
     * @param string $className
     * @param string $method
     * @return array
     */
    public static function get(string $className,string $method): array
    {
        return self::$throttle[$className][$method] ?? [];
    }
}
