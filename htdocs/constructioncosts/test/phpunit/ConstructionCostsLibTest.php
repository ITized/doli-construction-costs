<?php
/* Copyright (C) 2024-2026ITized <https://github.com/ITized>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file       htdocs/constructioncosts/test/phpunit/ConstructionCostsLibTest.php
 * \ingroup    constructioncosts
 * \brief      PHPUnit tests for lib/constructioncosts.lib.php functions
 */

// Stubs needed for the lib functions
if (!function_exists('complete_head_from_modules')) {
function complete_head_from_modules($conf, $langs, $object, &$head, &$h, $type, $mode = '')
{
// No-op in test
}
}

require_once dirname(__FILE__).'/../../lib/constructioncosts.lib.php';

/**
 * Class ConstructionCostsLibTest
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 */
class ConstructionCostsLibTest extends PHPUnit\Framework\TestCase
{
/**
 * @var object Mock user with all rights
 */
private $userAllRights;

/**
 * @var object Mock user with no rights
 */
private $userNoRights;

protected function setUp(): void
{
global $conf, $langs, $user;

$conf = new stdClass();
$conf->constructioncosts = new stdClass();
$conf->constructioncosts->enabled = 1;

$langs = new class {
public function load($f) {}
public function loadLangs($a) {}
public function trans($key) { return $key; }
};

// User with all rights
$this->userAllRights = new class {
public function hasRight($module, $object, $perm) {
return true;
}
};

// User with no rights
$this->userNoRights = new class {
public function hasRight($module, $object, $perm) {
return false;
}
};
}

/**
 * Test constructioncostsAdminPrepareHead returns settings and about tabs
 */
public function testAdminPrepareHeadReturnsTabs()
{
$head = constructioncostsAdminPrepareHead();

$this->assertIsArray($head);
$this->assertGreaterThanOrEqual(2, count($head));

// First tab: settings
$this->assertStringContainsString('setup.php', $head[0][0]);
$this->assertEquals('Settings', $head[0][1]);
$this->assertEquals('settings', $head[0][2]);

// Second tab: about
$this->assertStringContainsString('about.php', $head[1][0]);
$this->assertEquals('About', $head[1][1]);
$this->assertEquals('about', $head[1][2]);
}

/**
 * Test constructioncostsPrepareHead returns all tabs for a user with all rights
 */
public function testPrepareHeadAllRights()
{
global $user;
$user = $this->userAllRights;

$head = constructioncostsPrepareHead();

$this->assertIsArray($head);

// Collect tab IDs
$tabIds = array();
foreach ($head as $tab) {
$tabIds[] = $tab[2];
}

$this->assertContains('dashboard', $tabIds);
$this->assertContains('pricingrules', $tabIds);
$this->assertContains('worktemplates', $tabIds);
$this->assertContains('suppliers', $tabIds);
$this->assertContains('import', $tabIds);
$this->assertContains('supplierprices', $tabIds);
$this->assertContains('offerconvert', $tabIds);
}

/**
 * Test constructioncostsPrepareHead returns only dashboard for user with no rights
 */
public function testPrepareHeadNoRights()
{
global $user;
$user = $this->userNoRights;

$head = constructioncostsPrepareHead();

$this->assertIsArray($head);
$this->assertCount(1, $head);
$this->assertEquals('dashboard', $head[0][2]);
}

/**
 * Test dashboard tab URL has no mode parameter
 */
public function testDashboardTabUrl()
{
global $user;
$user = $this->userAllRights;

$head = constructioncostsPrepareHead();

$this->assertStringNotContainsString('mode=', $head[0][0]);
}

/**
 * Test each non-dashboard tab URL contains the correct mode parameter
 */
public function testTabUrlsContainMode()
{
global $user;
$user = $this->userAllRights;

$head = constructioncostsPrepareHead();

foreach ($head as $tab) {
if ($tab[2] !== 'dashboard') {
$this->assertStringContainsString('mode='.$tab[2], $tab[0], 'Tab '.$tab[2].' should have mode= in URL');
}
}
}
}
