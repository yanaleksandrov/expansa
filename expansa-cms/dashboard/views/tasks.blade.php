<?php

use Expansa\View;

/**
 * Expansa dashboard tools.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/tasks.php
 *
 * @package Expansa\Templates
 */
if ( ! defined( 'EX_PATH' ) ) {
	exit;
}
?>
<div class="expansa-main">
    <?php
	echo view(
		'table/header',
		[
			'title' => t( 'Tasks' ),
		]
	);
    ?>
    <div class="kanban">
        <div class="kanban__wrapper">
            <div class="kanban__col">
                <div class="kanban__title">To Do <span class="badge badge--azure-lt ml-auto">Add task</span> <span class="badge ml-2">3</span></div>
                <div class="kanban__item">
                    <div class="kanban__name">
                        CEO plugin
                        <div class="kanban__meta">
                            <span><i class="ph ph-chats"></i> 4</span> <span><i class="ph ph-folder-notch-open"></i> 4</span>
                        </div>
                    </div>
                    <div class="kanban__img">
                        <img src="https://placeimg.com/300/150/hello" width="300" height="150" alt="Post image">
                    </div>
                    <div class="kanban__progress">
                        <div class="progress" x-progress.100.0.25.1000ms></div>
                        <div class="fs-12 t-muted mt-1 df aic">Progress <span class="ml-auto">25%</span></div>
                    </div>
                    <div class="kanban__status">
                        <span class="badge badge--red-lt">High</span><span class="ml-auto fs-12 t-muted">Due in 2 days</span>
                    </div>
                </div>
                <div class="kanban__item">
                    <div class="kanban__name">Translate backend</div>
                    <div class="kanban__progress">
                        <div class="progress" x-progress.100.0.75.1000ms></div>
                        <div class="fs-12 t-muted mt-1 df aic">Progress <span class="ml-auto">75%</span></div>
                    </div>
                    <div class="kanban__status">
                        <span class="badge badge--orange-lt">Medium</span><span class="ml-auto fs-12 t-muted">Due in 5 days</span>
                    </div>
                </div>
                <div class="kanban__item">
                    <div class="kanban__name">Make design</div>
                    <div class="kanban__progress">
                        <div class="progress" x-progress.100.0.96.1000ms></div>
                        <div class="fs-12 t-muted mt-1 df aic">Progress <span class="ml-auto">96%</span></div>
                    </div>
                    <div class="kanban__status">
                        <span class="badge badge--green-lt">Low</span><span class="ml-auto fs-12 t-muted">Due in 7 days</span>
                    </div>
                </div>
            </div>
            <div class="kanban__col">
                <div class="kanban__title">In Progress <span class="badge ml-auto">1</span></div>
                <div class="kanban__item">
                    <div class="kanban__name">Translate backend</div>
                    <div class="kanban__progress">
                        <div class="progress" x-progress.100.20.80.1000ms></div>
                        <div class="fs-12 t-muted mt-1 df aic">Progress <span class="ml-auto">75%</span></div>
                    </div>
                    <div class="kanban__status">
                        <span class="badge badge--orange-lt">Medium</span><span class="ml-auto fs-12 t-muted">Due in 5 days</span>
                    </div>
                </div>
            </div>
            <div class="kanban__col">
                <div class="kanban__title">Review <span class="badge ml-auto">0</span></div>
            </div>
            <div class="kanban__col">
                <div class="kanban__title">Closed <span class="badge ml-auto">1</span></div>
                <div class="kanban__item">
                    <div class="kanban__name">Make design</div>
                    <div class="kanban__progress">
                        <div class="progress" x-progress.100.0.96.1000ms></div>
                        <div class="fs-12 t-muted mt-1 df aic">Progress <span class="ml-auto">96%</span></div>
                    </div>
                    <div class="kanban__status">
                        <span class="badge badge--green-lt">Low</span><span class="ml-auto fs-12 t-muted">Due in 7 days</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
