<?php

arch('it will not use debugging functions')
    ->expect(['dd', 'dump', 'ray'])
    ->each->not->toBeUsed();

arch('exceptions extend FiberyException')
    ->expect('WMBH\Fibery\Exceptions')
    ->toExtend('WMBH\Fibery\Exceptions\FiberyException')
    ->ignoring('WMBH\Fibery\Exceptions\FiberyException');

arch('api managers have consistent naming')
    ->expect('WMBH\Fibery\Api')
    ->toHaveSuffix('Manager');
