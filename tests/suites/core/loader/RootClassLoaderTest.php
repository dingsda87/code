<?php
/**
 * <!--
 * This file is part of the adventure php framework (APF) published under
 * http://adventure-php-framework.org.
 *
 * The APF is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * The APF is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with the APF. If not, see http://www.gnu.org/licenses/lgpl-3.0.txt.
 * -->
 */
namespace APF\tests\suites\core\loader;

use APF\core\loader\RootClassLoader;
use APF\core\loader\StandardClassLoader;
use Exception;

/**
 * @package APF\tests\suites\core\loader
 * @class RootClassLoaderTest
 *
 * Tests the capabilities of the RootClassLoader.
 *
 * @author Christian Achatz
 * @version
 * Version 0.1, 27.02.2014<br />
 */
class RootClassLoaderTest extends \PHPUnit_Framework_TestCase {

   const VENDOR = 'VENDOR';
   const SOURCE_PATH = '/var/www/html/src';

   protected function setUp() {
      RootClassLoader::addLoader(new StandardClassLoader(self::VENDOR, self::SOURCE_PATH));
   }

   public function testValidVendor() {
      $vendor = RootClassLoader::getLoaderByVendor(self::VENDOR);
      assertEquals(
         self::SOURCE_PATH,
         $vendor->getRootPath()
      );
      assertEquals(
         self::SOURCE_PATH,
         $vendor->getConfigurationRootPath()
      );
   }

   public function testIllegalVendor() {
      $this->setExpectedException('InvalidArgumentException');
      RootClassLoader::getLoaderByVendor('FOO');
   }

   public function testGetLoaderByNamespace() {

      try {
         $loader = RootClassLoader::getLoaderByNamespace(self::VENDOR . '\foo\bar');
         assertEquals(
            self::SOURCE_PATH,
            $loader->getRootPath()
         );
      } catch (Exception $e) {
         $this->fail($e->getMessage());
      }

      try {
         $loader = RootClassLoader::getLoaderByNamespace(self::VENDOR . '\foo');
         assertEquals(
            self::SOURCE_PATH,
            $loader->getRootPath()
         );
      } catch (Exception $e) {
         $this->fail($e->getMessage());
      }

   }

   public function testGetLoaderByNamespacedClass() {

      $vendor = RootClassLoader::getLoaderByClass(self::VENDOR . '\foo\Bar');
      assertEquals(
         self::SOURCE_PATH,
         $vendor->getRootPath()
      );

      $vendor = RootClassLoader::getLoaderByClass(self::VENDOR . '\foo\bar\Baz');
      assertEquals(
         self::SOURCE_PATH,
         $vendor->getRootPath()
      );

   }

   public function testGetLoaderByVendorOnlyClass() {

      $vendor = RootClassLoader::getLoaderByClass(self::VENDOR . '\Bar');
      assertEquals(
         self::SOURCE_PATH,
         $vendor->getRootPath()
      );

   }

   public function testGetClassName() {
      assertEquals(
         'StandardClassLoader',
         RootClassLoader::getClassName('APF\core\loader\StandardClassLoader')
      );
      assertEquals(
         'StandardClassLoader',
         RootClassLoader::getClassName('APF\StandardClassLoader')
      );
   }

   public function testGetNamespace() {
      assertEquals(
         'APF\core\loader',
         RootClassLoader::getNamespace('APF\core\loader\StandardClassLoader')
      );
      assertEquals(
         'APF',
         RootClassLoader::getNamespace('APF\StandardClassLoader')
      );
   }

   public function testGetNamespaceWithoutVendor() {
      assertEquals(
         'core\loader',
         RootClassLoader::getNamespaceWithoutVendor('APF\core\loader\StandardClassLoader')
      );
      assertEquals(
         'core',
         RootClassLoader::getNamespaceWithoutVendor('APF\core\StandardClassLoader')
      );
   }

   public function testGetVendor() {
      assertEquals(
         'APF',
         RootClassLoader::getVendor('APF\StandardClassLoader')
      );
      assertEquals(
         'APF',
         RootClassLoader::getVendor('APF\core\loader\StandardClassLoader')
      );
   }

}
