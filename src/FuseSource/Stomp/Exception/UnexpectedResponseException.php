<?php
namespace FuseSource\Stomp\Exception;

use FuseSource\Stomp\Frame;

/**
 *
 * Copyright 2005-2006 The Apache Software Foundation
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

/* vim: set expandtab tabstop=3 shiftwidth=3: */

/**
 * Exception that occurs, when a frame / response was received that was not expected at this moment.
 *
 *
 * @package Stomp
 * @author Jens Radtke <swefl.oss@fin-sn.de>
 */
class UnexpectedResponseException extends StompException
{
    /**
     *
     * @var Frame
     */
    private $_frame;

    /**
     *
     * @param Frame $frame
     * @param string $expectedInfo
     */
    function __construct(Frame $frame, $expectedInfo)
    {
        $this->_frame = $frame;
        parent::__construct(sprintf('Unexpected response received. %s', $expectedInfo));
    }

    /**
     *
     * @return Frame
     */
    public function getFrame()
    {
        return $this->_frame;
    }

}
