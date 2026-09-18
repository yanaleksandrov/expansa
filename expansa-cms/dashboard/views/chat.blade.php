<?php
/**
 * AI assistant chat template can be overridden by copying it to themes/yourtheme/dashboard/views/chat.php
 *
 * @package Expansa\Templates
 */
if ( ! defined( 'EX_PATH' ) ) {
	exit;
}
?>
<div class="chat" u-data="chat">
	<section class="chat-main">
		<div class="chat-progress" u-bind="progressBar">
			<div class="chat-progress-bar"></div>
		</div>

		<div class="chat-messages">
			<div class="chat-empty" u-bind="emptyState">
				<?php
				echo view(
                    'global/state',
					[
						'icon'        => 'ufo',
						'title'       => t('Start of the process'),
						'description' => t('Ask a question to continue this process'),
					]
				)->render();
				?>
			</div>

			<div class="chat-message" u-each="(message, i) in activeMessages" u-bind="messageItem(message)">
				<div class="chat-message-avatar">
					<i u-bind="messageAvatar(message)"></i>
				</div>
				<div class="chat-message-body">
					<div class="chat-message-text" u-bind="messageText(message)"></div>
					<div class="chat-message-time" u-bind="messageTime(message)"></div>
				</div>
			</div>
		</div>

		<div class="chat-thinking" u-bind="thinkingIndicator">
			<div class="chat-message-avatar">
				<i class="ph ph-sparkle"></i>
			</div>
			<div class="chat-thinking-body">
				<div class="chat-thinking-dots"><span></span><span></span><span></span></div>
				<div class="chat-thinking-status" u-bind="thinkingText"></div>
			</div>
		</div>

		<div class="chat-composer">
			<textarea class="chat-composer-input" rows="1" placeholder="<?php echo t_attr( 'Message the assistant...' ); ?>" u-bind="composerInput"></textarea>
			<button type="button" class="btn btn--primary chat-composer-send" u-bind="sendButton">
				<i class="ph ph-paper-plane-tilt"></i>
			</button>
			<button type="button" class="btn btn--outline chat-composer-stop" u-bind="stopButton">
				<i class="ph ph-stop"></i>
			</button>
		</div>
	</section>

	<aside class="chat-sidebar">
		<button type="button" class="btn btn--primary chat-sidebar-new" u-bind="newProcessButton">
			<i class="ph ph-plus"></i> <?php echo t( 'New process' ); ?>
		</button>

		<div class="chat-sidebar-title"><?php echo t( 'Processes' ); ?></div>

		<div class="chat-sidebar-list">
			<div class="chat-sidebar-item" u-each="(process, i) in visibleProcesses" u-bind="processRow(process)">
				<span class="chat-sidebar-item-status">
					<span class="chat-sidebar-item-status-label" u-bind="processStatusLabel(process)"></span>
					<span class="chat-sidebar-item-status-badge" u-bind="processStatusBadge(process)">
						<i u-bind="processStatusGlyph(process)"></i>
					</span>
				</span>

				<button type="button" class="chat-sidebar-item-main" u-bind="processSelectButton(process)">
					<span class="chat-sidebar-item-title" u-bind="processTitle(process)"></span>
				</button>

				<input type="text" class="chat-sidebar-item-rename" u-bind="processRenameInput(process)">

				<details class="details chat-sidebar-item-menu" @click.outside="$el.removeAttribute('open')">
					<summary class="btn btn--icon btn--xs chat-sidebar-item-menu-btn" title="<?php echo t_attr( 'Actions' ); ?>">
						<i class="ph ph-dots-three-vertical"></i>
					</summary>
					<div class="details-content">
						<ul class="user-menu">
							<li class="user-menu-item">
								<button type="button" class="user-menu-link" u-bind="renameProcessButton(process)">
									<i class="ph ph-pencil-simple"></i> <?php echo t( 'Rename' ); ?>
								</button>
							</li>
							<li class="user-menu-item">
								<button type="button" class="user-menu-link" u-bind="archiveProcessButton(process)">
									<i class="ph ph-archive"></i> <?php echo t( 'Archive' ); ?>
								</button>
							</li>
							<li class="user-menu-item">
								<button type="button" class="user-menu-link" u-bind="deleteProcessButton(process)">
									<i class="ph ph-trash"></i> <?php echo t( 'Delete' ); ?>
								</button>
							</li>
						</ul>
					</div>
				</details>
			</div>
		</div>
	</aside>
</div>
