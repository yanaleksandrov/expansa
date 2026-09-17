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
	<aside class="chat-sidebar">
		<button type="button" class="btn btn--primary chat-sidebar-new" u-bind="newProcessButton">
			<i class="ph ph-plus"></i> <?php echo t( 'New process' ); ?>
		</button>

		<div class="chat-sidebar-title"><?php echo t( 'Processes' ); ?></div>

		<div class="chat-sidebar-list">
			<button type="button" class="chat-sidebar-item" u-each="(process, i) in processes" u-bind="processItem(process)">
				<i class="ph ph-chat-circle-text"></i>
				<span class="chat-sidebar-item-body">
					<span class="chat-sidebar-item-title" u-bind="processTitle(process)"></span>
					<span class="chat-sidebar-item-time" u-bind="processTime(process)"></span>
				</span>
			</button>
		</div>
	</aside>

	<section class="chat-main">
		<div class="chat-progress" u-bind="progressBar">
			<div class="chat-progress-bar"></div>
		</div>

		<div class="chat-messages">
			<div class="chat-empty" u-bind="emptyState">
				<i class="ph ph-sparkle chat-empty-icon"></i>
				<h6><?php echo t( 'Start of the process' ); ?></h6>
				<div class="fs-14 t-muted"><?php echo t( 'Ask a question to continue this process' ); ?></div>
			</div>

			<div class="chat-message" u-each="(message, i) in activeMessages" u-bind="messageItem(message)">
				<div class="chat-message-avatar">
					<i u-bind="messageAvatar(message)"></i>
				</div>
				<div class="chat-message-text" u-bind="messageText(message)"></div>
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
</div>
