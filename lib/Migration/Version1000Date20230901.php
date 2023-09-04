<?php
declare(strict_types=1);

/**
* @copyright Copyright (c) 2023 Sebastian Krupinski <krupinski01@gmail.com>
*
* @author Sebastian Krupinski <krupinski01@gmail.com>
*
* @license AGPL-3.0-or-later
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

namespace OCA\EAS\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\SimpleMigrationStep;
use OCP\Migration\IOutput;

class Version1000Date20230901 extends SimpleMigrationStep {

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if (!$schema->hasTable('eas_integration_crls')) {
			$table = $schema->createTable('eas_integration_crls');
			// id
			$table->addColumn('id', Types::INTEGER, [
				'autoincrement' => true,
				'notnull' => true
			]);
			// user id
			$table->addColumn('uid', Types::STRING, [
				'length' => 255,
				'notnull' => true
			]);
			// correlation type
			$table->addColumn('type', Types::STRING, [
				'length' => 4,
				'notnull' => true
			]);
			// affiliation id
			$table->addColumn('aid', Types::INTEGER, [
				'notnull' => false
			]);
			// local object id
			$table->addColumn('loid', Types::STRING, [
				'length' => 255,
				'notnull' => true
			]);
			// local collection id
			$table->addColumn('lcid', Types::STRING, [
				'length' => 255,
				'notnull' => false
			]);
			// local object state
			$table->addColumn('lostate', Types::STRING, [
				'length' => 255,
				'notnull' => false
			]);
			// remote object id
			$table->addColumn('roid', Types::STRING, [
				'length' => 1024,
				'notnull' => true
			]);
			// remote collection id
			$table->addColumn('rcid', Types::STRING, [
				'length' => 1024,
				'notnull' => false
			]);
			// remote object state
			$table->addColumn('rostate', Types::STRING, [
				'length' => 1024,
				'notnull' => false
			]);
			// Lock state
			$table->addColumn('hlock', Types::INTEGER, [
				'notnull' => true,
				'default' => '0',
			]);
			// Lock holder
			$table->addColumn('hlockhd', Types::INTEGER, [
				'notnull' => true,
				'default' => '0',
			]);
			// Lock heart beat
			$table->addColumn('hlockhb', Types::INTEGER, [
				'notnull' => true,
				'default' => '0',
			]);
			// Lock heart beat
			$table->addColumn('hperformed', Types::INTEGER, [
				'notnull' => true,
				'default' => '0',
			]);
			// Lock heart beat
			$table->addColumn('haltered', Types::INTEGER, [
				'notnull' => true,
				'default' => '0',
			]);

			$table->setPrimaryKey(['id']);
		}

		return $schema;
	}
}
