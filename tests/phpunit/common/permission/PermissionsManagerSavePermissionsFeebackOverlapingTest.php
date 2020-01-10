<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\ForgeConfigSandbox;
use Tuleap\GlobalResponseMock;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class PermissionsManagerSavePermissionsFeebackOverlapingTest extends TestCase
{
    use MockeryPHPUnitIntegration, ForgeConfigSandbox, GlobalResponseMock;

    protected $permissions_manager;
    protected $project;
    protected $permission_type;
    protected $object_id;
    protected $permissions_dao;
    protected $project_id;
    private $normalizer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->project_id          = 404;
        $this->project             = \Mockery::spy(\Project::class)->shouldReceive('getId')->andReturns($this->project_id)->getMock();
        $this->permissions_dao     = \Mockery::spy(\PermissionsDao::class);
        $this->permission_type     = 'FOO';
        $this->object_id           = 'BAR';
        $this->permissions_manager = new PermissionsManager($this->permissions_dao);
        $this->normalizer = new PermissionsNormalizer();
        $this->permissions_dao->shouldReceive('clearPermission')->andReturns(true);
        $this->permissions_dao->shouldReceive('addPermission')->andReturns(true);
    }

    protected function expectPermissionsOnce($ugroup): void
    {
        $this->permissions_dao->shouldReceive('addPermission')->with($this->permission_type, $this->object_id, $ugroup)->once();
    }

    protected function savePermissions($ugroups): void
    {
        $this->permissions_manager->savePermissions($this->project, $this->object_id, $this->permission_type, $ugroups);
    }

    public function testItInformsThatProjectMembersIsSavedWhenSVNAdminWikiAdminAndProjectMembers(): void
    {
        $override_collection = new PermissionsNormalizerOverrideCollection();

        $this->normalizer->getNormalizedUGroupIds(
            $this->project,
            array(ProjectUGroup::SVN_ADMIN, ProjectUGroup::WIKI_ADMIN, ProjectUGroup::PROJECT_MEMBERS),
            $override_collection
        );

        $this->assertEquals(
            array(ProjectUGroup::SVN_ADMIN, ProjectUGroup::WIKI_ADMIN),
            $override_collection->getOverrideBy(ProjectUGroup::PROJECT_MEMBERS)
        );
    }

    public function testItInformsThatAnonymousOverlapProjectMembers(): void
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::ANONYMOUS);
        $this->project->shouldReceive('isPublic')->andReturns(true);

        $override_collection = new PermissionsNormalizerOverrideCollection();

        $this->normalizer->getNormalizedUGroupIds(
            $this->project,
            array(ProjectUGroup::ANONYMOUS, ProjectUGroup::PROJECT_MEMBERS),
            $override_collection
        );

        $this->assertEquals(
            array(ProjectUGroup::PROJECT_MEMBERS),
            $override_collection->getOverrideBy(ProjectUGroup::ANONYMOUS)
        );
    }
}
