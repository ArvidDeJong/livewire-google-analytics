/*
 * Runs a listener script against a fake window and prints what reached gtag() as JSON.
 * Usage: node run-listener.js <script file> [<script file> ...]
 * Every file runs against the same window, the way wire:navigate runs a body script again on each visit.
 */
const fs = require('fs');
const vm = require('vm');

const listeners = [];
const calls = [];
const errors = [];

const window = {
    addEventListener(name, handler) {
        listeners.push({ name, handler });
    },
};

const silentConsole = { debug() {}, warn() {}, log() {}, error() {} };

const context = vm.createContext({ window, console: silentConsole });

process.argv.slice(2).forEach((file) => vm.runInContext(fs.readFileSync(file, 'utf8'), context));

function dispatch(detail) {
    listeners.forEach(({ handler }) => {
        try {
            handler(detail === undefined ? {} : { detail });
        } catch (error) {
            errors.push(String(error));
        }
    });
}

/* Google Analytics is blocked or not loaded yet. */
dispatch({ name: 'generate_lead', params: { form_name: 'blocked' } });

window.gtag = (...args) => calls.push(args);

dispatch({ name: 'generate_lead', params: { form_name: 'contact', value: "'); alert(1); //" } });
dispatch({ name: 'login' });
dispatch({ params: { form_name: 'nameless' } });
dispatch({ name: '', params: {} });
dispatch(undefined);

process.stdout.write(JSON.stringify({
    listens_to: listeners.map(({ name }) => name),
    calls,
    errors,
}));
