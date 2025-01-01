'use strict';

const defaultId = 'selectionArea';

const defaultStyle = 'background: rgba(52, 152, 219, 0.1); border: 1px solid #4089c3; position: absolute; z-index: 1000; top: 0; left: 0;';

class Area {
    constructor(def, x = 0, y = 0) {
        this.id = def.id || defaultId;
        this.class = def.class || false;
        this.x = x;
        this.y = y;
        this.w = 0;
        this.h = 0;
    }
    instance(parent) {
        let areaElem = document.createElement('div');
        areaElem.setAttribute('id', this.id);
        areaElem.setAttribute('style', defaultStyle);
        if (this.class) {
            areaElem.setAttribute('class', this.class);
        }
        parent.appendChild(areaElem);
        this.parent = parent;
        this.elem = document.getElementById(this.id);
    }
    resize(x, y) {
        this.w = Math.abs(this.x - x);
        this.h = Math.abs(this.y - y);
    }
    move(posx, posy) {
        let pos = {
            y: this.y >= posy ? posy : this.y,
            x: this.x >= posx ? posx : this.x,
            w: this.w,
            h: this.h
        };
        this.elem.style.top = `${pos.y}px`;
        this.elem.style.left = `${pos.x}px`;
        this.elem.style.width = `${pos.w}px`;
        this.elem.style.height = `${pos.h}px`;
    }
    destroy() {
        if (this.parent.contains(this.elem)) {
            this.parent.removeChild(this.elem);
        }
    }
    isOver(target) {
        let aRect = this.elem.getBoundingClientRect();
        let bRect = target.getBoundingClientRect();
        let a = {
            top: aRect.top,
            left: aRect.left,
            bottom: aRect.top + aRect.height,
            right: aRect.left + aRect.width
        };
        let b = {
            top: bRect.top,
            left: bRect.left,
            bottom: bRect.top + bRect.height,
            right: bRect.left + bRect.width
        };
        return !(b.left > a.right || b.right < a.left || b.top > a.bottom || b.bottom < a.top);
    }
}

const initMouse = 'mousedown';

const updateMouse = 'mousemove';

const endMouse = 'mouseup';

const initTouch = 'touchstart';

const updateTouch = 'touchmove';

const endTouch = 'touchend';

class Selection {
    constructor(config) {
        try {
            this.nodes = [];
            this.container = this.validateContainer(config.container || document.querySelector('.parent'));
            this.selector = this.validateSelector(config.selector || '.child');
            this.areaAttributes = config.areaAttributes || {
                id: 'my-custom-area',
                class: ''
            };
            this.touchable = config.touchable || 'ontouchstart' in window || navigator.maxTouchPoints || true;
            this.autostart = config.autostart || true;
            this.onSelectEnd = config.onSelectEnd || null;
            this.classSelected = config.classSelected || 'selected';
        } catch (e) {
            throw e;
        }
        if (this.autostart) {
            this.start();
        }
    }
    validateContainer(container) {
        if (!container || !(container instanceof HTMLElement)) {
            throw new Error('"container" attribute must be HTMLElement');
        }
        return container;
    }
    validateSelector(selector) {
        if (!selector) {
            throw new Error('"elements" attribute must be defined');
        }
        if (typeof selector === 'string') {
            return selector;
        } else {
            throw new Error('"elements" property must be a String');
        }
    }
    start() {
        this.container.addEventListener(initMouse, this);
        this.container.addEventListener(updateMouse, this);
        this.container.addEventListener(endMouse, this);
        if (this.touchable) {
            this.container.addEventListener(initTouch, this);
            this.container.addEventListener(updateTouch, this);
            this.container.addEventListener(endTouch, this);
        }
    }
    stop() {
        this.container.removeEventListener(initMouse, this);
        this.container.removeEventListener(updateMouse, this);
        this.container.removeEventListener(endMouse, this);
        if (this.touchable) {
            this.container.removeEventListener(initTouch, this);
            this.container.removeEventListener(updateTouch, this);
            this.container.removeEventListener(endTouch, this);
        }
    }
    handleEvent(e) {
        e.preventDefault();
        let pos = this.touchable && e.targetTouches && e.targetTouches.length ? e.targetTouches[0] : e;
        let [x, y] = [ pos.pageX, pos.pageY ];
        switch (e.type) {
          case initMouse:
          case initTouch:
            this.init(x, y);
            break;

          case updateMouse:
          case updateTouch:
            this.update(x, y);
            break;

          case endMouse:
          case endTouch:
            this.end(x, y);
            break;
        }
    }
    init(x, y) {
        this.selected = [];
        this.area = new Area(this.areaAttributes, x, y);
        this.area.instance(this.container);
    }
    update(x, y) {
        if (this.area) {
            Array.from(this.container.querySelectorAll(this.selector)).forEach((node => {
                if (!this.nodes.includes(node)) {
                    if (this.area.isOver(node)) {
                        node.classList.add(this.classSelected);
                    } else {
                        node.classList.remove(this.classSelected);
                    }
                }
            }));
            this.area.resize(x, y);
            this.area.move(x, y);
        }
    }
    end() {
        if (this.area) {
            let nodes = Array.from(this.container.querySelectorAll(this.selector));
            this.selected = nodes.filter((node => this.area.isOver(node) && this.selected.indexOf(node) === -1));
            this.nodes = this.nodes.concat(this.selected.filter((element => !this.nodes.includes(element))));
            this.selected.forEach((element => {
                if (!element.classList.contains(this.classSelected)) {
                    element.classList.add(this.classSelected);
                }
            }));
            this.area.destroy();
            this.area = null;
            if (typeof this.onSelectEnd === 'function') {
                this.onSelectEnd(this);
            }
        }
    }
}

const selection = Selection;

document.addEventListener('alpine:init', (() => {
    Alpine.directive('storage', ((el, {value, expression, modifiers}, {evaluateLater, cleanup}) => {
        new selection({
            container: el,
            selector: '.storage__item',
            classSelected: 'active',
            onSelectEnd: selection => {
                console.log(selection);
            }
        });
    }));
}));