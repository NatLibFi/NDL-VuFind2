<?php

/**
 * Trait which returns pre-configured db mocks
 *
 * PHP version 8
 *
 * Copyright (C) The National Library of Finland 2025.
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category VuFind
 * @package  Tests
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 */

namespace FinnaTest\Traits;

use Finna\Auth\ILSAuthenticator;
use Finna\Db\Row\FinnaResourceList;
use Finna\Db\Row\User;
use Finna\Db\Service\AccessTokenService;
use Finna\Db\Service\FinnaResourceListService;
use Finna\Db\Service\UserService;
use Finna\Db\Table\FinnaResourceList as TableFinnaResourceList;
use Finna\Db\Table\User as TableUser;
use Laminas\Db\ResultSet\ResultSet;
use Laminas\Http\Client;
use Laminas\Http\Response;
use PHPUnit\Framework\MockObject\MockObject;
use VuFind\Db\Row\AccessToken;
use VuFind\Db\Table\AccessToken as TableAccessToken;
use VuFindHttp\HttpService;

/**
 * Trait which returns pre-configured db mocks
 *
 * @category VuFind
 * @package  Tests
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 */
trait MockServicesTrait
{
    /**
     * Get Finna access token service as a mocked service
     *
     * @param array $dbEntities Array containing db entities to use as a database
     *
     * @return MockObject
     */
    public function getFinnaAccessTokenService(array $dbEntities = []): MockObject
    {
        $accessTokenService = $this->getMockBuilder(AccessTokenService::class)->onlyMethods(['getDbTable'])
            ->disableOriginalConstructor()->getMock();

        $accessTokens = [];
        foreach ($dbEntities as $entity) {
            $accessTokens[] = $this->getMockedRowObject(AccessToken::class, $entity);
        }
        $accessTokenTable = $this->getMockedTableObject(TableAccessToken::class, $accessTokens);
        $accessTokenService->expects($this->any())->method('getDbTable')->willReturn($accessTokenTable);
        return $accessTokenService;
    }

    /**
     * Get Finna resource list service as a mocked service
     *
     * @param array       $dbEntities  Array containing db entities to use as a database
     * @param ?MockObject $table       Mocked table
     * @param ?MockObject $rowTemplate Mocked row template as db entity
     *
     * @return MockObject
     */
    public function getFinnaResourceListService(
        array $dbEntities = [],
        ?MockObject $table = null,
        ?MockObject $rowTemplate = null
    ): MockObject {
        $resourceListService = $this->getMockBuilder(FinnaResourceListService::class)
            ->onlyMethods(['getDbTable', 'persistEntity'])->disableOriginalConstructor()->getMock();

        $resourceLists = [];
        foreach ($dbEntities as $entity) {
            $resourceLists[] = $this->getMockedRowObject(FinnaResourceList::class, $entity, $rowTemplate);
        }
        $resourceListTable = $this->getMockedTableObject(
            TableFinnaResourceList::class,
            $resourceLists,
            $table,
            $rowTemplate
        );
        $resourceListService->expects($this->any())->method('getDbTable')->willReturn($resourceListTable);
        return $resourceListService;
    }

    /**
     * Get Finna user service
     *
     * @param array $dbEntities Array containing db entities to use as a database
     *
     * @return MockObject
     */
    public function getFinnaUserService(array $dbEntities): MockObject
    {
        $userService = $this->getMockBuilder(UserService::class)->onlyMethods(['getDbTable'])
            ->disableOriginalConstructor()->getMock();
        $users = [];
        foreach ($dbEntities as $entity) {
            $users[] = $this->getMockedRowObject(User::class, $entity);
        }
        $userTable = $this->getMockedTableObject(TableUser::class, $users);
        $userService->expects($this->any())->method('getDbTable')->willReturn($userTable);
        return $userService;
    }

    /**
     * Get http service
     *
     * @param array $urlAndResponseMap Url and response map
     *
     * @return MockObject
     */
    public function getHttpService(array $urlAndResponseMap): MockObject
    {
        $httpService = $this->getMockBuilder(HttpService::class)
            ->onlyMethods(['createClient'])->disableOriginalConstructor()->getMock();
        foreach ($urlAndResponseMap as $url => $responseData) {
            $responseMock = $this->getMockBuilder(Response::class)
                ->onlyMethods(['isSuccess', 'getBody'])->disableOriginalConstructor()->getMock();
            $responseMock->expects($this->any())->method('isSuccess')->willReturn($responseData['success']);
            $responseMock->expects($this->any())->method('getBody')->willReturn($responseData['body']);

            $clientMock = $this->getMockBuilder(Client::class)
                ->onlyMethods(['send'])->disableOriginalConstructor()->getMock();
            $clientMock->expects($this->any())->method('send')->willReturn($responseMock);
            $httpService->expects($this->any())->method('createClient')->with($url)->willReturn($clientMock);
        }

        return $httpService;
    }

    /**
     * Get a mocked ILS Authenticator
     *
     * @param array $patron Patron logged in
     *
     * @return MockObject
     */
    public function getFinnaIlsAuthenticator(array $patron): MockObject
    {
        $ilsAuthenticator = $this->getMockBuilder(ILSAuthenticator::class)->onlyMethods(['storedCatalogLogin'])
            ->disableOriginalConstructor()->getMock();
        $ilsAuthenticator->expects($this->any())->method('storedCatalogLogin')->willReturn($patron);
        return $ilsAuthenticator;
    }

    /**
     * Create a mocked row object
     *
     * @param string      $name     Row class name
     * @param array       $data     Array containing data as key => value
     * @param ?MockObject $template Template for row object
     *
     * @return MockObject
     */
    public function getMockedRowObject(string $name, array $data, ?MockObject $template = null): MockObject
    {
        if (null === $template) {
            $rowObject = $this->getMockBuilder($name)->onlyMethods([])->disableOriginalConstructor()->getMock();
        } else {
            $rowObject = clone $template;
        }

        foreach ($data as $key => $value) {
            $rowObject->__set($key, $value);
        }
        return $rowObject;
    }

    /**
     * Create a mocked table object
     *
     * @param string      $name          Table class name
     * @param array       $dbRows        Database rows
     * @param ?MockObject $tableTemplate Mocked table
     * @param ?MockObject $newEntity     Mocked row template as new entity
     *
     * @return MockObject
     */
    public function getMockedTableObject(
        string $name,
        array $dbRows,
        ?MockObject $tableTemplate = null,
        ?MockObject $newEntity = null
    ): MockObject {
        if (null !== $tableTemplate) {
            return clone $tableTemplate;
        }
        $mockedTable = $this->getMockBuilder($name)->onlyMethods(['select', 'createRow', 'delete'])
            ->disableOriginalConstructor()->getMock();

        $mockedTable->expects($this->any())->method('select')->willReturnCallback(function ($query) use ($dbRows) {
            $resultSetRow = $this->getMockBuilder(ResultSet::class)->onlyMethods(['current'])
                ->disableOriginalConstructor()->getMock();
            $foundEntities = [];
            foreach ($dbRows as $entity) {
                // Loop through select query keys and values and compare those to the object
                foreach ($query as $key => $value) {
                    if ($entity->__get($key) !== $value) {
                        continue 2;
                    }
                }
                $foundEntities[] = $entity;
            }
            $resultSetRow->expects($this->any())->method('current')->willReturn($foundEntities[0] ?? null);
            return $resultSetRow;
        });
        $mockedTable->expects($this->any())->method('delete')->willReturn(1);
        if ($newEntity) {
            $mockedTable->expects($this->any())->method('createRow')->willReturn($newEntity);
        }
        return $mockedTable;
    }
}
