<?php
/**
 * Copyright (c) 2012 Lukas Reschke <lukas@statuscode.ch>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test;

use OC\AppScriptDependency;
use OC\AppScriptSort;

/**
 * Class AppScriptSortTest
 *
 * @package Test
 * @group DB
 */
class AppScriptSortTest extends \Test\TestCase {
	protected function setUp(): void {
		parent::setUp();

		\OC_Util::$scripts = [];
	}

	public function testSort() {
		$scripts = [
			'first' => ['myFirstJSFile'],
			'core' => [
				'core/js/myFancyJSFile1',
				'core/js/myFancyJSFile4',
				'core/js/myFancyJSFile5',
				'core/js/myFancyJSFile1',
			],
			'files' => ['files/js/myFancyJSFile2'],
			'myApp5' => ['myApp5/js/myApp5JSFile'],
			'myApp' => ['myApp/js/myFancyJSFile3'],
			'myApp4' => ['myApp4/js/myApp4JSFile'],
			'myApp3' => ['myApp3/js/myApp3JSFile'],
			'myApp2' => ['myApp2/js/myApp2JSFile'],
		];
		$scriptDeps = [
			'first' => new AppScriptDependency('first', ['core']),
			'core' => new AppScriptDependency('core', ['core']),
			'files' => new AppScriptDependency('files', ['core']),
			'myApp5' => new AppScriptDependency('myApp5', ['myApp2']),
			'myApp' => new AppScriptDependency('myApp', ['core']),
			'myApp4' => new AppScriptDependency('myApp4', ['myApp3']),
			'myApp3' => new AppScriptDependency('myApp3', ['myApp2']),
			'myApp2' => new AppScriptDependency('myApp2', ['myApp']),
		];

		$scriptSort = new AppScriptSort($scripts, $scriptDeps);
		$sortedScripts = $scriptSort->sort();

		$sortedScriptKeys = array_keys($sortedScripts);

		// Core should appear first
		$this->assertEquals(
			0,
			array_search('core', $sortedScriptKeys, true)
		);

		// Dependencies should appear before their children
		$this->assertLessThan(
			array_search('files', $sortedScriptKeys, true),
			array_search('core', $sortedScriptKeys, true)
		);
		$this->assertLessThan(
			array_search('myApp2', $sortedScriptKeys, true),
			array_search('myApp', $sortedScriptKeys, true)
		);
		$this->assertLessThan(
			array_search('myApp3', $sortedScriptKeys, true),
			array_search('myApp2', $sortedScriptKeys, true)
		);
		$this->assertLessThan(
			array_search('myApp4', $sortedScriptKeys, true),
			array_search('myApp3', $sortedScriptKeys, true)
		);
		$this->assertLessThan(
			array_search('myApp5', $sortedScriptKeys, true),
			array_search('myApp2', $sortedScriptKeys, true)
		);

		// All apps still there
		foreach ($scripts as $app => $_) {
			$this->assertContains($app, $sortedScriptKeys);
		}
	}
}
