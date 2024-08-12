<?php

namespace Mastir\PhpDump\Mutator;

use Mastir\PhpDump\PhpDumpScope;

interface ScopeArgumentsMutator extends Mutator
{
    public function onScopeArguments(PhpDumpScope $scope, array &$arguments);
}
