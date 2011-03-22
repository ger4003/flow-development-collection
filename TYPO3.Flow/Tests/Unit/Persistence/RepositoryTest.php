<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Tests\Unit\Persistence;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the base Repository
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class RepositoryTest extends \F3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function abstractRepositoryImplementsRepositoryInterface() {
		$repository = new \F3\FLOW3\Persistence\Repository;
		$this->assertTrue($repository instanceof \F3\FLOW3\Persistence\RepositoryInterface);
	}

	/**
	 * dataProvider for constructSetsObjectTypeFromClassName
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function modelAndRepositoryClassNames() {
		return array(
			array('F3\Blog\Domain\Repository', 'BlogRepository', 'F3\Blog\Domain\Model\Blog'),
			array('Domain\Repository\Content', 'PageRepository', 'Domain\Model\Content\Page')
		);
	}

	/**
	 * @test
	 * @dataProvider modelAndRepositoryClassNames
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function constructSetsObjectTypeFromClassName($repositoryNamespace, $repositoryClassName, $modelClassName) {
		$idSuffix = uniqid();
		$mockClassName = $repositoryNamespace . '\\' . $repositoryClassName . $idSuffix;
		eval('namespace ' . $repositoryNamespace . '; class ' . $repositoryClassName . $idSuffix . ' extends \F3\FLOW3\Persistence\Repository {
			public function _getObjectType() {
				return $this->objectType;
			}
		}');

		$repository = new $mockClassName();
		$this->assertEquals($modelClassName . $idSuffix, $repository->_getObjectType());
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function createQueryCallsPersistenceManagerWithExpectedClassName() {
		$mockPersistenceManager = $this->getMock('F3\FLOW3\Persistence\Generic\PersistenceManager');
		$mockPersistenceManager->expects($this->once())->method('createQueryForType')->with('ExpectedType');

		$repository = $this->getAccessibleMock('F3\FLOW3\Persistence\Repository', array('dummy'));
		$repository->_set('objectType', 'ExpectedType');
		$repository->injectPersistenceManager($mockPersistenceManager);

		$repository->createQuery();
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function createQuerySetsDefaultOrderingIfDefined() {
		$orderings = array('foo' => \F3\FLOW3\Persistence\QueryInterface::ORDER_ASCENDING);
		$mockQuery = $this->getMock('F3\FLOW3\Persistence\QueryInterface');
		$mockQuery->expects($this->once())->method('setOrderings')->with($orderings);
		$mockPersistenceManager = $this->getMock('F3\FLOW3\Persistence\Generic\PersistenceManager');
		$mockPersistenceManager->expects($this->exactly(2))->method('createQueryForType')->with('ExpectedType')->will($this->returnValue($mockQuery));

		$repository = $this->getAccessibleMock('F3\FLOW3\Persistence\Repository', array('dummy'));
		$repository->_set('objectType', 'ExpectedType');
		$repository->injectPersistenceManager($mockPersistenceManager);
		$repository->setDefaultOrderings($orderings);
		$repository->createQuery();

		$repository->setDefaultOrderings(array());
		$repository->createQuery();
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function findAllCreatesQueryAndReturnsResultOfExecuteCall() {
		$expectedResult = $this->getMock('F3\FLOW3\Persistence\QueryResultInterface');

		$mockQuery = $this->getMock('F3\FLOW3\Persistence\QueryInterface');
		$mockQuery->expects($this->once())->method('execute')->with()->will($this->returnValue($expectedResult));

		$repository = $this->getMock('F3\FLOW3\Persistence\Repository', array('createQuery'));
		$repository->expects($this->once())->method('createQuery')->will($this->returnValue($mockQuery));

		$this->assertSame($expectedResult, $repository->findAll());
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function findByidentifierReturnsResultOfGetObjectByIdentifierCall() {
		$identifier = '123-456';
		$object = new \stdClass();

		$mockPersistenceManager = $this->getMock('F3\FLOW3\Persistence\PersistenceManagerInterface');
		$mockPersistenceManager->expects($this->once())->method('getObjectByIdentifier')->with($identifier, 'stdClass')->will($this->returnValue($object));

		$repository = $this->getAccessibleMock('F3\FLOW3\Persistence\Repository', array('createQuery'));
		$repository->injectPersistenceManager($mockPersistenceManager);
		$repository->_set('objectType', 'stdClass');

		$this->assertSame($object, $repository->findByIdentifier($identifier));
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function addDelegatesToPersistenceManager() {
		$object = new \stdClass();
		$mockPersistenceManager = $this->getMock('F3\FLOW3\Persistence\PersistenceManagerInterface');
		$mockPersistenceManager->expects($this->once())->method('add')->with($object);
		$repository = $this->getAccessibleMock('F3\FLOW3\Persistence\Repository', array('dummy'));
		$repository->injectPersistenceManager($mockPersistenceManager);
		$repository->_set('objectType', get_class($object));
		$repository->add($object);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function removeDelegatesToPersistenceManager() {
		$object = new \stdClass();
		$mockPersistenceManager = $this->getMock('F3\FLOW3\Persistence\PersistenceManagerInterface');
		$mockPersistenceManager->expects($this->once())->method('remove')->with($object);
		$repository = $this->getAccessibleMock('F3\FLOW3\Persistence\Repository', array('dummy'));
		$repository->injectPersistenceManager($mockPersistenceManager);
		$repository->_set('objectType', get_class($object));
		$repository->remove($object);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function updateDelegatesToPersistenceManager() {
		$object = new \stdClass();
		$mockPersistenceManager = $this->getMock('F3\FLOW3\Persistence\PersistenceManagerInterface');
		$mockPersistenceManager->expects($this->once())->method('merge')->with($object);
		$repository = $this->getAccessibleMock('F3\FLOW3\Persistence\Repository', array('dummy'));
		$repository->injectPersistenceManager($mockPersistenceManager);
		$repository->_set('objectType', get_class($object));
		$repository->update($object);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function magicCallMethodAcceptsFindBySomethingCallsAndExecutesAQueryWithThatCriteria() {
		$mockQueryResult = $this->getMock('F3\FLOW3\Persistence\QueryResultInterface');
		$mockQuery = $this->getMock('F3\FLOW3\Persistence\QueryInterface');
		$mockQuery->expects($this->once())->method('equals')->with('foo', 'bar')->will($this->returnValue('matchCriteria'));
		$mockQuery->expects($this->once())->method('matching')->with('matchCriteria')->will($this->returnValue($mockQuery));
		$mockQuery->expects($this->once())->method('execute')->with()->will($this->returnValue($mockQueryResult));

		$repository = $this->getMock('F3\FLOW3\Persistence\Repository', array('createQuery'));
		$repository->expects($this->once())->method('createQuery')->will($this->returnValue($mockQuery));

		$this->assertSame($mockQueryResult, $repository->findByFoo('bar'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function magicCallMethodAcceptsFindOneBySomethingCallsAndExecutesAQueryWithThatCriteria() {
		$object = new \stdClass();
		$mockQueryResult = $this->getMock('F3\FLOW3\Persistence\QueryResultInterface');
		$mockQueryResult->expects($this->once())->method('getFirst')->will($this->returnValue($object));
		$mockQuery = $this->getMock('F3\FLOW3\Persistence\QueryInterface');
		$mockQuery->expects($this->once())->method('equals')->with('foo', 'bar')->will($this->returnValue('matchCriteria'));
		$mockQuery->expects($this->once())->method('matching')->with('matchCriteria')->will($this->returnValue($mockQuery));
		$mockQuery->expects($this->once())->method('execute')->will($this->returnValue($mockQueryResult));

		$repository = $this->getMock('F3\FLOW3\Persistence\Repository', array('createQuery'));
		$repository->expects($this->once())->method('createQuery')->will($this->returnValue($mockQuery));

		$this->assertSame($object, $repository->findOneByFoo('bar'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function magicCallMethodAcceptsCountBySomethingCallsAndExecutesAQueryWithThatCriteria() {
		$mockQuery = $this->getMock('F3\FLOW3\Persistence\QueryInterface');
		$mockQuery->expects($this->once())->method('equals')->with('foo', 'bar')->will($this->returnValue('matchCriteria'));
		$mockQuery->expects($this->once())->method('matching')->with('matchCriteria')->will($this->returnValue($mockQuery));
		$mockQuery->expects($this->once())->method('count')->will($this->returnValue(2));

		$repository = $this->getMock('F3\FLOW3\Persistence\Repository', array('createQuery'));
		$repository->expects($this->once())->method('createQuery')->will($this->returnValue($mockQuery));

		$this->assertSame(2, $repository->countByFoo('bar'));
	}

	/**
	 * @test
	 * @expectedException Exception
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function magicCallMethodTriggersAnErrorIfUnknownMethodsAreCalled() {
		$repository = $this->getMock('F3\FLOW3\Persistence\Repository', array('dummy'));
		$repository->__call('foo', array());
	}

	/**
	 * @test
	 * @expectedException F3\FLOW3\Persistence\Exception\IllegalObjectTypeException
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function addChecksObjectType() {
		$repository = $this->getAccessibleMock('F3\FLOW3\Persistence\Repository', array('dummy'));
		$repository->_set('objectType', 'ExpectedObjectType');

		$repository->add(new \stdClass());
	}

	/**
	 * @test
	 * @expectedException F3\FLOW3\Persistence\Exception\IllegalObjectTypeException
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function removeChecksObjectType() {
		$repository = $this->getAccessibleMock('F3\FLOW3\Persistence\Repository', array('dummy'));
		$repository->_set('objectType', 'ExpectedObjectType');

		$repository->remove(new \stdClass());
	}
	/**
	 * @test
	 * @expectedException F3\FLOW3\Persistence\Exception\IllegalObjectTypeException
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function updateChecksObjectType() {
		$repository = $this->getAccessibleMock('F3\FLOW3\Persistence\Repository', array('dummy'));
		$repository->_set('objectType', 'ExpectedObjectType');

		$repository->update(new \stdClass());
	}
}

?>