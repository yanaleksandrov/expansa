<?php

use App\Models\Option;
use Expansa\Builders\Form;

// All values are read from the actual server.
$upload_limits = array_filter([
    ini_parse_quantity((string) ini_get('upload_max_filesize')),
    ini_parse_quantity((string) ini_get('post_max_size')),
]);
$server_max_mb = $upload_limits ? (int) floor(min($upload_limits) / 1048576) : 0;

$has_gd      = extension_loaded('gd');
$has_imagick = class_exists('Imagick');
$has_webp    = ($has_gd && function_exists('imagewebp')) || ($has_imagick && ! empty(Imagick::queryFormats('WEBP')));
$has_avif    = ($has_gd && function_exists('imageavif')) || ($has_imagick && ! empty(Imagick::queryFormats('AVIF')));

$libraries      = implode(', ', array_keys(array_filter(['GD' => $has_gd, 'Imagick' => $has_imagick]))) ?: t('none');
$execution_time = (int) ini_get('max_execution_time');

// Formats offered in "Convert Images To" depend on what the server can really encode.
$convert_options = ['none' => t('Do not convert')];
if ($has_webp) {
    $convert_options['webp'] = 'WebP';
}
if ($has_avif) {
    $convert_options['avif'] = 'AVIF';
}

/**
 * Website file storage in dashboard.
 *
 * @since 2027.1
 */
return Form::enqueue(
    'site-storage',
    [
        'class'           => 'tab',
        'u-data'          => 'tab',
        '@load'           => '$dirty.watch($el)',
        '@submit.prevent' => '$ajax.post("options/update", "", () => $dirty.remove($el))',
    ],
    [
        [
            'name'    => 'storage',
            'type'    => 'tab',
            'label'   => t('Uploads & Storage'),
            'caption' => t('Upload limits and file organization'),
            'icon'    => 'ph ph-tree-structure',
            'fields'  => [
                [
                    'type'          => 'group',
                    'name'          => 'images',
                    'label'         => t('Upload Limits'),
                    'class'         => '',
                    'label_class'   => '',
                    'content_class' => 'dg ga-4 g-6 gtc-1',
                    'fields'        => [
                        [
                            'type'        => 'number',
                            'name'        => 'images[max_file_size]',
                            'error'       => 'images.max_file_size',
                            'label'       => t('Maximum File Size'),
                            'class'       => '',
                            'label_class' => '',
                            'reset'       => 0,
                            'before'      => '',
                            'after'       => 'MB',
                            'instruction' => t('Maximum size of an uploaded file. It cannot exceed the PHP limits upload_max_filesize and post_max_size.')
                                . ' ' . t('On this server: upload_max_filesize :upload, post_max_size :post.', ini_get('upload_max_filesize'), ini_get('post_max_size'))
                                . ($server_max_mb > 0 ? ' ' . t('Current server limit: :size MB.', $server_max_mb) : ''),
                            'tooltip'     => '',
                            'copy'        => 0,
                            'validator'   => '',
                            'conditions'  => [],
                            'attributes'  => [
                                'u-prop' => 'images.max_file_size',
                                'value'  => Option::get('images.max_file_size'),
                                'min'    => 1,
                                'max'    => $server_max_mb > 0 ? $server_max_mb : null,
                            ],
                        ],
                    ],
                ],
                [
                    'type'          => 'group',
                    'name'          => 'images',
                    'label'         => t('Folder Structure'),
                    'class'         => '',
                    'label_class'   => '',
                    'content_class' => 'dg ga-4 g-6 gtc-1',
                    'fields'        => [
                        [
                            'type'        => 'select',
                            'name'        => 'images[organization]',
                            'error'       => 'images.organization',
                            'label'       => t('File Organization'),
                            'class'       => '',
                            'label_class' => '',
                            'reset'       => 0,
                            'before'      => '',
                            'after'       => '',
                            'instruction' => t('Changing this setting only affects new files. Existing files stay where they are until you move them with the button. Save the setting first: the move uses the saved value. Files are copied, their hashes are verified, and old copies are removed only after verification.'),
                            'tooltip'     => '',
                            'copy'        => 0,
                            'validator'   => '',
                            'conditions'  => [],
                            'attributes'  => [
                                'u-prop' => 'images.organization',
                                'value'  => Option::get('images.organization'),
                            ],
                            'options'     => [
                                'hash'      => t('Into hash-based folders'),
                                'yearmonth' => t('Into month- and year-based folders'),
                            ],
                        ],
                        [
                            'type'     => 'custom',
                            'callback' => function () {
                                ?>
                                <div class="storage-tree">
                                    <ul class="storage-tree-list">
                                        <li>
                                            <i class="ph ph-folder-open"></i> <span u-text="images.storage_directory"></span>storage/i/
                                            <ul u-show="images.organization === 'yearmonth'">
                                                <li>
                                                    <i class="ph ph-folder-open"></i> <?php echo date('Y'); ?>/<?php echo date('m'); ?>/sunset.jpg
                                                </li>
                                            </ul>
                                            <ul u-show="images.organization === 'hash'">
                                                <li>
                                                    <i class="ph ph-folder-open"></i> 3f/a2/c44298fc5b**********78ac52b855.jpg
                                                </li>
                                            </ul>
                                        </li>
                                    </ul>
                                    <p class="storage-tree-note" u-show="images.organization === 'yearmonth'"><?php echo t('Files are grouped by upload date. Folders are easy to browse by hand.'); ?></p>
                                    <p class="storage-tree-note" u-show="images.organization === 'hash'"><?php echo t('Folders are named after the first characters of the SHA-256 hash of the file. Files spread evenly across folders and duplicates are found instantly.'); ?></p>
                                    <div class="df g-2">
                                        <button type="button" class="btn btn--primary" @click="startBulkOperation('migrate')" disabled><?php echo t('Move Existing Files'); ?></button>
                                        <button type="button" class="btn btn--outline" @click="startBulkOperation('migrate', true)" disabled><?php echo t('Dry Run'); ?></button>
                                    </div>
                                </div>
                                <?php
                            },
                        ],
                    ],
                ],
            ],
        ],
        [
            'name'    => 'images',
            'type'    => 'tab',
            'label'   => t('Images'),
            'caption' => t('Formats, quality, thumbnails and variants'),
            'icon'    => 'ph ph-image',
            'fields'  => [
                [
                    'type'          => 'group',
                    'name'          => 'images',
                    'label'         => t('Generated Images'),
                    'class'         => '',
                    'label_class'   => '',
                    'content_class' => 'dg ga-4 g-6 gtc-1',
                    'fields'        => [
                        [
                            'type'        => 'select',
                            'name'        => 'images[convert_to]',
                            'error'       => 'images.convert_to',
                            'label'       => t('Convert Images To'),
                            'class'       => '',
                            'label_class' => '',
                            'reset'       => 0,
                            'before'      => '',
                            'after'       => '',
                            'instruction' => t('Applies only to new thumbnails and variants. Original files are never converted. Formats that the server cannot create are not shown.')
                                . ' ' . t('Image libraries on this server: :libraries. WebP: :webp, AVIF: :avif.', $libraries, $has_webp ? t('supported') : t('not supported'), $has_avif ? t('supported') : t('not supported')),
                            'tooltip'     => '',
                            'copy'        => 0,
                            'validator'   => '',
                            'conditions'  => [],
                            'attributes'  => [
                                'u-prop' => 'images.convert_to',
                                'value'  => Option::get('images.convert_to'),
                            ],
                            'options'     => $convert_options,
                        ],
                        [
                            'type'        => 'button-group',
                            'name'        => 'images[convert_existing]',
                            'label'       => t('Convert Existing Images'),
                            'class'       => '',
                            'label_class' => '',
                            'before'      => '',
                            'after'       => '',
                            'instruction' => t('Creates converted copies of existing images in the selected format. Originals are never modified. Run a dry run first to see how many files are affected. The operation runs in batches: keep this tab open until it finishes.')
                                . ' ' . ($execution_time > 0 ? t('PHP max_execution_time on this server: :seconds s.', $execution_time) : t('PHP max_execution_time on this server: unlimited.')),
                            'tooltip'     => '',
                            'conditions'  => [],
                            'options'     => [
                                'convert' => [
                                    'content'    => t('Convert Existing Images'),
                                    'class'      => 'btn btn--primary',
                                    'attributes' => ['@click' => 'startBulkOperation("convert")'],
                                ],
                                'dry_run' => [
                                    'content'    => t('Dry Run'),
                                    'class'      => 'btn btn--outline',
                                    'attributes' => ['@click' => 'startBulkOperation("convert", true)'],
                                ],
                            ],
                        ],
                        [
                            'type'        => 'image-quality',
                            'name'        => 'images[quality]',
                            'error'       => 'images.quality',
                            'label'       => t('Image Quality'),
                            'class'       => '',
                            'label_class' => '',
                            'reset'       => 0,
                            'before'      => '',
                            'after'       => '',
                            'instruction' => t('Quality used when generating thumbnails and variants. Higher quality produces larger files, the growth is steepest above 90. Optimal quality: 85–90. The size is an average measured on JPEG and WebP photos, the real size depends on the image.'),
                            'tooltip'     => '',
                            'copy'        => 0,
                            'validator'   => '',
                            'conditions'  => [],
                            'attributes'  => [
                                'u-prop'            => 'images.quality',
                                'value'             => Option::get('images.quality'),
                                'min'               => 40,
                                'max'               => 100,
                                'step'              => 1,
                                'data-optimal-from' => 85,
                                'data-optimal-to'   => 90,
                            ],
                            'options'     => [
                                [
                                    'content' => t('Thumbnail') . ' 400×400',
                                    'size'    => 64,
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'type'          => 'group',
                    'name'          => 'images',
                    'label'         => t('Thumbnails'),
                    'class'         => '',
                    'label_class'   => '',
                    'content_class' => 'dg ga-4 g-6 gtc-2',
                    'fields'        => [
                        [
                            'type'        => 'number',
                            'name'        => 'images[thumbnail_width]',
                            'error'       => 'images.thumbnail_width',
                            'label'       => t('Thumbnail Width'),
                            'class'       => '',
                            'label_class' => '',
                            'reset'       => 0,
                            'before'      => '',
                            'after'       => 'px',
                            'instruction' => t('Width of the thumbnail created during upload.'),
                            'tooltip'     => '',
                            'copy'        => 0,
                            'validator'   => '',
                            'conditions'  => [],
                            'attributes'  => [
                                'u-prop' => 'images.thumbnail_width',
                                'value'  => Option::get('images.thumbnail_width', 400),
                                'min'    => 240,
                                'max'    => 4096,
                            ],
                        ],
                        [
                            'type'        => 'number',
                            'name'        => 'images[thumbnail_height]',
                            'error'       => 'images.thumbnail_height',
                            'label'       => t('Thumbnail Height'),
                            'class'       => '',
                            'label_class' => '',
                            'reset'       => 0,
                            'before'      => '',
                            'after'       => 'px',
                            'instruction' => t('Height of the thumbnail created during upload.'),
                            'tooltip'     => '',
                            'copy'        => 0,
                            'validator'   => '',
                            'conditions'  => [],
                            'attributes'  => [
                                'u-prop' => 'images.thumbnail_height',
                                'value'  => Option::get('images.thumbnail_height', 400),
                                'min'    => 240,
                                'max'    => 4096,
                            ],
                        ],
                    ]
                ],
                [
                    'type'          => 'group',
                    'name'          => 'images',
                    'label'         => t('Variant Limits'),
                    'class'         => '',
                    'label_class'   => '',
                    'content_class' => 'dg ga-4 g-6 gtc-2',
                    'fields'        => [
                        [
                            'type'        => 'number',
                            'name'        => 'images[max_width]',
                            'error'       => 'images.max_width',
                            'label'       => t('Maximum Width'),
                            'class'       => '',
                            'label_class' => '',
                            'reset'       => 0,
                            'before'      => '',
                            'after'       => 'px',
                            'instruction' => t('Maximum width of a generated variant. The maximum allowed side is 4096 pixels.'),
                            'tooltip'     => '',
                            'copy'        => 0,
                            'validator'   => '',
                            'conditions'  => [],
                            'attributes'  => [
                                'u-prop' => 'images.max_width',
                                'value'  => Option::get('images.max_width', 4096),
                                'min'    => 1,
                                'max'    => 4096,
                            ],
                        ],
                        [
                            'type'        => 'number',
                            'name'        => 'images[max_height]',
                            'error'       => 'images.max_height',
                            'label'       => t('Maximum Height'),
                            'class'       => '',
                            'label_class' => '',
                            'reset'       => 0,
                            'before'      => '',
                            'after'       => 'px',
                            'instruction' => t('Maximum height of a generated variant. The maximum allowed side is 4096 pixels.'),
                            'tooltip'     => '',
                            'copy'        => 0,
                            'validator'   => '',
                            'conditions'  => [],
                            'attributes'  => [
                                'u-prop' => 'images.max_height',
                                'value'  => Option::get('images.max_height', 4096),
                                'min'    => 1,
                                'max'    => 4096,
                            ],
                        ],
                        [
                            'type'        => 'select',
                            'name'        => 'images[rounding_step]',
                            'error'       => 'images.rounding_step',
                            'label'       => t('Size Rounding Step'),
                            'class'       => '',
                            'label_class' => '',
                            'reset'       => 0,
                            'before'      => '',
                            'after'       => '',
                            'instruction' => t('Dimensions are rounded up to this step so that nearly identical sizes do not create duplicate variants. A larger step creates fewer variants, but they are less exact.'),
                            'tooltip'     => '',
                            'copy'        => 0,
                            'validator'   => '',
                            'conditions'  => [],
                            'attributes'  => [
                                'u-prop' => 'images.rounding_step',
                                'value'  => Option::get('images.rounding_step', 16),
                            ],
                            'options'     => [
                                '4'  => t(':size px', 4),
                                '8'  => t(':size px', 8),
                                '16' => t(':size px (recommended)', 16),
                                '32' => t(':size px', 32),
                                '64' => t(':size px', 64),
                            ],
                        ],
                    ],
                ],
            ],
        ],
        [
            'name'    => 'maintenance',
            'type'    => 'tab',
            'label'   => t('Maintenance'),
            'caption' => t('Cleanup, integrity checks and duplicates'),
            'icon'    => 'ph ph-wrench',
            'fields'  => [
                [
                    'type'          => 'group',
                    'name'          => 'images',
                    'label'         => t('File Cleanup'),
                    'class'         => '',
                    'label_class'   => '',
                    'content_class' => 'dg ga-4 g-6 gtc-1',
                    'fields'        => [
                        [
                            'type'        => 'select',
                            'name'        => 'images[cleanup_type]',
                            'error'       => 'images.cleanup_type',
                            'label'       => t('File Type'),
                            'class'       => '',
                            'label_class' => '',
                            'reset'       => 0,
                            'before'      => '',
                            'after'       => '',
                            'instruction' => t('Choose which files to include. Thumbnails are not selected by default. Originals require a separate confirmation before deletion.'),
                            'tooltip'     => '',
                            'copy'        => 0,
                            'validator'   => '',
                            'conditions'  => [],
                            'attributes'  => [
                                'u-prop' => 'images.cleanup_type',
                                'value'  => Option::get('images.cleanup_type'),
                            ],
                            'options'     => [
                                'variants'   => t('Variants only'),
                                'thumbnails' => t('Thumbnails only'),
                                'originals'  => t('Originals only'),
                            ],
                        ],
                        [
                            'type'        => 'select',
                            'name'        => 'images[cleanup_age]',
                            'error'       => 'images.cleanup_age',
                            'label'       => t('File Age'),
                            'class'       => '',
                            'label_class' => '',
                            'reset'       => 0,
                            'before'      => '',
                            'after'       => '',
                            'instruction' => t('Find files created before the selected age. File age is taken from the file creation time on disk.'),
                            'tooltip'     => '',
                            'copy'        => 0,
                            'validator'   => '',
                            'conditions'  => [],
                            'attributes'  => [
                                'u-prop' => 'images.cleanup_age',
                                'value'  => Option::get('images.cleanup_age'),
                            ],
                            'options'     => [
                                '0'   => t('Any age'),
                                '7'   => t('Older than 7 days'),
                                '30'  => t('Older than 30 days'),
                                '90'  => t('Older than 90 days'),
                                '180' => t('Older than 180 days'),
                                '365' => t('Older than 1 year'),
                            ],
                        ],
                        [
                            'type'        => 'number',
                            'name'        => 'images[cleanup_min_size]',
                            'error'       => 'images.cleanup_min_size',
                            'label'       => t('Minimum File Size'),
                            'class'       => '',
                            'label_class' => '',
                            'reset'       => 0,
                            'before'      => '',
                            'after'       => 'MB',
                            'instruction' => t('Optional. Find only files larger than this size. Use 0 to ignore the size.'),
                            'tooltip'     => '',
                            'copy'        => 0,
                            'validator'   => '',
                            'conditions'  => [],
                            'attributes'  => [
                                'u-prop' => 'images.cleanup_min_size',
                                'value'  => Option::get('images.cleanup_min_size'),
                                'min'    => 0,
                            ],
                        ],
                        [
                            'type'        => 'select',
                            'name'        => 'images[cleanup_format]',
                            'error'       => 'images.cleanup_format',
                            'label'       => t('File Format'),
                            'class'       => '',
                            'label_class' => '',
                            'reset'       => 0,
                            'before'      => '',
                            'after'       => '',
                            'instruction' => t('Optional. Find only files of the selected format.'),
                            'tooltip'     => '',
                            'copy'        => 0,
                            'validator'   => '',
                            'conditions'  => [],
                            'attributes'  => [
                                'u-prop' => 'images.cleanup_format',
                                'value'  => Option::get('images.cleanup_format'),
                            ],
                            'options'     => [
                                'any'  => t('Any format'),
                                'webp' => 'WebP',
                                'avif' => 'AVIF',
                                'jpeg' => 'JPEG',
                                'png'  => 'PNG',
                                'gif'  => 'GIF',
                                'svg'  => 'SVG',
                            ],
                        ],
                        [
                            'type'        => 'text',
                            'name'        => 'images[cleanup_image]',
                            'error'       => 'images.cleanup_image',
                            'label'       => t('Image'),
                            'class'       => '',
                            'label_class' => '',
                            'reset'       => 0,
                            'before'      => '',
                            'after'       => '',
                            'instruction' => t('Optional. Enter the ID or the SHA-256 hash of one image to find only its files.'),
                            'tooltip'     => '',
                            'copy'        => 0,
                            'validator'   => '',
                            'conditions'  => [],
                            'attributes'  => [
                                'u-prop' => 'images.cleanup_image',
                                'value'  => Option::get('images.cleanup_image'),
                            ],
                        ],
                        [
                            'type'        => 'button-group',
                            'name'        => 'images[cleanup_actions]',
                            'label'       => t('Cleanup'),
                            'class'       => '',
                            'label_class' => '',
                            'before'      => '',
                            'after'       => '',
                            'instruction' => t('Preview shows the number of matching files and the amount of storage that can be freed, it does not delete anything. Deletion asks for confirmation and runs in batches: keep this tab open until it finishes. Original files require a separate confirmation.'),
                            'tooltip'     => '',
                            'conditions'  => [],
                            'options'     => [
                                'preview' => [
                                    'content'    => t('Preview Results'),
                                    'class'      => 'btn btn--primary',
                                    'attributes' => ['@click' => 'previewCleanup()'],
                                ],
                                'delete'  => [
                                    'content'    => t('Delete Found Files'),
                                    'class'      => 'btn btn--danger',
                                    'attributes' => [
                                        '@click'    => 'confirmCleanup()',
                                        ':disabled' => '!cleanupPreviewReady',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'type'          => 'group',
                    'name'          => 'images',
                    'label'         => t('Integrity Check'),
                    'class'         => '',
                    'label_class'   => '',
                    'content_class' => 'dg ga-4 g-6 gtc-1',
                    'fields'        => [
                        [
                            'type'        => 'button-group',
                            'name'        => 'images[check_actions]',
                            'label'       => '',
                            'class'       => '',
                            'label_class' => '',
                            'before'      => '',
                            'after'       => '',
                            'instruction' => t('Scan the database and the disk for files without records, records without files, stalled uploads and old temporary files. The scan makes no changes, found problems are fixed after confirmation.'),
                            'tooltip'     => '',
                            'conditions'  => [],
                            'options'     => [
                                'check' => [
                                    'content'    => t('Check Files'),
                                    'class'      => 'btn btn--secondary',
                                    'attributes' => ['@click' => 'checkFiles()'],
                                ],
                                'fix'   => [
                                    'content'    => t('Fix Found Issues'),
                                    'class'      => 'btn btn--secondary',
                                    'attributes' => [
                                        '@click'    => 'confirmFixIssues()',
                                        ':disabled' => '!checkResultsReady',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'type'          => 'group',
                    'name'          => 'images',
                    'label'         => t('Duplicate Files'),
                    'class'         => '',
                    'label_class'   => '',
                    'content_class' => 'dg ga-4 g-6 gtc-1',
                    'fields'        => [
                        [
                            'type'        => 'button-group',
                            'name'        => 'images[merge_duplicates]',
                            'label'       => t('Merge Duplicate Files'),
                            'class'       => '',
                            'label_class' => '',
                            'before'      => '',
                            'after'       => '',
                            'instruction' => t('Finds duplicates by SHA-256 hash and merges them into one file. Run a dry run and check the result before removing extra copies. Old copies are removed in a separate step after verification.'),
                            'tooltip'     => '',
                            'conditions'  => [],
                            'options'     => [
                                'merge'   => [
                                    'content'    => t('Merge Duplicate Files'),
                                    'class'      => 'btn btn--secondary',
                                    'attributes' => ['@click' => 'startBulkOperation("deduplicate")'],
                                ],
                                'dry_run' => [
                                    'content'    => t('Dry Run'),
                                    'class'      => 'btn btn--outline',
                                    'attributes' => ['@click' => 'startBulkOperation("deduplicate", true)'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
        [
            'type'     => 'custom',
            'callback' => function () {
                ?>
                <div class="expansa-form-actions">
                    <div class="expansa-form-actions-caption">
                        <i class="ph ph-warning-circle"></i> <?php echo t('Unsaved changes'); ?>
                    </div>
                    <div class="expansa-form-actions-buttons">
                        <button class="btn t-red" type="reset"><?php echo t('Discard'); ?></button>
                        <button class="btn t-green" type="submit"><?php echo t('Save'); ?></button>
                    </div>
                </div>
                <?php
            },
        ],
    ]
);
