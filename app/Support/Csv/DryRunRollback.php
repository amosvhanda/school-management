<?php

namespace App\Support\Csv;

use RuntimeException;

/**
 * Thrown to force a transaction rollback after a successful dry-run row.
 */
class DryRunRollback extends RuntimeException
{
}
