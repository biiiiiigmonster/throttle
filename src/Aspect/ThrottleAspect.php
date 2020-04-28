<?php declare(strict_types=1);


namespace BiiiiiigMonster\Throttle\Aspect;

use BiiiiiigMonster\Cache\CacheManager;
use BiiiiiigMonster\Throttle\Exception\ThrottleException;
use BiiiiiigMonster\Throttle\Throttle;
use BiiiiiigMonster\Throttle\ThrottleRegister;
use BiiiiiigMonster\Throttle\Annotation\Mapping\Throttle as ThrottleMapping;
use Swoft\Aop\Annotation\Mapping\After;
use Swoft\Aop\Annotation\Mapping\Around;
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
 *     include={ThrottleMapping::class}
 * )
 */
class ThrottleAspect
{
    /**
     * @Inject()
     *
     * @var Throttle
     */
    private Throttle $throttle;

    /**
     * @Inject()
     * @var CacheManager
     */
    private CacheManager $cache;

    /**
     * @Around()
     * @param ProceedingJoinPoint $proceedingJoinPoint
     * @return mixed
     * @throws \Throwable
     */
    public function aroundAdvice(ProceedingJoinPoint $proceedingJoinPoint)
    {
        $method = $proceedingJoinPoint->getMethod();
        $argsMap = $proceedingJoinPoint->getArgsMap();
        $className = $proceedingJoinPoint->getClassName();

        $throttles = ThrottleRegister::get($className,$method);
        $isIdempotent = false;
        foreach ($throttles as [$prefix,$key,$maxAccept,$ttl,$idempotent]) {
            if(!$key = $this->throttle->evaluateKey($key,$className,$method,$argsMap)) {
                $key = "$className@$method";
            }

            //第一次访问初始化计数，有效时间$ttl
            $times = $this->cache->remember("{$prefix}{$key}",1,$ttl);
            if($times>=$maxAccept && $idempotent) {
                $isIdempotent = true;
                break;
            }
        }

//        if($isIdempotent) {
//            /**
//             * 如果设置了幂等， 并且在临界阈值访问次数时，将此次结果设置缓存
//             * 例：当$maxAccept设置成5时，前四次访问均正常执行，到第五次的时候会将此次结果缓存，并且后续时间(Redis::ttl("{$prefix}{$key}"))
//             *     内所有次访问均返回第五次的缓存结果，当然，这些操作的前提是设置了幂等返回
//             */
//            return $this->cache->remember("idempotent:$className@$method",fn() => $proceedingJoinPoint->proceed(),$this->cache->ttl("{$prefix}{$key}"));
//        }

        return $proceedingJoinPoint->proceed();
    }

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
        foreach ($throttles as [$prefix,$key,$maxAccept,$ttl,$idempotent]) {
            if(!$key = $this->throttle->evaluateKey($key,$className,$method,$argsMap)) {
                //如果没有从缓存注解中解析出有效key（因为ThrottleRegister注解key非必填），则采用默认规则来赋值key
                $key = "$className@$method";
            }
            $times = $this->cache->get("{$prefix}{$key}");
            if($times>$maxAccept) {
                $check = false;
                break;
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
     * @param JoinPoint $joinPoint
     */
    public function after(JoinPoint $joinPoint)
    {
        $argsMap = $joinPoint->getArgsMap();
        $method = $joinPoint->getMethod();
        $className = $joinPoint->getClassName();

        $throttles = ThrottleRegister::get($className,$method);
        foreach ($throttles as [$prefix,$key,$maxAccept,$ttl,$idempotent]) {
            if(!$key = $this->throttle->evaluateKey($key,$className,$method,$argsMap)) {
                //如果没有从缓存注解中解析出有效key（因为ThrottleRegister注解key非必填），则采用默认规则来赋值key
                $key = "$className@$method";
            }
            $this->cache->inc("{$prefix}{$key}");//执行完毕，访问计数+1
        }
    }
}
