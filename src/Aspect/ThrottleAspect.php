<?php declare(strict_types=1);


namespace BiiiiiigMonster\Throttle\Aspect;

use BiiiiiigMonster\Cache\CacheManager;
use BiiiiiigMonster\Throttle\Exception\ThrottleException;
use BiiiiiigMonster\Throttle\Throttle;
use BiiiiiigMonster\Throttle\ThrottleRegister;
use BiiiiiigMonster\Throttle\Annotation\Mapping\Throttle as ThrottleMapping;
use BiiiiiigMonster\Throttle\Annotation\Mapping\Throttles as ThrottlesMapping;
use Swoft\Aop\Annotation\Mapping\After;
use Swoft\Aop\Annotation\Mapping\Aspect;
use Swoft\Aop\Annotation\Mapping\Before;
use Swoft\Aop\Annotation\Mapping\PointAnnotation;
use Swoft\Aop\Point\JoinPoint;
use Swoft\Aop\Point\ProceedingJoinPoint;
use Swoft\Bean\Annotation\Mapping\Inject;

/**
 * Class ThrottleAspect
 * @package BiiiiiigMonster\Throttle\Aspect
 *
 * @Aspect()
 * @PointAnnotation(
 *     include={ThrottleMapping::class,ThrottlesMapping::class}
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
        foreach ($throttles as [$prefix,$key,$maxAccept,$ttl]) {
            if(!$key = $this->throttle->evaluateKey($key,$className,$method,$argsMap)) {
                //如果没有从缓存注解中解析出有效key（因为ThrottleRegister注解key非必填），则采用默认规则来赋值key
                $key = "$className@$method";
            }
            $times = $this->cache->remember("{$prefix}{$key}",0,$ttl);
            if($times>=$maxAccept) {
                $check = false;
            }
        }

        if(!$check) {
            //这种验证方式存在一点点bug啊，不过要求不严格可以用，在一个$ttl内最多可以访问2*$maxAccept-1次,细细品
            //$idempotent：如果注解中设置幂等为true，则不抛出异常，允许正常执行
            throw new ThrottleException("{$joinPoint->getClassName()}->{$method}请求太频繁");
        }
    }

    /**
     * @After()
     * After注解，无论切点是正常执行还是抛出异常，都会执行此处
     * @param JoinPoint $joinPoint
     */
    public function after(JoinPoint $joinPoint)
    {
        $argsMap = $joinPoint->getArgsMap();
        $method = $joinPoint->getMethod();
        $className = $joinPoint->getClassName();

        $throttles = ThrottleRegister::get($className,$method);
        foreach ($throttles as [$prefix,$key,$maxAccept,$ttl]) {
            if(!$key = $this->throttle->evaluateKey($key,$className,$method,$argsMap)) {
                //如果没有从缓存注解中解析出有效key（因为ThrottleRegister注解key非必填），则采用默认规则来赋值key
                $key = "$className@$method";
            }
            //执行完毕，访问计数+1
            $this->cache->inc("{$prefix}{$key}");
        }
    }
}
