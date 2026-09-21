<?php

it('renders the view as one inline script without Blade leftovers', function () {
    $html = view('livewire-google-analytics::script')->render();

    expect(substr_count($html, '<script>'))->toBe(1)
        ->and(substr_count($html, '</script>'))->toBe(1)
        ->and(trim($html))->toStartWith('<script>')->toEndWith('</script>')
        ->and($html)->not->toContain('{{')
        ->and($html)->not->toContain('@include');
});

it('renders the same script for every request when nothing was carried over', function () {
    $plain = view('livewire-google-analytics::script')->render();

    config(['app.name' => '</script><script>alert(1)</script>']);
    request()->merge(['name' => '</script><script>alert(1)</script>']);

    expect(view('livewire-google-analytics::script', ['name' => '</script>'])->render())
        ->toBe($plain)
        ->not->toContain('alert');
});

it('turns the queue off from the include, and only with a real false', function (mixed $queue, bool $off) {
    $script = viewScript(['queue' => $queue]);

    expect($script)->toContain($off ? 'if (true) {' : 'if (false) {')
        ->and($script)->not->toContain('alert');
})->with([
    'false' => [false, true],
    'true' => [true, false],
    'null' => [null, false],
    'zero' => [0, false],
    'a string that tries to leave the script' => ['</script><script>alert(1)</script>', false],
]);

it('drops events without gtag when the include turns the queue off', function () {
    $steps = [['run', 0], ['dispatch', ['name' => 'login', 'params' => ['method' => 'email']]], ['gtag', true], ['advance', 5000]];

    expect(runListener([viewScript(['queue' => false])], $steps)['calls'])->toBe([])
        ->and(runListener([viewScript()], $steps)['calls'])->toBe([['event', 'login', ['method' => 'email']]]);
})->skip(fn () => nodeIsMissing(), 'Node is not installed.');
