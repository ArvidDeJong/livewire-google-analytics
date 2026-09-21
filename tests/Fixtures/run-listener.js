/*
 * Runs listener scripts against a fake window with a fake clock and prints what happened as JSON.
 * Usage: node run-listener.js <scenario.json>
 *
 * The scenario: { "scripts": [<file>, ...], "settings": <object or null>, "steps": [[<step>, <argument>], ...] }
 *
 *   ["run", 0]          run scripts[0]; running one again is what wire:navigate does with a body script
 *   ["gtag", true]      Google's tag appears (false removes it again)
 *   ["dispatch", {...}] fire the ga:event browser event with this detail (null: an event without detail)
 *   ["advance", 5000]   move the clock forward and run the timers that are due
 *   ["settings", {...}] set window.livewireGoogleAnalytics
 */
const fs = require('fs');
const vm = require('vm');

const scenario = JSON.parse(fs.readFileSync(process.argv[2], 'utf8'));

let clock = 1700000000000;
let nextTimerId = 1;
const timers = new Map();
const listeners = [];
const calls = [];
const errors = [];
const messages = [];

const window = {
    addEventListener(name, handler) {
        listeners.push({ name, handler });
    },
};

if (scenario.settings) {
    window.livewireGoogleAnalytics = scenario.settings;
}

const record = (level) => (...args) => messages.push([level, ...args]);

const context = vm.createContext({
    window,
    console: { debug: record('debug'), warn: record('warn'), log: record('log'), error: record('error') },
    Date: { now: () => clock },
    setInterval(callback, every) {
        timers.set(nextTimerId, { callback, every, due: clock + every });

        return nextTimerId++;
    },
    clearInterval(id) {
        timers.delete(id);
    },
    setTimeout() {
        throw new Error('The listener is expected to use setInterval.');
    },
});

function guarded(callback) {
    try {
        callback();
    } catch (error) {
        errors.push(String(error));
    }
}

function advance(milliseconds) {
    const end = clock + milliseconds;

    for (;;) {
        const due = [...timers.entries()].filter(([, timer]) => timer.due <= end).sort((a, b) => a[1].due - b[1].due)[0];

        if (!due) {
            break;
        }

        const [id, timer] = due;
        clock = timer.due;
        timer.due += timer.every;
        guarded(() => timers.has(id) && timer.callback());
    }

    clock = end;
}

const steps = {
    run: (index) => guarded(() => vm.runInContext(fs.readFileSync(scenario.scripts[index], 'utf8'), context)),
    gtag: (present) => {
        if (present) {
            window.gtag = (...args) => calls.push(args);
        } else {
            delete window.gtag;
        }
    },
    dispatch: (detail) => listeners.forEach(({ handler }) => guarded(() => handler(detail === null ? {} : { detail }))),
    advance,
    settings: (settings) => {
        window.livewireGoogleAnalytics = settings;
    },
};

scenario.steps.forEach(([step, argument]) => steps[step](argument));

process.stdout.write(JSON.stringify({
    listens_to: listeners.map(({ name }) => name),
    calls,
    errors,
    messages,
    timers: timers.size,
}));
