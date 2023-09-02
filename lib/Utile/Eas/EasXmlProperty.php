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

namespace OCA\EAS\Utile\Eas;

class EasXmlProperty
{
    private ?string $_namespace = null;
    private mixed $_contents = null;
    private bool $_opaque = false;

    /*Constructor method with arguments*/
    public function __construct(string $namespace, mixed $content, bool $opaque = false)
    {
        $this->_namespace = $namespace;
        $this->_contents = $content;
        $this->_opaque = $opaque;
    }

    public function getNamespace(): string {
        return $this->_namespace;
    }

    public function setNamespace(string $namespace): void {
        $this->_namespace = $namespace;
    }

    public function getContents(): mixed {
        return $this->_contents;
    }
    
    public function setContents(mixed $content): void {
        $this->_contents = $content;
    }

    public function hasContents(): bool {
        return isset($this->_contents);
    }

    public function getOpaque(): bool {
        return $this->_opaque;
    }
    
    public function setOpaque(bool $opaque): void {
        $this->_opaque = $opaque;
    }

}