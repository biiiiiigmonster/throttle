<?php declare(strict_types=1);


namespace BiiiiiigMonster\Throttle\Aspect;

use BiiiiiigMonster\Cache\CacheManager;
use BiiiiiigMonster\Throttle\Exception\ThrottleException;
use BiiiiiigMonster\Throttle\Throttle;
use BiiiiiigMonster\Throttle\ThrottleRegister;
use BiiiiiigMonster\Throttle\Annotation\Mapping\Throttle as ThrottleMapping;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use Swoft\Aop\Annotation\Mapping\After;
use Swoft\Aop\Annotation\Mapping\Aspect;
use Swoft\Aop\Annotation\Mapping\Before;
use Swoft\Aop\Annotation\Mapping\PointAnnotation;
use Swoft\Aop\Point\JoinPoint;
use Swoft\Aop\Point\ProceedingJoinPoint;
use Swoft\Bean\Annotation\Mapping\Inject;
use Swoft\Stdlib\Helper\StringHelper;

/**
 * Class ThrottleAspect
 * @package BiiiiiigMonster\Throttle\Aspect
 *
 * @Aspect()
 * @PointAnnotation(
 *     include={ThrottleMapping::class}
 * )
 */
class ThrottleAspect
{
    /**
     * @Inject()
     * @var Throttle
     */
    private Throttle $throttle;

    /**
     * @Inject()
     * @var CacheManager
     */
    private CacheManager $cache;

    /**
     * @Before()
     * @param JoinPoint $joinPoint
     * @throws ThrottleException
     */
    public function before(JoinPoint $joinPoint)
    {
        $argsMap = $joinPoint->getArgsMap();
        $method = $joinPoint->getMethod();
        $className = $joinPoint->getClassName();

        $throttles = ThrottleRegister::get($className,$method);
        $check = true;
        foreach ($throttles as $key=>[$prefix,$rate]) {
            //当rate不同格式的时候对应不同解析
            if(StringHelper::contains($rate,'|')) {
                [$maxAccept,$parse] = StringHelper::explode($rate,'|',2);
                $ttl = Carbon::now()->diffInRealSeconds(Carbon::parse($parse),false);
                /**
                 * 因为是绝对时间值，所以会存在过期现象
                 * 例如某个需求只用在2020-05-14 12:00:00时间之前做个限制，过了这个如期之后就可以不用考虑后续逻辑了
                 * 因此才有这个continue存在的意义
                 */
                if($ttl<=0) continue;
            } else {
                [$maxAccept,$intervalDefinition] = StringHelper::explode($rate,'/',2);
                $ttl = CarbonInterval::fromString($intervalDefinition);
            }

            $key = $this->cache->evaluateKey($key,$className,$method,$argsMap);
            $times = $this->cache->remember("{$prefix}{$key}",0,$ttl);
            if($times>=$maxAccept) {
                $check = false;
            }
            /**
             * 这个计数+1怎么加，在哪加，其实有一定说法，
             * 我先把下面的After注解取消了，后面再仔细定夺。
             */
            $this->cache->inc("{$prefix}{$key}");
        }

        if(!$check) {
            //这种验证方式存在一点点bug啊，不过要求不严格可以用，在一个$ttl内最多可以访问2*$maxAccept-1次,细细品
            throw new ThrottleException("{$joinPoint->getClassName()}->{$method}请求太频繁");
        }
    }

    /**
     * After()：这里先暂时用不上了
     * After注解，无论切点是正常执行还是抛出异常，都会执行此处
     * @param JoinPoint $joinPoint
     */
    public function after(JoinPoint $joinPoint)
    {
        $argsMap = $joinPoint->getArgsMap();
        $method = $joinPoint->getMethod();
        $className = $joinPoint->getClassName();

        $throttles = ThrottleRegister::get($className,$method);
        foreach ($throttles as $key=>[$prefix]) {
            $key = $this->cache->evaluateKey($key,$className,$method,$argsMap);
            //执行完毕，访问计数+1
            $this->cache->inc("{$prefix}{$key}");
        }
    }
}
