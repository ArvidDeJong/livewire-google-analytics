<?php

/*
 * The listener runs in Node against a fake window and a fake clock, see tests/Fixtures/run-listener.js.
 * Every case runs against the Blade view and against the publishable file: they have to behave the same.
 */

dataset('listeners', [
    'view' => fn () => viewScript(),
    'file' => fn () => fileScript(),
]);

function lead(string $form): array
{
    return ['name' => 'generate_lead', 'params' => ['form_name' => $form]];
}

beforeEach(function () {
    if (nodeIsMissing()) {
        $this->markTestSkipped('Node is not installed.');
    }
});

it('forwards a named event to gtag and ignores everything else', function (string $script) {
    $result = runListener([$script], [
        ['run', 0],
        ['gtag', true],
        ['dispatch', ['name' => 'generate_lead', 'params' => ['form_name' => 'contact', 'value' => "'); alert(1); //</script>"]]],
        ['dispatch', ['name' => 'login']],
        ['dispatch', ['params' => ['form_name' => 'nameless']]],
        ['dispatch', ['name' => '', 'params' => ['form_name' => 'empty name']]],
        ['dispatch', null],
    ]);

    expect($result['listens_to'])->toBe(['ga:event'])
        ->and($result['errors'])->toBe([])
        ->and($result['timers'])->toBe(0)
        ->and($result['calls'])->toBe([
            ['event', 'generate_lead', ['form_name' => 'contact', 'value' => "'); alert(1); //</script>"]],
            ['event', 'login', []],
        ]);
})->with('listeners');

it('keeps the events that arrive before gtag and sends them in order once it is there', function (string $script) {
    $result = runListener([$script], [
        ['run', 0],
        ['dispatch', lead('first')],
        ['advance', 400],
        ['dispatch', lead('second')],
        ['advance', 5000],
        ['gtag', true],
        ['advance', 1000],
        ['advance', 60000],
        ['dispatch', lead('third')],
    ]);

    expect($result['errors'])->toBe([])
        ->and($result['calls'])->toBe([
            ['event', 'generate_lead', ['form_name' => 'first']],
            ['event', 'generate_lead', ['form_name' => 'second']],
            ['event', 'generate_lead', ['form_name' => 'third']],
        ])
        // Nothing is waiting any more, so nothing keeps polling.
        ->and($result['timers'])->toBe(0);
})->with('listeners');

it('sends the waiting events before a new one when the next event finds gtag', function (string $script) {
    $result = runListener([$script], [
        ['run', 0],
        ['dispatch', lead('first')],
        ['advance', 300],
        ['gtag', true],
        // Before the timer fires: the new event must not overtake the waiting one.
        ['dispatch', lead('second')],
        ['advance', 10000],
    ]);

    expect($result['calls'])->toBe([
        ['event', 'generate_lead', ['form_name' => 'first']],
        ['event', 'generate_lead', ['form_name' => 'second']],
    ])->and($result['timers'])->toBe(0);
})->with('listeners');

it('polls for gtag only while events are waiting', function (string $script) {
    $idle = runListener([$script], [['run', 0], ['advance', 10000]]);
    $waiting = runListener([$script], [['run', 0], ['dispatch', lead('first')], ['dispatch', lead('second')], ['advance', 10000]]);

    expect($idle['timers'])->toBe(0)
        ->and($waiting['timers'])->toBe(1)
        ->and($waiting['calls'])->toBe([]);
})->with('listeners');

it('keeps at most 50 waiting events and drops the oldest', function (string $script) {
    $steps = [['run', 0]];

    foreach (range(1, 60) as $number) {
        $steps[] = ['dispatch', lead('form-'.$number)];
    }

    $result = runListener([$script], [...$steps, ['gtag', true], ['advance', 1000]]);

    expect($result['calls'])->toHaveCount(50)
        ->and($result['calls'][0])->toBe(['event', 'generate_lead', ['form_name' => 'form-11']])
        ->and($result['calls'][49])->toBe(['event', 'generate_lead', ['form_name' => 'form-60']]);
})->with('listeners');

it('does not send an event that waited longer than 30 minutes', function (string $script) {
    $result = runListener([$script], [
        ['run', 0],
        ['dispatch', lead('stale')],
        ['advance', 10 * 60 * 1000],
        ['dispatch', lead('fresh')],
        ['advance', 25 * 60 * 1000],
        ['gtag', true],
        ['advance', 1000],
    ]);

    expect($result['calls'])->toBe([['event', 'generate_lead', ['form_name' => 'fresh']]])
        ->and($result['timers'])->toBe(0);
})->with('listeners');

it('stops polling when every waiting event has expired', function (string $script) {
    $result = runListener([$script], [
        ['run', 0],
        ['dispatch', lead('stale')],
        ['advance', 31 * 60 * 1000],
        ['gtag', true],
        ['advance', 5000],
    ]);

    expect($result['calls'])->toBe([])->and($result['timers'])->toBe(0);
})->with('listeners');

it('drops events without gtag again when the site turns the queue off', function (string $script) {
    $result = runListener([$script], [
        ['run', 0],
        ['dispatch', lead('dropped')],
        ['advance', 5000],
        ['gtag', true],
        ['advance', 5000],
        ['dispatch', lead('sent')],
    ], settings: ['queue' => false]);

    expect($result['calls'])->toBe([['event', 'generate_lead', ['form_name' => 'sent']]])
        ->and($result['timers'])->toBe(0)
        ->and($result['errors'])->toBe([]);
})->with('listeners');

it('listens once and keeps one queue when the script runs again on the same page', function (array $scripts) {
    // wire:navigate runs every script in the body again on each visit, while the window stays.
    $result = runListener($scripts, [
        ['run', 0],
        ['dispatch', lead('before the visit')],
        ['run', 1],
        ['dispatch', lead('after the visit')],
        ['run', 0],
        ['gtag', true],
        ['advance', 1000],
        ['dispatch', lead('with gtag')],
        ['advance', 5000],
    ]);

    expect($result['listens_to'])->toBe(['ga:event'])
        ->and($result['errors'])->toBe([])
        ->and($result['timers'])->toBe(0)
        ->and($result['calls'])->toBe([
            ['event', 'generate_lead', ['form_name' => 'before the visit']],
            ['event', 'generate_lead', ['form_name' => 'after the visit']],
            ['event', 'generate_lead', ['form_name' => 'with gtag']],
        ]);
})->with([
    'the view twice' => fn () => [viewScript(), viewScript()],
    'the file twice' => fn () => [fileScript(), fileScript()],
    'the view, then the file' => fn () => [viewScript(), fileScript()],
    'the file, then the view' => fn () => [fileScript(), viewScript()],
]);

it('never defines gtag or a dataLayer itself', function (string $script) {
    $result = runListener([$script], [['run', 0], ['dispatch', lead('waiting')], ['advance', 3000]]);

    expect($script)->not->toContain('dataLayer')
        ->and($script)->not->toMatch('/window\.gtag\s*=[^=]/')
        ->and($result['calls'])->toBe([]);
})->with('listeners');

it('writes to the console from the publishable file only', function () {
    $steps = [
        ['run', 0],
        ['dispatch', lead('waiting')],
        ['gtag', true],
        ['advance', 1000],
        ['dispatch', ['params' => ['form_name' => 'nameless']]],
    ];

    expect(runListener([viewScript()], $steps)['messages'])->toBe([])
        ->and(viewScript())->not->toContain('console.');

    expect(runListener([fileScript()], $steps)['messages'])->toBe([
        ['debug', '[GA4] Livewire Google Analytics listener initialized'],
        ['debug', '[GA4] gtag not available, event is waiting:', 'generate_lead'],
        ['debug', '[GA4] Event tracked:', 'generate_lead', ['form_name' => 'waiting']],
        ['warn', '[GA4] Event dispatched without name:', ['params' => ['form_name' => 'nameless']]],
    ]);

    expect(runListener([fileScript()], [['run', 0], ['dispatch', lead('dropped')]], ['queue' => false])['messages'][1])
        ->toBe(['debug', '[GA4] gtag not available, skipping event:', 'generate_lead']);
});

it('has the same code in the view and in the publishable file', function () {
    $core = function (string $script): string {
        preg_match('/\/\* core:start \*\/(.*)\/\* core:end \*\//s', $script, $match);

        // The file is indented one level less than the script inside the view.
        return (string) preg_replace('/^\s+/m', '', $match[1] ?? '');
    };

    expect($core(viewScript()))->not->toBe('')->toBe($core(fileScript()));
});
