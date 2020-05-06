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
 * @Target("METHOD")
 * @Attributes(
 *     @Attribute("throttles",type="array"),
 * )
 */
final class Throttles
{
    /**
         * Throttles
         *
         * @var array
         *
         * @Required()
         */
        private $throttles = [];

        /**
         * Throttles constructor.
         *
         * @param $values
         */
        public function __construct($values)
        {
            if (isset($values['value'])) {
                $this->throttles = $values['value'];
            }
            if (isset($values['throttles'])) {
                $this->throttles = $values['throttles'];
            }
        }

        /**
         * @return array
         */
        public function getThrottles(): array
        {
            return $this->throttles;
        }
}
