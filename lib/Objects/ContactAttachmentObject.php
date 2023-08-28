<?php
//declare(strict_types=1);

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

namespace OCA\EAS\Objects;

class ContactAttachmentObject {

    public string $Id;
    public ?string $Name;
	public ?string $Type;
    public ?string $Encoding;
    public ?string $Flag;
    public ?string $Size;
    public ?string $Data;
    
    public function __construct(
        string $id = null,
        string $name = null,
        string $type = null,
        string $encoding = null,
        string $flag = null,
        string $size = null,
        string $data = null
    ) {
        $this->Id = $id;
        $this->Name = $name;
        $this->Type = $type;
        $this->Encoding = $encoding;
        $this->Flag = $flag;
        $this->Size = $size;
        $this->Data = $data;
	}
}
