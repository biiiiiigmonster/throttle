<?php declare(strict_types=1);

namespace BiiiiiigMonster\Throttle\Annotation\Mapping;

use Doctrine\Common\Annotations\Annotation\Attribute;
use Doctrine\Common\Annotations\Annotation\Attributes;
use Doctrine\Common\Annotations\Annotation\Target;

/**
 * Class Throttle
 * @package BiiiiiigMonster\Throttle\Annotation\Mapping
 * @Annotation
 * @Target({"METHOD","ANNOTATION"})
 * @Attributes(
 *     @Attribute("frequency",type="string"),
 *     @Attribute("prefix",type="string"),
 *     @Attribute("key",type="string"),
 *     @Attribute("idempotent",type="bool"),
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
     * @example 1/1m,1/5m,5/30s... unit support [s:每秒,m:每分,h:每小时,d:每天]
     */
    private $frequency = '1/1m';

    /**
     * @var bool
     * 是否幂等返回
     * date(2020-04-29)：这个注解参数暂时无效了哈，是因为现在出现了一个矛盾的点；幂等这个需求是来源于想风控但又不想报错的出发点，
     * 然后我现在又想支持Throttle多次注解，就类似与Middleware跟Middlewares的方式，那么在批量设置的时候，如果出现有的注解项支持幂等设置，
     * 有的又不支持，那么当这个地方出现风控的时候，该以如何形式去处理，我就有点懵逼了；所以暂时就不处理这个参数了，以后再做打算。
     * 目前功能还是可以正常使用的，放心~
     */
    private $idempotent = false;

    /**
     * Throttle constructor.
     * @param array $values
     */
    public function __construct(array $values)
    {
        if (isset($values['value'])) {
            $this->frequency = $values['value'];
        }
        if (isset($values['frequency'])) {
            $this->frequency = $values['frequency'];
        }
        if (isset($values['key'])) {
            $this->key = $values['key'];
        }
        //暂时不建议自定义缓存标识前缀
        if (isset($values['prefix'])) {
            $this->prefix = $values['prefix'];
        }
        if (isset($values['idempotent'])) {
            $this->idempotent = $values['idempotent'];
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
    public function getFrequency(): string
    {
        return $this->frequency;
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @return bool
     */
    public function isIdempotent(): bool
    {
        return $this->idempotent;
    }
}
