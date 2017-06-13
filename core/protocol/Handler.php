<?php
/*******************************************************************************
 *  This file is part of CatCore.
 *
 *  CatCore is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  CatCore is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Foobar.  If not, see <http://www.gnu.org/licenses/>.
 *******************************************************************************
 * Author: Lidanyang  <simonarthur2012@gmail.com>
 ******************************************************************************/


namespace core\protocol;


class Handler
{
    /**
     * @var IProtocol
     */
    private $protocol;

    public function __construct(IProtocol $protocol)
    {
        $this->protocol = $protocol;
    }

    public function handle($data)
    {
        $result = $this->protocol->parse($data);
    }
}