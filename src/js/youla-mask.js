(function() {
    class TextMask {
        static PLACEHOLDER="_";
        static IS_ANDROID=/Android/i.test(navigator.userAgent);
        constructor(el, mask) {
            this.el = el;
            this.resolveMask = typeof mask === "function" ? mask : () => mask;
            this.previousValue = "";
            this.previousPlaceholder = "";
            this.onInput = () => this.update(el.value);
            el.addEventListener("input", this.onInput);
            this.update(el.value);
        }
        destroy() {
            this.el.removeEventListener("input", this.onInput);
        }
        update(rawValue = this.el.value) {
            if (rawValue === this.previousValue) {
                return;
            }
            const {previousValue: previousValue, previousPlaceholder: previousPlaceholder} = this;
            const {selectionEnd: caretPosition} = this.el;
            const mask = this.resolveMask(rawValue);
            const placeholder = TextMask.buildPlaceholder(mask);
            const conformedValue = TextMask.conform(rawValue, mask, placeholder, previousValue, caretPosition);
            const adjustedCaretPosition = TextMask.adjustCaretPosition({
                previousValue: previousValue,
                previousPlaceholder: previousPlaceholder,
                conformedValue: conformedValue,
                placeholder: placeholder,
                rawValue: rawValue,
                caretPosition: caretPosition
            });
            this.previousValue = conformedValue;
            this.previousPlaceholder = placeholder;
            if (this.el.value === conformedValue) {
                return;
            }
            this.el.value = conformedValue;
            TextMask.setCaretPosition(this.el, adjustedCaretPosition);
        }
        static countFixedChars(placeholderStr, length) {
            let count = 0;
            for (let i = 0; i < length; i++) {
                if (placeholderStr[i] !== TextMask.PLACEHOLDER) {
                    count++;
                }
            }
            return count;
        }
        static buildPlaceholder(mask) {
            if (mask.includes(TextMask.PLACEHOLDER)) {
                throw new Error(`Youla.js: a u-mask pattern can't contain a literal "${TextMask.PLACEHOLDER}" — it's the placeholder character used internally.`);
            }
            return mask.map(token => token instanceof RegExp ? TextMask.PLACEHOLDER : token).join("");
        }
        static conform(rawValue, mask, placeholder, previousValue, caretPosition) {
            const {PLACEHOLDER: PLACEHOLDER} = TextMask;
            const editLength = rawValue.length - previousValue.length;
            const isAddition = editLength > 0;
            const indexOfFirstChange = caretPosition + (isAddition ? -editLength : 0);
            const rawChars = rawValue.split("");
            for (let i = rawValue.length - 1; i >= 0; i--) {
                const shouldOffset = i >= indexOfFirstChange && previousValue.length === mask.length;
                if (rawChars[i] === placeholder[shouldOffset ? i - editLength : i]) {
                    rawChars.splice(i, 1);
                }
            }
            let conformedValue = "";
            placeholderLoop: for (let i = 0; i < placeholder.length; i++) {
                if (placeholder[i] !== PLACEHOLDER) {
                    conformedValue += placeholder[i];
                    continue;
                }
                while (rawChars.length > 0) {
                    const rawChar = rawChars.shift();
                    if (mask[i].test(rawChar)) {
                        conformedValue += rawChar;
                        continue placeholderLoop;
                    }
                }
                break;
            }
            if (!isAddition) {
                let lastFilledSlot = -1;
                for (let i = 0; i < conformedValue.length; i++) {
                    if (placeholder[i] === PLACEHOLDER) {
                        lastFilledSlot = i;
                    }
                }
                conformedValue = conformedValue.slice(0, lastFilledSlot + 1);
            }
            return conformedValue;
        }
        static adjustCaretPosition({previousValue: previousValue = "", previousPlaceholder: previousPlaceholder = "", caretPosition: caretPosition = 0, conformedValue: conformedValue, rawValue: rawValue, placeholder: placeholder}) {
            const {PLACEHOLDER: PLACEHOLDER} = TextMask;
            if (caretPosition === 0 || !rawValue.length) {
                return 0;
            }
            const editLength = rawValue.length - previousValue.length;
            const isAddition = editLength > 0;
            const isFirstRawValue = previousValue.length === 0;
            const isPartialMultiCharEdit = editLength > 1 && !isAddition && !isFirstRawValue;
            if (isPartialMultiCharEdit) {
                return caretPosition;
            }
            const possiblyHasRejectedChar = isAddition && previousValue === conformedValue;
            let startingSearchIndex = 0;
            let trackRightCharacter;
            let targetChar;
            if (possiblyHasRejectedChar) {
                startingSearchIndex = caretPosition - editLength;
            } else {
                const normalizedConformedValue = conformedValue.toLowerCase();
                const normalizedRawValue = rawValue.toLowerCase();
                const leftHalfChars = normalizedRawValue.slice(0, caretPosition).split("");
                const intersection = leftHalfChars.filter(char => normalizedConformedValue.includes(char));
                targetChar = intersection[intersection.length - 1];
                const previousLeftMaskChars = TextMask.countFixedChars(previousPlaceholder, intersection.length);
                const leftMaskChars = TextMask.countFixedChars(placeholder, intersection.length);
                const maskLengthChanged = leftMaskChars !== previousLeftMaskChars;
                const targetIsMaskMovingLeft = previousPlaceholder[intersection.length - 1] !== undefined && placeholder[intersection.length - 2] !== undefined && previousPlaceholder[intersection.length - 1] !== PLACEHOLDER && previousPlaceholder[intersection.length - 1] !== placeholder[intersection.length - 1] && previousPlaceholder[intersection.length - 1] === placeholder[intersection.length - 2];
                if (!isAddition && (maskLengthChanged || targetIsMaskMovingLeft) && previousLeftMaskChars > 0 && placeholder.includes(targetChar) && rawValue[caretPosition] !== undefined) {
                    trackRightCharacter = true;
                    targetChar = rawValue[caretPosition];
                }
                const countTargetCharInIntersection = intersection.filter(char => char === targetChar).length;
                const countTargetCharInPlaceholder = placeholder.slice(0, placeholder.indexOf(PLACEHOLDER)).split("").filter((char, index) => char === targetChar && rawValue[index] !== char).length;
                const requiredNumberOfMatches = countTargetCharInPlaceholder + countTargetCharInIntersection + (trackRightCharacter ? 1 : 0);
                let numberOfEncounteredMatches = 0;
                for (let i = 0; i < conformedValue.length; i++) {
                    startingSearchIndex = i + 1;
                    if (normalizedConformedValue[i] === targetChar) {
                        numberOfEncounteredMatches++;
                    }
                    if (numberOfEncounteredMatches >= requiredNumberOfMatches) {
                        break;
                    }
                }
            }
            if (isAddition) {
                let lastPlaceholderChar = startingSearchIndex;
                for (let i = startingSearchIndex; i <= placeholder.length; i++) {
                    if (placeholder[i] === PLACEHOLDER) {
                        lastPlaceholderChar = i;
                    }
                    if (placeholder[i] === PLACEHOLDER || i === placeholder.length) {
                        return lastPlaceholderChar;
                    }
                }
            } else if (trackRightCharacter) {
                for (let i = startingSearchIndex - 1; i >= 0; i--) {
                    if (conformedValue[i] === targetChar || i === 0) {
                        return i;
                    }
                }
            } else {
                for (let i = startingSearchIndex; i >= 0; i--) {
                    if (placeholder[i - 1] === PLACEHOLDER || i === 0) {
                        return i;
                    }
                }
            }
        }
        static setCaretPosition(el, position) {
            if (document.activeElement !== el) {
                return;
            }
            const setSelection = () => el.setSelectionRange(position, position, "none");
            TextMask.IS_ANDROID ? requestAnimationFrame(setSelection) : setSelection();
        }
    }
    document.addEventListener("youla:init", () => {
        function sanitizeDomain(value) {
            return value.replace(/[^a-zA-Z0-9.-]/g, "").replace(/^[.-]+/, "").replace(/\.[.-]+/g, ".").replace(/-+\./g, ".").replace(/\.{2,}/g, ".");
        }
        const TYPE_FILTERS = {
            tel: /[^ \-()+\d]/g,
            number: /[^.-\d]/g,
            color: /[^ a-zA-Z(),\d]/g,
            url: sanitizeDomain
        };
        function buildMaskTokens(pattern, rawValue) {
            function limit(position, symbol, max) {
                let pos = position;
                max = max.toString();
                if (pattern.charAt(--pos) === symbol) {
                    if (rawValue.charAt(pos) === max.charAt(0)) {
                        return new RegExp("[0-" + max.charAt(1) + "]");
                    }
                    return /\d/;
                }
                return new RegExp("[0-" + max.charAt(0) + "]");
            }
            let position = -1;
            return pattern.match(/(\{[^}]+?\})|(.)/g).map(symbol => {
                ++position;
                switch (symbol) {
                  case "i":
                    return limit(position, symbol, 59);

                  case "H":
                    return limit(position, symbol, 23);

                  case "D":
                    return limit(position, symbol, 31);

                  case "M":
                    return limit(position, symbol, 12);

                  case "Y":
                  case "0":
                    return /\d/;

                  default:
                    if (/\{[^}]+?\}/.test(symbol)) {
                        return new RegExp(symbol.slice(1, -1));
                    }
                    return symbol;
                }
            });
        }
        function applyMask(el, mode, output) {
            el._x_mask?.destroy();
            if (mode === "pattern") {
                const textMask = new TextMask(el, rawValue => buildMaskTokens(output, rawValue));
                el._x_mask = {
                    mode: mode,
                    output: output,
                    destroy: () => textMask.destroy()
                };
                return;
            }
            const filter = mode === "regexp" ? output : TYPE_FILTERS[el.getAttribute("type")];
            if (!filter) {
                el._x_mask = {
                    mode: mode,
                    output: output,
                    destroy() {}
                };
                return;
            }
            const sanitize = typeof filter === "function" ? filter : value => value.replace(filter, "");
            const onInput = () => {
                const filtered = sanitize(el.value);
                if (filtered !== el.value) {
                    el.value = filtered;
                }
            };
            el.addEventListener("input", onInput);
            el._x_mask = {
                mode: mode,
                output: output,
                destroy: () => el.removeEventListener("input", onInput)
            };
        }
        Youla.directive("mask", (el, output) => {
            if (!(el instanceof HTMLInputElement)) {
                console.warn('Youla.js: "u-mask" requires an <input>.');
                return;
            }
            const mode = output instanceof RegExp ? "regexp" : typeof output === "string" && output ? "pattern" : "auto";
            if (el._x_mask && el._x_mask.mode === mode && el._x_mask.output === output) {
                return;
            }
            applyMask(el, mode, output);
        });
    });
})();