<?php

/**
 * FavoritesService Test Class.
 *
 * PHP version 8
 *
 * Copyright (C) The National Library of Finland 2026.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, see
 * <https://www.gnu.org/licenses/>.
 *
 * @category VuFind
 * @package  Tests
 * @author   Minna Rönkä <minna.ronka@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:testing:unit_tests Wiki
 */

namespace FinnaTest\Favorites;

use Finna\Db\Entity\UserEntityInterface;
use Finna\Db\Entity\UserListEntityInterface;
use Finna\Db\Service\ResourceServiceInterface;
use Finna\Db\Service\UserListService;
use Finna\Db\Service\UserListServiceInterface;
use Finna\Db\Service\UserResourceService;
use Finna\Db\Service\UserResourceServiceInterface;
use Finna\Db\Service\UserServiceInterface;
use Finna\Favorites\FavoritesService;
use Finna\Record\Loader;
use PHPUnit\Framework\MockObject\MockObject;
use VuFind\Db\Entity\ResourceEntityInterface;
use VuFind\Db\Service\ResourceTagsService;
use VuFind\Record\ResourcePopulator;
use VuFind\Tags\TagsService;

/**
 * FavoritesService Test Class.
 *
 * @category VuFind
 * @package  Tests
 * @author   Minna Rönkä <minna.ronka@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:testing:unit_tests Wiki
 */
class FavoritesServiceTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Get a FavoritesService object.
     *
     * @param UserListServiceInterface     $listService         Mock list service (optional)
     * @param UserResourceServiceInterface $userResourceService Mock user resource service (optional)
     *
     * @return FavoritesService
     */
    protected function getFavoritesService(
        ?UserListServiceInterface $listService = null,
        ?UserResourceServiceInterface $userResourceService = null
    ): FavoritesService {
        return new FavoritesService(
            $this->createMock(ResourceServiceInterface::class),
            $this->createMock(ResourceTagsService::class),
            $listService ?? $this->createMock(UserListServiceInterface::class),
            $userResourceService ?? $this->createMock(UserResourceServiceInterface::class),
            $this->createMock(UserServiceInterface::class),
            $this->createMock(ResourcePopulator::class),
            $this->createMock(TagsService::class),
            $this->createMock(Loader::class)
        );
    }

    /**
     * Create a mock user object.
     *
     * @return MockObject&UserEntityInterface
     */
    protected function getMockUser(): MockObject&UserEntityInterface
    {
        $user = $this->createMock(UserEntityInterface::class);
        $user->method('getId')->willReturn(1234);
        return $user;
    }

    /**
     * Create a mock list object for mock user.
     *
     * @return MockObject&UserListEntityInterface
     */
    protected function getMockListForMockUser(): MockObject&UserListEntityInterface
    {
        $list = $this->createMock(UserListEntityInterface::class);
        $list->method('getUser')->willReturn($this->getMockUser());
        $list->method('getTitle')->willReturn('Mock List Title');
        $list->method('getId')->willReturn(12345);
        return $list;
    }

    /**
     * Create a mock list service expecting to update list timestamp and saving list once.
     *
     * @return MockObject&UserListServiceInterface
     */
    protected function getListServiceExpectingSavingListOnce(): MockObject&UserListServiceInterface
    {
        $list = $this->getMockListForMockUser();
        $list->expects($this->once())->method('setFinnaUpdated');
        $listService = $this->createMock(UserListService::class);
        $listService->method('getUserListById')->with(12345)->willReturn($list);
        $listService->expects($this->once())->method('persistEntity');
        return $listService;
    }

    /**
     * Test that saving user list updates finnaUpdated timestamp.
     *
     * @return void
     */
    public function testSaveListForUser()
    {
        $listService = $this->getListServiceExpectingSavingListOnce();
        $service = $this->getFavoritesService($listService);
        $service->saveListForUser($listService->getUserListById(12345), $this->getMockUser());
    }

    /**
     * Test saveMany.
     *
     * @return void
     */
    public function testSaveMany()
    {
        $listService = $this->getListServiceExpectingSavingListOnce();
        $service = $this->getFavoritesService($listService, $this->createMock(UserResourceService::class));
        $service->saveMany(['list' => 12345], $this->getMockUser(), []);
    }

    /**
     * Test saveResourceToFavorites.
     *
     * @return void
     */
    public function testSaveResourceToFavorites()
    {
        $listService = $this->getListServiceExpectingSavingListOnce();
        $service = $this->getFavoritesService($listService);
        $service->saveResourceToFavorites(
            $this->getMockUser(),
            $this->createMock(ResourceEntityInterface::class),
            $listService->getUserListById(12345),
            [],
            ''
        );
    }

    /**
     * Test updateResourceCustomOrder.
     *
     * @return void
     */
    public function testUpdateResourceCustomOrder()
    {
        $listService = $this->getListServiceExpectingSavingListOnce();
        $service = $this->getFavoritesService($listService, $this->createMock(UserResourceService::class));
        $service->updateResourceCustomOrder(
            $this->getMockUser(),
            $this->createMock(ResourceEntityInterface::class),
            $listService->getUserListById(12345),
            2
        );
    }

    /**
     * Test removeListResourcesById.
     *
     * @return void
     */
    public function testRemoveListResourcesById()
    {
        $listService = $this->getListServiceExpectingSavingListOnce();
        $service = $this->getFavoritesService($listService);
        $service->removeListResourcesById($listService->getUserListById(12345), $this->getMockUser(), [12345]);
    }

    /**
     * Test saveCustomFavoriteOrder.
     *
     * @return void
     */
    public function testSaveCustomFavoriteOrder()
    {
        $listService = $this->getListServiceExpectingSavingListOnce();
        $service = $this->getFavoritesService($listService);
        $service->saveCustomFavoriteOrder($this->getMockUser(), 12345, []);
    }

    /**
     * Test addListTag.
     *
     * @return void
     */
    public function testAddListTag()
    {
        $listService = $this->getListServiceExpectingSavingListOnce();
        $service = $this->getFavoritesService($listService);
        $service->addListTag('My Test Tag', $listService->getUserListById(12345), $this->getMockUser());
    }
}
