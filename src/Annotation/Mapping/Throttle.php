<?php declare(strict_types=1);

namespace BiiiiiigMonster\Throttle\Annotation\Mapping;

use Doctrine\Common\Annotations\Annotation\Attribute;
use Doctrine\Common\Annotations\Annotation\Attributes;
use Doctrine\Common\Annotations\Annotation\Required;
use Doctrine\Common\Annotations\Annotation\Target;

/**
 * Class Throttle
 * @package BiiiiiigMonster\Throttle\Annotation\Mapping
 * @Annotation
 * @Target({"METHOD"})
 * @Attributes(
 *     @Attribute("rate",type="string"),
 *     @Attribute("prefix",type="string"),
 *     @Attribute("key",type="string"),
 * )
 */
final class Throttle
{
    /**
     * @var string
     */
    private $prefix = 'throttle:';

    /**
     * @var string
     */
    private $key = '';

    /**
     * @var string
     * 支持两种格式，区分条件为分隔符
     * 1、相对时间值，分隔符为'/'，比如1分钟内限制访问1次。
     * 2、绝对时间值，分隔符为'|'，比如某时刻内限制访问1次。
     * @example 1/1m,1/2h 5m,5/30s... maxAccepts/intervalDefinition 分母表达式参考CarbonInterval::fromString入参
     * @example 1|tomorrow,1|+2 day 23:59:59... maxAccepts/parse 分母表达式参考Carbon::parse入参
     * Ps：第二种格式好像可以实现定点活动开启验证功能诶，例如这样:0|2020-12-31 12:00:00
     */
    private $rate = '1/1m';

    /**
     * Throttle constructor.
     * @param array $values
     */
    public function __construct(array $values)
    {
        if (isset($values['value'])) {
            $this->rate = $values['value'];
        }
        if (isset($values['rate'])) {
            $this->rate = $values['rate'];
        }
        if (isset($values['key'])) {
            $this->key = $values['key'];
        }
        //暂时不建议自定义缓存标识前缀
        if (isset($values['prefix'])) {
            $this->prefix = $values['prefix'];
        }
    }

    /**
     * @return string
     */
    public function getPrefix(): string
    {
        return $this->prefix;
    }

    /**
     * @return string
     */
    public function getRate(): string
    {
        return $this->rate;
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }
}
