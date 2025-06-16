/**
 * A vanilla JS customisable select box/text input plugin ⚡️.
 *
 * License: MIT <https://opensource.org/licenses/MIT>
 *
 * @version 11.1.0
 * @source  https://github.com/Choices-js/Choices
 */
import Choices from 'choices.js';

(function (global, factory) {
    typeof exports === 'object' && typeof module !== 'undefined' ? module.exports = factory() :
        typeof define === 'function' && define.amd ? define(factory) :
            (global = typeof globalThis !== 'undefined' ? globalThis : global || self, global.Choices = factory());
})(this, (function () {
    'use strict';

    return Choices;
}));