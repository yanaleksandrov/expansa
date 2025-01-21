<?php
use Expansa\Url;

/**
 * Form for create & edit posts.
 *
 * @since 2025.1
 */

?>
<div class="dg g-6 gtc-12">
    <div class="dg ga-8">
        <?php
        echo Dashboard\Form::build(
            [
                [
                    'type'        => 'textarea',
                    'name'        => 'title',
                    'label'       => '',
                    'class'       => '',
                    'label_class' => '',
                    'reset'       => 0,
                    'before'      => '',
                    'after'       => '',
                    'instruction' => '',
                    'tooltip'     => '',
                    'copy'        => 0,
                    'validator'   => '',
                    'conditions'  => [],
                    'attributes'  => [
                        'rows'        => 1,
                        'required'    => true,
                        'placeholder' => t('Add title...'),
                    ],
                ],
                [
                    'type'        => 'text',
                    'name'        => 'permalink',
                    'label'       => '',
                    'class'       => '',
                    'label_class' => '',
                    'reset'       => 0,
                    'before'      => sprintf('<code class="badge">%s</code>', Url::site()),
                    'after'       => '',
                    'instruction' => '',
                    'tooltip'     => '',
                    'copy'        => 0,
                    'validator'   => '',
                    'conditions'  => [],
                    'attributes'  => [
                        'required' => true,
                    ],
                ],
                [
                    'type'        => 'textarea',
                    'name'        => 'excerpt',
                    'label'       => '',
                    'class'       => '',
                    'label_class' => '',
                    'reset'       => 0,
                    'before'      => '',
                    'after'       => '',
                    'instruction' => t('This section only applicable to post types that have excerpts enabled. Here you can write a one to two sentence description of the post.'),
                    'tooltip'     => '',
                    'copy'        => 0,
                    'validator'   => '',
                    'conditions'  => [],
                    'attributes'  => [
                        'rows'        => 1,
                        'value'       => '',
                        'placeholder' => t('Write an excerpt (optional)...'),
                    ],
                ],
            ]
        );
        ?>
    </div>
    <div class="dg ga-4">
        <?php
        echo Dashboard\Form::build(
            [
                [
                    'type'        => 'select',
                    'name'        => 'status',
                    'label'       => '',
                    'class'       => '',
                    'label_class' => '',
                    'reset'       => 0,
                    'before'      => '',
                    'after'       => '',
                    'instruction' => '',
                    'tooltip'     => '',
                    'copy'        => 0,
                    'validator'   => '',
                    'conditions'  => [],
                    'attributes'  => [
                        'value' => 'publish',
                    ],
                    'options'     => [
                        'publish' => t('Publish'),
                        'pending' => t('Pending'),
                        'draft'   => t('Draft'),
                    ],
                ],
                [
                    'type'        => 'select',
                    'name'        => 'visibility',
                    'label'       => '',
                    'class'       => '',
                    'label_class' => '',
                    'reset'       => 0,
                    'before'      => '',
                    'after'       => '',
                    'instruction' => '',
                    'tooltip'     => '',
                    'copy'        => 0,
                    'validator'   => '',
                    'conditions'  => [],
                    'attributes'  => [
                        'value' => 'public',
                    ],
                    'options'     => [
                        'public'  => t('Public'),
                        'private' => t('Private'),
                        'pending' => t('Password protected'),
                    ],
                ],
                [
                    'type'        => 'date',
                    'name'        => 'from',
                    'label'       => '',
                    'class'       => '',
                    'label_class' => '',
                    'reset'       => 0,
                    'before'      => t('%sFrom:%s', '<samp class="badge badge--blue-lt">', '</samp>'),
                    'after'       => '',
                    'instruction' => '',
                    'tooltip'     => '',
                    'copy'        => 0,
                    'validator'   => '',
                    'conditions'  => [],
                    'attributes'  => [
                        'placeholder' => t('e.g. Just another Expansa site'),
                    ],
                ],
                [
                    'type'        => 'date',
                    'name'        => 'to',
                    'label'       => '',
                    'class'       => '',
                    'label_class' => '',
                    'reset'       => 0,
                    'before'      => t('%sTo:%s', '<samp class="badge badge--blue-lt">', '</samp>'),
                    'after'       => '',
                    'instruction' => '',
                    'tooltip'     => '',
                    'copy'        => 0,
                    'validator'   => '',
                    'conditions'  => [],
                    'attributes'  => [
                        'placeholder' => t('e.g. Just another Expansa site'),
                    ],
                ],
                [
                    'type'        => 'select',
                    'name'        => 'language',
                    'label'       => '',
                    'class'       => '',
                    'label_class' => '',
                    'reset'       => 0,
                    'before'      => '',
                    'after'       => '',
                    'instruction' => '',
                    'tooltip'     => '',
                    'copy'        => 0,
                    'validator'   => '',
                    'conditions'  => [],
                    'attributes'  => [
                        'value'    => 'us',
                        'required' => true,
                    ],
                    'options'     => [
                        'us' => [
                            'image'   => 'assets/images/flags/us.svg',
                            'content' => t('English - english'),
                        ],
                        'ru' => [
                            'image'   => 'assets/images/flags/ru.svg',
                            'content' => t('Russian - русский'),
                        ],
                        'he' => [
                            'image'   => 'assets/images/flags/il.svg',
                            'content' => t('עִבְרִית - Hebrew'),
                        ],
                    ],
                ],
                [
                    'type'        => 'select',
                    'name'        => 'discussion',
                    'label'       => '',
                    'class'       => '',
                    'label_class' => '',
                    'reset'       => 0,
                    'before'      => '',
                    'after'       => '',
                    'instruction' => '',
                    'tooltip'     => '',
                    'copy'        => 0,
                    'validator'   => '',
                    'conditions'  => [],
                    'attributes'  => [],
                    'options'     => [
                        'open'        => t('Open'),
                        'close'       => t('Close'),
                        'temporarily' => t('Temporarily'),
                    ],
                ],
            ]
        );
        ?>
    </div>
</div>
