<?php

declare(strict_types=1);

arch('it will not use debugging functions')
    ->expect(['dd', 'dump', 'var_dump', 'print_r'])
    ->each->not->toBeUsed();
