# youla-expansa.js (removed 2026-09-18)

`src/youla-expansa.js` and everything that documented or demoed it were removed from this repo
because the extension moved to a separate project (Expansa CMS). This file preserves the usage
instructions and the full source as it existed right before removal, in case anything here needs
to be reintroduced or ported.

Removed alongside it:

- `src/view/steps.html` — the Expansa CMS installation wizard, built entirely on this extension
  (`u-step`, `step`, `$ajax`, Expansa's own `expansa.css`/`controls.css`/`utility.css`).
- Docs example pages: `dialog`, `notice`, `password`, `avatar`, `table`, `builder`, `selfie`,
  `step`, `progress`, `custom-directives`.
- The site-wide `u-data="notice"` / `u-data="dialog"` widgets that lived in the shared
  `src/view/parts/footer.html` (present on every docs page) and their CSS in `src/styles.scss`.
- Tests: `step-action`, `step-isSteps`, `step-required`, `step-scoped-refresh` (`mount-single-pass`
  was kept — it was actually testing core mount behavior and got rewritten to not depend on this
  extension).

Registration pattern for all of it: everything is wired up on `document.addEventListener('youla:init', ...)`
via the public `Youla.directive()` / `Youla.data()` / `Youla.variable()` / `Youla.method()` API — core
has no reference to any of this.

## step (`u-step` directive + `Youla.data('step')`)

Turns a form into a multi-step wizard: each step shows in turn, and moving to the next one only
happens once the current step is complete.

- `u-step="condition"` goes on each step panel and decides when that panel counts as complete
  (usually a check on the entered data). Fields like `name`/`email` don't need to be declared
  separately — `u-prop` adds them to the same data on first render.
- `u-step.required` skips writing a condition by hand: the step is complete only once every
  `required` field inside the panel (`input`/`select`/`textarea`) passes its own native
  `checkValidity()` — so `type="email"`, `pattern`, `minlength`, etc. all count, not just
  "non-empty". For a radio group, put `required` on just one of them — the browser validates the
  whole group. `u-step.required` alone (no expression) means the required-fields check is the
  only condition; `u-step.required="condition"` combines both, and both must hold.
- `u-step:action="expr"` (read straight off the DOM, no registered directive) runs once each time
  that panel becomes the current one — e.g. to fire a request when the wizard reaches a step.
- Mount the wizard's own data with `u-data="step"` — then every method below is available with no
  prefix anywhere inside that component (`@click`, `u-show`, `:disabled`, text interpolation).

### `step` provider methods

| Method | What it does |
| --- | --- |
| `goNext()` / `goBack()` | Moves one step forward/back, if such a step exists |
| `goto(index)` | Jumps straight to step `index` (1-based), without checking intermediate steps |
| `canGoNext()` / `canGoBack()` | `true` if the move is possible; `canGoNext()` also implies the current step is complete |
| `isFirst()` / `isLast()` | `true` on the first/last step, regardless of completion |
| `isStep(n)` | `true` if step `n` is the current one |
| `isSteps(...values)` | `true` if the current step matches any of the given values — each is either an exact index or an inclusive `[from, to]` range, e.g. `isSteps(1, [3, 5])` |
| `isCompleted()` | `true` once the user reached the last step and it's complete — the whole wizard is done |
| `progress()` | `{ total, complete, current, incomplete, progress, completion, percentage }` |
| `getState()` | All of the above bundled into one object — handy for debugging |

Every `can*`/`is*` method has a negated counterpart for readability in markup:
`cannotGoNext()`, `cannotGoBack()`, `isNotFirst()`, `isNotLast()`, `isUncompleted()`.

### Example

```html
<div u-data="step">
  <div class="progress-bar">
    <i :style="{ width: progress().completion }"></i>
  </div>

  <ul class="dots">
    <li u-each="n in 3" :class="{ active: isStep(n) }" u-text="n"></li>
  </ul>

  <div u-step.required>
    <label>Your name</label>
    <input required u-prop="name" placeholder="John">
  </div>

  <div u-step.required hidden>
    <label>Email</label>
    <input required type="email" u-prop="email" placeholder="john@example.com">
  </div>

  <div u-step="true" hidden>
    Done! <span u-text="name"></span>, we'll send a confirmation to <span u-text="email"></span>.
  </div>

  <p>
    <button type="button" :disabled="cannotGoBack()" @click="goBack()">Back</button>
    <button type="button" :disabled="cannotGoNext()" @click="goNext()" u-show="isNotLast()">Next</button>
  </p>

  <pre u-text="JSON.stringify(getState(), null, 2)"></pre>
</div>
```

## notice (`$notice`)

A ready-made toast notification queue: five types (`info`, `success`, `warning`, `error`,
`loading`), a ring countdown indicator on each card, closes by timer or by click. Hovering a
notice pauses its auto-close timer.

```html
<button type="button" @click="$notice.info('Form saved')">Info</button>
<button type="button" @click="$notice.success('Changes applied')">Success</button>
<button type="button" @click="$notice.warning('Check the highlighted fields')">Warning</button>
<button type="button" @click="$notice.error('Could not save')">Error</button>
<button type="button" @click="$notice.loading('Loading data…')">Loading</button>
```

The queue needs a container mounted once, anywhere on the page (this repo had it in
`parts/footer.html`, so it applied site-wide):

```html
<div class="notice-root" u-data="notice" @mouseenter="pause()" @mouseleave="resume()">
  <div u-each="item in items" class="notice__item" :class="item.classes()">
    <svg class="notice__spinner" viewBox="0 0 24 24" width="24" height="24">
      <circle cx="12" cy="12" r="11"
        @load="$el.style.animationDuration = item.duration + 'ms'; $el.style.animationDelay = '-' + elapsed(item) + 'ms'">
      </circle>
    </svg>
    <span class="notice__message" u-text="item.message"></span>
    <button type="button" class="notice__close" u-show="item.closable" @click="close(item.id)">×</button>
  </div>
</div>
```

No other setup is needed — call a method wherever the triggering event happens in your code:
`@click`, an `$ajax` response handler, or any other component method.

### `notice` reference

| Name | What it does |
| --- | --- |
| `$notice.info/success/warning/error/loading(message, duration?)` | Show a notice. Optional third argument overrides lifetime: a number of ms, `'auto'` (computed from message length), or `0` (never auto-closes, only by "×" or `close(item.id)`) |
| `$notice.add(message, type, duration)` | Same as above but with the type passed as a string — useful when the type is dynamic |
| `$notice.duration = 5000` | Default auto-close delay in ms (7000 by default) |
| `$notice.close(item.id)` | Closes one notice early |
| `$notice.items` | The current queue — for rendering a counter elsewhere |
| `item.classes()` | Ready class string for a card (`notice__item--info`, `--success`, etc.) |

**Must keep when customizing markup**: `u-each="item in items"`, `close(item.id)` on the close
button, and `@mouseenter="pause()"` / `@mouseleave="resume()"` on the container — without them
notices stop closing (by button and by timer) and hover-pause stops working.

## dialog (`$dialog`)

For stackable modal dialogs — dialogs can open other dialogs, so short "action → confirm" chains
work, not just one window at a time.

```html
<div u-data>
  <button type="button" @click="$dialog.open('dialog-window-content')">Open dialog</button>

  <template id="dialog-window-content">
    <h3>Window title</h3>
    <p>Close it by clicking the backdrop, pressing Escape, or the button below.</p>
    <button type="button" @click="$dialog.close()">Close</button>
  </template>
</div>
```

The shell lives once, site-wide, in `parts/footer.html`:

```html
<div class="dialog" u-data="dialog" @keydown.esc.window="close()">
  <div u-each="entry in stack" class="dialog-backdrop" @click="close(entry.id)">
    <div class="dialog-window" role="dialog" aria-modal="true" @click.stop="null" u-html="entry.content"></div>
  </div>
</div>
```

`@click.stop` on `.dialog-window` stops a click inside the dialog from bubbling to the backdrop
and closing it by accident. Because each dialog covers the full screen, only the topmost one's
backdrop is ever actually clickable.

### `$dialog` reference

| Method | When to call it |
| --- | --- |
| `$dialog.open(templateID, data?)` | Opens a window on top of anything already open: copies `<template id="templateID">` into a new stack entry |
| `$dialog.close()` | Closes the top dialog of the stack — same call already wired to the Close button, Escape, and backdrop click |
| `$dialog.clear()` | Closes the whole stack at once (e.g. on navigation or logout) |
| `$dialog.init(templateID, callback)` | Re-opens the outer dialog if it was left open in a reloaded/shared `?dialog=` URL |

The second argument to `open()` is merged straight into the stack entry and is readable inside the
`<template>` as `entry` (the same loop variable `u-each="entry in stack"` provides):
`open('confirm-delete', { order })` reads as `entry.order`.

```html
<button type="button" @click="$dialog.open('confirm-delete', { order: 'Order #42' })">
  Delete order #42
</button>

<template id="confirm-delete">
  <h3>Delete <span u-text="entry.order"></span>?</h3>
  <p>This can't be undone.</p>
  <button type="button" @click="$dialog.close()">Cancel</button>
  <button type="button" @click="$notice.success(entry.order + ' deleted'); $dialog.close()">Delete</button>
</template>
```

Every open/close also dispatches a bubbling `open`/`close` DOM event, regardless of which
`templateID` or button triggered it — catch it anywhere with the `.document` modifier
(`@open.document="..."`, `@close.document="..."`) without touching `$dialog` itself.

**Must keep when customizing markup**: `u-data="dialog"`, `u-each="entry in stack"`,
`u-html="entry.content"` (actually renders the template's content), `@click="close(entry.id)"` on
the backdrop, `@click.stop` on the window, and `@keydown.esc.window="close()"` on the root element
(drop it only if you want Escape to do nothing).

## password

A drop-in password field component: checks the password against a policy (lowercase, UPPERCASE,
digits, special characters, minimum length), shows a strength meter, can generate a password that
already satisfies the policy, and toggles show/hide. No custom JS needed beyond mounting it.

```html
<div u-data="password">
  <input :type="visible ? 'text' : 'password'" u-prop="value" @input="check(value)"
    placeholder="Choose a password">
  <button type="button" @click="toggle()" u-text="visible ? 'Hide' : 'Show'"></button>
  <button type="button" @click="generate()">Generate password</button>

  <div class="password-meter">
    <div class="password-meter__bar" :class="`level-${level()}`" :style="{ width: `${progress}%` }"></div>
  </div>
  <div u-text="value && label()"></div>

  <ul class="password-rules">
    <li :class="valid.lowercase && 'is-done'">2+ lowercase letters</li>
    <li :class="valid.uppercase && 'is-done'">2+ UPPERCASE letters</li>
    <li :class="valid.digit && 'is-done'">2+ digits</li>
    <li :class="valid.special && 'is-done'">2+ special characters</li>
    <li :class="valid.length && 'is-done'">At least 12 characters</li>
  </ul>
</div>
```

Markup and styling are free to change; the only things that must stay are `u-prop="value"` and
`@input="check(value)"` on the field, and `toggle()`/`generate()` wired to their buttons.
`generate()` already fills the field and updates the meter — no need to also call `check()`.

The policy itself (`min` counts per character class, minimum length, and which characters count
as which class) lives in `min`/`charsets` in the component source — edit those to change the
requirements.

### `password` reference

| Name | When to use it |
| --- | --- |
| `value` | Current password |
| `visible` | `true` while shown as plain text |
| `toggle()` | Flips `visible` |
| `generate()` | Fills the field with a password that already passes every rule |
| `check(value)` | Recomputes the meter/rules for a typed value — call on `@input` |
| `valid` | Which rules currently pass: `lowercase`, `uppercase`, `special`, `digit`, `length` |
| `progress` | Overall completion percent (0–100), grows gradually per matched character, not just per satisfied rule |
| `level()` / `label()` | A 0–4 strength tier and its label ("Слишком слабый" … "Отличный") |
| `min`, `charsets` | The policy itself — edit only in the component source, shared by every field on the page |

## avatar

An avatar uploader: preview right after picking a file, before upload, with initials as a
placeholder while nothing is picked.

```html
<div u-data="avatar">
  <div class="avatar-circle" u-bind="picture">
    <span u-bind="initials" hidden></span>
  </div>

  <input type="text" placeholder="Enter name" u-bind="field">
  <input type="file" accept="image/*" u-bind="uploader">
  <button type="button" u-bind="remover" hidden>Remove</button>
</div>
```

```js
Youla.data('avatar', () => ({
  name: '',
  image: '',
  field: {
    'u-prop': 'name',
  },
  picture: {
    ':title': 'name',
    ':style': "image && `background-image:url(${image})`",
  },
  initials: {
    'u-show': '!image',
    'u-text'() {
      return this.name.trim().split(/\s+/).map(word => word[0]).slice(0, 2).join('').toUpperCase();
    },
  },
  uploader: {
    '@change'() {
      let file = this.$event.target.files[0];
      if (file) {
        let reader = new FileReader();
        reader.onload = e => this.image = e.target.result;
        reader.readAsDataURL(file);
      }
    },
  },
  remover: {
    'u-show': 'image',
    '@click'() {
      let input = this.$root.querySelector('input[type="file"]');
      if (input) {
        input.value = '';
      }
      this.image = '';
    },
  },
}));
```

`image` holds the picked file as a `data:image/*` URI — enough for a preview, but actually
uploading it to a server is left to the caller.

| Set | Goes on | What it does |
| --- | --- | --- |
| `field` | the name text field | `u-prop` two-way binds it to `name` |
| `picture` | the preview container | `:style` fills it with `image` as a background once it's non-empty |
| `initials` | the placeholder inside the preview | `u-show` while there's no file; `u-text` gives the first two letters of `name` |
| `uploader` | `input[type="file"]` | `@change` reads the picked file into `image` |
| `remover` | the "Remove" button | `u-show` once `image` is set; `@click` clears both `image` and the file input |

## table (row-selection checkboxes)

Bulk actions on a list — delete, export, change status for several rows — need a fast way to
select rows: a header checkbox toggles every row, and Shift-click selects a range between two
clicks, like a mail client or admin panel.

```html
<table u-data="table">
  <thead>
    <tr>
      <th><input type="checkbox" u-bind="trigger"></th>
      <th>Name</th>
      <th>Email</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td><input type="checkbox" u-bind="item"></td>
      <td>John Smith</td>
      <td>john@example.com</td>
    </tr>
    <!-- more rows -->
  </tbody>
</table>
```

```js
Youla.data('table', () => ({
  anchor: null,
  trigger: {
    '@change': 'selectAll($el, $root)',
  },
  item: {
    '@click': 'selectItem($el, $root, $event)',
  },
  items(root) {
    return [...root.querySelectorAll('[u-bind~="item"]')];
  },
  selectAll(el, root) {
    this.items(root).forEach(input => input.checked = el.checked);
  },
  selectItem(el, root, event) {
    let items   = this.items(root);
    let index   = items.indexOf(el);
    let checked = el.checked;
    let start   = event.shiftKey && this.anchor !== null ? this.anchor : index;

    for (let i = Math.min(start, index); i <= Math.max(start, index); i++) {
      items[i].checked = checked;
    }
    this.anchor = index;
  },
}));
```

| Set | Goes on | What it does |
| --- | --- | --- |
| `trigger` | the "select all" checkbox (e.g. in the header) | toggles every `item` checkbox at once |
| `item` | each selectable row's checkbox | a plain click toggles just it; Shift-click selects the range from the previous click to this one |

Doesn't have to be a `<table>` — any list of checkboxes with `u-bind="trigger"`/`u-bind="item"`
works.

## builder (visual condition builder)

A conditional-logic builder: rule groups joined by **OR**, rules within a group joined by
**AND** — the same scheme as ACF's or Gravity Forms' conditional logic: "show this field if
(condition 1 AND condition 2) OR (condition 3)".

Each rule is `{ field, operator, value }`. `submit()` just hands back the resulting JSON — send it
wherever, e.g. via `$ajax`.

```html
<div u-data="builder">
  <div class="builder-toolbar">
    <button type="button" @click="addGroup()">+ Group (OR)</button>
    <button type="button" @click="submit()">Save</button>
  </div>

  <div u-each="(group, key) in groups" class="builder-group">
    <div class="builder-group__header">
      <span u-text="key === 0 ? 'If' : 'Or, if'"></span>
      <button type="button" @click="addRule(key)">+ Rule (AND)</button>
      <button type="button" @click="removeGroup(key)">Remove group</button>
    </div>

    <div u-each="(rule, index) in group.rules" class="builder-rule">
      <select :value="rule.field" @change="rule.field = $el.value">
        <option value="post">Post type</option>
        <option value="post_status">Post status</option>
        <option value="user_role">User role</option>
        <option value="page_template">Page template</option>
      </select>
      <select :value="rule.operator" @change="rule.operator = $el.value">
        <option value="===">equals</option>
        <option value="!=">not equal</option>
        <option value="contains">contains</option>
      </select>
      <input type="text" :value="rule.value" @input="rule.value = $el.value" placeholder="Value">
      <button type="button" @click="removeRule(key, index)">×</button>
    </div>
  </div>
</div>
```

Important when porting the markup: don't put the "+ Group"/"Save" buttons (or "+ Rule"/"Remove
group") *after* the `u-each` list they act on — they must come before it in the DOM, or they
disappear on first render. (The original demo displayed them at the bottom purely via CSS
`order`, not DOM position.)

`submit()` result shape:

```json
[
  { "rules": [
    { "field": "post_status", "operator": "!=", "value": "contributor" },
    { "field": "user_role", "operator": "===", "value": "editor" }
  ] },
  { "rules": [
    { "field": "page_template", "operator": "contains", "value": "landing" }
  ] }
]
```

Applying the conditions is left to the consuming project:

```js
function matches(groups, context) {
  return groups.some(group =>
    group.rules.every(rule => {
      const actual = context[rule.field];

      switch (rule.operator) {
        case '===':      return actual === rule.value;
        case '!=':       return actual !== rule.value;
        case 'contains': return String(actual).includes(rule.value);
      }
    })
  );
}
```

### `builder` reference

| Name | When to use it |
| --- | --- |
| `groups` | Array of rule groups (OR between groups); each has its own `rules` (AND within the group) |
| `default` | Template for a new rule — `{ field, operator, value }` — copied when adding a group/rule |
| `addGroup()` | Adds a new group with one default rule |
| `removeGroup(index)` | Removes a group by index |
| `addRule(key)` | Adds a default rule to group `key` |
| `removeRule(key, index)` | Removes rule `index` from group `key` |
| `submit()` | Hands back the resulting structure — in the demo it just logs to console |

## stream (`u-data="stream"` — camera selfie)

Wraps `getUserMedia` into a ready "camera preview → snapshot to `<canvas>` → result in `<img>`"
flow. The markup only needs two elements bound with `u-bind="videoRef"` and `u-bind="imageRef"` —
those sets apply `u-ref` from the component's own data; camera handling and cropping stay inside
the component. `<canvas>` is optional — without `u-bind="canvasRef"` the component creates an
off-DOM one itself.

```html
<div u-data="stream">
  <video u-bind="videoRef" autoplay playsinline muted></video>
  <img u-bind="imageRef" alt="Selfie">

  <p u-show="error">
    <span u-show="error === 'denied'">Camera access denied.</span>
    <span u-show="error === 'unavailable'">Could not access the camera.</span>
    <span u-show="error === 'unsupported'">This browser doesn't support the camera.</span>
  </p>

  <button type="button" @click="snap()">Take snapshot</button>
  <button type="button" @click="stop()">Stop camera</button>
</div>
```

Requesting camera access only works on `localhost` or over HTTPS. The first click asks for
permission; later clicks reuse the already-granted stream.

### `stream` reference

| Name | What it does |
| --- | --- |
| `videoRef`, `imageRef`, `canvasRef` | Ready `u-bind` sets, each applies the matching `u-ref` (`video`/`image`/`canvas`) |
| `error` | `null`, or `'denied'` / `'unavailable'` / `'unsupported'` — set when `start()` runs |
| `snap()` | Checks for `video`/`image`, starts the camera if needed, draws the current frame into a canvas sized to `image`. Returns a `data:image/png` and also sets `image.src` |
| `start()` | Requests `getUserMedia` and attaches the stream to `video`. Waits for visibility via `IntersectionObserver` if the video is hidden; a no-op if already streaming or already waiting |
| `stop()` | Stops every track and cancels any pending visibility wait |
| `check()` | `true` if both `video` and `image` are found in the markup; otherwise logs why and returns `false` |
| `getCanvas()` | The `u-bind="canvasRef"` element if present, else a lazily-created, reused off-screen `<canvas>` |
| `isVisible(el)` | `true` unless the element is hidden via `display`, `visibility`, or zero `opacity` |

Each `video` gets its own stream — several `u-data="stream"` instances on one page don't interfere
with each other.

## Directives documented on the "Пользовательские директивы" (custom directives) page

These five directives lived in `youla-expansa.js` and were used on that page purely as worked
examples of what a custom directive can look like:

- **`u-noautofill`** — makes a field `readonly` until first focus so the browser doesn't get a
  chance to offer autofill, then lifts `readonly` on focus. Value is unused; pair with `u-prop`
  as normal.
- **`u-highlight[.lang]`** — doesn't highlight anything itself; it prepares markup for a
  highlighter like Prism.js by wrapping the element's children in `<code class="language-lang">`,
  adding `line-numbers`, and setting `data-lang`. The language comes from the modifier
  (`u-highlight.js` → `language-js`); no modifier defaults to `language-html`.
- **`u-collapse="expr"`** — animates height/padding/margin between 0 and the element's natural
  size, expanding on truthy, collapsing on falsy. No CSS class needed.
- **`u-textarea="maxRows"`** — grows a `<textarea>` to fit content as the user types, up to
  `maxRows` lines (default 99), then stops growing and scrolls internally. Minimum height is the
  plain `rows` attribute, untouched by the directive.
- **`u-sticky`** — keeps an element pinned within its `position: relative` parent's bounds while
  scrolling past it, instead of sticking to the viewport; a sidebar taller than the viewport
  scrolls internally rather than running off-screen.

## `u-progress` (progress-bar animation directive)

Animates a progress bar into view the first time the element enters the viewport, and sets two
CSS custom properties: `--youla-progress` (current percent) and `--youla-progress-transition`
(the CSS transition for it). The directive draws nothing itself — wire those variables into
`width`, color, or anything else via your own CSS, e.g. `width: var(--youla-progress, 0%)`.

| Modifier | Meaning | Default |
| --- | --- | --- |
| 1st (e.g. `20` in `.20.90`) | `from` — starting percent | `0` |
| 2nd (e.g. `90`; optional if the value is bound) | `to` — ending percent | `100` |
| `.800ms`, `.0ms`, etc. | Transition duration (same "number + unit" modifier as `u-tooltip`'s delay) | `0ms` |

`from`/`to` are parsed positionally (`from.to`), not by keyword, so order matters; the duration
modifier is found separately regardless of position.

```html
<!-- static range -->
<div class="progress-bar" u-progress.20.90.800ms><i></i></div>

<!-- reactive: "to" comes from bound data instead of a modifier -->
<div u-data="{ percent: 20 }">
  <div class="progress-bar" u-progress.0.600ms="percent"><i></i></div>
  <button type="button" @click="percent = 55">55%</button>
</div>

<!-- defaults: from=0, to=100, duration=0ms — fills instantly -->
<div class="progress-bar" u-progress><i></i></div>
```

Once revealed, a bound value stays reactive — every change smoothly re-animates
`--youla-progress` to the new value without waiting to re-enter the viewport. A non-numeric bound
value quietly falls back to the `to` modifier (or its default) instead of breaking the animation.
Each element gets its own `IntersectionObserver`, so multiple bars on a page animate
independently. Respects `prefers-reduced-motion`: skips the transition and jumps straight to the
final value.

## Undocumented extras (source only, no dedicated docs page existed)

- **`search`** (`Youla.data('search')`) — ready `u-bind` sets (`wrapper`, `button`, `input`) for a
  Ctrl+K search box: outside-click/Escape closes it, arrow keys move through `links`, Enter
  navigates to the highlighted one.
- **`tab`** (`Youla.data('tab')`) — URL-synced tabs: `tabButton(id)`/`tabContent(id)` return
  `u-bind` sets; active tab is read from `?tab=` or `data-tab` on the root, and clicking a tab
  button updates both the data and the URL via `pushState`.
- **`$dirty`** (`Youla.variable('dirty')`) — warns about unsaved form changes. Call
  `$dirty.watch($el)` once per form (e.g. `<form @load="$dirty.watch($el)">`) and
  `$dirty.remove($el)` after a successful save; while dirty, clicking any `<a href>` on the page
  shakes the page instead of navigating.
- **`$copy`** (`Youla.method('copy')`) — `$copy(subject, classes?)` writes `subject` to the
  clipboard and toggles `classes` (default `['ph-copy', 'ph-check']`) on the triggering element
  for one second as feedback.
- **`$safe.slug(value)`** (`Youla.method('safe')`) — normalizes a string into a URL slug: strips
  accents, drops anything but letters/numbers/spaces/hyphens, and collapses whitespace into single
  hyphens.

## Full source (as it was immediately before removal)

```js
document.addEventListener('youla:init', ()=> {
  /**
   * Multi-step wizard: `u-step="condition"` marks a panel's completion; use as `u-data="step"`.
   * `u-step.required` also requires every required field's native `checkValidity()`.
   * `u-step:action="expr"` runs once each time that panel becomes the current one.
   *
   * @since 1.0
   */
  (() => {
    // Local, not dom.js's closestDirective() — this plugin only reaches into the public "u-data"/".__x" contract.
    function hasUData(el) {
      return [...el.attributes].some(({ name }) => name === 'u-data' || name.startsWith('u-data.'));
    }

    function closestComponent(el) {
      while (el && !hasUData(el)) {
        el = el.parentElement;
      }
      return el ? el.__x : null;
    }

    const REQUIRED_FIELDS_SELECTOR = 'input[required], select[required], textarea[required]';

    Youla.directive('step', (el, output, attribute, component) => {
      const wizard   = component.data;
      const step     = wizard.getStep(el);
      const required = attribute.modifiers.includes('required');

      // Bound once per panel; refresh(el) recomputes only this panel's checkValidity(), not the whole component.
      if (required && !el._x_stepRequiredBound) {
        el._x_stepRequiredBound = true;

        el.querySelectorAll(REQUIRED_FIELDS_SELECTOR).forEach(field => {
          ['input', 'change'].forEach(event => field.addEventListener(event, () => component.refresh(el)));
        });
      }

      // Bare "u-step.required" (no expression) means the required-fields check alone is the condition, not extra on top of an absent (false) one.
      let isComplete = required && attribute.expression.trim() === '' ? true : !!output;

      if (required) {
        isComplete = isComplete && [...el.querySelectorAll(REQUIRED_FIELDS_SELECTOR)].every(field => field.checkValidity());
      }

      // "step" is a reference into the reactive "steps" array (see getStep()), so this write triggers a normal refresh on its own.
      if (step.isComplete !== isComplete) {
        step.isComplete = isComplete;
      }
    });

    Youla.data('step', () => ({
      steps: [],
      currentIndex: 1,
      progress() {
        const total   = this.steps.length;
        const current = Math.min(this.currentIndex, total);

        let complete = 0;
        for(let index = 0; index < current; index++) {
          if(this.steps[index].isComplete) {
            complete++;
          }
        }
        return {
          total, complete, current,
          incomplete: total - complete,
          progress: `${Math.floor(current / total * 100)}%`,
          completion: `${Math.floor(complete / total * 100)}%`,
          percentage: Math.floor(complete / total * 100),
        };
      },
      // "index" is 1-based, matching every other public step number; the "steps" array itself stays 0-based.
      stepAt(index) {
        return this.steps[index - 1] || { el: null };
      },
      current() {
        return this.stepAt(this.currentIndex);
      },
      previous() {
        return this.stepAt(this.previousIndex());
      },
      next() {
        return this.stepAt(this.nextIndex());
      },
      previousIndex() {
        return this.currentIndex - 1 >= 1 ? this.currentIndex - 1 : null;
      },
      nextIndex() {
        return this.currentIndex + 1 <= this.steps.length ? this.currentIndex + 1 : null;
      },
      isStep(index) {
        return Array.isArray(index) ? index.includes(this.currentIndex) : index === this.currentIndex;
      },
      // Each argument is either an exact index or a [from, to] inclusive range, e.g. isSteps(1, [3, 5]).
      isSteps(...values) {
        return values.some(value => (
          Array.isArray(value)
            ? this.currentIndex >= value[0] && this.currentIndex <= value[1]
            : value === this.currentIndex
        ));
      },
      isFirst() {
        return this.previousIndex() === null;
      },
      isNotFirst() {
        return !this.isFirst();
      },
      isLast() {
        return this.nextIndex() === null;
      },
      isNotLast() {
        return !this.isLast();
      },
      isCompleted() {
        return this.current().isComplete && this.nextIndex() === null;
      },
      isUncompleted() {
        return !this.isCompleted();
      },
      canGoNext() {
        return this.current().isComplete && this.nextIndex() !== null;
      },
      cannotGoNext() {
        return !this.canGoNext();
      },
      canGoBack() {
        return this.previousIndex() !== null;
      },
      cannotGoBack() {
        return !this.canGoBack();
      },
      getState() {
        return {
          currentIndex: this.currentIndex,
          isFirst: this.isFirst(),
          isNotFirst: this.isNotFirst(),
          isLast: this.isLast(),
          isNotLast: this.isNotLast(),
          canGoBack: this.canGoBack(),
          cannotGoBack: this.cannotGoBack(),
          canGoNext: this.canGoNext(),
          cannotGoNext: this.cannotGoNext(),
          isCompleted: this.isCompleted(),
          isUncompleted: this.isUncompleted(),
          progress: this.progress(),
        };
      },
      goNext() {
        this.goto(this.nextIndex());
      },
      goBack() {
        this.goto(this.previousIndex());
      },
      goto(index) {
        const previousIndex = this.currentIndex;

        if(index !== null && this.steps[index - 1] !== void 0) {
          this.currentIndex = index;
        }
        this.render();

        if (this.currentIndex !== previousIndex) {
          this.runAction(this.steps[this.currentIndex - 1]);
        }
        return this.current();
      },
      // Read straight off the DOM, not as a reactive attribute: "u-step:action" has no registered directive, so core skips it entirely.
      runAction(step) {
        const expression = step?.el.getAttribute('u-step:action');
        if (!expression) {
          return;
        }

        const component = closestComponent(step.el);
        component?.invokeListener(expression, null, step.el);
      },
      render() {
        this.steps.forEach((step, index) => {
          const isHidden = (index + 1) !== this.currentIndex;
          if(step.el.hidden !== isHidden) {
            step.el.hidden = isHidden;
          }
        });
      },
      // Returns a live reference into the reactive "steps" array, not a bare object outside it.
      getStep(el) {
        let index = el._x_stepIndex;
        if (index === undefined) {
          index = el._x_stepIndex = this.steps.push({ el, isComplete: true }) - 1;
          this.render();
        }
        return this.steps[index];
      },
    }));
  })();

  /**
   * Notifications system: a single `u-data="notice"` container (parts/footer.html) holds the
   * queue. `$notice` resolves to that container's data, so `$notice.info('Saved')` works anywhere.
   *
   * @since 1.0
   */
  (() => {
    Youla.variable('notice', () => document.querySelector('[u-data="notice"]')?.__x?.data);

    Youla.data('notice', () => ({
      items: [],
      duration: 7000,
      hovering: false,
      info( message, duration ) {
        this.add( message, 'info', duration );
      },
      success( message, duration ) {
        this.add( message, 'success', duration );
      },
      warning( message, duration ) {
        this.add( message, 'warning', duration );
      },
      error( message, duration ) {
        this.add( message, 'error', duration );
      },
      loading( message, duration ) {
        this.add( message, 'loading', duration );
      },
      // @mouseenter on the container: freezes every item's countdown where it stood.
      pause() {
        this.hovering = true;

        this.items.forEach(item => {
          if ( item.timer ) {
            clearTimeout( item.timer );
            item.timer     = null;
            item.remaining = Math.max( 0, item.remaining - ( Date.now() - item.startedAt ) );
          }
        });
      },
      // @mouseleave: picks every countdown back up from where pause() froze it.
      resume() {
        this.hovering = false;

        this.items.forEach( item => this.schedule(item.id) );
      },
      schedule( id ) {
        let item = this.items.find( item => item.id === id );
        if ( item && !item.timer && item.duration ) {
          item.startedAt = Date.now();
          item.timer     = setTimeout( () => this.close(id), item.remaining );
        }
      },
      elapsed( item ) {
        return ( item.duration - item.remaining ) + ( item.timer ? Date.now() - item.startedAt : 0 );
      },
      close( id ) {
        let item = this.items.find( item => item.id === id );
        if ( typeof item !== 'undefined' ) {
          clearTimeout( item.timer );

          // u-each only re-renders when "items" itself is reassigned, not on a mutated nested key.
          this.items = this.items.map( item => item.id === id ? { ...item, selectors: [ ...item.selectors, 'hide' ] } : item );

          setTimeout( () => {
            this.items = this.items.filter( item => item.id !== id );
          }, 1000 )
        }
      },
      add( message, type, duration ) {
        if ( message ) {
          let id = Date.now();

          if ( duration === 'auto' ) {
            duration = Math.max( message.length * 70, 1500 );
          } else if ( duration === void 0 ) {
            duration = this.duration;
          }

          // Spinner is a real inline <svg> (parts/footer.html), animated via CSS, so it can be paused on :hover.
          this.items = [ ...this.items, {
            id: id,
            message: message,
            closable: true,
            selectors: [ type || 'info' ],
            duration: duration,
            remaining: duration,
            startedAt: Date.now(),
            timer: null,
            classes() {
              return this.selectors.map( x => 'is-' + x ).join(' ')
            },
          } ];

          if ( !this.hovering ) {
            this.schedule(id);
          }
        }
      },
    }));
  })();

  /**
   * Accessible, stackable dialog: open() pushes a new one on top instead of replacing the
   * current one, so a template can raise its own (e.g. a confirm) from within another. Only
   * the outermost dialog syncs to "?dialog=" in the URL — nested ones are transient.
   *
   * @since 1.0
   */
  (() => {
    Youla.variable('dialog', () => document.querySelector('[u-data="dialog"]')?.__x?.data);

    const searchParamsHandler = (param, value, isRemove) => {
      const url    = new URL(window.location.href);
      const params = new URLSearchParams(url.search);

      if (isRemove) {
        params.delete(param);
      } else {
        params.set(param, value);
      }
      url.search = params.toString();

      window.history.replaceState({}, '', url.toString());
    };

    let uid     = 0;
    let scrollY = 0;

    /**
     * Freezes body at its current scroll offset — plain overflow:hidden alone doesn't
     * block touch scrolling on iOS Safari.
     */
    function lockScroll() {
      scrollY = window.scrollY;

      Object.assign(document.body.style, {
        position: 'fixed',
        top: `-${scrollY}px`,
        width: '100%',
        overflow: 'hidden',
      });
    }

    /**
     * Restores body scroll to the offset lockScroll() froze it at.
     */
    function unlockScroll() {
      Object.assign(document.body.style, {
        position: '',
        top: '',
        width: '',
        overflow: '',
      });

      // "instant" overrides the site's global "scroll-behavior: smooth" — this restore isn't a user-facing scroll.
      window.scrollTo({ top: scrollY, left: 0, behavior: 'instant' });
    }

    // injectDataProviders() reruns this factory per u-data element; "root" only matters for this one's own component.
    Youla.data('dialog', (root) => ({
      stack: [],

      open(templateID, data = {}) {
        setTimeout(() => {
          let template = document.getElementById(templateID);
          if (!template) {
            return;
          }

          const isBase = this.stack.length === 0;

          // u-each (parts/footer.html) initializes each entry itself, wiring up the template's own "@click".
          this.stack.push({ id: ++uid, content: template.innerHTML, ...data });

          // Only the base dialog locks scroll — a nested call would read scrollY as 0 (already frozen).
          if (isBase) {
            lockScroll();
          }

          root.dispatchEvent(new Event('open', { bubbles: true }));

          if (isBase) {
            searchParamsHandler('dialog', templateID, false);
          }
        }, 25);
      },
      // No "id" closes the top dialog; an explicit one (the backdrop's own click) closes that entry.
      close(id) {
        const target = id ?? this.stack.at(-1)?.id;
        this.stack   = this.stack.filter(dialog => dialog.id !== target);

        root.dispatchEvent(new Event('close', { bubbles: true }));

        if (this.stack.length === 0) {
          unlockScroll();
          searchParamsHandler('dialog', null, true);
        }
      },
      // Snapshot first: close() reassigns "stack" on every call, so iterating the live array would skip entries.
      clear() {
        [...this.stack].forEach(entry => this.close(entry.id));
      },
      // Reopens the base dialog from a shared URL, e.g. via "@load" on the element matching templateID.
      async init(templateID, callback) {
        const params = new URLSearchParams(window.location.search);

        if (templateID && params.get('dialog') === templateID && callback) {
          const data = await callback();

          if (data) {
            this.open(templateID, data);
          }
        }
      },
    }));
  })();

  /**
   * Password policy: checks a string against a fixed policy (minimum count per character
   * class, minimum length) and can generate a password satisfying it.
   *
   * @since 1.0
   */
  Youla.data('password', () => ({
    value: '',
    visible: false,
    progress: 0,
    labels: ['Слишком слабый', 'Слабый', 'Средний', 'Хороший', 'Отличный'],
    min: {
      lowercase: 2,
      uppercase: 2,
      special: 2,
      digit: 2,
      length: 12
    },
    valid: {
      lowercase: false,
      uppercase: false,
      special: false,
      digit: false,
      length: false
    },
    charsets: {
      lowercase: 'abcdefghijklmnopqrstuvwxyz',
      uppercase: 'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
      special: '!@#$%^&*(){|}~',
      digit: '0123456789'
    },
    toggle() {
      this.visible = !this.visible;
    },
    level() {
      return Math.min(4, Math.round(this.progress / 25));
    },
    label() {
      return this.labels[this.level()];
    },
    check(value) {
      // Whitespace isn't part of any charset and would otherwise count toward length for free.
      if (/\s/.test(value)) {
        value = this.value = value.replace(/\s/g, '');
      }

      let matchCount = 0;
      // One point per character class plus one for the length rule.
      let totalWeight = Object.keys(this.charsets).reduce((sum, type) => sum + this.min[type], 0) + 1;

      for (const type in this.charsets) {
        let charsetRegex = new RegExp(`[${this.charsets[type]}]`, 'g');
        let charsetCount = (value.match(charsetRegex) || []).length;

        matchCount += Math.min(charsetCount, this.min[type]);
        this.valid[type] = charsetCount >= this.min[type];
      }

      this.valid.length = value.length >= this.min.length;
      if (this.valid.length) {
        matchCount += 1;
      }

      this.progress = (matchCount / totalWeight) * 100;

      return this.progress;
    },
    generate() {
      let pool = Object.values(this.charsets).join('');
      let password = '';

      for (const type in this.charsets) {
        for (let i = 0; i < this.min[type]; i++) {
          password += this.charsets[type][Math.floor(Math.random() * this.charsets[type].length)];
        }
      }

      while (password.length < this.min.length) {
        password += pool[Math.floor(Math.random() * pool.length)];
      }

      this.value = this.shuffle(password);
      this.check(this.value);

      return this.value;
    },
    shuffle(password) {
      let array = password.split('');

      for (let i = array.length - 1; i > 0; i--) {
        let j = Math.floor(Math.random() * (i + 1));
        [array[i], array[j]] = [array[j], array[i]];
      }

      return array.join('');
    },
  }));

  /**
   * Avatar uploader.
   *
   * @since 1.0
   */
  Youla.data('avatar', () => ({
    name: '',
    image: '',
    field: {
      'u-prop': 'name',
    },
    picture: {
      ':title': 'name',
      ':style': "image && `background-image:url(${image})`",
    },
    initials: {
      'u-show': '!image',
      'u-text'() {
        return this.name.trim().split(/\s+/).map(word => word[0]).slice(0, 2).join('').toUpperCase();
      },
    },
    uploader: {
      '@change'() {
        let file = this.$event.target.files[0];
        if (file) {
          let reader = new FileReader();
          reader.onload = e => this.image = e.target.result;
          reader.readAsDataURL(file);
        }
      },
    },
    remover: {
      'u-show': 'image',
      '@click'() {
        let input = this.$root.querySelector('input[type="file"]');
        if (input) {
          input.value = '';
        }
        this.image = '';
      },
    },
  }));

  /**
   * Table checkboxes
   *
   * @since 1.0
   */
  Youla.data('table', () => ({
    anchor: null,
    trigger: {
      '@change': 'selectAll($el, $root)',
    },
    item: {
      '@click': 'selectItem($el, $root, $event)',
    },
    items(root) {
      return [...root.querySelectorAll('[u-bind~="item"]')];
    },
    selectAll(el, root) {
      this.items(root).forEach(input => input.checked = el.checked);
    },
    selectItem(el, root, event) {
      let items   = this.items(root);
      let index   = items.indexOf(el);
      let checked = el.checked;
      let start   = event.shiftKey && this.anchor !== null ? this.anchor : index;

      for (let i = Math.min(start, index); i <= Math.max(start, index); i++) {
        items[i].checked = checked;
      }
      this.anchor = index;
    },
  }));

  /**
   * Custom fields builder.
   *
   * @since 1.0
   */
  Youla.data('builder', () => ({
    default: {
      field: 'post',
      operator: '===',
      value: '',
    },
    groups: [],
    addGroup() {
      this.groups.push({ rules: [ { ...this.default } ] });
    },
    removeGroup(index) {
      this.groups.splice(index, 1);
    },
    addRule(key) {
      this.groups[key].rules.push({ ...this.default });
    },
    removeRule(key, index) {
      this.groups[key].rules.splice(index, 1);
    },
    submit() {
      console.log(JSON.parse(JSON.stringify(this.groups)));
    },
  }));

  /**
   * Selfie: `u-data="stream"` (one instance per root) wraps `getUserMedia` into a
   * preview -> snapshot -> canvas -> image flow.
   *
   * @since 1.0
   */
  Youla.data('stream', (root) => ({
    error: null,
    canvas: null,
    videoRef: { 'u-ref': 'video' },
    imageRef: { 'u-ref': 'image' },
    canvasRef: { 'u-ref': 'canvas' },
    get refs() {
      return {
        video:  root.querySelector('[u-ref="video"]'),
        image:  root.querySelector('[u-ref="image"]'),
        canvas: root.querySelector('[u-ref="canvas"]'),
      };
    },
    check() {
      const { video, image } = this.refs;

      if (!video) {
        console.error('Video for selfie preview is undefined');
        return false;
      }

      if (!image) {
        console.error('Image for output selfie is undefined');
        return false;
      }

      return true;
    },
    getCanvas() {
      return this.refs.canvas || (this.canvas || (this.canvas = document.createElement('canvas')));
    },
    isVisible(element) {
      const styles = window.getComputedStyle(element);
      if (styles) {
        return !(styles.visibility === 'hidden' || styles.display === 'none' || parseFloat(styles.opacity) === 0);
      }
      return false;
    },
    async requestStream(video) {
      if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        this.error = 'unsupported';
        return;
      }

      try {
        video.srcObject = video._x_stream = await navigator.mediaDevices.getUserMedia({video: true});
        this.error = null;
      } catch (error) {
        this.error = error.name === 'NotAllowedError' || error.name === 'SecurityError' ? 'denied' : 'unavailable';
      }
    },
    start() {
      const video = this.refs.video;
      if (video._x_stream || video._x_streamObserver) {
        return;
      }

      if (this.isVisible(video)) {
        this.requestStream(video);
        return;
      }

      video._x_streamObserver = new IntersectionObserver(entries => {
        if (entries.some(entry => entry.isIntersecting) && this.isVisible(video)) {
          video._x_streamObserver.disconnect();
          video._x_streamObserver = null;
          this.requestStream(video);
        }
      });
      video._x_streamObserver.observe(video);
    },
    snap() {
      if (!this.check()) {
        return null;
      }
      this.start();

      const canvas = this.getCanvas();
      const { video, image } = this.refs;

      let imageStyles = window.getComputedStyle(image),
        targetRatio = parseInt(imageStyles.width, 10) / parseInt(imageStyles.height, 10);

      let videoWidth  = video.videoWidth,
        videoHeight = video.videoHeight,
        videoRatio  = videoWidth / videoHeight;

      let sWidth, sHeight;
      if (videoRatio > targetRatio) {
        sHeight = videoHeight;
        sWidth  = videoHeight * targetRatio;
      } else {
        sWidth  = videoWidth;
        sHeight = videoWidth / targetRatio;
      }

      let sx = (videoWidth - sWidth) / 2,
        sy = (videoHeight - sHeight) / 2;

      canvas.width  = sWidth;
      canvas.height = sHeight;

      let ctx = canvas.getContext('2d');

      // 1:1 pixel copy of the native camera resolution — no resampling, so no quality is lost
      ctx.drawImage(video, sx, sy, sWidth, sHeight, 0, 0, sWidth, sHeight);

      let imageData = canvas.toDataURL('image/png');
      if ( imageData ) {
        image.src = imageData;
        ctx.clearRect(0, 0, canvas.width, canvas.height);
      }
      return imageData;
    },
    stop() {
      const video = this.refs.video;
      if (video._x_streamObserver) {
        video._x_streamObserver.disconnect();
        video._x_streamObserver = null;
      }
      if (video._x_stream) {
        video._x_stream.getTracks().forEach(track => track.stop());
      }
      video._x_stream = null;
    },
  }));

  /**
   * Search box: `wrapper`/`button`/`input` are ready-made `u-bind` sets. `button`/`input` carry
   * their own `u-ref`, so the wrapper's Ctrl+K shortcut reaches the input via `$refs`.
   *
   * @since 1.0
   */
  Youla.data('search', () => ({
    currentIdx: -1,
    links: [],
    wrapper: {
      '@click.outside'() {
        this.$el.removeAttribute('open');
      },
      '@keydown.escape'() {
        this.$el.removeAttribute('open');
      },
      '@keydown.prevent.window.ctrl.k'() {
        this.$refs.searchButton.click();
      },
    },
    button: {
      'u-ref': 'searchButton',
      '@click'() {
        setTimeout(() => this.$refs.searchInput.focus());
      },
    },
    input: {
      'u-ref': 'searchInput',
      '@keydown.up'() {
        this.currentIdx = this.currentIdx <= 0 ? this.links.length - 1 : this.currentIdx - 1;
        if (!this.links[this.currentIdx]?.url && this.currentIdx === 0) {
          this.currentIdx = this.links.length - 1;
        }
      },
      '@keydown.down'() {
        this.currentIdx = this.currentIdx >= this.links.length - 1 ? 0 : this.currentIdx + 1;
        if (!this.links[this.currentIdx]?.url) {
          this.currentIdx++;
        }
      },
      '@keydown.enter'() {
        this.links[this.currentIdx] && (window.location.href = this.links[this.currentIdx].url);
      },
    },
  }));

  /**
   * Tabs, synced with the page URL: `u-data="tab"` on the wrapper, `u-bind="tabButton('id')"` on
   * each tab button, `u-bind="tabContent('id')"` on each panel. The active tab is read from the
   * `?tab=` query param if present, else `data-tab` on the `u-data` element itself.
   *
   * @since 1.0
   */
  Youla.data('tab', (root) => ({
    tab: new URLSearchParams(window.location.search).get('tab') || root.dataset.tab || null,
    tabButton(id) {
      return {
        ':class'() {
          return this.tab === id ? 'active' : '';
        },
        '@click'() {
          this.tab = id;

          const url = new URL(window.location.href);
          url.searchParams.set('tab', id);
          window.history.pushState({}, '', url);
        },
      };
    },
    tabContent(id) {
      return {
        'u-show'() {
          return this.tab === id;
        },
      };
    },
  }));

  /**
   * `$dirty` — warns about unsaved form changes. Call `$dirty.watch($el)` once per form (e.g.
   * `<form @load="$dirty.watch($el)">`) and `$dirty.remove($el)` after a successful save.
   *
   * @since 1.0
   */
  Youla.variable('dirty', () => {
    // This factory re-runs on every expression evaluation page-wide, so state lives on form/body attributes, not JS variables.
    const serialize = form => JSON.stringify(Object.fromEntries(new FormData(form).entries()));

    const sync = () => {
      const isDirty = [...document.querySelectorAll('form[data-dirty-watch]')]
        .some(form => form.dataset.initialState !== serialize(form));

      document.body.classList.toggle('is-unsaved', isDirty);
    };

    return {
      watch(form) {
        if (!(form instanceof HTMLFormElement) || form.dataset.dirtyWatch !== undefined) {
          return;
        }
        form.dataset.dirtyWatch = '';

        // Only ever bound once, page-wide, regardless of how many forms call watch().
        if (document.body.dataset.dirtyBound === undefined) {
          document.body.dataset.dirtyBound = '';

          // Shakes the page instead of following the link, while any watched form is still dirty.
          window.addEventListener('click', e => {
            if (document.body.classList.contains('is-unsaved') && e.target.closest('a[href]')) {
              e.preventDefault();

              document.body.classList.add('is-shake');
              setTimeout(() => document.body.classList.remove('is-shake'), 500);
            }
          }, true);
        }

        // Deferred so the form's own reactive hydration settles first, or that fill-in would register as a "dirty" change.
        setTimeout(() => {
          form.dataset.initialState = serialize(form);

          form.addEventListener('input', sync);
          form.addEventListener('change', sync);
          form.addEventListener('reset', () => setTimeout(() => {
            form.dataset.initialState = serialize(form);
            sync();
          }, 0));
        }, 50);
      },
      remove(form) {
        if (!(form instanceof HTMLFormElement)) {
          return;
        }

        form.dataset.initialState = serialize(form);
        sync();
      },
    };
  });

  /**
   * Copies a string to the clipboard, e.g. `@click="$copy('Some text', ['is-copied'])"`.
   *
   * @since 1.0
   */
  Youla.method('copy', (e, el) => (subject, classes) => {
    window.navigator.clipboard.writeText(subject).then(() => {
      const classes       = classes || ['ph-copy', 'ph-check'];
      const classesToggle = () => classes.forEach(s => el.classList.toggle(s));

      classesToggle();
      setTimeout(classesToggle, 1000);
    });
  });

  /**
   * Data sanitizing.
   *
   * @since 1.0
   */
  Youla.method('safe', () => ({
    slug(value) {
      // NFD-decomposes accented characters (e.g. "é" -> "e" + accent), then drops the accents and anything but letters/numbers/spaces/hyphens.
      return value
        .toString()
        .normalize('NFD')
        .replace(/[̀-ͯ]/g, '')
        .replace(/[^\p{L}\p{N}\s-]/gu, '')
        .trim()
        .replace(/\s+/g, '-')
        .replace(/-+/g, '-')
        .toLowerCase();
    },
  }));

  /**
   * Code syntax highlight
   *
   * @since 1.0
   */
  Youla.directive('highlight', (el, output, { modifiers }) => {
    // Wraps el's children in <code> exactly once — a later call would otherwise nest another wrapper around it each time.
    if (el._x_highlighted) {
      return;
    }
    el._x_highlighted = true;

    const lang    = modifiers[0] || 'html';
    const wrapper = document.createElement('code');

    wrapper.className = `language-${lang}`;
    wrapper.append(...el.childNodes);

    el.classList.add('line-numbers');
    el.setAttribute('data-lang', lang.toUpperCase());
    el.replaceChildren(wrapper);
  });

  /**
   * Disable autofill, reliably — the readonly-until-focus trick stops
   * autofill from prefilling the field before the user interacts with it,
   * even when the browser ignores `autocomplete="off"`.
   *
   * @since 1.0
   */
  Youla.directive('noautofill', (el) => {
    // Attaches focus/blur listeners exactly once — a later call would otherwise stack another pair, each fighting over el.readOnly.
    if (el._x_noautofill) {
      return;
    }
    el._x_noautofill = true;

    const lock = () => el.readOnly = true;

    lock();

    el.addEventListener('focus', () => requestAnimationFrame(() => el.readOnly = false));
    el.addEventListener('blur', lock);
  });

  /**
   * Pins a sidebar within its `position: relative` parent's bounds as it scrolls, instead
   * of sticking to the viewport — a taller-than-viewport sidebar scrolls internally.
   *
   * @since 1.0
   */
  Youla.directive('sticky', el => {
    // Attaches its window listeners exactly once — a later call would otherwise stack another "reposition" closure that runs forever.
    if (el._x_sticky) {
      return;
    }
    el._x_sticky = true;

    const parent = el.parentElement;
    if (getComputedStyle(parent).position !== 'relative') {
      console.warn('Youla.js: "u-sticky" requires its parent to have position: relative.');
      return;
    }

    const paddingTop    = parseInt(getComputedStyle(parent).paddingTop) + 42;
    const paddingBottom = parseInt(getComputedStyle(parent).paddingBottom);

    let top        = paddingTop;
    let lastScroll = window.scrollY;

    // Recomputed on every call (not cached) so a resize is picked up for free.
    const reposition = () => {
      const rect     = el.getBoundingClientRect();
      const overflow = rect.height - window.innerHeight;
      const delta    = window.scrollY - lastScroll;
      lastScroll     = window.scrollY;

      // Only slide while actually stuck — rect.top runs ahead of "top" otherwise.
      if (overflow <= 0 || rect.top > top) {
        return;
      }

      top = Math.min(paddingTop, Math.max(-overflow - paddingBottom, top - delta));
      el.style.top = `${top}px`;
    };

    el.style.position = 'sticky';
    el.style.top      = `${paddingTop}px`;

    ['load', 'scroll', 'resize'].forEach(event => window.addEventListener(event, reposition));
  });

  /**
   * Expands or collapses an element with a smooth slide animation, driven by the
   * directive's truthiness (`u-collapse="open"`) rather than a CSS class.
   *
   * @since 1.0
   */
  Youla.directive('collapse', (el, output) => {
    const isOpen   = !!output;
    const duration = 200;
    const props    = ['height', 'paddingTop', 'paddingBottom', 'marginTop', 'marginBottom'];

    el.style.overflow = 'hidden';
    if (isOpen) {
      el.style.display = 'block';
    }

    const from = Object.fromEntries(props.map(prop => [prop, parseFloat(getComputedStyle(el)[prop])]));

    let start;
    function step(timestamp) {
      start ??= timestamp;

      const elapsed = Math.min(timestamp - start, duration);
      const ratio   = isOpen ? elapsed / duration : 1 - elapsed / duration;

      props.forEach(prop => el.style[prop] = `${from[prop] * ratio}px`);

      if (elapsed < duration) {
        requestAnimationFrame(step);
      } else {
        if (!isOpen) {
          el.style.display = 'none';
        }
        [...props, 'overflow'].forEach(prop => el.style[prop] = '');
      }
    }
    requestAnimationFrame(step);
  });

  /**
   * Grows a <textarea> to fit its content as the user types, up to a
   * maximum number of rows (`u-textarea="6"`) — past that, it stops
   * growing and scrolls internally instead.
   *
   * @since 1.0
   */
  Youla.directive('textarea', (el, output) => {
    // Attaches its input listener exactly once — a later call would otherwise stack another, resizing the textarea redundantly on every keystroke.
    if (el.tagName !== 'TEXTAREA' || el._x_textarea) {
      return;
    }
    el._x_textarea = true;

    el.addEventListener('input', () => {
      const maxRows = parseInt(output) || 99;
      if (el.value.split(/\r\n|\r|\n/).length > maxRows) {
        return;
      }

      const border = parseInt(getComputedStyle(el).borderWidth) * 4;

      el.style.height = 'auto';
      el.style.height = `${el.scrollHeight + border + 4}px`;
    });
  });

  /**
   * Animates `--youla-progress` into view once the element enters the viewport, from/to
   * modifiers as percentages (`u-progress.20.80.600ms`). `to` can also be a reactive bound
   * value, e.g. `u-progress.0.600ms="percent"`. Skips the transition on reduced motion.
   *
   * @since 1.0
   */
  Youla.directive('progress', (el, output, { modifiers, duration, expression }) => {
    const [rawFrom = 0, rawTo = 100] = modifiers;

    const from = parseInt(rawFrom);

    const bound = expression !== '' && !isNaN(parseFloat(output));
    const to    = bound ? parseFloat(output) : parseInt(rawTo);

    if (isNaN(from) || isNaN(to)) {
      console.warn('Youla.js: "u-progress" requires numeric from/to modifiers as percentages (or a numeric bound value), e.g. u-progress.20.80.600ms.');
      return;
    }

    const start = Math.min(Math.max(from, 0), 100);
    const end   = Math.min(Math.max(to, 0), 100);

    const transitionDuration = duration ? `${duration.value}${duration.unit}` : '0ms';
    const reducedMotion      = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    const apply = (percent, animate) => {
      if (animate && !reducedMotion()) {
        el.style.setProperty('--youla-progress-transition', `width ${transitionDuration}`);
      }
      el.style.setProperty('--youla-progress', `${percent}%`);
    };

    // Already revealed — this call is a reactive update to the bound value, not the initial mount.
    if (el._x_progress?.revealed) {
      el._x_progress.end = end;
      apply(end, true);
      return;
    }

    if (el._x_progress) {
      el._x_progress.end = end;
      return;
    }

    el._x_progress = { revealed: false, end };

    new IntersectionObserver(([entry], observer) => {
      if (!entry.isIntersecting) {
        return;
      }
      observer.unobserve(el);

      el._x_progress.revealed = true;

      el.style.setProperty('--youla-progress', `${start}%`);

      if (reducedMotion()) {
        apply(el._x_progress.end, false);
        return;
      }

      setTimeout(() => apply(el._x_progress.end, true), 500);
    }).observe(el);
  });
});
```
