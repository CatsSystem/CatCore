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

namespace core\protocol\hprose;

use core\protocol\IProtocol;
use Exception;

class Hprose implements IProtocol
{
    public function parse($data)
    {
        $stream = new BytesIO($data);
        try {
            switch ($stream->getc()) {
                case Tags::TagCall: {
                    $data = $this->doInvoke($stream);
                    $stream->close();
                    return $data;
                }
                case Tags::TagEnd: {
                    $stream->close();
                    return null;
                }
                default:
                    throw new \Exception("Wrong Request: \r\n$data");
            }
        }
        catch (\Exception $e) {
            $stream->close();
            throw $e;
        }
    }

    public function pack($id, $data)
    {
        $stream = new BytesIO();
        $writer = new Writer($stream, true);
        if(empty($data))
        {
            $stream->write(Tags::TagFunctions);
            $writer->writeArray([]);
        }
        else
        {
            $stream->write($data);
        }
        $stream->write(Tags::TagEnd);
        $data = $stream->toString();
        $stream->close();
        $dataLength = strlen($data);
        return pack("N", $dataLength) . $data;
    }

    protected function doInvoke(BytesIO $stream)
    {
        $reader = new Reader($stream);
        $reader->reset();
        $name = $reader->readString();
        $alias = strtolower($name);
        $args = array();
        $tag = $stream->getc();
        if ($tag === Tags::TagList) {
            $reader->reset();
            $args = $reader->readListWithoutTag();
            $tag = $stream->getc();
            if ($tag === Tags::TagTrue) {
                $tag = $stream->getc();
            }
        }
        if ($tag !== Tags::TagEnd && $tag !== Tags::TagCall) {
            $data = $stream->toString();
            throw new Exception("Unknown tag: $tag\r\nwith following data: $data");
        }
        return [$alias, $args];
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