<?php

use Illuminate\Support\Facades\Blade;

it('renders the Boost guideline as text, without running the Blade it talks about', function () {
    $source = (string) file_get_contents(dirname(__DIR__, 2).'/resources/boost/guidelines/core.blade.php');

    // Boost renders the guideline with Blade. An @include outside @verbatim that is not escaped
    // would put the listener script in the guideline instead of the instruction to include it.
    $rendered = Blade::render($source);

    expect($rendered)
        ->toContain("`@include('livewire-google-analytics::script')`")
        ->toContain('<code-snippet name="Track a lead after a Livewire form was handled" lang="php">')
        ->not->toContain('<script>')
        ->not->toContain('@verbatim');

    $outsideVerbatim = (string) preg_replace('/@verbatim.*?@endverbatim/s', '', $source);

    expect($outsideVerbatim)->not->toContain('{{')->not->toContain('{!!');
});

it('ships a Boost skill that names the package', function () {
    $skill = (string) file_get_contents(
        dirname(__DIR__, 2).'/resources/boost/skills/livewire-google-analytics-development/SKILL.md'
    );

    expect($skill)->toStartWith("---\nname: livewire-google-analytics-development\n");
});
