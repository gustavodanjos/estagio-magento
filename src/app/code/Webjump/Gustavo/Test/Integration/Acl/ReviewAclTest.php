<?php

declare(strict_types=1);

namespace Webjump\Gustavo\Test\Integration\Acl;

use Magento\Authorization\Model\Acl\AclRetriever;
use Magento\Authorization\Model\Role;
use Magento\Authorization\Model\RoleFactory;
use Magento\Authorization\Model\RulesFactory;
use Magento\Framework\Acl\Builder as AclBuilder;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class ReviewAclTest extends TestCase
{
    private const ROLE_NAME = 'webjump_review_acl_test_role';

    private $roleFactory;
    private $rulesFactory;
    private $aclBuilder;
    private $aclRetriever;

    protected function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->roleFactory = $objectManager->create(RoleFactory::class);
        $this->rulesFactory = $objectManager->create(RulesFactory::class);
        $this->aclBuilder = $objectManager->create(AclBuilder::class);
        $this->aclRetriever = $objectManager->get(AclRetriever::class);
    }

    public function testReviewResourcesExistInAclTree(): void
    {
        $acl = $this->aclBuilder->getAcl();

        $this->assertContains('Webjump_Gustavo::webjump', $acl->getResources());
        $this->assertContains('Webjump_Gustavo::review', $acl->getResources());
        $this->assertContains('Webjump_Gustavo::review_export', $acl->getResources());
    }

    public function testRoleWithoutResourceIsNotAllowed(): void
    {
        $role = $this->createRoleWithResources([]);

        $this->assertFalse($this->isAllowed($role->getId(), 'Webjump_Gustavo::review'));
        $this->assertFalse($this->isAllowed($role->getId(), 'Webjump_Gustavo::review_export'));
    }

    public function testRoleWithResourceIsAllowed(): void
    {
        $role = $this->createRoleWithResources(['Webjump_Gustavo::review']);

        $this->assertTrue($this->isAllowed($role->getId(), 'Webjump_Gustavo::review'));
        $this->assertFalse(
            $this->isAllowed($role->getId(), 'Webjump_Gustavo::review_export'),
            'Export permission must be granted separately'
        );
    }

    private function createRoleWithResources(array $resources): Role
    {
        $role = $this->roleFactory->create();
        $role->setRoleName(self::ROLE_NAME . '_' . count($resources))
            ->setRoleType('G')
            ->save();

        $this->rulesFactory->create()
            ->setRoleId($role->getId())
            ->setResources($resources)
            ->saveRel();

        return $role;
    }

    private function isAllowed($roleId, string $resource): bool
    {
        $allowedResources = $this->aclRetriever->getAllowedResourcesByRole($roleId);

        return in_array($resource, $allowedResources, true)
            || in_array('Magento_Backend::all', $allowedResources, true);
    }
}
