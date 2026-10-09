<?php
declare(strict_types=1);

namespace Webjump\Gustavo\Test\Integration\CustomerData;

use Magento\Customer\Model\Session;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\AbstractController;
use Webjump\Gustavo\CustomerData\ScareCounter;

class ScareCounterTest extends AbstractController
{
    private ScareCounter $sectionSource;
    private Session $session;

    protected function setUp(): void
    {
        parent::setUp();
        $objectManager = Bootstrap::getObjectManager();
        $this->sectionSource = $objectManager->get(ScareCounter::class);
        $this->session = $objectManager->get(Session::class);
        $this->session->unsetData(ScareCounter::SESSION_KEY);
    }

    public function testSectionStartsAtZero(): void
    {
        $this->assertSame(['count' => 0], $this->sectionSource->getSectionData());
    }

    public function testSectionReturnsPersistedCount(): void
    {
        $this->session->setData(ScareCounter::SESSION_KEY, 7);

        $this->assertSame(['count' => 7], $this->sectionSource->getSectionData());
    }

    public function testScareCounterSectionIsServedByCustomerData(): void
    {
        $this->session->setData(ScareCounter::SESSION_KEY, 5);

        $this->getRequest()
            ->setParam('sections', 'scare-counter')
            ->setParam('force_new_section_timestamp', 1);
        $this->dispatch('customer/section/load');

        $body = (string)$this->getResponse()->getBody();
        $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);

        $this->assertArrayHasKey('scare-counter', $decoded);
        $this->assertSame(5, $decoded['scare-counter']['count']);
    }

    public function testScareActionIncrementsSessionAndRespondsWithJsonOnly(): void
    {
        $this->session->setData(ScareCounter::SESSION_KEY, 2);

        $this->getRequest()
            ->setMethod('POST')
            ->getHeaders()
            ->addHeaderLine('X-Requested-With', 'XMLHttpRequest');
        $this->dispatch('halloween/scare');

        $this->assertSame(['count' => 3], $this->sectionSource->getSectionData());

        $body = (string)$this->getResponse()->getBody();
        $this->assertStringNotContainsString('<', $body);

        $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        $this->assertTrue($decoded['success']);
        $this->assertSame(3, $decoded['count']);
    }
}