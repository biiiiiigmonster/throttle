<?php declare(strict_types=1);

namespace BiiiiiigMonster\Throttle;

use Swoft\Helper\ComposerJSON;
use Swoft\SwoftComponent;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use function dirname;

/**
 * Class AutoLoader
 */
class AutoLoader extends SwoftComponent
{
    /**
     * Get namespace and dir
     *
     * @return array
     * [
     *     namespace => dir path
     * ]
     */
    public function getPrefixDirs(): array
    {
        return [
            __NAMESPACE__ => __DIR__,
        ];
    }

    /**
     * Metadata information for the component.
     *
     * @return array
     * @see ComponentInterface::getMetadata()
     */
    public function metadata(): array
    {
        $jsonFile = dirname(__DIR__) . '/composer.json';

        return ComposerJSON::open($jsonFile)->getMetadata();
    }

    /**
     * @return array
     */
    public function beans(): array
    {
        return [
            'throttle' => [
                'class' => Throttle::class,
                'el' => bean('el'),
            ],
            'el' => [
                'class' => ExpressionLanguage::class,
            ],
        ];
    }
}
