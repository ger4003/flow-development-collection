<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Package\Meta;

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
 * @package FLOW3
 * @subpackage Package
 * @version $Id:F3\FLOW3\Package\Test.php 201 2007-03-30 11:18:30Z robert $
 */

require_once('vfs/vfsStream.php');

/**
 * Testcase for the XML Meta writer
 *
 * @package FLOW3
 * @subpackage Package
 * @version $Id:F3\FLOW3\Package\Test.php 201 2007-03-30 11:18:30Z robert $
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class XMLWriterTest extends \F3\Testing\BaseTestCase {

	/**
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setUp() {
		\vfsStreamWrapper::register();
		\vfsStreamWrapper::setRoot(new \vfsStreamDirectory('testDirectory'));
	}

	/**
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function testWritePackageMetaCreatesXml() {
		$packageMetaPath = \vfsStream::url('testDirectory') . '/';

		$mockPackage = $this->getMock('F3\FLOW3\Package\PackageInterface');
		$mockPackage->expects($this->once())->method('getPackageMetaPath')->will($this->returnValue($packageMetaPath));

		$meta = new \F3\FLOW3\Package\Meta('YetAnotherTestPackage');
		$meta->setTitle('Yet another test package');
		$meta->setDescription('A test package to test the creation of the Package.xml by the Package Manager');
		$meta->setVersion('0.1.1');
		$meta->setState('Beta');
		$meta->addCategory('Testing');
		$meta->addCategory('System');
		$meta->addParty(new \F3\FLOW3\Package\Meta\Person('LeadDeveloper', 'Robert Lemke', 'robert@typo3.org', 'http://www.flow3.org', 'TYPO3 Association', 'robert'));
		$meta->addParty(new \F3\FLOW3\Package\Meta\Company(null, 'Acme Inc.', 'info@acme.com', 'http://www.acme.com'));
		$meta->addConstraint(new \F3\FLOW3\Package\Meta\PackageConstraint('depends', 'FLOW3', '1.0.0', '1.9.9'));
		$meta->addConstraint(new \F3\FLOW3\Package\Meta\SystemConstraint('depends', 'PHP', NULL, '5.3.0'));
		$meta->addConstraint(new \F3\FLOW3\Package\Meta\SystemConstraint('suggests', 'Memory', '16M'));

		$metaWriter = new \F3\FLOW3\Package\Meta\XMLWriter();
		$metaWriter->writePackageMeta($mockPackage, $meta);
		$this->assertXmlFileEqualsXmlFile($packageMetaPath . 'Package.xml', __DIR__ . '/../Fixtures/XMLWriterTest_Package.xml');
	}
}
?>