import Area from './area';

const initMouse   = 'mousedown';
const updateMouse = 'mousemove';
const endMouse    = 'mouseup';
const initTouch   = 'touchstart';
const updateTouch = 'touchmove';
const endTouch    = 'touchend';

/**
 * SelectionArea class listen mouse movements to create and adapt a selection 
 * area checking if intersects with any target child element and returns the 
 * content of defined property of that childs.
 *
 * @param  {Object} config Config object
 * @param  {Element} config.container DOM Element to make selectable
 * @param  {string} [config.area='selectionArea'] DOM ID for selection area to define styles
 * @param  {string} [config.area.id='selectionArea'] DOM ID for selection area to define styles
 * @param  {Object} [config.area.class] DOM class for selection area to define styles
 * @param  {String} config.targets DOM selector of selectables childs
 * @param  {Array} config.targets List of selectable childs DOM selectors
 * @param  {boolean} [config.touchable=false] Listen to touch instead mouse 
 * events, default `false`
 * @param  {boolean} [config.autostart=false] Control autostart selection area 
 * events, default `false`
 * @param  {function} [config.onSelectEnd] Function to call when selection ends
 * @example
 * 
 * let config = {
 *     container: document.querySelector('.parent'),
 *     area: 'areaElemId' || {
 *         id: 'areaElemId',
 *         class: 'areaElemClass'
 *     },
 *     selector: '.targetSelector1',
 *     touchable: true,
 *     autostart: true,
 *     onSelectEnd: selection => {
 *         if (selection.length == 0) console.warn("empty selection");
 *         else console.log(selection);
 *     }
 * }
 * 
 * let selectable = new SelectionArea(config);
 * @class
 */
class Selection {
    constructor(config) {
        try {
            this.nodes          = [];
            this.container      = this.validateContainer( config.container || document.querySelector('.parent') );
            this.selector       = this.validateSelector( config.selector || '.child' );
            this.areaAttributes = config.areaAttributes || {id: 'my-custom-area', class: ''};
            this.touchable      = config.touchable || 'ontouchstart' in window || navigator.maxTouchPoints || true;
            this.autostart      = config.autostart || true;
            this.onSelectEnd    = config.onSelectEnd || null;
            this.classSelected  = config.classSelected || 'selected';
        } catch (e) {
            throw e;
        }

        if (this.autostart) {
            this.start();
        }
    }

    /**
     * Checks if data provided by user contains a valid HTML Element to return it.
     *
     * @param container
     * @returns {HTMLElement}
     */
    validateContainer(container) {
        if (!container || !(container instanceof HTMLElement)) {
            throw new Error('"container" attribute must be HTMLElement');
        }
        return container;
    }

    /**
     * Check if data provided by user contains a attribute named as 'elements' and
     * checks if that attribute is a String (so would be the elements selector).
     * Otherwise, throw a error.
     */
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

    /**
     * start function attachs to container the listeners on defined triggers.
     */
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

    /**
     * stop function removes the listeners from current container.
     */
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

    /**
     * handleEvent extends JavaScript Event interface as custom functions
     * dispatcher getting current position considering touch events.
     *
     * @param  {Event} e Event data
     * @ignore
     */
    handleEvent(e) {
        e.preventDefault();

        let pos = this.touchable && e.targetTouches && e.targetTouches.length ? e.targetTouches[0] : e;
        let [ x, y ] = [ pos.pageX, pos.pageY ];

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

    /**
     * init function clears current selection, creates new area with ID provided and instances it into current container.
     *
     * @param  {number} [x] Current position on x axis
     * @param  {number} [y] Current position on y axis
     * @ignore
     */
    init(x, y) {
        this.selected = [];
        this.area = new Area(this.areaAttributes, x, y);
        this.area.instance(this.container);
    }

    /**
     * update receives current position and updates current selection area with 
     * that position, resizing area and moving it.
     * @param  {number} [x] Current position on x axis
     * @param  {number} [y] Current position on y axis
     * @ignore
     */
    update(x, y) {
        if (this.area) {
            Array.from(this.container.querySelectorAll(this.selector)).forEach(node => {
                if (!this.nodes.includes(node)) {
                    if (this.area.isOver(node)) {
                        node.classList.add(this.classSelected);
                    } else {
                        node.classList.remove(this.classSelected);
                    }
                }
            });

            this.area.resize(x, y);
            this.area.move(x, y);
        }
    }
    /**
     * end extract selected items, destroy current selection area and
     * invokes callback passing values of selected items.
     * @ignore
     */
    end() {
        if (this.area) {
            let nodes = Array.from(this.container.querySelectorAll(this.selector));

            this.selected = nodes.filter(node => this.area.isOver(node) && this.selected.indexOf(node) === -1);
            this.nodes    = this.nodes.concat(this.selected.filter(element => !this.nodes.includes(element)));

            this.selected.forEach(element => {
                if (!element.classList.contains(this.classSelected)) {
                    element.classList.add(this.classSelected);
                }
            });

            this.area.destroy();
            this.area = null;
            if (typeof this.onSelectEnd === 'function') {
                this.onSelectEnd(this);
            }
        }
    }
}

export default Selection;