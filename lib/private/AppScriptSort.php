<?php
/**
 * @copyright Copyright (c) 2021, Jonas Meurer <jonas@freesources.org>
 *
 * @author Jonas Meurer <jonas@freesources.org>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OC;

/**
 * Sort scripts topologically by their dependencies
 * Implementation based on https://github.com/marcj/topsort.php
 */
class AppScriptSort {
	/** @var array */
	private $scripts;

	/** @var AppScriptDependency[] */
	private $scriptDeps;

	/** @var string[] */
	private $sortedScriptDeps;

	public function __construct(array $scripts, array $scriptDeps) {
		$this->scripts = $scripts;
		$this->scriptDeps = $scriptDeps;
	}

	/**
	 * Recursive topological sorting
	 *
	 * @param AppScriptDependency $app
	 * @param array|null $parents
	 */
	private function topSortVisit(AppScriptDependency $app, array &$parents = null): void {
		// Detect and log circular dependencies
		if (isset($parents[$app->getId()])) {
			\OCP\Util::writeLog('core', 'Circular dependency in app scripts at app ' . $app->getId(), \OCP\ILogger::ERROR);
		}

		// If app has not been visited
		if (!$app->isVisited()) {
			$parents[$app->getId()] = true;
			$app->setVisited(true);

			foreach ($app->getDeps() as $dep) {
				if ($app->getId() === $dep) {
					// Ignore dependency on itself
					continue;
				}

				if (isset($this->scriptDeps[$dep])) {
					$newParents = $parents;
					$this->topSortVisit($this->scriptDeps[$dep], $newParents);
				}
			}

			$this->sortedScriptDeps[] = $app->getId();
		}
	}

	/**
	 * @return array scripts sorted by dependencies
	 */
	public function sort(): array {
		// Sort scriptDeps into sortedScriptDeps
		foreach ($this->scriptDeps as $app) {
			$parents = [];
			$this->topSortVisit($app, $parents);
		}

		// Sort scripts into sortedScripts based on sortedScriptDeps order
		$sortedScripts = [];
		foreach ($this->sortedScriptDeps as $app) {
			$sortedScripts[$app] = $this->scripts[$app] ?? [];
		}

		// Add remaining scripts
		foreach ($this->scripts as $app => $scripts) {
			if (!isset($sortedScripts[$app])) {
				$sortedScripts[$app] = $this->scripts[$app];
			}
		}

		return $sortedScripts;
	}
}
