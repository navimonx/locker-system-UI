<?php
function dept_background(string $dept = ''): string {
    $map = [
        'CAS'  => 'images/cas.jpg',
        'CABA' => 'images/caba.jpg',
        'CEIT' => 'images/ceit.jpg',
        'COED' => 'images/coed.jpg',
        'CPAG' => 'images/cpag.jpg',
        'NB'   => 'images/nb.jpg',
    ];
    return $map[$dept] ?? 'images/plv.jpg';
}
