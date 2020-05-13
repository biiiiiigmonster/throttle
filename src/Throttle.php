<?php declare(strict_types=1);


namespace BiiiiiigMonster\Throttle;


use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

/**
 * Class Throttle
 * @package BiiiiiigMonster\Throttle
 */
class Throttle
{
    /**
     * @var ExpressionLanguage
     */
    protected ExpressionLanguage $el;

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

        $values = array_merge($args,[
            'request' => context()->getRequest(),// 表达式支持请求对象
            'CLASS' => $className,
            'METHOD' => $method,
        ]);
        return (string)$this->el->evaluate($key, $values);
    }
}
