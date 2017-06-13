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


namespace core\protocol\swoole;

use core\protocol\IProtocol;

class Swoole implements IProtocol
{
    public function parse($data)
    {
        return \swoole_serialize::unpack($data);
    }

    public function pack($id, $data)
    {
        $data = \swoole_serialize::pack($data);
        $len = strlen($data);
        return \pack("NN" , $len, $id) . $data;
    }

    public function checkLength($data)
    {
        $data = substr($data, 4);
        return [null, $data];
    }

    public function defaultSetting()
    {
        $config["open_length_check"]      = true;
        $config["package_length_type"]    = 'N';
        $config["package_length_offset"]  = 0;
        $config["package_body_offset"]    = 4;
        return $config;
    }
}