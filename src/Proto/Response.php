<?php

/*
 * This file is part of the PhpSocks package.
 *
 * (c) 2024 Roman Vazhynskyi
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace PhpSocks\Proto;

use PhpSocks\Connection;
use PhpSocks\Exception\PhpSocksException;

/**
 * @internal
 */
interface Response
{
    /**
     * @throws PhpSocksException
     */
    public function receive(Connection $conn): void;
}
