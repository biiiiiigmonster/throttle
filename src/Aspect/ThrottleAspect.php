<?php declare(strict_types=1);


namespace BiiiiiigMonster\Throttle\Aspect;

use BiiiiiigMonster\Throttle\Exception\ThrottleException;
use BiiiiiigMonster\Throttle\Throttle;
use BiiiiiigMonster\Throttle\ThrottleRegister;
use BiiiiiigMonster\Throttle\Annotation\Mapping\Throttle as ThrottleMapping;
use Swoft\Aop\Annotation\Mapping\Around;
use Swoft\Aop\Annotation\Mapping\Aspect;
use Swoft\Aop\Annotation\Mapping\Before;
use Swoft\Aop\Annotation\Mapping\PointAnnotation;
use Swoft\Aop\Point\JoinPoint;
use Swoft\Aop\Point\ProceedingJoinPoint;
use Swoft\Bean\Annotation\Mapping\Inject;
use Swoft\Redis\Redis;

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
     * @Inject("throttle")
     *
     * @var Throttle
     */
    private $throttle;

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

        [$prefix,$key,$maxAccept,$ttl,$idempotent] = ThrottleRegister::get($className,$method);
        if(!$key = $this->throttle->evaluateKey($key,$className,$method,$argsMap)) {
            $key = "$className@$method";
        }

        //第一次访问初始化计数，有效时间$ttl
        $times = remember("{$prefix}{$key}",1,$ttl);
        if($idempotent && $times>=$maxAccept) {
            /**
             * 如果设置了幂等， 并且在临界阈值访问次数时，将此次结果设置缓存
             * 例：当$maxAccept设置成5时，前四次访问均正常执行，到第五次的时候会将此次结果缓存，并且后续时间(Redis::ttl("{$prefix}{$key}"))
             *     内所有次访问均返回第五次的缓存结果，当然，这些操作的前提是设置了幂等返回
             */
            $return = remember("{$prefix}{$key}:idempotent",fn() => $proceedingJoinPoint->proceed(),Redis::ttl("{$prefix}{$key}"));
        } else {
            $return = $proceedingJoinPoint->proceed();
        }

        Redis::incr("{$prefix}{$key}");//正常执行，访问计数+1
        return $return;
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

        [$prefix,$key,$maxAccept,$ttl,$idempotent] = ThrottleRegister::get($className,$method);
        if(!$key = $this->throttle->evaluateKey($key,$className,$method,$argsMap)) {
            //如果没有从缓存注解中解析出有效key（因为ThrottleRegister注解key非必填），则采用默认规则来赋值key
            $key = "$className@$method";
        }
        $times = Redis::get("{$prefix}{$key}");

        if($times>$maxAccept && !$idempotent) {
            //这种验证方式存在一点点bug啊，不过要求不严格可以用，在一个$ttl内最多可以访问2*$maxAccept-1次,细细品
            //$idempotent：如果注解中设置幂等为true，则不抛出异常，允许正常执行
            throw new ThrottleException("{$joinPoint->getClassName()}->{$method}请求太频繁");
        }
    }
}
