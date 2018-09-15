<?php
/**
 * @var Kahlan\Cli\Kahlan $this
 */

$spec_dir = implode(DS, [
    __DIR__,
    'tests',
    'Unit'
]);

define('CONFIG_SET', 'default');

/** @var \Kahlan\Cli\CommandLine $commandLine */
$commandLine = $this->commandLine();
$commandLine->option('spec', CONFIG_SET, $spec_dir);
$commandLine->option('cc', CONFIG_SET, true);
$commandLine->option('reporter', CONFIG_SET, 'verbose');


