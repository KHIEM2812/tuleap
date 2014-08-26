<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tuleap\Testing\REST\v1;

use Tuleap\REST\JsonCast;
use Tuleap\User\REST\UserRepresentation;

class PreviousResultRepresentation {

    /**
     * @var DateTime
     */
    public $submitted_on;

    /**
     * @var UserRepresentation
     */
    public $submitted_by;

    /**
     * @var String
     */
    public $status;

    /**
     * @var String
     */
    public $result;

    public function build($submitted_on, UserRepresentation $submitted_by, $status, $result) {
        $this->submitted_on = JsonCast::toDate($submitted_on);
        $this->submitted_by = $submitted_by;
        $this->status       = $status;
        $this->result       = $result;
    }
}
