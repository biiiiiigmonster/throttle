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
class Throttle
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
