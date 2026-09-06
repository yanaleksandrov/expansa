(function() {
    'use strict';
    var filler_panelinline_namespaceObject = '.filler-dropdown{overflow-x:hidden;padding:8px;background:#fff;border:1px solid #e7e7e7;border-radius:12px;box-shadow:rgba(0,0,0,.2) 0 8px 24px;font-size:12px;display:flex;flex-direction:column;min-width:240px;box-sizing:border-box}.filler-palette-section+.filler-palette-section{margin-top:10px}.filler-palette-title{margin-bottom:6px;font-size:10px;letter-spacing:.05em;text-transform:uppercase;color:#8a8f93}.filler-palette-row{display:flex;flex-direction:column;gap:1px}.filler-palette-swatch{position:relative;display:flex;align-items:center;gap:8px;width:100%;padding:5px 6px;border:none;border-radius:5px;background:none;text-align:left;cursor:pointer}.filler-palette-swatch:hover{background:rgba(0,0,0,.05)}.filler-palette-swatch:hover .filler-palette-remove{opacity:1}.filler-palette-chip{flex:none;width:16px;height:16px;border-radius:3px;border:1px solid rgba(0,0,0,.1)}.filler-palette-label{flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:#1a1a1a}.filler-palette-hex{flex:none;font-family:var(--mono, ui-monospace, monospace);font-size:10px;color:#8a8f93}.filler-palette-remove{flex:none;display:flex;align-items:center;justify-content:center;opacity:0;width:16px;height:16px;border-radius:50%;background:#fff;border:1px solid #dadada;color:#8a8f93;font-size:9px;line-height:1}.filler-palette-add{display:flex;align-items:center;gap:8px;width:100%;padding:5px 6px;border:none;border-radius:5px;background:none;color:#8a8f93;cursor:pointer}.filler-palette-add:hover{background:rgba(0,0,0,.05)}.filler-palette-add .filler-palette-chip{display:flex;align-items:center;justify-content:center;border:1px dashed #dadada;line-height:1}.filler-dialog{background:#fff;border:1px solid #dfe2e3;border-radius:12px;box-shadow:0 8px 24px rgba(0,0,0,.2);font-size:12px;display:flex;flex-direction:column;min-width:240px}.filler-dialog-area{position:relative;width:100%;aspect-ratio:1/1;outline:rgba(0,0,0,.1) solid 1px;outline-offset:-1px;border-radius:5px;cursor:crosshair;touch-action:none;background-image:linear-gradient(to top, #000, transparent),linear-gradient(to right, #fff, transparent)}.filler-dialog-hue,.filler-dialog-alpha{position:relative;height:16px;border-radius:16px;cursor:pointer;touch-action:none;outline:rgba(0,0,0,.1) solid 1px;outline-offset:-1px}.filler-dialog-hue{background-image:linear-gradient(to right, red, yellow, lime, cyan, blue, magenta, red)}.filler-dialog-alpha{background-image:conic-gradient(#d8d8d8 90deg, #ffffff 90deg 180deg, #d8d8d8 180deg 270deg, #ffffff 270deg);background-size:8px 8px}.filler-dialog-alpha-gradient{position:absolute;inset:0;border-radius:inherit}.filler-dialog-handle,.filler-dialog-area-handle{--handle-size: 16px;position:absolute;transform:translate(-50%, -50%);pointer-events:none;box-sizing:border-box;box-shadow:0 0 .5px rgba(0,0,0,.2),0 3px 8px rgba(0,0,0,.1),0 1px 3px rgba(0,0,0,.1),0 0 0 1px rgba(0,0,0,.2),inset 0 0 0 1px rgba(0,0,0,.2);width:var(--handle-size);height:var(--handle-size);border-radius:50%;border:4px solid #fff}.filler-dialog-handle{position:absolute;top:50%;left:calc(var(--handle-size)/2 + var(--percent, 0)*(100% - var(--handle-size)))}.filler-dialog-tabs{display:flex;align-items:center;gap:4px;padding-bottom:3px;border-bottom:1px solid #dadada}.filler-dialog-tab{position:relative;padding:4px;border:none;background:none;font-size:10px;letter-spacing:.025em;text-transform:uppercase;color:#8a8f93;cursor:pointer;transition:all .25s ease}.filler-dialog-tab:hover{color:#1a1a1a}.filler-dialog-tab.is-active{color:#1a1a1a}.filler-dialog-tab.is-active::after{content:"";position:absolute;left:0;right:0;bottom:-3px;height:2px;border-radius:1px;background:#000}.filler-dialog-fields{display:flex;align-items:flex-end;gap:6px}.filler-dialog-eyedropper,.filler-dialog-copy{flex:none;display:flex;align-items:center;justify-content:center;width:24px;height:24px;padding:0;border:none;border-radius:5px;background:none;color:#8a8f93;cursor:pointer}.filler-dialog-eyedropper:hover,.filler-dialog-copy:hover{background:rgba(0,0,0,.06);color:#1a1a1a}.filler-dialog-eyedropper svg{display:block;fill:currentColor}.filler-dialog-eyedropper[hidden]{display:none}.filler-dialog-copy.is-copied{color:#2ecc71}.filler-dialog-fields-values{display:flex;flex:1}.filler-dialog-field{flex:1;min-width:0;display:flex;flex-direction:column-reverse;align-items:center;gap:2px;margin-left:-1px}.filler-dialog-field input{width:100%;min-width:0;padding:4px;border:1px solid #dadada;outline:none;text-align:center;font-family:var(--mono, ui-monospace, monospace);color:#1a1a1a;font-size:12px;box-sizing:border-box}.filler-dialog-field input:focus{z-index:1;border-color:#4c9ffe}.filler-dialog-field .filler-dialog-field-label{font-size:9px;letter-spacing:.05em;text-transform:uppercase;color:#8a8f93}.filler-dialog-sources{display:flex;align-items:center;gap:8px;border-bottom:1px solid #dadada;padding:8px}.filler-dialog-sources-group{display:flex;align-items:center;gap:3px}.filler-dialog-sources-group[hidden]{display:none}.filler-dialog-source{flex:none;display:flex;align-items:center;justify-content:center;width:24px;height:24px;padding:0;border:none;border-radius:5px;background:none;color:#8a8f93;cursor:pointer;transition:all .25s ease}.filler-dialog-source svg{display:block;fill:currentColor}.filler-dialog-source:hover{background:rgba(0,0,0,.05)}.filler-dialog-source.is-active{color:#1a1a1a;background:rgba(0,0,0,.06)}.filler-dialog-source[hidden]{display:none}.filler-dialog-close{margin-left:auto;flex:none;display:flex;align-items:center;justify-content:center;width:24px;height:24px;padding:0;border:none;border-radius:5px;background:none;font-size:15px;line-height:1;color:#8a8f93;cursor:pointer}.filler-dialog-close:hover{background:rgba(0,0,0,.06);color:#1a1a1a}.filler-dialog-solid{display:flex;flex-direction:column;gap:12px;padding:16px}.filler-dialog-solid[hidden]{display:none}.filler-dialog-image,.filler-dialog-video{display:flex;flex-direction:column;gap:10px;padding:16px}.filler-dialog-image[hidden],.filler-dialog-video[hidden]{display:none}.filler-dialog-image-toolbar,.filler-dialog-video-toolbar{display:flex;align-items:center;gap:6px}.filler-dialog-image-fit,.filler-dialog-image-rotate,.filler-dialog-video-fit,.filler-dialog-video-rotate{height:24px;border:1px solid #dadada;border-radius:5px;background-color:#fff;color:#1a1a1a;cursor:pointer}.filler-dialog-image-fit,.filler-dialog-video-fit{min-width:0;padding:4px 20px 4px 6px;outline:none;font-size:11px;margin-right:auto}.filler-dialog-image-fit:focus,.filler-dialog-video-fit:focus{border-color:#4c9ffe}.filler-dialog-image-fit,.filler-dialog-video-fit,.filler-dialog-video-setting-input:is(select){height:24px;-moz-appearance:none;appearance:none;-webkit-appearance:none;background-repeat:no-repeat;background-position:right 8px center;background-size:8px 4px;background-image:url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 10 6\'%3E%3Cpath d=\'M1 1l4 4 4-4\' fill=\'none\' stroke=\'%238a8f93\' stroke-width=\'1.5\' stroke-linecap=\'round\' stroke-linejoin=\'round\'/%3E%3C/svg%3E")}.filler-dialog-image-fit:focus,.filler-dialog-video-fit:focus,.filler-dialog-video-setting-input:is(select):focus{background-image:url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 10 6\'%3E%3Cpath d=\'M1 5l4-4 4 4\' fill=\'none\' stroke=\'%234c9ffe\' stroke-width=\'1.5\' stroke-linecap=\'round\' stroke-linejoin=\'round\'/%3E%3C/svg%3E")}.filler-dialog-image-rotate,.filler-dialog-video-rotate{flex:none;width:24px;padding:0;font-size:12px;line-height:1}.filler-dialog-image-rotate:hover,.filler-dialog-video-rotate:hover{background:rgba(0,0,0,.05)}.filler-dialog-image-upload,.filler-dialog-video-upload{position:relative;display:block;aspect-ratio:2/1;border:1px dashed #dadada;border-radius:5px;overflow:hidden;cursor:pointer;width:100%;background-image:conic-gradient(#d8d8d8 90deg, #ffffff 90deg 180deg, #d8d8d8 180deg 270deg, #ffffff 270deg);background-size:8px 8px}.filler-dialog-image-upload:hover,.filler-dialog-video-upload:hover{border-color:#4c9ffe}.filler-dialog-image-upload.has-media,.filler-dialog-video-upload.has-media{border-style:solid}.filler-dialog-image-upload-input,.filler-dialog-video-upload-input{position:absolute;inset:0;width:100%;height:100%;opacity:0;cursor:pointer}.filler-dialog-image-preview,.filler-dialog-video-preview{position:absolute;inset:0;display:flex;align-items:center;justify-content:center}.filler-dialog-image-preview-img,.filler-dialog-video-preview-video{width:100%;height:100%;pointer-events:none}.filler-dialog-image-placeholder,.filler-dialog-video-placeholder{padding:4px 8px;font-size:11px;text-align:center;color:#8a8f93;border-radius:5px;backdrop-filter:blur(2px);border:1px solid #fff;position:absolute;background-color:rgba(255,255,255,.25);box-shadow:0 0 0 100px rgba(0,0,0,.1)}.filler-dialog-image-remove,.filler-dialog-image-reset,.filler-dialog-video-remove,.filler-dialog-video-reset{position:absolute;top:4px;display:flex;align-items:center;justify-content:center;width:18px;height:18px;padding:0;border:1px solid #dadada;background:#fff;color:#8a8f93;font-size:11px;line-height:1;cursor:pointer}.filler-dialog-image-remove:hover,.filler-dialog-image-reset:hover,.filler-dialog-video-remove:hover,.filler-dialog-video-reset:hover{color:#1a1a1a}.filler-dialog-image-remove,.filler-dialog-video-remove{right:4px;border-radius:50%}.filler-dialog-image-remove[hidden],.filler-dialog-video-remove[hidden]{display:none}.filler-dialog-image-reset,.filler-dialog-video-reset{left:4px;display:none;border-radius:5px}.filler-dialog-image-upload.has-adjustments:hover .filler-dialog-image-reset,.filler-dialog-video-upload.has-adjustments:hover .filler-dialog-video-reset{display:flex}.filler-dialog-image-sliders{display:flex;flex-direction:column;gap:12px}.filler-dialog-image-sliders-title{font-size:9px;letter-spacing:.05em;text-transform:uppercase;color:#8a8f93}.filler-dialog-image-slider{display:flex;align-items:center;gap:2px}.filler-dialog-image-slider-head{display:flex;align-items:center;justify-content:space-between}.filler-dialog-image-slider-label{font-size:11px;color:#1a1a1a;text-overflow:ellipsis;white-space:nowrap;max-width:96px;overflow:hidden}.filler-dialog-image-slider-value{font-size:10px;font-family:var(--mono, ui-monospace, monospace);color:#8a8f93;margin-inline-start:4px}.filler-dialog-image-slider-input{--filler-slider-thumb: 16px;-webkit-appearance:none;-moz-appearance:none;appearance:none;width:100%;max-width:80px;height:var(--filler-slider-thumb);background:none;cursor:pointer;margin:0 0 0 auto}.filler-dialog-image-slider-input::-webkit-slider-runnable-track{height:var(--filler-slider-thumb);border-radius:9999px;outline:1px solid rgba(0,0,0,.2);outline-offset:-1px;background:linear-gradient(to right, #f5f5f5 0%, #f5f5f5 min(calc(var(--center, 0.5) * 100%), calc(var(--filler-slider-thumb, 16px) / 2 + var(--percent, 0.5) * (100% - var(--filler-slider-thumb, 16px)))), #ff6f59 min(calc(var(--center, 0.5) * 100%), calc(var(--filler-slider-thumb, 16px) / 2 + var(--percent, 0.5) * (100% - var(--filler-slider-thumb, 16px)))), #ff6f59 calc(var(--center, 0.5) * 100%), #4c9ffe calc(var(--center, 0.5) * 100%), #4c9ffe max(calc(var(--center, 0.5) * 100%), calc(var(--filler-slider-thumb, 16px) / 2 + var(--percent, 0.5) * (100% - var(--filler-slider-thumb, 16px)))), #f5f5f5 max(calc(var(--center, 0.5) * 100%), calc(var(--filler-slider-thumb, 16px) / 2 + var(--percent, 0.5) * (100% - var(--filler-slider-thumb, 16px)))), #f5f5f5 100%)}.filler-dialog-image-slider-input::-moz-range-track{height:var(--filler-slider-thumb);border-radius:9999px;outline:1px solid rgba(0,0,0,.2);outline-offset:-1px;background:linear-gradient(to right, #f5f5f5 0%, #f5f5f5 min(calc(var(--center, 0.5) * 100%), calc(var(--filler-slider-thumb, 16px) / 2 + var(--percent, 0.5) * (100% - var(--filler-slider-thumb, 16px)))), #ff6f59 min(calc(var(--center, 0.5) * 100%), calc(var(--filler-slider-thumb, 16px) / 2 + var(--percent, 0.5) * (100% - var(--filler-slider-thumb, 16px)))), #ff6f59 calc(var(--center, 0.5) * 100%), #4c9ffe calc(var(--center, 0.5) * 100%), #4c9ffe max(calc(var(--center, 0.5) * 100%), calc(var(--filler-slider-thumb, 16px) / 2 + var(--percent, 0.5) * (100% - var(--filler-slider-thumb, 16px)))), #f5f5f5 max(calc(var(--center, 0.5) * 100%), calc(var(--filler-slider-thumb, 16px) / 2 + var(--percent, 0.5) * (100% - var(--filler-slider-thumb, 16px)))), #f5f5f5 100%)}.filler-dialog-image-slider-input::-webkit-slider-thumb{-webkit-appearance:none;appearance:none;width:var(--filler-slider-thumb);height:var(--filler-slider-thumb);margin:0;border-radius:50%;border:1px solid #b1b1b1;box-shadow:0 1px 3px 0 rgba(0,0,0,.1019607843),0 5px 12px 0 rgba(0,0,0,.1294117647),0 0 .5px 0 rgba(0,0,0,.1490196078);background:#fff}.filler-dialog-image-slider-input::-moz-range-thumb{width:var(--filler-slider-thumb);height:var(--filler-slider-thumb);margin:0;border-radius:50%;border:1px solid #b1b1b1;box-shadow:0 1px 3px 0 rgba(0,0,0,.1019607843),0 5px 12px 0 rgba(0,0,0,.1294117647),0 0 .5px 0 rgba(0,0,0,.1490196078);background:#fff}.filler-dialog-image-slider-input:focus-visible::-webkit-slider-thumb{outline:2px solid #4c9ffe;outline-offset:2px}.filler-dialog-image-slider-input:focus-visible::-moz-range-thumb{outline:2px solid #4c9ffe;outline-offset:2px}.filler-dialog-image-slider-input:disabled{opacity:.5;cursor:default}.filler-dialog-video-settings{display:flex;flex-direction:column;gap:12px}.filler-dialog-video-settings-title{font-size:9px;letter-spacing:.05em;text-transform:uppercase;color:#8a8f93}.filler-dialog-video-setting{display:flex;align-items:center;justify-content:space-between;gap:8px;cursor:pointer}.filler-dialog-video-setting-label{font-size:11px;color:#1a1a1a;text-overflow:ellipsis;white-space:nowrap;overflow:hidden}.filler-dialog-video-setting-input{flex:none}.filler-dialog-video-setting-input[type=checkbox]{-moz-appearance:none;appearance:none;-webkit-appearance:none;width:30px;height:17px;margin:0;border:none;border-radius:9999px;background-color:#f5f5f5;background-image:url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 13 13\'%3E%3Ccircle cx=\'6.5\' cy=\'6.5\' r=\'6.5\' fill=\'white\'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:left 2px center;background-size:13px 13px;cursor:pointer;transition:background-color .15s ease,background-position .15s ease;outline:1px solid rgba(0,0,0,.2);outline-offset:-1px}.filler-dialog-video-setting-input[type=checkbox]:checked{background-color:#4c9ffe;background-position:right 2px center}.filler-dialog-video-setting-input[type=checkbox]:focus-visible{outline:2px solid #4c9ffe;outline-offset:2px}.filler-dialog-video-setting-input:is(select){padding:4px 20px 4px 6px;border:1px solid #dadada;border-radius:5px;background-color:#fff;outline:none;font-size:11px;color:#1a1a1a;cursor:pointer;field-sizing:content}.filler-dialog-video-setting-input:is(select):focus{border-color:#4c9ffe}';
    const PALETTE = [ {
        name: 'Black',
        hex: '#000000'
    }, {
        name: 'Blue',
        hex: '#2196F3'
    }, {
        name: 'Gray',
        hex: '#9E9E9E'
    }, {
        name: 'Green',
        hex: '#4CAF50'
    }, {
        name: 'Indigo',
        hex: '#3F51B5'
    }, {
        name: 'Orange',
        hex: '#FF9800'
    }, {
        name: 'Pink',
        hex: '#E91E63'
    }, {
        name: 'Purple',
        hex: '#9C27B0'
    }, {
        name: 'Red',
        hex: '#F44336'
    }, {
        name: 'Teal',
        hex: '#009688'
    }, {
        name: 'White',
        hex: '#FFFFFF'
    }, {
        name: 'Yellow',
        hex: '#FFEB3B'
    } ];
    const FILLER_PALETTE = null && 0;
    const classNames = (prefix, suffixes) => Object.fromEntries(Object.entries(suffixes).map(([key, suffix]) => [ key, suffix ? `${prefix}-${suffix}` : prefix ]));
    class Filler {
        static DEFAULTS={
            classes: {
                ...classNames('filler', {
                    container: '',
                    input: 'input',
                    alpha: 'alpha',
                    alphaInput: 'alpha-input',
                    alphaSuffix: 'alpha-suffix',
                    swatch: 'swatch',
                    swatchColor: 'swatch-color',
                    swatchColorOpaque: 'swatch-color-opaque',
                    swatchVideo: 'swatch-video',
                    dropdown: 'dropdown'
                }),
                ...classNames('filler-palette', {
                    paletteSection: 'section',
                    paletteTitle: 'title',
                    paletteRow: 'row',
                    paletteSwatch: 'swatch',
                    paletteChip: 'chip',
                    paletteLabel: 'label',
                    paletteHex: 'hex',
                    paletteRemove: 'remove',
                    paletteAdd: 'add'
                }),
                ...classNames('filler-dialog', {
                    dialog: '',
                    dialogArea: 'area',
                    dialogAreaHandle: 'area-handle',
                    dialogHue: 'hue',
                    dialogAlpha: 'alpha',
                    dialogAlphaGradient: 'alpha-gradient',
                    dialogHandle: 'handle',
                    dialogTabs: 'tabs',
                    dialogTab: 'tab',
                    dialogEyedropper: 'eyedropper',
                    dialogCopy: 'copy',
                    dialogFields: 'fields',
                    dialogFieldsValues: 'fields-values',
                    dialogField: 'field',
                    dialogFieldLabel: 'field-label',
                    dialogSources: 'sources',
                    dialogSourcesGroup: 'sources-group',
                    dialogSource: 'source',
                    dialogClose: 'close',
                    dialogSolid: 'solid'
                }),
                ...classNames('filler-dialog-image', {
                    dialogImage: '',
                    dialogImageToolbar: 'toolbar',
                    dialogImageFit: 'fit',
                    dialogImageRotate: 'rotate',
                    dialogImageUpload: 'upload',
                    dialogImageUploadInput: 'upload-input',
                    dialogImagePreview: 'preview',
                    dialogImagePreviewImg: 'preview-img',
                    dialogImagePlaceholder: 'placeholder',
                    dialogImageRemove: 'remove',
                    dialogImageReset: 'reset',
                    dialogImageSlidersTitle: 'sliders-title',
                    dialogImageSliders: 'sliders',
                    dialogImageSlider: 'slider',
                    dialogImageSliderHead: 'slider-head',
                    dialogImageSliderLabel: 'slider-label',
                    dialogImageSliderValue: 'slider-value',
                    dialogImageSliderInput: 'slider-input'
                }),
                ...classNames('filler-dialog-video', {
                    dialogVideo: '',
                    dialogVideoToolbar: 'toolbar',
                    dialogVideoFit: 'fit',
                    dialogVideoRotate: 'rotate',
                    dialogVideoUpload: 'upload',
                    dialogVideoUploadInput: 'upload-input',
                    dialogVideoPreview: 'preview',
                    dialogVideoPreviewVideo: 'preview-video',
                    dialogVideoPlaceholder: 'placeholder',
                    dialogVideoRemove: 'remove',
                    dialogVideoReset: 'reset',
                    dialogVideoSettingsTitle: 'settings-title',
                    dialogVideoSettings: 'settings',
                    dialogVideoSetting: 'setting',
                    dialogVideoSettingLabel: 'setting-label',
                    dialogVideoSettingInput: 'setting-input'
                })
            },
            format: 'hex',
            alpha: null,
            palette: PALETTE,
            sources: [ 'solid', 'image', 'video' ],
            customPaletteKey: 'youla-filler-palette',
            disabled: false,
            suffixText: '%',
            onChange: null,
            onSourceChange: null,
            onMediaChange: null,
            labels: {
                customPaletteTitle: 'Свой набор',
                addCurrentColor: 'Добавить текущий цвет',
                libraryTitle: 'Библиотека',
                copyValue: 'Скопировать значение',
                pickColor: 'Пипетка с экрана',
                eyedropper: 'Alt+клик — пипетка с экрана',
                closeDialog: 'Закрыть',
                solidSource: 'Заливка',
                imageSource: 'Изображение',
                uploadImage: 'Выбрать изображение',
                removeImage: 'Удалить изображение',
                videoSource: 'Видео',
                uploadVideo: 'Выбрать видео',
                removeVideo: 'Удалить видео',
                rotateImage: 'Повернуть на 90°',
                adjustments: 'Коррекция',
                resetAdjustments: 'Сбросить',
                objectFit: {
                    cover: 'Заполнение',
                    contain: 'Вписать',
                    fill: 'Растянуть',
                    none: 'Без изменений',
                    'scale-down': 'Уменьшение'
                },
                filters: {
                    brightness: 'Яркость',
                    contrast: 'Контраст',
                    saturate: 'Насыщенность',
                    grayscale: 'Оттенки серого',
                    sepia: 'Сепия',
                    hueRotate: 'Поворот тона',
                    invert: 'Инверсия',
                    blur: 'Размытие'
                },
                videoSettingsTitle: 'Настройки видео',
                videoSettings: {
                    autoplay: 'Автовоспроизведение',
                    loop: 'Зациклить',
                    muted: 'Без звука',
                    playsInline: 'Воспроизведение в блоке (playsinline)',
                    controls: 'Элементы управления',
                    preload: 'Предзагрузка'
                },
                videoPreload: {
                    none: 'Не загружать',
                    metadata: 'Только метаданные',
                    auto: 'Автоматически'
                }
            }
        };
        static SOURCE_ICONS={
            solid: '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="none" viewBox="0 0 12 12"><path fill="#ababab" d="M3 3h6v6H3z"/><path fill="#000" fill-rule="evenodd" d="M2 1h8a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1M0 2a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2zm3 7V3h6v6zM2 2.5a1 1 0 0 1 .5-.5h7a1 1 0 0 1 .5.5v7a1 1 0 0 1-.5.5h-7a1 1 0 0 1-.5-.5z" clip-rule="evenodd"/></svg>',
            image: '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="none" viewBox="0 0 12 12"><path fill="#000" d="M10 0a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V2a2 2 0 0 1 2-2zM2 1a1 1 0 0 0-1 1v8a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1zm2.22 4.08a.5.5 0 0 1 .63.07l4 4a.5.5 0 1 1-.7.7L4.5 6.21 2.85 7.85a.5.5 0 1 1-.7-.7l2-2zM8.5 2a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3m0 1a.5.5 0 1 0 0 1 .5.5 0 0 0 0-1"/></svg>',
            video: '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="none" viewBox="0 0 12 12"><path fill="#000" d="M10 0a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V2a2 2 0 0 1 2-2zM2 1a1 1 0 0 0-1 1v8a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1z"/><path stroke="#000" d="M4.1 4.3q.1-.6.8-.5l3 1.8q.5.4 0 .8l-3 1.8q-.7.3-.8-.5z"/></svg>'
        };
        static MEDIA={
            image: {
                tag: 'img',
                accept: 'image/*',
                classKeys: {
                    panel: 'dialogImage',
                    toolbar: 'dialogImageToolbar',
                    fit: 'dialogImageFit',
                    rotate: 'dialogImageRotate',
                    upload: 'dialogImageUpload',
                    uploadInput: 'dialogImageUploadInput',
                    preview: 'dialogImagePreview',
                    previewMedia: 'dialogImagePreviewImg',
                    placeholder: 'dialogImagePlaceholder',
                    remove: 'dialogImageRemove',
                    reset: 'dialogImageReset',
                    slidersTitle: 'dialogImageSlidersTitle',
                    sliders: 'dialogImageSliders',
                    slider: 'dialogImageSlider',
                    sliderHead: 'dialogImageSliderHead',
                    sliderLabel: 'dialogImageSliderLabel',
                    sliderValue: 'dialogImageSliderValue',
                    sliderInput: 'dialogImageSliderInput'
                },
                labelKeys: {
                    source: 'imageSource',
                    upload: 'uploadImage',
                    remove: 'removeImage',
                    rotate: 'rotateImage'
                }
            },
            video: {
                tag: 'video',
                accept: 'video/*',
                classKeys: {
                    panel: 'dialogVideo',
                    toolbar: 'dialogVideoToolbar',
                    fit: 'dialogVideoFit',
                    rotate: 'dialogVideoRotate',
                    upload: 'dialogVideoUpload',
                    uploadInput: 'dialogVideoUploadInput',
                    preview: 'dialogVideoPreview',
                    previewMedia: 'dialogVideoPreviewVideo',
                    placeholder: 'dialogVideoPlaceholder',
                    remove: 'dialogVideoRemove',
                    reset: 'dialogVideoReset',
                    settingsTitle: 'dialogVideoSettingsTitle',
                    settings: 'dialogVideoSettings',
                    setting: 'dialogVideoSetting',
                    settingLabel: 'dialogVideoSettingLabel',
                    settingInput: 'dialogVideoSettingInput'
                },
                labelKeys: {
                    source: 'videoSource',
                    upload: 'uploadVideo',
                    remove: 'removeVideo',
                    rotate: 'rotateImage'
                }
            }
        };
        static VIDEO_SETTINGS=[ {
            key: 'autoplay',
            type: 'checkbox',
            default: true
        }, {
            key: 'loop',
            type: 'checkbox',
            default: true
        }, {
            key: 'muted',
            type: 'checkbox',
            default: true
        }, {
            key: 'playsInline',
            type: 'checkbox',
            default: true
        }, {
            key: 'controls',
            type: 'checkbox',
            default: false
        }, {
            key: 'preload',
            type: 'select',
            default: 'auto',
            options: [ 'none', 'metadata', 'auto' ]
        } ];
        static EYEDROPPER_ICON='<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="none" viewBox="0 0 12 12"><path fill="#000" d="M12 2.2a2.2 2.2 0 0 0-.7-1.6C10.42-.23 9-.2 8.13.67L6.93 1.9a1.5 1.5 0 0 0-2.08.05l-.56.56a1 1 0 0 0 0 1.41l.13.13-3.19 3.19A2.5 2.5 0 0 0 .57 9.6l-.5 1.16a.9.9 0 0 0 .18.95 1 1 0 0 0 1.1.2l1.1-.47a2.5 2.5 0 0 0 2.32-.67l3.19-3.2.12.14a1 1 0 0 0 1.42 0l.56-.57a1.5 1.5 0 0 0 .05-2.07l1.23-1.24A2.2 2.2 0 0 0 12 2.2m-7.94 7.86a1.5 1.5 0 0 1-1.5.38.5.5 0 0 0-.34.02l-1.13.5.47-1.12a.5.5 0 0 0 .02-.36 1.5 1.5 0 0 1 .36-1.54l3.19-3.19 2.12 2.13zm6.57-6.93L9.05 4.72a.5.5 0 0 0 0 .7l.3.31a.5.5 0 0 1 0 .7L8.8 7 5 3.2l.56-.56a.5.5 0 0 1 .71 0l.3.3a.5.5 0 0 0 .71 0l1.56-1.56a1.3 1.3 0 0 1 1.77-.05 1.25 1.25 0 0 1 .02 1.8"/></svg>';
        static PANEL_MIN_WIDTH=200;
        static PANEL_MAX_WIDTH=280;
        static MEDIA_FILTERS=[ {
            key: 'brightness',
            css: 'brightness',
            min: 0,
            max: 200,
            default: 100,
            step: 1,
            unit: '%'
        }, {
            key: 'contrast',
            css: 'contrast',
            min: 0,
            max: 200,
            default: 100,
            step: 1,
            unit: '%'
        }, {
            key: 'saturate',
            css: 'saturate',
            min: 0,
            max: 200,
            default: 100,
            step: 1,
            unit: '%'
        }, {
            key: 'hueRotate',
            css: 'hue-rotate',
            min: -180,
            max: 180,
            default: 0,
            step: 1,
            unit: 'deg'
        }, {
            key: 'grayscale',
            css: 'grayscale',
            min: 0,
            max: 100,
            default: 0,
            step: 1,
            unit: '%'
        }, {
            key: 'sepia',
            css: 'sepia',
            min: 0,
            max: 100,
            default: 0,
            step: 1,
            unit: '%'
        }, {
            key: 'invert',
            css: 'invert',
            min: 0,
            max: 100,
            default: 0,
            step: 1,
            unit: '%'
        }, {
            key: 'blur',
            css: 'blur',
            min: 0,
            max: 20,
            default: 0,
            step: 1,
            unit: 'px'
        } ];
        static filterDisplayUnit(key) {
            const unit = Filler.MEDIA_FILTERS.find(f => f.key === key).unit;
            return unit === 'deg' ? '°' : unit;
        }
        static computeMediaFilter(media) {
            return Filler.MEDIA_FILTERS.map(({key, css, unit}) => `${css}(${media[key]}${unit})`).join(' ');
        }
        static clamp(value, min, max) {
            return Math.min(max, Math.max(min, value));
        }
        static el(tag, {part, ...props} = {}) {
            const el = Object.assign(document.createElement(tag), props);
            if (part !== false && props.className) {
                el.setAttribute('part', props.className);
            }
            return el;
        }
        static getPanelStylesheet() {
            if (!Filler._panelStylesheet) {
                const sheet = new CSSStyleSheet;
                sheet.replaceSync(filler_panelinline_namespaceObject);
                Filler._panelStylesheet = sheet;
            }
            return Filler._panelStylesheet;
        }
        static normalizeHex(hex) {
            if (typeof hex !== 'string') {
                return null;
            }
            let value = hex.trim().replace(/^#/, '');
            if (value.length === 3) {
                value = value.split('').map(c => c + c).join('');
            }
            return /^[0-9a-f]{6}$/i.test(value) ? `#${value.toUpperCase()}` : null;
        }
        static hexToRgb(hex) {
            const normalized = Filler.normalizeHex(hex);
            if (!normalized) {
                return null;
            }
            const n = parseInt(normalized.slice(1), 16);
            return {
                r: n >> 16 & 255,
                g: n >> 8 & 255,
                b: n & 255
            };
        }
        static hexToRgba(hex) {
            const value = typeof hex === 'string' ? hex.trim().replace(/^#/, '') : '';
            if (!/^[0-9a-f]{8}$/i.test(value)) {
                const rgb = Filler.hexToRgb(hex);
                return rgb ? {
                    ...rgb,
                    a: null
                } : null;
            }
            const n = parseInt(value, 16);
            return {
                r: n >>> 24 & 255,
                g: n >>> 16 & 255,
                b: n >>> 8 & 255,
                a: Math.round((n & 255) / 255 * 100)
            };
        }
        static rgbToHex({r, g, b}) {
            return `#${[ r, g, b ].map(v => Filler.clamp(Math.round(v), 0, 255).toString(16).padStart(2, '0')).join('').toUpperCase()}`;
        }
        static parseCssColor(value) {
            if (typeof value !== 'string' || !value.trim()) {
                return null;
            }
            const probe = Filler.el('span', {
                style: 'display: none'
            });
            probe.style.color = value.trim();
            if (!probe.style.color) {
                return null;
            }
            document.body.appendChild(probe);
            const computed = getComputedStyle(probe).color;
            probe.remove();
            const match = computed.match(/^rgba?\(([^)]+)\)$/);
            if (!match) {
                return null;
            }
            const [r, g, b, a = 1] = match[1].split(',').map(n => parseFloat(n));
            if ([ r, g, b ].some(Number.isNaN)) {
                return null;
            }
            return {
                r,
                g,
                b,
                a: Filler.clamp(a, 0, 1) * 100
            };
        }
        static extractMediaFile(dataTransfer, mimePrefix) {
            const fromFiles = [ ...dataTransfer.files || [] ].find(f => f.type.startsWith(mimePrefix));
            if (fromFiles) {
                return fromFiles;
            }
            const item = [ ...dataTransfer.items || [] ].find(i => i.kind === 'file' && i.type.startsWith(mimePrefix));
            return item ? item.getAsFile() : null;
        }
        static isNearWhite({r, g, b}) {
            return r >= 235 && g >= 235 && b >= 235;
        }
        static sanitizeDigits(value, min, max) {
            const digits = value.replace(/[^0-9]/g, '');
            return digits === '' ? '' : String(Filler.clamp(+digits, min, max));
        }
        static hueToChannels(h, c, x) {
            return h < 60 ? [ c, x, 0 ] : h < 120 ? [ x, c, 0 ] : h < 180 ? [ 0, c, x ] : h < 240 ? [ 0, x, c ] : h < 300 ? [ x, 0, c ] : [ c, 0, x ];
        }
        static rgbToHue({r, g, b}, max, d) {
            if (d === 0) {
                return 0;
            }
            const h = max === r ? 60 * ((g - b) / d % 6) : max === g ? 60 * ((b - r) / d + 2) : 60 * ((r - g) / d + 4);
            return h < 0 ? h + 360 : h;
        }
        static rgbToHsv({r, g, b}) {
            r /= 255;
            g /= 255;
            b /= 255;
            const max = Math.max(r, g, b);
            const d = max - Math.min(r, g, b);
            return {
                h: Filler.rgbToHue({
                    r,
                    g,
                    b
                }, max, d),
                s: max === 0 ? 0 : d / max * 100,
                v: max * 100
            };
        }
        static hsvToRgb({h, s, v}) {
            s /= 100;
            v /= 100;
            const c = v * s;
            const x = c * (1 - Math.abs(h / 60 % 2 - 1));
            const m = v - c;
            const [r, g, b] = Filler.hueToChannels(h, c, x);
            return {
                r: (r + m) * 255,
                g: (g + m) * 255,
                b: (b + m) * 255
            };
        }
        static rgbToHsl({r, g, b}) {
            r /= 255;
            g /= 255;
            b /= 255;
            const max = Math.max(r, g, b);
            const min = Math.min(r, g, b);
            const d = max - min;
            const l = (max + min) / 2;
            const s = d === 0 ? 0 : d / (1 - Math.abs(2 * l - 1));
            return {
                h: Filler.rgbToHue({
                    r,
                    g,
                    b
                }, max, d),
                s: s * 100,
                l: l * 100
            };
        }
        static hslToRgb({h, s, l}) {
            s /= 100;
            l /= 100;
            const c = (1 - Math.abs(2 * l - 1)) * s;
            const x = c * (1 - Math.abs(h / 60 % 2 - 1));
            const m = l - c / 2;
            const [r, g, b] = Filler.hueToChannels(h, c, x);
            return {
                r: (r + m) * 255,
                g: (g + m) * 255,
                b: (b + m) * 255
            };
        }
        static availableSpace(anchorRect, viewport, offset = 6) {
            const space = {
                right: viewport.width - anchorRect.right - offset,
                left: anchorRect.left - offset,
                bottom: viewport.height - anchorRect.bottom - offset,
                top: anchorRect.top - offset
            };
            if (space.right >= Filler.PANEL_MIN_WIDTH) {
                return {
                    side: 'right',
                    maxSize: space.right
                };
            }
            if (space.left >= Filler.PANEL_MIN_WIDTH) {
                return {
                    side: 'left',
                    maxSize: space.left
                };
            }
            const side = space.bottom >= space.top ? 'bottom' : 'top';
            return {
                side,
                maxSize: Math.max(space[side], 0)
            };
        }
        static computePosition(anchorRect, size, viewport, offset = 6) {
            const {side} = Filler.availableSpace(anchorRect, viewport, offset);
            const position = {
                bottom: {
                    top: anchorRect.bottom + offset,
                    left: anchorRect.left
                },
                top: {
                    top: anchorRect.top - size.height - offset,
                    left: anchorRect.left
                },
                right: {
                    top: anchorRect.top,
                    left: anchorRect.right + offset
                },
                left: {
                    top: anchorRect.top,
                    left: anchorRect.left - size.width - offset
                }
            }[side];
            return {
                top: Filler.clamp(position.top, 4, Math.max(viewport.height - size.height - 4, 4)),
                left: Filler.clamp(position.left, 4, Math.max(viewport.width - size.width - 4, 4))
            };
        }
        constructor(target, options = {}) {
            const el = this.el = typeof target === 'string' ? document.querySelector(target) : target;
            if (!(el instanceof HTMLInputElement)) {
                throw new Error(`Filler: no input element found for "${target}"`);
            }
            Object.assign(this, Filler.DEFAULTS, options, {
                classes: {
                    ...Filler.DEFAULTS.classes,
                    ...options.classes
                },
                labels: {
                    ...Filler.DEFAULTS.labels,
                    ...options.labels
                },
                palette: options.palette ? [ ...options.palette ] : [ ...Filler.DEFAULTS.palette ],
                sources: options.sources?.length ? [ ...options.sources ] : [ ...Filler.DEFAULTS.sources ]
            });
            this.disabled = options.disabled ?? el.disabled;
            const initialHex = Filler.normalizeHex(el.value) || '#000000';
            const initialAlpha = Filler.clamp(options.alpha ?? parseFloat(el.dataset.alpha ?? '100'), 0, 100);
            this.hsva = {
                ...Filler.rgbToHsv(Filler.hexToRgb(initialHex)),
                a: initialAlpha
            };
            this.source = options.source && this.sources.includes(options.source) ? options.source : this.sources[0];
            const imageFilterDefaults = Object.fromEntries(Filler.MEDIA_FILTERS.map(({key, default: value}) => [ key, value ]));
            const videoSettingDefaults = Object.fromEntries(Filler.VIDEO_SETTINGS.map(({key, default: value}) => [ key, value ]));
            this.image = {
                dataUrl: null,
                fit: 'cover',
                rotation: 0,
                ...imageFilterDefaults,
                ...options.image
            };
            this.video = {
                dataUrl: null,
                fit: 'cover',
                rotation: 0,
                ...videoSettingDefaults,
                ...options.video
            };
            this.mediaRefs = {
                image: null,
                video: null
            };
            this.customPalette = this.loadCustomPalette();
            this.dropdownOpen = false;
            this.dialogOpen = false;
            this.draggingAlpha = false;
            this.initialize();
        }
        get hex() {
            return Filler.rgbToHex(Filler.hsvToRgb(this.hsva));
        }
        initialize() {
            const {el, classes} = this;
            el.classList.add(classes.input);
            Object.assign(el, {
                type: 'text',
                autocomplete: 'off',
                spellcheck: false,
                maxLength: 9
            });
            const wrapper = this.wrapper = Filler.el('div', {
                className: classes.container
            });
            el.parentNode.insertBefore(wrapper, el);
            const swatchColor = this.swatchColor = Filler.el('span', {
                className: classes.swatchColor
            });
            const swatchColorOpaque = this.swatchColorOpaque = Filler.el('span', {
                className: classes.swatchColorOpaque
            });
            const swatchVideo = this.swatchVideo = Filler.el('video', {
                className: classes.swatchVideo,
                hidden: true
            });
            const swatch = this.swatch = Filler.el('button', {
                type: 'button',
                className: classes.swatch
            });
            swatch.append(swatchColor, swatchColorOpaque, swatchVideo);
            this.syncSwatchTitle();
            const alphaInput = this.alphaInput = Filler.el('input', {
                type: 'text',
                inputMode: 'numeric',
                maxLength: 3,
                className: classes.alphaInput
            });
            const suffix = this.suffix = Filler.el('span', {
                className: classes.alphaSuffix,
                textContent: this.suffixText
            });
            Object.assign(suffix.style, {
                cursor: 'ew-resize',
                touchAction: 'none'
            });
            const alphaWrapper = this.alphaWrapper = Filler.el('label', {
                className: classes.alpha
            });
            alphaWrapper.append(alphaInput, suffix);
            wrapper.append(swatch, el, alphaWrapper);
            if (this.disabled) {
                [ el, alphaInput, swatch ].forEach(e => {
                    e.disabled = true;
                });
                wrapper.classList.add('is-disabled');
            }
            this.addListeners();
            this.render();
        }
        addListeners() {
            const {el, alphaInput} = this;
            el.addEventListener('focus', () => {
                if (this.source !== 'solid') {
                    const wasOpen = this.dialogOpen;
                    this.openDialog();
                    if (!wasOpen && !this[this.source]?.dataUrl) {
                        this.mediaRefs[this.source]?.uploadInput?.click();
                    }
                    return;
                }
                el.select();
                this.openDropdown();
            });
            el.addEventListener('input', () => this.handleHexInput());
            el.addEventListener('blur', () => this.renderSwatch());
            el.addEventListener('paste', event => this.handleHexPaste(event));
            alphaInput.addEventListener('input', () => {
                const value = Filler.sanitizeDigits(alphaInput.value, 0, 100);
                if (value !== alphaInput.value) {
                    alphaInput.value = value;
                }
                if (value !== '') {
                    this.setAlpha(+value);
                }
            });
            alphaInput.addEventListener('blur', () => {
                alphaInput.value = Math.round(this.hsva.a);
            });
            this.bindAlphaSuffixDrag();
            const {swatch} = this;
            swatch.addEventListener('click', event => {
                if (event.altKey && window.EyeDropper) {
                    this.pickWithEyeDropper();
                    return;
                }
                this.toggleDialog();
            });
            swatch.addEventListener('paste', event => this.handleSwatchMediaData(event.clipboardData));
            swatch.addEventListener('dragover', event => {
                if (!this.disabled) {
                    event.preventDefault();
                }
            });
            swatch.addEventListener('drop', event => {
                event.preventDefault();
                this.handleSwatchMediaData(event.dataTransfer);
            });
        }
        pickWithEyeDropper() {
            if (this.disabled || !window.EyeDropper || !this.sources.includes('solid')) {
                return;
            }
            (new window.EyeDropper).open().then(({sRGBHex}) => {
                const hex = Filler.normalizeHex(sRGBHex);
                if (hex) {
                    this.setSource('solid');
                    this.applyHex(hex);
                }
            }).catch(() => {});
        }
        syncSwatchTitle() {
            this.swatch.title = window.EyeDropper && this.sources.includes('solid') ? this.labels.eyedropper : '';
        }
        handleSwatchMediaData(dataTransfer) {
            if (this.disabled || !dataTransfer) {
                return;
            }
            const imageFile = this.sources.includes('image') ? Filler.extractMediaFile(dataTransfer, 'image/') : null;
            const videoFile = !imageFile && this.sources.includes('video') ? Filler.extractMediaFile(dataTransfer, 'video/') : null;
            const file = imageFile || videoFile;
            if (!file) {
                return;
            }
            const type = imageFile ? 'image' : 'video';
            this.setSource(type);
            this.openDialog();
            this.handleMediaUpload(type, file);
        }
        bindAlphaSuffixDrag() {
            const {suffix, alphaInput} = this;
            suffix.addEventListener('pointerdown', event => {
                if (this.disabled) {
                    return;
                }
                event.preventDefault();
                let lastX = event.clientX;
                suffix.setPointerCapture(event.pointerId);
                this.draggingAlpha = true;
                const onMove = moveEvent => {
                    this.setAlpha(this.hsva.a + (moveEvent.clientX - lastX) / 2);
                    lastX = moveEvent.clientX;
                };
                const onUp = () => {
                    this.draggingAlpha = false;
                    alphaInput.value = Math.round(this.hsva.a);
                    suffix.removeEventListener('pointermove', onMove);
                    suffix.removeEventListener('pointerup', onUp);
                    suffix.removeEventListener('pointercancel', onUp);
                };
                suffix.addEventListener('pointermove', onMove);
                suffix.addEventListener('pointerup', onUp);
                suffix.addEventListener('pointercancel', onUp);
            });
        }
        handleHexInput() {
            const color = Filler.hexToRgba(this.el.value);
            if (!color) {
                return;
            }
            this.hsva = {
                ...Filler.rgbToHsv(color),
                a: color.a ?? this.hsva.a
            };
            this.render({
                skipHexInput: true
            });
        }
        handleHexPaste(event) {
            if (this.source !== 'solid') {
                return;
            }
            const color = Filler.parseCssColor(event.clipboardData?.getData('text'));
            if (!color) {
                return;
            }
            event.preventDefault();
            this.hsva = {
                ...Filler.rgbToHsv(color),
                a: color.a
            };
            this.render();
        }
        setAlpha(value) {
            this.hsva.a = Filler.clamp(value, 0, 100);
            this.render();
        }
        applyHex(hex) {
            const rgb = Filler.hexToRgb(hex);
            if (!rgb) {
                return;
            }
            this.hsva = {
                ...Filler.rgbToHsv(rgb),
                a: this.hsva.a
            };
            this.render();
            this.closeDropdown();
        }
        toggleDialog() {
            if (this.disabled) {
                return;
            }
            this.dialogOpen ? this.closeDialog() : this.openDialog();
        }
        render({skipHexInput = false} = {}) {
            const {el, hsva, alphaInput} = this;
            const hex = this.hex;
            const alphaRounded = Math.round(hsva.a);
            if (this.draggingAlpha || document.activeElement !== alphaInput) {
                alphaInput.value = alphaRounded;
            }
            this.renderSwatch({
                skipHexInput
            });
            if (this.dialogOpen) {
                this.renderDialog();
            }
            this.onChange?.(hex, alphaRounded);
            el.dispatchEvent(new Event('change', {
                bubbles: true
            }));
        }
        renderSwatch({skipHexInput = false} = {}) {
            const {el, hsva, swatch, swatchColor, swatchColorOpaque, swatchVideo, labels} = this;
            const isImage = this.source === 'image';
            const isVideo = this.source === 'video';
            swatch.classList.toggle('is-image', isImage);
            swatch.classList.toggle('is-video', isVideo);
            el.classList.toggle('is-image-value', isImage || isVideo);
            el.readOnly = isImage || isVideo;
            if (isVideo) {
                const {dataUrl, fit, rotation} = this.video;
                if (!skipHexInput) {
                    el.value = labels.videoSource;
                }
                swatchColor.style.backgroundColor = '';
                swatchColor.style.backgroundImage = '';
                if (dataUrl) {
                    if (swatchVideo.getAttribute('src') !== dataUrl) {
                        swatchVideo.src = dataUrl;
                    }
                } else {
                    swatchVideo.removeAttribute('src');
                }
                swatchVideo.hidden = !dataUrl;
                swatchVideo.style.objectFit = fit;
                swatchVideo.style.transform = `rotate(${rotation}deg)`;
                swatchVideo.style.opacity = hsva.a / 100;
                swatch.classList.remove('has-alpha');
                swatch.style.border = 'none';
                this.applyVideoSettings('video');
                return;
            }
            swatchVideo.hidden = true;
            if (!swatchVideo.paused) {
                swatchVideo.pause();
            }
            if (isImage) {
                const {dataUrl, rotation} = this.image;
                if (!skipHexInput) {
                    el.value = labels.imageSource;
                }
                swatchColor.style.backgroundColor = '';
                swatchColor.style.backgroundImage = dataUrl ? `url("${dataUrl}")` : '';
                swatchColor.style.transform = dataUrl ? `rotate(${rotation}deg)` : '';
                swatchColor.style.opacity = hsva.a / 100;
                swatch.classList.remove('has-alpha');
                swatch.style.border = 'none';
                this.applyImageFilter();
                return;
            }
            if (!skipHexInput) {
                el.value = this.hex;
            }
            const rgb = Filler.hsvToRgb(hsva);
            const rgbTriplet = `${Math.round(rgb.r)}, ${Math.round(rgb.g)}, ${Math.round(rgb.b)}`;
            const hasAlpha = hsva.a < 100;
            swatchColor.style.backgroundImage = '';
            swatchColor.style.transform = '';
            swatchColor.style.opacity = '';
            swatchColor.style.filter = '';
            swatchColor.style.backgroundColor = `rgba(${rgbTriplet}, ${hsva.a / 100})`;
            swatchColorOpaque.style.backgroundColor = `rgb(${rgbTriplet})`;
            swatch.classList.toggle('has-alpha', hasAlpha);
            swatch.style.border = Filler.isNearWhite(rgb) ? '1px solid #dfe2e3' : 'none';
        }
        getFormattedValue() {
            const {a} = this.hsva;
            const rgb = Filler.hsvToRgb(this.hsva);
            const r = Math.round(rgb.r);
            const g = Math.round(rgb.g);
            const b = Math.round(rgb.b);
            const opaque = a >= 100;
            const alpha = Math.round(a / 100 * 100) / 100;
            if (this.format === 'rgb') {
                return opaque ? `rgb(${r}, ${g}, ${b})` : `rgba(${r}, ${g}, ${b}, ${alpha})`;
            }
            if (this.format === 'hsl') {
                const hsl = Filler.rgbToHsl(rgb);
                const h = Math.round(hsl.h);
                const s = Math.round(hsl.s);
                const l = Math.round(hsl.l);
                return opaque ? `hsl(${h}, ${s}%, ${l}%)` : `hsla(${h}, ${s}%, ${l}%, ${alpha})`;
            }
            if (opaque) {
                return this.hex;
            }
            return this.hex + Math.round(a / 100 * 255).toString(16).padStart(2, '0').toUpperCase();
        }
        copyValue(button) {
            navigator.clipboard.writeText(this.getFormattedValue()).then(() => {
                button.classList.add('is-copied');
                clearTimeout(this.copyResetTimer);
                this.copyResetTimer = setTimeout(() => button.classList.remove('is-copied'), 1200);
            });
        }
        createShadowPanel(className) {
            const host = Filler.el('div');
            const shadow = host.attachShadow({
                mode: 'open'
            });
            if ('adoptedStyleSheets' in shadow) {
                shadow.adoptedStyleSheets = [ Filler.getPanelStylesheet() ];
            } else {
                shadow.appendChild(Filler.el('style', {
                    textContent: filler_panelinline_namespaceObject
                }));
            }
            const root = Filler.el('div', {
                className
            });
            shadow.appendChild(root);
            return {
                host,
                root
            };
        }
        attachFloating(panel, content, onClose) {
            const {wrapper} = this;
            Object.assign(panel.style, {
                position: 'fixed',
                zIndex: 999999,
                top: 0,
                left: 0
            });
            document.body.appendChild(panel);
            panel.style.visibility = 'hidden';
            const fieldWidth = wrapper.getBoundingClientRect().width;
            panel.style.width = `${Filler.clamp(fieldWidth, Filler.PANEL_MIN_WIDTH, Filler.PANEL_MAX_WIDTH)}px`;
            const reposition = () => {
                const anchorRect = wrapper.getBoundingClientRect();
                const viewport = {
                    width: window.innerWidth,
                    height: window.innerHeight
                };
                const {side, maxSize} = Filler.availableSpace(anchorRect, viewport);
                const stacked = side === 'top' || side === 'bottom';
                content.style.maxHeight = `${stacked ? maxSize : viewport.height - 8}px`;
                content.style.overflowY = 'auto';
                panel.style.maxWidth = stacked ? '' : `${maxSize}px`;
                const size = {
                    width: panel.offsetWidth,
                    height: panel.offsetHeight
                };
                const {top, left} = Filler.computePosition(anchorRect, size, viewport);
                panel.style.top = `${top}px`;
                panel.style.left = `${left}px`;
            };
            reposition();
            panel.style.visibility = 'visible';
            const onDocClick = event => {
                if (wrapper.contains(event.target) || panel.contains(event.target)) {
                    return;
                }
                onClose();
            };
            const onKeydown = event => event.key === 'Escape' && onClose();
            const addClickListenerTimer = setTimeout(() => document.addEventListener('click', onDocClick, true), 0);
            document.addEventListener('keydown', onKeydown);
            window.addEventListener('scroll', reposition, true);
            window.addEventListener('resize', reposition);
            return () => {
                clearTimeout(addClickListenerTimer);
                document.removeEventListener('click', onDocClick, true);
                document.removeEventListener('keydown', onKeydown);
                window.removeEventListener('scroll', reposition, true);
                window.removeEventListener('resize', reposition);
                panel.remove();
            };
        }
        openDropdown() {
            if (this.disabled || this.dropdownOpen) {
                return;
            }
            this.closeDialog();
            if (!this.dropdownBody) {
                const {host, root} = this.createShadowPanel(this.classes.dropdown);
                this.dropdownHost = host;
                this.dropdownBody = root;
            }
            this.renderDropdown();
            this.dropdownOpen = true;
            this.wrapper.classList.add('is-open');
            this.detachDropdown = this.attachFloating(this.dropdownHost, this.dropdownBody, () => this.closeDropdown());
        }
        closeDropdown() {
            if (!this.dropdownOpen) {
                return;
            }
            this.dropdownOpen = false;
            this.wrapper.classList.remove('is-open');
            this.detachDropdown?.();
            this.detachDropdown = null;
        }
        renderDropdown() {
            const {classes, labels} = this;
            const dropdown = this.dropdownBody;
            dropdown.innerHTML = '';
            const section = (title, row) => {
                const box = Filler.el('div', {
                    className: classes.paletteSection
                });
                box.append(Filler.el('div', {
                    className: classes.paletteTitle,
                    textContent: title
                }), row);
                return box;
            };
            const customRow = Filler.el('div', {
                className: classes.paletteRow
            });
            this.customPalette.forEach(hex => customRow.appendChild(this.createPaletteItem(hex, hex, true)));
            const addChip = Filler.el('span', {
                className: classes.paletteChip,
                textContent: '+'
            });
            const addLabel = Filler.el('span', {
                className: classes.paletteLabel,
                textContent: labels.addCurrentColor
            });
            const addButton = Filler.el('button', {
                type: 'button',
                className: classes.paletteAdd
            });
            addButton.append(addChip, addLabel);
            addButton.addEventListener('click', event => {
                event.stopPropagation();
                this.addCustomColor(this.hex);
            });
            customRow.appendChild(addButton);
            const paletteRow = Filler.el('div', {
                className: classes.paletteRow
            });
            [ ...this.palette ].sort((a, b) => a.name.localeCompare(b.name)).forEach(({name, hex}) => paletteRow.appendChild(this.createPaletteItem(hex, name, false)));
            dropdown.append(section(labels.customPaletteTitle, customRow), section(labels.libraryTitle, paletteRow));
        }
        createPaletteItem(hex, label, removable) {
            const {classes} = this;
            const item = Filler.el('button', {
                type: 'button',
                className: classes.paletteSwatch,
                title: label
            });
            const chip = Filler.el('span', {
                className: classes.paletteChip
            });
            chip.style.backgroundColor = hex;
            item.append(chip, Filler.el('span', {
                className: classes.paletteLabel,
                textContent: label
            }));
            if (!removable) {
                item.appendChild(Filler.el('span', {
                    className: classes.paletteHex,
                    textContent: hex
                }));
            }
            item.addEventListener('click', () => this.applyHex(hex));
            if (removable) {
                const remove = Filler.el('span', {
                    className: classes.paletteRemove,
                    textContent: '×'
                });
                remove.addEventListener('click', event => {
                    event.stopPropagation();
                    this.removeCustomColor(hex);
                });
                item.appendChild(remove);
            }
            return item;
        }
        addCustomColor(hex) {
            const normalized = Filler.normalizeHex(hex);
            if (!normalized || this.customPalette.includes(normalized)) {
                return;
            }
            this.customPalette = [ normalized, ...this.customPalette ].slice(0, 24);
            this.persistCustomPalette();
            this.renderDropdown();
        }
        removeCustomColor(hex) {
            this.customPalette = this.customPalette.filter(c => c !== hex);
            this.persistCustomPalette();
            this.renderDropdown();
        }
        loadCustomPalette() {
            if (!this.customPaletteKey) {
                return [];
            }
            try {
                const stored = JSON.parse(localStorage.getItem(this.customPaletteKey) || '[]');
                return Array.isArray(stored) ? stored.filter(hex => Filler.normalizeHex(hex)) : [];
            } catch {
                return [];
            }
        }
        persistCustomPalette() {
            if (!this.customPaletteKey) {
                return;
            }
            try {
                localStorage.setItem(this.customPaletteKey, JSON.stringify(this.customPalette));
            } catch {}
        }
        openDialog() {
            if (this.disabled || this.dialogOpen) {
                return;
            }
            this.closeDropdown();
            if (!this.dialog) {
                this.buildDialog();
            }
            this.renderDialogFields();
            this.syncDialogTabs();
            this.renderDialog();
            this.syncSourceUI();
            this.dialogOpen = true;
            this.wrapper.classList.add('is-open');
            this.detachDialog = this.attachFloating(this.dialogHost, this.dialog, () => this.closeDialog());
        }
        closeDialog() {
            if (!this.dialogOpen) {
                return;
            }
            this.dialogOpen = false;
            this.wrapper.classList.remove('is-open');
            this.detachDialog?.();
            this.detachDialog = null;
        }
        buildDialog() {
            const {classes} = this;
            const area = Filler.el('div', {
                className: classes.dialogArea
            });
            const areaHandle = Filler.el('div', {
                className: classes.dialogAreaHandle
            });
            area.appendChild(areaHandle);
            this.bindAreaDrag(area);
            const hue = Filler.el('div', {
                className: classes.dialogHue
            });
            const hueHandle = Filler.el('div', {
                className: classes.dialogHandle
            });
            hue.appendChild(hueHandle);
            this.bindTrackDrag(hue, ratio => {
                this.hsva.h = ratio * 360;
                this.render();
            });
            const alpha = Filler.el('div', {
                className: classes.dialogAlpha
            });
            const alphaGradient = Filler.el('div', {
                className: classes.dialogAlphaGradient
            });
            const alphaHandle = Filler.el('div', {
                className: classes.dialogHandle
            });
            alpha.append(alphaGradient, alphaHandle);
            this.bindTrackDrag(alpha, ratio => this.setAlpha(ratio * 100));
            const tabs = Filler.el('div', {
                className: classes.dialogTabs
            });
            [ 'hex', 'rgb', 'hsl' ].forEach(format => {
                const tab = Filler.el('button', {
                    type: 'button',
                    className: classes.dialogTab,
                    textContent: format.toUpperCase()
                });
                tab.dataset.format = format;
                tab.addEventListener('click', () => {
                    this.format = format;
                    this.renderDialogFields();
                    this.syncDialogTabs();
                });
                tabs.appendChild(tab);
            });
            const eyedropperButton = this.dialogEyedropperButton = Filler.el('button', {
                type: 'button',
                className: classes.dialogEyedropper,
                title: this.labels.pickColor,
                innerHTML: Filler.EYEDROPPER_ICON,
                hidden: !window.EyeDropper
            });
            eyedropperButton.addEventListener('click', () => this.pickWithEyeDropper());
            const copyButton = Filler.el('button', {
                type: 'button',
                className: classes.dialogCopy,
                title: this.labels.copyValue,
                textContent: '⧉'
            });
            copyButton.addEventListener('click', () => this.copyValue(copyButton));
            const fieldsValues = Filler.el('div', {
                className: classes.dialogFieldsValues
            });
            const fields = Filler.el('div', {
                className: classes.dialogFields
            });
            fields.append(eyedropperButton, fieldsValues, copyButton);
            const solidPanel = Filler.el('div', {
                className: classes.dialogSolid
            });
            solidPanel.append(area, hue, alpha, tabs, fields);
            const sourceButtons = this.buildSourceButtons();
            const imagePanel = this.buildMediaPanel('image');
            const videoPanel = this.buildMediaPanel('video');
            const {host, root: dialog} = this.createShadowPanel(classes.dialog);
            this.dialogHost = host;
            this.dialog = dialog;
            dialog.append(sourceButtons, solidPanel, imagePanel, videoPanel);
            Object.assign(this, {
                dialogArea: area,
                dialogAreaHandle: areaHandle,
                dialogHue: hue,
                dialogHueHandle: hueHandle,
                dialogAlpha: alpha,
                dialogAlphaGradient: alphaGradient,
                dialogAlphaHandle: alphaHandle,
                dialogTabs: tabs,
                dialogFields: fieldsValues,
                dialogFieldInputs: [],
                dialogSolidPanel: solidPanel,
                dialogImagePanel: imagePanel,
                dialogVideoPanel: videoPanel
            });
            this.syncSourceUI();
        }
        buildSourceButtons() {
            const {classes} = this;
            const row = this.dialogSources = Filler.el('div', {
                className: classes.dialogSources
            });
            const group = this.dialogSourcesGroup = Filler.el('div', {
                className: classes.dialogSourcesGroup
            });
            this.dialogSourceButtons = {};
            [ 'solid', 'image', 'video' ].forEach(type => {
                const button = Filler.el('button', {
                    type: 'button',
                    className: classes.dialogSource,
                    innerHTML: Filler.SOURCE_ICONS[type]
                });
                button.addEventListener('click', () => this.setSource(type));
                this.dialogSourceButtons[type] = button;
                group.appendChild(button);
            });
            const closeButton = this.dialogCloseButton = Filler.el('button', {
                type: 'button',
                className: classes.dialogClose,
                title: this.labels.closeDialog,
                textContent: '×'
            });
            closeButton.addEventListener('click', () => this.closeDialog());
            row.append(group, closeButton);
            this.syncSourceLabels();
            return row;
        }
        syncSourceLabels() {
            const {dialogSourceButtons, labels} = this;
            if (!dialogSourceButtons) {
                return;
            }
            dialogSourceButtons.solid.title = labels.solidSource;
            dialogSourceButtons.image.title = labels.imageSource;
            dialogSourceButtons.video.title = labels.videoSource;
        }
        setSource(type) {
            if (this.disabled || type === this.source || !this.sources.includes(type)) {
                return;
            }
            this.source = type;
            this.syncSourceUI();
            this.renderSwatch();
            this.onSourceChange?.(type);
        }
        syncSourceUI() {
            const {dialogSourcesGroup, dialogSourceButtons, dialogSolidPanel, dialogImagePanel, dialogVideoPanel, sources, source} = this;
            if (!dialogSourcesGroup) {
                return;
            }
            Object.entries(dialogSourceButtons).forEach(([type, button]) => {
                button.hidden = !sources.includes(type);
                button.classList.toggle('is-active', type === source);
            });
            dialogSolidPanel.hidden = source !== 'solid';
            dialogImagePanel.hidden = source !== 'image';
            dialogVideoPanel.hidden = source !== 'video';
        }
        buildMediaPanel(type) {
            const {classes, labels} = this;
            const {tag, accept, classKeys, labelKeys} = Filler.MEDIA[type];
            const cls = Object.fromEntries(Object.entries(classKeys).map(([k, classKey]) => [ k, classes[classKey] ]));
            const lbl = Object.fromEntries(Object.entries(labelKeys).map(([k, labelKey]) => [ k, labels[labelKey] ]));
            const media = this[type];
            const fitSelect = Filler.el('select', {
                className: cls.fit
            });
            Object.entries(labels.objectFit).forEach(([value, text]) => {
                fitSelect.appendChild(Filler.el('option', {
                    value,
                    textContent: text,
                    selected: value === media.fit
                }));
            });
            fitSelect.addEventListener('change', () => {
                media.fit = fitSelect.value;
                this.renderMediaPreview(type);
            });
            const rotateButton = Filler.el('button', {
                type: 'button',
                className: cls.rotate,
                title: lbl.rotate,
                textContent: '⤾'
            });
            rotateButton.addEventListener('click', () => {
                media.rotation = (media.rotation + 90) % 360;
                this.renderMediaPreview(type);
            });
            const toolbar = Filler.el('div', {
                className: cls.toolbar
            });
            toolbar.append(fitSelect, rotateButton);
            const uploadInput = Filler.el('input', {
                type: 'file',
                accept,
                className: cls.uploadInput
            });
            uploadInput.addEventListener('change', () => this.handleMediaUpload(type, uploadInput.files?.[0]));
            const previewMedia = Filler.el(tag, {
                className: cls.previewMedia
            });
            const placeholder = Filler.el('span', {
                className: cls.placeholder,
                textContent: lbl.upload
            });
            const removeButton = Filler.el('button', {
                type: 'button',
                className: cls.remove,
                title: lbl.remove,
                textContent: '×'
            });
            removeButton.addEventListener('click', event => {
                event.preventDefault();
                event.stopPropagation();
                this.clearMedia(type);
            });
            const resetButton = Filler.el('button', {
                type: 'button',
                className: cls.reset,
                title: labels.resetAdjustments,
                textContent: '↺'
            });
            resetButton.addEventListener('click', event => {
                event.preventDefault();
                event.stopPropagation();
                this.resetMediaAdjustments(type);
            });
            const preview = Filler.el('span', {
                className: cls.preview
            });
            preview.append(previewMedia, placeholder, resetButton, removeButton);
            const upload = Filler.el('label', {
                className: cls.upload
            });
            upload.append(uploadInput, preview);
            this.mediaRefs[type] = {
                uploadInput,
                previewMedia,
                placeholder,
                upload,
                removeButton
            };
            const panel = Filler.el('div', {
                className: cls.panel
            });
            panel.append(toolbar, upload);
            panel.append(...type === 'video' ? this.buildVideoSettings(type, cls, media) : this.buildFilterSliders(type, cls, media));
            this.renderMediaPreview(type);
            this.syncMediaAdjustmentsState(type);
            return panel;
        }
        static setSliderPercent(input) {
            const min = +input.min;
            const max = +input.max;
            input.style.setProperty('--percent', (+input.value - min) / (max - min));
        }
        buildFilterSliders(type, cls, media) {
            const {labels} = this;
            const slidersTitle = Filler.el('span', {
                className: cls.slidersTitle,
                textContent: labels.adjustments
            });
            const sliders = Filler.el('div', {
                className: cls.sliders
            });
            const sliderInputs = {};
            const sliderValues = {};
            Filler.MEDIA_FILTERS.forEach(({key, min, max, step, default: def}) => {
                const displayUnit = Filler.filterDisplayUnit(key);
                const valueEl = Filler.el('span', {
                    className: cls.sliderValue,
                    part: false,
                    textContent: `${media[key]}${displayUnit}`
                });
                const head = Filler.el('span', {
                    className: cls.sliderHead
                });
                head.append(Filler.el('span', {
                    className: cls.sliderLabel,
                    textContent: labels.filters[key]
                }), valueEl);
                const input = Filler.el('input', {
                    type: 'range',
                    className: cls.sliderInput,
                    min,
                    max,
                    step,
                    value: media[key]
                });
                input.style.setProperty('--center', (def - min) / (max - min));
                Filler.setSliderPercent(input);
                input.addEventListener('input', () => {
                    media[key] = +input.value;
                    valueEl.textContent = `${input.value}${displayUnit}`;
                    Filler.setSliderPercent(input);
                    this.applyImageFilter();
                    this.syncMediaAdjustmentsState(type);
                });
                sliderInputs[key] = input;
                sliderValues[key] = valueEl;
                const row = Filler.el('label', {
                    className: cls.slider
                });
                row.append(head, input);
                sliders.appendChild(row);
            });
            Object.assign(this.mediaRefs[type], {
                sliderInputs,
                sliderValues
            });
            return [ slidersTitle, sliders ];
        }
        buildVideoSettings(type, cls, media) {
            const {labels} = this;
            const settingsTitle = Filler.el('span', {
                className: cls.settingsTitle,
                textContent: labels.videoSettingsTitle
            });
            const settings = Filler.el('div', {
                className: cls.settings
            });
            const settingInputs = {};
            Filler.VIDEO_SETTINGS.forEach(({key, type: controlType, options}) => {
                const row = Filler.el('label', {
                    className: cls.setting
                });
                row.appendChild(Filler.el('span', {
                    className: cls.settingLabel,
                    textContent: labels.videoSettings[key]
                }));
                const onInput = value => {
                    media[key] = value;
                    this.applyVideoSettings(type);
                    this.syncMediaAdjustmentsState(type);
                };
                let input;
                if (controlType === 'select') {
                    input = Filler.el('select', {
                        className: cls.settingInput
                    });
                    options.forEach(value => {
                        input.appendChild(Filler.el('option', {
                            value,
                            textContent: labels.videoPreload[value],
                            selected: value === media[key]
                        }));
                    });
                    input.addEventListener('change', () => onInput(input.value));
                } else {
                    input = Filler.el('input', {
                        type: 'checkbox',
                        className: cls.settingInput,
                        checked: media[key]
                    });
                    input.addEventListener('change', () => onInput(input.checked));
                }
                row.appendChild(input);
                settings.appendChild(row);
                settingInputs[key] = input;
            });
            Object.assign(this.mediaRefs[type], {
                settingInputs
            });
            return [ settingsTitle, settings ];
        }
        hasMediaAdjustments(type) {
            const media = this[type];
            return type === 'video' ? Filler.VIDEO_SETTINGS.some(({key, default: value}) => media[key] !== value) : Filler.MEDIA_FILTERS.some(({key, default: value}) => media[key] !== value);
        }
        syncMediaAdjustmentsState(type) {
            this.mediaRefs[type].upload.classList.toggle('has-adjustments', this.hasMediaAdjustments(type));
        }
        handleMediaUpload(type, file) {
            const {accept} = Filler.MEDIA[type];
            if (!file || !file.type.startsWith(accept.replace('*', ''))) {
                return;
            }
            const media = this[type];
            const reader = new FileReader;
            reader.onload = () => {
                media.dataUrl = reader.result;
                media.rotation = 0;
                this.renderMediaPreview(type);
            };
            reader.readAsDataURL(file);
        }
        clearMedia(type) {
            const media = this[type];
            media.dataUrl = null;
            media.rotation = 0;
            this.mediaRefs[type].uploadInput.value = '';
            this.resetMediaAdjustments(type);
            this.renderMediaPreview(type);
        }
        resetMediaAdjustments(type) {
            const media = this[type];
            if (type === 'video') {
                const {settingInputs} = this.mediaRefs[type];
                Filler.VIDEO_SETTINGS.forEach(({key, type: controlType, default: value}) => {
                    media[key] = value;
                    if (controlType === 'select') {
                        settingInputs[key].value = value;
                    } else {
                        settingInputs[key].checked = value;
                    }
                });
                this.applyVideoSettings(type);
            } else {
                const {sliderInputs, sliderValues} = this.mediaRefs[type];
                Filler.MEDIA_FILTERS.forEach(({key, default: value}) => {
                    media[key] = value;
                    const input = sliderInputs[key];
                    input.value = value;
                    Filler.setSliderPercent(input);
                    sliderValues[key].textContent = `${value}${Filler.filterDisplayUnit(key)}`;
                });
            }
            this.syncMediaAdjustmentsState(type);
            this.renderSwatch();
        }
        renderMediaPreview(type) {
            const {dataUrl, fit, rotation} = this[type];
            const {previewMedia, placeholder, upload, removeButton, sliderInputs} = this.mediaRefs[type];
            upload.classList.toggle('has-media', !!dataUrl);
            placeholder.hidden = !!dataUrl;
            removeButton.hidden = !dataUrl;
            previewMedia.hidden = !dataUrl;
            if (sliderInputs) {
                Object.values(sliderInputs).forEach(input => {
                    input.disabled = !dataUrl;
                });
            }
            if (dataUrl) {
                if (previewMedia.getAttribute('src') !== dataUrl) {
                    previewMedia.src = dataUrl;
                }
                previewMedia.style.objectFit = fit;
                previewMedia.style.transform = `rotate(${rotation}deg)`;
            } else {
                previewMedia.removeAttribute('src');
            }
            if (type === 'video') {
                this.applyVideoSettings(type);
            }
            this.renderSwatch();
            this.onMediaChange?.(type, this[type]);
        }
        applyImageFilter() {
            const filter = Filler.computeMediaFilter(this.image);
            const refs = this.mediaRefs.image;
            if (refs?.previewMedia) {
                refs.previewMedia.style.filter = filter;
            }
            if (this.source === 'image' && this.swatchColor) {
                this.swatchColor.style.filter = filter;
            }
        }
        applyVideoSettings(type) {
            const media = this[type];
            const refs = this.mediaRefs[type];
            const apply = el => {
                if (!el) {
                    return;
                }
                el.loop = media.loop;
                el.muted = media.muted;
                el.controls = media.controls;
                el.playsInline = media.playsInline;
                el.autoplay = media.autoplay;
                if (!el.getAttribute('src')) {
                    return;
                }
                if (media.autoplay) {
                    el.play().catch(() => {});
                } else {
                    el.pause();
                }
            };
            apply(refs?.previewMedia);
            if (this.source === type) {
                apply(this.swatchVideo);
            }
        }
        bindTrackDrag(track, onRatio) {
            const update = event => {
                const rect = track.getBoundingClientRect();
                onRatio(Filler.clamp((event.clientX - rect.left) / rect.width, 0, 1));
            };
            track.addEventListener('pointerdown', event => {
                if (this.disabled) {
                    return;
                }
                event.preventDefault();
                track.setPointerCapture(event.pointerId);
                update(event);
                const onMove = moveEvent => update(moveEvent);
                const onUp = () => {
                    track.removeEventListener('pointermove', onMove);
                    track.removeEventListener('pointerup', onUp);
                    track.removeEventListener('pointercancel', onUp);
                };
                track.addEventListener('pointermove', onMove);
                track.addEventListener('pointerup', onUp);
                track.addEventListener('pointercancel', onUp);
            });
        }
        bindAreaDrag(area) {
            const update = event => {
                const rect = area.getBoundingClientRect();
                const x = Filler.clamp(event.clientX - rect.left, 0, rect.width);
                const y = Filler.clamp(event.clientY - rect.top, 0, rect.height);
                this.hsva.s = x / rect.width * 100;
                this.hsva.v = 100 - y / rect.height * 100;
                this.render();
            };
            area.addEventListener('pointerdown', event => {
                if (this.disabled) {
                    return;
                }
                event.preventDefault();
                area.setPointerCapture(event.pointerId);
                update(event);
                const onMove = moveEvent => update(moveEvent);
                const onUp = () => {
                    area.removeEventListener('pointermove', onMove);
                    area.removeEventListener('pointerup', onUp);
                    area.removeEventListener('pointercancel', onUp);
                };
                area.addEventListener('pointermove', onMove);
                area.addEventListener('pointerup', onUp);
                area.addEventListener('pointercancel', onUp);
            });
        }
        syncDialogTabs() {
            [ ...this.dialogTabs.children ].forEach(tab => {
                if (tab === undefined || !tab.dataset) {
                    return;
                }
                tab.classList.toggle('is-active', tab.dataset.format === this.format);
            });
        }
        renderDialogFields() {
            const fields = this.dialogFields;
            fields.innerHTML = '';
            this.dialogFieldInputs = [];
            const addField = (label, {min = 0, max, isHex = false} = {}, onInput) => {
                const input = Filler.el('input', {
                    type: 'text',
                    inputMode: isHex ? 'text' : 'numeric',
                    maxLength: isHex ? 6 : String(max).length
                });
                input.addEventListener('input', () => {
                    const value = isHex ? input.value.replace(/[^0-9a-fA-F]/g, '') : Filler.sanitizeDigits(input.value, min, max);
                    if (value !== input.value) {
                        input.value = value;
                    }
                    onInput(value);
                });
                input.addEventListener('focus', () => input.select());
                const wrap = Filler.el('label', {
                    className: this.classes.dialogField
                });
                wrap.append(input, Filler.el('span', {
                    className: this.classes.dialogFieldLabel,
                    textContent: label
                }));
                fields.appendChild(wrap);
                this.dialogFieldInputs.push(input);
            };
            if (this.format === 'rgb') {
                const withRgb = patch => {
                    const rgb = {
                        ...Filler.hsvToRgb(this.hsva),
                        ...patch
                    };
                    this.hsva = {
                        ...Filler.rgbToHsv(rgb),
                        a: this.hsva.a
                    };
                    this.render();
                };
                addField('R', {
                    max: 255
                }, v => withRgb({
                    r: Filler.clamp(+v || 0, 0, 255)
                }));
                addField('G', {
                    max: 255
                }, v => withRgb({
                    g: Filler.clamp(+v || 0, 0, 255)
                }));
                addField('B', {
                    max: 255
                }, v => withRgb({
                    b: Filler.clamp(+v || 0, 0, 255)
                }));
            } else if (this.format === 'hsl') {
                const withHsl = patch => {
                    const hsl = {
                        ...Filler.rgbToHsl(Filler.hsvToRgb(this.hsva)),
                        ...patch
                    };
                    this.hsva = {
                        ...Filler.rgbToHsv(Filler.hslToRgb(hsl)),
                        a: this.hsva.a
                    };
                    this.render();
                };
                addField('H', {
                    max: 360
                }, v => withHsl({
                    h: Filler.clamp(+v || 0, 0, 360)
                }));
                addField('S', {
                    max: 100
                }, v => withHsl({
                    s: Filler.clamp(+v || 0, 0, 100)
                }));
                addField('L', {
                    max: 100
                }, v => withHsl({
                    l: Filler.clamp(+v || 0, 0, 100)
                }));
            } else {
                addField('HEX', {
                    isHex: true
                }, v => {
                    const rgb = Filler.hexToRgb(`#${v}`);
                    if (rgb) {
                        this.hsva = {
                            ...Filler.rgbToHsv(rgb),
                            a: this.hsva.a
                        };
                        this.render();
                    }
                });
            }
            addField('A', {
                max: 100
            }, v => this.setAlpha(Filler.clamp(+v || 0, 0, 100)));
            this.updateDialogFieldValues();
        }
        updateDialogFieldValues() {
            const {format, dialogFieldInputs} = this;
            if (!dialogFieldInputs.length) {
                return;
            }
            const {a} = this.hsva;
            const rgb = Filler.hsvToRgb(this.hsva);
            let values;
            if (format === 'rgb') {
                values = [ rgb.r, rgb.g, rgb.b, a ].map(Math.round);
            } else if (format === 'hsl') {
                const hsl = Filler.rgbToHsl(rgb);
                values = [ hsl.h, hsl.s, hsl.l, a ].map(Math.round);
            } else {
                values = [ this.hex.slice(1), Math.round(a) ];
            }
            dialogFieldInputs.forEach((input, index) => {
                if (input.getRootNode().activeElement !== input) {
                    input.value = values[index];
                }
            });
        }
        renderDialog() {
            const {h, s, v, a} = this.hsva;
            const {dialogAreaHandle} = this;
            const hueRgb = Filler.hsvToRgb({
                h,
                s: 100,
                v: 100
            });
            this.dialogArea.style.backgroundColor = `rgb(${Math.round(hueRgb.r)}, ${Math.round(hueRgb.g)}, ${Math.round(hueRgb.b)})`;
            const rgb = Filler.hsvToRgb({
                h,
                s,
                v
            });
            const rgbTriplet = `${Math.round(rgb.r)}, ${Math.round(rgb.g)}, ${Math.round(rgb.b)}`;
            dialogAreaHandle.style.left = `${s}%`;
            dialogAreaHandle.style.top = `${100 - v}%`;
            dialogAreaHandle.style.backgroundColor = `rgb(${rgbTriplet})`;
            this.dialogHueHandle.style.setProperty('--percent', h / 360);
            this.dialogAlphaGradient.style.backgroundImage = `linear-gradient(to right, rgba(${rgbTriplet}, 0), rgba(${rgbTriplet}, 1))`;
            this.dialogAlphaHandle.style.setProperty('--percent', a / 100);
            this.updateDialogFieldValues();
        }
        update(options = {}) {
            const {image, video, source, alpha, ...rest} = options;
            const paletteChanged = 'palette' in options;
            const sourcesChanged = 'sources' in options;
            const labelsChanged = 'labels' in options;
            Object.assign(this, rest, {
                classes: options.classes ? {
                    ...this.classes,
                    ...options.classes
                } : this.classes,
                labels: options.labels ? {
                    ...this.labels,
                    ...options.labels
                } : this.labels,
                palette: options.palette ? [ ...options.palette ] : this.palette,
                sources: options.sources?.length ? [ ...options.sources ] : this.sources
            });
            if ('disabled' in options) {
                const {disabled, el, alphaInput, swatch, wrapper} = this;
                el.disabled = alphaInput.disabled = swatch.disabled = disabled;
                wrapper.classList.toggle('is-disabled', disabled);
                if (disabled) {
                    this.closeDropdown();
                    this.closeDialog();
                }
            }
            if (paletteChanged && this.dropdownOpen) {
                this.renderDropdown();
            }
            if (sourcesChanged) {
                if (!this.sources.includes(this.source)) {
                    this.source = this.sources[0];
                }
                this.syncSourceUI();
                this.syncSwatchTitle();
            }
            if (labelsChanged) {
                this.syncSourceLabels();
                this.syncSwatchTitle();
                if (this.dialogCloseButton) {
                    this.dialogCloseButton.title = this.labels.closeDialog;
                }
                if (this.source !== 'solid') {
                    this.renderSwatch();
                }
            }
        }
        destroy() {
            this.closeDialog();
            this.closeDropdown();
            clearTimeout(this.copyResetTimer);
        }
    }
    document.addEventListener('youla:init', () => {
        Youla.directive('filler', (el, output) => {
            if (!(el instanceof HTMLInputElement)) {
                console.warn('Youla.js: "u-filler" requires an <input>.');
                return;
            }
            const options = output && typeof output === 'object' ? output : {};
            if (el._x_filler) {
                el._x_filler.update(options);
                return;
            }
            el._x_filler = new Filler(el, options);
        });
    });
})();