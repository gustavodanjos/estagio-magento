<?php
declare(strict_types=1);

namespace Webjump\Gustavo\ViewModel;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\ScopeInterface;

class HalloweenCountdown implements ArgumentInterface
{
    private const XML_PATH_COUNTDOWN_ENABLED = 'webjump_gustavo/halloween_theme/countdown_enabled';

    private const TARGET_MONTH = 10;
    private const TARGET_DAY = 31;
    private const TARGET_HOUR = 23;
    private const TARGET_MINUTE = 59;
    private const TARGET_SECOND = 59;

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly TimezoneInterface $timezone
    ) {
    }

    public function isEnabled(): bool
    {
        $value = trim((string)$this->scopeConfig->getValue(
            self::XML_PATH_COUNTDOWN_ENABLED,
            ScopeInterface::SCOPE_STORE
        ));

        return $value === '' ? true : (bool)(int)$value;
    }

    public function getTargetTimestamp(): int
    {
        $timezone = new \DateTimeZone($this->timezone->getConfigTimezone());
        $now = new \DateTimeImmutable('now', $timezone);

        $target = $now
            ->setDate((int)$now->format('Y'), self::TARGET_MONTH, self::TARGET_DAY)
            ->setTime(self::TARGET_HOUR, self::TARGET_MINUTE, self::TARGET_SECOND);

        return $target->getTimestamp();
    }

    /**
     * Unit labels consumed by the Knockout component, indexed by pluralization key.
     *
     * @return array<string, array{singular: string, plural: string}>
     */
    public function getUnitLabels(): array
    {
        return [
            'day' => [
                'singular' => (string)__('day'),
                'plural' => (string)__('days'),
            ],
            'hour' => [
                'singular' => (string)__('hour'),
                'plural' => (string)__('hours'),
            ],
            'minute' => [
                'singular' => (string)__('minute'),
                'plural' => (string)__('minutes'),
            ],
            'second' => [
                'singular' => (string)__('second'),
                'plural' => (string)__('seconds'),
            ],
        ];
    }

    public function getMessageTemplate(): string
    {
        return (string)__('The Haunted Night ends in %1');
    }

    public function getConjunction(): string
    {
        return (string)__('and');
    }

    public function getEndedMessage(): string
    {
        return (string)__('The campaign is over. The spirits rest... for now.');
    }
}
