<?php


namespace BiiiiiigMonster\Throttle;


use Swoft\Bean\Annotation\Mapping\Bean;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

/**
 * Class Throttle
 * @package BiiiiiigMonster\Throttle
 * @Bean("throttle")
 */
class Throttle
{
    /**
     * @param string $key
     * @param string $className
     * @param string $method
     * @param array $args
     * @return string
     */
    public function evaluateKey(string $key, string $className, string $method, array $args): string
    {
        if($key==='') return '';
        // Parse express language
        $el = new ExpressionLanguage();
        $values = array_merge($args,[
            'request' => context()->getRequest(),//表达式支持请求对象
            'CLASS' => $className,
            'METHOD' => $method,
        ]);
        return (string)$el->evaluate($key, $values);
    }
}
